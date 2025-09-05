@extends('layouts.auth.master')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}" />
@endsection

@section('title', pageTitle(trans('admin/fees.securityCode')))

@section('content')
    <p class="mb-0">{{ trans('admin/fees.enterSecurityCode') }}</p>

    <form id="verify-pin-form" action="{{ route('teacher.verify-pin.insert') }}" method="POST">
        @csrf
        <div class="mb-3">
            <div class="auth-input-wrapper d-flex align-items-center justify-content-between numeral-mask-wrapper">
                <input type="tel" class="form-control auth-input text-center numeral-mask h-px-50 mx-sm-1 my-2"
                    maxlength="1" autofocus />
                <input type="tel" class="form-control auth-input text-center numeral-mask h-px-50 mx-sm-1 my-2"
                    maxlength="1" />
                <input type="tel" class="form-control auth-input text-center numeral-mask h-px-50 mx-sm-1 my-2"
                    maxlength="1" />
                <input type="tel" class="form-control auth-input text-center numeral-mask h-px-50 mx-sm-1 my-2"
                    maxlength="1" />
                <input type="tel" class="form-control auth-input text-center numeral-mask h-px-50 mx-sm-1 my-2"
                    maxlength="1" />
                <input type="tel" class="form-control auth-input text-center numeral-mask h-px-50 mx-sm-1 my-2"
                    maxlength="1" />
            </div>
            <input id="pin" type="hidden" name="pin" />
        </div>
        <button type="submit" class="btn btn-primary d-grid w-100 mb-5">{{ trans('main.submit') }}</button>
    </form>
@endsection

@section('page-js')
    @if (session('error'))
        toastr.error("{{ session('error') }}");
    @endif

    <script src="{{ asset('assets/vendor/libs/cleavejs/cleave.js') }}"></script>
    <script src="{{ asset('assets/js/pages-auth.js') }}"></script>
    <script src="{{ asset('assets/js/pages-auth-two-steps.js') }}"></script>

    <script>
        const formId = $("#verify-pin-form");
        const submitButton = formId.find('button[type="submit"]');
        const originalButtonContent = submitButton.html();

        $(formId).on('submit', function(e) {
            e.preventDefault();

            console.log(submitButton)

            submitButton.find('.waves-ripple').remove();
            submitButton.prop('disabled', true);
            submitButton.html(
                `<i class="ri-loader-4-line ri-spin ri-20px me-1"></i> {{ trans('main.processing') }}...`
            );

            const pin = $('.numeral-mask').map(function() {
                return $(this).val();
            }).get().join('');
            $('#pin').val(pin);

            const formData = new FormData(this);

            $.ajax({
                url: $(this).attr('action'),
                type: $(this).attr('method'),
                dataType: "json",
                processData: false,
                contentType: false,
                data: formData,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message)
                        window.location.href = response.redirect;
                        resetButtonState(submitButton, originalButtonContent);
                    } else {
                        toastr.error(response.error || '{{ trans('main.errorMessage') }}');
                        resetButtonState(submitButton, originalButtonContent);
                    }
                },
                error: function(xhr, status, error) {
                    if (xhr.status === 429) {
                        toastr.error(tooManyRequestsMessage);
                    } else if (xhr.responseJSON) {
                        if (xhr.responseJSON.errors) {
                            $.each(xhr.responseJSON.errors, function(key, val) {
                                toastr.error(val);
                            });
                        } else if (xhr.responseJSON.error) {
                            toastr.error(xhr.responseJSON.error);
                        } else {
                            toastr.error('{{ trans('main.errorMessage') }}');
                        }
                    } else {
                        toastr.error('{{ trans('main.errorMessage') }}');
                    }

                    resetButtonState(submitButton, originalButtonContent);
                },
                complete: function() {
                    resetButtonState(submitButton, originalButtonContent);
                }
            });
        });

        function resetButtonState(submitButton, originalButtonContent) {
            setTimeout(function() {
                submitButton.prop('disabled', false);
                submitButton.html(originalButtonContent);
                submitButton.blur();
                submitButton.find('.waves-ripple').remove();
                if (typeof Waves !== 'undefined') {
                    Waves.init();
                    Waves.attach(submitButton[0]);
                }
            }, 1500);
        }
    </script>
@endsection
