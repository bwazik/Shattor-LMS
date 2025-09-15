<div class="card mb-6">
    <h5 class="card-header">{{ trans('account.authorizedDevices') }}</h5>
    <div class="card-body">
        <p class="card-text">{{ trans('account.authorizedDevicesDescription', ['count' => 2]) }}</p>
    </div>
    <div class="table-responsive">
        <table class="table table-border-bottom-0">
            <thead>
                <tr>
                    <th class="text-truncate">{{ trans('account.operatingSystem') }}</th>
                    <th class="text-truncate">{{ trans('account.device') }}</th>
                    <th class="text-truncate">{{ trans('account.location') }}</th>
                    <th class="text-truncate">{{ trans('account.lastActivity') }}</th>
                    @if (isAdmin())
                        <th class="text-truncate">{{ trans('main.actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($devices as $device)
                    <tr>
                        <td class="text-truncate">
                            <i class="ri {{ $device['icon'] }} ri-24px text-success me-4"></i>
                            <span class="text-heading">{{ $device['platform'] }}</span>
                        </td>
                        <td class="text-truncate">{{ $device['device'] }}</td>
                        <td class="text-truncate">{{ $device['location'] }}</td>
                        <td class="text-truncate">
                            <h6
                                class="mb-0 align-items-center d-flex w-px-100 {{ $device['last_activity'] == trans('account.online') ? 'text-success' : 'text-warning' }}">
                                <i class="icon-base ri ri-circle-fill icon-10px me-1"
                                    style="font-size:8px;"></i>{{ $device['last_activity'] }}
                            </h6>
                        </td>
                        @if (isAdmin())
                            <td class="text-truncate">
                                <button
                                    class="btn btn-sm btn-icon btn-text-danger rounded-pill text-body waves-effect waves-light me-1"
                                    id="delete-button" data-id="{{ $device['id'] }}"
                                    data-platform="{{ $device['platform'] }}"
                                    data-bs-target="#delete-modal" data-bs-toggle="modal" data-bs-dismiss="modal">
                                    <i class="ri-delete-bin-7-line ri-20px text-danger"></i>
                                </button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ isAdmin() ? 5 : 4 }}" class="text-center">
                            {{ trans('main.datatable.empty_table') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
