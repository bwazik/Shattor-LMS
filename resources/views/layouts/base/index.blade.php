<!DOCTYPE html>

<html
    lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}"
    class="@yield('html-classes')" data-theme="theme-default" data-assets-path="{{ asset('assets') }}/"
    data-template="@yield('data-template')" data-style="light">

<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="description"
        content="شطُّور - منصة تعليمية تفاعلية للطلاب والمعلمين" />
    <meta name="author" content="عبدالله محمد (بازوكا)، مطوّر ومؤسس منصة شطور" />
    <meta name="keywords" content="منصة شطور, شطور, منصة تعليمية, أدوات للمدرسين, إدارة الطلاب, متابعة الطلاب, التعليم أونلاين, حصص أونلاين, اختبارات أونلاين, واجبات أونلاين, حضور وغياب, تقارير الطلاب, تواصل مع أولياء الأمور, دروس خصوصية, مجموعات دراسية, منصة للمدرسين, منصة للطلاب, إدارة المجموعات, منصة تعليم مصرية, منصة تعليم خاصة, نظام تعليمي, تعليم ذكي, تعليم عن بعد, منصة تعليمية عربية, تطوير التعليم, إدارة السنتر, منصة للمدرس, منصة للطالب, إدارة الفصل, تعليم تفاعلي, أدوات تعليمية, منصات تعليم إلكتروني,
        Shattor, Shattor Platform, education platform, teacher tools, student management, online learning, online classes, online exams, online assignments, attendance system, absence tracking, student reports, parent communication, private tutoring, study groups, teacher platform, student platform, class management, Egyptian education platform, private education, smart education, distance learning, Arabic education platform, e-learning platform, interactive education, tutoring platform, school management system" />
    <!-- Title -->
    <title>@yield("title")</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/brand/navbar.png') }}" />
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@100..900&display=swap" rel="stylesheet">
    <!-- Vendor Fonts -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/remixicon/remixicon.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/flag-icons.css') }}" />
    <!-- Menu waves for no-customizer fix -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/node-waves/node-waves.css') }}" />
    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/core.css') }}" class="template-customizer-core-css" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/theme-default.css') }}" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />
    @yield('head')
    <style>
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Hide arrows in Firefox */
        input[type="number"] {
            -moz-appearance: textfield;
        }

        /* Hide arrows in other browsers */
        input[type="number"] {
            appearance: textfield;
        }

        textarea {
            resize: none;
        }
    </style>
</head>

<body>
    @yield('body')

    @yield('scripts')
</body>

</html>
