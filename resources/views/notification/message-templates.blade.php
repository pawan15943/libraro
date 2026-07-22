@extends('layouts.library')

@section('content')

<style>
    .msg-tpl-tabs .nav-link {
        color: #495057;
        font-weight: 500;
        border: none;
        border-radius: 8px 8px 0 0;
        padding: 0.75rem 1.5rem;
    }

    .msg-tpl-tabs .nav-link.active {
        background-color: white;
        border-bottom: 3px solid #667eea;
        color: #667eea;
    }

    .msg-tpl-card {
        border: 1px solid #eee;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        background: #fff;
    }

    .msg-tpl-vars {
        font-size: 0.85rem;
        color: #dc3545;
        margin-top: 8px;
    }
</style>

<div class="container-fluid mt-4">
    <h4 class="mb-3">Message Templates</h4>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <ul class="nav nav-tabs msg-tpl-tabs" id="msgTplTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="waba-tab" data-bs-toggle="tab" data-bs-target="#waba-pane" type="button" role="tab">WhatsApp</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="text-tab" data-bs-toggle="tab" data-bs-target="#text-pane" type="button" role="tab">Text</button>
        </li>
    </ul>

    <div class="tab-content bg-white border border-top-0 p-4 rounded-bottom">

        {{-- WhatsApp tab --}}
        <div class="tab-pane fade show active" id="waba-pane" role="tabpanel">
            <p class="text-danger">Note: Max message length is 700 characters.</p>

            <form method="POST" action="{{ route('message.templates.update') }}">
                @csrf
                @foreach ($wabaMessageTemplates as $index => $template)
                    <div class="msg-tpl-card">
                        <label class="form-label text-muted">{{ $template->template_name }}</label>
                        <input type="hidden" name="templates[{{ $index }}][operation_id]" value="{{ $template->operation_id }}">
                        <input type="hidden" name="templates[{{ $index }}][type]" value="{{ $template->type }}">
                        <textarea class="form-control" name="templates[{{ $index }}][template_message]" rows="4" maxlength="700">{{ old("templates.$index.template_message", $template->template_message) }}</textarea>
                        @if ($template->variables_display !== '')
                            <div class="msg-tpl-vars">
                                You can edit this message, but the following variables must remain exactly as provided: {{ $template->variables_display }} Do not modify, remove, rename, or add any other variables.
                            </div>
                        @endif
                    </div>
                @endforeach

                @if ($wabaMessageTemplates->isEmpty())
                    <p class="text-muted">No WhatsApp templates available yet.</p>
                @else
                    <button type="submit" class="btn btn-primary px-5">Save</button>
                @endif
            </form>
        </div>

        {{-- Text tab --}}
        <div class="tab-pane fade" id="text-pane" role="tabpanel">
            <p class="text-danger">Note: Max text message length is 400 characters.</p>

            <form method="POST" action="{{ route('message.templates.update') }}">
                @csrf
                @foreach ($textMessageTemplates as $index => $template)
                    <div class="msg-tpl-card">
                        <label class="form-label text-muted">{{ $template->template_name }}</label>
                        <input type="hidden" name="templates[{{ $index }}][operation_id]" value="{{ $template->operation_id }}">
                        <input type="hidden" name="templates[{{ $index }}][type]" value="{{ $template->type }}">
                        <textarea class="form-control" name="templates[{{ $index }}][template_message]" rows="4" maxlength="400">{{ old("templates.$index.template_message", $template->template_message) }}</textarea>
                        @if ($template->variables_display !== '')
                            <div class="msg-tpl-vars">
                                You can edit this message, but the following variables must remain exactly as provided: {{ $template->variables_display }} Do not modify, remove, rename, or add any other variables.
                            </div>
                        @endif
                    </div>
                @endforeach

                @if ($textMessageTemplates->isEmpty())
                    <p class="text-muted">No text templates available yet.</p>
                @else
                    <button type="submit" class="btn btn-primary px-5">Save</button>
                @endif
            </form>
        </div>

    </div>
</div>

@endsection
