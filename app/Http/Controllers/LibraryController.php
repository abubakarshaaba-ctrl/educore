<?php
namespace App\Http\Controllers;

use App\Models\LibraryBook;
use App\Models\LibraryLoan;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LibraryController extends Controller
{
    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    public function index(Request $request)
    {
        $query = LibraryBook::latest();
        if ($request->filled('category'))  $query->where('category', $request->category);
        if ($request->filled('search'))    $query->where(fn($q) => $q->where('title','like','%'.$request->search.'%')->orWhere('author','like','%'.$request->search.'%'));

        $books = $query->paginate(25)->withQueryString();
        $stats = [
            'total_books'   => LibraryBook::sum('total_copies'),
            'available'     => LibraryBook::sum('available_copies'),
            'issued'        => LibraryLoan::where('status','issued')->count(),
            'overdue'       => LibraryLoan::where('status','overdue')->orWhere(fn($q) => $q->where('status','issued')->where('due_date','<',now()))->count(),
        ];
        $categories = ['fiction','science','mathematics','religion','history','literature','technology','language','social_studies','other'];
        return view('library.index', compact('books','stats','categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'          => ['required','string','max:200'],
            'author'         => ['required','string','max:150'],
            'isbn'           => ['nullable','string','max:20'],
            'publisher'      => ['nullable','string'],
            'edition'        => ['nullable','string'],
            'year'           => ['nullable','integer','min:1900','max:'.date('Y')],
            'category'       => ['required','string'],
            'location'       => ['nullable','string'],
            'total_copies'   => ['required','integer','min:1'],
            'purchase_price' => ['nullable','numeric'],
            'condition'      => ['nullable','in:excellent,good,fair,poor'],
        ]);
        $data['available_copies'] = $data['total_copies'];
        LibraryBook::create($data);
        return back()->with('success', 'Book added to library.');
    }

    public function loans(Request $request)
    {
        $query = LibraryLoan::with(['book', 'student'])->latest();
        if ($request->filled('status')) $query->where('status', $request->status);

        // Auto-mark overdue
        LibraryLoan::where('status','issued')->where('due_date','<',now()->toDateString())
            ->update(['status' => 'overdue']);

        $loans   = $query->paginate(25)->withQueryString();
        $books   = LibraryBook::where('available_copies','>',0)->orderBy('title')->get();
        $students= Student::where('status', Student::STATUS_ACTIVE)->orderBy('last_name')->get();
        return view('library.loans', compact('loans','books','students'));
    }

    public function issueBook(Request $request)
    {
        $data = $request->validate([
            'book_id'    => ['required', Rule::exists('library_books', 'id')->where('tenant_id', $this->tenantId())],
            'student_id' => ['nullable', Rule::exists('students', 'id')->where(fn ($query) => $query->where('tenant_id', $this->tenantId())->where('status', Student::STATUS_ACTIVE))],
            'due_date'   => ['required','date','after:today'],
        ]);
        $book = LibraryBook::findOrFail($data['book_id']);
        if ($book->available_copies < 1) {
            return back()->withErrors(['error' => 'No copies available.']);
        }
        LibraryLoan::create(array_merge($data, [
            'issue_date' => now()->toDateString(),
            'status'     => 'issued',
            'issued_by'  => auth()->id(),
        ]));
        $book->decrement('available_copies');
        return back()->with('success', 'Book issued successfully.');
    }

    public function returnBook(LibraryLoan $loan)
    {
        $loan->update(['status' => 'returned', 'return_date' => now()->toDateString()]);
        $loan->book->increment('available_copies');
        return back()->with('success', 'Book returned.');
    }
}
