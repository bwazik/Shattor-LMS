@extends('layouts.landing.master')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/front-page-help-center.css') }}" />
@endsection

@section('title', pageTitle('admin/helpCenter.helpCenter'))

@section('content')
    <section class="section-py first-section-pt">
        <div class="container">
            <div class="row gy-6 gy-lg-0">
                <div class="col-lg-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2 row-gap-1">
                            <li class="breadcrumb-item">
                                <a
                                    href="{{ route('student.help-center.index') }}">{{ trans('admin/helpCenter.helpCenter') }}</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a
                                    href="{{ $article->category->articles->isNotEmpty() ? route('student.help-center.show', [$article->category->slug, $article->category->articles->sortByDesc('published_at')->first()->slug]) : '#' }}">{{ $article->category->name }}</a>
                            </li>
                            <li class="breadcrumb-item active">{{ $article->title }}</li>
                        </ol>
                    </nav>
                    <h4 class="mb-2">{{ $article->title }}</h4>
                    <p>{{ trans('main.updated_at') }}: {{ $article->updated_at->diffForHumans() }} - {{ trans('main.views') }}: {{ $article->views ?? '0' }}</p>
                    <hr class="my-6" />
                    @forelse ($article->articleContents ?? [] as $content)
                        @if ($content->type === 1)
                            <p>{{ $content->content }}</p>
                        @endif
                        @if ($content->type === 2)
                            <div class="my-6">
                                <img src="{{ asset('storage/articles/' . $article->slug . '/' . $content->content) }}"
                                    alt="article image" class="img-fluid w-100" />
                            </div>
                        @endif
                    @empty
                        <div class="text-center mt-6">
                            {{ trans('admin/helpCenter.emptyContent') }}
                        </div>
                    @endforelse
                </div>
                <div class="col-lg-4">
                    <div class="input-group input-group-merge mb-6">
                        <span class="input-group-text" id="article-search"><i class="ri-search-line ri-20px"></i></span>
                        <input type="text" class="form-control" placeholder="{{ trans('main.datatable.search') }}"
                            aria-label="Search..." aria-describedby="article-search" />
                    </div>
                    <div class="bg-lighter py-2 px-5 rounded-3">
                        <h5 class="mb-0">{{ trans('admin/helpCenter.articlesInSection') }}</h5>
                    </div>
                    <ul class="list-unstyled mt-4 mb-0">
                        @forelse ($relatedArticles as $related)
                            <li class="mb-4">
                                <a href="{{ route('student.help-center.show', [$related->category->slug, $related->slug]) }}"
                                    class="text-heading d-flex justify-content-between align-items-center">
                                    <span class="text-truncate me-1">{{ $related->title }}</span>
                                    <i class="tf-icons ri-arrow-right-s-line ri-20px scaleX-n1-rtl text-muted"></i>
                                </a>
                            </li>
                        @empty
                            <div class="text-center mt-6">
                                {{ trans('admin/helpCenter.emptyArticles') }}
                            </div>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('page-js')

@endsection
