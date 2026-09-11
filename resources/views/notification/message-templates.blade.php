@extends('layouts.library')

@section('content')

<style>
    .custom-notification-module {
        width: 100%;
        max-width: 100%;
        margin: 0 0 2.5rem 0;
        font-family: 'Outfit', sans-serif;
    }

    /* Tabs Styling (Real Tab folder design, no bottom separator) */
    .custom-notification-module .msg-tabs-wrap {
        display: flex;
        gap: 6px;
        margin-bottom: 20px;
        border-bottom: none !important;
        padding-bottom: 0 !important;
    }

    .custom-notification-module .msg-tab-btn {
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        color: #475569;
        border-radius: 8px 8px 0 0;
        padding: 8px 18px;
        font-weight: 500;
        font-size: 0.88rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
        user-select: none;
        box-shadow: none !important;
    }

    .custom-notification-module .msg-tab-btn:hover {
        background: #f1f5f9;
        color: #18225f;
        border-color: #94a3b8;
    }

    .custom-notification-module .msg-tab-btn.active {
        background: #18225f !important;
        color: #ffffff !important;
        border-color: #18225f !important;
        font-weight: 500 !important;
        box-shadow: none !important;
    }

    .custom-notification-module .msg-tab-btn.active i {
        color: #ffffff !important;
    }

    /* Template Cards (No shadow, clean border) */
    .custom-notification-module .msg-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 22px;
        margin-bottom: 20px;
        box-shadow: none !important;
        transition: border-color 0.2s ease;
    }

    .custom-notification-module .msg-card:hover {
        box-shadow: none !important;
        border-color: #cbd5e1;
    }

    .custom-notification-module .msg-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 16px;
        padding-bottom: 14px;
        border-bottom: 1px solid #f1f5f9;
    }

    .custom-notification-module .msg-card-title-wrap {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .custom-notification-module .msg-card-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: grid;
        place-items: center;
        font-size: 1.15rem;
        flex-shrink: 0;
        box-shadow: none !important;
    }

    .custom-notification-module .msg-card-title-wrap h5 {
        margin: 0;
        color: #18225f;
        font-size: 1.05rem;
        font-weight: 600;
        font-family: 'Outfit', sans-serif;
    }

    .custom-notification-module .msg-card-title-wrap small {
        color: #64748b;
        font-size: 0.82rem;
        display: block;
        margin-top: 2px;
        font-weight: 400;
    }

    .custom-notification-module .msg-card-badge {
        font-size: 0.78rem;
        font-weight: 500;
        padding: 4px 12px;
        border-radius: 50rem;
        background: #f1f5f9;
        color: #18225f;
        border: 1px solid #cbd5e1;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    /* Textarea & Counter */
    .custom-notification-module .msg-textarea-wrap {
        position: relative;
        margin-bottom: 14px;
    }

    .custom-notification-module .msg-textarea-wrap label {
        color: #18225f !important;
        font-family: 'Outfit', sans-serif !important;
        font-weight: 500 !important;
        font-size: 0.88rem;
    }

    .custom-notification-module .msg-textarea {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 0.65rem;
        padding: 12px 14px;
        font-size: 0.92rem;
        line-height: 1.55;
        color: #1e293b;
        background: #fafbfe;
        resize: none;
        overflow-y: hidden;
        min-height: 70px;
        box-sizing: border-box;
        transition: border-color 0.15s ease-in-out;
        font-family: inherit;
    }

    .custom-notification-module .msg-textarea:focus {
        outline: none;
        background: #ffffff;
        border-color: #18225f !important;
        box-shadow: 0 0 0 3px rgba(24, 34, 95, 0.08) !important;
    }

    .custom-notification-module .msg-char-counter {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        font-size: 0.78rem;
        color: #64748b;
        margin-top: 5px;
        font-weight: 400;
    }

    .custom-notification-module .msg-char-counter.warning {
        color: #d97706 !important;
        font-weight: 500;
    }

    .custom-notification-module .msg-char-counter.danger {
        color: #dc2626 !important;
        font-weight: 500;
    }

    /* Variables Section */
    .custom-notification-module .msg-vars-panel {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 0.65rem;
        padding: 14px;
    }

    .custom-notification-module .msg-vars-heading {
        font-size: 0.82rem;
        font-weight: 500;
        color: #18225f;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .custom-notification-module .msg-vars-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .custom-notification-module .msg-var-btn {
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #18225f;
        padding: 5px 11px;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 400;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s ease-in-out;
        user-select: none;
        box-shadow: none !important;
    }

    .custom-notification-module .msg-var-btn code {
        font-weight: 500;
        color: #18225f;
        background: #f1f5f9;
        padding: 2px 5px;
        border-radius: 4px;
        font-size: 0.78rem;
    }

    .custom-notification-module .msg-var-btn .var-desc {
        color: #64748b;
        font-size: 0.76rem;
        font-weight: 400;
    }

    .custom-notification-module .msg-var-btn:hover {
        border-color: #18225f;
        background: #f1f5f9;
        box-shadow: none !important;
    }

    .custom-notification-module .msg-var-btn:active {
        background: #e2e8f0;
    }

    /* Save Bar (Aligned right, no shadow, clean border) */
    .custom-notification-module .msg-save-bar {
        position: sticky;
        bottom: 20px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.65rem;
        padding: 12px 20px;
        box-shadow: none !important;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        z-index: 100;
        margin-top: 20px;
    }

    .custom-notification-module .msg-save-bar p {
        margin: 0;
        color: #64748b;
        font-size: 0.84rem;
        font-weight: 400;
    }

    /* Primary Button (Size equal to text, right-aligned, reduced font weight, no shadow) */
    .custom-notification-module .btn-primary.button {
        background: #18225f !important;
        color: #ffffff !important;
        border: 1px solid #18225f !important;
        border-radius: 6px !important;
        font-family: 'Outfit', sans-serif !important;
        font-weight: 500 !important;
        font-size: 0.88rem !important;
        padding: 7px 18px !important;
        width: auto !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        box-shadow: none !important;
        margin-left: auto;
        transition: background-color 0.15s ease-in-out !important;
        white-space: nowrap !important;
    }

    .custom-notification-module .btn-primary.button:hover {
        background: #121947 !important;
        border-color: #121947 !important;
        transform: none !important;
        box-shadow: none !important;
    }

    /* Toast Notification */
    .var-toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: #18225f;
        color: #ffffff;
        padding: 10px 18px;
        border-radius: 8px;
        font-size: 0.84rem;
        font-weight: 500;
        font-family: 'Outfit', sans-serif;
        box-shadow: none !important;
        border: 1px solid rgba(255, 255, 255, 0.15);
        display: flex;
        align-items: center;
        gap: 8px;
        z-index: 9999;
        transform: translateY(100px);
        opacity: 0;
        transition: all 0.25s ease-in-out;
    }

    .var-toast.show {
        transform: translateY(0);
        opacity: 1;
    }

    @media (max-width: 768px) {
        .custom-notification-module .msg-card-header { flex-direction: column; align-items: flex-start; }
        .custom-notification-module .msg-save-bar { flex-direction: column; align-items: flex-end; text-align: right; }
    }
</style>

<div class="custom-notification-module">
    {{-- Subheader Block (Page Title and Breadcrumb are rendered by AppServiceProvider via partials.breadcrumbs) --}}
    <div class="d-flex justify-content-between align-items-center mb-3 pt-1">
        <p class="text-muted small mb-0 font-outfit" style="font-weight: 400;">Customize automated and manual notification templates for WhatsApp &amp; SMS messages.</p>
        <span class="badge rounded-pill px-3 py-1 font-outfit" style="background-color: #f1f5f9; color: #18225f; border: 1px solid #cbd5e1; font-size: 0.8rem; font-weight: 500;">
            <i class="fa-solid fa-sliders me-1" style="color: #34939F;"></i> 3 Core Templates
        </span>
    </div>

    {{-- Alerts (Shadows removed) --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4 rounded-3 border" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation fs-5 text-danger"></i>
                <div style="font-weight: 400;">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4 rounded-3 border" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-circle-check fs-5 text-success"></i>
                <div style="font-weight: 400;">{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Tabs Navigation (Real Tab Look, No Bottom Separator) --}}
    <div class="msg-tabs-wrap" id="msgTabs" role="tablist">
        <button class="msg-tab-btn active" id="waba-tab" data-bs-toggle="tab" data-bs-target="#waba-pane" type="button" role="tab">
            <i class="fab fa-whatsapp" style="color: #25D366; font-size: 1.1rem;"></i> WhatsApp Templates
        </button>
        <button class="msg-tab-btn" id="text-tab" data-bs-toggle="tab" data-bs-target="#text-pane" type="button" role="tab">
            <i class="fa-solid fa-message" style="color: #34939F; font-size: 1rem;"></i> Text (SMS) Templates
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
                            <label class="form-label mb-1">Message Content <span>*</span></label>
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
                                <i class="fa-solid fa-code" style="color: #34939F;"></i>
                                <span>Available Dynamic Variables (Click badge to copy):</span>
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
                                            title="Click to copy {{ $varTag }}">
                                        <i class="fa-regular fa-copy text-muted var-icon"></i>
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
                    <button type="submit" class="btn btn-primary button font-outfit"><i class="fa-solid fa-floppy-disk me-1"></i> Save WhatsApp Templates</button>
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
                            <span class="msg-card-badge"><i class="fa-solid fa-message me-1" style="color: #34939F;"></i> SMS Text</span>
                        </div>

                        <input type="hidden" name="templates[{{ $index }}][operation_id]" value="{{ $template->operation_id }}">
                        <input type="hidden" name="templates[{{ $index }}][type]" value="{{ $template->type }}">

                        {{-- Message Textarea --}}
                        <div class="msg-textarea-wrap">
                            <label class="form-label mb-1">Message Content <span>*</span></label>
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
                                <i class="fa-solid fa-code" style="color: #34939F;"></i>
                                <span>Available Dynamic Variables (Click badge to copy):</span>
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
                                            title="Click to copy {{ $varTag }}">
                                        <i class="fa-regular fa-copy text-muted var-icon"></i>
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
                    <button type="submit" class="btn btn-primary button font-outfit"><i class="fa-solid fa-floppy-disk me-1"></i> Save Text Templates</button>
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
        // Auto-fit textarea height to its content
        function autoResizeTextarea(textarea) {
            if (!textarea) return;
            textarea.style.height = 'auto';
            textarea.style.height = (textarea.scrollHeight + 4) + 'px';
        }

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

        // Initialize all textareas
        document.querySelectorAll('.msg-textarea').forEach(function (textarea) {
            updateCounter(textarea);
            autoResizeTextarea(textarea);
            textarea.addEventListener('input', function () {
                updateCounter(this);
                autoResizeTextarea(this);
            });
        });

        // Recalculate textarea heights when switching tabs
        document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(function (tabBtn) {
            tabBtn.addEventListener('shown.bs.tab', function (e) {
                const targetSelector = e.target.getAttribute('data-bs-target');
                const targetPane = document.querySelector(targetSelector);
                if (targetPane) {
                    targetPane.querySelectorAll('.msg-textarea').forEach(function (ta) {
                        autoResizeTextarea(ta);
                    });
                }
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

        // Variable copy option (Copies to clipboard for pasting anywhere; does not insert into textarea)
        document.querySelectorAll('.msg-var-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const varText = this.getAttribute('data-var');
                const icon = this.querySelector('.var-icon');

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

                // Temporary visual checkmark feedback on the button
                if (icon) {
                    icon.className = 'fa-solid fa-check text-success var-icon';
                    setTimeout(function () {
                        icon.className = 'fa-regular fa-copy text-muted var-icon';
                    }, 1200);
                }

                showToast('Copied ' + varText + ' to clipboard! You can paste it anywhere.');
            });
        });
    });
</script>

@endsection
