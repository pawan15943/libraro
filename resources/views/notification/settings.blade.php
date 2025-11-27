@extends('layouts.library')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />

<style>
    .choices {
        width: 100%;
        margin: 0;
        padding: 0;
        height: auto ! IMPORTANT;
    }

    .choices__inner {
        height: auto !important;
        width: 100%;
        text-align: left ! IMPORTANT;
    }

    .choices__list.choices__list--dropdown.is-active {
        text-align: left;
        height: auto !important;
    }

</style>

@section('content')
@if ($errors->any())
<div class="alert alert-danger">
    <ul style="margin:0;padding-left:18px">
        @foreach ($errors->all() as $e)
        <li>{{ $e }}</li>
        @endforeach
    </ul>
</div>
@endif



<form action="{{ route('notification.settings.save') }}" method="POST" id="notification-settings-form">
    @csrf
    <div class="row">
        <div class="mb-4 col-lg-6">
            <label>Select Branche<span class="text-danger">*</span></label>

            <select name="branch_ids" id="my-select" class="form-select" >

                @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ $selectedBranchId == $b->id ? 'selected' : '' }}>
                    {{ $b->name }}
                </option>
                @endforeach
            </select>
        </div>
    </div>


    <div class="row">
        <div class="col-lg-12">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 20%">Operation</th>
                            
                             @if(wabaNotificationActive())
                            <th style="width: 40%">WABA (WhatsApp)</th>
                            @endif
                             @if(textNotificationActive())
                            <th style="width: 40%">Text (SMS)</th>
                            @endif
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($operations as $opName => $templateList)
                       

                        <tr>
                            <td class="fw-bold">{{ $opName }}</td>
                              <!-- WABA SECTION -->
                             @if(wabaNotificationActive())
                            <td>
                                <div class="form-check form-switch mb-1">
                                    <input type="checkbox" class="form-check-input waba-toggle" data-op="{{ $opName }}"  {{ !empty(array_intersect(array_column($templateList->where('type','waba')->toArray(), 'template_id'), $oldWaba ?? [])) ? 'checked' : '' }}>
                                    <label>Enable</label>
                                </div>

                                <select class="form-control waba-select" name="settings[{{ $opName }}][waba_template_id]" id="waba_select_{{ $opName }}" {{ empty(array_intersect(array_column($templateList->where('type','waba')->toArray(), 'template_id'), $oldWaba ?? [])) ? 'disabled' : '' }}>
                                    <option value="">Select WABA Template</option>
                                    @foreach($templateList as $t)
                                    @if($t->type == 'waba')
                                    <option value="{{ $t->template_id }}" data-message="{{ $t->template_message }}" {{ in_array($t->template_id, $oldWaba ?? []) ? 'selected' : '' }}>
                                        {{ $t->template_name }}
                                    </option>
                                    @endif
                                    @endforeach
                                </select>

                                <textarea class="form-control mt-2 mb-2 waba-message-area" id="waba_msg_{{ $opName }}" rows="4" {{ empty(array_intersect(array_column($templateList->where('type','waba')->toArray(), 'template_id'), $oldWaba ?? [])) ? 'disabled' : '' }}>
                                    @foreach($templateList as $t)
                                        @if($t->type == 'waba' && in_array($t->template_id, $oldWaba ?? []))
                                            {{ $t->template_message }}
                                        @endif
                                    @endforeach
                                </textarea>

                                @php
                                $hasReminderTemplate = $templateList->contains(function ($item) {
                                return in_array($item->template_code, ['expired-reminder-waba','extended-reminder-waba']);
                                });
                                @endphp
                                @if($hasReminderTemplate)
                                <select name="message_time[{{ $opName }}][]" class="form-control my-select" multiple>
                                    <option value="">Select Message Time</option>
                                    <option value="1_before">1 day before</option>
                                    <option value="2_before">2 days before</option>
                                    <option value="3_before">3 days before</option>
                                    <option value="4_before">4 days before</option>
                                    <option value="5_before">5 days before</option>
                                    <option value="1_after">1 day after</option>
                                    <option value="2_after">2 days after</option>
                                    <option value="3_after">3 days after</option>
                                    <option value="4_after">4 days after</option>
                                    <option value="5_after">5 days after</option>
                                </select>
                                @endif

                            </td>
                            @endif
                            <!-- TEXT SECTION -->
                            @if(textNotificationActive())
                            <td>
                                <div class="form-check form-switch mb-1">
                                    <input type="checkbox" class="form-check-input text-toggle" data-op="{{ $opName }}" {{ !empty(array_intersect(array_column($templateList->where('type','text')->toArray(), 'template_id'), $oldText ?? [])) ? 'checked' : '' }}>
                                    <label>Enable</label>
                                </div>

                                <select class="form-control text-select" name="settings[{{ $opName }}][text_template_id]" id="text_select_{{ $opName }}" {{ empty(array_intersect(array_column($templateList->where('type','text')->toArray(), 'template_id'), $oldText ?? [])) ? 'disabled' : '' }}>
                                    <option value="">Select Text Template</option>
                                    @foreach($templateList as $t)
                                    @if($t->type == 'text')
                                    <option value="{{ $t->template_id }}" data-message="{{ $t->template_message }}" {{ in_array($t->template_id, $oldText ?? []) ? 'selected' : '' }}>
                                        {{ $t->template_name }}
                                    </option>
                                    @endif
                                    @endforeach
                                </select>

                                <textarea class="form-control mt-2 mb-2 text-message-area" id="text_msg_{{ $opName }}" rows="4" {{ empty(array_intersect(array_column($templateList->where('type','text')->toArray(), 'template_id'), $oldText ?? [])) ? 'disabled' : '' }}>
                                    @foreach($templateList as $t)
                                        @if($t->type == 'text' && in_array($t->template_id, $oldText ?? []))
                                            {{ $t->template_message }}
                                        @endif
                                    @endforeach
                                </textarea>
                                @php
                                $hasReminderTemplate = $templateList->contains(function ($item) {
                                return in_array($item->template_code, ['expired-reminder-sms', 'extended-reminder-waba']);
                                });
                                @endphp 
                                @if($hasReminderTemplate)
                                <select name="message_time[{{ $opName }}][]" class="form-control my-select" multiple>
                                    <option value="">Select Message Time</option>
                                    <option value="1_before">1 day before</option>
                                    <option value="2_before">2 days before</option>
                                    <option value="3_before">3 days before</option>
                                    <option value="4_before">4 days before</option>
                                    <option value="5_before">5 days before</option>
                                    <option value="1_after">1 day after</option>
                                    <option value="2_after">2 days after</option>
                                    <option value="3_after">3 days after</option>
                                    <option value="4_after">4 days after</option>
                                    <option value="5_after">5 days after</option>
                                </select>
                                @endif



                            </td>
                             @endif
                          

                        </tr>

                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row my-4">
        <div class="col-lg-3">
            <button type="submit" class="btn btn-primary button">
                Save
            </button>
        </div>
    </div>
</form>

<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
    $(document).ready(function() {
        $('#branch_id').select2({
            placeholder: "Select branches"
            , allowClear: true
        });
    });

</script>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        // For select
        const selectElement = document.getElementById('my-select');
        const choicesSelect = new Choices(selectElement, {
            removeItemButton: true
        , });
        const selects = document.querySelectorAll('.my-select');

        selects.forEach(function(selectElement) {
            new Choices(selectElement, {
                removeItemButton: true
            , });
        });


        // For input (tags-like input)
        const inputElement = document.getElementById('my-input');
        const choicesInput = new Choices(inputElement, {
            delimiter: ','
            , editItems: true
            , maxItemCount: 5
            , removeItemButton: true
        , });
    });

    document.addEventListener('DOMContentLoaded', function() {

        // TEXT Toggle
        document.querySelectorAll('.text-toggle').forEach(tg => {
            tg.addEventListener('change', function() {
                let op = this.dataset.op;
                let select = document.getElementById('text_select_' + op);
                let textarea = document.getElementById('text_msg_' + op);

                if (this.checked) {
                    select.disabled = false;
                    textarea.disabled = false;
                } else {
                    select.value = "";
                    textarea.value = "";
                    select.disabled = true;
                    textarea.disabled = true;
                }
            });
        });

        // WABA Toggle
        document.querySelectorAll('.waba-toggle').forEach(tg => {
            tg.addEventListener('change', function() {
                let op = this.dataset.op;
                let select = document.getElementById('waba_select_' + op);
                let textarea = document.getElementById('waba_msg_' + op);

                if (this.checked) {
                    select.disabled = false;
                    textarea.disabled = false;
                } else {
                    select.value = "";
                    textarea.value = "";
                    select.disabled = true;
                    textarea.disabled = true;
                }
            });
        });

        // When changing template - update message textarea
        document.querySelectorAll('.text-select').forEach(sel => {
            sel.addEventListener('change', function() {
                let message = this.selectedOptions[0].getAttribute('data-message') || "";
                let op = this.id.replace("text_select_", "");
                document.getElementById('text_msg_' + op).value = message;
            });
        });

        document.querySelectorAll('.waba-select').forEach(sel => {
            sel.addEventListener('change', function() {
                let message = this.selectedOptions[0].getAttribute('data-message') || "";
                let op = this.id.replace("waba_select_", "");
                document.getElementById('waba_msg_' + op).value = message;
            });
        });

    });

</script>
@endsection
