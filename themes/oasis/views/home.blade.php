@php
    require_once base_path('themes/oasis/helpers.php');
    $catalog = $oasisCatalog ?? (@include(base_path('themes/oasis/sync-catalog.php')));
    $assetPrefix = $catalog['__asset_prefix'] ?? '/storage/catalog-feed';
    $specialMeta = $catalog['categories']['special'] ?? ($catalog['categories']['bundles'] ?? []);
    $goldMeta = $catalog['categories']['creditos'] ?? ($catalog['categories']['oro'] ?? []);
    $passMeta = $catalog['categories']['pase_temporada'] ?? ($catalog['categories']['pase_de_vuelo'] ?? null);
    $lastSync = !empty($catalog['ultima_actualizacion'])
        ? \Carbon\Carbon::parse($catalog['ultima_actualizacion'])->format('d M Y • H:i')
        : null;

    $specialProducts = \App\Models\Product::with('category', 'plans.prices')
        ->where('hidden', false)
        ->whereIn('slug', array_keys($specialMeta))
        ->get();

    $passProduct = \App\Models\Product::with('category', 'plans.prices')
        ->where('hidden', false)
        ->whereIn('slug', ['season-pass-activo', 'flight-pass-temporada'])
        ->first();

    $goldSlugs = array_map(fn($k) => str_replace('_', '-', (string) $k), array_keys($goldMeta));
    $goldProducts = \App\Models\Product::with('category', 'plans.prices')
        ->where('hidden', false)
        ->whereIn('slug', $goldSlugs)
        ->get()
        ->sortBy(fn($p) => (int) filter_var($p->slug, FILTER_SANITIZE_NUMBER_INT));
@endphp

<div class="container mt-8 space-y-12">
    <!-- Hero Terminal OasisStore -->
    <div class="oasis-terminal p-6 sm:p-10 relative overflow-hidden">
        <div class="oasis-split-layout items-center">
            <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold tracking-wider uppercase bg-primary/15 text-primary border border-primary/30">
                        <span class="size-2 rounded-full bg-primary animate-pulse"></span>
                        {{ oasis_t('CATÁLOGO SINCRONIZADO • LIQUIDACIÓN P2P DIRECTA') }}
                    </span>
                    @if($lastSync)
                    <span class="oasis-mono-pill px-2.5 py-1 rounded-full bg-background border border-neutral text-muted font-bold">
                        🔄 {{ oasis_t('Actualizado') }}: {{ $lastSync }}
                    </span>
                    @endif
                </div>

                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-base leading-tight">
                    {{ oasis_t($catalog['titulo_tienda'] ?? 'Tienda Digital de Paquetes, Pases & Créditos') }}
                </h1>

                <p class="text-sm sm:text-base text-muted leading-relaxed max-w-2xl">
                    {{ oasis_t($catalog['subtitulo_tienda'] ?? 'Catálogo sincronizado en tiempo real con las ofertas rotativas activas. Selecciona cualquier bundle promocional, Pase de Temporada o paquete de divisa virtual y liquídalo directamente en moneda local o USDT con 0% de comisión de pasarela.') }}
                </p>

                <div class="pt-2 flex flex-wrap items-center gap-3">
                    <a href="#ofertas-especiales" class="inline-block">
                        <x-button.primary class="!w-auto px-6 py-3 font-extrabold text-sm">
                            {{ oasis_t('Ver Bundles Rotativos ↓') }}
                        </x-button.primary>
                    </a>
                    <a href="#boveda-monedas"
                       class="px-5 py-3 rounded-xl text-xs font-bold border bg-background border-neutral text-base hover:border-primary transition">
                        🪙 {{ oasis_t('Ir a Bóveda de Monedas') }}
                    </a>
                </div>
            </div>

            <!-- Tarjeta Derecha: Destacado Pase de Temporada Activo -->
            @if($passProduct && $passMeta)
            <div class="oasis-voucher p-6 space-y-4">
                <div class="flex items-center justify-between gap-2">
                    <span class="oasis-mono-pill px-2.5 py-1 rounded bg-primary/15 text-primary border border-primary/30 font-bold uppercase">
                        ⚡ {{ oasis_t($passMeta['niveles_badge'] ?? 'PASE DE TEMPORADA') }}
                    </span>
                    <span class="oasis-mono-pill text-muted font-bold">{{ oasis_t('TEMPORADA ACTUAL') }}</span>
                </div>

                <div class="flex items-center gap-4">
                    @php
                        $fpImgs = array_values(array_filter($passMeta['imagenes'] ?? []));
                    @endphp
                    @if(!empty($fpImgs[0]))
                    <img src="{{ $assetPrefix }}/{{ ltrim($fpImgs[0], '/') }}"
                         alt="{{ $passProduct->name }}"
                         class="w-20 h-20 object-contain rounded-xl bg-background/80 p-1.5 border border-neutral shrink-0">
                    @endif
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-secondary block">{{ oasis_t('Pase de Temporada') }}</span>
                        <h2 class="text-xl font-extrabold text-base">{{ $passMeta['nombre'] }}</h2>
                        <p class="text-xs text-muted mt-0.5">{{ oasis_t($passMeta['subtitulo'] ?? '') }}</p>
                    </div>
                </div>

                @if(!empty($passMeta['contenido_destacado']))
                <div class="flex flex-wrap gap-1.5 pt-1">
                    @foreach(array_slice($passMeta['contenido_destacado'], 0, 6) as $feat)
                    <span class="px-2 py-0.5 rounded-md bg-background/80 border border-neutral text-[11px] font-semibold text-muted">
                        • {{ oasis_t($feat) }}
                    </span>
                    @endforeach
                </div>
                @endif

                <div class="oasis-voucher-divider"></div>

                <div class="flex items-center justify-between gap-4">
                    <div>
                        <span class="text-[11px] uppercase text-muted block">{{ oasis_t('Precio Oficial') }}</span>
                        <span class="text-2xl font-extrabold text-primary oasis-mono-pill">
                            {{ $passProduct->price()->formatted->price }}
                        </span>
                    </div>
                    <a href="{{ route('products.checkout', ['category' => $passProduct->category, 'product' => $passProduct->slug]) }}" wire:navigate>
                        <x-button.primary class="!w-auto px-5 py-2.5 font-extrabold text-xs">
                            {{ oasis_t('Adquirir Pase →') }}
                        </x-button.primary>
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- SECCIÓN 1: OFERTAS ESPECIALES ROTATIVAS (Con Capas de Imagen Fondo + Brillo + Figura Recortada) -->
    @if($specialProducts->isNotEmpty())
    <div id="ofertas-especiales" class="space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
            <div>
                <span class="oasis-mono-pill text-primary font-bold uppercase">{{ oasis_t('ROTACIÓN ACTIVA • TIEMPO LIMITADO') }}</span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-base mt-1">{{ oasis_t('Ofertas Especiales & Bundles Destacados') }}</h2>
            </div>
            <a href="{{ route('category.show', ['category' => 'ofertas-especiales']) }}" wire:navigate
               class="text-xs font-bold text-primary hover:underline">
                {{ oasis_t('Ver categoría completa →') }}
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            @foreach($specialProducts as $product)
            @php
                $meta = $specialMeta[$product->slug] ?? [];
                $unitMeta = $meta['unidad'] ?? ($meta['avion'] ?? []);
                $tierMeta = $meta['nivel_cuenta'] ?? ($meta['nivel_hangar'] ?? []);
                $bgImg = !empty($meta['imagenes']['fondo']) ? $assetPrefix . '/' . ltrim($meta['imagenes']['fondo'], '/') : null;
                $glowImg = !empty($meta['imagenes']['brillo']) ? $assetPrefix . '/' . ltrim($meta['imagenes']['brillo'], '/') : null;
                $heroRel = $meta['imagenes']['principal'] ?? ($meta['imagenes']['avion_recortado'] ?? null);
                $heroImg = !empty($heroRel) ? $assetPrefix . '/' . ltrim($heroRel, '/') : null;
                $expira = !empty($meta['expira_en']) ? \Carbon\Carbon::parse($meta['expira_en'])->format('d M, H:i') : null;
            @endphp
            <div class="oasis-terminal flex flex-col justify-between overflow-hidden group hover:border-primary transition-all duration-200">
                <div>
                    <!-- Showcase Visual Multicapa del Bundle -->
                    <div class="relative h-52 w-full overflow-hidden border-b border-neutral bg-background flex items-center justify-center"
                         @if($bgImg) style="background-image: url('{{ $bgImg }}'); background-size: cover; background-position: center;" @endif>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/25 to-black/40"></div>

                        @if($glowImg)
                        <img src="{{ $glowImg }}" alt="" class="absolute inset-0 w-full h-full object-contain opacity-75 pointer-events-none" />
                        @endif

                        @if($heroImg)
                        <img src="{{ $heroImg }}"
                             alt="{{ $product->name }}"
                             loading="lazy"
                             class="relative z-10 max-h-44 w-auto object-contain drop-shadow-[0_12px_24px_rgba(0,0,0,0.85)] group-hover:scale-105 transition-transform duration-300" />
                        @endif

                        <!-- Badges Superiores -->
                        <div class="absolute top-3 left-3 right-3 z-20 flex items-center justify-between gap-2">
                            @if(!empty($unitMeta['modelo']))
                            <span class="oasis-mono-pill px-2.5 py-1 rounded-md bg-black/75 text-white border border-white/20 font-bold backdrop-blur-md">
                                ⚡ {{ oasis_t($unitMeta['clase'] ?? '') }} • {{ $unitMeta['modelo'] }}
                            </span>
                            @else
                            <span class="oasis-mono-pill px-2.5 py-1 rounded-md bg-black/75 text-primary border border-primary/40 font-bold backdrop-blur-md">
                                {{ oasis_t('BUNDLE ESPECIAL') }}
                            </span>
                            @endif

                            @if(!empty($tierMeta['nivel']))
                            <span class="oasis-mono-pill px-2.5 py-1 rounded-md bg-primary text-white font-extrabold shadow">
                                {{ oasis_t('NV. ' . $tierMeta['nivel'] . ' UNLOCK') }}
                            </span>
                            @endif
                        </div>

                        @if($expira)
                        <div class="absolute bottom-2.5 right-3 z-20">
                            <span class="oasis-mono-pill px-2 py-0.5 rounded bg-black/80 text-amber-300 border border-amber-400/30 text-[11px] font-bold">
                                ⏳ {{ oasis_t('Expira') }}: {{ $expira }}
                            </span>
                        </div>
                        @endif
                    </div>

                    <!-- Cuerpo del Bundle -->
                    <div class="p-5 space-y-3">
                        <h3 class="text-xl font-extrabold text-base group-hover:text-primary transition">
                            {{ oasis_t($product->name) }}
                        </h3>

                        @if(!empty($meta['extras']['descripcion']))
                        <p class="text-xs text-muted leading-relaxed">
                            {{ oasis_t($meta['extras']['descripcion']) }}
                        </p>
                        @endif

                        <!-- Ítems Visuales Incluidos en el Bundle -->
                        @if(!empty($meta['extras']['items']))
                        <div class="pt-2 space-y-1.5">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-muted block">{{ oasis_t('Contenido del Bundle:') }}</span>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach(array_slice($meta['extras']['items'], 0, 4) as $subItem)
                                <div class="flex items-center gap-2 p-1.5 rounded-lg bg-background/80 border border-neutral text-xs">
                                    @if(!empty($subItem['imagen']))
                                    <img src="{{ $assetPrefix }}/{{ ltrim($subItem['imagen'], '/') }}"
                                         alt="{{ $subItem['nombre'] }}"
                                         class="size-8 object-contain rounded shrink-0 bg-black/30 p-0.5" />
                                    @else
                                    <span class="size-8 rounded bg-primary/15 text-primary flex items-center justify-center text-xs shrink-0">🎁</span>
                                    @endif
                                    <span class="font-semibold text-base text-[11px] leading-tight line-clamp-2">{{ oasis_t($subItem['nombre']) }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Precio y Acción Directa P2P -->
                <div class="p-5 pt-0 mt-auto">
                    <div class="pt-4 border-t border-neutral/60 flex items-center justify-between gap-3 mb-3">
                        <div>
                            <span class="text-[11px] uppercase tracking-wider text-muted block">{{ oasis_t('Precio Oficial') }}</span>
                            <span class="text-[11px] text-primary font-bold">{{ oasis_t('0% comisión P2P') }}</span>
                        </div>
                        <span class="text-xl sm:text-2xl font-extrabold text-primary oasis-mono-pill">
                            {{ $product->price()->formatted->price }}
                        </span>
                    </div>

                    <a href="{{ route('products.checkout', ['category' => $product->category, 'product' => $product->slug]) }}"
                       wire:navigate class="block">
                        <x-button.primary class="w-full py-3 font-extrabold text-xs">
                            {{ oasis_t('Adquirir Bundle en Terminal P2P →') }}
                        </x-button.primary>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- SECCIÓN 2: BÓVEDA DE MONEDAS & CRÉDITOS -->
    @if($goldProducts->isNotEmpty())
    <div id="boveda-monedas" class="space-y-5 pt-4 border-t border-neutral/50">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
            <div>
                <span class="oasis-mono-pill text-secondary font-bold uppercase">{{ oasis_t('RECARGAS OFICIALES • ACREDITACIÓN DIRECTA') }}</span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-base mt-1">{{ oasis_t('Bóveda de Monedas & Divisa Virtual') }}</h2>
            </div>
            <a href="{{ route('category.show', ['category' => 'boveda-monedas']) }}" wire:navigate
               class="text-xs font-bold text-primary hover:underline">
                {{ oasis_t('Ver todos los paquetes →') }}
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($goldProducts as $product)
            @php
                $gKey = str_replace('-', '_', $product->slug);
                $gData = $goldMeta[$gKey] ?? [];
                $gImg = !empty($gData['imagen']) ? $assetPrefix . '/' . ltrim($gData['imagen'], '/') : null;
                $gTag = $gData['etiqueta'] ?? null;
            @endphp
            <div class="oasis-terminal p-5 flex flex-col justify-between hover:border-primary transition-all duration-200 group">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-2">
                        <span class="oasis-mono-pill px-2.5 py-0.5 rounded bg-primary/15 text-primary border border-primary/30 font-bold">
                            🪙 {{ $gData['cantidad'] ?? '' }} {{ oasis_t('UNIDADES') }}
                        </span>
                        @if($gTag)
                        <span class="oasis-mono-pill px-2.5 py-0.5 rounded-full bg-secondary/20 text-secondary border border-secondary/30 font-extrabold">
                            ★ {{ oasis_t($gTag) }}
                        </span>
                        @endif
                    </div>

                    <div class="h-36 rounded-xl bg-background/80 border border-neutral flex items-center justify-center p-3 overflow-hidden">
                        @if($gImg)
                        <img src="{{ $gImg }}"
                             alt="{{ $product->name }}"
                             loading="lazy"
                             class="max-h-32 w-auto object-contain group-hover:scale-105 transition-transform duration-300 drop-shadow" />
                        @endif
                    </div>

                    <div>
                        <h3 class="text-lg font-extrabold text-base group-hover:text-primary transition">
                            {{ oasis_t($product->name) }}
                        </h3>
                        <p class="text-xs text-muted mt-0.5">{{ oasis_t('Acreditación directa a tu ID de jugador') }}</p>
                    </div>
                </div>

                <div class="pt-4 mt-4 border-t border-neutral/60 space-y-3">
                    <div class="flex items-baseline justify-between">
                        <span class="text-xs text-muted">{{ oasis_t('Importe P2P:') }}</span>
                        <span class="text-xl font-extrabold text-primary oasis-mono-pill">
                            {{ $product->price()->formatted->price }}
                        </span>
                    </div>

                    <a href="{{ route('products.checkout', ['category' => $product->category, 'product' => $product->slug]) }}"
                       wire:navigate class="block">
                        <x-button.primary class="w-full py-2.5 font-extrabold text-xs">
                            {{ oasis_t('Recargar ' . $product->name . ' →') }}
                        </x-button.primary>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {!! hook('pages.home') !!}
</div>
