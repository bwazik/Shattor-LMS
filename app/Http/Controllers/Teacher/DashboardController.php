<?php

namespace App\Http\Controllers\Teacher;

use App\Models\Lesson;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    protected $teacherId;

    public function __construct()
    {
        $this->teacherId = auth()->guard('teacher')->user()->id;
    }

    public function index(Request $request)
    {
        $lessonsQuery = Lesson::query()->with(['group:id,uuid,name'])
            ->select('id', 'uuid', 'title', 'group_id', 'date', 'time', 'status')
            ->whereDate('date', today())
            ->whereHas('group', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->orderBy('time');

        if ($request->ajax()) {
            return datatables()->eloquent($lessonsQuery)
                ->addIndexColumn()
                ->addColumn('attendances', fn($row) => formatSpanUrl(route('teacher.lessons.attendances', $row->uuid), trans('admin/lessons.attendancesLink')))
                ->editColumn('title', fn($row) => $row->title)
                ->editColumn('group_id', fn($row) => formatRelation($row->group_id, $row->group, 'name'))
                ->editColumn('status', fn($row) => formatLessonStatus($row->status))
                ->addColumn('actions', fn($row) => $this->generateIndexActionButtons($row))
                ->filterColumn('group_id', fn($query, $keyword) => filterByRelation($query, 'group', 'name', $keyword))
                ->rawColumns(['selectbox', 'attendances', 'group_id', 'status', 'actions'])
                ->make(true);
        }

        return view('teacher.dashboard');
    }

    private function generateIndexActionButtons($row): string
    {
        return
            '<div class="d-inline-block">' .
            '<a href="javascript:;" class="btn btn-sm btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">' .
            '<i class="ri-more-2-line"></i>' .
            '</a>' .
            '<ul class="dropdown-menu dropdown-menu-end m-0">' .
            '<li>
                        <a target="_blank" href="' . route('teacher.lessons.reports', $row->uuid) . '" class="dropdown-item">' . trans('main.reports') . '</a>
                    </li>' .
            '<li>
                        <a target="_blank" href="' . route('teacher.lessons.compensatories', $row->uuid) . '" class="dropdown-item">' . trans('admin/compensatories.compensatories') . '</a>
                    </li>' .
            '</ul>' .
            '</div>';
    }

    public function dublicatedStudents(Request $request)
    {
        $locale = app()->getLocale();
        $jsonPath = $locale === 'ar' ? '$.ar' : '$.en';

        // 1. Get duplicated clean names (RAW from SQL)
        $rawCleanNames = DB::table('students')
            ->join('student_teacher', 'students.id', '=', 'student_teacher.student_id')
            ->where('student_teacher.teacher_id', $this->teacherId)
            ->selectRaw("TRIM(LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, ?)))) AS clean_name", [$jsonPath])
            ->pluck('clean_name');

        // 🔥 NEW: normalize all SQL clean names
        $normalizedMap = $rawCleanNames->map(fn($n) => $this->normalizeArabicName($n));

        // 🔥 NEW: find only duplicated normalized versions
        $duplicatedNormalized = $normalizedMap
            ->groupBy(fn($n) => $n)
            ->filter(fn($group) => $group->count() > 1)
            ->keys()
            ->values();

        // convert normalized duplicates back → original raw names
        $duplicatedCleanNames = $rawCleanNames->filter(function ($original) use ($duplicatedNormalized) {
            return $duplicatedNormalized->contains($this->normalizeArabicName($original));
        })->values();

        // لو مفيش تكرار
        if ($duplicatedCleanNames->isEmpty()) {
            return $request->ajax()
                ? datatables()->of(collect([]))->make(true)
                : view('teacher.dashboard.duplicates', ['duplicated' => collect()]);
        }

        // 2. Main query — SAME without modification
        $query = Student::query()
            ->with(['grade:id,name'])
            ->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId))
            ->where(function ($q) use ($jsonPath, $duplicatedCleanNames) {
                $q->whereRaw(
                    "TRIM(LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, ?)))) IN (" . implode(',', array_fill(0, $duplicatedCleanNames->count(), '?')) . ")",
                    array_merge([$jsonPath], $duplicatedCleanNames->toArray())
                );
            })
            ->select('students.id', 'students.uuid', 'students.name', 'students.phone', 'students.grade_id', 'students.profile_pic')
            ->orderByRaw("TRIM(LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, ?))))", [$jsonPath])
            ->orderBy('name');

        if ($request->ajax()) {
            return datatables()->eloquent($query)
                ->addIndexColumn()
                ->addColumn('details', fn($row) => generateDetailsColumn(
                    $row->getTranslation('name', $locale),
                    $row->profile_pic,
                    'storage/profiles/students',
                    $row->phone,
                    'teacher.students.profile.index',
                    $row->uuid
                ))
                ->editColumn('grade_id', fn($row) => formatRelation($row->grade_id, $row->grade, 'name'))
                ->rawColumns(['details'])
                ->make(true);
        }

        $duplicated = $query->get()->groupBy(
            fn($s) =>
            trim(strtolower($s->getTranslation('name', $locale)))
        );

        return view('teacher.dashboard.duplicates', compact('duplicated'));
    }

    function normalizeArabicName($name)
    {
        $name = mb_strtolower($name);

        $name = str_replace(
            ['أ', 'إ', 'آ'],
            'ا',
            $name
        );

        $name = str_replace('ة', 'ه', $name);

        $name = str_replace('ى', 'ي', $name);

        $name = str_replace('ؤ', 'و', $name);

        $name = str_replace('ئ', 'ي', $name);

        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }

    public function multipleGroupStudents(Request $request)
    {
        $violatingStudents = collect();

        $groupTypes = [
            5 => [
                'pure' => [35, 36, 37, 38, 39],
                'applied' => [40, 41, 42, 43],
            ],
            6 => [
                'literary' => [22, 23],
                'scientific_pair1' => [26, 28],
                'scientific_pair2' => [27, 29],
            ],
        ];

        // ==================== Grade 4: Max 1 group ====================
        $grade4Students = Student::with(['grade:id,name', 'groups:id,name'])
            ->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId))
            ->where('grade_id', 4)
            ->whereHas('groups', function ($q) {
                $q->whereNull('student_group.ended_at');
            })
            ->get();

        foreach ($grade4Students as $student) {
            $activeGroups = $student->groups;

            if ($activeGroups->count() > 1) {
                $violatingStudents->push([
                    'student' => $student,
                    'groups' => $activeGroups,
                    'grade_order' => 4,
                    'violation_reason' => 'يجب أن يكون الطالب في مجموعة واحدة فقط',
                ]);
            }
        }

        // ==================== Grade 5: Pure + Applied rules ====================
        $grade5Students = Student::with(['grade:id,name', 'groups:id,name'])
            ->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId))
            ->where('grade_id', 5)
            ->whereHas('groups', function ($q) {
                $q->whereNull('student_group.ended_at');
            })
            ->get();

        $pureGroupIds5 = $groupTypes[5]['pure'];
        $appliedGroupIds5 = $groupTypes[5]['applied'];

        foreach ($grade5Students as $student) {
            $activeGroups = $student->groups->unique('id');
            $groupIds = $activeGroups->pluck('id')->unique()->toArray();

            $inPure = array_intersect($groupIds, $pureGroupIds5);
            $inApplied = array_intersect($groupIds, $appliedGroupIds5);

            $hasViolation = false;
            $reason = '';

            if ($student->specialization == 1) {
                // Scientific: Must have 1 pure + 1 applied
                if (count($inPure) != 1 || count($inApplied) != 1) {
                    $hasViolation = true;
                    $reason = sprintf(
                        'طالب علمي يجب أن يكون في مجموعة بحتة واحدة ومجموعة تطبيقية واحدة (حالياً: %d بحتة + %d تطبيقية)',
                        count($inPure),
                        count($inApplied)
                    );
                }
            } elseif ($student->specialization == 2) {
                // Literary: Must have 1 pure only (no applied)
                if (count($inPure) != 1 || count($inApplied) > 0) {
                    $hasViolation = true;
                    $reason = sprintf(
                        'طالب أدبي يجب أن يكون في مجموعة بحتة واحدة فقط (حالياً: %d بحتة + %d تطبيقية)',
                        count($inPure),
                        count($inApplied)
                    );
                }
            }

            if ($hasViolation) {
                $violatingStudents->push([
                    'student' => $student,
                    'groups' => $activeGroups,
                    'grade_order' => 5,
                    'violation_reason' => $reason,
                ]);
            }
        }

        // ==================== Grade 6: Literary/Scientific rules ====================
        $grade6Students = Student::with(['grade:id,name', 'groups:id,name'])
            ->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId))
            ->where('grade_id', 6)
            ->whereHas('groups', function ($q) {
                $q->whereNull('student_group.ended_at');
            })
            ->get();

        $literaryGroups6 = $groupTypes[6]['literary'];
        $scientificPair1 = $groupTypes[6]['scientific_pair1'];
        $scientificPair2 = $groupTypes[6]['scientific_pair2'];

        foreach ($grade6Students as $student) {
            $activeGroups = $student->groups->unique('id');
            $groupIds = $activeGroups->pluck('id')->unique()->toArray();

            $hasViolation = false;
            $reason = '';

            if ($student->specialization == 2) {
                // Literary: Must have 1 from literary groups only
                $inLiterary = array_intersect($groupIds, $literaryGroups6);
                $inScientific = array_intersect($groupIds, array_merge($scientificPair1, $scientificPair2));

                if (count($inLiterary) != 1 || count($inScientific) > 0) {
                    $hasViolation = true;
                    $reason = sprintf(
                        'طالب أدبي يجب أن يكون في مجموعة أدبية واحدة فقط (حالياً: %d أدبية + %d علمية)',
                        count($inLiterary),
                        count($inScientific)
                    );
                }
            } elseif ($student->specialization == 1) {
                // Scientific: Must have 1 from pair1 + 1 from pair2
                $inPair1 = array_intersect($groupIds, $scientificPair1);
                $inPair2 = array_intersect($groupIds, $scientificPair2);
                $inLiterary = array_intersect($groupIds, $literaryGroups6);

                if (count($inPair1) != 1 || count($inPair2) != 1 || count($inLiterary) > 0) {
                    $hasViolation = true;
                    $reason = sprintf(
                        'طالب علمي يجب أن يكون في مجموعة واحدة من كل زوج (حالياً: %d من الزوج الأول + %d من الزوج الثاني + %d أدبية)',
                        count($inPair1),
                        count($inPair2),
                        count($inLiterary)
                    );
                }
            }

            if ($hasViolation) {
                $violatingStudents->push([
                    'student' => $student,
                    'groups' => $activeGroups,
                    'grade_order' => 6,
                    'violation_reason' => $reason,
                ]);
            }
        }

        // Sort by grade (4, 5, 6)
        $violatingStudents = $violatingStudents->sortBy('grade_order')->values();

        if ($request->ajax()) {
            return datatables()->of($violatingStudents)
                ->addIndexColumn()
                ->addColumn('details', function ($row) {
                    $student = $row['student'];
                    return generateDetailsColumn(
                        $student->name,
                        $student->profile_pic,
                        'storage/profiles/students',
                        $student->phone,
                        'teacher.students.profile.index',
                        $student->uuid
                    );
                })
                ->addColumn('grade_id', fn($row) => formatRelation($row['student']->grade_id, $row['student']->grade, 'name'))
                ->addColumn('specialization', function ($row) {
                    $student = $row['student'];
                    if ($student->specialization == 1) {
                        return formatSpan('success', trans('main.scientific'));
                    } elseif ($student->specialization == 2) {
                        return formatSpan('warning', trans('main.literary'));
                    }
                    return '-';
                })
                ->addColumn('groups', function ($row) {
                    return $row['groups']->map(function ($group) {
                        return formatSpan('info', $group->name);
                    })->implode('');
                })
                ->addColumn('violation', function ($row) {
                    return formatSpan('danger', $row['violation_reason']);
                })
                ->rawColumns(['details', 'groups', 'specialization', 'violation'])
                ->make(true);
        }
    }
}
