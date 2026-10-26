<?php

namespace Paymenter\Extensions\Gateways\OasisP2P;

use App\Attributes\ExtensionMeta;
use App\Classes\Extension\Gateway;
use App\Events\Invoice\Paid as InvoicePaid;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

#[ExtensionMeta(
    name: 'OasisP2P',
    description: 'Pasarela P2P (Transferencia SPEI México y Criptomonedas) con adjuntos privados en Ticket, retención Pending Setup y alertas Webhook.',
    version: '1.1.0',
    author: 'OasisStore'
)]
class OasisP2P extends Gateway
{
    /**
     * Registra rutas, namespace de vistas y escucha del evento oficial Invoice\Paid.
     */
    public function boot(): void
    {
        require __DIR__ . '/routes.php';
        View::addNamespace('gateways.oasisp2p', __DIR__ . '/resources/views');

        // Invoice\Paid se dispara dentro de DB::afterCommit inmediatamente después de ProcessPaidInvoiceService
        Event::listen(InvoicePaid::class, function (InvoicePaid $event) {
            $invoice = $event->invoice->loadMissing(['items.reference', 'user']);
            $holdPending = $this->config('hold_service_pending_after_paid') !== false;

            $heldServices = [];
            if ($holdPending) {
                foreach ($invoice->items as $item) {
                    if ($item->reference_type === Service::class && $item->reference instanceof Service) {
                        // Mantener el servicio en STATUS_PENDING ("Pending Setup") hasta entrega manual en el Dashboard
                        $item->reference->update(['status' => Service::STATUS_PENDING]);
                        $heldServices[] = $item->reference->id;
                    }
                }
            }

            $this->dispatchWebhookAsync('invoice.paid', $invoice, [
                'order_state' => $holdPending ? 'Pending Setup (Retenido para entrega manual)' : 'Active',
                'service_ids' => $heldServices,
            ]);
        });
    }

    /**
     * Campos configurables desde Admin Dashboard -> Extensions -> Gateways -> OasisP2P.
     */
    public function getConfig($values = []): array
    {
        return [
            [
                'name' => 'hold_service_pending_after_paid',
                'label' => 'Retener Servicio en "Pending" tras Marcar Factura Pagada (Entrega Manual)',
                'type' => 'checkbox',
                'default' => true,
                'description' => 'Evita que el servicio pase a Active automáticamente al pagar la factura, permitiéndote entregar la microtransacción manualmente y activarlo desde el Dashboard.',
            ],
            [
                'name' => 'spei_enabled',
                'label' => 'Habilitar Transferencia México (SPEI)',
                'type' => 'checkbox',
                'default' => true,
            ],
            [
                'name' => 'spei_bank',
                'label' => 'Banco Receptor (México)',
                'type' => 'text',
                'default' => 'STP / BBVA / Nu México',
                'required' => false,
            ],
            [
                'name' => 'spei_clabe',
                'label' => 'CLABE Interbancaria (18 dígitos)',
                'type' => 'text',
                'default' => '000000000000000000',
                'required' => false,
            ],
            [
                'name' => 'spei_holder',
                'label' => 'Beneficiario / Titular de la Cuenta',
                'type' => 'text',
                'default' => 'OasisStore Digital',
                'required' => false,
            ],
            [
                'name' => 'crypto_enabled',
                'label' => 'Habilitar Criptomonedas (USDT / Binance Pay)',
                'type' => 'checkbox',
                'default' => true,
            ],
            [
                'name' => 'crypto_network',
                'label' => 'Red Cripto Aceptada',
                'type' => 'text',
                'default' => 'USDT (Red TRC20 / BEP20)',
                'required' => false,
            ],
            [
                'name' => 'crypto_address',
                'label' => 'Dirección de Wallet / Binance Pay ID',
                'type' => 'text',
                'default' => 'TXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                'required' => false,
            ],
            [
                'name' => 'instructions_note',
                'label' => 'Instrucciones adicionales para el comprador',
                'type' => 'textarea',
                'default' => 'Coloca el número de tu Factura en el concepto de pago y adjunta tu comprobante aquí mismo. Se abrirá un ticket privado vinculado a tu orden.',
                'required' => false,
            ],
        ];
    }

    public function canUseGateway($total, $currency, $type, $items = []): bool
    {
        return $total > 0;
    }

    /**
     * Renderiza el formulario P2P dentro del modal de pago de la factura (`invoices/partials/payment-modal.blade.php`).
     */
    public function pay(Invoice $invoice, $total)
    {
        return view('gateways.oasisp2p::pay', [
            'invoice' => $invoice,
            'total' => $invoice->formattedRemaining,
            'spei' => [
                'enabled' => (bool) $this->config('spei_enabled'),
                'bank' => (string) $this->config('spei_bank'),
                'clabe' => (string) $this->config('spei_clabe'),
                'holder' => (string) $this->config('spei_holder'),
            ],
            'crypto' => [
                'enabled' => (bool) $this->config('crypto_enabled'),
                'network' => (string) $this->config('crypto_network'),
                'address' => (string) $this->config('crypto_address'),
            ],
            'instructions_note' => (string) $this->config('instructions_note'),
        ]);
    }

    /**
     * Recibe el comprobante subido por el cliente, lo almacena en el disco privado `tickets/uploads`
     * mediante el modelo nativo `TicketMessageAttachment` (protegido por `TicketMessageAttachmentPolicy`),
     * crea el Ticket vinculado al Servicio/Factura y dispara el webhook en segundo plano.
     */
    public static function submitReceipt(Request $request, Invoice $invoice)
    {
        if ($invoice->status !== Invoice::STATUS_PENDING) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', 'Esta factura ya no se encuentra pendiente de pago.');
        }

        $rateKey = sprintf('oasisp2p-receipt:%d:%d', $invoice->id, $request->user()->id);
        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', 'Has enviado demasiados comprobantes recientemente. Espera unos minutos.');
        }
        RateLimiter:: hit($rateKey, 600);

        $validated = $request->validate([
            'payment_channel' => ['required', 'in:spei_mexico,crypto'],
            'transaction_ref' => ['nullable', 'string', 'max:120'],
            'player_identifier' => ['nullable', 'string', 'max:120'],
            'receipt_file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:8192', // Máximo 8 MB
            ],
        ]);
        $validated['transaction_ref'] = !empty($validated['transaction_ref'])
            ? $validated['transaction_ref']
            : 'Verificada en captura adjunta';

        $file = $request->file('receipt_file');
        $safeExtension = strtolower($file->guessExtension() ?: 'png');
        $newName = Str::ulid() . '.' . $safeExtension;
        $relativePath = 'tickets/uploads/' . $newName;

        // Guardar en el almacenamiento PRIVADO nativo de tickets de Paymenter (nunca en public/)
        $file->storeAs('tickets/uploads', $newName);
        $fullPath = storage_path('app/' . $relativePath);

        $channelLabel = $validated['payment_channel'] === 'spei_mexico'
            ? 'Transferencia México (SPEI)'
            : 'Criptomonedas (USDT / Crypto)';

        $invoiceLabel = $invoice->number ?: $invoice->id;
        $serviceId = $invoice->items()
            ->where('reference_type', Service::class)
            ->value('reference_id');

        [$ticket, $attachment] = DB::transaction(function () use (
            $request,
            $invoice,
            $invoiceLabel,
            $serviceId,
            $channelLabel,
            $validated,
            $relativePath,
            $fullPath,
            $safeExtension,
            $file
        ) {
            // `subject` es el campo fillable real en App\Models\Ticket
            $ticket = Ticket::create([
                'user_id' => $request->user()->id,
                'subject' => sprintf('[Comprobante P2P] Factura #%s — %s', $invoiceLabel, $channelLabel),
                'priority' => 'high',
                'status' => 'open',
                'service_id' => $serviceId,
            ]);

            $messageBody = implode("\n", array_filter([
                "**Comprobante de Pago P2P recibido para validación**",
                "- **Factura vinculada:** #{$invoiceLabel} (ID interno: `{$invoice->id}`)",
                $serviceId ? "- **Servicio vinculado:** #{$serviceId}" : null,
                "- **Método utilizado:** {$channelLabel}",
                "- **Referencia / Clave de Rastreo / TxID:** `{$validated['transaction_ref']}`",
                !empty($validated['player_identifier'])
                    ? "- **ID de Usuario / Destino Microtransacción:** `{$validated['player_identifier']}`"
                    : null,
                "- **Estado:** `Pending Setup` (En revisión desde el Admin Dashboard)",
            ]));

            $message = TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'message' => $messageBody,
            ]);

            $attachment = $message->attachments()->create([
                'path' => $relativePath,
                'filename' => sprintf('comprobante-factura-%s.%s', $invoiceLabel, $safeExtension),
                'mime_type' => File::mimeType($fullPath) ?: $file->getMimeType(),
                'filesize' => File::size($fullPath) ?: $file->getSize(),
            ]);

            return [$ticket, $attachment];
        });

        (new self())->dispatchWebhookAsync('p2p.receipt_uploaded', $invoice, [
            'ticket_id' => $ticket->id,
            'service_id' => $serviceId,
            'attachment_uuid' => $attachment->uuid,
            'payment_channel' => $channelLabel,
            'transaction_ref' => $validated['transaction_ref'],
            'player_identifier' => $validated['player_identifier'] ?? null,
            'order_state' => 'Pending Setup',
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Comprobante adjuntado de forma segura. Tu orden está en revisión.');
    }

    /**
     * Despacha el webhook fuera del ciclo de respuesta HTTP para no bloquear al usuario ni al Admin.
     */
    protected function dispatchWebhookAsync(string $eventType, Invoice $invoice, array $extra = []): void
    {
        $webhookUrl = $_ENV['OASIS_WEBHOOK_URL'] ?? getenv('OASIS_WEBHOOK_URL') ?: 'http://oasis-webhook:8000/webhook/paymenter';
        $webhookSecret = $_ENV['OASIS_WEBHOOK_SECRET'] ?? getenv('OASIS_WEBHOOK_SECRET') ?: '';

        if (empty($webhookSecret)) {
            return;
        }

        $baseUrl = rtrim(config('app.url'), '/');
        $serviceId = $extra['service_id'] ?? ($extra['service_ids'][0] ?? null);

        $payload = [
            'event' => $eventType,
            'timestamp' => now()->toIso8601String(),
            'invoice' => [
                'id' => $invoice->id,
                'number' => $invoice->number ?: (string) $invoice->id,
                'status' => $invoice->status,
                'total' => (string) $invoice->total,
                'currency_code' => $invoice->currency_code ?? 'MXN',
                'user_name' => optional($invoice->user)->name ?? 'Cliente',
                'user_email' => optional($invoice->user)->email ?? 'sin-correo',
            ],
            'dashboard_links' => [
                'admin_invoice_url' => "{$baseUrl}/admin/invoices/{$invoice->id}/edit",
                'admin_ticket_url' => isset($extra['ticket_id'])
                    ? "{$baseUrl}/admin/tickets/{$extra['ticket_id']}/edit"
                    : "{$baseUrl}/admin/tickets",
                'admin_service_url' => $serviceId
                    ? "{$baseUrl}/admin/services/{$serviceId}/edit"
                    : null,
                'protected_receipt_url' => isset($extra['attachment_uuid'])
                    ? route('tickets.attachments.show', $extra['attachment_uuid'])
                    : null,
            ],
            'meta' => $extra,
        ];

        dispatch(function () use ($webhookUrl, $webhookSecret, $payload) {
            $rawJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $signature = hash_hmac('sha256', $rawJson, $webhookSecret);

            try {
                Http::timeout(5)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'X-Oasis-Signature' => $signature,
                    ])
                    ->withBody($rawJson, 'application/json')
                    ->post($webhookUrl);
            } catch (\Throwable $e) {
                Log::warning("OasisP2P Webhook error: {$e->getMessage()}");
            }
        })->afterResponse();
    }
}
