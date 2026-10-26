@php
    $oasisGateway = \App\Models\Gateway::where('extension', 'OasisP2P')->where('enabled', true)->first();
    $oasisPayView = ($oasisGateway && $invoice->status === 'pending')
        ? \App\Helpers\ExtensionHelper::pay($oasisGateway, $invoice)
        : null;
    $invRef = $invoice->number ?: $invoice->id;
    $existingTicket = auth()->check()
        ? auth()->user()->tickets()
            ->where(function ($q) use ($invoice, $invRef) {
                $q->where('subject', 'like', "%Factura #{$invRef}%")
                  ->orWhere('subject', 'like', "%Factura #{$invoice->id}%");
            })
            ->latest()
            ->first()
        : null;
@endphp

<div class="container mt-8 space-y-6" @if ($checkPayment) wire:poll.5s="checkPaymentStatus" @endif>
    <!-- Pipeline P2P de 3 Pasos -->
    <div class="oasis-pipeline">
        <div class="oasis-step oasis-step-done">
            <span class="oasis-step-num">✓</span>
            <div>
                <p class="font-bold text-xs leading-tight">1. Paquete Reservado</p>
                <p class="text-[11px] text-muted">Stock bloqueado para tu cuenta</p>
            </div>
        </div>
        <div class="oasis-step oasis-step-done">
            <span class="oasis-step-num">✓</span>
            <div>
                <p class="font-bold text-xs leading-tight">2. Orden #{{ $invRef }} Emitida</p>
                <p class="text-[11px] text-muted">Folio P2P generado sin comisión</p>
            </div>
        </div>
        <div class="oasis-step oasis-step-active">
            <span class="oasis-step-num">3</span>
            <div>
                <p class="font-bold text-xs leading-tight">3. Transferencia & Comprobante</p>
                <p class="text-[11px] text-muted">
                    {{ $existingTicket ? 'Comprobante en revisión activa' : 'Envía tu captura para liberar el paquete' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Encabezado de la Factura / Orden P2P -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="oasis-mono-pill px-2.5 py-0.5 rounded-md bg-primary/15 text-primary border border-primary/30 font-bold">
                    ORDEN #{{ $invRef }}
                </span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold tracking-wider uppercase {{ $invoice->status === 'paid' ? 'bg-primary/15 text-primary border border-primary/30' : 'bg-secondary/15 text-secondary border border-secondary/30' }}">
                    <span class="size-2 rounded-full {{ $invoice->status === 'paid' ? 'bg-primary' : 'bg-secondary animate-pulse' }}"></span>
                    @if ($invoice->status === 'paid')
                        Orden Pagada • En Proceso de Entrega
                    @elseif ($existingTicket)
                        Comprobante Enviado • En Sala P2P
                    @else
                        Pendiente de Transferencia P2P
                    @endif
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-base mt-1.5">
                Caja de Liquidación P2P #{{ $invRef }}
            </h1>
        </div>

        <div class="flex items-center gap-3">
            @if ($existingTicket)
            <a href="{{ route('tickets.show', $existingTicket) }}" wire:navigate>
                <x-button.primary class="text-xs font-bold">
                    Ir a Sala de Validación #{{ $existingTicket->id }} →
                </x-button.primary>
            </a>
            @endif

            <button type="button" wire:click="downloadPDF" class="px-3.5 py-2 rounded-xl text-xs font-semibold border bg-background-secondary border-neutral text-muted hover:text-base transition flex items-center gap-2">
                <span wire:loading wire:target="downloadPDF">
                    <x-ri-loader-5-fill class="size-4 animate-spin" />
                </span>
                <span wire:loading.remove wire:target="downloadPDF">
                    Descargar Recibo PDF
                </span>
            </button>
        </div>
    </div>

    <!-- Banner de Confirmación si ya subió su comprobante -->
    @if ($existingTicket && $invoice->status === 'pending')
    <div class="oasis-terminal border-primary/40 p-5 sm:p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <h2 class="text-base sm:text-lg font-extrabold text-primary">✅ ¡Comprobante recibido en la Sala P2P #{{ $existingTicket->id }}!</h2>
            <p class="text-xs sm:text-sm text-muted">
                Tu captura ya está resguardada en bóveda privada y nuestro administrador recibió la alerta en su móvil. Entra a la sala para ver el estado en vivo.
            </p>
        </div>
        <a href="{{ route('tickets.show', $existingTicket) }}" wire:navigate class="shrink-0">
            <x-button.primary class="font-bold">
                Entrar a Sala de Orden #{{ $existingTicket->id }} →
            </x-button.primary>
        </a>
    </div>
    @endif

    <!-- Layout Dividido: Terminal P2P (Izquierda) + Voucher de Orden (Derecha Sticky) -->
    <div class="{{ ($invoice->status === 'pending' && $oasisPayView) ? 'oasis-split-layout' : 'space-y-6' }}">
        @if ($invoice->status === 'pending' && $oasisPayView)
        <div class="oasis-terminal p-6 sm:p-8 space-y-5">
            <div class="flex items-center justify-between border-b border-neutral pb-4">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-primary block">Terminal Directa OasisP2P</span>
                    <h2 class="text-xl font-extrabold text-base">Datos de Transferencia y Subida de Captura</h2>
                </div>
                <div class="text-right">
                    <span class="text-[11px] uppercase text-muted block">Transferir Exactamente</span>
                    <span class="text-xl sm:text-2xl font-extrabold text-primary oasis-mono-pill">{{ $invoice->formattedRemaining }}</span>
                </div>
            </div>

            {!! $oasisPayView !!}
        </div>
        @endif

        <!-- Voucher de la Orden (Derecha Sticky) -->
        <div class="oasis-voucher oasis-sticky-panel p-6 space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-primary block">Folio Oficial</span>
                    <h2 class="text-lg font-extrabold text-base">Detalle de la Orden</h2>
                </div>
                <span class="oasis-mono-pill px-2.5 py-1 rounded bg-background border border-neutral text-muted font-bold">
                    #{{ $invRef }}
                </span>
            </div>

            <div class="grid grid-cols-2 gap-3 text-xs pt-1">
                <div class="p-3 rounded-xl bg-background/70 border border-neutral">
                    <span class="text-muted block">Titular de Cuenta</span>
                    <span class="font-bold text-base truncate block mt-0.5">{{ $invoice->user_name }}</span>
                </div>
                <div class="p-3 rounded-xl bg-background/70 border border-neutral">
                    <span class="text-muted block">Emisión</span>
                    <span class="font-bold text-base oasis-mono-pill block mt-0.5">{{ $invoice->created_at->format('d M Y, H:i') }}</span>
                </div>
            </div>

            <div class="oasis-voucher-divider"></div>

            <!-- Paquetes Incluidos -->
            <div class="space-y-2.5">
                <span class="text-xs font-bold uppercase tracking-wider text-muted block">Paquetes en Custodia</span>
                @foreach ($invoice->items as $item)
                <div class="p-3.5 rounded-xl bg-background/80 border border-neutral flex items-center justify-between gap-3">
                    <div class="space-y-0.5">
                        <p class="font-bold text-sm text-base">{{ $item->description }}</p>
                        <p class="text-xs text-muted oasis-mono-pill">Cantidad: {{ $item->quantity }}</p>
                    </div>
                    <span class="font-extrabold text-primary oasis-mono-pill shrink-0">{{ $item->formattedTotal }}</span>
                </div>
                @endforeach
            </div>

            <div class="oasis-voucher-divider"></div>

            <!-- Totales -->
            <div class="space-y-2">
                @if ($invoice->formattedTotal->tax > 0)
                <div class="flex justify-between text-xs text-muted">
                    <span>Subtotal:</span>
                    <span class="oasis-mono-pill">{{ $invoice->formattedTotal->format($invoice->formattedTotal->subtotal) }}</span>
                </div>
                <div class="flex justify-between text-xs text-muted">
                    <span>{{ $invoice->tax->name }} ({{ $invoice->tax->rate }}%):</span>
                    <span class="oasis-mono-pill">{{ $invoice->formattedTotal->formatted->tax }}</span>
                </div>
                @endif

                <div class="flex justify-between items-baseline pt-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-muted">Total de la Orden</span>
                    <span class="text-2xl sm:text-3xl font-extrabold text-primary oasis-mono-pill">{{ $invoice->formattedTotal }}</span>
                </div>
            </div>

            @if ($invoice->transactions->isNotEmpty())
            <div class="pt-3 border-t border-neutral space-y-2">
                <span class="text-xs font-bold uppercase tracking-wider text-muted block">Pagos Acreditados</span>
                @foreach ($invoice->transactions->sortByDesc('created_at') as $transaction)
                <div class="p-3 rounded-xl bg-background border border-neutral flex items-center justify-between text-xs">
                    <div>
                        <p class="font-bold text-base">{{ $transaction->gateway?->name ?? 'Créditos' }}</p>
                        <p class="text-muted oasis-mono-pill">{{ $transaction->created_at->format('d M Y H:i') }} • Ref: {{ $transaction->transaction_id ?: 'N/A' }}</p>
                    </div>
                    <span class="font-bold text-primary oasis-mono-pill">{{ $transaction->formattedAmount }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
