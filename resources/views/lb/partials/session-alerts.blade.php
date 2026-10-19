{{-- Server messages, same keys the old auth views showed. --}}
@if(session('success'))
    <div class="lb-alert lb-alert--success" role="status"><x-lb-icon name="circle-check" /><span>{{ session('success') }}</span></div>
@endif
@if(session('status'))
    <div class="lb-alert lb-alert--success" role="status"><x-lb-icon name="circle-check" /><span>{{ session('status') }}</span></div>
@endif
@if(session('message'))
    <div class="lb-alert lb-alert--info" role="status"><x-lb-icon name="info" /><span>{{ session('message') }}</span></div>
@endif
@if(session('info'))
    <div class="lb-alert lb-alert--info" role="status"><x-lb-icon name="info" /><span>{{ session('info') }}</span></div>
@endif
@if(session('session_expired'))
    <div class="lb-alert lb-alert--warning" role="status"><x-lb-icon name="clock-3" /><span>{{ session('session_expired') }}</span></div>
@endif
@if(session('error'))
    <div class="lb-alert lb-alert--danger" role="alert"><x-lb-icon name="circle-alert" /><span>{{ session('error') }}</span></div>
@endif
@if($errors->has('error'))
    <div class="lb-alert lb-alert--danger" role="alert"><x-lb-icon name="circle-alert" /><span>{{ $errors->first('error') }}</span></div>
@endif
