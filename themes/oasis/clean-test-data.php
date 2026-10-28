<?php
/**
 * Script de Purga y Puesta a Cero de Datos Transaccionales en OasisStore
 * 
 * Uso vía terminal en el servidor:
 * docker compose exec paymenter php /app/themes/oasis/clean-test-data.php
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

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "====================================================================\n";
echo "🧹 Purga de Transacciones de Prueba & Reset a Cero de Tablas\n";
echo "====================================================================\n";

$tablesToTruncate = [
    'invoice_transactions',
    'invoice_items',
    'invoices',
    'orders',
    'service_properties',
    'service_configs',
    'services',
    'ticket_attachments',
    'ticket_messages',
    'tickets',
    'cart_items',
    'carts',
    'notifications',
];

DB::statement('SET FOREIGN_KEY_CHECKS=0;');

foreach ($tablesToTruncate as $table) {
    if (Schema::hasTable($table)) {
        DB::table($table)->truncate();
        echo "✔ Tabla limpiada y contador reiniciado: {$table}\n";
    }
}

DB::statement('SET FOREIGN_KEY_CHECKS=1;');

echo "\n✨ Estado resultante del Panel de Administración:\n";
echo "- Facturas (Invoices): 0\n";
echo "- Órdenes (Orders): 0\n";
echo "- Servicios (Services): 0\n";
echo "- Tickets de Soporte & Comprobantes: 0\n";
echo "- Carritos Activos: 0\n";
echo "\n🔒 Preservados intactos:\n";
echo "- Usuarios Administradores (Tu cuenta y contraseña se mantienen)\n";
echo "- Catálogo de Productos, Categorías y Precios\n";
echo "- Divisas (COP, USD, MXN) y Ajustes de Pasarela OasisP2P\n";
echo "====================================================================\n";
