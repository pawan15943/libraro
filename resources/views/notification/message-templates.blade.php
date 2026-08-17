@extends('layouts.library')

@section('content')

<style>
    .msg-tpl-container {
        max-width: 1100px;
        margin: 0 auto 3.5rem;
    }

    .msg-tpl-header {
        background: #fff;
        border: 1px solid #e7e9f0;
        border-radius: 14px;
        padding: 22px 26px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        box-shadow: 0 2px 10px rgba(17, 24, 63, .03);
    }

    .msg-tpl-header h4 {
        margin: 0;
        color: #07156f;
        font-size: 1.35rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .msg-tpl-header p {
        margin: 4px 0 0;
        color: #6b7280;
        font-size: .88rem;
    }

    /* Tabs Styling */
    .msg-tabs-wrap {
        display: flex;
        gap: 12px;
        margin-bottom: 20px;
        border-bottom: 2px solid #eef0f6;
        padding-bottom: 4px;
    }

    .msg-tab-btn {
        border: 0;
        background: #f4f6fb;
        color: #555b6d;
        border-radius: 10px 10px 0 0;
        padding: 12px 24px;
        font-weight: 700;
        font-size: .95rem;
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        transition: all .2s ease;
        position: relative;
    }

    .msg-tab-btn:hover {
        background: #eaeffa;
        color: #07156f;
    }

    .msg-tab-btn.active {
        background: #fff;
        color: #07156f;
        box-shadow: 0 -3px 12px rgba(17, 24, 63, .04);
        border: 1px solid #e7e9f0;
        border-bottom-color: #fff;
        margin-bottom: -6px;
        padding-bottom: 16px;
    }

    .msg-tab-btn.active::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: #07156f;
        border-radius: 10px 10px 0 0;
    }

    /* Template Cards */
    .msg-card {
        background: #fff;
        border: 1px solid #e7e9f0;
        border-radius: 14px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 2px 10px rgba(17, 24, 63, .03);
        transition: box-shadow .2s ease, border-color .2s ease;
    }

    .msg-card:hover {
        box-shadow: 0 6px 20px rgba(17, 24, 63, .06);
        border-color: #d2d7e8;
    }

    .msg-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 16px;
        padding-bottom: 14px;
        border-bottom: 1px solid #f1f3f8;
    }

    .msg-card-title-wrap {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .msg-card-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }

    .msg-card-title-wrap h5 {
        margin: 0;
        color: #1a1d2e;
        font-size: 1.05rem;
        font-weight: 700;
    }

    .msg-card-title-wrap small {
        color: #71778e;
        font-size: .82rem;
        display: block;
        margin-top: 2px;
    }

    .msg-card-badge {
        font-size: .75rem;
        font-weight: 700;
        padding: 4px 12px;
        border-radius: 20px;
        background: #f0f2f9;
        color: #4b526d;
        text-transform: uppercase;
        letter-spacing: .4px;
    }

    /* Textarea & Counter */
    .msg-textarea-wrap {
        position: relative;
        margin-bottom: 14px;
    }

    .msg-textarea {
        width: 100%;
        border: 1.5px solid #dfe3ee;
        border-radius: 10px;
        padding: 14px 16px;
        font-size: .95rem;
        line-height: 1.6;
        color: #1f2438;
        background: #fafbfe;
        resize: vertical;
        min-height: 125px;
        transition: all .2s ease;
        font-family: inherit;
    }

    .msg-textarea:focus {
        outline: none;
        background: #fff;
        border-color: #07156f;
        box-shadow: 0 0 0 4px rgba(7, 21, 111, .08);
    }

    .msg-char-counter {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        font-size: .78rem;
        color: #838a99;
        margin-top: 5px;
    }

    .msg-char-counter.warning {
        color: #d97706;
        font-weight: 700;
    }

    .msg-char-counter.danger {
        color: #dc2626;
        font-weight: 700;
    }

    /* Variables Section */
    .msg-vars-panel {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        padding: 14px 16px;
    }

    .msg-vars-heading {
        font-size: .82rem;
        font-weight: 700;
        color: #334155;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .msg-vars-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .msg-var-btn {
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #0f172a;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: .82rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all .15s ease;
        user-select: none;
    }

    .msg-var-btn code {
        font-weight: 700;
        color: #07156f;
        background: #eef2ff;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: .8rem;
    }

    .msg-var-btn .var-desc {
        color: #64748b;
        font-size: .78rem;
    }

    .msg-var-btn:hover {
        border-color: #07156f;
        background: #f0f3ff;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(7, 21, 111, .1);
    }

    .msg-var-btn:active {
        transform: translateY(0);
    }

    /* Toast Notification */
    .var-toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: #07156f;
        color: #fff;
        padding: 12px 20px;
        border-radius: 10px;
        font-size: .88rem;
        font-weight: 600;
        box-shadow: 0 8px 24px rgba(0, 0, 0, .18);
        display: flex;
        align-items: center;
        gap: 8px;
        z-index: 9999;
        transform: translateY(100px);
        opacity: 0;
        transition: all .25s ease;
    }

    .var-toast.show {
        transform: translateY(0);
        opacity: 1;
    }

    .msg-save-bar {
        position: sticky;
        bottom: 20px;
        background: #fff;
        border: 1px solid #e7e9f0;
        border-radius: 12px;
        padding: 14px 22px;
        box-shadow: 0 6px 24px rgba(17, 24, 63, .08);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        z-index: 100;
        margin-top: 24px;
    }

    .msg-save-bar p {
        margin: 0;
        color: #6b7280;
        font-size: .85rem;
    }

    @media (max-width: 768px) {
        .msg-tpl-header { flex-direction: column; align-items: flex-start; }
        .msg-card-header { flex-direction: column; align-items: flex-start; }
        .msg-save-bar { flex-direction: column; align-items: stretch; text-align: center; }
    }
</style>

<div class="msg-tpl-container">
    {{-- Page Header --}}
    <div class="msg-tpl-header">
        <div>
            <h4><i class="fa-solid fa-comments text-primary"></i> Message Templates</h4>
            <p>Customize automated and manual notification templates for WhatsApp & SMS messages.</p>
        </div>
        <div class="d-none d-md-block">
            <span class="badge bg-primary px-3 py-2" style="font-size: .82rem;"><i class="fa-solid fa-sliders me-1"></i> 3 Core Templates</span>
        </div>
    </div>

    {{-- Alerts --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation fs-5"></i>
                <div>
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-circle-check fs-5"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Tabs Navigation --}}
    <div class="msg-tabs-wrap" id="msgTabs" role="tablist">
        <button class="msg-tab-btn active" id="waba-tab" data-bs-toggle="tab" data-bs-target="#waba-pane" type="button" role="tab">
            <i class="fab fa-whatsapp" style="color: #25D366; font-size: 1.15rem;"></i> WhatsApp Templates
        </button>
        <button class="msg-tab-btn" id="text-tab" data-bs-toggle="tab" data-bs-target="#text-pane" type="button" role="tab">
            <i class="fa-solid fa-message" style="color: #0d6efd; font-size: 1.05rem;"></i> Text (SMS) Templates
        </button>
    </div>

    <div class="tab-content">
        {{-- WhatsApp (WABA) Tab Pane --}}
        <div class="tab-pane fade show active" id="waba-pane" role="tabpanel">
            <form method="POST" action="{{ route('message.templates.update') }}" class="msg-form" data-max="700">
                @csrf
                @foreach ($wabaMessageTemplates as $index => $template)
                    <div class="msg-card">
                        <div class="msg-card-header">
                            <div class="msg-card-title-wrap">
                                <div class="msg-card-icon" style="background: {{ $template->bg }}; color: {{ $template->color }};">
                                    <i class="{{ $template->icon }}"></i>
                                </div>
                                <div>
                                    <h5>{{ $template->name }}</h5>
                                    <small>{{ $template->sub_title }}</small>
                                </div>
                            </div>
                            <span class="msg-card-badge"><i class="fab fa-whatsapp me-1" style="color: #25D366;"></i> WhatsApp</span>
                        </div>

                        <input type="hidden" name="templates[{{ $index }}][operation_id]" value="{{ $template->operation_id }}">
                        <input type="hidden" name="templates[{{ $index }}][type]" value="{{ $template->type }}">

                        {{-- Message Textarea --}}
                        <div class="msg-textarea-wrap">
                            <label class="form-label fw-bold text-dark mb-1">Message Content <span>*</span></label>
                            <textarea class="msg-textarea"
                                      id="waba_msg_{{ $index }}"
                                      name="templates[{{ $index }}][template_message]"
                                      rows="5"
                                      maxlength="700"
                                      placeholder="Enter your message template here...">{{ old('templates.' . $index . '.template_message', $template->template_message) }}</textarea>
                            <div class="msg-char-counter">
                                <span class="char-count">0</span> / 700 characters
                            </div>
                        </div>

                        {{-- Variables Badges Section --}}
                        <div class="msg-vars-panel">
                            <div class="msg-vars-heading">
                                <i class="fa-solid fa-code text-primary"></i>
                                <span>Available Dynamic Variables (Click badge to copy & insert):</span>
                            </div>
                            <div class="msg-vars-grid">
                                @foreach ($template->variables as $var)
                                    @php
                                        $desc = $template->descriptions[$var] ?? '';
                                        $varTag = '{{' . $var . '}}';
                                    @endphp
                                    <button type="button"
                                            class="msg-var-btn"
                                            data-target="waba_msg_{{ $index }}"
                                            data-var="{{ $varTag }}"
                                            data-name="{{ $var }}"
                                            title="Click to copy & insert {{ $varTag }}">
                                        <i class="fa-regular fa-copy text-muted"></i>
                                        <code>{{ $varTag }}</code>
                                        @if($desc)
                                            <span class="var-desc">({{ $desc }})</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="msg-save-bar">
                    <p><i class="fa-solid fa-circle-info text-info me-1"></i> Make sure to test your templates before sending bulk reminders.</p>
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold"><i class="fa-solid fa-floppy-disk me-2"></i> Save WhatsApp Templates</button>
                </div>
            </form>
        </div>

        {{-- Text (SMS) Tab Pane --}}
        <div class="tab-pane fade" id="text-pane" role="tabpanel">
            <form method="POST" action="{{ route('message.templates.update') }}" class="msg-form" data-max="400">
                @csrf
                @foreach ($textMessageTemplates as $index => $template)
                    <div class="msg-card">
                        <div class="msg-card-header">
                            <div class="msg-card-title-wrap">
                                <div class="msg-card-icon" style="background: {{ $template->bg }}; color: {{ $template->color }};">
                                    <i class="{{ $template->icon }}"></i>
                                </div>
                                <div>
                                    <h5>{{ $template->name }}</h5>
                                    <small>{{ $template->sub_title }}</small>
                                </div>
                            </div>
                            <span class="msg-card-badge"><i class="fa-solid fa-message me-1" style="color: #0d6efd;"></i> SMS Text</span>
                        </div>

                        <input type="hidden" name="templates[{{ $index }}][operation_id]" value="{{ $template->operation_id }}">
                        <input type="hidden" name="templates[{{ $index }}][type]" value="{{ $template->type }}">

                        {{-- Message Textarea --}}
                        <div class="msg-textarea-wrap">
                            <label class="form-label fw-bold text-dark mb-1">Message Content <span>*</span></label>
                            <textarea class="msg-textarea"
                                      id="text_msg_{{ $index }}"
                                      name="templates[{{ $index }}][template_message]"
                                      rows="4"
                                      maxlength="400"
                                      placeholder="Enter your SMS template here...">{{ old('templates.' . $index . '.template_message', $template->template_message) }}</textarea>
                            <div class="msg-char-counter">
                                <span class="char-count">0</span> / 400 characters
                            </div>
                        </div>

                        {{-- Variables Badges Section --}}
                        <div class="msg-vars-panel">
                            <div class="msg-vars-heading">
                                <i class="fa-solid fa-code text-primary"></i>
                                <span>Available Dynamic Variables (Click badge to copy & insert):</span>
                            </div>
                            <div class="msg-vars-grid">
                                @foreach ($template->variables as $var)
                                    @php
                                        $desc = $template->descriptions[$var] ?? '';
                                        $varTag = '{{' . $var . '}}';
                                    @endphp
                                    <button type="button"
                                            class="msg-var-btn"
                                            data-target="text_msg_{{ $index }}"
                                            data-var="{{ $varTag }}"
                                            data-name="{{ $var }}"
                                            title="Click to copy & insert {{ $varTag }}">
                                        <i class="fa-regular fa-copy text-muted"></i>
                                        <code>{{ $varTag }}</code>
                                        @if($desc)
                                            <span class="var-desc">({{ $desc }})</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="msg-save-bar">
                    <p><i class="fa-solid fa-circle-info text-info me-1"></i> Max text message length is 400 characters.</p>
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold"><i class="fa-solid fa-floppy-disk me-2"></i> Save Text Templates</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Floating Toast for Copy feedback --}}
<div id="varToast" class="var-toast">
    <i class="fa-solid fa-circle-check text-success"></i>
    <span id="varToastText">Variable copied to clipboard!</span>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Character counter update
        function updateCounter(textarea) {
            const wrap = textarea.closest('.msg-textarea-wrap');
            if (!wrap) return;
            const counter = wrap.querySelector('.char-count');
            const counterWrap = wrap.querySelector('.msg-char-counter');
            const max = parseInt(textarea.getAttribute('maxlength')) || 700;
            const len = textarea.value.length;
            if (counter) counter.textContent = len;

            if (counterWrap) {
                counterWrap.classList.remove('warning', 'danger');
                if (len >= max) {
                    counterWrap.classList.add('danger');
                } else if (len >= max * 0.85) {
                    counterWrap.classList.add('warning');
                }
            }
        }

        document.querySelectorAll('.msg-textarea').forEach(function (textarea) {
            updateCounter(textarea);
            textarea.addEventListener('input', function () {
                updateCounter(this);
            });
        });

        // Toast feedback
        let toastTimeout;
        function showToast(message) {
            const toast = document.getElementById('varToast');
            const toastText = document.getElementById('varToastText');
            if (!toast || !toastText) return;
            toastText.textContent = message;
            toast.classList.add('show');
            clearTimeout(toastTimeout);
            toastTimeout = setTimeout(function () {
                toast.classList.remove('show');
            }, 2500);
        }

        // Variable copy and insert into textarea
        document.querySelectorAll('.msg-var-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const varText = this.getAttribute('data-var');
                const targetId = this.getAttribute('data-target');
                const textarea = document.getElementById(targetId);

                // Copy to clipboard
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(varText);
                } else {
                    const temp = document.createElement('textarea');
                    temp.value = varText;
                    document.body.appendChild(temp);
                    temp.select();
                    document.execCommand('copy');
                    document.body.removeChild(temp);
                }

                // Insert into textarea at cursor position
                if (textarea) {
                    const start = textarea.selectionStart;
                    const end = textarea.selectionEnd;
                    const text = textarea.value;
                    const before = text.substring(0, start);
                    const after = text.substring(end, text.length);

                    textarea.value = before + varText + after;
                    textarea.selectionStart = textarea.selectionEnd = start + varText.length;
                    textarea.focus();
                    updateCounter(textarea);
                }

                showToast('Copied ' + varText + ' & inserted into template!');
            });
        });
    });
</script>

@endsection
