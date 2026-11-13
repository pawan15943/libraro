{{--
    Form + list for complaints and suggestions (same table shape).
    Needs: $action (form route), $items, $noun ('complaint' / 'suggestion'),
           $titleHint, $descHint, $icon.
    Status set by the library: 0 waiting, 1 resolved, 2 rejected, 3 needs more info.
--}}
@php
    $status = [
        0 => ['Waiting', 'warn'],
        1 => ['Resolved', 'ok'],
        2 => ['Not accepted', 'bad'],
        3 => ['Needs more info', 'acc'],
    ];
@endphp

<div class="lr-grid is-main">
    <div class="lr-stack">
        <form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="lr-card lr-form" data-lr-form novalidate>
            @csrf
            <div class="lr-field">
                <label for="tk-title">Title</label>
                <input id="tk-title" type="text" name="title" class="lr-input @error('title') is-bad @enderror" maxlength="255" required
                    value="{{ old('title') }}" placeholder="{{ $titleHint }}">
                @error('title')<span class="lr-err">{{ $message }}</span>@enderror
            </div>

            <div class="lr-field">
                <label for="tk-desc">Details</label>
                <textarea id="tk-desc" name="description" class="lr-textarea @error('description') is-bad @enderror" required
                    placeholder="{{ $descHint }}">{{ old('description') }}</textarea>
                @error('description')<span class="lr-err">{{ $message }}</span>@enderror
            </div>

            <div class="lr-field">
                <span class="lr-label">Photo <i>(optional)</i></span>
                <label class="lr-file">
                    <input type="file" name="attachment" accept="image/png,image/jpeg,image/webp">
                    <span class="lr-file-ic"><x-lb-icon name="camera" /></span>
                    <span><b>Add a photo</b><small>JPG, PNG or WEBP, up to 200 KB</small></span>
                </label>
                @error('attachment')<span class="lr-err">{{ $message }}</span>@enderror
            </div>

            <button type="submit" class="lr-btn is-block"><x-lb-icon name="send" /> Send {{ $noun }}</button>
        </form>
    </div>

    <div class="lr-stack">
        <div class="lr-card">
            <div class="lr-card-title">
                <h2><x-lb-icon :name="$icon" /> My {{ $noun }}s</h2>
                @if ($items->isNotEmpty())<span class="lr-chip mut no-dot">{{ $items->count() }}</span>@endif
            </div>
            @if ($items->isEmpty())
                <div class="lr-empty">
                    <div class="lr-empty-ic"><x-lb-icon name="inbox" /></div>
                    <b>Nothing sent yet</b>
                    <p>Your {{ $noun }}s and the library's reply will show here.</p>
                </div>
            @else
                <div class="lr-rows">
                    @foreach ($items as $it)
                        @php [$label, $tone] = $status[(int) $it->status] ?? $status[0]; @endphp
                        <div class="lr-row" style="align-items:flex-start">
                            <div class="lr-row-main">
                                <b>{{ $it->title }}</b>
                                @if ($it->description)<p>{{ $it->description }}</p>@endif
                                <p style="font-size:12.5px;margin-top:2px">
                                    {{ $it->created_at?->format('j M Y') }}
                                    @if ($it->attachment) · <a href="{{ asset($it->attachment) }}" target="_blank" rel="noopener" style="color:var(--lp-acc-ink);font-weight:600">See photo</a>@endif
                                </p>
                                @if (!empty($it->response))
                                    <div class="lr-row-reply"><b>Library:</b> {{ $it->response }}</div>
                                @endif
                            </div>
                            <span class="lr-chip {{ $tone }}">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
