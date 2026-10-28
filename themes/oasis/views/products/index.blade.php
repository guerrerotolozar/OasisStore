@php
    require_once base_path('themes/oasis/helpers.php');
    $catalog = $oasisCatalog ?? (@include(base_path('themes/oasis/sync-catalog.php')));
    $assetPrefix = $catalog['__asset_prefix'] ?? '/storage/catalog-feed';
    $specialMeta = $catalog['categories']['special'] ?? ($catalog['categories']['bundles'] ?? []);
    $goldMeta = $catalog['categories']['creditos'] ?? ($catalog['categories']['oro'] ?? []);
@endphp

<div class="container mt-8 space-y-8">
    <!-- Hero Banner de la Categoría -->
    <div class="oasis-terminal p-6 sm:p-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div class="space-y-3 max-w-2xl">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold tracking-wider uppercase bg-primary/15 text-primary border border-primary/30">
                    <span class="size-2 rounded-full bg-primary animate-pulse"></span>
                    {{ oasis_t('CATÁLOGO SINCRONIZADO • LIQUIDACIÓN P2P') }}
                </span>
                <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-base">
                    {{ oasis_t($category->name) }}
                </h1>
                @if(theme('show_category_description', true) && $category->description)
                <article class="prose dark:prose-invert text-muted max-w-none text-sm">
                    {!! oasis_t($category->description) !!}
                </article>
                @endif
            </div>

            <!-- Pipeline Compacto -->
            <div class="grid grid-cols-3 gap-3 bg-background/80 border border-neutral rounded-xl p-4 text-center shrink-0">
                <div class="px-2">
                    <span class="oasis-mono-pill font-bold text-primary block">{{ oasis_t('PASO 1') }}</span>
                    <span class="text-xs text-muted mt-0.5 block">{{ oasis_t('Elige tu Pack') }}</span>
                </div>
                <div class="px-2 border-x border-neutral">
                    <span class="oasis-mono-pill font-bold text-secondary block">{{ oasis_t('PASO 2') }}</span>
                    <span class="text-xs text-muted mt-0.5 block">{{ oasis_t('SPEI / USDT') }}</span>
                </div>
                <div class="px-2">
                    <span class="oasis-mono-pill font-bold text-primary block">{{ oasis_t('PASO 3') }}</span>
                    <span class="text-xs text-muted mt-0.5 block">{{ oasis_t('Sala P2P') }}</span>
                </div>
            </div>
        </div>

        @if (count($categories) > 1)
        <div class="mt-6 pt-5 border-t border-neutral flex flex-wrap gap-2">
            @foreach ($categories as $ccategory)
            <a href="{{ route('category.show', ['category' => $ccategory->slug]) }}"
               wire:navigate
               class="px-3.5 py-1.5 rounded-lg text-xs font-semibold border transition {{ $category->id == $ccategory->id ? 'bg-primary text-white border-primary' : 'bg-background border-neutral text-muted hover:text-base' }}">
                {{ oasis_t($ccategory->name) }}
            </a>
            @endforeach
        </div>
        @endif
    </div>

    <!-- Grid de Paquetes del Catálogo -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($products as $product)
        @php
            $sMeta = $specialMeta[$product->slug] ?? null;
            $unitMeta = $sMeta['unidad'] ?? ($sMeta['avion'] ?? []);
            $gKey = str_replace('-', '_', $product->slug);
            $gMeta = $goldMeta[$gKey] ?? null;
            $bgImg = !empty($sMeta['imagenes']['fondo']) ? $assetPrefix . '/' . ltrim($sMeta['imagenes']['fondo'], '/') : null;
            $glowImg = !empty($sMeta['imagenes']['brillo']) ? $assetPrefix . '/' . ltrim($sMeta['imagenes']['brillo'], '/') : null;
            $displayPrice = $product->price()->formatted->price;
        @endphp
        <div class="oasis-terminal flex flex-col justify-between overflow-hidden hover:border-primary transition-all duration-200 group">
            <div>
                <div class="relative h-48 w-full bg-background/80 border-b border-neutral flex items-center justify-center overflow-hidden"
                     @if($bgImg) style="background-image: url('{{ $bgImg }}'); background-size: cover; background-position: center;" @endif>
                    @if($bgImg)
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-black/40"></div>
                    @endif
                    @if($glowImg)
                    <img src="{{ $glowImg }}" alt="" class="absolute inset-0 w-full h-full object-contain opacity-75 pointer-events-none" />
                    @endif
                    @if ($product->image)
                    <img src="{{ Storage::url($product->image) }}"
                         alt="{{ $product->name }}"
                         loading="lazy"
                         class="relative z-10 max-h-40 w-auto object-contain group-hover:scale-105 transition-transform duration-300 drop-shadow-lg">
                    @endif

                    <div class="absolute top-3 left-3 right-3 z-20 flex items-center justify-between gap-2">
                        <span class="oasis-mono-pill px-2.5 py-0.5 rounded bg-black/75 text-primary border border-primary/40 font-bold backdrop-blur-sm">
                            {{ oasis_t($unitMeta['modelo'] ?? ($gMeta['etiqueta'] ?? 'ENTREGA P2P')) }}
                        </span>
                        <span class="oasis-mono-pill px-2.5 py-0.5 rounded-full bg-black/75 border border-primary/40 text-primary font-bold">
                            ● {{ oasis_t('Disponible') }}
                        </span>
                    </div>
                </div>

                <div class="p-5 space-y-3">
                    <h2 class="text-lg font-extrabold text-base group-hover:text-primary transition">
                        {{ oasis_t($product->name) }}
                    </h2>

                    @if ($product->description)
                    <article class="prose dark:prose-invert prose-xs text-muted line-clamp-4 pt-1 border-t border-neutral/60 [&_ul]:my-1 [&_ul]:space-y-0.5 [&_li]:text-[11px] [&_strong]:text-base [&_code]:text-primary [&_code]:bg-primary/10 [&_code]:px-1 [&_code]:py-0.2 [&_code]:rounded">
                        {!! Str::markdown(oasis_t($product->description)) !!}
                    </article>
                    @endif
                </div>
            </div>

            <div class="p-5 pt-0 mt-auto space-y-3">
                <div class="pt-4 border-t border-neutral/60 flex items-baseline justify-between">
                    <div>
                        <span class="text-[11px] uppercase tracking-wider text-muted block">{{ oasis_t('Precio Oficial') }}</span>
                        <span class="text-[11px] text-primary font-semibold">{{ oasis_t('Sin recargo P2P') }}</span>
                    </div>
                    <span class="text-xl font-extrabold text-primary oasis-mono-pill">
                        {{ $displayPrice }}
                    </span>
                </div>

                @if ($product->stock !== 0 && $product->price()->available)
                <a href="{{ route('products.checkout', ['category' => $product->category, 'product' => $product->slug]) }}"
                   wire:navigate class="block">
                    <x-button.primary class="w-full py-3 font-extrabold text-xs">
                        {{ oasis_t('Adquirir en Terminal P2P →') }}
                    </x-button.primary>
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
