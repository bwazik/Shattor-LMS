<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Cache;
use App\Traits\DatabaseTransactionTrait;

class LandingController extends Controller
{
    use ServiceResponseTrait, DatabaseTransactionTrait;

    public function index()
    {
        $metrics = Cache::remember('landing_fun_facts', 1440, function () {
            return [
                'teachers' => Teacher::count(),
                'students' => Student::count(),
                'lessons' => Lesson::completed()->count(),
                'groups' => Group::count(),
            ];
        });

        $faqs = Cache::remember('landing_faqs', 1440, function () {
            return Faq::atLanding()->active()->orderBy('order')->get();
        });

        return view('landing.index', compact('metrics', 'faqs'));
    }

    public function contact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:100',
            'phone' => 'required|numeric|regex:/^(01)[0-9]{9}$/',
            'message' => 'required|string|max:500'
        ]);

        $result = $this->executeTransaction(function () use ($validated) {
            return $this->successResponse(trans('toasts.messageSent'));
        }, trans('main.errorMessage'));

        return $this->conrtollerJsonResponse($result);
    }
}
