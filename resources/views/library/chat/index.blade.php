@extends('layouts.library')
@section('lcx-main')
    <div class="lcx-hello">
        <div class="lcx-hello-ic" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.2A8 8 0 1 1 21 12Z"/><rect x="9" y="10" width="6" height="4.5" rx="1"/><path d="M10.2 10V9a1.8 1.8 0 0 1 3.6 0v1"/></svg>
        </div>
        <h4>Chat with your students</h4>
        <p>Private and end-to-end encrypted. Only you (in this browser) and the student can read these messages. Staff accounts cannot use chat.</p>
        <button type="button" class="lcx-new" data-lcx-new><span aria-hidden="true">+</span> Start a new chat</button>
        <ul class="lcx-points">
            <li>Pick any student from your library</li>
            <li>Messages are scrambled before they leave your browser</li>
            <li>Use "Key backup" inside a chat to read old messages on another device</li>
        </ul>
    </div>
@endsection
@section('content')
@include('library.chat._shell')
@endsection
