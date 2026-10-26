<footer class="w-full px-4 py-6 mt-24 bg-background-secondary border-t border-neutral">
    <div class="container my-6 mx-auto px-4 sm:px-6 md:px-8 lg:px-10">
        <div class="flex flex-col md:flex-row justify-between gap-6 items-center">
            <div class="flex flex-col gap-3 items-center md:items-start">
                <div class="flex flex-row gap-2 items-center">
                    <x-logo class="h-10" />
                    @if(theme('logo_display', 'logo-only') != 'logo-only')
                    <span class="text-xl font-bold leading-none flex items-center">{{ config('app.name') }}</span>
                    @endif
                </div>
                <div class="text-xs text-muted">
                    © {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-background border border-neutral text-muted">
                    Transferencia P2P
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-background border border-neutral text-muted">
                    USDT / Crypto
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-primary/15 border border-primary/30 text-primary">
                    Entrega Digital Verificada
                </span>
            </div>
        </div>
    </div>
</footer>
