@php
    $axUser = Auth::guard('web')->user();
    $axName = $axUser->name ?? 'Administrator';
    $axInitials = collect(preg_split('/\s+/', trim($axName)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
@endphp
<aside class="ax-sidebar outline-none" id="axSidebar" tabindex="-1" aria-label="Admin navigation">
    <a href="{{ route('home') }}" class="ax-brand">
        <span class="ax-brand-mark" aria-hidden="true"><i class="fa-solid fa-book-open-reader"></i></span>
        <span class="min-w-0">
            <span class="ax-brand-name">Libraro</span>
            <span class="ax-brand-sub">Admin Console</span>
        </span>
    </a>

    <div class="ax-search">
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        <input type="search" id="axNavSearch" placeholder="Search menu" autocomplete="off" spellcheck="false" aria-label="Search menu" aria-keyshortcuts="Meta+K Control+K">
        <kbd class="ax-kbd" aria-hidden="true">⌘K</kbd>
    </div>

    <nav class="ax-nav" id="axNav">
        @foreach(\App\Support\AdminNav::visible() as $gi => $section)
            @if(! $section['title'])
                {{-- single links (Dashboard) --}}
                <ul class="ax-top">
                    @foreach($section['items'] as $item)
                        @php $on = \App\Support\AdminNav::isActive($item); @endphp
                        <li class="ax-item">
                            <a href="{{ route($item['route']) }}" class="ax-link {{ $on ? 'is-active' : '' }}" @if($on) aria-current="page" @endif>
                                <span class="ax-ico ax-tint-{{ $item['tint'] }}" aria-hidden="true"><i class="{{ $item['icon'] }}"></i></span>
                                <span class="ax-label">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                @php
                    $groupId = 'axg-'.\Illuminate\Support\Str::slug($section['title']);
                    $groupOn = collect($section['items'])->contains(fn ($i) => \App\Support\AdminNav::isActive($i));
                @endphp
                <div class="ax-group {{ $groupOn ? 'is-open has-active' : '' }}" data-group="{{ $groupId }}">
                    <button type="button" class="ax-group-btn" aria-expanded="{{ $groupOn ? 'true' : 'false' }}" aria-controls="{{ $groupId }}">
                        <span class="ax-ico ax-tint-{{ $section['tint'] ?? 'gray' }}" aria-hidden="true"><i class="{{ $section['icon'] ?? 'fa-solid fa-folder' }}"></i></span>
                        <span class="ax-label">{{ $section['title'] }}</span>
                        <span class="ax-count" aria-hidden="true">{{ count($section['items']) }}</span>
                        <i class="fa-solid fa-chevron-right ax-caret" aria-hidden="true"></i>
                    </button>
                    <div class="ax-sub-wrap" id="{{ $groupId }}">
                        <ul class="ax-sub">
                            @foreach($section['items'] as $item)
                                @php $on = \App\Support\AdminNav::isActive($item); @endphp
                                <li class="ax-item">
                                    <a href="{{ route($item['route']) }}" class="ax-sub-link {{ $on ? 'is-active' : '' }}" @if($on) aria-current="page" @endif>
                                        <i class="{{ $item['icon'] }} ax-sub-ico" aria-hidden="true"></i>
                                        <span class="ax-label">{{ $item['label'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        @endforeach
        <div class="ax-nav-empty" id="axNavEmpty" role="status">No menu matches</div>
    </nav>

    <div class="ax-sidebar-foot">
        <span class="ui-avatar size-8 text-xs" aria-hidden="true">{{ $axInitials ?: 'A' }}</span>
        <div class="min-w-0 flex-1">
            <div class="ax-user-name">{{ $axName }}</div>
            <div class="ax-user-mail">{{ $axUser->email ?? '' }}</div>
        </div>
        <button type="submit" form="axLogout" class="ax-icon-btn" title="Sign out" aria-label="Sign out">
            <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
        </button>
    </div>
</aside>
<div class="ax-scrim" id="axScrim" aria-hidden="true"></div>
