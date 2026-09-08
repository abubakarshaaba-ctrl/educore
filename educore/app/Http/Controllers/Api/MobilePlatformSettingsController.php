<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MobilePlatformSettingsController extends Controller
{
    public function updateSettings(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'settings' => ['required', 'array:platform_name,support_email,support_phone,support_whatsapp,support_website,office_address,grace_period_days,bank_transfer_bank_name,bank_transfer_account_name,bank_transfer_account_number,default_sms_gateway,sms_sender_id,maintenance_mode'],
            'settings.platform_name' => ['required', 'string', 'max:100'],
            'settings.support_email' => ['required', 'email', 'max:180'],
            'settings.support_phone' => ['required', 'string', 'max:30'],
            'settings.support_whatsapp' => ['required', 'string', 'max:30'],
            'settings.support_website' => ['required', 'url:http,https', 'max:200'],
            'settings.office_address' => ['required', 'string', 'max:255'],
            'settings.grace_period_days' => ['required', 'integer', 'min:0', 'max:90'],
            'settings.bank_transfer_bank_name' => ['nullable', 'required_with:settings.bank_transfer_account_name,settings.bank_transfer_account_number', 'string', 'max:120'],
            'settings.bank_transfer_account_name' => ['nullable', 'required_with:settings.bank_transfer_bank_name,settings.bank_transfer_account_number', 'string', 'max:160'],
            'settings.bank_transfer_account_number' => ['nullable', 'required_with:settings.bank_transfer_bank_name,settings.bank_transfer_account_name', 'string', 'max:30', 'regex:/^[0-9]+$/'],
            'settings.default_sms_gateway' => ['required', Rule::in(['termii', 'africas_talking', 'twilio'])],
            'settings.sms_sender_id' => ['required', 'string', 'min:3', 'max:11', 'regex:/^[A-Za-z0-9 ]+$/'],
            'settings.maintenance_mode' => ['required', 'boolean'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $definitions = $this->definitions();
        $changed = [];
        foreach ($data['settings'] as $key => $value) {
            $definition = $definitions[$key];
            $before = PlatformSetting::valueFor($key);
            PlatformSetting::setValue($key, $value, $definition['type'], $definition['group'], $definition['label']);
            if ($before !== $value) {
                $changed[] = $key;
            }
        }

        $this->audit($request, $user, 'platform.settings.updated', [
            'changed_keys' => $changed,
        ], trim($data['reason']));

        return response()->json([
            'message' => 'Platform settings saved.',
            'changed_keys' => $changed,
        ]);
    }

    public function updateGateway(Request $request, string $provider): JsonResponse
    {
        $user = $this->guard($request);
        abort_unless(in_array($provider, ['paystack', 'monnify', 'flutterwave'], true), 404);

        $rules = match ($provider) {
            'paystack' => [
                'public_key' => ['required', 'string', 'max:255', 'regex:/^pk_(test|live)_[A-Za-z0-9]+$/'],
                'secret_key' => ['nullable', 'string', 'max:255', 'regex:/^sk_(test|live)_[A-Za-z0-9]+$/'],
                'live' => ['required', 'boolean'],
            ],
            'monnify' => [
                'public_key' => ['required', 'string', 'max:255'],
                'secret_key' => ['nullable', 'string', 'max:255'],
                'contract_code' => ['required', 'string', 'max:100'],
                'live' => ['required', 'boolean'],
            ],
            default => [
                'public_key' => ['required', 'string', 'max:255'],
                'secret_key' => ['nullable', 'string', 'max:255'],
                'live' => ['required', 'boolean'],
            ],
        };
        $rules['reason'] = ['required', 'string', 'min:5', 'max:500'];
        $data = $request->validate($rules);

        $mapping = match ($provider) {
            'paystack' => [
                'public_key' => 'paystack_public_key',
                'secret_key' => 'paystack_secret_key',
                'live' => 'paystack_is_live',
            ],
            'monnify' => [
                'public_key' => 'monnify_api_key',
                'secret_key' => 'monnify_secret_key',
                'contract_code' => 'monnify_contract_code',
                'live' => 'monnify_is_live',
            ],
            default => [
                'public_key' => 'flutterwave_public_key',
                'secret_key' => 'flutterwave_secret_key',
                'live' => 'flutterwave_is_live',
            ],
        };

        PlatformSetting::setValue($mapping['public_key'], trim($data['public_key']), 'string', 'payments', Str::headline($mapping['public_key']));
        if (filled($data['secret_key'] ?? null)) {
            PlatformSetting::setValue($mapping['secret_key'], trim($data['secret_key']), 'encrypted', 'payments', Str::headline($mapping['secret_key']));
        }
        if (isset($mapping['contract_code'])) {
            PlatformSetting::setValue($mapping['contract_code'], trim($data['contract_code']), 'string', 'payments', Str::headline($mapping['contract_code']));
        }
        PlatformSetting::setValue($mapping['live'], (bool) $data['live'], 'boolean', 'payments', Str::headline($mapping['live']));

        $this->audit($request, $user, 'platform.gateway.updated', [
            'provider' => $provider,
            'public_identifier_changed' => true,
            'secret_replaced' => filled($data['secret_key'] ?? null),
            'contract_code_changed' => isset($mapping['contract_code']),
            'live' => (bool) $data['live'],
        ], trim($data['reason']));

        return response()->json([
            'message' => Str::headline($provider).' gateway settings saved.',
            'provider' => $provider,
            'secret_replaced' => filled($data['secret_key'] ?? null),
            'live' => (bool) $data['live'],
        ]);
    }

    private function guard(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_unless($user->isSuperAdmin(), 403, 'Platform Super Admin access required.');
        abort_unless(Schema::hasTable('platform_settings'), 503, 'Platform settings storage is unavailable.');

        return $user;
    }

    private function audit(Request $request, User $user, string $action, array $newValues, string $reason): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        AuditLog::create([
            'tenant_id' => null,
            'actor_user_id' => $user->id,
            'auditable_type' => PlatformSetting::class,
            'auditable_id' => 0,
            'action' => $action,
            'old_values' => [],
            'new_values' => $newValues,
            'reason' => $reason,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    private function definitions(): array
    {
        return [
            'platform_name' => ['type' => 'string', 'group' => 'general', 'label' => 'Platform Name'],
            'support_email' => ['type' => 'string', 'group' => 'contact', 'label' => 'Support Email'],
            'support_phone' => ['type' => 'string', 'group' => 'contact', 'label' => 'Support Phone'],
            'support_whatsapp' => ['type' => 'string', 'group' => 'contact', 'label' => 'Support WhatsApp'],
            'support_website' => ['type' => 'string', 'group' => 'contact', 'label' => 'Support Website'],
            'office_address' => ['type' => 'string', 'group' => 'contact', 'label' => 'Office Address'],
            'grace_period_days' => ['type' => 'integer', 'group' => 'billing', 'label' => 'Grace Period'],
            'bank_transfer_bank_name' => ['type' => 'string', 'group' => 'payments', 'label' => 'Bank Transfer Bank Name'],
            'bank_transfer_account_name' => ['type' => 'string', 'group' => 'payments', 'label' => 'Bank Transfer Account Name'],
            'bank_transfer_account_number' => ['type' => 'string', 'group' => 'payments', 'label' => 'Bank Transfer Account Number'],
            'default_sms_gateway' => ['type' => 'string', 'group' => 'notifications', 'label' => 'Default SMS Gateway'],
            'sms_sender_id' => ['type' => 'string', 'group' => 'notifications', 'label' => 'SMS Sender ID'],
            'maintenance_mode' => ['type' => 'boolean', 'group' => 'system', 'label' => 'Maintenance Mode'],
        ];
    }
}
