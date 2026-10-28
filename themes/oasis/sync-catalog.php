<?php
/**
 * Motor de Ingesta y Sincronización de Catálogo JSON -> OasisStore (Paymenter)
 * Detecta automáticamente el archivo `catalogo.json` exportado por el contenedor
 * `catalog-scraper` dentro de `/app/storage/app/public/<feed>/catalogo.json` y
 * sincroniza Categorías, Productos, Imágenes y Precios de forma idempotente.
 */

use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use App\Models\Setting;

return (function (): ?array {
    $preferredPath = storage_path('app/public/catalog-feed/catalogo.json');
    $catalogPath = is_file($preferredPath) ? $preferredPath : null;

    if (!$catalogPath) {
        $matches = glob(storage_path('app/public/*/catalogo.json')) ?: [];
        $catalogPath = $matches[0] ?? null;
    }

    if (!$catalogPath || !is_file($catalogPath)) {
        return null;
    }

    $feedFolder = basename(dirname($catalogPath));
    $raw = @file_get_contents($catalogPath);
    if (!$raw) {
        return null;
    }

    $catalog = json_decode($raw, true);
    if (!is_array($catalog) || empty($catalog['categories'])) {
        return null;
    }

    $catalog['__asset_prefix'] = '/storage/' . $feedFolder;
    $catalog['__feed_folder'] = $feedFolder;

    // Sincronizar divisa inicial con el carrito existente o la configuración oficial de la tienda
    try {
        if (function_exists('session') && session()->isStarted() && !session()->has('currency')) {
            $cart = \App\Classes\Cart::getOnce();
            $initialCurrency = ($cart && $cart->exists && !empty($cart->currency_code))
                ? $cart->currency_code
                : config('settings.default_currency', 'COP');
            session(['currency' => $initialCurrency]);
        }
    } catch (\Throwable $e) {
        // Ignorar en CLI
    }

    $stampFile = base_path('themes/oasis/.last_synced_stamp');
    $currentStamp = ($catalog['ultima_actualizacion'] ?? 'unknown') . '|' . $feedFolder . '|v7_multicurrency_clean_gamer_desc';

    if (is_file($stampFile) && trim((string) @file_get_contents($stampFile)) === $currentStamp) {
        return $catalog;
    }

    try {
        // 1. Registrar Divisas Multimoneda Oficiales (Respetando ediciones del Admin Panel)
        $cop = Currency::firstOrCreate(
            ['code' => 'COP'],
            ['name' => 'Peso Colombiano', 'prefix' => '$ ', 'suffix' => ' COP', 'format' => '1.000,00']
        );
        $usd = Currency::firstOrCreate(
            ['code' => 'USD'],
            ['name' => 'Dólar', 'prefix' => '$', 'suffix' => ' USD', 'format' => '1,000.00']
        );
        $mxn = Currency::firstOrCreate(
            ['code' => 'MXN'],
            ['name' => 'Peso Mexicano', 'prefix' => '$', 'suffix' => ' MXN', 'format' => '1,000.00']
        );
        Setting::updateOrCreate(['key' => 'default_currency'], ['value' => 'COP']);

        // Diccionario de traducción y maquetación amigable para gamers
        $gamerTranslations = [
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

        $translateText = function (string $text) use ($gamerTranslations): string {
            return str_ireplace(array_keys($gamerTranslations), array_values($gamerTranslations), $text);
        };

        $upsertProductWithPrice = function (
            Category $category,
            string $slug,
            string $name,
            string $description,
            float $priceCop,
            ?string $imageRelPath
        ) use ($cop, $usd, $mxn): Product {
            $product = Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $category->id,
                    'name' => $name,
                    'description' => $description,
                    'image' => $imageRelPath,
                    'stock' => 99,
                    'per_user_limit' => 0,
                    'allow_quantity' => 'combined',
                    'hidden' => false,
                ]
            );

            $plan = $product->plans()->where('type', 'one-time')->first();
            if (!$plan) {
                $plan = $product->plans()->create([
                    'name' => 'Entrega Directa P2P',
                    'type' => 'one-time',
                    'billing_period' => 1,
                    'billing_unit' => 'month',
                    'sort' => 1,
                ]);
            }

            // 1. Precio COP (Base oficial del juego)
            $plan->prices()->updateOrCreate(
                ['currency_code' => $cop->code],
                ['price' => round($priceCop, 2), 'setup_fee' => 0.00]
            );

            // 2. Precio USD (Con margen comercial ~6% para cubrir comisiones de pasarelas internacionales PayPal / Wise)
            $priceUsd = max(1.00, round(($priceCop / 4050) * 1.06, 2));
            $plan->prices()->updateOrCreate(
                ['currency_code' => $usd->code],
                ['price' => $priceUsd, 'setup_fee' => 0.00]
            );

            // 3. Precio MXN (SPEI México a tasa representativa ~215 COP por 1 MXN)
            $priceMxn = max(10.00, round($priceCop / 215, 2));
            $plan->prices()->updateOrCreate(
                ['currency_code' => $mxn->code],
                ['price' => $priceMxn, 'setup_fee' => 0.00]
            );

            return $product;
        };

        $activeSlugs = [];

        // 2. Categoría 1: Ofertas Especiales & Bundles Rotativos
        $catSpecial = Category::updateOrCreate(
            ['slug' => 'ofertas-especiales'],
            [
                'name' => 'Ofertas Especiales & Bundles',
                'description' => 'Paquetes promocionales de tiempo limitado con desbloqueo directo de equipamiento, unidades y cosméticos.',
            ]
        );

        $specialItems = $catalog['categories']['special'] ?? ($catalog['categories']['bundles'] ?? []);
        foreach ($specialItems as $key => $item) {
            $slug = (string) $key;
            $activeSlugs[] = $slug;

            $descLines = [];
            $unitInfo = $item['unidad'] ?? ($item['avion'] ?? null);
            $tierInfo = $item['nivel_cuenta'] ?? ($item['nivel_hangar'] ?? null);

            if (!empty($unitInfo['modelo'])) {
                $clase = $translateText($unitInfo['clase'] ?? 'Caza');
                $nivelHangar = !empty($tierInfo['nivel']) ? " • Hangar Nivel {$tierInfo['nivel']}" : '';
                $descLines[] = "✈️ **{$clase} • {$unitInfo['modelo']}{$nivelHangar}**";
            }

            if (!empty($item['extras']['descripcion'])) {
                $cleanDesc = trim($translateText($item['extras']['descripcion']));
                if ($cleanDesc) {
                    $descLines[] = "\n{$cleanDesc}\n";
                }
            }

            $perks = [];
            if (!empty($item['extras']['incluye'])) {
                foreach ($item['extras']['incluye'] as $inc) {
                    $perks[] = "- ⚡ " . $translateText($inc);
                }
            }
            if (!empty($item['extras']['items'])) {
                foreach ($item['extras']['items'] as $sub) {
                    if (!empty($sub['nombre'])) {
                        $itemName = $translateText($sub['nombre']);
                        $bullet = "- 🎁 **{$itemName}**";
                        if (!in_array($bullet, $perks)) {
                            $perks[] = $bullet;
                        }
                    }
                }
            }

            if (!empty($perks)) {
                $descLines[] = "**Contenido del Paquete:**\n" . implode("\n", array_slice($perks, 0, 8));
            }

            $heroImg = $item['imagenes']['principal']
                ?? ($item['imagenes']['avion_recortado'] ?? ($item['imagenes']['fondo'] ?? null));
            $imgRel = $heroImg ? $feedFolder . '/' . ltrim($heroImg, '/') : null;
            $priceCop = (float) ($item['precio']['cop'] ?? 0);

            $upsertProductWithPrice(
                $catSpecial,
                $slug,
                (string) ($item['nombre'] ?? $slug),
                implode("\n", $descLines),
                $priceCop,
                $imgRel
            );
        }

        // 3. Categoría 2: Pase de Temporada
        $catPass = Category::updateOrCreate(
            ['slug' => 'pase-de-vuelo'],
            [
                'name' => 'Pase de Temporada',
                'description' => 'Pase de temporada activo con recompensas instantáneas y niveles de progresión adicional.',
            ]
        );

        $passData = $catalog['categories']['pase_temporada'] ?? ($catalog['categories']['pase_de_vuelo'] ?? null);
        if (!empty($passData)) {
            $slug = 'season-pass-activo';
            $activeSlugs[] = $slug;

            $fpLines = [];
            if (!empty($passData['niveles_badge'])) {
                $fpLines[] = "⭐ **Bono de Temporada:** `{$passData['niveles_badge']}`";
            }
            if (!empty($passData['subtitulo'])) {
                $fpLines[] = "\n" . $passData['subtitulo'] . "\n";
            }
            if (!empty($passData['contenido_destacado'])) {
                $fpLines[] = "**Recompensas Destacadas:**";
                foreach ($passData['contenido_destacado'] as $feat) {
                    $fpLines[] = "- ⚡ " . $translateText($feat);
                }
            }

            $fpImgs = array_values(array_filter($passData['imagenes'] ?? []));
            $fpImgRel = !empty($fpImgs[0]) ? $feedFolder . '/' . ltrim($fpImgs[0], '/') : null;

            $upsertProductWithPrice(
                $catPass,
                $slug,
                'Pase de Temporada: ' . ($passData['nombre'] ?? 'Edición Actual'),
                implode("\n", $fpLines),
                (float) ($passData['precio']['cop'] ?? 41570.49),
                $fpImgRel
            );
        }

        // 4. Categoría 3: Bóveda de Monedas & Créditos
        $catGold = Category::updateOrCreate(
            ['slug' => 'boveda-monedas'],
            [
                'name' => 'Bóveda de Monedas & Créditos',
                'description' => 'Paquetes de divisa virtual con acreditación directa a tu ID de jugador.',
            ]
        );

        $currencyItems = $catalog['categories']['creditos'] ?? ($catalog['categories']['oro'] ?? []);
        foreach ($currencyItems as $key => $g) {
            $slug = str_replace('_', '-', (string) $key);
            $activeSlugs[] = $slug;

            $badge = !empty($g['etiqueta']) ? " • {$g['etiqueta']}" : '';
            $goldLines = [
                "🪙 **Paquete de Monedas:** `{$g['cantidad']} Unidades`{$badge}",
                "- ⚡ Acreditación directa al ID de jugador indicado",
                "- 🛡️ Liquidación P2P directa sin intermediarios",
            ];

            $gImg = !empty($g['imagen']) ? $feedFolder . '/' . ltrim($g['imagen'], '/') : null;

            $upsertProductWithPrice(
                $catGold,
                $slug,
                (string) ($g['nombre'] ?? $slug),
                implode("\n", $goldLines),
                (float) ($g['precio']['cop'] ?? 0),
                $gImg
            );
        }

        // 5. Ocultar productos antiguos o rotados y limpiar categorías obsoletas
        Product::whereNotIn('slug', $activeSlugs)->update([
            'hidden' => true,
            'category_id' => $catGold->id,
        ]);

        Category::whereNotIn('slug', ['ofertas-especiales', 'pase-de-vuelo', 'boveda-monedas'])->delete();

        \App\Providers\SettingsProvider::flushCache();
        @unlink(base_path('themes/oasis/sync_error.txt'));
        @file_put_contents($stampFile, $currentStamp);
        @chmod($stampFile, 0666);
    } catch (\Throwable $e) {
        @file_put_contents(base_path('themes/oasis/sync_error.txt'), $e->getMessage() . "\n" . $e->getTraceAsString());
    }

    return $catalog;
})();
