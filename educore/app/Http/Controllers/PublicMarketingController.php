<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PublicMarketingController extends Controller
{
    public function index(): View
    {
        $stats = [
            'schools' => Tenant::publiclyAccessible()->count(),
            'students' => Student::withoutTenantScope()->count(),
            'staff' => User::whereNotNull('tenant_id')->where('is_active', true)->count(),
        ];

        $tiers = \App\Services\PricingService::tiers();

        return view('welcome', compact('stats', 'tiers'));
    }

    public function privacy(): View
    {
        return view('legal.privacy');
    }

    public function terms(): View
    {
        return view('legal.terms');
    }

    public function sendContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $recipient = config('portal.contact_email');
        $body = implode("\n", [
            'New EduCore contact request',
            '',
            'Name: ' . $data['name'],
            'Email: ' . $data['email'],
            'Subject: ' . $data['subject'],
            '',
            $data['message'],
        ]);

        $this->dispatchInquiryMail($recipient, 'EduCore contact request: ' . $data['subject'], $body, $data['email'], $data['name']);

        return back()->with('contact_status', 'Thanks. Your message has been sent to EduCore.');
    }

    public function sendSchoolOnboarding(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'school_name' => ['required', 'string', 'max:180'],
            'school_slug' => ['nullable', 'string', 'max:120'],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:180'],
            'admin_phone' => ['required', 'string', 'max:40'],
            'package' => ['required', 'string', 'max:120'],
            'estimated_students' => ['nullable', 'integer', 'min:1', 'max:200000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $recipient = config('portal.school_onboarding_email');
        $body = implode("\n", [
            'New school onboarding request',
            '',
            'School: ' . $data['school_name'],
            'Preferred slug: ' . ($data['school_slug'] ?: 'Not provided'),
            'Package: ' . $data['package'],
            'Estimated students: ' . ($data['estimated_students'] ?? 'Not provided'),
            '',
            'Administrator: ' . $data['admin_name'],
            'Administrator email: ' . $data['admin_email'],
            'Administrator phone: ' . $data['admin_phone'],
            '',
            'Notes:',
            $data['notes'] ?: 'None',
        ]);

        $this->dispatchInquiryMail($recipient, 'EduCore school onboarding request: ' . $data['school_name'], $body, $data['admin_email'], $data['admin_name']);

        return back()->with('onboarding_status', 'Your school onboarding request has been sent. EduCore will follow up shortly.');
    }

    private function dispatchInquiryMail(string $recipient, string $subject, string $body, string $replyEmail, string $replyName): void
    {
        try {
            Mail::raw($body, function (Message $message) use ($recipient, $subject, $replyEmail, $replyName) {
                $message->to($recipient)->subject($subject);
                $message->replyTo($replyEmail, $replyName);
            });
        } catch (\Throwable $e) {
            Log::warning('EduCore inquiry mail could not be sent', [
                'recipient' => $recipient,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
