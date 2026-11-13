@extends('layouts.admin')

@section('title', $notificat ? 'Edit Notification' : 'Create Notification')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-solid fa-arrow-left" :href="route('admin.notifications.index')">Notifications</x-ui.button>
@endsection

@push('styles')
<style>
    /* live banner preview: the navy strip libraries see under their header */
    .nt-preview { background: var(--tint); color: #fff; border-radius: var(--r-lg); padding: 16px; box-shadow: var(--sh-2); transition: opacity var(--dur-3) var(--ease); }
    .nt-preview__type { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: var(--r-pill); font: 600 11px/1.3 var(--font-text); color: #fff; background: var(--red); }
    .nt-preview .ui-badge--green { background: rgba(52, 199, 89, .9); color: #fff; }
    .nt-preview .ui-badge--red { background: rgba(255, 59, 48, .9); color: #fff; }
    .nt-preview__title { font: 600 15px/1.35 var(--font-display); margin: 10px 0 4px; overflow-wrap: anywhere; }
    .nt-preview__desc { font-size: 13px; line-height: 1.45; color: rgba(255, 255, 255, .72); overflow-wrap: anywhere; white-space: pre-line; }
    .nt-preview__cta { display: inline-flex; align-items: center; gap: 6px; margin-top: 12px; padding: 5px 12px; border-radius: var(--r-pill); background: #fff; color: var(--tint); font: 600 12px/1.3 var(--font-text); }
</style>
@endpush

@section('content')
@php
    $data = $notificat ? json_decode($notificat->data, true) : [];
    $isEdit = !empty($notificat);
    $actionRoute = $isEdit ? route('notifications.update') : route('notifications.send');
    $expired = $notificat && $notificat->end_date < date('Y-m-d');
    $formStartDate = $expired ? date('Y-m-d') : ($notificat->start_date ?? date('Y-m-d'));
    $formEndDate = $expired ? date('Y-m-d', strtotime('+7 days')) : ($notificat->end_date ?? date('Y-m-d', strtotime('+7 days')));
    $inactive = isset($notificat->status) && (string) $notificat->status === '0';
@endphp

<x-ui.flash />

<div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_340px] lg:items-start">
    <form action="{{ $actionRoute }}" method="POST" id="notificationForm" class="min-w-0">
        @csrf
        @if($isEdit)
            @method('PUT')
            <input type="hidden" name="batch_id" value="{{ $notificat->batch_id }}">
        @endif

        <x-ui.section label="Audience">
            <x-ui.card>
                <div class="grid gap-x-4 gap-y-1 md:grid-cols-2">
                    <x-ui.select name="guard" id="guard" label="Send to" required placeholder="Choose who sees it" hint="Who will receive and see this notification."
                        :options="['library' => 'Library owners (library panel)', 'web' => 'Website & admin', 'learner' => 'Learners']" :value="$notificat->guard ?? null" />
                    <x-ui.select name="notification_type" id="notification_type" label="Category" required placeholder="Select a category"
                        :options="['important' => 'Important announcement', 'offers' => 'Offers & discounts', 'maintenance' => 'System maintenance', 'wishes' => 'Greetings & wishes']" :value="$data['notification_type'] ?? null" />
                </div>
            </x-ui.card>
        </x-ui.section>

        <x-ui.section label="Message">
            <x-ui.card>
                <x-ui.input name="title" id="title" label="Title" required :value="$data['title'] ?? null" placeholder="e.g. Scheduled system upgrade on Sunday" />
                <x-ui.textarea name="description" id="description" label="Description" required rows="4" :value="$data['description'] ?? null" placeholder="Clear, short details for the reader…" />
                <div class="grid gap-x-4 gap-y-1 md:grid-cols-2">
                    <x-ui.input type="url" name="link" id="link" label="Button link" icon="fa-solid fa-link" :value="$data['link'] ?? null" placeholder="https://libraro.com/…" hint="Optional. Adds a Learn more button." />
                    <x-ui.input type="url" name="image" id="image" label="Banner or icon URL" icon="fa-regular fa-image" :value="$data['image'] ?? null" placeholder="https://…" hint="Optional." />
                </div>
            </x-ui.card>
        </x-ui.section>

        <x-ui.section label="Schedule">
            <x-ui.card>
                <div class="grid gap-x-4 gap-y-1 sm:grid-cols-2 md:grid-cols-3">
                    <x-ui.input type="date" name="start_date" id="start_date" label="Show from" required :value="$formStartDate" />
                    <x-ui.input type="date" name="end_date" id="end_date" label="Expires on" required :value="$formEndDate" />
                    <div class="sm:col-span-2 md:col-span-1">
                        <x-ui.select name="status" id="status" label="Status" required :options="['1' => 'Active', '0' => 'Inactive']" :value="(string) ($notificat->status ?? 1)" />
                    </div>
                </div>
            </x-ui.card>
        </x-ui.section>

        <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <x-ui.button variant="gray" :href="route('admin.notifications.index')">Cancel</x-ui.button>
            <x-ui.button type="submit" icon="fa-solid fa-paper-plane">{{ $isEdit ? 'Update notification' : 'Send notification' }}</x-ui.button>
        </div>
    </form>

    {{-- live preview --}}
    <aside class="lg:sticky lg:top-4">
        <div class="ui-section-label">Live preview</div>
        <x-ui.card>
            <p class="ui-subhead mb-3">How the announcement looks right below the header.</p>
            <div id="previewBox" class="nt-preview">
                <div class="flex items-center justify-between gap-2">
                    <span id="previewTypeBadge" class="nt-preview__type">Important</span>
                    <span id="previewStatusBadge" class="ui-badge {{ $inactive ? 'ui-badge--red' : 'ui-badge--green' }}">{{ $inactive ? 'Inactive' : 'Active' }}</span>
                </div>
                <div id="previewTitle" class="nt-preview__title">{{ $data['title'] ?? 'Notification Title' }}</div>
                <div id="previewDesc" class="nt-preview__desc">{{ $data['description'] ?? 'Your notification description will appear here in real-time as you type.' }}</div>
                <div id="previewLinkWrap" style="{{ !empty($data['link']) ? '' : 'display:none;' }}">
                    <span class="nt-preview__cta">Learn more <i class="fa-solid fa-arrow-right"></i></span>
                </div>
            </div>

            <hr class="ui-divider">
            <div class="ui-headline mb-2 text-[13px]"><i class="fa-solid fa-circle-info mr-1 text-tint"></i> Where it shows</div>
            <ul class="ui-footnote m-0 flex list-none flex-col gap-1.5 p-0">
                <li><strong class="text-label-2">Library owners:</strong> under the dashboard header, in the bell menu and in the notification centre.</li>
                <li><strong class="text-label-2">Website / admin:</strong> on the public website, right under the navigation bar.</li>
            </ul>
        </x-ui.card>
    </aside>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const titleInput = document.getElementById('title');
        const descInput = document.getElementById('description');
        const linkInput = document.getElementById('link');
        const typeSelect = document.getElementById('notification_type');
        const statusSelect = document.getElementById('status');

        const previewTitle = document.getElementById('previewTitle');
        const previewDesc = document.getElementById('previewDesc');
        const previewTypeBadge = document.getElementById('previewTypeBadge');
        const previewStatusBadge = document.getElementById('previewStatusBadge');
        const previewLinkWrap = document.getElementById('previewLinkWrap');
        const previewBox = document.getElementById('previewBox');

        function updatePreview() {
            if (titleInput && previewTitle) {
                previewTitle.textContent = titleInput.value.trim() || 'Notification Title';
            }
            if (descInput && previewDesc) {
                previewDesc.textContent = descInput.value.trim() || 'Your notification description will appear here in real-time as you type.';
            }
            if (linkInput && previewLinkWrap) {
                previewLinkWrap.style.display = linkInput.value.trim() ? 'block' : 'none';
            }
            if (typeSelect && previewTypeBadge) {
                const val = typeSelect.value;
                previewTypeBadge.textContent = val ? val.charAt(0).toUpperCase() + val.slice(1) : 'Important';
                if (val === 'offers') {
                    previewTypeBadge.style.background = 'var(--teal)';
                } else if (val === 'wishes') {
                    previewTypeBadge.style.background = 'var(--green)';
                } else if (val === 'maintenance') {
                    previewTypeBadge.style.background = 'var(--orange)';
                } else {
                    previewTypeBadge.style.background = 'var(--red)';
                }
            }
            if (statusSelect && previewStatusBadge) {
                if (statusSelect.value === '0') {
                    previewStatusBadge.textContent = 'Inactive';
                    previewStatusBadge.className = 'ui-badge ui-badge--red';
                    if (previewBox) previewBox.style.opacity = '0.65';
                } else {
                    previewStatusBadge.textContent = 'Active';
                    previewStatusBadge.className = 'ui-badge ui-badge--green';
                    if (previewBox) previewBox.style.opacity = '1';
                }
            }
        }

        if (titleInput) titleInput.addEventListener('input', updatePreview);
        if (descInput) descInput.addEventListener('input', updatePreview);
        if (linkInput) linkInput.addEventListener('input', updatePreview);
        if (typeSelect) typeSelect.addEventListener('change', updatePreview);
        if (statusSelect) statusSelect.addEventListener('change', updatePreview);
        updatePreview();
    });
</script>
@endsection
