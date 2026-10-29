{{-- Shared time picker: any <input data-lb-time> becomes a friendly time field --}}
<link rel="stylesheet" href="{{ asset('public/css/lb-timepicker.css') }}?v={{ @filemtime(public_path('css/lb-timepicker.css')) }}">
<script src="{{ asset('public/js/lb-timepicker.js') }}?v={{ @filemtime(public_path('js/lb-timepicker.js')) }}" defer></script>
