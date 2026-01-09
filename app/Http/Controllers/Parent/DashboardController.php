<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $students = auth()->guard('parent')->user()
            ->students()
            ->with('grade:id,name')
            ->withCount([
                'attendances',
                'studentResults as quizzes_count',
                'assignmentSubmissions as assignments_count'
            ])
            ->get();

        return view('parent.dashboard', compact('students'));
    }
}