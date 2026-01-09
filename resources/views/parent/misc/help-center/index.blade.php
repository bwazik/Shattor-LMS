@extends('layouts.landing.master')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/front-page-help-center.css') }}" />
@endsection

@section('title', pageTitle('admin/helpCenter.helpCenter'))

@section('content')
    <!-- Help Center Header: Start -->
    <section class="section-py first-section-pt help-center-header position-relative overflow-hidden">
        <img class="banner-bg-img z-n1" src="../../assets/img/pages/header-light.png" alt="Help center header"
            data-app-light-img="pages/header-light.png" data-app-dark-img="pages/header-dark.png" />
        <h4 class="text-center text-primary mb-2">{{ trans('admin/helpCenter.header') }}</h4>
        <p class="text-body text-center mb-0 px-4">{{ trans('admin/helpCenter.subheader') }}</p>
    </section>
    <!-- Help Center Header: End -->

    <!-- Pinned Articles: Start -->
    <section class="section-py">
        <div class="container">
            <h4 class="text-center mb-6">{{ trans('admin/helpCenter.pinnedArticles') }}</h4>
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <div class="row gy-6 gy-md-0">
                        @forelse ($pinnedArticles as $article)
                            <div class="col-md-4">
                                <div class="card border shadow-none">
                                    <div class="card-body text-center">
                                        <span
                                            class="avatar-initial bg-label-primary rounded-3 d-inline-flex align-items-center justify-content-center"
                                            style="width: 64px; height: 64px;">
                                            <i class="ri ri-{{ $article->category->icon }}"
                                                style="font-size: 30px; line-height: 1;"></i>
                                        </span>
                                        <h5 class="my-3">{{ $article->title }}</h5>
                                        <p class="mb-3">
                                            {{ Str::limit($article->description, 60, '…') }}</p>
                                        <a class="btn btn-outline-primary"
                                            href="{{ route('parent.help-center.show', [$article->category->slug, $article->slug]) }}">{{ trans('admin/helpCenter.readMore') }}</a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center mt-6">{{ trans('admin/helpCenter.emptyArticles') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Pinned Articles: End -->

    <!-- Knowledge Base: Start -->
    <section class="section-py bg-body">
        <div class="container">
            <h4 class="text-center mb-6">{{ trans('admin/helpCenter.articlesCategories') }}</h4>
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <div class="row g-6">
                        @foreach ($categories as $category)
                            <div class="col-md-4 col-ms-6">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div
                                                class="avatar avatar-sm flex-shrink-0 me-2 d-flex align-items-center justify-content-center">
                                                <span
                                                    class="avatar-initial bg-label-primary rounded-3 d-flex align-items-center justify-content-center">
                                                    <i class="ri ri-{{ $category->icon }} ri-20px"></i>
                                                </span>
                                            </div>
                                            <h5 class="mb-0 ms-1">{{ $category->name }}</h5>
                                        </div>
                                        <ul class="list-unstyled my-6">
                                            @forelse ($category->articles->take(6) as $article)
                                                <li class="mb-2">
                                                    <a href="{{ route('parent.help-center.show', [$category->slug, $article->slug]) }}"
                                                        class="text-heading d-flex justify-content-between align-items-center">
                                                        <span class="text-truncate me-1">{{ $article->title }}</span>
                                                        <i class="icon-base ri ri-arrow-right-s-line scaleX-n1-rtl text-body-secondary"></i>
                                                    </a>
                                                </li>
                                            @empty
                                                <div class="text-center mt-6">
                                                    {{ trans('admin/helpCenter.emptyArticles') }}
                                                </div>
                                            @endforelse
                                        </ul>
                                        @if ($category->articles->isNotEmpty())
                                            <p class="mb-0 fw-medium mt-6">
                                                <a href="{{ route('parent.help-center.show', [$category->slug, $category->articles->sortByDesc('published_at')->first()->slug]) }}"
                                                    class="d-flex align-items-center">
                                                    <span
                                                        class="me-3">{{ trans('admin/helpCenter.seeAll', ['count' => $category->articles->count()]) }}</span>
                                                    <i class="tf-icons ri-arrow-right-line scaleX-n1-rtl"></i>
                                                </a>
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Knowledge Base: End -->

    <!-- Help Area: Start -->
    <section class="section-py">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <div class="badge bg-label-primary rounded-pill">{{ trans('admin/faqs.contact.question') }}</div>
                    <h4 class="my-2">{{ trans('admin/faqs.contact.title') }}</h4>
                    <p class="mb-0">{{ trans('admin/faqs.contact.subtitle') }}</p>
                </div>
            </div>
            <div class="row justify-content-center gap-sm-0 gap-6">
                <div class="col-sm-6">
                    <div class="p-6 rounded-4 bg-faq-section d-flex align-items-center flex-column">
                        <div class="avatar avatar-md">
                            <span class="avatar-initial bg-label-primary rounded-3">
                                <i class="ri-phone-line ri-30px"></i>
                            </span>
                        </div>
                        <h5 class="mt-4 mb-1"><a class="text-heading"
                                href="https://wa.me/+201098617164">+201098617164</a>
                        </h5>
                        <p class="mb-0">{{ trans('admin/faqs.contact.phone') }}</p>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="p-6 rounded-4 bg-faq-section d-flex align-items-center flex-column">
                        <div class="avatar avatar-md">
                            <span class="avatar-initial bg-label-primary rounded-3">
                                <i class="ri-mail-line ri-30px"></i>
                            </span>
                        </div>
                        <h5 class="mt-4 mb-1"><a class="text-heading"
                                href="mailto:support@shattor.com">support@shattor.com</a></h5>
                        <p class="mb-0">{{ trans('admin/faqs.contact.email') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Help Area: End -->
@endsection

@section('page-js')

@endsection
