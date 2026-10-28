<?php

use Illuminate\Support\Facades\Route;
use Paymenter\Extensions\Gateways\OasisP2P\OasisP2P;

Route::middleware(['web'])->group(function () {
    // Endpoint principal alternativo /home -> /
    Route::get('/home', function () {
        return redirect('/');
    })->name('oasis.home');

    // Endpoint directo para cambio de moneda dinámico sin hardcoding y con sincronización total de carrito
    Route::get('/currency/{code}', function (string $code) {
        $code = strtoupper(trim($code));
        $currency = \App\Models\Currency::where('code', $code)->first();
        if ($currency) {
            session(['currency' => $currency->code]);
            try {
                // Actualizamos la moneda del carrito activo del visitante
                $cart = \App\Classes\Cart::getOnce();
                if ($cart && $cart->exists) {
                    $cart->currency_code = $currency->code;
                    $cart->save();
                }
                // Si el usuario está autenticado, sincronizamos sus carritos en base de datos
                if (auth()->check()) {
                    \App\Models\Cart::where('user_id', auth()->id())->update(['currency_code' => $currency->code]);
                }
            } catch (\Throwable $e) {
                // Silencioso
            }
        }
        return redirect()->back();
    })->name('oasis.currency.switch');

    Route::middleware(['auth', 'can:view,invoice', 'throttle:3,10'])
        ->post('/extensions/gateways/oasisp2p/invoice/{invoice}/receipt', [OasisP2P::class, 'submitReceipt'])
        ->name('extensions.gateways.oasisp2p.receipt');
});