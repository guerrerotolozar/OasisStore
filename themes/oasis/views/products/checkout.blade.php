@php
    require_once base_path('themes/oasis/helpers.php');
@endphp
<div class="container mt-8 space-y-6">
    <!-- Pipeline P2P de 3 Pasos -->
    <div class="oasis-pipeline">
        <div class="oasis-step oasis-step-active">
            <span class="oasis-step-num">1</span>
            <div>
                <p class="font-bold text-xs leading-tight">{{ oasis_t('1. Configuración de Paquete') }}</p>
                <p class="text-[11px] text-muted">{{ oasis_t('Revisa el contenido de tu recarga') }}</p>
            </div>
        </div>
        <div class="oasis-step">
            <span class="oasis-step-num">2</span>
            <div>
                <p class="font-bold text-xs leading-tight">{{ oasis_t('2. Emisión de Orden P2P') }}</p>
                <p class="text-[11px] text-muted">{{ oasis_t('Generación de folio único') }}</p>
            </div>
        </div>
        <div class="oasis-step">
            <span class="oasis-step-num">3</span>
            <div>
                <p class="font-bold text-xs leading-tight">{{ oasis_t('3. Pago & Entrega Directa') }}</p>
                <p class="text-[11px] text-muted">{{ oasis_t('SPEI / USDT + Validación inmediata') }}</p>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between">
        <a href="{{ route('category.show', ['category' => $product->category->slug]) }}"
           wire:navigate
           class="px-3.5 py-2 rounded-xl text-xs font-semibold border bg-background-secondary border-neutral text-muted hover:text-base transition">
            {{ oasis_t('← Volver al Catálogo') }}
        </a>
        <span class="oasis-mono-pill px-3 py-1 rounded-full bg-primary/15 text-primary border border-primary/30 font-bold uppercase">
            {{ oasis_t('BÓVEDA DIGITAL OASIS') }}
        </span>
    </div>

    <!-- Layout Dividido: Especificaciones del Paquete (Izquierda) + Voucher de Reserva (Derecha Sticky) -->
    <div class="oasis-split-layout">
        <!-- Detalles y Opciones del Paquete -->
        <div class="oasis-terminal p-6 sm:p-8 space-y-6">
            <div class="flex flex-col sm:flex-row gap-5 items-start">
                @if ($product->image)
                <img src="{{ Storage::url($product->image) }}"
                     alt="{{ $product->name }}"
                     class="w-24 h-24 rounded-2xl object-cover border border-neutral shrink-0">
                @else
                <div class="w-20 h-20 rounded-2xl bg-primary/15 border border-primary/30 flex items-center justify-center text-3xl shrink-0">
                    💎
                </div>
                @endif
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="oasis-mono-pill px-2 py-0.5 rounded bg-primary/15 text-primary border border-primary/30 font-bold uppercase">
                            {{ oasis_t($product->category->name) }}
                        </span>
                        @if ($product->stock > 0)
                        <span class="text-xs font-semibold text-secondary">● {{ $product->stock }} {{ oasis_t('disponibles en bóveda') }}</span>
                        @endif
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-base">{{ oasis_t($product->name) }}</h1>
                    @if ($product->description)
                    <article class="prose dark:prose-invert prose-sm text-muted max-w-none pt-1 [&_ul]:my-2 [&_ul]:space-y-1 [&_li]:text-xs [&_strong]:text-base [&_code]:text-primary [&_code]:bg-primary/10 [&_code]:px-1.5 [&_code]:py-0.5 [&_code]:rounded">
                        {!! Str::markdown(oasis_t($product->description)) !!}
                    </article>
                    @endif
                </div>
            </div>

            @if ($product->availablePlans()->count() > 1)
            <div class="pt-4 border-t border-neutral">
                <x-form.select wire:model.live="plan_id" name="plan_id" label="Selecciona una modalidad">
                    @foreach ($product->availablePlans() as $availablePlan)
                    <option value="{{ $availablePlan->id }}">
                        {{ $availablePlan->name }} - {{ $availablePlan->price()->formatted->price }}
                        @if ($availablePlan->price()->has_setup_fee)
                            + {{ $availablePlan->price()->formatted->setup_fee }} {{ __('product.setup_fee') }}
                        @endif
                    </option>
                    @endforeach
                </x-form.select>
            </div>
            @endif

            @foreach ($product->configOptions as $configOption)
                @php
                    $showPriceTag = $configOption->children->filter(fn ($value) => !$value->price(billing_period: $plan->billing_period, billing_unit: $plan->billing_unit)->is_free)->count() > 0;
                @endphp
                <div class="pt-4 border-t border-neutral">
                    <x-form.configoption :config="$configOption" :name="'configOptions.' . $configOption->id" :showPriceTag="$showPriceTag" :plan="$plan">
                        @if ($configOption->type == 'select')
                            @foreach ($configOption->children as $configOptionValue)
                                <option value="{{ $configOptionValue->id }}">
                                    {{ $configOptionValue->name }}
                                    {{ ($showPriceTag && $configOptionValue->price(billing_period: $plan->billing_period, billing_unit: $plan->billing_unit)->available) ? ' - ' . $configOptionValue->price(billing_period: $plan->billing_period, billing_unit: $plan->billing_unit) : '' }}
                                </option>
                            @endforeach
                        @elseif($configOption->type == 'radio')
                            @foreach ($configOption->children as $configOptionValue)
                                <div class="flex items-center gap-2">
                                    <input type="radio" id="{{ $configOptionValue->id }}" name="{{ $configOption->id }}"
                                        wire:model.live="configOptions.{{ $configOption->id }}"
                                        value="{{ $configOptionValue->id }}" />
                                    <label for="{{ $configOptionValue->id }}">
                                        {{ $configOptionValue->name }}
                                        {{ ($showPriceTag && $configOptionValue->price(billing_period: $plan->billing_period, billing_unit: $plan->billing_unit)->available) ? ' - ' . $configOptionValue->price(billing_period: $plan->billing_period, billing_unit: $plan->billing_unit) : '' }}
                                    </label>
                                </div>
                            @endforeach
                        @endif
                    </x-form.configoption>
                </div>
            @endforeach

            @foreach ($this->getCheckoutConfig() as $configOption)
                @php $configOption = (object) $configOption; @endphp
                <div class="pt-4 border-t border-neutral">
                    <x-form.configoption :config="$configOption" :name="'checkoutConfig.' . $configOption->name">
                        @if ($configOption->type == 'select')
                            @foreach ($configOption->options as $configOptionValue => $configOptionValueName)
                                <option value="{{ $configOptionValue }}">{{ $configOptionValueName }}</option>
                            @endforeach
                        @elseif($configOption->type == 'radio')
                            @foreach ($configOption->options as $configOptionValue => $configOptionValueName)
                                <div class="flex items-center gap-2">
                                    <input type="radio" id="{{ $configOptionValue }}" name="{{ $configOption->name }}"
                                        wire:model.live="checkoutConfig.{{ $configOption->name }}"
                                        value="{{ $configOptionValue }}" />
                                    <label for="{{ $configOptionValue }}">{{ $configOptionValueName }}</label>
                                </div>
                            @endforeach
                        @endif
                    </x-form.configoption>
                </div>
            @endforeach

            <!-- Ficha de Flujo Express -->
            <div class="pt-4 border-t border-neutral/60 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                <div class="p-3.5 rounded-xl bg-background/70 border border-neutral space-y-1">
                    <p class="font-bold text-base">🇲🇽 SPEI Interbancario 24/7</p>
                    <p class="text-muted">Transferencia directa desde cualquier banco en México sin recargo.</p>
                </div>
                <div class="p-3.5 rounded-xl bg-background/70 border border-neutral space-y-1">
                    <p class="font-bold text-base">⚡ USDT (TRC20 / BEP20)</p>
                    <p class="text-muted">Liquidación cripto directa 1:1 con confirmación en sala P2P.</p>
                </div>
            </div>
        </div>

        <!-- Voucher Lateral de Orden (Derecha Sticky) -->
        <div class="oasis-voucher oasis-sticky-panel p-6 space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-primary block">{{ oasis_t('Orden Express') }}</span>
                    <h2 class="text-lg font-extrabold text-base">{{ oasis_t('Boleto de Reserva') }}</h2>
                </div>
                <span class="oasis-mono-pill px-2.5 py-1 rounded bg-background border border-neutral text-muted font-bold">
                    {{ oasis_t('1 PAQUETE') }}
                </span>
            </div>

            <div class="oasis-voucher-divider"></div>

            <div class="space-y-2.5 text-sm">
                <div class="flex justify-between text-muted">
                    <span>{{ oasis_t('Paquete seleccionado:') }}</span>
                    <span class="font-semibold text-base text-right">{{ oasis_t($product->name) }}</span>
                </div>
                <div class="flex justify-between text-muted">
                    <span>{{ oasis_t('Modalidad:') }}</span>
                    <span class="oasis-mono-pill text-primary font-bold">{{ oasis_t('Pago Único P2P') }}</span>
                </div>
                @if ($total->total_tax > 0)
                <div class="flex justify-between text-muted">
                    <span>{{ oasis_t('Subtotal:') }}</span>
                    <span class="font-semibold text-base oasis-mono-pill">{{ $total->format($total->subtotal) }}</span>
                </div>
                <div class="flex justify-between text-muted">
                    <span>{{ \App\Classes\Settings::tax()->name }} ({{ \App\Classes\Settings::tax()->rate }}%):</span>
                    <span class="font-semibold text-base oasis-mono-pill">{{ $total->formatted->total_tax }}</span>
                </div>
                @endif
            </div>

            <div class="oasis-voucher-divider"></div>

            <div class="flex justify-between items-baseline">
                <span class="text-xs font-bold uppercase tracking-wider text-muted">{{ oasis_t('Total del Paquete') }}</span>
                <span class="text-2xl sm:text-3xl font-extrabold text-primary oasis-mono-pill">{{ $total }}</span>
            </div>

            @if (($product->stock > 0 || !$product->stock) && $product->price()->available)
            <x-button.primary wire:click="checkout" wire:loading.attr="disabled" class="w-full py-3.5 font-extrabold text-sm shadow-lg">
                <x-loading target="checkout" />
                <div wire:loading.remove wire:target="checkout" class="flex items-center justify-center gap-2">
                    <span>{{ oasis_t('Preparar Orden en Terminal P2P') }}</span>
                    <span>→</span>
                </div>
            </x-button.primary>
            @endif
        </div>
    </div>
</div>
