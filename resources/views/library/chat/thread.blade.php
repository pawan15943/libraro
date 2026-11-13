@extends('layouts.library')
@section('lcx-main')
    <a href="{{ route('chat.index') }}" class="lcx-back"><span aria-hidden="true">&larr;</span> All chats</a>
    <div class="lc" id="lcRoot"></div>
@endsection
@section('content')
@include('library.chat._shell')
<script src="{{ asset('public/js/lb-chat.js') }}?v={{ @filemtime(public_path('js/lb-chat.js')) }}"></script>
<script>
LibraroChat.mount({
    root: document.getElementById('lcRoot'),
    role: 'library',
    scope: 'library:{{ $libraryId }}',
    otherProp: 'learner',
    otherName: @json($learner->name),
    otherSub: @json('ID ' . $learner->learner_no),
    noPeerText: 'This student has not opened chat yet. You can send once they do.',
    emptyTitle: 'Start the conversation',
    emptyText: @json('Say hello to ' . (strtok($learner->name, ' ') ?: 'your student') . '. They will see your message when they open Chat in their portal.'),
    quick: ['Hello! How can I help you?', 'Your plan ends soon. Would you like to renew?', 'Please visit the library desk when you can.'],
    csrf: @json(csrf_token()),
    urls: {
        key: @json(route('chat.key', $learner->id)),
        messages: @json(route('chat.messages', $learner->id)),
        send: @json(route('chat.send', $learner->id)),
        read: @json(route('chat.read', $learner->id)),
        upload: @json(route('chat.upload', $learner->id)),
        file: @json(url('library/chat/' . $learner->id . '/file'))
    }
});
</script>
@endsection
