<div>
    <label for="itemToReject" class="form-label">{{ trans('main.reject_warning') }}</label>
    <input type="text" id="itemToReject" class="form-control" value="{{ trans("main.items") }}: 0" disabled />
</div>
<p class="mt-3">{{ trans('main.confirm_rejection') }}</p>
<p class="text-muted">{{ trans('main.cannot_undo_rejection') }}</p>
