<div class="space-y-5 text-base"
     x-data="{
        channel: @js($spei['enabled'] ? 'spei_mexico' : 'crypto'),
        copiedKey: '',
        fileName: '',
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
    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2.5">
            1. Elige dónde transferir
        </label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @if($spei['enabled'])
            <button type="button"
                @click="channel = 'spei_mexico'"
                :class="channel === 'spei_mexico'
                    ? 'border-primary bg-primary/15 ring-2 ring-primary/30'
                    : 'border-neutral bg-background/80 hover:border-primary'"
                class="flex items-center justify-between p-4 rounded-xl border transition-all text-left">
                <div>
                    <p class="font-extrabold text-sm text-base">🇲🇽 Transferencia SPEI</p>
                    <p class="text-xs text-muted mt-0.5">CLABE Interbancaria 24/7</p>
                </div>
                <span class="oasis-mono-pill font-bold px-2 py-0.5 rounded bg-primary/20 text-primary">SPEI</span>
            </button>
            @endif

            @if($crypto['enabled'])
            <button type="button"
                @click="channel = 'crypto'"
                :class="channel === 'crypto'
                    ? 'border-primary bg-primary/15 ring-2 ring-primary/30'
                    : 'border-neutral bg-background/80 hover:border-primary'"
                class="flex items-center justify-between p-4 rounded-xl border transition-all text-left">
                <div>
                    <p class="font-extrabold text-sm text-base">⚡ Cripto Directo (USDT)</p>
                    <p class="text-xs text-muted mt-0.5">{{ $crypto['network'] }}</p>
                </div>
                <span class="oasis-mono-pill font-bold px-2 py-0.5 rounded bg-secondary/20 text-secondary">USDT</span>
            </button>
            @endif
        </div>
    </div>

    <!-- Datos Bancarios SPEI México (Click directo en el número para copiar) -->
    @if($spei['enabled'])
    <div x-show="channel === 'spei_mexico'" class="rounded-2xl bg-background/80 border border-neutral p-4 sm:p-5 space-y-4">
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

        <!-- CLABE: Click directo en la cápsula completa para copiar (Sin botones toscos) -->
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

        <!-- Concepto sugerido: Click directo en la píldora para copiar -->
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

    <!-- Datos Criptomonedas (Click directo en la dirección para copiar) -->
    @if($crypto['enabled'])
    <div x-show="channel === 'crypto'" class="rounded-2xl bg-background/80 border border-neutral p-4 sm:p-5 space-y-4">
        <div class="flex items-center justify-between text-xs pb-3 border-b border-neutral/60">
            <div>
                <span class="text-muted block text-[11px] font-semibold uppercase tracking-wider">Red Habilitada</span>
                <span class="font-bold text-sm sm:text-base text-primary mt-0.5 block">{{ $crypto['network'] }}</span>
            </div>
            <span class="oasis-mono-pill px-2.5 py-1 rounded-md bg-secondary/15 text-secondary border border-secondary/30 font-bold">
                USDT 1:1
            </span>
        </div>

        <div>
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-xs text-muted font-bold uppercase tracking-wider">Dirección de Wallet / USDT</span>
                <span class="text-[11px] text-muted hidden sm:inline">Toca la dirección para copiar</span>
            </div>

            <div @click="copyValue(@js($crypto['address']), 'crypto')"
                 role="button"
                 tabindex="0"
                 @keydown.enter="copyValue(@js($crypto['address']), 'crypto')"
                 @keydown.space.prevent="copyValue(@js($crypto['address']), 'crypto')"
                 class="group relative flex items-center justify-between p-3.5 sm:p-4 rounded-xl border transition-all duration-200 cursor-pointer select-none"
                 :class="copiedKey === 'crypto'
                    ? 'border-primary bg-primary/15 ring-2 ring-primary/30 shadow-[0_0_18px_rgba(16,185,129,0.25)]'
                    : 'border-neutral bg-background/90 hover:border-primary/70 hover:bg-primary/5 active:scale-[0.99]'"
                 title="Haz clic para copiar la dirección">
                
                <div class="overflow-hidden pr-2">
                    <code class="text-xs sm:text-sm font-mono font-extrabold tracking-wider break-all transition-colors duration-150"
                          :class="copiedKey === 'crypto' ? 'text-primary' : 'text-base group-hover:text-primary'">
                        {{ $crypto['address'] }}
                    </code>
                </div>

                <div class="flex items-center gap-1.5 text-xs font-semibold shrink-0 ml-2">
                    <span x-show="copiedKey !== 'crypto'" class="text-muted group-hover:text-primary transition-colors flex items-center gap-1.5 text-[11px]">
                        <svg class="size-3.5 transition-transform group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                        <span>Copiar</span>
                    </span>
                    <span x-show="copiedKey === 'crypto'" class="text-primary font-extrabold flex items-center gap-1 text-xs">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>¡Copiada!</span>
                    </span>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Paso 2: Subida Directa de Captura (Limpio y directo) -->
    <form action="{{ route('extensions.gateways.oasisp2p.receipt', $invoice) }}"
          method="POST"
          enctype="multipart/form-data"
          class="space-y-4 pt-3 border-t border-neutral">
        @csrf
        <input type="hidden" name="payment_channel" :value="channel">

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
                   placeholder="Opcional: Tu UID, @usuario o instrucción rápida si aplica..."
                   class="w-full rounded-xl px-3.5 py-2.5 text-sm border focus:outline-none">
        </div>

        <div class="pt-1">
            <x-button.primary type="submit" class="w-full py-3.5 font-extrabold text-sm shadow-lg">
                Enviar Captura y Entrar a Sala de Validación P2P →
            </x-button.primary>
        </div>
    </form>
</div>
