<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SchoolGroupController extends Controller
{
    private function guard()
    {
        if (!auth()->user()?->is_super_admin) abort(403);
    }

    // ── List all groups ───────────────────────────────────────────────
    public function index()
    {
        $this->guard();
        $groups = DB::table('school_groups')
            ->select('school_groups.*')
            ->selectSub(function ($query) {
                $query->from('school_group_members')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('school_group_members.group_id', 'school_groups.id');
            }, 'member_count')
            ->orderBy('school_groups.name')
            ->paginate(20);

        return view('super.groups.index', compact('groups'));
    }

    // ── Show one group ────────────────────────────────────────────────
    public function show($groupId)
    {
        $this->guard();
        $group = DB::table('school_groups')->where('id', $groupId)->firstOrFail();

        $members = DB::table('school_group_members')
            ->join('tenants','tenants.id','=','school_group_members.tenant_id')
            ->where('school_group_members.group_id', $groupId)
            ->select('school_group_members.*','tenants.name','tenants.status',
                     'tenants.subscription_expires_at','tenants.email')
            ->get();

        $availableTenants = Tenant::whereNotIn('id', $members->pluck('tenant_id'))
            ->orderBy('name')->get();

        // Aggregate stats across all schools in the group
        $stats = [
            'total_students' => DB::table('students')
                ->whereIn('tenant_id', $members->pluck('tenant_id'))->count(),
            'total_staff'    => DB::table('users')
                ->whereIn('tenant_id', $members->pluck('tenant_id'))->count(),
            'total_revenue'  => DB::table('invoices')
                ->whereIn('tenant_id', $members->pluck('tenant_id'))->sum('amount_paid'),
            'active_count'   => $members->where('status','active')->count(),
        ];

        return view('super.groups.show', compact('group','members','availableTenants','stats'));
    }

    // ── Create group ──────────────────────────────────────────────────
    public function store(Request $request)
    {
        $this->guard();
        $data = $request->validate([
            'name'        => ['required','string','max:120'],
            'description' => ['nullable','string'],
            'owner_name'  => ['nullable','string','max:100'],
            'owner_email' => ['nullable','email','max:150'],
        ]);

        $data['slug']       = Str::slug($data['name']).'-'.Str::random(4);
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::table('school_groups')->insert($data);

        return redirect()->route('super.groups.index')->with('success', "Group '{$data['name']}' created.");
    }

    // ── Add school to group ───────────────────────────────────────────
    public function addMember(Request $request, $groupId)
    {
        $this->guard();
        $data = $request->validate([
            'tenant_id' => ['required','exists:tenants,id'],
            'role'      => ['nullable','in:member,lead'],
        ]);

        DB::table('school_group_members')->updateOrInsert(
            ['group_id' => $groupId, 'tenant_id' => $data['tenant_id']],
            ['role' => $data['role'] ?? 'member', 'created_at' => now(), 'updated_at' => now()]
        );

        return back()->with('success', 'School added to group.');
    }

    // ── Remove school from group ──────────────────────────────────────
    public function removeMember($groupId, $tenantId)
    {
        $this->guard();
        DB::table('school_group_members')
            ->where('group_id', $groupId)
            ->where('tenant_id', $tenantId)
            ->delete();

        return back()->with('success', 'School removed from group.');
    }

    // ── Group aggregate report ────────────────────────────────────────
    public function report($groupId)
    {
        $this->guard();
        $group = DB::table('school_groups')->where('id', $groupId)->firstOrFail();

        $tenantIds = DB::table('school_group_members')
            ->where('group_id', $groupId)->pluck('tenant_id');

        $schools = Tenant::whereIn('id', $tenantIds)->get()->map(function ($t) {
            $t->students = DB::table('students')->where('tenant_id', $t->id)->where('status','active')->count();
            $t->staff    = DB::table('users')->where('tenant_id', $t->id)->count();
            $t->revenue  = DB::table('invoices')->where('tenant_id', $t->id)->sum('amount_paid');
            $t->outstanding = DB::table('invoices')->where('tenant_id', $t->id)
                ->selectRaw('SUM(total_amount - amount_paid) as bal')->value('bal') ?? 0;
            return $t;
        });

        return view('super.groups.report', compact('group','schools'));
    }

    // ── Delete group ──────────────────────────────────────────────────
    public function destroy($groupId)
    {
        $this->guard();
        DB::table('school_group_members')->where('group_id', $groupId)->delete();
        DB::table('school_groups')->where('id', $groupId)->delete();
        return redirect()->route('super.groups.index')->with('success', 'Group deleted.');
    }
}
