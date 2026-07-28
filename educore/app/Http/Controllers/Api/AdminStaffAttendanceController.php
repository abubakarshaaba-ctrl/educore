<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendanceRecord;
use App\Models\StaffAttendanceSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminStaffAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user=$this->guard($request); $date=$request->date('date')?->toDateString() ?? today()->toDateString();
        $settings=StaffAttendanceSetting::forTenant($user->tenant_id);
        $records=StaffAttendanceRecord::where('tenant_id',$user->tenant_id)->whereDate('attendance_date',$date)->with('staff:id,name,staff_id')->get();
        $eligible=User::attendanceEligibleOn($user->tenant_id,$date)->count();
        return response()->json([
            'date'=>$date,
            'summary'=>['eligible'=>$eligible,'clocked_in'=>$records->whereNotNull('clock_in_time')->count(),'early'=>$records->where('status','early')->count(),'present'=>$records->where('status','present')->count(),'late'=>$records->where('status','late')->count(),'absent'=>max(0,$eligible-$records->count())],
            'records'=>$records->map(fn($r)=>['id'=>$r->id,'staff'=>$r->staff?->name,'staff_id'=>$r->staff?->staff_id,'status'=>$r->status,'clock_in'=>$r->clock_in_time,'clock_out'=>$r->clock_out_time,'method'=>$r->clock_in_method]),
            'settings'=>$this->settingsPayload($settings),
        ]);
    }

    public function report(Request $request)
    {
        $user=$this->guard($request); $month=$request->integer('month',now()->month); $year=$request->integer('year',now()->year);
        $start=Carbon::create($year,$month,1)->startOfDay(); $end=(clone $start)->endOfMonth();
        $records=StaffAttendanceRecord::where('tenant_id',$user->tenant_id)->whereBetween('attendance_date',[$start,$end])->get()->groupBy('user_id');
        $staff=User::tenantStaff($user->tenant_id)->orderBy('name')->get()->map(function($member)use($records){$rows=$records->get($member->id,collect());return ['id'=>$member->id,'name'=>$member->name,'staff_id'=>$member->staff_id,'early'=>$rows->where('status','early')->count(),'present'=>$rows->where('status','present')->count(),'late'=>$rows->where('status','late')->count(),'absent'=>$rows->where('status','absent')->count(),'days'=>$rows->count()];});
        return response()->json(['month'=>$month,'year'=>$year,'staff'=>$staff]);
    }

    public function updateSettings(Request $request)
    {
        $user=$this->guard($request); $data=$request->validate(['resumption_time'=>['required','date_format:H:i'],'grace_minutes'=>['required','integer','min:0','max:120'],'closing_time'=>['required','date_format:H:i'],'geo_enabled'=>['required','boolean'],'geo_lat'=>['nullable','numeric','between:-90,90'],'geo_lng'=>['nullable','numeric','between:-180,180'],'geo_radius_meters'=>['nullable','integer','min:10','max:2000']]);
        $data['resumption_time'].=':00';$data['closing_time'].=':00'; StaffAttendanceSetting::forTenant($user->tenant_id)->update($data);
        return response()->json(['message'=>'Staff attendance settings updated.']);
    }

    private function guard(Request $request): User { $user=$request->user(); abort_unless($user && in_array($user->roleKey(),['admin','principal','head','head_teacher','vice_principal'],true),403,'School administrator access required.'); return $user; }
    private function settingsPayload($s):array{return ['resumption_time'=>substr((string)$s->resumption_time,0,5),'grace_minutes'=>$s->grace_minutes,'closing_time'=>substr((string)$s->closing_time,0,5),'geo_enabled'=>(bool)$s->geo_enabled,'geo_lat'=>$s->geo_lat,'geo_lng'=>$s->geo_lng,'geo_radius_meters'=>$s->geo_radius_meters];}
}
