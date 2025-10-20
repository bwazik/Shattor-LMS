@extends('layouts.landing.master')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/front-page-help-center.css') }}" />
@endsection

@section('title', pageTitle('admin/helpCenter.helpCenter'))

@section('content')
    <section class="section-py first-section-pt">
        <div class="d-flex justify-content-center gap-3">
            <button id="add-button" type="button" class="btn btn-primary mb-6 mt-7 px-sm-5 waves-effect waves-light"
                tabindex="0" data-bs-toggle="modal" data-bs-target="#add-modal">
                <span>
                    <i class="ri-add-line ri-16px me-sm-2"></i>
                    <span
                        class="d-none d-sm-inline-block">{{ trans('main.addItem', ['item' => trans('admin/helpCenter.content')]) }}</span>
                </span>
            </button>
        </div>
        <div class="container">
            <div class="row gy-6 gy-lg-0">
                <div class="col-lg-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2 row-gap-1">
                            <li class="breadcrumb-item">
                                <a
                                    href="{{ route('admin.help-center.index') }}">{{ trans('admin/helpCenter.helpCenter') }}</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a
                                    href="{{ $article->category->articles->isNotEmpty() ? route('admin.help-center.show', [$article->category->slug, $article->category->articles->sortByDesc('published_at')->first()->slug]) : '#' }}">{{ $article->category->name }}</a>
                            </li>
                            <li class="breadcrumb-item active">{{ $article->title }}</li>
                        </ol>
                    </nav>
                    <h4 class="mb-2">{{ $article->title }}</h4>
                    <p>{{ trans('main.updated_at') }}: {{ $article->updated_at->diffForHumans() }} - {{ trans('main.views') }}: {{ $article->views }}</p>
                    <hr class="my-6" />
                    @forelse ($article->articleContents ?? [] as $content)
                        @if ($content->type === 1)
                            <p>
                                {{ $content->content }}
                                <button
                                    class="btn btn-sm btn-icon btn-text-secondary text-body rounded-pill waves-effect waves-light"
                                    tabindex="0" type="button" data-bs-toggle="modal" data-bs-target="#edit-modal"
                                    id="edit-button" data-id="{{ $content->id }}" data-order="{{ $content->order }}"
                                    data-content="{{ $content->content }}">
                                    <i class="ri-edit-box-line ri-20px"></i>
                                </button>
                                <button
                                    class="btn btn-sm btn-icon btn-text-danger rounded-pill text-body waves-effect waves-light me-1"
                                    id="delete-button" data-id="{{ $content->id }}"
                                    data-content="{{ $content->content }}" data-bs-target="#delete-modal"
                                    data-bs-toggle="modal" data-bs-dismiss="modal">
                                    <i class="ri-delete-bin-7-line ri-20px text-danger"></i>
                                </button>
                            </p>
                        @endif
                        @if ($content->type === 2)
                            <div class="my-6">
                                <img src="{{ asset('storage/articles/' . $article->slug . '/' . $content->content) }}"
                                    alt="article image" class="img-fluid w-100" />
                                <button
                                    class="btn btn-sm btn-icon btn-text-danger rounded-pill text-body waves-effect waves-light me-1"
                                    id="delete-button" data-id="{{ $content->id }}"
                                    data-content="{{ $content->content }}" data-bs-target="#delete-modal"
                                    data-bs-toggle="modal" data-bs-dismiss="modal">
                                    <i class="ri-delete-bin-7-line ri-20px text-danger"></i>
                                </button>
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
                                <a href="{{ route('admin.help-center.show', [$related->category->slug, $related->slug]) }}"
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

    <!-- Add Modal -->
    <x-modal modalType="add" modalSize="modal-lg"
        modalTitle="{{ trans('main.addItem', ['item' => trans('admin/helpCenter.content')]) }}"
        action="{{ route('admin.help-center.insertContent', $article->id) }}" hasFiles>
        <div class="row g-5">
            <x-select-input context="modal" name="type" label="{{ trans('main.type') }}" :options="[1 => trans('main.text'), 2 => trans('main.image')]" required />
            <x-basic-input context="modal" type="number" name="order" label="{{ trans('main.order') }}" placeholder="1"
                required />
            <div class="col-12 textContentWrapper">
                <div class="form-floating form-floating-outline">
                    <textarea id="textContent" class="form-control h-px-100" name="textContent"
                        placeholder="{{ trans('main.content') }}" aria-label="{{ trans('main.content') }}" maxlength="1000"></textarea>
                    <label for="textContent">{{ trans('main.content') }}</label>
                </div>
                <span class="invalid-feedback" id="textContent_error" role="alert"></span>
            </div>
            <div class="col-12 fileContentWrapper">
                <div class="form-floating form-floating-outline">
                    <input type="file" id="fileContent" class="form-control" name="fileContent"
                        placeholder="{{ trans('main.content') }}" aria-label="{{ trans('main.content') }}" />
                    <label for="fileContent">{{ trans('main.content') }}</label>
                </div>
                <span class="invalid-feedback" id="fileContent_error" role="alert"></span>
            </div>
        </div>
    </x-modal>
    <!-- Edit Modal -->
    <x-modal modalType="edit" modalSize="modal-lg"
        modalTitle="{{ trans('main.editItem', ['item' => trans('admin/helpCenter.content')]) }}"
        action="{{ route('admin.help-center.updateContent', $article->id) }}" id>
        <div class="row g-5">
            <x-basic-input divClasses="col-12" type="number" name="order" label="{{ trans('main.order') }}"
                placeholder="1" required />
            <div class="col-12">
                <div class="form-floating form-floating-outline">
                    <textarea id="content" class="form-control h-px-100" name="content" placeholder="{{ trans('main.content') }}"
                        aria-label="{{ trans('main.content') }}" maxlength="1000"></textarea>
                    <label for="content">{{ trans('main.content') }}</label>
                </div>
                <span class="invalid-feedback" id="content_error" role="alert"></span>
            </div>
        </div>
    </x-modal>
    <!-- Delete Modal -->
    <x-modal modalType="delete"
        modalTitle="{{ trans('main.deleteItem', ['item' => trans('admin/helpCenter.article')]) }}"
        action="{{ route('admin.help-center.deleteContent') }}" id submitColor="danger"
        submitButton="{{ trans('main.yes_delete') }}">
        @include('partials.delete-modal-body')
    </x-modal>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/custom.js') }}"></script>

    <script>
        // Setup add modal
        setupModal({
            buttonId: '#add-button',
            modalId: '#add-modal',
            fields: {
                type: () => '',
            }
        });
        // Setup edit modal
        setupModal({
            buttonId: '#edit-button',
            modalId: '#edit-modal',
            fields: {
                id: button => button.data('id'),
                order: button => button.data('order'),
                content: button => button.data('content'),
            }
        });
        // Setup delete modal
        setupModal({
            buttonId: '#delete-button',
            modalId: '#delete-modal',
            fields: {
                id: button => button.data('id'),
                itemToDelete: button => `${button.data('content')}`
            }
        });

        let fields = ['type', 'order', 'textContent', 'fileContent'];
        handleFormSubmit('#add-form', fields, '#add-modal', 'modal');
        handleFormSubmit('#edit-form', fields, '#edit-modal', 'modal');
        handleDeletionFormSubmit('#delete-form', '#delete-modal');

        $('select[id="type"]').on('change', function() {
            const selected = $(this).val();

            if (selected === '1') {
                $('.textContentWrapper').show().find('textarea').prop('disabled', false);
                $('.fileContentWrapper').hide().find('input[type="file"]').prop('disabled', true);
            } else if (selected === '2') {
                $('.fileContentWrapper').show().find('input[type="file"]').prop('disabled', false);
                $('.textContentWrapper').hide().find('textarea').prop('disabled', true);
            }
        }).trigger('change');
    </script>
@endsection
