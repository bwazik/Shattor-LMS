<div>
    <label for="itemToAccept" class="form-label">{{ trans('main.accept_warning') }}</label>
    <input type="text" id="itemToAccept" class="form-control" value="{{ trans("main.items") }}: 0" disabled />
</div>
<p class="mt-3">{{ trans('main.confirm_acception') }}</p>
<p class="text-muted">{{ trans('main.cannot_undo_acception') }}</p>
