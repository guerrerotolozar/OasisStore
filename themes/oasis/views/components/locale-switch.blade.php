@php
    $activeCurr = session('currency', 'COP');
    $locales = config('settings.allowed_languages', []);
@endphp

<x-dropdown>
    <x-slot:trigger>
        <span class="flex items-center gap-1.5 px-1 py-0.5 text-sm font-semibold text-nowrap">
            <span>{{ config('app.available_locales')[app()->getLocale()] ?? 'Español' }}</span>
            <span class="text-base/40">|</span>
            <span class="text-primary font-bold">{{ $activeCurr }}</span>
        </span>
    </x-slot:trigger>
    <x-slot:content>
        <div class="p-3 w-64 space-y-3">
            <!-- Selector de Moneda de Pago Oficial -->
            <div>
                <strong class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-2">
                    Moneda de Pago
                </strong>
                <div class="space-y-1">
                    <!-- Opción COP (Colombia) -->
                    <a href="{{ route('oasis.currency.switch', 'COP') }}"
                       class="flex items-center justify-between p-2 rounded-lg transition {{ $activeCurr === 'COP' ? 'bg-primary/15 text-primary font-bold border border-primary/30' : 'hover:bg-neutral/40 text-base' }}">
                        <div class="flex items-center gap-2">
                            <svg class="size-4 rounded-full overflow-hidden shadow-sm shrink-0 border border-white/10" viewBox="0 0 900 600" aria-hidden="true">
                                <rect width="900" height="300" fill="#FCD116"/>
                                <rect y="300" width="900" height="150" fill="#003893"/>
                                <rect y="450" width="900" height="150" fill="#CE1126"/>
                            </svg>
                            <span class="text-xs">Peso Colombiano</span>
                        </div>
                        <span class="oasis-mono-pill text-[10px] font-bold px-1.5 py-0.5 rounded bg-primary/20 text-primary">COP</span>
                    </a>

                    <!-- Opción MXN (México SPEI) -->
                    <a href="{{ route('oasis.currency.switch', 'MXN') }}"
                       class="flex items-center justify-between p-2 rounded-lg transition {{ $activeCurr === 'MXN' ? 'bg-primary/15 text-primary font-bold border border-primary/30' : 'hover:bg-neutral/40 text-base' }}">
                        <div class="flex items-center gap-2">
                            <svg class="size-4 rounded-full overflow-hidden shadow-sm shrink-0 border border-white/10" viewBox="0 0 900 600" aria-hidden="true">
                                <rect width="300" height="600" fill="#006847"/>
                                <rect x="300" width="300" height="600" fill="#FFFFFF"/>
                                <rect x="600" width="300" height="600" fill="#CE1126"/>
                                <circle cx="450" cy="300" r="45" fill="#8B5A2B"/>
                            </svg>
                            <span class="text-xs">Peso Mexicano (SPEI)</span>
                        </div>
                        <span class="oasis-mono-pill text-[10px] font-bold px-1.5 py-0.5 rounded bg-primary/20 text-primary">MXN</span>
                    </a>

                    <!-- Opción USD (PayPal / Wise / Cripto) -->
                    <a href="{{ route('oasis.currency.switch', 'USD') }}"
                       class="flex items-center justify-between p-2 rounded-lg transition {{ $activeCurr === 'USD' ? 'bg-primary/15 text-primary font-bold border border-primary/30' : 'hover:bg-neutral/40 text-base' }}">
                        <div class="flex items-center gap-2">
                            <svg class="size-4 rounded-full overflow-hidden shadow-sm shrink-0 border border-white/10" viewBox="0 0 7410 3900" aria-hidden="true">
                                <rect width="7410" height="3900" fill="#B22234"/>
                                <path d="M0,450H7410M0,1050H7410M0,1650H7410M0,2250H7410M0,2850H7410M0,3450H7410" stroke="#FFF" stroke-width="300"/>
                                <rect width="2964" height="2100" fill="#3C3B6E"/>
                            </svg>
                            <span class="text-xs">Dólar (PayPal / Wise)</span>
                        </div>
                        <span class="oasis-mono-pill text-[10px] font-bold px-1.5 py-0.5 rounded bg-primary/20 text-primary">USD</span>
                    </a>
                </div>
            </div>

            <!-- Selector de Idioma (si está habilitado más de un idioma) -->
            @if(count($locales) > 1)
            <div class="pt-2 border-t border-neutral/60">
                <strong class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1.5"> Idioma </strong>
                <x-select wire:model.live="currentLocale" :options="collect($locales)->map(fn($code) => [
                    'value' => $code,
                    'label' => config('app.available_locales')[$code] ?? $code
                    ])->values()->toArray()" placeholder="Seleccionar idioma" />
            </div>
            @endif
        </div>
    </x-slot:content>
</x-dropdown>
