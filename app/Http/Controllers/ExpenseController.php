<?php
namespace App\Http\Controllers;

use App\Models\SchoolExpense;
use App\Models\AcademicSession;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = SchoolExpense::latest('expense_date');
        if ($request->filled('category')) $query->where('category', $request->category);
        if ($request->filled('term_id'))   $query->where('term_id', $request->term_id);

        $expenses  = $query->paginate(25)->withQueryString();
        $terms     = Term::with('session')->latest()->get();
        $sessions  = AcademicSession::latest()->get();

        $totals = SchoolExpense::select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')->pluck('total','category');

        $grandTotal = SchoolExpense::sum('amount');
        $categories = ['utilities','supplies','maintenance','staff','transport','food','rent','equipment','other'];

        return view('expenses.index', compact('expenses','terms','sessions','totals','grandTotal','categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'          => ['required','string','max:150'],
            'category'       => ['required','string'],
            'amount'         => ['required','numeric','min:1'],
            'expense_date'   => ['required','date'],
            'payment_method' => ['nullable','string'],
            'term_id'        => ['nullable','exists:terms,id'],
            'session_id'     => ['nullable','exists:academic_sessions,id'],
            'description'    => ['nullable','string'],
        ]);
        $data['recorded_by'] = auth()->id();
        SchoolExpense::create($data);
        return back()->with('success', 'Expense recorded.');
    }

    public function destroy(SchoolExpense $expense)
    {
        $expense->delete();
        return back()->with('success', 'Expense deleted.');
    }
}
