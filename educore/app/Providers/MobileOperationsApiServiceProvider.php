<?php

namespace App\Providers;

use App\Http\Controllers\Api\MobileSubscriptionController;
use App\Http\Controllers\Api\MobileTransfersController;
use App\Http\Middleware\AuthenticateApiToken;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MobileOperationsApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::prefix('api/v1')
            ->middleware(AuthenticateApiToken::class)
            ->group(function (): void {
                Route::get('transfers', [MobileTransfersController::class, 'index']);
                Route::post('transfers/cross-school', [MobileTransfersController::class, 'requestCrossSchool']);
                Route::post('transfers/cross-school/{transfer}/approve', [MobileTransfersController::class, 'approveCrossSchool']);
                Route::post('transfers/cross-school/{transfer}/reject', [MobileTransfersController::class, 'rejectCrossSchool']);
                Route::post('transfers/intra-class', [MobileTransfersController::class, 'requestIntraClass']);
                Route::post('transfers/intra-class/{transfer}/approve', [MobileTransfersController::class, 'approveIntraClass']);
                Route::post('transfers/intra-class/{transfer}/reject', [MobileTransfersController::class, 'rejectIntraClass']);
                Route::post('transfers/intra-class/{transfer}/cancel', [MobileTransfersController::class, 'cancelIntraClass']);
                Route::post('transfers/interclass', [MobileTransfersController::class, 'requestInterclass']);
                Route::post('transfers/interclass/{transfer}/approve', [MobileTransfersController::class, 'approveInterclass']);
                Route::post('transfers/interclass/{transfer}/reject', [MobileTransfersController::class, 'rejectInterclass']);
                Route::post('transfers/interclass/{transfer}/cancel', [MobileTransfersController::class, 'cancelInterclass']);

                Route::get('admin/subscription', [MobileSubscriptionController::class, 'index']);
                Route::post('admin/subscription/invoices', [MobileSubscriptionController::class, 'createInvoice']);
                Route::post('admin/subscription/invoices/{invoice}/checkout', [MobileSubscriptionController::class, 'checkout']);
                Route::post('admin/subscription/invoices/{invoice}/bank-transfer', [MobileSubscriptionController::class, 'submitBankTransfer']);
                Route::post('admin/subscription/verify', [MobileSubscriptionController::class, 'verify']);
            });
    }
}
