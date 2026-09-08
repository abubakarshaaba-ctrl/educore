<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LibraryBook;
use App\Models\LibraryLoan;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MobileLibraryController extends Controller
{
    public function options(Request $request): JsonResponse
    {
        $user = $this->guard($request, manage: true);
        $tenantId = (int) $user->tenant_id;

        $books = LibraryBook::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('available_copies', '>', 0)
            ->orderBy('title')
            ->limit(250)
            ->get(['id', 'title', 'author', 'available_copies'])
            ->map(fn (LibraryBook $book) => [
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->author,
                'available_copies' => (int) $book->available_copies,
            ]);

        $students = Student::query()
            ->where('tenant_id', $tenantId)
            ->where('status', Student::STATUS_ACTIVE)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(500)
            ->get(['id', 'admission_number', 'first_name', 'last_name'])
            ->map(fn (Student $student) => [
                'id' => $student->id,
                'name' => $student->full_name,
                'reference' => $student->admission_number,
            ]);

        $staff = User::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereIn('role', User::staffRoleNames())
            ->orderBy('name')
            ->limit(300)
            ->get(['id', 'name', 'staff_id'])
            ->map(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'reference' => $member->staff_id,
            ]);

        return response()->json([
            'books' => $books,
            'students' => $students,
            'staff' => $staff,
        ]);
    }

    public function issue(Request $request): JsonResponse
    {
        $user = $this->guard($request, manage: true);
        $tenantId = (int) $user->tenant_id;
        $data = $request->validate([
            'book_id' => [
                'required',
                'integer',
                Rule::exists('library_books', 'id')->where(fn ($query) => $query
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true)),
            ],
            'student_id' => [
                'nullable',
                'integer',
                Rule::exists('students', 'id')->where(fn ($query) => $query
                    ->where('tenant_id', $tenantId)
                    ->where('status', Student::STATUS_ACTIVE)),
            ],
            'staff_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true)),
            ],
            'due_date' => ['required', 'date', 'after:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $borrowers = (int) ! empty($data['student_id']) + (int) ! empty($data['staff_id']);
        if ($borrowers !== 1) {
            throw ValidationException::withMessages([
                'borrower' => ['Select exactly one student or staff borrower.'],
            ]);
        }

        $loan = DB::transaction(function () use ($data, $tenantId, $user) {
            $book = LibraryBook::query()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($data['book_id']);

            abort_if(! $book->is_active, 409, 'This book is inactive.');
            abort_if((int) $book->available_copies < 1, 409, 'No copies are currently available.');

            $loan = LibraryLoan::create([
                'tenant_id' => $tenantId,
                'book_id' => $book->id,
                'student_id' => $data['student_id'] ?? null,
                'staff_id' => $data['staff_id'] ?? null,
                'issue_date' => today()->toDateString(),
                'due_date' => $data['due_date'],
                'status' => 'issued',
                'notes' => $data['notes'] ?? null,
                'issued_by' => $user->id,
            ]);

            $book->update(['available_copies' => max(0, (int) $book->available_copies - 1)]);

            return $loan;
        });

        return response()->json([
            'message' => 'Book issued successfully.',
            'loan' => [
                'id' => $loan->id,
                'book_id' => $loan->book_id,
                'student_id' => $loan->student_id,
                'staff_id' => $loan->staff_id,
                'due_date' => (string) $loan->due_date,
                'status' => $loan->status,
            ],
        ], 201);
    }

    public function returnLoan(Request $request, int $loan): JsonResponse
    {
        $user = $this->guard($request, manage: true);
        $tenantId = (int) $user->tenant_id;

        $record = DB::transaction(function () use ($tenantId, $loan) {
            $record = LibraryLoan::query()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($loan);

            abort_unless(in_array($record->status, ['issued', 'overdue'], true), 409, 'Only active loans can be returned.');

            $book = LibraryBook::query()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($record->book_id);

            $record->update([
                'status' => 'returned',
                'return_date' => today()->toDateString(),
            ]);
            $book->update([
                'available_copies' => min((int) $book->total_copies, (int) $book->available_copies + 1),
            ]);

            return $record;
        });

        return response()->json([
            'message' => 'Book returned successfully.',
            'loan' => [
                'id' => $record->id,
                'status' => $record->status,
                'return_date' => (string) $record->return_date,
            ],
        ]);
    }

    private function guard(Request $request, bool $manage = false): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->isStudent() || $user->isParent() || $user->isSuperAdmin(), 403, 'School library access required.');
        abort_unless($user->tenant_id, 403, 'School library access required.');
        abort_unless(
            $manage ? $user->canManage('library') : $user->canAccessModule('library'),
            403,
            $manage ? 'Library management permission required.' : 'Library access required.'
        );

        return $user;
    }
}
