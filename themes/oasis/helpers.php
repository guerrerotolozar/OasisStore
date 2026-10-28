<?php
/**
 * OasisStore — Motor de Internacionalización y Diccionario Bilingüe (ES / EN)
 * Proporciona traducción en tiempo real tanto para elementos estáticos de UI
 * (header, footer, botones, badges) como para modelos dinámicos (categorías,
 * paquetes de oro, descripciones y perks de Metalstorm).
 */

class OasisTranslationDictionary
{
    private static ?array $esToEn = null;
    private static ?array $enToEs = null;

    public static function getEn(): array
    {
        if (self::$esToEn !== null) {
            return self::$esToEn;
        }

        self::$esToEn = [
            // Navegación & Categorías
            'Categorías' => 'Categories',
            'Categorias' => 'Categories',
            'Tienda' => 'Shop',
            'Inicio' => 'Home',
            'Ofertas Especiales' => 'Special Offers',
            'Ofertas Especiales & Bundles Destacados' => 'Special Offers & Featured Bundles',
            'Pase de Temporada' => 'Season Pass',
            'Pase de Vuelo' => 'Flight Pass',
            'Bóveda de Monedas' => 'Gold Vault',
            'Bóveda de Monedas & Divisa Virtual' => 'Gold Vault & Virtual Currency',
            'Bóveda de Monedas & Créditos' => 'Gold & Credits Vault',
            'Ver categoría completa →' => 'View full category →',
            'Ver todos los paquetes →' => 'View all packages →',
            'Volver al Catálogo' => 'Back to Catalog',
            '← Volver al Catálogo' => '← Back to Catalog',
            'Explorar Otros Paquetes' => 'Explore Other Packages',
            '← Explorar Otros Paquetes' => '← Explore Other Packages',

            // Hero & Badges de Tienda
            'Tienda Digital de Paquetes, Pases & Créditos' => 'Digital Store for Bundles, Passes & Credits',
            'Catálogo sincronizado en tiempo real con las ofertas rotativas activas. Selecciona cualquier bundle promocional, Pase de Temporada o paquete de divisa virtual y liquídalo directamente en moneda local o USDT con 0% de comisión de pasarela.' => 'Real-time catalog synchronized with active rotating offers. Select any promo bundle, Season Pass or virtual currency pack and settle directly in local currency or USDT with 0% gateway fee.',
            'CATÁLOGO SINCRONIZADO • LIQUIDACIÓN P2P DIRECTA' => 'SYNCHRONIZED CATALOG • DIRECT P2P SETTLEMENT',
            'CATÁLOGO SINCRONIZADO • LIQUIDACIÓN P2P' => 'SYNCHRONIZED CATALOG • P2P SETTLEMENT',
            'RECARGAS OFICIALES • ACREDITACIÓN DIRECTA' => 'OFFICIAL TOP-UPS • DIRECT CREDIT',
            'ROTACIÓN ACTIVA • TIEMPO LIMITADO' => 'ACTIVE ROTATION • LIMITED TIME',
            'BÓVEDA DIGITAL OASIS' => 'OASIS DIGITAL VAULT',
            'TEMPORADA ACTUAL' => 'CURRENT SEASON',
            'Actualizado' => 'Updated',
            'Ver Bundles Rotativos ↓' => 'View Rotating Bundles ↓',
            'Ver Bundles Rotativos !' => 'View Rotating Bundles !',
            'Ir a Bóveda de Monedas' => 'Go to Gold Vault',
            '🪙 Ir a Bóveda de Monedas' => '🪙 Go to Gold Vault',
            'Precio Oficial' => 'Official Price',
            'PRECIO OFICIAL' => 'OFFICIAL PRICE',
            '0% comisión P2P' => '0% P2P fee',
            '0% comisión pasarela' => '0% gateway fee',
            'Adquirir Pase →' => 'Get Pass →',
            'Adquirir Pase' => 'Get Pass',
            'Adquirir Bundle en Terminal P2P →' => 'Get Bundle in P2P Terminal →',
            'Adquirir en Terminal P2P →' => 'Get in P2P Terminal →',
            'Disponible' => 'Available',
            'Sin recargo P2P' => 'No P2P surcharge',
            'Contenido del Bundle:' => 'Bundle Contents:',
            'Contenido del Paquete:' => 'Package Contents:',
            'Acreditación directa a tu ID de jugador' => 'Direct credit to your Player ID',
            'Importe P2P:' => 'P2P Amount:',
            'UNIDADES' => 'UNITS',
            'BUNDLE ESPECIAL' => 'SPECIAL BUNDLE',
            'Expira' => 'Expires',

            // Selector de Moneda e Idioma
            'Moneda de Pago' => 'Payment Currency',
            'MONEDA DE PAGO' => 'PAYMENT CURRENCY',
            'Idioma' => 'Language',
            'IDIOMA' => 'LANGUAGE',
            'Seleccionar idioma' => 'Select language',
            'Peso Colombiano' => 'Colombian Peso',
            'Peso Mexicano' => 'Mexican Peso',
            'Peso Mexicano (SPEI)' => 'Mexican Peso (SPEI)',
            'Dólar' => 'US Dollar',
            'Dólar (PayPal / Wise / USDT)' => 'US Dollar (PayPal / Wise / USDT)',

            // Pipeline de 3 Pasos
            'PASO 1' => 'STEP 1',
            'PASO 2' => 'STEP 2',
            'PASO 3' => 'STEP 3',
            'Elige tu Pack' => 'Choose your Pack',
            'Sala P2P' => 'P2P Room',
            '1. Configuración de Paquete' => '1. Package Setup',
            'Revisa el contenido de tu recarga' => 'Review your top-up contents',
            '2. Emisión de Orden P2P' => '2. P2P Order Issuance',
            'Generación de folio único' => 'Unique order tracking generation',
            'Confirmación sin comisiones extra' => 'Confirmation without extra fees',
            '3. Pago & Entrega Directa' => '3. Payment & Direct Delivery',
            'SPEI / USDT + Validación inmediata' => 'SPEI / USDT + Instant validation',
            '1. Paquete Digital Elegido' => '1. Digital Package Selected',
            'Reserva lista en bóveda' => 'Reservation secured in vault',
            '1. Paquete Reservado' => '1. Package Reserved',
            'Stock bloqueado para tu cuenta' => 'Stock allocated for your account',
            '2. Orden #:ref Emitida' => '2. Order #:ref Issued',
            'Folio P2P generado sin comisión' => 'P2P order created with 0% fee',
            '3. Transferencia & Comprobante' => '3. Transfer & Receipt',
            'Envía tu captura para liberar el paquete' => 'Upload your payment receipt to deliver the pack',
            'Comprobante en revisión activa' => 'Receipt under active review',

            // Carrito & Checkout
            'TERMINAL DE ORDEN EXPRESS • 0% COMISIÓN PASARELA' => 'EXPRESS ORDER TERMINAL • 0% GATEWAY FEE',
            'Confirmación de Paquete Digital' => 'Digital Package Confirmation',
            'No tienes ninguna orden activa en la terminal' => 'You have no active orders in the terminal',
            'Selecciona un paquete de créditos o recarga digital desde el catálogo para iniciar una orden de entrega directa P2P.' => 'Select a credit pack or digital top-up from the catalog to start a direct P2P delivery order.',
            'Resumen de tu Orden' => 'Order Summary',
            'Subtotal' => 'Subtotal',
            'Total a Pagar' => 'Total to Pay',
            'Total' => 'Total',
            'Proceder al Pago' => 'Proceed to Payment',
            'Vaciar Carrito' => 'Clear Cart',
            'Aplicar Cupón' => 'Apply Coupon',
            'Código de Descuento' => 'Discount Code',
            'Código promocional' => 'Promo code',
            'Aplicar' => 'Apply',
            'Eliminar' => 'Remove',
            'Cantidad' => 'Quantity',
            'Precio unitario' => 'Unit price',
            'disponibles en bóveda' => 'available in vault',
            'Detalles y Opciones del Paquete' => 'Package Details & Options',
            'Especificaciones del Paquete' => 'Package Specifications',
            'Voucher de Reserva' => 'Reservation Voucher',
            'Continuar con la Orden →' => 'Proceed with Order →',
            'Iniciar Orden P2P →' => 'Start P2P Order →',
            'Continuar al Checkout P2P' => 'Proceed to P2P Checkout',
            'Volver a :name' => 'Back to :name',
            'Agotado' => 'Out of stock',
            'Precio Final' => 'Final Price',
            '1. Ordena' => '1. Order',
            'Genera factura' => 'Generate invoice',
            '2. Transfiere' => '2. Transfer',
            '3. Recibe' => '3. Receive',
            'Activación P2P' => 'P2P Activation',
            'Continuar al Checkout →' => 'Proceed to Checkout →',
            'Ver Paquetes Disponibles →' => 'View Available Packages →',
            'ENTREGA DIGITAL P2P' => 'P2P DIGITAL DELIVERY',
            '• Activación manual verificada' => '• Verified manual activation',
            'Importe del Paquete' => 'Package Amount',
            'c/u' => 'each',
            'Configurar' => 'Configure',
            'Descartar' => 'Remove',
            '▲ Ocultar detalles' => '▲ Hide details',
            '▼ Ver contenido del paquete' => '▼ View package contents',
            '¿Por qué usamos Liquidación Directa P2P?' => 'Why do we use Direct P2P Settlement?',
            'Bóveda Privada' => 'Private Vault',
            '1. Cero Recargos Ocultos' => '1. Zero Hidden Fees',
            'Pagas exactamente el valor del paquete vía Bancolombia/Nequi, SPEI o USDT sin tarifas fijas de pasarelas externas.' => 'You pay the exact package value via Bancolombia/Nequi, SPEI or USDT with no gateway surcharges.',
            '2. Alerta Push en Vivo' => '2. Live Push Alert',
            'Al subir tu captura, nuestro servidor alerta en menos de 2 segundos al móvil del administrador.' => 'Upon receipt upload, our server notifies the administrator within 2 seconds.',
            '3. Sala P2P Dedicada' => '3. Dedicated P2P Room',
            'Cada orden abre una sala privada con chat directo y seguimiento hasta que recibes tus créditos.' => 'Each order opens a private room with direct chat until credits are delivered.',
            'Voucher Digital' => 'Digital Voucher',
            'Resumen de Liquidación' => 'Settlement Summary',
            'Importe del paquete' => 'Package amount',
            'Comisión de pasarela P2P:' => 'P2P gateway fee:',
            '$0.00 (Bonificada)' => '$0.00 (Waived)',
            'Total Exacto a Transferir' => 'Exact Total to Transfer',
            'Rieles: Nequi/Bancolombia, SPEI 24/7 o USDT' => 'Rails: Nequi/Bancolombia, SPEI 24/7 or USDT',
            'Emitir Orden P2P y Ver Datos de Pago' => 'Issue P2P Order & View Payment Details',
            '🔒 Al continuar se generará tu folio único de pago y acceso a la sala de validación.' => '🔒 Proceeding will generate your unique tracking ID and validation room access.',
            'Canjear' => 'Redeem',
            'Quitar' => 'Remove',
            'Acepto los' => 'I accept the',
            'términos de entrega digital' => 'digital delivery terms',
            'Ingresa tu código...' => 'Enter your code...',
            '▾ Ocultar código promocional' => '▾ Hide promo code',
            '🏷️ ¿Tienes un código promocional de lanzamiento?' => '🏷️ Have a launch promo code?',
            '🏷️ Cupón activo:' => '🏷️ Active coupon:',
            'Colombia / SPEI / USDT + Validación inmediata' => 'Colombia / SPEI / USDT + Instant validation',
            'Orden Express' => 'Express Order',
            'Boleto de Reserva' => 'Reservation Ticket',
            '1 PAQUETE' => '1 PACKAGE',
            'PAQUETE' => 'PACKAGE',
            'Paquete seleccionado:' => 'Selected package:',
            'Modalidad:' => 'Payment Mode:',
            'Pago Único P2P' => 'One-Time P2P Payment',
            'Subtotal:' => 'Subtotal:',
            'Total del Paquete' => 'Package Total',
            'Preparar Orden en Terminal P2P' => 'Prepare Order in P2P Terminal',
            'Selecciona una modalidad' => 'Select an option',

            // Facturación & Sala P2P
            'Caja de Liquidación P2P' => 'P2P Settlement Desk',
            'ORDEN' => 'ORDER',
            'Orden Pagada • En Proceso de Entrega' => 'Order Paid • Processing Delivery',
            'Comprobante Enviado • En Sala P2P' => 'Receipt Submitted • In P2P Room',
            'Pendiente de Transferencia P2P' => 'Pending P2P Transfer',
            'Factura' => 'Invoice',
            'Fecha de emisión:' => 'Issue date:',
            'Fecha de vencimiento:' => 'Due date:',
            'Estado:' => 'Status:',
            'Pendiente' => 'Pending',
            'Pagado' => 'Paid',
            'Cancelado' => 'Cancelled',
            'Instrucciones de Pago' => 'Payment Instructions',
            'Adjuntar Comprobante' => 'Attach Payment Receipt',
            'Subir Captura o PDF' => 'Upload Screenshot or PDF',
            'Enviar Comprobante' => 'Submit Receipt',
            'Abrir Ticket de Soporte' => 'Open Support Ticket',

            // Footer
            'Todos los derechos reservados.' => 'All rights reserved.',
            'Todos los derechos reservados' => 'All rights reserved',
            'Transferencia P2P' => 'P2P Transfer',
            'USDT / Crypto' => 'USDT / Crypto',
            'Entrega Digital Verificada' => 'Verified Digital Delivery',

            // Paquetes Fijos de Oro
            'Oro 4000 Monedas' => '4,000 Gold Bars',
            'Oro 2000 Monedas' => '2,000 Gold Bars',
            'Oro 950 Monedas' => '950 Gold Bars',
            'Oro 360 Monedas' => '360 Gold Bars',
            'Oro 170 Monedas' => '170 Gold Bars',
            'Oro 80 Monedas' => '80 Gold Bars',

            // Dashboard del Cliente & Tickets P2P
            'Centro de Control del Cliente' => 'Client Control Center',
            'Gestiona tus entregas digitales activas, órdenes en validación manual P2P y comprobantes de pago.' => 'Manage your active digital deliveries, manual P2P orders and payment receipts.',
            'Activos' => 'Active',
            'En Revisión' => 'Under Review',
            'Por Pagar' => 'Due / Unpaid',
            'Tickets P2P' => 'P2P Tickets',
            'Mis Compras & Entregas' => 'My Orders & Deliveries',
            'Paquetes digitales activos en tu cuenta' => 'Active digital packages in your account',
            'Ver todas mis órdenes' => 'View all my orders',
            'Tickets & Comprobantes P2P' => 'P2P Tickets & Receipts',
            'Seguimiento de pagos y soporte directo' => 'Payment tracking and direct support',
            '+ Nuevo' => '+ New',
            'Ver todos mis tickets' => 'View all my tickets',
            'Facturas Pendientes de Pago' => 'Invoices Pending Payment',
            'Selecciona una factura para enviar tu comprobante P2P' => 'Select an invoice to submit your P2P receipt',
            'Ver historial de facturas' => 'View invoice history',
            '¿Cómo funciona la entrega P2P?' => 'How does P2P delivery work?',
            '1. Genera tu orden:' => '1. Place your order:',
            'Elige tu paquete en el catálogo y selecciona pago por Transferencia o Cripto.' => 'Choose your package from the catalog and select payment via Bank Transfer or Crypto.',
            '2. Adjunta tu comprobante:' => '2. Attach your receipt:',
            'Dentro de la factura podrás subir tu captura o PDF; el sistema abrirá un ticket privado automáticamente.' => 'Inside the invoice you can upload your screenshot or PDF; the system will open a private ticket automatically.',
            '3. Entrega verificada:' => '3. Verified delivery:',
            'Nuestro sistema notifica al instante al administrador para validar y activar tu producto.' => 'Our system notifies the administrator instantly to validate and activate your product.',
            '1. Orden P2P Emitida' => '1. P2P Order Issued',
            'Comprobante registrado en bóveda' => 'Receipt recorded in vault',
            '2. Sala de Validación P2P' => '2. P2P Validation Room',
            'Verificación finalizada' => 'Verification completed',
            'Revisión directa con Administrador' => 'Direct review with Administrator',
            '3. Liberación de Paquete' => '3. Package Release',
            'Activación inmediata tras validar' => 'Immediate activation after validation',
            'En Revisión Activa' => 'Active Review',
            'Orden Cerrada' => 'Order Closed',
            'Respuesta del Admin' => 'Admin Reply',
            '← Volver a Mi Panel' => '← Back to My Dashboard',
            'Comprobante Inicial' => 'Initial Receipt',
            'Cliente' => 'Client',
            'Administrador Oasis' => 'Oasis Admin',
            'SALA P2P' => 'P2P ROOM',
            'Ampliar captura' => 'Expand image',
            '🔍 Ampliar captura' => '🔍 Expand image',
            'Chat Directo con el Validador P2P' => 'Direct Chat with P2P Validator',
            'Respuesta en tiempo real' => 'Real-time response',
            'Escribe aquí cualquier dato adicional (ej. ID de jugador, nota sobre tu transferencia o consulta rápida)...' => 'Write any additional details here (e.g. Player ID, transfer note or quick question)...',
            'Adjuntar imagen / PDF' => 'Attach image / PDF',
            'Cargando archivo...' => 'Uploading file...',
            'Enviar Mensaje' => 'Send Message',
            'Ficha de Custodia P2P' => 'P2P Custody Voucher',
            'Estado actual:' => 'Current status:',
            'Validando Comprobante' => 'Validating Receipt',
            'Apertura:' => 'Opened:',
            'Paquete vinculado:' => 'Linked package:',
            'Notificación Push Enviada' => 'Push Notification Sent',
            'Tu comprobante está almacenado en bóveda privada y nuestro administrador ya recibió la alerta directa en su dispositivo móvil.' => 'Your receipt is stored in private vault and our administrator has received a direct alert on their mobile device.',
            'Marcar orden como resuelta / Cerrar sala' => 'Mark order resolved / Close room',
            'Vista ampliada del comprobante P2P' => 'Expanded view of P2P receipt',
            'Comprobante' => 'Receipt',
            '↗ Pestaña nueva' => '↗ New tab',
            '✕ Cerrar' => '✕ Close',
            'Pago P2P — Factura #' => 'P2P Payment — Invoice #',

            // Perks de Aeronaves y Bundles
            'Caza Pesado' => 'Heavy Fighter',
            'Caza Medio' => 'Medium Fighter',
            'Caza Ligero' => 'Light Fighter',
            'Interceptor' => 'Interceptor',
            'Ataque Terrestre' => 'Attack Aircraft',
            'Desbloqueo Anticipado de Hangar' => 'Early Hangar Level Unlock',
            'Aeronave en Nivel 8' => 'Aircraft Level 8',
            'Ranura de Habilidad Especial Activada' => 'Special Ability Slotted',
            'Pintura / Camuflaje Legendario F-15' => 'Legendary F-15 Paint',
            'Todas las Rutas de Mejora en Nivel 2' => 'All System Upgrade tracks Level 2',
            'Rango de Maestría Oro Desbloqueado' => 'Mastery Gold Tier Unlocked',
            'Insignia de Piloto, Banner y Calcomanía' => 'Pilot Icon, Banner and Aircraft Decal',
            'Desbloqueo de Caza + Skin Legendaria' => 'Plane Unlock and Legendary Skin',
            'Banner de Piloto Exclusivo' => 'Exclusive Pilot Banner',
            'Icono / Avatar de Piloto' => 'Pilot Icon / Avatar',
            'Calcomanía de Aeronave' => 'Aircraft Decal',
            'Domina los cielos al instante con equipamiento de élite y mejoras directas a tu cuenta:' => 'Dominate the skies instantly. The Ultimate Bundle is your fast track to elite status with the following:',
            'Caja Enorme de Suministros' => 'Huge Supply Crate',
            '200 Monedas de Oro' => '200 Gold Bars',
            '3,600 Créditos de Plata' => '3,600 Silver Credits',
            'Acelera al máximo tu progreso con este paquete de inicio único.' => 'Go full throttle with this one-time kickstart.',
        ];

        return self::$esToEn;
    }

    public static function getEs(): array
    {
        if (self::$enToEs !== null) {
            return self::$enToEs;
        }

        self::$enToEs = [
            'HEAVY FIGHTER' => 'Caza Pesado',
            'MEDIUM FIGHTER' => 'Caza Medio',
            'LIGHT FIGHTER' => 'Caza Ligero',
            'INTERCEPTOR' => 'Interceptor',
            'ATTACK' => 'Ataque Terrestre',
            'EARLY HANGAR LEVEL UNLOCK!' => 'Desbloqueo Anticipado de Hangar',
            'EARLY HANGAR LEVEL UNLOCK' => 'Desbloqueo Anticipado de Hangar',
            'Aircraft Level 8' => 'Aeronave en Nivel 8',
            'Special Ability Slotted' => 'Ranura de Habilidad Especial Activada',
            'Legendary F-15 Paint' => 'Pintura / Camuflaje Legendario F-15',
            'All System Upgrade tracks Level 2' => 'Todas las Rutas de Mejora en Nivel 2',
            'Mastery Gold Tier Unlocked' => 'Rango de Maestría Oro Desbloqueado',
            'Pilot Icon, Banner and Aircraft Decal' => 'Insignia de Piloto, Banner y Calcomanía',
            'Plane Unlock and Legendary Skin' => 'Desbloqueo de Caza + Skin Legendaria',
            'Pilot Banner' => 'Banner de Piloto Exclusivo',
            'Pilot Icon' => 'Icono / Avatar de Piloto',
            'Aircraft Decal' => 'Calcomanía de Aeronave',
            'Dominate the skies instantly. The Ultimate Bundle is your fast track to elite status with the following:' => 'Domina los cielos al instante con equipamiento de élite y mejoras directas a tu cuenta:',
            'HUGE CRATE' => 'Caja Enorme de Suministros',
            '200 GOLD' => '200 Monedas de Oro',
            '3,600 SILVER' => '3,600 Créditos de Plata',
            'Go full throttle with this one-time kickstart.' => 'Acelera al máximo tu progreso con este paquete de inicio único.',
        ];

        return self::$enToEs;
    }

    public static function translatePatternEn(string $text): string
    {
        // 1. "Oro 4000 Monedas" -> "4,000 Gold Bars"
        if (preg_match('/^Oro\s+([0-9.,]+)\s+Monedas$/i', $text, $m)) {
            $num = number_format((float) str_replace(['.', ','], '', $m[1]));
            return "{$num} Gold Bars";
        }

        // 2. "Recargar Oro 4000 Monedas →" -> "Top up 4,000 Gold Bars →"
        if (preg_match('/^Recargar\s+(.+)\s*→$/i', $text, $m)) {
            $inner = self::translatePatternEn(trim($m[1]));
            return "Top up {$inner} →";
        }

        // 3. "Recargar :name" -> "Top up :name"
        if (preg_match('/^Recargar\s+(.+)$/i', $text, $m)) {
            $inner = self::translatePatternEn(trim($m[1]));
            return "Top up {$inner}";
        }

        // 4. "NV. 14 UNLOCK" -> "LV. 14 UNLOCK"
        if (preg_match('/^NV\.\s*(\d+)\s*(.*)$/i', $text, $m)) {
            return "LV. {$m[1]} {$m[2]}";
        }

        return $text;
    }
}

if (!function_exists('oasis_t')) {
    function oasis_t(?string $text, array $replace = []): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $locale = function_exists('app') ? app()->getLocale() : 'es';

        if ($locale !== 'en') {
            // Español: Traducir del inglés al español si viniera en inglés
            $esDict = OasisTranslationDictionary::getEs();
            $translated = $esDict[$text] ?? $text;
            foreach ($replace as $k => $v) {
                $translated = str_replace(':' . $k, (string) $v, $translated);
            }
            return $translated;
        }

        // Inglés: Traducir del español al inglés
        $enDict = OasisTranslationDictionary::getEn();

        if (isset($enDict[$text])) {
            $translated = $enDict[$text];
        } else {
            $translated = OasisTranslationDictionary::translatePatternEn($text);
        }

        foreach ($replace as $k => $v) {
            $translated = str_replace(':' . $k, (string) $v, $translated);
        }

        return $translated;
    }
}

// Inyectar en el traductor oficial de Laravel para máxima compatibilidad con @lang y __()
try {
    if (function_exists('app') && app()->bound('translator')) {
        $translator = app('translator');
        $translator->addLines(OasisTranslationDictionary::getEn(), 'en');
        $translator->addLines(OasisTranslationDictionary::getEs(), 'es');
    }
} catch (\Throwable $e) {
    // Ignorar en CLI temprano
}
