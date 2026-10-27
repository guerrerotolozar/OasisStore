<?php

use Illuminate\Support\Facades\Route;
use Paymenter\Extensions\Gateways\OasisP2P\OasisP2P;

Route::middleware(['web'])->group(function () {
    // Endpoint principal alternativo /home -> /
    Route::get('/home', function () {
        return redirect('/');
    })->name('oasis.home');

    // Endpoint directo para cambio de moneda instantáneo sin choques de carrito
    Route::get('/currency/{code}', function (string $code) {
        $allowed = ['COP', 'USD', 'MXN'];
        $code = strtoupper($code);
        if (in_array($code, $allowed)) {
            session(['currency' => $code]);
            try {
                \App\Classes\Cart::clear();
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
