<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGatewayConfig extends BaseTenantModel
{
    protected $table = 'payment_gateway_configs';

    protected $fillable = [
        'tenant_id',
        'gateway',
        'public_key',
        'secret_key',
        'contract_code',
        'is_live',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'secret_key' => 'encrypted',
            'is_live'    => 'boolean',
            'is_active'  => 'boolean',
        ];
    }
}
