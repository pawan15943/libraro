@extends('layouts.library')

@section('content')
<link rel="stylesheet" href="{{ asset('public/css/lib-admin.css') }}?v={{ time() }}">

@php
    $userPermissionIds = $user->permissions->pluck('id');
@endphp

<div class="lib-admin">
    <form method="POST" action="{{ route('library-users.permissions.update', $user->id) }}" id="permissionsForm">
        @csrf

        <div class="la-toolbar">
            <p class="la-hint">Choose what <strong>{{ $user->name }}</strong> can see and do. Changes apply the next time they open a page.</p>
            <label class="la-check-all la-btn la-btn-ghost" for="checkAllPermissions">
                <input type="checkbox" id="checkAllPermissions" class="form-check-input">
                Select all
            </label>
        </div>

        @foreach($groupedPermissions as $categoryId => $permissions)
            @php
                $categoryName = $categoryId ? (\App\Models\PermissionCategory::where('id', $categoryId)->value('name') ?? 'Other') : 'Other';
            @endphp
            <section class="la-card la-perm-section" data-perm-section>
                <div class="la-perm-section-head">
                    <h5 class="la-perm-section-title">{{ $categoryName }}</h5>
                    <label class="la-check-all">
                        <input type="checkbox" class="form-check-input" data-perm-section-all aria-label="Select all in {{ $categoryName }}">
                        <span>All</span>
                    </label>
                </div>
                <div class="la-perm-grid">
                    @foreach($permissions as $name => $id)
                        <label class="la-perm-item" for="perm_{{ $id }}">
                            <input type="checkbox" name="permissions[]"
                                   value="{{ $id }}"
                                   id="perm_{{ $id }}"
                                   class="form-check-input permission"
                                   {{ $userPermissionIds->contains($id) ? 'checked' : '' }}>
                            <span>{{ ucwords(strtolower($name)) }}</span>
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="la-savebar">
            <span class="la-savebar-text"><strong id="permSelectedCount">0</strong> of <span id="permTotalCount">0</span> permissions selected</span>
            <button type="submit" class="la-btn la-btn-primary" id="permSaveBtn">
                <i class="fa-solid fa-floppy-disk"></i> Save permissions
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('permissionsForm');
        var all = document.getElementById('checkAllPermissions');
        var boxes = Array.prototype.slice.call(form.querySelectorAll('.permission'));
        var sections = Array.prototype.slice.call(form.querySelectorAll('[data-perm-section]'));

        // Keep "Select all" boxes and the counter in step with the individual permissions.
        function sync() {
            var checked = boxes.filter(function (b) { return b.checked; }).length;
            document.getElementById('permSelectedCount').textContent = checked;
            document.getElementById('permTotalCount').textContent = boxes.length;
            all.checked = checked === boxes.length && boxes.length > 0;
            all.indeterminate = checked > 0 && checked < boxes.length;

            sections.forEach(function (section) {
                var own = section.querySelectorAll('.permission');
                var on = section.querySelectorAll('.permission:checked').length;
                var toggle = section.querySelector('[data-perm-section-all]');
                toggle.checked = on === own.length && own.length > 0;
                toggle.indeterminate = on > 0 && on < own.length;
            });
        }

        all.addEventListener('change', function () {
            boxes.forEach(function (b) { b.checked = all.checked; });
            sync();
        });

        sections.forEach(function (section) {
            section.querySelector('[data-perm-section-all]').addEventListener('change', function () {
                var value = this.checked;
                section.querySelectorAll('.permission').forEach(function (b) { b.checked = value; });
                sync();
            });
        });

        boxes.forEach(function (b) { b.addEventListener('change', sync); });

        form.addEventListener('submit', function () {
            var button = document.getElementById('permSaveBtn');
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Saving...';
        });

        sync();
    });
</script>
@endsection
