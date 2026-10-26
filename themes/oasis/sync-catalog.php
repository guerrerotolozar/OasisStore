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

    // Sincronizar moneda activa en sesión con la moneda base del catálogo
    try {
        if (function_exists('session') && session()->isStarted() && session('currency') !== 'COP') {
            session(['currency' => 'COP']);
        }
    } catch (\Throwable $e) {
        // Ignorar cuando se ejecuta por CLI sin sesión HTTP
    }

    $stampFile = base_path('themes/oasis/.last_synced_stamp');
    $currentStamp = ($catalog['ultima_actualizacion'] ?? 'unknown') . '|' . $feedFolder . '|v5';

    if (is_file($stampFile) && trim((string) @file_get_contents($stampFile)) === $currentStamp) {
        return $catalog;
    }

    try {
        // 1. Asegurar divisa principal (COP) y divisa secundaria (USD)
        $cop = Currency::updateOrCreate(
            ['code' => 'COP'],
            ['name' => 'Peso Colombiano', 'prefix' => '$ ', 'suffix' => ' COP', 'format' => '1.000,00']
        );
        $usd = Currency::updateOrCreate(
            ['code' => 'USD'],
            ['name' => 'US Dollar', 'prefix' => '$', 'suffix' => ' USD', 'format' => '1,000.00']
        );
        Setting::updateOrCreate(['key' => 'default_currency'], ['value' => 'COP']);

        $upsertProductWithPrice = function (
            Category $category,
            string $slug,
            string $name,
            string $description,
            float $priceCop,
            ?string $imageRelPath
        ) use ($cop, $usd): Product {
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

            $plan->prices()->updateOrCreate(
                ['currency_code' => $cop->code],
                ['price' => round($priceCop, 2), 'setup_fee' => 0.00]
            );

            $plan->prices()->updateOrCreate(
                ['currency_code' => $usd->code],
                ['price' => max(1.00, round($priceCop / 4200, 2)), 'setup_fee' => 0.00]
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

            $lines = [];
            $unitInfo = $item['unidad'] ?? ($item['avion'] ?? null);
            if (!empty($unitInfo['modelo'])) {
                $clase = $unitInfo['clase'] ?? 'SPECIAL UNIT';
                $lines[] = "**Unidad Destacada:** `{$clase} • {$unitInfo['modelo']}`";
            }
            $tierInfo = $item['nivel_cuenta'] ?? ($item['nivel_hangar'] ?? null);
            if (!empty($tierInfo['nivel'])) {
                $unlockTxt = $tierInfo['texto'] ?? 'Desbloqueo de Nivel';
                $lines[] = "**Nivel {$tierInfo['nivel']}:** *{$unlockTxt}*";
            }
            if (!empty($item['extras']['descripcion'])) {
                $lines[] = "\n" . $item['extras']['descripcion'];
            }
            if (!empty($item['extras']['incluye'])) {
                $lines[] = "\n**Incluye en tu cuenta:**";
                foreach ($item['extras']['incluye'] as $inc) {
                    $lines[] = "- {$inc}";
                }
            }
            if (!empty($item['extras']['items'])) {
                $lines[] = "\n**Contenido del Bundle:**";
                foreach ($item['extras']['items'] as $sub) {
                    if (!empty($sub['nombre'])) {
                        $lines[] = "- 🎁 **{$sub['nombre']}**";
                    }
                }
            }

            $heroImg = $item['imagenes']['principal']
                ?? ($item['imagenes']['avion_recortado'] ?? ($item['imagenes']['fondo'] ?? null));
            $imgRel = $heroImg ? $feedFolder . '/' . ltrim($heroImg, '/') : null;
            $priceCop = (float) ($item['precio']['cop'] ?? 0);

            $upsertProductWithPrice(
                $catSpecial,
                $slug,
                (string) ($item['nombre'] ?? $slug),
                implode("\n", $lines),
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
                $fpLines[] = "**Bono Web:** `{$passData['niveles_badge']}`";
            }
            if (!empty($passData['subtitulo'])) {
                $fpLines[] = $passData['subtitulo'];
            }
            if (!empty($passData['contenido_destacado'])) {
                $fpLines[] = "\n**Recompensas Destacadas del Pase:**";
                foreach ($passData['contenido_destacado'] as $feat) {
                    $fpLines[] = "- ⚡ {$feat}";
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
                "**Paquete de Divisa Virtual:** `{$g['cantidad']} Unidades`{$badge}",
                "- Acreditación directa al ID de jugador indicado",
                "- Liquidación P2P sin recargo de pasarela",
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
