<div class="container mt-10 space-y-8">
    <!-- Encabezado del Dashboard de Cliente -->
    <div class="bg-background-secondary border border-neutral rounded-2xl p-6 sm:p-8 shadow-lg">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div class="space-y-2">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold tracking-wider uppercase bg-primary/15 text-primary border border-primary/30">
                    <span class="size-2 rounded-full bg-primary animate-pulse"></span>
                    {{ oasis_t('Centro de Control del Cliente') }}
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-base">
                    {{ oasis_t('Hola') }}, {{ Auth::user()->name }}
                </h1>
                <p class="text-xs sm:text-sm text-muted">
                    {{ oasis_t('Gestiona tus entregas digitales activas, órdenes en validación manual P2P y comprobantes de pago.') }}
                </p>
            </div>

            <!-- Tarjetas KPI Rápidas -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 shrink-0">
                <div class="bg-background border border-neutral rounded-xl p-3.5 text-center">
                    <span class="text-xs text-muted block">{{ oasis_t('Activos') }}</span>
                    <span class="text-xl font-extrabold text-primary mt-0.5 block">
                        {{ Auth::user()->services()->where('status', 'active')->count() }}
                    </span>
                </div>
                <div class="bg-background border border-neutral rounded-xl p-3.5 text-center">
                    <span class="text-xs text-muted block">{{ oasis_t('En Revisión') }}</span>
                    <span class="text-xl font-extrabold text-secondary mt-0.5 block">
                        {{ Auth::user()->services()->where('status', 'pending')->count() }}
                    </span>
                </div>
                <div class="bg-background border border-neutral rounded-xl p-3.5 text-center">
                    <span class="text-xs text-muted block">{{ oasis_t('Por Pagar') }}</span>
                    <span class="text-xl font-extrabold text-base mt-0.5 block">
                        {{ Auth::user()->invoices()->where('status', 'pending')->count() }}
                    </span>
                </div>
                <div class="bg-background border border-neutral rounded-xl p-3.5 text-center">
                    <span class="text-xs text-muted block">{{ oasis_t('Tickets P2P') }}</span>
                    <span class="text-xl font-extrabold text-primary mt-0.5 block">
                        {{ Auth::user()->tickets()->where('status', '!=', 'closed')->count() }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid Principal de Operaciones del Cliente -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
        <div class="grid gap-8 items-start">
            <!-- Productos / Entregas Digitales Activas -->
            <div class="bg-background-secondary border border-neutral rounded-2xl p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="bg-background border border-neutral p-2 rounded-lg text-primary">
                            <x-ri-archive-stack-fill class="size-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-base">{{ oasis_t('Mis Compras & Entregas') }}</h2>
                            <p class="text-xs text-muted">{{ oasis_t('Paquetes digitales activos en tu cuenta') }}</p>
                        </div>
                    </div>
                    <span class="bg-primary flex items-center justify-center font-semibold rounded-md size-5 text-sm text-white">
                        {{ Auth::user()->services()->where('status', 'active')->count() }}
                    </span>
                </div>

                <div class="space-y-4">
                    <livewire:services.widget status="active" />
                </div>

                <x-navigation.link class="bg-background hover:bg-background-secondary border border-neutral flex items-center justify-center rounded-lg py-2.5 text-xs font-semibold"
                    :href="route('services')">
                    {{ oasis_t('Ver todas mis órdenes') }}
                    <x-ri-arrow-right-fill class="size-5" />
                </x-navigation.link>
            </div>

            <!-- Tickets de Soporte y Comprobantes P2P -->
            @if(!config('settings.tickets_disabled', false))
            <div class="bg-background-secondary border border-neutral rounded-2xl p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="bg-background border border-neutral p-2 rounded-lg text-primary">
                            <x-ri-customer-service-fill class="size-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-base">{{ oasis_t('Tickets & Comprobantes P2P') }}</h2>
                            <p class="text-xs text-muted">{{ oasis_t('Seguimiento de pagos y soporte directo') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('tickets.create') }}" wire:navigate class="px-2.5 py-1 rounded-lg text-xs font-semibold border border-primary/30 bg-primary/15 text-primary">
                            {{ oasis_t('+ Nuevo') }}
                        </a>
                        <span class="bg-primary flex items-center justify-center font-semibold rounded-md size-5 text-sm text-white">
                            {{ Auth::user()->tickets()->where('status', '!=', 'closed')->count() }}
                        </span>
                    </div>
                </div>

                <div class="space-y-4">
                    <livewire:tickets.widget />
                </div>

                <x-navigation.link class="bg-background hover:bg-background-secondary border border-neutral flex items-center justify-center rounded-lg py-2.5 text-xs font-semibold"
                    :href="route('tickets')">
                    {{ oasis_t('Ver todos mis tickets') }}
                    <x-ri-arrow-right-fill class="size-5" />
                </x-navigation.link>
            </div>
            @endif
        </div>

        <div class="grid gap-8 items-start">
            <!-- Facturas Pendientes de Pago -->
            <div class="bg-background-secondary border border-neutral rounded-2xl p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="bg-background border border-neutral p-2 rounded-lg text-secondary">
                            <x-ri-receipt-fill class="size-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-base">{{ oasis_t('Facturas Pendientes de Pago') }}</h2>
                            <p class="text-xs text-muted">{{ oasis_t('Selecciona una factura para enviar tu comprobante P2P') }}</p>
                        </div>
                    </div>
                    <span class="bg-primary flex items-center justify-center font-semibold rounded-md size-5 text-sm text-white">
                        {{ Auth::user()->invoices()->where('status', 'pending')->count() }}
                    </span>
                </div>

                <div class="space-y-4">
                    <livewire:invoices.widget :limit="5" />
                </div>

                <x-navigation.link class="bg-background hover:bg-background-secondary border border-neutral flex items-center justify-center rounded-lg py-2.5 text-xs font-semibold"
                    :href="route('invoices')">
                    {{ oasis_t('Ver historial de facturas') }}
                    <x-ri-arrow-right-fill class="size-5" />
                </x-navigation.link>
            </div>

            <!-- Guía Rápida de Validación P2P -->
            <div class="bg-background-secondary border border-neutral rounded-2xl p-6 space-y-3">
                <h3 class="text-sm font-bold text-base uppercase tracking-wider">{{ oasis_t('¿Cómo funciona la entrega P2P?') }}</h3>
                <div class="space-y-2 text-xs text-muted">
                    <p><strong class="text-primary">{{ oasis_t('1. Genera tu orden:') }}</strong> {{ oasis_t('Elige tu paquete en el catálogo y selecciona pago por Transferencia o Cripto.') }}</p>
                    <p><strong class="text-secondary">{{ oasis_t('2. Adjunta tu comprobante:') }}</strong> {{ oasis_t('Dentro de la factura podrás subir tu captura o PDF; el sistema abrirá un ticket privado automáticamente.') }}</p>
                    <p><strong class="text-primary">{{ oasis_t('3. Entrega verificada:') }}</strong> {{ oasis_t('Nuestro sistema notifica al instante al administrador para validar y activar tu producto.') }}</p>
                </div>
            </div>

            {!! hook('pages.dashboard') !!}
        </div>
    </div>
</div>
