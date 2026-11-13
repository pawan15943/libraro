@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('content')
<x-ui.flash />

<form action="{{ route('videos.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <x-ui.section label="Add a video">
        <x-ui.card>
            <div class="ui-fields md:grid-cols-3">
                <x-ui.input name="video_titel" label="Title" placeholder="e.g. How to add a learner" required />
                <x-ui.input type="url" name="youtube_link" label="YouTube link" icon="fa-brands fa-youtube" placeholder="https://youtu.be/…" />
                <x-ui.input type="file" name="video" label="Video file" accept="video/mp4,video/quicktime,video/x-msvideo,video/x-ms-wmv" hint="MP4, MOV, AVI or WMV, up to 50 MB" />
            </div>
            <x-slot:footer>
                <x-ui.button type="submit" icon="fa-solid fa-upload">Save video</x-ui.button>
            </x-slot:footer>
        </x-ui.card>
    </x-ui.section>
</form>

<x-ui.section :label="'All videos · '.count($videos)">
    @if(count($videos))
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($videos as $video)
                <x-ui.card flush class="!mb-0 overflow-hidden">
                    @if($video->video)
                        <video class="block aspect-video w-full bg-black" controls preload="metadata">
                            <source src="{{ asset('public/uploade/' . $video->video) }}" type="video/mp4">
                        </video>
                    @else
                        <div class="grid aspect-video place-items-center bg-fill-4 text-3xl text-label-4"><i class="fa-regular fa-circle-play"></i></div>
                    @endif
                    <div class="flex items-center justify-between gap-3 p-4">
                        <div class="ui-headline min-w-0 truncate">{{ $video->video_titel ?? $video->title ?? 'Untitled video' }}</div>
                        @if($video->youtube_link)
                            <x-ui.button size="sm" variant="tinted" icon="fa-brands fa-youtube" :href="$video->youtube_link" target="_blank" rel="noopener">YouTube</x-ui.button>
                        @endif
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    @else
        <x-ui.card><x-ui.empty icon="fa-regular fa-circle-play" title="No videos yet" text="Help videos you add show up here." /></x-ui.card>
    @endif
</x-ui.section>
@endsection
