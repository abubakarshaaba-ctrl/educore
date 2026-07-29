<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\JobApplicant;
use App\Models\JobPosting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentTransaction;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\Term;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PremiumDocumentTemplateTest extends TestCase
{
    public function test_certificate_template_renders_as_a_pdf(): void
    {
        $level = new ClassLevel(['name' => 'Senior Secondary School 3']);
        $arm = new ClassArm(['name' => 'Gold']);
        $arm->setRelation('classLevel', $level);

        $student = new Student([
            'first_name' => 'Amina',
            'middle_name' => 'Grace',
            'last_name' => 'Okafor',
            'admission_number' => 'EDU/2021/0042',
            'admission_date' => '2021-09-13',
            'graduation_date' => '2026-07-24',
        ]);
        $student->setRelation('currentClassArm', $arm);

        $pdf = Pdf::loadView('certificates.certificate-pdf', [
            'student' => $student,
            'tenant' => $this->tenant(),
            'type' => 'testimonial',
            'remarks' => 'A disciplined learner who served the school community with distinction.',
            'serial' => 'EDU-20260724-0042',
            'issuedAt' => Carbon::parse('2026-07-24'),
        ])->setPaper('a4', 'portrait')->output();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(15_000, strlen($pdf));
    }

    public function test_admission_offer_template_renders_as_a_pdf(): void
    {
        $level = new ClassLevel(['name' => 'Basic 7']);
        $admission = new Admission([
            'application_number' => 'ADM-2026-00142',
            'first_name' => 'David',
            'other_names' => 'Chinedu',
            'last_name' => 'Bello',
            'guardian_name' => 'Mrs Grace Bello',
            'guardian_address' => 'Abuja, FCT',
            'address' => 'Abuja, FCT',
            'academic_year' => '2026/2027',
        ]);
        $admission->setRelation('applyingForClassLevel', $level);

        $pdf = Pdf::loadView('admissions.offer-letter-pdf', [
            'admission' => $admission,
            'tenant' => $this->tenant(),
            'intro' => 'We are pleased to offer your child provisional admission.',
            'body' => 'This offer recognises the successful completion of the school admission process.',
            'closing' => 'We look forward to welcoming your family to our school community.',
            'signatory1' => 'Admissions Officer',
            'signatory2' => 'Principal',
        ])->setPaper('a4', 'portrait')->output();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(15_000, strlen($pdf));
    }

    public function test_job_offer_template_renders_as_a_pdf(): void
    {
        $posting = new JobPosting([
            'title' => 'Senior Mathematics Teacher',
            'department' => 'Academics',
        ]);
        $applicant = new JobApplicant([
            'name' => 'Fatima Mohammed',
            'email' => 'fatima@example.test',
            'phone' => '08030000000',
        ]);
        $applicant->id = 27;
        $applicant->setRelation('jobPosting', $posting);

        $pdf = Pdf::loadView('recruitment.job-offer-letter-pdf', [
            'applicant' => $applicant,
            'tenant' => $this->tenant(),
            'intro' => 'We are pleased to offer you employment with our school.',
            'body' => 'Your professionalism and teaching experience will strengthen our academic team.',
            'closing' => 'We look forward to your acceptance and contribution.',
            'signatory1' => 'Head of Human Resources',
            'signatory2' => 'Principal',
        ])->setPaper('a4', 'portrait')->output();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(15_000, strlen($pdf));
    }

    public function test_paid_invoice_print_view_shows_payment_and_remaining_balance(): void
    {
        $student = new Student([
            'first_name' => 'Amina',
            'last_name' => 'Okafor',
            'admission_number' => 'EDU/2025/0142',
        ]);
        $invoice = new Invoice([
            'invoice_number' => 'INV-2026-0142',
            'total_amount' => 120000,
            'amount_paid' => 75000,
            'status' => 'partially_paid',
            'due_date' => '2026-08-15',
        ]);
        $invoice->setRelation('student', $student);
        $invoice->setRelation('term', new Term(['name' => 'First Term']));
        $invoice->setRelation('items', collect([
            new InvoiceItem(['description' => 'Tuition', 'amount' => 120000]),
        ]));
        $invoice->setRelation('transactions', collect([
            new PaymentTransaction([
                'gateway_reference' => 'MAN-PAID-001',
                'gateway' => 'bank_transfer',
                'amount_paid' => 75000,
                'paid_at' => '2026-07-24 10:30:00',
                'status' => 'success',
            ]),
        ]));

        $html = view('fees.invoice-print', [
            'invoice' => $invoice,
            'tenant' => $this->tenant(),
        ])->render();

        $this->assertStringContainsString('Unpaid balance', $html);
        $this->assertStringContainsString('45,000.00', $html);
        $this->assertStringContainsString('MAN-PAID-001', $html);
    }

    private function tenant(): Tenant
    {
        return new Tenant([
            'name' => 'Greenfield Academy',
            'motto' => 'Knowledge, Character and Service',
            'address' => '12 School Road, Maitama, Abuja, FCT',
            'phone' => '08012345678',
            'email' => 'info@greenfield.example',
        ]);
    }
}
