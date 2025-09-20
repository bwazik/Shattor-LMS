<?php

namespace App\Http\Controllers\Student;

use Carbon\Carbon;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    protected $student;

    public function __construct()
    {
        $this->student = auth()->guard('student')->user();
    }

    public function index()
    {
        $studentName = explode(' ', trim($this->student->name))[0];

        $today = Carbon::today();

        switch ($this->student->grade_id) {
            case 6:
                $examDate = Carbon::create(2026, 6, 10);
                $daysLeft = $today->diffInDays($examDate, false);
                $examMessage = trans('dashboard.examMessage.grade6', ['days' => $daysLeft]);
                break;
            case 5:
                $examDate = Carbon::create(2026, 1, 15);
                $daysLeft = $today->diffInDays($examDate, false);
                $examMessage = trans('dashboard.examMessage.anyGrade', ['days' => $daysLeft]);
                break;
            case 4:
                $examDate = Carbon::create(2026, 1, 1);
                $daysLeft = $today->diffInDays($examDate, false);
                $examMessage = trans('dashboard.examMessage.anyGrade', ['days' => $daysLeft]);
                break;
            default:
                $examDate = Carbon::create(2026, 1, 1);
                $daysLeft = $today->diffInDays($examDate, false);
                $examMessage = trans('dashboard.examMessage.anyGrade', ['days' => $daysLeft]);
                break;
        }

        return view('student.dashboard', compact('studentName', 'examMessage'));
    }
}
