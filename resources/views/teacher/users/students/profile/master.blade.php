@extends('layouts.teacher.master')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-profile.css') }}" />

    <style>
        .profile-img-container {
            position: relative;
            display: inline-block;
        }

        .user-profile-img {
            object-fit: cover;
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .profile-action-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            opacity: 0;
        }

        .profile-dropdown {
            opacity: 0;
        }

        .profile-img-container:hover .user-profile-img {
            opacity: 0.85;
            transform: scale(1.05);
        }

        .profile-img-container:hover .profile-action-btn {
            opacity: 1;
        }

        .profile-img-container:hover .profile-dropdown {
            opacity: 1;
        }
    </style>
@endsection

@section('title', pageTitle('admin/students.studentsProfile'))

@section('content')
    <!-- Header -->
    @include('teacher.users.students.profile.header')
    <!-- Header -->

    <!-- Navbar -->
    @include('teacher.users.students.profile.navbar')
    <!-- Navbar -->

    <!-- Details -->
    <div class="row">
        @include('teacher.users.students.profile.details')
        <div class="col-xl-8 col-lg-7 col-md-7">
            @yield('profile-content')
        </div>
    </div>
    <!-- Details -->
@endsection

@section('page-js')
    <script>
        document.addEventListener('DOMContentLoaded', function(e) {
            (function() {
                let accountUserImage = document.getElementById('uploadedAvatar');
                const fileInput = document.querySelector('.account-file-input'),
                    resetFileInput = document.querySelector('.account-image-reset');

                if (accountUserImage) {
                    const resetImage = accountUserImage.src;
                    fileInput.onchange = () => {
                        if (fileInput.files[0]) {
                            accountUserImage.src = window.URL.createObjectURL(fileInput.files[0]);
                        }
                    };
                    resetFileInput.onclick = () => {
                        fileInput.value = '';
                        accountUserImage.src = resetImage;
                    };
                }
            })();
        });

        const allowedExtensions = ['jpg', 'jpeg', 'png'];
        handleProfilePicSubmit('#update-profile-form', 1.5, allowedExtensions);
    </script>

    @yield('profile-js')
@endsection
