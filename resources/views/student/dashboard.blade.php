@extends('layouts.student.master')

@section('page-css')

@endsection

@section('title', pageTitle('layouts/sidebar.dashboard'))

@section('content')
    <div class="row mb-12 g-6">
        <div class="col-md-12 col-lg-12">
            <div class="card">
                <div class="d-flex align-items-end row">
                    <div class="col-md-6 order-2 order-md-1">
                        <div class="card-body">
                            <h4 class="card-title mb-4">{{ trans('dashboard.welcomeMessage') }} <span
                                    class="fw-bold">{{ $studentName }}!</span> 👋🏻</h4>
                            <p class="mb-0">{{ $examMessage }}</p>
                            <p>{{ trans('dashboard.profileMessage') }}</p>
                            <a href="#"
                                class="btn btn-primary waves-effect waves-light me-2">{{ trans('layouts/navbar.myProfile') }}</a>
                            <a href="{{ route('student.account.personal.edit') }}"
                                class="btn btn-primary waves-effect waves-light">{{ trans('dashboard.downloadQrCode') }}</a>
                        </div>
                    </div>
                    <div class="col-md-6 text-center text-md-end order-1 order-md-2">
                        <div class="card-body pb-0 px-0 pt-1_5">
                            <img src="{{ asset('assets/img/illustrations/illustration-john-light.png') }}" height="186"
                                class="scaleX-n1-rtl" alt="View Profile"
                                data-app-light-img="illustrations/illustration-john-light.png"
                                data-app-dark-img="illustrations/illustration-john-dark.png" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-12 g-6">
        <div class="col-md-12 col-lg-12">
            <div class="card">
                <div class="row g-0">
                    <div class="col-md-4">
                        <img class="card-img card-img-left"
                            height="100" width="100"
                            src="https://yt3.googleusercontent.com/UzsuQ96fmzYdKm5ApDh7NLrWJ_z7tH8ohsMlZLolDyBzdE9LKG_iSQVzGxuZluCIH6Sg5oiSGQ=s160-c-k-c0x00ffffff-no-rj"
                            alt="youtube logo">
                    </div>
                    <div class="col-md-8">
                        <div class="card-body">
                            <h5 class="card-title">فيزيكس ببساطة</h5>
                            <p class="card-text">لو عايز تفهم الفيزياء بطريقة بسيطة وسهلة، تابع فيديوهاتها على اليوتيوب، أو
                                احجز معاها حصص برايفت أونلاين / أوفلاين.</p>
                            <a href="https://www.youtube.com/@Physics.in.few.minutes"
                                class="btn btn-primary waves-effect waves-light me-1">رابط القناة</a>
                            <a href="https://wa.me/+201095429533" class="btn btn-primary waves-effect waves-light">للتواصل
                                واتس اب</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- <div class="text-center">
        <h1>{{ trans('main.soon') }} 🚀</h1>
        <img src="{{ asset('assets/img/illustrations/misc-coming-soon-illustration.png') }}" alt="misc-coming-soon"
            class="img-fluid z-1" width="190" />
    </div> --}}
@endsection

@section('page-js')
@endsection
