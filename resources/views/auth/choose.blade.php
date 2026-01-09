@extends('layouts.auth.master')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}" />
    <style>
        .auth-choose-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 1rem;
        }

        .auth-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.25rem 0.75rem;;
            border-radius: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease;
            text-decoration: none;
            color: inherit;
            width: 100%;
        }

        .auth-card:hover {
            transform: translateY(-5px) scale(1.03);
        }

        .icon-wrapper {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-bottom: 0.5rem;
            transition: transform 0.3s ease;
        }

        .auth-card:hover .icon-wrapper {
            transform: rotate(10deg);
        }

        .auth-card h5 {
            font-size: 0.9rem;
            font-weight: 500;
            margin-top: 0.25rem;
            margin-bottom: 0;
        }

        @media (max-width: 360px) {
            .auth-card {
                padding: 1rem 0.5rem;
            }
            .icon-wrapper {
                width: 50px;
                height: 50px;
            }
            .auth-card h5 {
                font-size: 0.8rem;
            }
        }

        .auth-title {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .auth-subtitle {
            font-size: 0.95rem;
            color: var(--bs-secondary-color);
        }

        @media (max-width: 400px) {
            .auth-title {
                font-size: 1.25rem;
            }
            .auth-subtitle {
                font-size: 0.85rem;
            }
        }
    </style>
@endsection

@section('title', pageTitle(trans('layouts/login.title')))

@section('content')
    <h4 class="mb-2">{{ trans('layouts/login.welcome') }}</h4>
    <p class="mb-5">{{ trans('layouts/login.sign_in_prompt') }}</p>
    <div class="row g-3 justify-content-center text-nowrap">
        <!-- Teacher Login -->
        <div class="col-3">
            <a href="{{ route('login', 'teacher') }}" class="auth-card"
                aria-label="{{ trans('admin/teachers.teacher') }}">
                <div class="icon-wrapper bg-light">
                    <i class="tf-icons ri-presentation-line ri-2x text-primary"></i>
                </div>
                <h5 class="mt-1 mb-0 text-center">{{ trans('admin/teachers.teacher') }}</h5>
            </a>
        </div>

        <!-- Assistant Login -->
        <div class="col-3">
            <a href="{{ route('login', 'assistant') }}" class="auth-card"
                aria-label="{{ trans('admin/assistants.assistant') }}">
                <div class="icon-wrapper bg-light">
                    <i class="tf-icons ri-user-star-line ri-2x text-success"></i>
                </div>
                <h5 class="mt-1 mb-0 text-center">{{ trans('admin/assistants.assistant') }} ({{ trans('main.soon') }})</h5>
            </a>
        </div>

        <!-- Student Login -->
        <div class="col-3">
            <a href="{{ route('login', 'student') }}" class="auth-card"
                aria-label="{{ trans('admin/students.student') }}">
                <div class="icon-wrapper bg-light">
                    <i class="tf-icons ri-graduation-cap-line ri-2x text-info"></i>
                </div>
                <h5 class="mt-1 mb-0 text-center">{{ trans('admin/students.student') }}</h5>
            </a>
        </div>

        <!-- Parent Login -->
        <div class="col-3">
            <a href="{{ route('login', 'parent') }}" class="auth-card"
                aria-label="{{ trans('admin/parents.parent') }}">
                <div class="icon-wrapper bg-light">
                    <i class="tf-icons ri-parent-line ri-2x text-warning"></i>
                </div>
                <h5 class="mt-1 mb-0 text-center">{{ trans('admin/parents.parent') }}</h5>
            </a>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/js/pages-auth.js') }}"></script>
@endsection
