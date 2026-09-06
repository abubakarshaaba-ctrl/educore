<?php

namespace App\Services\Mobile;

use App\Models\AcademicSession;
use App\Models\AcademicTrack;
use App\Models\Admission;
use App\Models\ClassLevelSubject;
use App\Models\Guardian;
use App\Models\Hostel;
use App\Models\HostelAllocation;
use App\Models\Invoice;
use App\Models\LibraryBook;
use App\Models\LibraryLoan;
use App\Models\PaymentTransaction;
use App\Models\PayrollPeriod;
use App\Models\SchoolAsset;
use App\Models\SchoolExpense;
use App\Models\StudentHealthRecord;
use App\Models\Subject;
use App\Models\Term;
use App\Models\TransportBus;
use App\Models\TransportRoute;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class MobileOperationsService
{
    private const MODULES = [
        'fees' => ['Fees & payments', 'Invoices, balances and verified transactions', 'fees'],
        'expenses' => ['Expenses', 'Recorded school expenditure', 'expenses'],
        'payroll' => ['Payroll', 'Authoritative payroll periods', 'payroll'],
        'admissions' => ['Admissions', 'Applications and current decisions', 'admissions'],
        'library' => ['Library', 'Catalogue availability and active loans', 'library'],
        'transport' => ['Transport', 'Routes, vehicles and rider capacity', 'transport'],
        'health' => ['Health records', 'Student health coverage and alerts', 'health'],
        'inventory' => ['Inventory', 'School assets and condition register', 'inventory'],
        'hostels' => ['Hostels', 'Boarding capacity and active allocations', 'hostels'],
        'subjects' => ['Subjects', 'Active subject catalogue', 'subjects'],
        'curriculum' => ['Curriculum', 'Tracks and class-level subject rules', 'curriculum'],
        'academic-cycle' => ['Academic sessions', 'Sessions, terms and current cycle', 'academic-cycle'],
    ];

    public function for(User $user, string $requestedModule): array
    {
        $module = $requestedModule === 'parent.fees' ? 'fees' : $requestedModule;
        abort_unless(isset(self::MODULES[$module]), 404, 'This operations module is not available.');
        $this->authorize($user, $module, $requestedModule);

        [$title, $description] = self::MODULES[$module];
        $payload = match ($module) {
            'fees' => $this->fees($user),
            'expenses' => $this->expenses($user),
            'payroll' => $this->payroll($user),
            'admissions' => $this->admissions($user),
            'library' => $this->library($user),
            'transport' => $this->transport($user),
            'health' => $this->health($user),
            'inventory' => $this->inventory($user),
            'hostels' => $this->hostels($user),
            'subjects' => $this->subjects($user),
            'curriculum' => $this->curriculum($user),
            'academic-cycle' => $this->academicCycle($user),
        };

        return [
            'contract_version' => 1,
            'module' => [
                'key' => $module,
                'title' => $title,
                'description' => $description,
                'can_manage' => ! $user->isParent() && $user->canManage(self::MODULES[$module][2]),
                'mobile_policy' => 'read_first',
            ],
            ...$payload,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function authorize(User $user, string $module, string $requestedModule): void
    {
        if ($requestedModule === 'parent.fees') {
            abort_unless($user->isParent(), 403, 'Parent fee access required.');

            return;
        }

        abort_if($user->isStudent() || $user->isParent() || $user->isSuperAdmin(), 403, 'School operations access required.');
        abort_unless($user->canAccessModule(self::MODULES[$module][2]), 403, 'You do not have access to this module.');
    }

    private function fees(User $user): array
    {
        if (! Schema::hasTable('invoices')) {
            return $this->emptySections(['invoices' => 'Invoices', 'payments' => 'Payments']);
        }

        $invoiceQuery = Invoice::query()->where('tenant_id', $user->tenant_id);
        if ($user->isParent()) {
            $guardian = Schema::hasTable('guardians')
                ? Guardian::where('tenant_id', $user->tenant_id)->where('user_id', $user->id)->first()
                : null;
            $studentIds = $guardian && Schema::hasTable('guardian_student')
                ? $guardian->students()->where('students.tenant_id', $user->tenant_id)->pluck('students.id')
                : collect();
            $invoiceQuery->whereIn('student_id', $studentIds);
        }

        $total = (clone $invoiceQuery)->sum('total_amount');
        $paid = (clone $invoiceQuery)->sum('amount_paid');
        $invoices = (clone $invoiceQuery)->with(['student:id,first_name,last_name', 'term:id,name'])
            ->latest()->limit(100)->get()->map(fn (Invoice $invoice): array => $this->record(
                $invoice->id,
                $invoice->invoice_number,
                $invoice->student?->full_name ?? 'Student invoice',
                $invoice->status,
                [
                    'Total' => $this->money($invoice->total_amount),
                    'Paid' => $this->money($invoice->amount_paid),
                    'Balance' => $this->money($invoice->balance),
                    'Term' => $invoice->term?->name ?? 'Not assigned',
                    'Due' => $invoice->due_date?->toDateString() ?? 'Not set',
                ],
            ));

        $payments = Schema::hasTable('payment_transactions')
            ? PaymentTransaction::query()->where('tenant_id', $user->tenant_id)
                ->when($user->isParent(), fn ($query) => $query->whereIn('invoice_id', $invoices->pluck('id')))
                ->with(['student:id,first_name,last_name', 'invoice:id,invoice_number'])
                ->latest()->limit(100)->get()->map(fn (PaymentTransaction $payment): array => $this->record(
                    $payment->id,
                    $payment->gateway_reference ?: 'Payment '.$payment->id,
                    $payment->student?->full_name ?? $payment->invoice?->invoice_number ?? 'Verified transaction',
                    $payment->status,
                    [
                        'Amount' => $this->money($payment->amount_paid, $payment->currency ?: 'NGN'),
                        'Gateway' => $payment->gateway ?: 'Manual',
                        'Paid at' => $payment->paid_at?->toIso8601String() ?? 'Pending',
                    ],
                ))
            : collect();

        return [
            'metrics' => [
                $this->metric('billed', 'Billed', $this->money($total), 'currency', 'navy'),
                $this->metric('paid', 'Collected', $this->money($paid), 'currency', 'success'),
                $this->metric('balance', 'Outstanding', $this->money(max(0, $total - $paid)), 'currency', 'warning'),
                $this->metric('transactions', 'Payments', (string) $payments->count(), 'number', 'blue'),
            ],
            'sections' => [
                $this->section('invoices', 'Invoices', $invoices),
                $this->section('payments', 'Verified payments', $payments),
            ],
        ];
    }

    private function expenses(User $user): array
    {
        $items = Schema::hasTable('school_expenses')
            ? SchoolExpense::query()->where('tenant_id', $user->tenant_id)->latest('expense_date')->limit(100)->get()
                ->map(fn (SchoolExpense $expense): array => $this->record($expense->id, $expense->title, $expense->category, 'recorded', [
                    'Amount' => $this->money($expense->amount),
                    'Date' => (string) $expense->expense_date,
                    'Method' => $expense->payment_method ?: 'Not recorded',
                    'Reference' => $expense->reference ?: 'None',
                ]))
            : collect();

        return $this->singleSection('expenses', 'Recent expenses', $items, [
            $this->metric('total', 'Total recorded', $this->money($items->sum(fn ($item) => $this->moneyValue($item, 'Amount'))), 'currency', 'warning'),
            $this->metric('records', 'Entries', (string) $items->count(), 'number', 'navy'),
        ]);
    }

    private function payroll(User $user): array
    {
        $items = Schema::hasTable('payroll_periods')
            ? PayrollPeriod::query()->where('tenant_id', $user->tenant_id)->latest('period_start')->limit(100)->get()
                ->map(fn (PayrollPeriod $period): array => $this->record($period->id, $period->title, trim($period->period_start.' — '.$period->period_end), $period->status, [
                    'Gross' => $this->money($period->total_gross),
                    'Deductions' => $this->money($period->total_deductions),
                    'Net' => $this->money($period->total_net),
                    'Payment date' => $period->payment_date ?: 'Not paid',
                ]))
            : collect();

        return $this->singleSection('periods', 'Payroll periods', $items, [
            $this->metric('periods', 'Periods', (string) $items->count(), 'number', 'navy'),
            $this->metric('net', 'Latest net', data_get($items->first(), 'fields.2.value', $this->money(0)), 'currency', 'success'),
        ]);
    }

    private function admissions(User $user): array
    {
        $items = Schema::hasTable('admissions')
            ? Admission::query()->where('tenant_id', $user->tenant_id)->with('applyingForClassLevel:id,name')
                ->latest()->limit(100)->get()->map(fn (Admission $admission): array => $this->record(
                    $admission->id,
                    trim("{$admission->first_name} {$admission->other_names} {$admission->last_name}"),
                    $admission->application_number,
                    $admission->status,
                    [
                        'Applying for' => $admission->applyingForClassLevel?->name ?? 'Not selected',
                        'Guardian' => $admission->guardian_name ?: 'Not recorded',
                        'Phone' => $admission->guardian_phone ?: 'Not recorded',
                        'Applied' => (string) $admission->application_date,
                    ],
                ))
            : collect();

        return $this->singleSection('applications', 'Applications', $items, [
            $this->metric('total', 'Applications', (string) $items->count(), 'number', 'navy'),
            $this->metric('pending', 'Pending', (string) $items->where('status', 'pending')->count(), 'number', 'warning'),
            $this->metric('admitted', 'Admitted', (string) $items->where('status', 'admitted')->count(), 'number', 'success'),
        ]);
    }

    private function library(User $user): array
    {
        $books = Schema::hasTable('library_books')
            ? LibraryBook::query()->where('tenant_id', $user->tenant_id)->orderBy('title')->limit(150)->get()
                ->map(fn (LibraryBook $book): array => $this->record($book->id, $book->title, $book->author ?: 'Unknown author', $book->is_active ? 'active' : 'inactive', [
                    'Available' => $book->available_copies.' of '.$book->total_copies,
                    'Category' => $book->category ?: 'General',
                    'Location' => $book->location ?: 'Not assigned',
                    'ISBN' => $book->isbn ?: 'Not recorded',
                ]))
            : collect();
        $loans = Schema::hasTable('library_loans')
            ? LibraryLoan::query()->where('tenant_id', $user->tenant_id)->whereIn('status', ['issued', 'overdue'])
                ->with(['book:id,title', 'student:id,first_name,last_name'])->latest('issue_date')->limit(100)->get()
                ->map(fn (LibraryLoan $loan): array => $this->record($loan->id, $loan->book?->title ?? 'Library loan', $loan->student?->full_name ?? 'Staff loan', $loan->status, [
                    'Issued' => (string) $loan->issue_date,
                    'Due' => (string) $loan->due_date,
                    'Fine' => $this->money($loan->fine_amount),
                ]))
            : collect();

        return [
            'metrics' => [
                $this->metric('titles', 'Book titles', (string) $books->count(), 'number', 'navy'),
                $this->metric('available', 'Available copies', (string) $books->sum(fn ($book) => (int) strtok(data_get($book, 'fields.0.value', '0'), ' ')), 'number', 'success'),
                $this->metric('loans', 'Active loans', (string) $loans->count(), 'number', 'warning'),
            ],
            'sections' => [$this->section('catalogue', 'Catalogue', $books), $this->section('loans', 'Active loans', $loans)],
        ];
    }

    private function transport(User $user): array
    {
        $routes = Schema::hasTable('transport_routes')
            ? TransportRoute::query()->where('tenant_id', $user->tenant_id)->with(['bus', 'driver:id,name'])->withCount('assignments')
                ->orderBy('name')->get()->map(fn (TransportRoute $route): array => $this->record($route->id, $route->name, $route->bus?->plate_number ?? 'No vehicle', $route->is_active ? 'active' : 'inactive', [
                    'Riders' => (string) $route->assignments_count,
                    'Capacity' => (string) ($route->bus?->capacity ?? 0),
                    'Driver' => $route->driver?->name ?? 'Not assigned',
                    'Fare' => $this->money($route->fare),
                ]))
            : collect();
        $buses = Schema::hasTable('transport_buses')
            ? TransportBus::query()->where('tenant_id', $user->tenant_id)->orderBy('plate_number')->get()
                ->map(fn (TransportBus $bus): array => $this->record($bus->id, $bus->plate_number, $bus->model ?: 'School vehicle', $bus->is_active ? 'active' : 'inactive', [
                    'Capacity' => (string) $bus->capacity,
                    'Year' => (string) ($bus->year ?: 'Not recorded'),
                ]))
            : collect();

        return [
            'metrics' => [
                $this->metric('routes', 'Routes', (string) $routes->count(), 'number', 'navy'),
                $this->metric('vehicles', 'Vehicles', (string) $buses->count(), 'number', 'blue'),
                $this->metric('riders', 'Assigned riders', (string) $routes->sum(fn ($route) => (int) data_get($route, 'fields.0.value', 0)), 'number', 'success'),
            ],
            'sections' => [$this->section('routes', 'Routes', $routes), $this->section('vehicles', 'Vehicles', $buses)],
        ];
    }

    private function health(User $user): array
    {
        $records = Schema::hasTable('student_health_records')
            ? StudentHealthRecord::query()->where('tenant_id', $user->tenant_id)->with('student:id,first_name,last_name,admission_number')
                ->latest()->limit(150)->get()->map(fn (StudentHealthRecord $record): array => $this->record($record->id, $record->student?->full_name ?? 'Student record', $record->student?->admission_number, filled($record->allergies) || filled($record->current_medications) ? 'attention' : 'recorded', [
                    'Blood group' => $record->blood_group ?: 'Not recorded',
                    'Genotype' => $record->genotype ?: 'Not recorded',
                    'Allergies' => $record->allergies ?: 'None recorded',
                    'Medication' => $record->current_medications ?: 'None recorded',
                ]))
            : collect();

        return $this->singleSection('records', 'Health records', $records, [
            $this->metric('records', 'Records', (string) $records->count(), 'number', 'navy'),
            $this->metric('alerts', 'Attention', (string) $records->where('status', 'attention')->count(), 'number', 'warning'),
        ]);
    }

    private function inventory(User $user): array
    {
        $items = Schema::hasTable('assets')
            ? SchoolAsset::query()->where('tenant_id', $user->tenant_id)->with('assignedTo:id,name')->orderBy('name')->limit(150)->get()
                ->map(fn (SchoolAsset $asset): array => $this->record($asset->id, $asset->name, $asset->category ?: 'Uncategorised', $asset->status ?: $asset->condition, [
                    'Condition' => $asset->condition ?: 'Not recorded',
                    'Location' => $asset->location ?: 'Not assigned',
                    'Assigned to' => $asset->assignedTo?->name ?? 'Unassigned',
                    'Serial' => $asset->serial_number ?: 'Not recorded',
                ]))
            : collect();

        return $this->singleSection('assets', 'Asset register', $items, [
            $this->metric('assets', 'Assets', (string) $items->count(), 'number', 'navy'),
            $this->metric('attention', 'Needs attention', (string) $items->filter(fn ($item) => in_array(strtolower((string) $item['status']), ['damaged', 'repair', 'lost'], true))->count(), 'number', 'warning'),
        ]);
    }

    private function hostels(User $user): array
    {
        $hostels = Schema::hasTable('hostels')
            ? Hostel::query()->where('tenant_id', $user->tenant_id)->with('warden:id,name')->withCount(['rooms', 'allocations as occupied_count' => fn ($query) => $query->where('status', 'active')])
                ->orderBy('name')->get()->map(fn (Hostel $hostel): array => $this->record($hostel->id, $hostel->name, ucfirst($hostel->gender ?: 'mixed').' boarding', $hostel->occupied_count >= $hostel->capacity ? 'full' : 'available', [
                    'Occupied' => $hostel->occupied_count.' of '.$hostel->capacity,
                    'Rooms' => (string) $hostel->rooms_count,
                    'Warden' => $hostel->warden?->name ?? 'Not assigned',
                ]))
            : collect();
        $allocations = Schema::hasTable('hostel_allocations')
            ? HostelAllocation::query()->where('tenant_id', $user->tenant_id)->where('status', 'active')
                ->with(['student:id,first_name,last_name,admission_number', 'hostel:id,name', 'room:id,room_number'])
                ->latest('allocated_at')->limit(150)->get()->map(fn (HostelAllocation $allocation): array => $this->record($allocation->id, $allocation->student?->full_name ?? 'Student allocation', $allocation->student?->admission_number, 'active', [
                    'Hostel' => $allocation->hostel?->name ?? 'Not assigned',
                    'Room' => $allocation->room?->room_number ?? 'Not assigned',
                    'Allocated' => $allocation->allocated_at?->toDateString() ?? 'Not recorded',
                ]))
            : collect();

        return [
            'metrics' => [
                $this->metric('hostels', 'Hostels', (string) $hostels->count(), 'number', 'navy'),
                $this->metric('residents', 'Residents', (string) $allocations->count(), 'number', 'success'),
            ],
            'sections' => [$this->section('hostels', 'Hostels', $hostels), $this->section('allocations', 'Active allocations', $allocations)],
        ];
    }

    private function subjects(User $user): array
    {
        $items = Schema::hasTable('subjects')
            ? Subject::query()->where('tenant_id', $user->tenant_id)->withCount('classLevelRules')->orderBy('name')->limit(200)->get()
                ->map(fn (Subject $subject): array => $this->record($subject->id, $subject->name, $subject->code ?: 'No code', $subject->is_active ? 'active' : 'inactive', [
                    'Class-level rules' => (string) $subject->class_level_rules_count,
                ]))
            : collect();

        return $this->singleSection('subjects', 'Subject catalogue', $items, [
            $this->metric('subjects', 'Subjects', (string) $items->count(), 'number', 'navy'),
            $this->metric('active', 'Active', (string) $items->where('status', 'active')->count(), 'number', 'success'),
        ]);
    }

    private function curriculum(User $user): array
    {
        $tracks = Schema::hasTable('academic_tracks')
            ? AcademicTrack::query()->forTenant($user->tenant_id)->get()->map(fn (AcademicTrack $track): array => $this->record($track->id, $track->name, ucfirst($track->section ?: 'general').' track', $track->is_active ? 'active' : 'inactive', []))
            : collect();
        $rules = Schema::hasTable('class_level_subjects')
            ? ClassLevelSubject::query()->where('tenant_id', $user->tenant_id)->with(['classLevel:id,name', 'subject:id,name', 'academicTrack:id,name'])
                ->orderBy('class_level_id')->limit(200)->get()->map(fn (ClassLevelSubject $rule): array => $this->record($rule->id, $rule->subject?->name ?? 'Subject rule', $rule->classLevel?->name ?? 'Class level', $rule->is_active ? $rule->subject_status : 'inactive', [
                    'Track' => $rule->academicTrack?->name ?? 'All tracks',
                    'Group' => $rule->elective_group ?: 'Not grouped',
                ]))
            : collect();

        return [
            'metrics' => [
                $this->metric('tracks', 'Tracks', (string) $tracks->count(), 'number', 'navy'),
                $this->metric('rules', 'Subject rules', (string) $rules->count(), 'number', 'blue'),
                $this->metric('active', 'Active rules', (string) $rules->whereNotIn('status', ['inactive', 'not_offered'])->count(), 'number', 'success'),
            ],
            'sections' => [$this->section('tracks', 'Academic tracks', $tracks), $this->section('rules', 'Class subject rules', $rules)],
        ];
    }

    private function academicCycle(User $user): array
    {
        $sessions = Schema::hasTable('academic_sessions')
            ? AcademicSession::query()->where('tenant_id', $user->tenant_id)->with('terms')->latest()->get()
                ->map(fn (AcademicSession $session): array => $this->record($session->id, $session->name, $session->terms->count().' terms', $session->is_current ? 'current' : 'closed', []))
            : collect();
        $terms = Schema::hasTable('terms')
            ? Term::query()->where('tenant_id', $user->tenant_id)->with('session:id,name')->latest('start_date')->limit(100)->get()
                ->map(fn (Term $term): array => $this->record($term->id, $term->name, $term->session?->name ?? 'Academic session', $term->is_current ? 'current' : 'closed', [
                    'Starts' => $term->start_date?->toDateString() ?? 'Not set',
                    'Ends' => $term->end_date?->toDateString() ?? 'Not set',
                    'Next term' => $term->next_term_begins?->toDateString() ?? 'Not set',
                ]))
            : collect();

        return [
            'metrics' => [
                $this->metric('sessions', 'Sessions', (string) $sessions->count(), 'number', 'navy'),
                $this->metric('terms', 'Terms', (string) $terms->count(), 'number', 'blue'),
            ],
            'sections' => [$this->section('sessions', 'Sessions', $sessions), $this->section('terms', 'Terms', $terms)],
        ];
    }

    private function singleSection(string $key, string $title, Collection $items, array $metrics): array
    {
        return ['metrics' => $metrics, 'sections' => [$this->section($key, $title, $items)]];
    }

    private function emptySections(array $sections): array
    {
        return [
            'metrics' => [],
            'sections' => collect($sections)->map(fn (string $title, string $key): array => $this->section($key, $title, collect()))->values()->all(),
        ];
    }

    private function metric(string $key, string $label, string $value, string $format, string $tone): array
    {
        return compact('key', 'label', 'value', 'format', 'tone');
    }

    private function section(string $key, string $title, Collection $records): array
    {
        return ['key' => $key, 'title' => $title, 'count' => $records->count(), 'records' => $records->values()];
    }

    private function record(mixed $id, string $title, ?string $subtitle, ?string $status, array $fields): array
    {
        return [
            'id' => (string) $id,
            'title' => $title,
            'subtitle' => $subtitle ?: null,
            'status' => $status ?: null,
            'fields' => collect($fields)->map(fn (mixed $value, string $label): array => ['label' => $label, 'value' => (string) $value])->values(),
        ];
    }

    private function money(mixed $amount, string $currency = 'NGN'): string
    {
        return ($currency === 'NGN' ? '₦' : $currency.' ').number_format((float) $amount, 2);
    }

    private function moneyValue(array $record, string $label): float
    {
        $value = collect($record['fields'])->firstWhere('label', $label)['value'] ?? '0';

        return (float) preg_replace('/[^0-9.-]/', '', $value);
    }
}
