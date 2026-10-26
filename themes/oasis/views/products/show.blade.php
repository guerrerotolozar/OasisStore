<div class="container mt-10 space-y-8">
    <!-- Ruta de regreso a la Categoría -->
    <div class="flex items-center justify-between">
        <a href="{{ route('category.show', ['category' => $category->slug]) }}"
           wire:navigate
           class="px-3.5 py-1.5 rounded-lg text-xs font-semibold border bg-background-secondary border-neutral text-muted hover:text-base transition">
            ← Volver a {{ $category->name }}
        </a>

        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold tracking-wider uppercase bg-primary/15 text-primary border border-primary/30">
            <span class="size-2 rounded-full bg-primary animate-pulse"></span>
            Entrega Digital Verificada
        </span>
    </div>

    <!-- Tarjeta Principal del Paquete Digital -->
    <div class="bg-background-secondary border border-neutral rounded-2xl p-6 sm:p-8 shadow-lg">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
            <!-- Imagen / Arte del Paquete -->
            <div class="relative aspect-[16/10] w-full bg-background rounded-2xl overflow-hidden border border-neutral flex items-center justify-center">
                @if ($product->image)
                <img src="{{ Storage::url($product->image) }}"
                     alt="{{ $product->name }}"
                     class="w-full h-full object-cover">
                @else
                <div class="text-center space-y-2 p-6">
                    <span class="text-xs font-bold uppercase tracking-widest text-primary px-3 py-1 rounded-full bg-primary/15 border border-primary/30">
                        OASIS DIGITAL PACK
                    </span>
                    <p class="text-lg font-extrabold text-base mt-2">{{ $product->name }}</p>
                </div>
                @endif

                <div class="absolute top-3 right-3">
                    @if ($product->stock === 0)
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-red-500/20 text-red-400 border border-red-500/40 backdrop-blur-sm">
                        Agotado
                    </span>
                    @else
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-background/85 text-primary border border-primary/40 backdrop-blur-sm">
                        Disponible
                    </span>
                    @endif
                </div>
            </div>

            <!-- Información, Precio y Botón de Compra -->
            <div class="flex flex-col justify-between space-y-6">
                <div class="space-y-4">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-muted">{{ $category->name }}</span>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-base mt-1">{{ $product->name }}</h1>
                    </div>

                    <div class="p-4 rounded-xl bg-background border border-neutral flex items-baseline justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted">Precio Final</span>
                        <span class="text-2xl font-extrabold text-primary">
                            {{ $product->price()->formatted->price }}
                        </span>
                    </div>

                    @if ($product->description)
                    <article class="prose dark:prose-invert text-sm text-muted max-w-none">
                        {!! $product->description !!}
                    </article>
                    @endif
                </div>

                <!-- Pasos de Validación P2P -->
                <div class="grid grid-cols-3 gap-2 bg-background border border-neutral rounded-xl p-3 text-center">
                    <div>
                        <span class="text-xs font-bold text-primary block">1. Ordena</span>
                        <span class="text-[11px] text-muted">Genera factura</span>
                    </div>
                    <div class="border-x border-neutral">
                        <span class="text-xs font-bold text-secondary block">2. Transfiere</span>
                        <span class="text-[11px] text-muted">SPEI / Crypto</span>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-primary block">3. Recibe</span>
                        <span class="text-[11px] text-muted">Activación P2P</span>
                    </div>
                </div>

                @if ($product->stock !== 0 && $product->price()->available)
                <a href="{{ route('products.checkout', ['category' => $category, 'product' => $product->slug]) }}"
                   wire:navigate class="block">
                    <x-button.primary class="w-full py-3 text-sm font-bold flex items-center justify-center gap-2">
                        <x-ri-shopping-bag-4-fill class="size-5" />
                        Continuar al Checkout P2P
                    </x-button.primary>
                </a>
                @endif
            </div>
        </div>
    </div>
</div>
