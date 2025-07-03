<div class="card-body">
    <div class="d-flex align-items-start align-items-sm-center gap-6">
        <a href="{{ $qrcode }}" download="shattor-qr-code.png">
            <img src="{{ $qrcode }}" alt="QR Code" class="d-block w-px-100 h-px-100 rounded-4" id="QRCode" />
        </a>
        <img src="{{ asset(Auth::user()->profile_pic ? 'storage/profiles/' . $guard . '/' . Auth::user()->profile_pic : 'assets/img/avatars/default.jpg') }}"
            alt="profile-picture" class="d-block w-px-100 h-px-100 rounded-4" id="uploadedAvatar" />
        <div class="button-wrapper">
            <form id="update-profile-form" action="{{ $action }}"
                method="POST" enctype="multipart/form-data">
                @csrf
                <label for="upload" class="btn btn-primary me-3 mb-4" tabindex="0">
                    <span class="d-none d-sm-block">{{ trans('account.uploadPhoto') }}</span>
                    <i class="ri-upload-2-line d-block d-sm-none"></i>
                    <input type="file" id="upload" name="profile" class="account-file-input" hidden
                        accept="image/png, image/jpeg, image/jpg" />
                </label>
                <button type="submit" class="btn btn-primary me-2 mb-4">
                    <i class="ri-file-check-line d-block d-sm-none"></i>
                    <span class="d-none d-sm-block">{{ trans('account.save') }}</span>
                </button>
                <button type="button" class="btn btn-outline-danger account-image-reset mb-4">
                    <i class="ri-refresh-line d-block d-sm-none"></i>
                    <span class="d-none d-sm-block">{{ trans('main.cancel') }}</span>
                </button>
            </form>
            <div>{{ trans('account.allowedFileTypes') }}</div>
        </div>
    </div>
</div>
