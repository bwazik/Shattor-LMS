<?php

namespace App\Http\Controllers\Parent\Misc;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class FaqsController extends Controller
{
    public function index(Request $request)
    {
        $categories = Cache::remember('parent_faqs_categories', now()->addMinutes(1440), function () {
            return Category::with(['faqs' => fn($q) => $q->forParents()->active()->orderBy('order')])
                ->whereHas('faqs', fn($q) => $q->forParents()->active())
                ->orderBy('order')
                ->get();
        });

        return view('parent.misc.faqs.index', compact('categories'));
    }
}
