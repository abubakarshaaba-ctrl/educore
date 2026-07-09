<?php

namespace App\Http\Controllers;

use App\Models\LetterTemplate;
use Illuminate\Http\Request;

class LetterTemplateController extends Controller
{
    public function edit()
    {
        $tenant = auth()->user()->tenant;

        $admissionOffer = LetterTemplate::forTenant($tenant->id, LetterTemplate::TYPE_ADMISSION_OFFER);
        $jobOffer = LetterTemplate::forTenant($tenant->id, LetterTemplate::TYPE_JOB_OFFER);

        return view('settings.letter-templates', compact('admissionOffer', 'jobOffer'));
    }

    public function update(Request $request, string $type)
    {
        abort_unless(in_array($type, [LetterTemplate::TYPE_ADMISSION_OFFER, LetterTemplate::TYPE_JOB_OFFER]), 404);

        $data = $request->validate([
            'intro_text'         => ['nullable', 'string', 'max:2000'],
            'body_text'          => ['nullable', 'string', 'max:5000'],
            'closing_text'       => ['nullable', 'string', 'max:2000'],
            'signatory_1_label'  => ['nullable', 'string', 'max:100'],
            'signatory_2_label'  => ['nullable', 'string', 'max:100'],
        ]);

        LetterTemplate::updateOrCreate(
            ['tenant_id' => auth()->user()->tenant_id, 'type' => $type],
            $data
        );

        return back()->with('success', 'Letter template saved.');
    }
}
