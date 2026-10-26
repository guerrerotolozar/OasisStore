<?php

use Illuminate\Support\Facades\Route;
use Paymenter\Extensions\Gateways\OasisP2P\OasisP2P;

Route::middleware(['web', 'auth', 'can:view,invoice', 'throttle:3,10'])
    ->post('/extensions/gateways/oasisp2p/invoice/{invoice}/receipt', [OasisP2P::class, 'submitReceipt'])
    ->name('extensions.gateways.oasisp2p.receipt');
