<?php

namespace App\Providers;

use App\Http\Controllers\Api\MobileSubscriptionController;
use App\Http\Middleware\AuthenticateApiToken;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MobileSubscriptionApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::prefix('api/v1')
            ->middleware(AuthenticateApiToken::class)
            ->group(function (): void {
                Route::get('admin/subscription', [MobileSubscriptionController::class, 'index']);
                Route::post('admin/subscription/invoices', [MobileSubscriptionController::class, 'createInvoice']);
                Route::post('admin/subscription/invoices/{invoice}/checkout', [MobileSubscriptionController::class, 'checkout']);
                Route::post('admin/subscription/invoices/{invoice}/bank-transfer', [MobileSubscriptionController::class, 'submitBankTransfer']);
                Route::post('admin/subscription/verify', [MobileSubscriptionController::class, 'verify']);
            });
    }
}
