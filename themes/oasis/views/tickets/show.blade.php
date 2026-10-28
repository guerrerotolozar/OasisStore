<div class="container mt-8 space-y-6" x-data="{
    lightboxSrc: '',
    lightboxTitle: '',
    openReceipt(src, title) {
        this.lightboxSrc = src;
        this.lightboxTitle = title || 'Comprobante P2P';
        const dlg = document.getElementById('oasis-receipt-lightbox');
        if (dlg) dlg.showModal();
    }
}">
    <!-- Pipeline P2P de 3 Pasos -->
    <div class="oasis-pipeline">
        <div class="oasis-step oasis-step-done">
            <span class="oasis-step-num">✓</span>
            <div>
                <p class="font-bold text-xs leading-tight">{{ oasis_t('1. Orden P2P Emitida') }}</p>
                <p class="text-[11px] text-muted">{{ oasis_t('Comprobante registrado en bóveda') }}</p>
            </div>
        </div>
        <div class="oasis-step {{ $ticket->status === 'closed' ? 'oasis-step-done' : 'oasis-step-active' }}">
            <span class="oasis-step-num">{{ $ticket->status === 'closed' ? '✓' : '2' }}</span>
            <div>
                <p class="font-bold text-xs leading-tight">{{ oasis_t('2. Sala de Validación P2P') }}</p>
                <p class="text-[11px] text-muted">
                    {{ $ticket->status === 'closed' ? oasis_t('Verificación finalizada') : oasis_t('Revisión directa con Administrador') }}
                </p>
            </div>
        </div>
        <div class="oasis-step {{ $ticket->status === 'closed' ? 'oasis-step-active' : '' }}">
            <span class="oasis-step-num">3</span>
            <div>
                <p class="font-bold text-xs leading-tight">{{ oasis_t('3. Liberación de Paquete') }}</p>
                <p class="text-[11px] text-muted">{{ oasis_t('Activación inmediata tras validar') }}</p>
            </div>
        </div>
    </div>

    <!-- Encabezado de la Sala de Orden -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="oasis-mono-pill px-2.5 py-0.5 rounded-md bg-primary/15 text-primary border border-primary/30 font-bold">
                    {{ oasis_t('SALA P2P') }} #{{ $ticket->id }}
                </span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider {{ $ticket->status == 'open' ? 'bg-primary/15 text-primary border border-primary/30' : ($ticket->status == 'closed' ? 'bg-neutral text-muted' : 'bg-secondary/15 text-secondary border border-secondary/30') }}">
                    <span class="size-1.5 rounded-full {{ $ticket->status == 'open' ? 'bg-primary animate-pulse' : 'bg-secondary' }}"></span>
                    @if ($ticket->status == 'open')
                        {{ oasis_t('En Revisión Activa') }}
                    @elseif ($ticket->status == 'closed')
                        {{ oasis_t('Orden Cerrada') }}
                    @else
                        {{ oasis_t('Respuesta del Admin') }}
                    @endif
                </span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-base mt-1.5">
                {{ $ticket->subject }}
            </h1>
        </div>

        <a href="{{ route('dashboard') }}" wire:navigate
           class="px-3.5 py-2 rounded-xl text-xs font-semibold border bg-background-secondary border-neutral text-muted hover:text-base transition self-start sm:self-auto">
            {{ oasis_t('← Volver a Mi Panel') }}
        </a>
    </div>

    <!-- Layout Dividido: Chat de Orden P2P (Izquierda) + Voucher de Estado (Derecha) -->
    <div class="oasis-split-layout">
        <!-- Columna Principal: Historial de Mensajes + Barra de Chat Directa -->
        <div class="space-y-4">
            <div class="oasis-terminal p-4 sm:p-6 space-y-4" wire:poll.10s>
                @foreach ($ticket->messages()->with('user', 'attachments')->get() as $index => $message)
                @php
                    $isClient = $message->user_id === $ticket->user_id;
                @endphp
                <div class="rounded-xl p-4 border transition {{ $isClient ? 'bg-background/70 border-neutral' : 'bg-primary/5 border-primary/35' }}">
                    <div class="flex items-center justify-between gap-2 pb-2.5 mb-2.5 border-b border-neutral/60">
                        <div class="flex items-center gap-2.5">
                            <img src="{{ $message->user->avatar }}" class="size-7 rounded-full border border-neutral" alt="{{ $message->user->name }}" />
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-base">{{ $message->user->name }}</span>
                                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded {{ $isClient ? 'bg-background-secondary text-muted border border-neutral' : 'bg-primary/20 text-primary' }}">
                                        {{ $isClient ? ($index === 0 ? oasis_t('Comprobante Inicial') : oasis_t('Cliente')) : oasis_t('Administrador Oasis') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <span class="text-xs text-muted oasis-mono-pill">
                            {{ $message->created_at->diffForHumans() }}
                        </span>
                    </div>

                    <!-- Contenido del Mensaje -->
                    <div class="prose dark:prose-invert prose-sm max-w-none text-base break-words leading-relaxed">
                        {!! Str::markdown($message->message, [
                            'html_input' => 'strip',
                            'allow_unsafe_links' => false,
                        ]) !!}
                    </div>

                    <!-- Miniaturas Compactas de Comprobantes Adjuntos -->
                    @if ($message->attachments->isNotEmpty())
                    <div class="mt-3 pt-3 border-t border-neutral/60 flex flex-wrap gap-3">
                        @foreach($message->attachments as $attachment)
                            @if($attachment->canPreview())
                            <div class="oasis-receipt-card">
                                <div class="flex items-center justify-between gap-2 text-[11px] text-muted">
                                    <span class="truncate font-semibold text-base" title="{{ $attachment->filename }}">
                                        🧾 {{ $attachment->filename }}
                                    </span>
                                    <span class="oasis-mono-pill shrink-0">{{ $attachment->formattedSize }}</span>
                                </div>

                                <img src="{{ $attachment->url }}"
                                     alt="{{ $attachment->filename }}"
                                     loading="lazy"
                                     @click="openReceipt(@js($attachment->url), @js($attachment->filename))"
                                     class="oasis-receipt-thumb" />

                                <div class="flex items-center justify-between gap-2 pt-0.5">
                                    <button type="button"
                                            @click="openReceipt(@js($attachment->url), @js($attachment->filename))"
                                            class="text-[11px] font-bold text-primary hover:underline flex items-center gap-1">
                                        {{ oasis_t('🔍 Ampliar captura') }}
                                    </button>
                                    <a href="{{ $attachment->url }}" target="_blank" rel="noopener"
                                       class="text-[11px] text-muted hover:text-base">
                                        {{ oasis_t('↗ Original') }}
                                    </a>
                                </div>
                            </div>
                            @else
                            <a href="{{ $attachment->url }}" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-background border border-neutral hover:border-primary text-xs font-semibold transition">
                                <x-ri-attachment-2 class="size-4 text-primary" />
                                <span>{{ $attachment->filename }}</span>
                                <span class="text-muted oasis-mono-pill">({{ $attachment->formattedSize }})</span>
                            </a>
                            @endif
                        @endforeach
                    </div>
                    @endif
                </div>
                @endforeach
            </div>

            <!-- Composer Directo de Chat P2P (Sin barra pesada de Markdown) -->
            <div class="oasis-terminal p-4 sm:p-5">
                <form wire:submit.prevent="save" id="reply" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label for="oasis-direct-msg" class="text-xs font-bold uppercase tracking-wider text-muted flex items-center gap-1.5">
                            <span class="size-2 rounded-full bg-primary"></span>
                            {{ oasis_t('Chat Directo con el Validador P2P') }}
                        </label>
                        <span class="text-[11px] text-muted">{{ oasis_t('Respuesta en tiempo real') }}</span>
                    </div>

                    <div class="oasis-composer space-y-3">
                        <textarea id="oasis-direct-msg"
                                  wire:model="message"
                                  rows="2"
                                  required
                                  placeholder="{{ oasis_t('Escribe aquí cualquier dato adicional (ej. ID de jugador, nota sobre tu transferencia o consulta rápida)...') }}"
                                  @keydown.ctrl.enter="$el.form.requestSubmit()"></textarea>

                        <!-- Píldoras de archivos seleccionados para subir -->
                        @if (!empty($attachments))
                        <div class="flex flex-wrap gap-2 pt-2 border-t border-neutral/60">
                            @foreach ($attachments as $att)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-primary/15 border border-primary/30 text-xs font-semibold text-primary">
                                📎 {{ method_exists($att, 'getClientOriginalName') ? $att->getClientOriginalName() : 'Archivo listo' }}
                            </span>
                            @endforeach
                        </div>
                        @endif

                        <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-neutral/60">
                            <div class="flex items-center gap-2">
                                <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-background-secondary border border-neutral hover:border-primary text-xs font-semibold text-muted hover:text-base transition">
                                    <x-ri-image-add-line class="size-4 text-primary" />
                                    <span>{{ oasis_t('Adjuntar imagen / PDF') }}</span>
                                    <input type="file"
                                           wire:model="attachments"
                                           multiple
                                           accept=".jpg,.jpeg,.png,.webp,.pdf"
                                           class="sr-only" />
                                </label>

                                <span wire:loading wire:target="attachments" class="text-xs text-primary font-semibold flex items-center gap-1">
                                    <x-ri-loader-5-fill class="size-3.5 animate-spin" />
                                    {{ oasis_t('Cargando archivo...') }}
                                </span>
                            </div>

                            <x-button.primary type="submit" class="!w-auto px-5 py-2 text-xs font-bold" wire:loading.attr="disabled">
                                <x-loading target="save" />
                                <div wire:loading.remove wire:target="save" class="flex items-center gap-1.5">
                                    <span>{{ oasis_t('Enviar Mensaje') }}</span>
                                    <span>→</span>
                                </div>
                            </x-button.primary>
                        </div>
                    </div>
                    <x-input-error class="mt-1" :messages="$errors->get('message')" />
                    <x-input-error class="mt-1" :messages="$errors->get('attachments.*')" />
                </form>
            </div>
        </div>

        <!-- Columna Derecha (Sticky): Ficha / Voucher de Estado de la Orden P2P -->
        <div class="oasis-voucher oasis-sticky-panel p-6 space-y-5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-primary">{{ oasis_t('Ficha de Custodia P2P') }}</span>
                <span class="oasis-mono-pill px-2 py-0.5 rounded bg-background border border-neutral text-muted">
                    ID #{{ $ticket->id }}
                </span>
            </div>

            <div class="space-y-3 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-muted">{{ oasis_t('Estado actual:') }}</span>
                    <span class="font-bold {{ $ticket->status == 'open' ? 'text-primary' : 'text-base' }}">
                        {{ $ticket->status == 'open' ? oasis_t('Validando Comprobante') : ucfirst($ticket->status) }}
                    </span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-muted">{{ oasis_t('Apertura:') }}</span>
                    <span class="font-semibold text-base oasis-mono-pill">{{ $ticket->created_at->format('d M Y, H:i') }}</span>
                </div>

                @if ($ticket->service)
                <div class="pt-2 border-t border-neutral/60">
                    <span class="text-xs text-muted block">{{ oasis_t('Paquete vinculado:') }}</span>
                    <span class="font-bold text-base">{{ oasis_t($ticket->service->product->name) }}</span>
                </div>
                @endif
            </div>

            <div class="oasis-voucher-divider"></div>

            <!-- Garantía de Entrega P2P -->
            <div class="rounded-xl bg-background/70 border border-neutral p-3.5 space-y-2 text-xs">
                <p class="font-bold text-base flex items-center gap-1.5">
                    <span class="text-primary">⚡</span> {{ oasis_t('Notificación Push Enviada') }}
                </p>
                <p class="text-muted leading-relaxed">
                    {{ oasis_t('Tu comprobante está almacenado en bóveda privada y nuestro administrador ya recibió la alerta directa en su dispositivo móvil.') }}
                </p>
            </div>

            @if ($ticket->status !== 'closed')
            <div class="pt-1">
                <button type="button"
                        wire:click="closeTicket"
                        class="w-full py-2 px-3 rounded-xl border border-neutral hover:border-red-500/50 text-xs font-semibold text-muted hover:text-red-400 transition">
                    {{ oasis_t('Marcar orden como resuelta / Cerrar sala') }}
                </button>
            </div>
            @endif
        </div>
    </div>

    <!-- Modal Nativo <dialog closedby="any"> para Inspección de Comprobante -->
    <dialog id="oasis-receipt-lightbox"
            closedby="any"
            aria-label="Vista ampliada del comprobante P2P"
            class="oasis-lightbox">
        <div class="p-4 sm:p-5 space-y-3">
            <div class="flex items-center justify-between gap-4 border-b border-neutral pb-3">
                <div class="flex items-center gap-2 overflow-hidden">
                    <span class="text-primary">🧾</span>
                    <h3 class="text-sm font-bold text-base truncate" x-text="lightboxTitle">{{ oasis_t('Comprobante') }}</h3>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a :href="lightboxSrc" target="_blank" rel="noopener"
                       class="px-2.5 py-1 rounded-lg bg-background-secondary border border-neutral text-xs font-semibold text-muted hover:text-base">
                        {{ oasis_t('↗ Pestaña nueva') }}
                    </a>
                    <form method="dialog">
                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-primary/20 text-primary text-xs font-bold hover:bg-primary/30">
                            {{ oasis_t('✕ Cerrar') }}
                        </button>
                    </form>
                </div>
            </div>

            <div class="flex items-center justify-center bg-background rounded-xl p-2 max-h-[75vh] overflow-auto">
                <img :src="lightboxSrc" :alt="lightboxTitle" class="max-h-[70vh] w-auto object-contain rounded-lg" />
            </div>
        </div>
    </dialog>

    <script>
        (function () {
            const dialog = document.getElementById('oasis-receipt-lightbox');
            if (dialog && !('closedBy' in HTMLDialogElement.prototype)) {
                dialog.addEventListener('click', (event) => {
                    if (event.target !== dialog) return;
                    const rect = dialog.getBoundingClientRect();
                    const isDialogContent = (
                        rect.top <= event.clientY &&
                        event.clientY <= rect.top + rect.height &&
                        rect.left <= event.clientX &&
                        event.clientX <= rect.left + rect.width
                    );
                    if (isDialogContent) return;
                    dialog.close();
                });
            }
        })();
    </script>
</div>
