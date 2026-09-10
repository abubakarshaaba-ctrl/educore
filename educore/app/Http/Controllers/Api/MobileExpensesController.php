<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\SchoolExpense;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MobileExpensesController extends Controller
{
    private const CATEGORIES = [
        'utilities', 'supplies', 'maintenance', 'staff', 'transport',
        'food', 'rent', 'equipment', 'other',
    ];

    public function index(Request $request)
    {
        $user = $this->guard($request);
        $tenantId = (int) $user->tenant_id;
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', Rule::in(array_merge(['all'], self::CATEGORIES))],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);
        $search = trim((string) ($data['q'] ?? ''));
        $category = (string) ($data['category'] ?? 'all');
        $perPage = (int) ($data['per_page'] ?? 30);
        $base = SchoolExpense::where('tenant_id', $tenantId);
        $query = (clone $base)
            ->with(['session:id,name', 'term:id,name,session_id'])
            ->when($category !== 'all', fn ($q) => $q->where('category', $category))
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($nested) use ($search): void {
                    $nested->where('title', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('payment_method', 'like', "%{$search}%");
                });
            })
            ->latest('expense_date')
            ->latest('id')
            ->paginate($perPage);

        $categoryTotals = (clone $base)
            ->selectRaw('category, SUM(amount) total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->map(fn ($value) => (float) $value);

        return response()->json([
            'contract_version' => 1,
            'capabilities' => ['manage' => $user->canManage('expenses')],
            'metrics' => [
                'total' => (float) (clone $base)->sum('amount'),
                'records' => (clone $base)->count(),
            ],
            'category_totals' => $categoryTotals,
            'categories' => collect(self::CATEGORIES)->map(fn (string $key): array => [
                'key' => $key,
                'label' => str($key)->replace('_', ' ')->title()->toString(),
            ])->prepend(['key' => 'all', 'label' => 'All'])->values(),
            'sessions' => AcademicSession::where('tenant_id', $tenantId)
                ->latest('id')->get(['id', 'name', 'is_current']),
            'terms' => Term::where('tenant_id', $tenantId)
                ->with('session:id,name')->latest('id')->get()
                ->map(fn (Term $term): array => [
                    'id' => $term->id,
                    'name' => $term->name,
                    'session_id' => $term->session_id,
                    'session' => $term->session?->name,
                    'is_current' => (bool) $term->is_current,
                ]),
            'expenses' => collect($query->items())->map(fn (SchoolExpense $expense): array => $this->payload($expense)),
            'selected' => ['search' => $search, 'category' => $category],
            'meta' => [
                'page' => $query->currentPage(),
                'per_page' => $query->perPage(),
                'total' => $query->total(),
                'last_page' => $query->lastPage(),
                'has_more' => $query->hasMorePages(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->guard($request, manage: true);
        $data = $this->validatedExpense($request, $user);
        $expense = SchoolExpense::create($data + [
            'tenant_id' => $user->tenant_id,
            'recorded_by' => $user->id,
        ]);
        $expense->load(['session:id,name', 'term:id,name,session_id']);

        return response()->json([
            'message' => 'Expense recorded.',
            'expense' => $this->payload($expense),
        ], 201);
    }

    public function update(Request $request, int $expense)
    {
        $user = $this->guard($request, manage: true);
        $record = SchoolExpense::where('tenant_id', $user->tenant_id)->findOrFail($expense);
        $data = $this->validatedExpense($request, $user);
        $record->update($data);
        $record->refresh()->load(['session:id,name', 'term:id,name,session_id']);

        return response()->json([
            'message' => 'Expense updated.',
            'expense' => $this->payload($record),
        ]);
    }

    public function destroy(Request $request, int $expense)
    {
        $user = $this->guard($request, manage: true);
        $record = SchoolExpense::where('tenant_id', $user->tenant_id)->findOrFail($expense);
        $record->delete();

        return response()->json(['message' => 'Expense deleted.']);
    }

    private function validatedExpense(Request $request, User $user): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'amount' => ['required', 'numeric', 'min:1'],
            'expense_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:60'],
            'reference' => ['nullable', 'string', 'max:120'],
            'term_id' => ['nullable', Rule::exists('terms', 'id')->where('tenant_id', $user->tenant_id)],
            'session_id' => ['nullable', Rule::exists('academic_sessions', 'id')->where('tenant_id', $user->tenant_id)],
            'description' => ['nullable', 'string', 'max:4000'],
        ]);

        if (!empty($data['term_id'])) {
            $term = Term::where('tenant_id', $user->tenant_id)->findOrFail($data['term_id']);
            if (!empty($data['session_id']) && (int) $term->session_id !== (int) $data['session_id']) {
                throw ValidationException::withMessages([
                    'term_id' => 'The selected term does not belong to the selected academic session.',
                ]);
            }
            $data['session_id'] = $data['session_id'] ?? $term->session_id;
        }

        return $data;
    }

    private function payload(SchoolExpense $expense): array
    {
        return [
            'id' => $expense->id,
            'title' => $expense->title,
            'category' => $expense->category,
            'amount' => (float) $expense->amount,
            'expense_date' => $expense->expense_date?->toDateString(),
            'payment_method' => $expense->payment_method,
            'reference' => $expense->reference,
            'description' => $expense->description,
            'session_id' => $expense->session_id,
            'session' => $expense->session?->name,
            'term_id' => $expense->term_id,
            'term' => $expense->term?->name,
            'recorded_by' => $expense->recorded_by,
        ];
    }

    private function guard(Request $request, bool $manage = false): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->isStudent() || $user->isParent() || $user->isSuperAdmin(), 403, 'School expense access required.');
        abort_unless($user->tenant_id, 403, 'School expense access required.');
        abort_unless(
            $manage ? $user->canManage('expenses') : $user->canAccessModule('expenses'),
            403,
            $manage ? 'Expense management permission required.' : 'Expense access required.'
        );

        return $user;
    }
}
