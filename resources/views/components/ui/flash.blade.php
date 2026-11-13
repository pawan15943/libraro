{{-- Session "success" / "error" messages and validation errors as banners. <x-ui.flash /> --}}
@if(session('success'))<x-ui.banner tone="success" dismissible>{{ session('success') }}</x-ui.banner>@endif
@if(session('error'))<x-ui.banner tone="danger" dismissible>{{ session('error') }}</x-ui.banner>@endif
@if($errors->any())
    <x-ui.banner tone="danger" dismissible>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</x-ui.banner>
@endif
