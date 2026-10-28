<?php
/**
 * Script CLI de Aprovisionamiento Automático de OasisStore
 * Uso en terminal: docker compose exec paymenter php /app/themes/oasis/bootstrap-store.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: CLI execution only.');
}

if (!defined('LARAVEL_START')) {
    require_once '/app/vendor/autoload.php';
    $app = require_once '/app/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
}

@exec('composer dump-autoload -o --working-dir=/app 2>&1', $composerOut);
echo "✔ Autoloader actualizado: " . implode(' | ', $composerOut) . "\n";

use App\Models\Category;
use App\Models\Currency;
use App\Models\Gateway;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;

echo "==================================================================\n";
echo "🚀 Aprovisionando Catálogo, Pasarela OasisP2P y Prueba Arturo-Api\n";
echo "==================================================================\n";

// 1. Asegurar Tema Oasis, Logo Only, URL Oficial e Idiomas Permitidos (Solo ES y EN)
Setting::updateOrCreate(['key' => 'theme'], ['value' => 'oasis']);
Setting::updateOrCreate(['key' => 'theme_oasis_logo_display'], ['value' => 'logo-only']);
Setting::updateOrCreate(['key' => 'allowed_languages'], ['value' => json_encode(['es', 'en'])]);
$appUrl = env('APP_URL', 'http://100.103.230.95:8085');
Setting::updateOrCreate(['key' => 'app_url'], ['value' => $appUrl]);
config(['app.url' => $appUrl]);

// 2. Asegurar divisas del sistema (COP, USD, MXN)
$cop = Currency::updateOrCreate(
    ['code' => 'COP'],
    ['name' => 'Peso Colombiano', 'prefix' => '$ ', 'suffix' => ' COP', 'format' => '1.000,00']
);
$usd = Currency::updateOrCreate(
    ['code' => 'USD'],
    ['name' => 'Dólar (PayPal / Wise / USDT)', 'prefix' => '$', 'suffix' => ' USD', 'format' => '1,000.00']
);
$mxn = Currency::updateOrCreate(
    ['code' => 'MXN'],
    ['name' => 'Peso Mexicano (SPEI)', 'prefix' => '$', 'suffix' => ' MXN', 'format' => '1,000.00']
);
echo "✔ Divisas activas verificadas: COP, USD, MXN\n";

// 3. Crear o actualizar la Pasarela Personalizada OasisP2P
$gateway = Gateway::updateOrCreate(
    ['extension' => 'OasisP2P', 'type' => 'gateway'],
    [
        'name' => 'Transferencia / Cripto P2P',
        'enabled' => true,
    ]
);

$defaultInstructions = <<<'MD'
### 🏦 Opción 1: Transferencia Bancaria Directa (SPEI / Nequi / Bancolombia)
- **Beneficiario:** OasisStore Digital
- **Cuenta / CLABE:** `0000 0000 0000 0000 00` *(Edita este dato en Admin -> Gateways)*
- **Concepto:** `Factura #{invoice_id}`

### 🪙 Opción 2: Criptomonedas (USDT - Red TRC20 / BEP20)
- **Wallet USDT (TRC20):** `TXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX` *(Edita tu wallet en Admin -> Gateways)*
- **Importe exacto:** El indicado en tu factura

> Una vez realizado el pago, adjunta tu captura o PDF en el formulario inferior para abrir tu ticket de validación inmediata.
MD;

$gatewaySettings = [
    'hold_service_pending_after_paid' => true,
    'spei_enabled' => true,
    'spei_bank' => 'STP / BBVA / Nu México',
    'spei_clabe' => '0000 0000 0000 0000 00',
    'spei_holder' => 'OasisStore Digital',
    'crypto_enabled' => true,
    'crypto_network' => 'USDT (Red TRC20 / BEP20)',
    'crypto_address' => 'TXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
    'instructions_note' => 'Coloca el número de tu Factura en el concepto de pago y adjunta tu comprobante aquí mismo. Se abrirá un ticket privado vinculado a tu orden.',
];
foreach ($gatewaySettings as $k => $v) {
    $gateway->settings()->updateOrCreate(['key' => $k], ['value' => $v]);
}
echo "✔ Pasarela P2P activada y configurada: {$gateway->name} (ID: {$gateway->id})\n";

// 4. Crear Categoría inicial si no existe
$category = Category::updateOrCreate(
    ['slug' => 'recargas-digitales'],
    [
        'name' => 'Recargas & Créditos Digitales',
        'description' => 'Paquetes digitales, monedas in-game y microtransacciones con validación P2P directa.',
    ]
);
echo "✔ Categoría lista: {$category->name} (/products/{$category->slug})\n";

// 5. Crear Producto de demostración con Plan One-Time y Precio en USD
$product = Product::updateOrCreate(
    ['slug' => 'pack-1000-creditos-oasis', 'category_id' => $category->id],
    [
        'name' => 'Pack 1,000 Créditos Oasis + Bonus',
        'description' => "**Incluye:**\n- 1,000 Créditos Digitales + 100 Bonus\n- Entrega manual verificada (Estado *Pending Setup* -> *Active*)\n- Soporte directo por Ticket vinculado a tu factura",
        'stock' => 50,
        'per_user_limit' => 0,
        'allow_quantity' => 'separated',
    ]
);

$plan = $product->plans()->where('type', 'one-time')->first();
if (!$plan) {
    $plan = $product->plans()->create([
        'name' => 'Pago Único',
        'type' => 'one-time',
        'billing_period' => 1,
        'billing_unit' => 'month',
        'sort' => 1,
    ]);
}

$plan->prices()->updateOrCreate(
    ['currency_code' => $cop->code],
    [
        'price' => 40000.00,
        'setup_fee' => 0.00,
    ]
);
$plan->prices()->updateOrCreate(
    ['currency_code' => $usd->code],
    [
        'price' => 10.00,
        'setup_fee' => 0.00,
    ]
);
$plan->prices()->updateOrCreate(
    ['currency_code' => $mxn->code],
    [
        'price' => 190.00,
        'setup_fee' => 0.00,
    ]
);
echo "✔ Producto de muestra listo: {$product->name} (COP, USD, MXN)\n";

// 6. Limpiar caché de Laravel/Paymenter
\App\Providers\SettingsProvider::flushCache();
\Illuminate\Support\Facades\Artisan::call('optimize:clear');
echo "✔ Caché de vistas y configuración limpiada.\n";

// 7. Probar Webhook firmado HMAC hacia oasis-webhook -> Arturo-Api
$webhookUrl = env('OASIS_WEBHOOK_URL', 'http://oasis-webhook:8000/webhook/paymenter');
$secret = env('OASIS_WEBHOOK_SECRET', '');
if ($webhookUrl && $secret) {
    $appUrl = rtrim(config('app.url', 'http://localhost:8085'), '/');
    $payload = [
        'event' => 'p2p.receipt_submitted',
        'timestamp' => now()->toIso8601String(),
        'invoice' => [
            'id' => 1001,
            'number' => 'OASIS-1001',
            'status' => 'pending',
            'total' => '10.00',
            'currency_code' => 'USD',
            'user_name' => 'Administrador Oasis',
            'user_email' => 'admin@oasisstore.local',
        ],
        'dashboard_links' => [
            'admin_invoice_url' => "{$appUrl}/admin/invoices/1001/edit",
            'admin_ticket_url' => "{$appUrl}/admin/tickets/1/edit",
        ],
        'meta' => [
            'payment_method' => 'Transferencia / Cripto P2P (Test CLI)',
            'reference' => 'TEST-ARTURO-CLI-001',
            'order_state' => 'Pending Setup (Validación P2P)',
        ],
    ];
    $rawBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $sig = hash_hmac('sha256', $rawBody, $secret);

    try {
        $health = Http::timeout(5)->get('http://oasis-webhook:8000/health');
        echo "✔ Healthcheck oasis-webhook: " . $health->body() . "\n";

        $resp = Http::timeout(10)->withHeaders([
            'Content-Type' => 'application/json',
            'X-Oasis-Signature' => $sig,
        ])->withBody($rawBody, 'application/json')->post($webhookUrl);

        echo "✔ Test Webhook -> oasis-webhook -> Arturo-Api: HTTP {$resp->status()} | {$resp->body()}\n";
    } catch (\Throwable $e) {
        echo "⚠ Error al contactar oasis-webhook: " . $e->getMessage() . "\n";
    }
}

echo "==================================================================\n";
echo "✅ Todo listo. Catálogo disponible en: " . config('app.url') . "\n";
echo "==================================================================\n";
