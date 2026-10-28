@php
    $oasisGateway = \App\Models\Gateway::where('extension', 'OasisP2P')->where('enabled', true)->first();
    $oasisPayView = ($oasisGateway && $invoice->status === 'pending')
        ? \App\Helpers\ExtensionHelper::pay($oasisGateway, $invoice)
        : null;
@endphp

<x-modal :title="oasis_t('Pago P2P — Factura #') . ($invoice->number ?: $invoice->id)" open>
    <x-slot name="closeTrigger">
        <div class="flex items-center gap-4">
            <span class="text-sm font-bold text-primary">{{ $invoice->formattedRemaining }}</span>
            <button wire:click="exitPay" @click="open = false" class="text-muted hover:text-base">
                <x-ri-close-fill class="size-6" />
            </button>
        </div>
    </x-slot>

    @if ($this->pay)
        <div class="mt-4">{!! $this->pay !!}</div>
    @elseif ($oasisPayView)
        <div class="mt-4">{!! $oasisPayView !!}</div>
    @else
        @include('invoices.partials.payment-options')
    @endif
</x-modal>
