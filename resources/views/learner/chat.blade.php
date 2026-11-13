@extends('layouts.learner')

@section('title', 'Chat with library')

@push('lr-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-chat.css') }}?v={{ @filemtime(public_path('css/lb-chat.css')) }}">
@endpush

@section('content')
<div class="lcx-learner">
    <div class="lcx-learner-intro">
        <span class="lc-av is-lg">{{ mb_strtoupper(mb_substr($lr['library']['name'] ?? 'L', 0, 1)) }}</span>
        <div>
            <h1>Chat with {{ $lr['library']['name'] ?? 'your library' }}</h1>
            <p>Message the library owner directly. Private and end-to-end encrypted.</p>
        </div>
    </div>
    <div class="lcx-learner-body">
        <div class="lc" id="lcRoot" data-lc-initial-lib-key="{{ $libraryKey ? '1' : '0' }}"></div>
        <aside class="lcx-learner-side">
            <div class="lr-card">
                <div class="lr-card-title"><h2>How chat works</h2></div>
                <ul class="lcx-points is-plain">
                    <li>Only your browser and the owner's browser can read messages. Libraro's server only passes scrambled text.</li>
                    <li>The owner replies when they are online. You will see a dot on the chat icon.</li>
                    <li>Use "Key backup" to keep your messages if you change phone.</li>
                </ul>
            </div>
            @if (!empty($lr['library']['phone']))
                <a href="tel:{{ preg_replace('/\s+/', '', $lr['library']['phone']) }}" class="lr-card lcx-call"><x-lb-icon name="phone" /><span><b>Need it now?</b><small>Call {{ $lr['library']['phone'] }}</small></span></a>
            @endif
        </aside>
    </div>
</div>

<noscript><div class="lr-alert warn"><div><b>Please turn on JavaScript to use chat.</b></div></div></noscript>
@endsection

@push('lr-scripts')
<script src="{{ asset('public/js/lb-chat-crypto.js') }}?v={{ @filemtime(public_path('js/lb-chat-crypto.js')) }}"></script>
<script src="{{ asset('public/js/lb-chat.js') }}?v={{ @filemtime(public_path('js/lb-chat.js')) }}"></script>
<script>
LibraroChat.mount({
    root: document.getElementById('lcRoot'),
    role: 'learner',
    scope: 'learner:{{ $learnerId }}',
    otherProp: 'library',
    otherName: @json($lr['library']['name'] ?? 'Library owner'),
    otherSub: 'Library owner',
    emptyTitle: 'Start a chat with your library',
    emptyText: 'Ask about your plan, seat, payment or timing. Pick a quick message or write your own.',
    quick: ['I want to renew my plan', 'I want to change my seat', 'I have a payment question', 'I have a question about timing'],
    noPeerText: 'Your library has not turned on chat yet. Please check again later.',
    csrf: @json(csrf_token()),
    urls: {
        key: @json(route('learner.chat.key')),
        messages: @json(route('learner.chat.messages')),
        send: @json(route('learner.chat.send')),
        read: @json(route('learner.chat.read')),
        upload: @json(route('learner.chat.upload')),
        file: @json(url('learner/chat/file'))
    }
});
</script>
@endpush
