<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\FeeStructure;
use App\Models\ClassLevel;
use App\Models\Student;
use App\Models\Term;
use App\Models\PayrollPeriod;
use App\Models\PayrollItem;
use App\Models\StaffSalarySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AccountantController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $this->guard($request);
        $invoices = Invoice::where('tenant_id', $user->tenant_id);
        $expenses = Schema::hasTable('school_expenses')
            ? DB::table('school_expenses')->where('tenant_id', $user->tenant_id)
            : null;

        return response()->json([
            'summary' => [
                'billed' => (float) (clone $invoices)->sum('total_amount'),
                'collected' => (float) (clone $invoices)->sum('amount_paid'),
                'outstanding' => (float) (clone $invoices)->selectRaw('COALESCE(SUM(total_amount - amount_paid), 0) balance')->value('balance'),
                'expenses' => $expenses ? (float) (clone $expenses)->sum('amount') : 0,
            ],
            'invoices' => (clone $invoices)->with('student:id,first_name,last_name')->latest()->limit(50)->get()->map(fn ($invoice) => [
                'id' => $invoice->id, 'number' => $invoice->invoice_number,
                'student' => $invoice->student?->full_name,
                'total' => (float) $invoice->total_amount, 'paid' => (float) $invoice->amount_paid,
                'balance' => max(0, (float) $invoice->total_amount - (float) $invoice->amount_paid),
                'status' => $invoice->status,
            ]),
        ]);
    }

    public function payroll(Request $request)
    {
        $user = $this->guard($request);
        if (!Schema::hasTable('payroll_periods')) return response()->json(['periods' => []]);
        $periods = DB::table('payroll_periods')->where('tenant_id', $user->tenant_id)
            ->latest('period_start')->limit(50)->get()->map(fn ($period) => [
                'id' => $period->id, 'title' => $period->title,
                'start' => $period->period_start, 'end' => $period->period_end,
                'status' => $period->status, 'gross' => (float) $period->total_gross,
                'deductions' => (float) $period->total_deductions, 'net' => (float) $period->total_net,
            ]);
        return response()->json(['periods' => $periods]);
    }

    public function preparationOptions(Request $request)
    {
        $user=$this->guard($request);
        return response()->json([
            'terms'=>Term::where('tenant_id',$user->tenant_id)->with('session:id,name')->latest()->get()->map(fn($term)=>['id'=>$term->id,'name'=>$term->name,'session'=>$term->session?->name]),
            'class_levels'=>ClassLevel::where('tenant_id',$user->tenant_id)->orderBy('order_index')->get(['id','name']),
        ]);
    }

    public function generateFees(Request $request)
    {
        $user=$this->guard($request);$data=$request->validate(['term_id'=>['required',Rule::exists('terms','id')->where('tenant_id',$user->tenant_id)],'class_level_id'=>['required',Rule::exists('class_levels','id')->where('tenant_id',$user->tenant_id)]]);
        $term=Term::findOrFail($data['term_id']);$level=ClassLevel::with('classArms')->findOrFail($data['class_level_id']);
        $students=Student::whereIn('current_class_arm_id',$level->classArms->pluck('id'))->billingEligible()->pluck('id');
        $structures=FeeStructure::where('class_level_id',$level->id)->where('term_id',$term->id)->where('is_active',true)->with('feeCategory')->get();
        abort_if($structures->isEmpty(),422,'No active fee structure exists for this class and term.');
        $generated=0;$skipped=0;$total=$structures->sum('amount');
        DB::transaction(function()use($students,$term,$structures,$total,$user,&$generated,&$skipped){foreach($students as $studentId){if(Invoice::where('student_id',$studentId)->where('term_id',$term->id)->exists()){$skipped++;continue;}$number='INV-'.now()->format('Y').'-'.str_pad((string)(Invoice::withoutTenantScope()->where('tenant_id',$user->tenant_id)->count()+$generated+1),5,'0',STR_PAD_LEFT);$invoice=Invoice::create(['tenant_id'=>$user->tenant_id,'student_id'=>$studentId,'term_id'=>$term->id,'session_id'=>$term->session_id,'invoice_number'=>$number,'total_amount'=>$total,'amount_paid'=>0,'status'=>'unpaid','due_date'=>$term->end_date]);foreach($structures as $structure){InvoiceItem::create(['tenant_id'=>$user->tenant_id,'invoice_id'=>$invoice->id,'fee_category_id'=>$structure->fee_category_id,'description'=>$structure->feeCategory?->name??'School fee','amount'=>$structure->amount]);}$generated++;}});
        return response()->json(['message'=>"{$generated} fee bills prepared; {$skipped} existing bills skipped.",'generated'=>$generated,'skipped'=>$skipped]);
    }

    public function generatePayroll(Request $request)
    {
        $user=$this->guard($request);$data=$request->validate(['title'=>['required','string','max:150'],'period_start'=>['required','date'],'period_end'=>['required','date','after_or_equal:period_start']]);
        $period=DB::transaction(function()use($data,$user){$period=PayrollPeriod::create([...$data,'tenant_id'=>$user->tenant_id]);$grossTotal=0;$dedTotal=0;$netTotal=0;$settings=StaffSalarySetting::where('tenant_id',$user->tenant_id)->where('is_active',true)->where('basic_salary','>',0)->get();foreach($settings as $setting){$gross=(float)$setting->basic_salary+(float)$setting->housing_allowance+(float)$setting->transport_allowance+(float)$setting->other_allowances;$pension=round(((float)$setting->basic_salary+(float)$setting->housing_allowance+(float)$setting->transport_allowance)*.08,2);$net=$gross-$pension;PayrollItem::create(['tenant_id'=>$user->tenant_id,'payroll_period_id'=>$period->id,'staff_id'=>$setting->staff_id,'basic_salary'=>$setting->basic_salary,'housing_allowance'=>$setting->housing_allowance,'transport_allowance'=>$setting->transport_allowance,'other_allowances'=>$setting->other_allowances,'gross_pay'=>$gross,'tax_deduction'=>0,'pension_deduction'=>$pension,'other_deductions'=>0,'total_deductions'=>$pension,'net_pay'=>$net,'bank_name'=>$setting->bank_name,'account_number'=>$setting->account_number,'account_name'=>$setting->account_name,'payment_status'=>'pending']);$grossTotal+=$gross;$dedTotal+=$pension;$netTotal+=$net;}$period->update(['total_gross'=>$grossTotal,'total_deductions'=>$dedTotal,'total_net'=>$netTotal]);return $period;});
        return response()->json(['message'=>'Payroll prepared successfully.','period'=>['id'=>$period->id,'title'=>$period->title,'net'=>(float)$period->total_net]],201);
    }

    private function guard(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->isAccountant(), 403, 'Accountant access required.');
        return $user;
    }
}
