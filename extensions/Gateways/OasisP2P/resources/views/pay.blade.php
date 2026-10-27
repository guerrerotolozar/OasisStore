<div class="space-y-5 text-base"
     x-data="{
        channel: @js($defaultChannel),
        copiedKey: '',
        fileName: '',
        selectedNetwork: @js($crypto['default_network']),
        networks: @js($crypto['networks']),
        get currentAddress() {
            return this.networks[this.selectedNetwork]?.address || '';
        },
        get currentNetworkName() {
            return this.networks[this.selectedNetwork]?.name || this.selectedNetwork;
        },
        get currentNetworkNote() {
            return this.networks[this.selectedNetwork]?.note || '';
        },
        copyValue(val, key) {
            const markCopied = () => {
                this.copiedKey = key;
                setTimeout(() => {
                    if (this.copiedKey === key) this.copiedKey = '';
                }, 2000);
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(val).then(markCopied).catch(() => this.fallbackCopy(val, markCopied));
            } else {
                this.fallbackCopy(val, markCopied);
            }
        },
        fallbackCopy(val, onSuccess) {
            const textArea = document.createElement('textarea');
            textArea.value = val;
            textArea.style.position = 'fixed';
            textArea.style.top = '0';
            textArea.style.left = '0';
            textArea.style.width = '2em';
            textArea.style.height = '2em';
            textArea.style.padding = '0';
            textArea.style.border = 'none';
            textArea.style.outline = 'none';
            textArea.style.boxShadow = 'none';
            textArea.style.background = 'transparent';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                const successful = document.execCommand('copy');
                if (successful) onSuccess();
            } catch (err) {
                console.error('Fallback clipboard error', err);
            }
            document.body.removeChild(textArea);
        }
     }">

    <!-- Paso 1: Selector de Método P2P -->
    @php
        $activeChannelsCount = ($cop['enabled'] ? 1 : 0) + ($spei['enabled'] ? 1 : 0) + ($crypto['enabled'] ? 1 : 0);
        $gridClass = $activeChannelsCount === 3
            ? 'grid grid-cols-1 sm:grid-cols-3 gap-2.5'
            : ($activeChannelsCount === 2 ? 'grid grid-cols-1 sm:grid-cols-2 gap-3' : 'grid grid-cols-1 gap-3');
    @endphp

    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2.5">
            1. Elige dónde transferir
        </label>
        <div class="{{ $gridClass }}">
            <!-- 1. COLOMBIA -->
            @if($cop['enabled'])
            <button type="button"
                @click="channel = 'cop_colombia'"
                :class="channel === 'cop_colombia'
                    ? 'border-primary bg-primary/15 ring-2 ring-primary/30 shadow-[0_0_15px_rgba(16,185,129,0.15)]'
                    : 'border-neutral bg-background/80 hover:border-primary/60'"
                class="flex items-center justify-between p-3.5 rounded-xl border transition-all text-left">
                <div class="flex items-center gap-2.5 overflow-hidden pr-1">
                    <!-- Bandera de Colombia en SVG nítido (sin fallos de fuentes en Windows) -->
                    <svg class="size-5 rounded-full overflow-hidden shadow-sm shrink-0 border border-white/10" viewBox="0 0 900 600" aria-hidden="true">
                        <rect width="900" height="300" fill="#FCD116"/>
                        <rect y="300" width="900" height="150" fill="#003893"/>
                        <rect y="450" width="900" height="150" fill="#CE1126"/>
                    </svg>
                    <div class="overflow-hidden">
                        <p class="font-extrabold text-sm text-base truncate">Colombia</p>
                        <p class="text-xs text-muted mt-0.5 truncate">{{ $cop['bank'] }}</p>
                    </div>
                </div>
                <span class="oasis-mono-pill font-bold px-2 py-0.5 rounded bg-primary/20 text-primary text-[11px] shrink-0">COP</span>
            </button>
            @endif

            <!-- 2. MÉXICO -->
            @if($spei['enabled'])
            <button type="button"
                @click="channel = 'spei_mexico'"
                :class="channel === 'spei_mexico'
                    ? 'border-primary bg-primary/15 ring-2 ring-primary/30 shadow-[0_0_15px_rgba(16,185,129,0.15)]'
                    : 'border-neutral bg-background/80 hover:border-primary/60'"
                class="flex items-center justify-between p-3.5 rounded-xl border transition-all text-left">
                <div class="flex items-center gap-2.5 overflow-hidden pr-1">
                    <!-- Bandera de México en SVG nítido -->
                    <svg class="size-5 rounded-full overflow-hidden shadow-sm shrink-0 border border-white/10" viewBox="0 0 900 600" aria-hidden="true">
                        <rect width="300" height="600" fill="#006847"/>
                        <rect x="300" width="300" height="600" fill="#FFFFFF"/>
                        <rect x="600" width="300" height="600" fill="#CE1126"/>
                        <circle cx="450" cy="300" r="45" fill="#8B5A2B"/>
                    </svg>
                    <div class="overflow-hidden">
                        <p class="font-extrabold text-sm text-base truncate">México</p>
                        <p class="text-xs text-muted mt-0.5 truncate">SPEI 24/7</p>
                    </div>
                </div>
                <span class="oasis-mono-pill font-bold px-2 py-0.5 rounded bg-primary/20 text-primary text-[11px] shrink-0">SPEI</span>
            </button>
            @endif

            <!-- 3. CRIPTOMONEDAS -->
            @if($crypto['enabled'])
            <button type="button"
                @click="channel = 'crypto'"
                :class="channel === 'crypto'
                    ? 'border-primary bg-primary/15 ring-2 ring-primary/30 shadow-[0_0_15px_rgba(16,185,129,0.15)]'
                    : 'border-neutral bg-background/80 hover:border-primary/60'"
                class="flex items-center justify-between p-3.5 rounded-xl border transition-all text-left">
                <div class="flex items-center gap-2.5 overflow-hidden pr-1">
                    <!-- Logo Tether USDT oficial en SVG -->
                    <svg class="size-5 rounded-full shrink-0 shadow-sm" viewBox="0 0 32 32" aria-hidden="true">
                        <circle cx="16" cy="16" r="16" fill="#26A17B"/>
                        <path d="M17.922 17.383c-.11.008-.68.04-1.636.04-.766 0-1.424-.03-1.615-.04-4.373-.18-7.666-.898-7.666-1.758 0-.858 3.293-1.578 7.666-1.76v2.793c.196.012.864.037 1.635.037.938 0 1.507-.027 1.616-.037v-2.793c4.363.182 7.647.902 7.647 1.76 0 .86-3.284 1.578-7.647 1.758zm0-3.864V10.74h4.74V7.5H9.338v3.24h4.74v2.779c-4.93.208-8.666 1.096-8.666 2.169 0 1.072 3.737 1.96 8.666 2.167v6.645h3.844V18.52c4.92-.207 8.647-1.095 8.647-2.167 0-1.073-3.727-1.96-8.647-2.169z" fill="#FFF"/>
                    </svg>
                    <div class="overflow-hidden">
                        <p class="font-extrabold text-sm text-base truncate">Criptomonedas</p>
                        <p class="text-xs text-muted mt-0.5 truncate">USDT & Binance</p>
                    </div>
                </div>
                <span class="oasis-mono-pill font-bold px-2 py-0.5 rounded bg-secondary/20 text-secondary text-[11px] shrink-0">USDT</span>
            </button>
            @endif
        </div>
    </div>

    <!-- PANEL 1: COLOMBIA (Bancolombia, Nequi, Daviplata, Transfiya) -->
    @if($cop['enabled'])
    <div x-show="channel === 'cop_colombia'" x-cloak class="rounded-2xl bg-background/80 border border-neutral p-4 sm:p-5 space-y-4">
        <!-- Fila de Banco, Modalidad y Titular -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs pb-3 border-b border-neutral/60">
            <div>
                <span class="text-muted block text-[11px] font-semibold uppercase tracking-wider">Banco / Billetera</span>
                <span class="font-bold text-sm text-base mt-0.5 block">{{ $cop['bank'] }}</span>
            </div>
            <div>
                <span class="text-muted block text-[11px] font-semibold uppercase tracking-wider">Tipo / Modalidad</span>
                <span class="font-bold text-sm text-primary mt-0.5 block">{{ $cop['account_type'] }}</span>
            </div>
            <div>
                <span class="text-muted block text-[11px] font-semibold uppercase tracking-wider">Beneficiario / Titular</span>
                <span class="font-bold text-sm text-base mt-0.5 block">{{ $cop['holder'] }}</span>
            </div>
        </div>

        <!-- Número de Cuenta / Billetera / Celular: Toca directo para copiar -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-xs text-muted font-bold uppercase tracking-wider">Número de Cuenta / Billetera (Nequi / Bancos)</span>
                <span class="text-[11px] text-muted hidden sm:inline">Toca los dígitos para copiar</span>
            </div>

            <div @click="copyValue(@js($cop['account_number']), 'cop_account')"
                 role="button"
                 tabindex="0"
                 @keydown.enter="copyValue(@js($cop['account_number']), 'cop_account')"
                 @keydown.space.prevent="copyValue(@js($cop['account_number']), 'cop_account')"
                 class="group relative flex items-center justify-between p-3.5 sm:p-4 rounded-xl border transition-all duration-200 cursor-pointer select-none"
                 :class="copiedKey === 'cop_account'
                    ? 'border-primary bg-primary/15 ring-2 ring-primary/30 shadow-[0_0_18px_rgba(16,185,129,0.25)]'
                    : 'border-neutral bg-background/90 hover:border-primary/70 hover:bg-primary/5 active:scale-[0.99]'"
                 title="Haz clic para copiar el número de cuenta o celular">
                
                <div class="flex items-center gap-2 overflow-hidden">
                    <code class="text-sm sm:text-base font-mono font-extrabold tracking-wider transition-colors duration-150"
                          :class="copiedKey === 'cop_account' ? 'text-primary' : 'text-base group-hover:text-primary'">
                        {{ $cop['account_number'] }}
                    </code>
                </div>

                <div class="flex items-center gap-1.5 text-xs font-semibold shrink-0 ml-2">
                    <span x-show="copiedKey !== 'cop_account'" class="text-muted group-hover:text-primary transition-colors flex items-center gap-1.5 text-[11px]">
                        <svg class="size-3.5 transition-transform group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                        <span>Copiar</span>
                    </span>
                    <span x-show="copiedKey === 'cop_account'" class="text-primary font-extrabold flex items-center gap-1 text-xs">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>¡Copiado!</span>
                    </span>
                </div>
            </div>
        </div>

        @if(!empty($cop['note']))
        <div class="px-3.5 py-2 rounded-xl bg-primary/10 border border-primary/20 text-xs text-primary flex items-center gap-2">
            <span>💡</span>
            <span class="font-medium">{{ $cop['note'] }}</span>
        </div>
        @endif

        <!-- Concepto sugerido -->
        <div class="pt-2 border-t border-neutral/60 flex items-center justify-between text-xs">
            <span class="text-muted font-medium">Concepto / Referencia sugerida:</span>
            <button type="button"
                @click="copyValue('ORDEN-{{ $invoice->number ?: $invoice->id }}', 'concepto')"
                class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border transition-all duration-150 cursor-pointer select-none"
                :class="copiedKey === 'concepto'
                    ? 'border-primary bg-primary/15 text-primary ring-1 ring-primary/40'
                    : 'border-neutral bg-background hover:border-primary/60 hover:bg-primary/5 text-base'"
                title="Clic para copiar concepto">
                <code class="oasis-mono-pill font-bold" :class="copiedKey === 'concepto' ? 'text-primary' : 'group-hover:text-primary'">ORDEN-{{ $invoice->number ?: $invoice->id }}</code>
                <span x-show="copiedKey !== 'concepto'" class="text-[10px] text-muted group-hover:text-primary font-semibold">Copiar</span>
                <span x-show="copiedKey === 'concepto'" class="text-[10px] font-bold text-primary">✓</span>
            </button>
        </div>
    </div>
    @endif

    <!-- PANEL 2: MÉXICO (Transferencia SPEI) -->
    @if($spei['enabled'])
    <div x-show="channel === 'spei_mexico'" x-cloak class="rounded-2xl bg-background/80 border border-neutral p-4 sm:p-5 space-y-4">
        <!-- Fila de Banco y Titular -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs pb-3 border-b border-neutral/60">
            <div>
                <span class="text-muted block text-[11px] font-semibold uppercase tracking-wider">Banco Receptor</span>
                <span class="font-bold text-sm sm:text-base text-base mt-0.5 block">{{ $spei['bank'] }}</span>
            </div>
            <div>
                <span class="text-muted block text-[11px] font-semibold uppercase tracking-wider">Beneficiario / Titular</span>
                <span class="font-bold text-sm sm:text-base text-base mt-0.5 block">{{ $spei['holder'] }}</span>
            </div>
        </div>

        <!-- CLABE: Click directo en la cápsula completa para copiar -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-xs text-muted font-bold uppercase tracking-wider">CLABE Interbancaria (18 dígitos)</span>
                <span class="text-[11px] text-muted hidden sm:inline">Toca los dígitos para copiar</span>
            </div>

            <div @click="copyValue(@js($spei['clabe']), 'clabe')"
                 role="button"
                 tabindex="0"
                 @keydown.enter="copyValue(@js($spei['clabe']), 'clabe')"
                 @keydown.space.prevent="copyValue(@js($spei['clabe']), 'clabe')"
                 class="group relative flex items-center justify-between p-3.5 sm:p-4 rounded-xl border transition-all duration-200 cursor-pointer select-none"
                 :class="copiedKey === 'clabe'
                    ? 'border-primary bg-primary/15 ring-2 ring-primary/30 shadow-[0_0_18px_rgba(16,185,129,0.25)]'
                    : 'border-neutral bg-background/90 hover:border-primary/70 hover:bg-primary/5 active:scale-[0.99]'"
                 title="Haz clic para copiar la CLABE">
                
                <div class="flex items-center gap-2 overflow-hidden">
                    <code class="text-sm sm:text-base font-mono font-extrabold tracking-wider transition-colors duration-150"
                          :class="copiedKey === 'clabe' ? 'text-primary' : 'text-base group-hover:text-primary'">
                        {{ $spei['clabe'] }}
                    </code>
                </div>

                <div class="flex items-center gap-1.5 text-xs font-semibold shrink-0 ml-2">
                    <span x-show="copiedKey !== 'clabe'" class="text-muted group-hover:text-primary transition-colors flex items-center gap-1.5 text-[11px]">
                        <svg class="size-3.5 transition-transform group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                        <span>Copiar</span>
                    </span>
                    <span x-show="copiedKey === 'clabe'" class="text-primary font-extrabold flex items-center gap-1 text-xs">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>¡Copiada!</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Concepto sugerido -->
        <div class="pt-2 border-t border-neutral/60 flex items-center justify-between text-xs">
            <span class="text-muted font-medium">Concepto sugerido:</span>
            <button type="button"
                @click="copyValue('ORDEN-{{ $invoice->number ?: $invoice->id }}', 'concepto')"
                class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border transition-all duration-150 cursor-pointer select-none"
                :class="copiedKey === 'concepto'
                    ? 'border-primary bg-primary/15 text-primary ring-1 ring-primary/40'
                    : 'border-neutral bg-background hover:border-primary/60 hover:bg-primary/5 text-base'"
                title="Clic para copiar concepto">
                <code class="oasis-mono-pill font-bold" :class="copiedKey === 'concepto' ? 'text-primary' : 'group-hover:text-primary'">ORDEN-{{ $invoice->number ?: $invoice->id }}</code>
                <span x-show="copiedKey !== 'concepto'" class="text-[10px] text-muted group-hover:text-primary font-semibold">Copiar</span>
                <span x-show="copiedKey === 'concepto'" class="text-[10px] font-bold text-primary">✓</span>
            </button>
        </div>
    </div>
    @endif

    <!-- PANEL 3: CRIPTOMONEDAS (USDT Multi-Red Seleccionable + Binance Pay ID Fijo) -->
    @if($crypto['enabled'])
    <div x-show="channel === 'crypto'" x-cloak class="rounded-2xl bg-background/80 border border-neutral p-4 sm:p-5 space-y-4">
        
        <!-- 3.1: SELECCIONABLE DE RED (Cambia dinámicamente el cuadro sin saturar de tarjetas) -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs text-muted font-bold uppercase tracking-wider">Red de USDT</span>
                <span class="text-[11px] text-muted">Toca para cambiar la red receptora</span>
            </div>

            <!-- Segmented Pills para elegir la Red -->
            <div class="flex flex-wrap gap-2">
                <template x-for="(net, netKey) in networks" :key="netKey">
                    <button type="button"
                            @click="selectedNetwork = netKey"
                            :class="selectedNetwork === netKey
                                ? 'border-primary bg-primary/15 text-primary ring-2 ring-primary/30 font-extrabold shadow-[0_0_12px_rgba(16,185,129,0.2)]'
                                : 'border-neutral bg-background/90 text-muted hover:border-neutral/90 hover:text-base'"
                            class="px-3.5 py-1.5 rounded-xl border text-xs transition-all flex items-center gap-2 cursor-pointer select-none">
                        <span class="size-2 rounded-full transition-colors"
                              :class="selectedNetwork === netKey ? 'bg-primary' : 'bg-muted/40'"></span>
                        <span x-text="net.name"></span>
                        <span class="oasis-mono-pill text-[10px] font-bold px-1.5 py-0.2 rounded"
                              :class="selectedNetwork === netKey ? 'bg-primary/20 text-primary' : 'bg-neutral text-muted'"
                              x-text="net.badge"></span>
                    </button>
                </template>
            </div>
        </div>

        <!-- 3.2: CUADRO ÚNICO DINÁMICO DE DIRECCIÓN (Se actualiza al instante según la red elegida) -->
        <div class="p-3.5 sm:p-4 rounded-xl bg-background/90 border border-neutral space-y-2.5">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs text-muted font-bold uppercase tracking-wider block">
                        Dirección USDT (<span class="text-primary font-mono" x-text="selectedNetwork"></span>)
                    </span>
                    <span class="text-[11px] text-muted mt-0.5 block" x-text="currentNetworkNote"></span>
                </div>
                <span class="oasis-mono-pill px-2.5 py-0.5 rounded-md bg-secondary/15 text-secondary border border-secondary/30 font-bold text-xs">
                    USDT 1:1
                </span>
            </div>

            <!-- Dirección con toque directo para copiar -->
            <div @click="copyValue(currentAddress, 'crypto_address')"
                 role="button"
                 tabindex="0"
                 @keydown.enter="copyValue(currentAddress, 'crypto_address')"
                 @keydown.space.prevent="copyValue(currentAddress, 'crypto_address')"
                 class="group relative flex items-center justify-between p-3 sm:p-3.5 rounded-xl border transition-all duration-200 cursor-pointer select-none"
                 :class="copiedKey === 'crypto_address'
                    ? 'border-primary bg-primary/15 ring-2 ring-primary/30 shadow-[0_0_18px_rgba(16,185,129,0.25)]'
                    : 'border-neutral bg-background hover:border-primary/70 hover:bg-primary/5 active:scale-[0.99]'"
                 title="Haz clic para copiar la dirección">
                
                <div class="overflow-hidden pr-2">
                    <code class="text-xs sm:text-sm font-mono font-extrabold tracking-wider break-all transition-colors duration-150"
                          :class="copiedKey === 'crypto_address' ? 'text-primary' : 'text-base group-hover:text-primary'"
                          x-text="currentAddress">
                    </code>
                </div>

                <div class="flex items-center gap-1.5 text-xs font-semibold shrink-0 ml-2">
                    <span x-show="copiedKey !== 'crypto_address'" class="text-muted group-hover:text-primary transition-colors flex items-center gap-1.5 text-[11px]">
                        <svg class="size-3.5 transition-transform group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                        <span>Copiar</span>
                    </span>
                    <span x-show="copiedKey === 'crypto_address'" class="text-primary font-extrabold flex items-center gap-1 text-xs">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>¡Copiada!</span>
                    </span>
                </div>
            </div>

            <p class="text-[11px] text-muted flex items-center gap-1.5">
                <span>⚠️</span>
                <span>Envía únicamente <strong>USDT</strong> mediante la red <span class="text-primary font-mono font-bold" x-text="selectedNetwork"></span>.</span>
            </p>
        </div>

        <!-- 3.3: CUADRO FIJO EXTRA PARA BINANCE PAY ID (Siempre disponible por si el usuario usa Binance) -->
        @if(!empty($crypto['binance_id']))
        <div class="p-3.5 sm:p-4 rounded-xl bg-background/90 border border-neutral space-y-2">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs text-muted font-bold uppercase tracking-wider block">
                        Binance Pay ID (Cero Comisiones)
                    </span>
                    <span class="text-[11px] text-muted mt-0.5 block">Transferencia directa entre usuarios Binance</span>
                </div>
                <span class="oasis-mono-pill px-2 py-0.5 rounded bg-primary/15 text-primary text-[10px] font-bold">
                    Sin fee
                </span>
            </div>

            <div @click="copyValue(@js($crypto['binance_id']), 'binance_id')"
                 role="button"
                 tabindex="0"
                 @keydown.enter="copyValue(@js($crypto['binance_id']), 'binance_id')"
                 @keydown.space.prevent="copyValue(@js($crypto['binance_id']), 'binance_id')"
                 class="group relative flex items-center justify-between p-3 sm:p-3.5 rounded-xl border transition-all duration-200 cursor-pointer select-none"
                 :class="copiedKey === 'binance_id'
                    ? 'border-primary bg-primary/15 ring-2 ring-primary/30 shadow-[0_0_18px_rgba(16,185,129,0.25)]'
                    : 'border-neutral bg-background hover:border-primary/70 hover:bg-primary/5 active:scale-[0.99]'"
                 title="Haz clic para copiar tu Binance Pay ID">
                
                <div class="flex items-center gap-2 overflow-hidden">
                    <code class="text-sm font-mono font-extrabold tracking-wider transition-colors duration-150"
                          :class="copiedKey === 'binance_id' ? 'text-primary' : 'text-base group-hover:text-primary'">
                        {{ $crypto['binance_id'] }}
                    </code>
                </div>

                <div class="flex items-center gap-1.5 text-xs font-semibold shrink-0 ml-2">
                    <span x-show="copiedKey !== 'binance_id'" class="text-muted group-hover:text-primary transition-colors flex items-center gap-1.5 text-[11px]">
                        <svg class="size-3.5 transition-transform group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                        <span>Copiar ID</span>
                    </span>
                    <span x-show="copiedKey === 'binance_id'" class="text-primary font-extrabold flex items-center gap-1 text-xs">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>¡ID Copiado!</span>
                    </span>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Paso 2: Subida Directa de Captura (Limpio y directo) -->
    <form action="{{ route('extensions.gateways.oasisp2p.receipt', $invoice) }}"
          method="POST"
          enctype="multipart/form-data"
          class="space-y-4 pt-3 border-t border-neutral">
        @csrf
        <input type="hidden" name="payment_channel" :value="channel">
        <input type="hidden" name="crypto_network" :value="channel === 'crypto' ? selectedNetwork : ''">

        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">
                2. Sube tu captura o comprobante de pago *
            </label>
            <label class="flex flex-col sm:flex-row items-center justify-between gap-3 p-4 rounded-xl bg-background/80 border-2 border-dashed border-primary/40 hover:border-primary cursor-pointer transition">
                <div class="flex items-center gap-3">
                    <span class="size-10 rounded-xl bg-primary/15 text-primary flex items-center justify-center text-lg font-bold shrink-0">
                        🧾
                    </span>
                    <div>
                        <p class="text-sm font-bold text-base" x-text="fileName || 'Haz clic aquí para elegir tu captura o PDF'"></p>
                        <p class="text-xs text-muted">Formatos: JPG, PNG, WEBP o PDF (Máx. 8 MB • Bóveda Privada)</p>
                    </div>
                </div>
                <span class="px-3 py-1.5 rounded-lg bg-primary/15 text-primary text-xs font-bold shrink-0">
                    Seleccionar archivo
                </span>
                <input type="file"
                       name="receipt_file"
                       required
                       accept=".jpg,.jpeg,.png,.webp,.pdf"
                       @change="fileName = $event.target.files[0]?.name || ''"
                       class="sr-only">
            </label>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-1.5">
                3. ID de Jugador / Nota de Entrega (Opcional)
            </label>
            <input type="text"
                   name="player_identifier"
                   maxlength="120"
                   placeholder="Opcional: Tu UID de jugador, Servidor, @usuario o instrucción rápida..."
                   class="w-full rounded-xl px-3.5 py-2.5 text-sm border focus:outline-none bg-background/90 border-neutral focus:border-primary text-base">
        </div>

        <div class="pt-1">
            <x-button.primary type="submit" class="w-full py-3.5 font-extrabold text-sm shadow-lg flex items-center justify-center gap-2">
                <span>Enviar Comprobante y Entrar a Sala de Validación P2P</span>
                <span>→</span>
            </x-button.primary>
        </div>
    </form>
</div>
