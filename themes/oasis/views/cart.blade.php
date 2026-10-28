@php
    require_once base_path('themes/oasis/helpers.php');
@endphp
<div class="container mt-8 space-y-6" x-data="{ showPromo: {{ $coupon ? 'true' : 'false' }} }">
    <!-- Pipeline P2P de 3 Pasos -->
    <div class="oasis-pipeline">
        <div class="oasis-step oasis-step-done">
            <span class="oasis-step-num">✓</span>
            <div>
                <p class="font-bold text-xs leading-tight">{{ oasis_t('1. Paquete Digital Elegido') }}</p>
                <p class="text-[11px] text-muted">{{ oasis_t('Reserva lista en bóveda') }}</p>
            </div>
        </div>
        <div class="oasis-step oasis-step-active">
            <span class="oasis-step-num">2</span>
            <div>
                <p class="font-bold text-xs leading-tight">{{ oasis_t('2. Emisión de Orden P2P') }}</p>
                <p class="text-[11px] text-muted">{{ oasis_t('Confirmación sin comisiones extra') }}</p>
            </div>
        </div>
        <div class="oasis-step">
            <span class="oasis-step-num">3</span>
            <div>
                <p class="font-bold text-xs leading-tight">{{ oasis_t('3. Pago & Entrega Directa') }}</p>
                <p class="text-[11px] text-muted">{{ oasis_t('Colombia / SPEI / USDT + Validación inmediata') }}</p>
            </div>
        </div>
    </div>

    <!-- Encabezado de Terminal Express -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="oasis-mono-pill px-2.5 py-0.5 rounded-md bg-primary/15 text-primary border border-primary/30 font-bold uppercase">
                {{ oasis_t('TERMINAL DE ORDEN EXPRESS • 0% COMISIÓN PASARELA') }}
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-base mt-1.5">
                {{ oasis_t('Confirmación de Paquete Digital') }}
            </h1>
        </div>
        <a href="{{ route('home') }}" wire:navigate
           class="px-3.5 py-2 rounded-xl text-xs font-semibold border bg-background-secondary border-neutral text-muted hover:text-base transition self-start sm:self-auto">
            {{ oasis_t('← Explorar Otros Paquetes') }}
        </a>
    </div>

    @if (Cart::items()->count() === 0)
    <div class="oasis-terminal p-8 sm:p-12 text-center space-y-4">
        <div class="size-12 rounded-2xl bg-primary/15 border border-primary/30 text-primary flex items-center justify-center mx-auto text-xl font-bold">
            ⚡
        </div>
        <h2 class="text-xl font-extrabold text-base">{{ oasis_t('No tienes ninguna orden activa en la terminal') }}</h2>
        <p class="text-xs sm:text-sm text-muted max-w-md mx-auto">
            {{ oasis_t('Selecciona un paquete de créditos o recarga digital desde el catálogo para iniciar una orden de entrega directa P2P.') }}
        </p>
        <div class="pt-2">
            <a href="{{ route('home') }}" wire:navigate class="inline-block">
                <x-button.primary class="px-6 py-2.5 font-bold">
                    {{ oasis_t('Ver Paquetes Disponibles →') }}
                </x-button.primary>
            </a>
        </div>
    </div>
    @else
    <!-- Layout Dividido: Ficha del Paquete (Izquierda) + Voucher de Liquidación (Derecha Sticky) -->
    <div class="oasis-split-layout">
        <!-- Columna Izquierda: Paquete Digital Seleccionado + Garantía de Custodia -->
        <div class="space-y-5">
            @foreach (Cart::items() as $item)
            <div class="oasis-terminal p-5 sm:p-6 space-y-4">
                <div class="flex flex-col sm:flex-row justify-between gap-4">
                    <div class="space-y-1.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="oasis-mono-pill px-2 py-0.5 rounded bg-primary/15 text-primary border border-primary/30 font-bold">
                                {{ oasis_t('ENTREGA DIGITAL P2P') }}
                            </span>
                            <span class="text-xs text-muted">{{ oasis_t('• Activación manual verificada') }}</span>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-extrabold text-base">
                            {{ oasis_t($item->product->name) }}
                        </h2>
                        @if ($item->product->description)
                        <div class="pt-2 text-xs text-muted" x-data="{ expanded: false }">
                            <div :class="expanded ? '' : 'line-clamp-3 sm:line-clamp-none'" class="prose dark:prose-invert prose-xs text-muted max-w-none [&_ul]:my-1.5 [&_ul]:space-y-1 [&_li]:text-xs [&_strong]:text-base [&_code]:text-primary [&_code]:bg-primary/10 [&_code]:px-1.5 [&_code]:py-0.5 [&_code]:rounded">
                                {!! Str::markdown(oasis_t($item->product->description)) !!}
                            </div>
                            <button type="button" @click="expanded = !expanded" class="sm:hidden text-[11px] font-bold text-primary hover:underline mt-1.5 inline-flex items-center gap-1">
                                <span x-text="expanded ? '{{ oasis_t('▲ Ocultar detalles') }}' : '{{ oasis_t('▼ Ver contenido del paquete') }}'"></span>
                            </button>
                        </div>
                        @endif

                        @if(!empty($item->config_options))
                        <div class="pt-2 flex flex-wrap gap-2">
                            @foreach ($item->config_options as $option)
                            <span class="px-2.5 py-1 rounded-lg bg-background border border-neutral text-xs text-muted">
                                {{ oasis_t($option['option_name']) }}: <strong class="text-base">{{ oasis_t($option['value_name']) }}</strong>
                            </span>
                            @endforeach
                        </div>
                        @endif
                    </div>

                    <div class="flex flex-row sm:flex-col justify-between items-center sm:items-end gap-3 shrink-0 border-t sm:border-t-0 border-neutral/60 pt-3 sm:pt-0">
                        <div class=" sm:text-right">
                            <span class="text-[11px] uppercase tracking-wider text-muted block">{{ oasis_t('Importe del Paquete') }}</span>
                            <span class="text-2xl font-extrabold text-primary oasis-mono-pill">
                                {{ $item->price->format($item->price->total * $item->quantity) }}
                            </span>
                            @if ($item->quantity > 1)
                            <span class="text-xs text-muted block">({{ $item->price }} {{ oasis_t('c/u') }})</span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            @if ($item->product->allow_quantity == 'combined')
                            <div class="flex items-center gap-1 bg-background border border-neutral rounded-xl p-1">
                                <button type="button"
                                        wire:click="updateQuantity({{ $item->id }}, {{ $item->quantity - 1 }})"
                                        class="size-7 rounded-lg bg-background-secondary hover:text-primary font-bold text-sm transition">
                                    -
                                </button>
                                <span class="px-2.5 text-xs font-bold oasis-mono-pill">{{ $item->quantity }}</span>
                                <button type="button"
                                        wire:click="updateQuantity({{ $item->id }}, {{ $item->quantity + 1 }})"
                                        class="size-7 rounded-lg bg-background-secondary hover:text-primary font-bold text-sm transition">
                                    +
                                </button>
                            </div>
                            @endif

                            @if ($item->product->plans->count() > 1 || $item->product->configOptions->isNotEmpty() || !empty($item->checkout_config))
                            <a href="{{ route('products.checkout', [$item->product->category, $item->product, 'edit' => $item->id]) }}" wire:navigate
                               class="px-3 py-1.5 rounded-lg bg-background border border-neutral hover:border-primary text-xs font-semibold text-muted hover:text-base transition">
                                {{ oasis_t('Configurar') }}
                            </a>
                            @endif

                            <button type="button"
                                    wire:click="removeProduct({{ $item->id }})"
                                    class="px-3 py-1.5 rounded-lg bg-background border border-neutral hover:border-red-500/50 text-xs font-semibold text-muted hover:text-red-400 transition">
                                <span wire:loading.remove wire:target="removeProduct({{ $item->id }})">{{ oasis_t('Descartar') }}</span>
                                <span wire:loading wire:target="removeProduct({{ $item->id }})">...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach

            <!-- Sello de Confianza & Arquitectura de Entrega Oasis P2P -->
            <div class="oasis-terminal p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-primary flex items-center gap-2">
                        <span>🛡️</span> {{ oasis_t('¿Por qué usamos Liquidación Directa P2P?') }}
                    </h3>
                    <span class="oasis-mono-pill text-[11px] text-muted">{{ oasis_t('Bóveda Privada') }}</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                    <div class="p-3 rounded-xl bg-background/70 border border-neutral space-y-1">
                        <p class="font-bold text-base">{{ oasis_t('1. Cero Recargos Ocultos') }}</p>
                        <p class="text-muted leading-relaxed">{{ oasis_t('Pagas exactamente el valor del paquete vía Bancolombia/Nequi, SPEI o USDT sin tarifas fijas de pasarelas externas.') }}</p>
                    </div>
                    <div class="p-3 rounded-xl bg-background/70 border border-neutral space-y-1">
                        <p class="font-bold text-base">{{ oasis_t('2. Alerta Push en Vivo') }}</p>
                        <p class="text-muted leading-relaxed">{{ oasis_t('Al subir tu captura, nuestro servidor alerta en menos de 2 segundos al móvil del administrador.') }}</p>
                    </div>
                    <div class="p-3 rounded-xl bg-background/70 border border-neutral space-y-1">
                        <p class="font-bold text-base">{{ oasis_t('3. Sala P2P Dedicada') }}</p>
                        <p class="text-muted leading-relaxed">{{ oasis_t('Cada orden abre una sala privada con chat directo y seguimiento hasta que recibes tus créditos.') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha (Sticky): Voucher Digital de Liquidación P2P -->
        <div class="oasis-voucher oasis-sticky-panel p-6 space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-primary block">{{ oasis_t('Voucher Digital') }}</span>
                    <h2 class="text-lg font-extrabold text-base">{{ oasis_t('Resumen de Liquidación') }}</h2>
                </div>
                <span class="oasis-mono-pill px-2.5 py-1 rounded bg-background border border-neutral text-muted font-bold">
                    P2P-DIRECT
                </span>
            </div>

            <div class="oasis-voucher-divider"></div>

            <!-- Desglose Financiero -->
            <div class="space-y-2.5 text-sm">
                <div class="flex justify-between text-muted">
                    <span>{{ oasis_t('Importe del paquete') }} ({{ Cart::items()->count() }}):</span>
                    <span class="font-semibold text-base oasis-mono-pill">{{ $total->format($total->subtotal) }}</span>
                </div>
                <div class="flex justify-between text-muted">
                    <span>{{ oasis_t('Comisión de pasarela P2P:') }}</span>
                    <span class="font-bold text-primary oasis-mono-pill">{{ oasis_t('$0.00 (Bonificada)') }}</span>
                </div>
                @if ($total->tax > 0)
                <div class="flex justify-between text-muted">
                    <span>{{ \App\Classes\Settings::tax()->name }} ({{ \App\Classes\Settings::tax()->rate }}%):</span>
                    <span class="font-semibold text-base oasis-mono-pill">{{ $total->format($total->tax) }}</span>
                </div>
                @endif
            </div>

            <!-- Código Promocional Colapsable (Limpio y sin ruido visual) -->
            <div class="pt-1">
                @if(!$coupon)
                <button type="button"
                        @click="showPromo = !showPromo"
                        class="text-xs font-semibold text-muted hover:text-primary transition flex items-center gap-1">
                    <span x-text="showPromo ? '{{ oasis_t('▾ Ocultar código promocional') }}' : '{{ oasis_t('🏷️ ¿Tienes un código promocional de lanzamiento?') }}'"></span>
                </button>
                <div x-show="showPromo" class="mt-2.5 flex items-end gap-2">
                    <x-form.input wire:model="coupon" name="coupon" label="" placeholder="{{ oasis_t('Ingresa tu código...') }}" />
                    <x-button.secondary wire:click="applyCoupon" class="h-fit !w-fit mb-0.5 text-xs" wire:loading.attr="disabled">
                        <x-loading target="applyCoupon" />
                        <div wire:loading.remove wire:target="applyCoupon">{{ oasis_t('Canjear') }}</div>
                    </x-button.secondary>
                </div>
                @else
                <div class="flex justify-between items-center bg-primary/10 border border-primary/30 rounded-xl px-3 py-2">
                    <span class="text-xs font-bold text-primary">🏷️ {{ oasis_t('Cupón activo:') }} {{ $coupon->code }}</span>
                    <button type="button" wire:click="removeCoupon" class="text-xs text-muted hover:text-red-400 font-semibold">
                        {{ oasis_t('Quitar') }}
                    </button>
                </div>
                @endif
            </div>

            <div class="oasis-voucher-divider"></div>

            <!-- Total Neto -->
            <div class="flex justify-between items-baseline">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-muted block">{{ oasis_t('Total Exacto a Transferir') }}</span>
                    <span class="text-[11px] text-muted">{{ oasis_t('Rieles: Nequi/Bancolombia, SPEI 24/7 o USDT') }}</span>
                </div>
                <span class="text-2xl sm:text-3xl font-extrabold text-primary oasis-mono-pill">
                    {{ $total->format($total->total) }}
                </span>
            </div>

            @if(config('settings.tos'))
            <x-form.checkbox wire:model="tos" name="tos">
                {{ oasis_t('Acepto los') }}
                <a href="{{ config('settings.tos') }}" target="_blank" class="text-primary hover:text-primary/80 underline">
                    {{ oasis_t('términos de entrega digital') }}
                </a>
            </x-form.checkbox>
            @endif

            <x-button.primary wire:click="checkout" class="w-full py-3.5 font-extrabold text-sm shadow-lg" wire:loading.attr="disabled">
                <x-loading target="checkout" />
                <div wire:loading.remove wire:target="checkout" class="flex items-center justify-center gap-2">
                    <span>{{ oasis_t('Emitir Orden P2P y Ver Datos de Pago') }}</span>
                    <span>→</span>
                </div>
            </x-button.primary>

            <p class="text-[11px] text-center text-muted">
                {{ oasis_t('🔒 Al continuar se generará tu folio único de pago y acceso a la sala de validación.') }}
            </p>
        </div>
    </div>
    @endif
</div>
