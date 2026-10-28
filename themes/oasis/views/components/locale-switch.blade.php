@php
    require_once base_path('themes/oasis/helpers.php');
    $cart = \App\Classes\Cart::getOnce();
    // Fuente de verdad única: sesión -> carrito existente -> default de configuración
    $activeCurr = session('currency') ?? ($cart->exists ? $cart->currency_code : config('settings.default_currency', 'COP'));
    if (!session()->has('currency')) {
        session(['currency' => $activeCurr]);
    }
    
    // Consulta dinámica de monedas configuradas en el panel administrativo
    $currencies = \App\Models\Currency::all();
    // Restringir estrictamente el catálogo lingüístico a Español e Inglés
    $locales = ['es', 'en'];
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <!-- Botón Disparador -->
    <button type="button"
            @click="open = !open"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-neutral/60 bg-background-secondary/80 hover:bg-neutral/20 transition text-sm text-base font-semibold text-nowrap cursor-pointer shadow-sm">
        <span>{{ config('app.available_locales')[app()->getLocale()] ?? (app()->getLocale() === 'en' ? 'English' : 'Español') }}</span>
        <span class="text-base/40">|</span>
        <span class="text-primary font-bold">{{ $activeCurr }}</span>
        <svg :class="{ '-rotate-180': open }" class="w-4 h-4 text-muted transition-transform duration-200 ml-0.5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
    </button>

    <!-- Menú Desplegable con Medidas Responsivas Reales -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
         x-cloak
         class="absolute left-0 sm:left-auto sm:right-0 mt-2 w-64 sm:w-72 p-2.5 bg-background-secondary rounded-xl shadow-2xl border border-neutral z-50 space-y-2">
        
        <!-- Lista Dinámica de Monedas (Desde la Base de Datos) -->
        <div>
            <div class="text-[10px] font-bold uppercase tracking-wider text-muted mb-1.5 px-1">
                {{ oasis_t('Moneda de Pago') }}
            </div>
            
            <div class="space-y-1">
                @foreach ($currencies as $curr)
                @php
                    $isActive = ($activeCurr === $curr->code);
                @endphp
                <a href="{{ route('oasis.currency.switch', $curr->code) }}"
                   class="flex items-center justify-between px-2.5 py-2 rounded-lg transition {{ $isActive ? 'bg-primary/15 text-primary font-bold border border-primary/30' : 'hover:bg-neutral/40 text-base' }}">
                    <div class="flex items-center gap-2.5 min-w-0">
                        @if ($curr->code === 'COP')
                        <svg width="20" height="20" class="w-5 h-5 min-w-[20px] min-h-[20px] rounded-full shrink-0 border border-white/20 shadow-xs" viewBox="0 0 900 600" aria-hidden="true">
                            <rect width="900" height="300" fill="#FCD116"/>
                            <rect y="300" width="900" height="150" fill="#003893"/>
                            <rect y="450" width="900" height="150" fill="#CE1126"/>
                        </svg>
                        @elseif ($curr->code === 'MXN')
                        <svg width="20" height="20" class="w-5 h-5 min-w-[20px] min-h-[20px] rounded-full shrink-0 border border-white/20 shadow-xs" viewBox="0 0 900 600" aria-hidden="true">
                            <rect width="300" height="600" fill="#006847"/>
                            <rect x="300" width="300" height="600" fill="#FFFFFF"/>
                            <rect x="600" width="300" height="600" fill="#CE1126"/>
                            <circle cx="450" cy="300" r="45" fill="#8B5A2B"/>
                        </svg>
                        @elseif ($curr->code === 'USD')
                        <svg width="20" height="20" class="w-5 h-5 min-w-[20px] min-h-[20px] rounded-full shrink-0 border border-white/20 shadow-xs" viewBox="0 0 7410 3900" aria-hidden="true">
                            <rect width="7410" height="3900" fill="#B22234"/>
                            <path d="M0,450H7410M0,1050H7410M0,1650H7410M0,2250H7410M0,2850H7410M0,3450H7410" stroke="#FFF" stroke-width="300"/>
                            <rect width="2964" height="2100" fill="#3C3B6E"/>
                        </svg>
                        @else
                        <div class="w-5 h-5 min-w-[20px] min-h-[20px] rounded-full bg-primary/20 text-primary flex items-center justify-center text-[10px] font-bold">
                            {{ substr($curr->code, 0, 2) }}
                        </div>
                        @endif
                        <span class="text-xs whitespace-nowrap">{{ oasis_t($curr->name) }}</span>
                    </div>
                    <span class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded bg-primary/20 text-primary shrink-0 ml-auto">{{ $curr->code }}</span>
                </a>
                @endforeach
            </div>
        </div>

        <!-- Selector de Idioma (si está habilitado más de un idioma) -->
        @if(count($locales) > 1)
        <div class="pt-2 border-t border-neutral/60">
            <strong class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1 px-1"> {{ oasis_t('Idioma') }} </strong>
            <x-select wire:model.live="currentLocale" :options="collect($locales)->map(fn($code) => [
                'value' => $code,
                'label' => config('app.available_locales')[$code] ?? $code
                ])->values()->toArray()" placeholder="{{ oasis_t('Seleccionar idioma') }}" />
        </div>
        @endif
    </div>
</div>