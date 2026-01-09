@extends('layouts.parent.master')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-faq.css') }}" />
@endsection

@section('title', pageTitle('admin/faqs.faqs'))

@section('content')
    <div
        class="faq-header d-flex flex-column justify-content-center align-items-center h-px-300 position-relative overflow-hidden rounded-4">
        <img src="{{ asset('assets/img/pages/header-light.png') }}" class="scaleX-n1-rtl faq-banner-img h-px-300 z-n1"
            alt="background image" data-app-light-img="pages/header-light.png" data-app-dark-img="pages/header-dark.png" />
        <h4 class="text-center text-primary mb-2">{{ trans('admin/faqs.header') }}</h4>
        <p class="text-body text-center mb-0 px-4">{{ trans('admin/faqs.subheader') }}</p>
    </div>

    <div class="row mt-6">
        <!-- Navigation -->
        <div class="col-lg-3 col-md-4 col-12 mb-md-0 mb-4">
            <div class="d-flex justify-content-between flex-column nav-align-left mb-2 mb-md-0">
                <ul class="nav nav-pills flex-column flex-nowrap" role="tablist">
                    @foreach ($categories as $index => $category)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $index === 0 ? 'active' : '' }}" data-bs-toggle="tab"
                                data-bs-target="#{{ $category->slug }}"
                                aria-selected="{{ $index === 0 ? 'true' : 'false' }}" role="tab">
                                <i class="ri ri-{{ $category->icon }} me-2"></i>
                                <span class="align-middle">{{ $category->name }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
                <div class="d-none d-md-block">
                    <div class="mt-4 text-center">
                        <img src="{{ asset('assets/img/illustrations/faq-illustration.png') }}" class="img-fluid"
                            width="135" alt="FAQ Image" />
                    </div>
                </div>
            </div>
        </div>
        <!-- /Navigation -->

        <!-- FAQs -->
        <div class="col-lg-9 col-md-8 col-12">
            <div class="tab-content p-0">
                @foreach ($categories as $index => $category)
                    <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="{{ $category->slug }}"
                        role="tabpanel">
                        <div class="d-flex mb-4 gap-4 align-items-center">
                            <div class="avatar avatar-md">
                                <span class="avatar-initial bg-label-primary rounded-4">
                                    <i class="ri ri-{{ $category->icon }} ri-30px"></i>
                                </span>
                            </div>
                            <div>
                                <h5 class="mb-0">
                                    <span class="align-middle">{{ $category->name }}</span>
                                </h5>
                                <span>{{ $category->description }}</span>
                            </div>
                        </div>
                        <div id="accordion{{ $category->slug }}"
                            class="accordion accordion-popout accordion-header-primary">
                            @forelse ($category->faqs as $faqIndex => $faq)
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            aria-expanded="false"
                                            data-bs-target="#accordion{{ $category->slug }}-{{ $faq->id }}"
                                            aria-controls="accordion{{ $category->slug }}-{{ $faq->id }}">
                                            {{ $faq->question }}
                                        </button>
                                    </h2>
                                    <div id="accordion{{ $category->slug }}-{{ $faq->id }}"
                                        class="accordion-collapse collapse"
                                        data-bs-parent="#accordion{{ $category->slug }}">
                                        <div class="accordion-body">
                                            {{ $faq->answer }}
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center mt-6">{{ trans('main.datatable.empty_table') }}</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <!-- /FAQs -->
    </div>

    <!-- Contact -->
    <div class="row my-6">
        <div class="col-12 text-center my-6">
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
                <h5 class="mt-4 mb-1"><a class="text-heading" href="https://wa.me/+201098617164">+201098617164</a></h5>
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
                <h5 class="mt-4 mb-1"><a class="text-heading" href="mailto:support@shattor.com">support@shattor.com</a></h5>
                <p class="mb-0">{{ trans('admin/faqs.contact.email') }}</p>
            </div>
        </div>
    </div>
    <!-- /Contact -->
@endsection

@section('page-js')

@endsection
