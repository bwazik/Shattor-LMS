<?php

namespace App\Http\Controllers\Student\Misc;

use App\Models\Article;
use App\Models\Category;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class HelpCenterController extends Controller
{
    protected $studentId;

    public function __construct()
    {
        $this->studentId = auth()->guard('student')->user()->id;
    }

    public function index()
    {
        $categories = Cache::remember('student_help_center_categories', 1440, function () {
            return Category::with(['articles' => fn($q) => $q->forStudents()->active()->orderBy('published_at', 'desc')])
                ->orderBy('order')
                ->get();
        });

        $pinnedArticles = Cache::remember('student_pinned_articles', 1440, function () {
            return Article::pinned()
                ->forStudents()
                ->active()
                ->orderBy('published_at', 'desc')
                ->take(3)
                ->get();
        });

        return view('student.misc.help-center.index', compact('categories', 'pinnedArticles'));
    }

    public function show($categorySlug, $articleSlug)
    {
        $article = Cache::remember("article_{$categorySlug}_{$articleSlug}", 1440, function () use ($categorySlug, $articleSlug) {
            return Article::with('category', 'articleContents')
                ->forStudents()
                ->active()
                ->where('slug', $articleSlug)
                ->whereHas('category', fn($q) => $q->where('slug', $categorySlug))
                ->firstOrFail();
        });

        $relatedArticles = Article::where('category_id', $article->category_id)
            ->forStudents()
            ->active()
            ->where('id', '!=', $article->id)
            ->orderBy('published_at', 'desc')
            ->get();

        $this->incrementViews($article->id);

        return view('student.misc.help-center.show', compact('article', 'relatedArticles'));
    }

    protected function incrementViews($articleId)
    {
        $cacheKey = "article_view_{$articleId}_student_{$this->studentId}";

        if (!Cache::has($cacheKey)) {
            Article::where('id', $articleId)->increment('views');
            Cache::put($cacheKey, true, now()->addMinutes(1440));
        }
    }
}
