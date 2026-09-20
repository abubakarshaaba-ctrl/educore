@php
    $pcUser = auth()->user();
    $pcCanManage = $pcUser
        ? app(\App\Services\ParallelCurriculumService::class)->canManageLifecycle($pcUser)
        : false;
    $pcCanViewOperations = $pcUser
        ? app(\App\Services\ParallelCurriculumOperationsService::class)->canViewOperations($pcUser)
        : false;
    $pcCanViewBroadsheet = $pcUser
        && (
            $pcUser->canAccessModule('scores.view')
            || $pcUser->canAccessExactModule('scores')
        );
    $pcCanUseComments = $pcUser
        && $pcUser->canAccessRoute('parallel-curriculum.form-teacher-comments.index');
    $pcCanUseSkills = $pcUser
        && $pcUser->canAccessRoute('parallel-curriculum.skills.index');
    $pcCanUseAttendance = $pcUser
        && $pcUser->canAccessRoute('parallel-curriculum.attendance.index');

    $pcCurrentLabel = match (true) {
        request()->routeIs('parallel-curriculum.setup') => 'Programme Setup',
        request()->routeIs('parallel-curriculum.student-assignments') => 'Student Assignments',
        request()->routeIs('parallel-curriculum.class-list') => 'Class List',
        request()->routeIs('parallel-curriculum.teacher-list') => 'Teacher List',
        request()->routeIs('parallel-curriculum.lifecycle.*') => 'Academic Lifecycle',
        request()->routeIs('parallel-curriculum.attendance.*') => 'Student Attendance',
        request()->routeIs('parallel-curriculum.skills.*') => 'Skills Rating',
        request()->routeIs('parallel-curriculum.operations.*') => 'Timetable & Attendance',
        request()->routeIs('parallel-curriculum.results.broadsheet*') => 'Broadsheet',
        request()->routeIs('parallel-curriculum.results.*') => 'Parallel Results',
        request()->routeIs('parallel-curriculum.score-sheet') => 'Score Entry',
        request()->routeIs('parallel-curriculum.breakdown') => 'Result Breakdown',
        request()->routeIs('parallel-curriculum.form-teacher-comments.*') => 'Form Teacher Comments',
        default => 'Workspace',
    };
@endphp

<nav class="pc-module-nav no-print" data-pc-module-nav aria-label="Parallel curriculum navigation">
    <div class="pc-module-nav-head">
        <a class="pc-module-brand" href="{{ route('parallel-curriculum.index') }}">
            <span class="pc-module-brand-mark" aria-hidden="true">PC</span>
            <span>
                <strong>Parallel Curriculum</strong>
                <small>{{ $pcCurrentLabel }}</small>
            </span>
        </a>

        <button
            class="pc-module-nav-toggle"
            type="button"
            aria-expanded="false"
            aria-controls="pc-module-nav-panel"
            data-pc-nav-toggle
        >
            <span>Navigate</span>
            <span aria-hidden="true">▾</span>
        </button>
    </div>

    <div class="pc-module-nav-panel" id="pc-module-nav-panel" data-pc-nav-panel>
        <div class="pc-module-nav-group">
            <span class="pc-module-nav-label">Workspace</span>
            <div class="pc-module-nav-items">
                <a
                    href="{{ route('parallel-curriculum.index') }}"
                    class="{{ request()->routeIs('parallel-curriculum.index') ? 'active' : '' }}"
                    @if(request()->routeIs('parallel-curriculum.index')) aria-current="page" @endif
                >Overview</a>
                <a
                    href="{{ route('parallel-curriculum.index') }}#parallel-score-entry"
                    class="{{ request()->routeIs('parallel-curriculum.score-sheet') ? 'active' : '' }}"
                    @if(request()->routeIs('parallel-curriculum.score-sheet')) aria-current="page" @endif
                >Score Entry</a>
            </div>
        </div>

        @if($pcCanManage)
            <div class="pc-module-nav-group">
                <span class="pc-module-nav-label">Set up</span>
                <div class="pc-module-nav-items">
                    <a
                        href="{{ route('parallel-curriculum.setup') }}"
                        class="{{ request()->routeIs('parallel-curriculum.setup') ? 'active' : '' }}"
                        @if(request()->routeIs('parallel-curriculum.setup')) aria-current="page" @endif
                    >Programme</a>
                    <a
                        href="{{ route('parallel-curriculum.lifecycle.index') }}"
                        class="{{ request()->routeIs('parallel-curriculum.lifecycle.*') ? 'active' : '' }}"
                        @if(request()->routeIs('parallel-curriculum.lifecycle.*')) aria-current="page" @endif
                    >Lifecycle</a>
                </div>
            </div>

            <div class="pc-module-nav-group">
                <span class="pc-module-nav-label">People</span>
                <div class="pc-module-nav-items">
                    <a
                        href="{{ route('parallel-curriculum.student-assignments') }}"
                        class="{{ request()->routeIs('parallel-curriculum.student-assignments') ? 'active' : '' }}"
                        @if(request()->routeIs('parallel-curriculum.student-assignments')) aria-current="page" @endif
                    >Students</a>
                    <a
                        href="{{ route('parallel-curriculum.class-list') }}"
                        class="{{ request()->routeIs('parallel-curriculum.class-list') ? 'active' : '' }}"
                        @if(request()->routeIs('parallel-curriculum.class-list')) aria-current="page" @endif
                    >Class List</a>
                    <a
                        href="{{ route('parallel-curriculum.teacher-list') }}"
                        class="{{ request()->routeIs('parallel-curriculum.teacher-list') ? 'active' : '' }}"
                        @if(request()->routeIs('parallel-curriculum.teacher-list')) aria-current="page" @endif
                    >Teacher List</a>
                </div>
            </div>
        @endif

        @if($pcCanViewOperations || $pcCanUseAttendance || $pcCanUseSkills || $pcCanUseComments)
            <div class="pc-module-nav-group">
                <span class="pc-module-nav-label">Daily work</span>
                <div class="pc-module-nav-items">
                    @if($pcCanUseAttendance)
                        <a
                            href="{{ route('parallel-curriculum.attendance.index') }}"
                            class="{{ request()->routeIs('parallel-curriculum.attendance.*') ? 'active' : '' }}"
                            @if(request()->routeIs('parallel-curriculum.attendance.*')) aria-current="page" @endif
                        >Student Attendance</a>
                    @endif
                    @if($pcCanUseSkills)
                        <a
                            href="{{ route('parallel-curriculum.skills.index') }}"
                            class="{{ request()->routeIs('parallel-curriculum.skills.*') ? 'active' : '' }}"
                            @if(request()->routeIs('parallel-curriculum.skills.*')) aria-current="page" @endif
                        >Skills Rating</a>
                    @endif
                    @if($pcCanUseComments)
                        <a
                            href="{{ route('parallel-curriculum.form-teacher-comments.index') }}"
                            class="{{ request()->routeIs('parallel-curriculum.form-teacher-comments.*') ? 'active' : '' }}"
                            @if(request()->routeIs('parallel-curriculum.form-teacher-comments.*')) aria-current="page" @endif
                        >Form Comments</a>
                    @endif
                    @if($pcCanViewOperations && $pcCanManage)
                        <a
                            href="{{ route('parallel-curriculum.operations.index') }}"
                            class="{{ request()->routeIs('parallel-curriculum.operations.*') ? 'active' : '' }}"
                            @if(request()->routeIs('parallel-curriculum.operations.*')) aria-current="page" @endif
                        >Timetable & Staff</a>
                    @endif
                </div>
            </div>
        @endif

        <div class="pc-module-nav-group">
            <span class="pc-module-nav-label">Results</span>
            <div class="pc-module-nav-items">
                @if($pcCanManage)
                    <a
                        href="{{ route('parallel-curriculum.results.index') }}"
                        class="{{ (request()->routeIs('parallel-curriculum.results.*') && !request()->routeIs('parallel-curriculum.results.broadsheet*')) || request()->routeIs('parallel-curriculum.breakdown') ? 'active' : '' }}"
                        @if((request()->routeIs('parallel-curriculum.results.*') && !request()->routeIs('parallel-curriculum.results.broadsheet*')) || request()->routeIs('parallel-curriculum.breakdown')) aria-current="page" @endif
                    >Parallel Results</a>
                    <a
                        href="{{ route('parallel-curriculum.results.broadsheet') }}"
                        class="{{ request()->routeIs('parallel-curriculum.results.broadsheet*') ? 'active' : '' }}"
                        @if(request()->routeIs('parallel-curriculum.results.broadsheet*')) aria-current="page" @endif
                    >Parallel Broadsheet</a>
                @endif
                @if($pcCanViewBroadsheet)
                    <a href="{{ route('scores.broadsheet') }}">Broadsheet</a>
                @endif
                <a href="{{ route('scores.index') }}">Conventional Scores</a>
            </div>
        </div>
    </div>
</nav>

@once
    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-pc-module-nav]').forEach(function (nav) {
                const toggle = nav.querySelector('[data-pc-nav-toggle]');
                if (!toggle) return;

                toggle.addEventListener('click', function () {
                    const open = nav.classList.toggle('is-open');
                    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                });

                nav.querySelectorAll('.pc-module-nav-panel a').forEach(function (link) {
                    link.addEventListener('click', function () {
                        if (window.matchMedia('(max-width: 768px)').matches) {
                            nav.classList.remove('is-open');
                            toggle.setAttribute('aria-expanded', 'false');
                        }
                    });
                });
            });
        });
        </script>
    @endpush
@endonce
