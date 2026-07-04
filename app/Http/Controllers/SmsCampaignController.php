<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use App\Models\ClassArm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SmsCampaignController extends Controller
{
    public function index()
    {
        $campaigns  = \App\Models\SmsCampaign::latest()->paginate(15);
        $classArms  = ClassArm::with('classLevel')->get();
        return view('sms.index', compact('campaigns', 'classArms'));
    }

    public function create()
    {
        $classArms = ClassArm::with('classLevel')->get();
        return view('sms.create', compact('classArms'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:120'],
            'message'     => ['required', 'string', 'max:1600'],
            'audience'    => ['required', 'in:all_parents,all_staff,class_parents,custom'],
            'class_arm_id'=> ['nullable', 'exists:class_arms,id'],
            'phones'      => ['nullable', 'string'],      // comma-separated for custom
            'schedule_at' => ['nullable', 'date'],
        ]);

        // Resolve recipient phone numbers
        $phones = $this->resolvePhones($data);

        $campaign = \App\Models\SmsCampaign::create([
            'title'        => $data['title'],
            'message'      => $data['message'],
            'audience'     => $data['audience'],
            'class_arm_id' => $data['class_arm_id'] ?? null,
            'recipient_count' => count($phones),
            'status'       => $data['schedule_at'] ? 'scheduled' : 'draft',
            'schedule_at'  => $data['schedule_at'] ?? null,
        ]);

        if (!$data['schedule_at']) {
            // Immediate send — dispatch job (stub: log for now)
            $this->sendCampaign($campaign, $phones);
        }

        return redirect()->route('sms.index')->with('success', "Campaign '{$data['title']}' created for {$campaign->recipient_count} recipients.");
    }

    public function show(\App\Models\SmsCampaign $campaign)
    {
        $logs = \App\Models\SmsLog::where('campaign_id', $campaign->id)->paginate(30);
        return view('sms.show', compact('campaign', 'logs'));
    }

    public function send(\App\Models\SmsCampaign $campaign)
    {
        $phones = $this->resolvePhones(['audience' => $campaign->audience, 'class_arm_id' => $campaign->class_arm_id]);
        $this->sendCampaign($campaign, $phones);
        return back()->with('success', "Campaign sent to {$campaign->recipient_count} recipients.");
    }

    public function destroy(\App\Models\SmsCampaign $campaign)
    {
        $campaign->delete();
        return back()->with('success', 'Campaign deleted.');
    }

    private function resolvePhones(array $data): array
    {
        $phones = [];
        $tid    = auth()->user()->tenant_id;

        if ($data['audience'] === 'all_parents') {
            $phones = DB::table('guardians')
                ->join('guardian_student', 'guardian_student.guardian_id', '=', 'guardians.id')
                ->join('students', 'students.id', '=', 'guardian_student.student_id')
                ->where('guardians.tenant_id', $tid)
                ->where('students.tenant_id', $tid)
                ->where('students.status', Student::STATUS_ACTIVE)
                ->whereNotNull('guardians.phone')
                ->pluck('guardians.phone')
                ->unique()
                ->toArray();
        } elseif ($data['audience'] === 'all_staff') {
            $phones = User::activeStaff($tid)
                ->whereNotNull('phone')
                ->pluck('phone')
                ->toArray();
        } elseif ($data['audience'] === 'class_parents' && !empty($data['class_arm_id'])) {
            $studentIds = Student::where('current_class_arm_id', $data['class_arm_id'])
                ->where('status', Student::STATUS_ACTIVE)
                ->pluck('id');
            $phones = DB::table('guardian_student')
                ->join('guardians','guardians.id','=','guardian_student.guardian_id')
                ->whereIn('guardian_student.student_id', $studentIds)
                ->whereNotNull('guardians.phone')
                ->pluck('guardians.phone')->unique()->toArray();
        } elseif ($data['audience'] === 'custom' && !empty($data['phones'])) {
            $phones = array_filter(array_map('trim', explode(',', $data['phones'])));
        }

        return array_values(array_unique($phones));
    }

    private function sendCampaign(\App\Models\SmsCampaign $campaign, array $phones): void
    {
        // In production: dispatch to queue / SMS provider (Twilio, Africa's Talking, etc.)
        // For now: create log entries as "queued"
        foreach ($phones as $phone) {
            \App\Models\SmsLog::create([
                'campaign_id' => $campaign->id,
                'phone'       => $phone,
                'message'     => $campaign->message,
                'status'      => 'queued',
            ]);
        }
        $campaign->update(['status' => 'sent', 'sent_at' => now(), 'recipient_count' => count($phones)]);
    }
}
