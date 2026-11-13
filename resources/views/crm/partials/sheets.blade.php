{{--
    The four action sheets of the Calling CRM (bottom sheets on phones, dialogs on desktop).
    admin-crm.js fills them from the data-lead JSON of the card / page that opened them.
--}}
@php
    $crmConfig = [
        'agent' => Auth::guard('web')->user()->name ?? 'Team',
        'stages' => $crm['stages']->map(fn ($s) => [
            'id' => $s->id, 'name' => $s->name, 'color' => $s->color, 'closed' => $s->is_closed, 'won' => $s->is_won,
            'subs' => $s->children->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
        ])->values(),
        'dispositions' => $crm['dispositions']->map(fn ($d) => [
            'id' => $d->id, 'name' => $d->name, 'connected' => $d->is_connected, 'stage' => $d->stage_id, 'sub' => $d->sub_stage_id,
            'minutes' => $d->follow_up_minutes, 'color' => $d->color,
        ])->values(),
        'templates' => $crm['templates']->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'channel' => $t->channel, 'body' => $t->body])->values(),
        'loginLink' => url('library/login'),
        'csrf' => csrf_token(),
    ];
@endphp
<script type="application/json" id="crmConfig">@json($crmConfig)</script>

{{-- Log call --}}
<div class="modal fade crm-sheet" id="crmCallSheet" tabindex="-1" aria-labelledby="crmCallTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" class="modal-content" data-crm-form="call">
            @csrf
            <div class="modal-header">
                <div class="min-w-0">
                    <h5 class="modal-title" id="crmCallTitle">Log call</h5>
                    <div class="crm-sheet-lead truncate"><span data-crm-fill="name"></span> · <a data-crm-fill="tel" class="crm-mono"></a></div>
                </div>
                <span class="crm-timer ms-auto" data-crm-timer hidden><i class="fa-solid fa-circle"></i><span></span></span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- earlier calls and notes, so the caller knows what was said before (admin-crm.js) --}}
                <section class="crm-history" data-crm-history aria-live="polite">
                    <div class="crm-history-head">
                        <span class="crm-history-title"><i class="fa-solid fa-clock-rotate-left"></i>Previous conversation</span>
                        <span class="crm-history-sum" data-crm-history-sum></span>
                    </div>
                    <div class="crm-history-body" data-crm-history-body>
                        <div class="crm-history-loading"><span class="ui-spinner"></span> Loading…</div>
                    </div>
                    <button type="button" class="crm-history-more" data-crm-history-more hidden></button>
                </section>

                <div>
                    <div class="ui-label">How did the call go?<span class="req">*</span></div>
                    <div class="crm-group-label">Connected</div>
                    <div class="crm-chips" data-crm-dispositions="1"></div>
                    <div class="crm-group-label">Not connected</div>
                    <div class="crm-chips" data-crm-dispositions="0"></div>
                </div>

                <div>
                    <label class="ui-label" for="crmCallNotes">Notes</label>
                    <textarea class="ui-textarea min-h-[76px]" id="crmCallNotes" name="notes" rows="2" maxlength="2000" placeholder="What did they say?"></textarea>
                </div>

                <div data-crm-follow-block>
                    <div class="ui-label">Next call</div>
                    <div class="crm-chips is-small" data-crm-quick="follow_up_at"></div>
                    <input type="datetime-local" class="ui-input mt-2.5" name="follow_up_at" data-crm-when aria-label="Next call date and time">
                    <input type="hidden" name="follow_up_note">
                </div>

                <div class="grid grid-cols-2 gap-x-3 gap-y-4">
                    <div class="min-w-0">
                        <label class="ui-label" for="crmCallStage">Stage</label>
                        <select class="ui-select" id="crmCallStage" name="stage_id" data-crm-stage-select>
                            <option value="">From the result</option>
                        </select>
                    </div>
                    <div class="min-w-0">
                        <label class="ui-label" for="crmCallSub">Sub-stage</label>
                        <select class="ui-select" id="crmCallSub" name="sub_stage_id" data-crm-sub-select><option value="">—</option></select>
                    </div>
                    <div class="min-w-0">
                        <div class="ui-label">Lead is</div>
                        <div class="ui-seg ui-seg--block" role="radiogroup" aria-label="Priority">
                            @foreach(\App\Models\Crm\CrmLead::PRIORITIES as $k => $v)
                                <label><input type="radio" name="priority" value="{{ $k }}"><span class="ui-seg__item px-2">{{ $v }}</span></label>
                            @endforeach
                        </div>
                    </div>
                    <div class="min-w-0">
                        <label class="ui-label" for="crmCallDur">Call time</label>
                        <input type="text" class="ui-input crm-mono" id="crmCallDur" inputmode="numeric" placeholder="m:ss" data-crm-duration>
                        <input type="hidden" name="duration_seconds">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="ui-btn ui-btn--gray" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="ui-btn crm-submit"><i class="fa-solid fa-check"></i> Save call</button>
            </div>
        </form>
    </div>
</div>

{{-- WhatsApp / SMS --}}
<div class="modal fade crm-sheet" id="crmMessageSheet" tabindex="-1" aria-labelledby="crmMsgTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="min-w-0">
                    <h5 class="modal-title" id="crmMsgTitle">Send a message</h5>
                    <div class="crm-sheet-lead truncate"><span data-crm-fill="name"></span> · <span data-crm-fill="mobile" class="crm-mono"></span></div>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="ui-seg ui-seg--block" role="radiogroup" aria-label="Channel">
                    <label><input type="radio" name="crm_channel" value="whatsapp" checked><span class="ui-seg__item"><i class="fa-brands fa-whatsapp"></i> WhatsApp</span></label>
                    <label><input type="radio" name="crm_channel" value="sms"><span class="ui-seg__item"><i class="fa-regular fa-comment-dots"></i> Text message</span></label>
                </div>
                <div>
                    <label class="ui-label" for="crmTemplate">Template</label>
                    <select class="ui-select" id="crmTemplate" data-crm-template></select>
                </div>
                <div>
                    <label class="ui-label" for="crmMsgText">Message</label>
                    <textarea class="ui-textarea" id="crmMsgText" rows="7" maxlength="2000" data-crm-text></textarea>
                    <div class="ui-hint text-right" data-crm-count></div>
                </div>
                <p class="crm-hint m-0 flex gap-2"><i class="fa-solid fa-circle-info mt-0.5 text-label-4"></i><span>Opens WhatsApp / your messages app with this text filled in. It is saved on the lead's timeline.</span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="ui-btn ui-btn--gray" data-bs-dismiss="modal">Cancel</button>
                <a href="#" target="_blank" rel="noopener" class="ui-btn ui-btn--success crm-send" data-crm-send><i class="fa-brands fa-whatsapp"></i> <span>Open WhatsApp</span></a>
            </div>
        </div>
    </div>
</div>

{{-- Follow-up reminder --}}
<div class="modal fade crm-sheet" id="crmReminderSheet" tabindex="-1" aria-labelledby="crmRemTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content" data-crm-form="reminder">
            @csrf
            <div class="modal-header">
                <div class="min-w-0">
                    <h5 class="modal-title" id="crmRemTitle">Set a follow-up</h5>
                    <div class="crm-sheet-lead truncate" data-crm-fill="name"></div>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div>
                    <div class="ui-label">When<span class="req">*</span></div>
                    <div class="crm-chips is-small" data-crm-quick="remind_at"></div>
                    <input type="datetime-local" class="ui-input mt-2.5" name="remind_at" required data-crm-when aria-label="Follow-up date and time">
                </div>
                <div>
                    <label class="ui-label" for="crmRemNote">Note</label>
                    <input type="text" class="ui-input" id="crmRemNote" name="note" maxlength="255" placeholder="e.g. Call after 5, owner will be free">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="ui-btn ui-btn--gray" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="ui-btn crm-submit"><i class="fa-regular fa-bell"></i> Set reminder</button>
            </div>
        </form>
    </div>
</div>

{{-- Status update: stage + sub-stage --}}
<div class="modal fade crm-sheet" id="crmStageSheet" tabindex="-1" aria-labelledby="crmStageTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" class="modal-content" data-crm-form="stage">
            @csrf
            <div class="modal-header">
                <div class="min-w-0">
                    <h5 class="modal-title" id="crmStageTitle">Update status</h5>
                    <div class="crm-sheet-lead truncate" data-crm-fill="name"></div>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div>
                    <div class="ui-label">Stage<span class="req">*</span></div>
                    <div class="crm-stage-pick" data-crm-stage-pick></div>
                </div>
                <div>
                    <label class="ui-label" for="crmStageSub">Sub-stage</label>
                    <select class="ui-select" id="crmStageSub" name="sub_stage_id" data-crm-sub-select><option value="">—</option></select>
                </div>
                <div>
                    <div class="ui-label">Lead is</div>
                    <div class="ui-seg ui-seg--block" role="radiogroup" aria-label="Priority">
                        @foreach(\App\Models\Crm\CrmLead::PRIORITIES as $k => $v)
                            <label><input type="radio" name="priority" value="{{ $k }}"><span class="ui-seg__item">{{ $v }}</span></label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="ui-label" for="crmStageNote">Note</label>
                    <textarea class="ui-textarea min-h-[76px]" id="crmStageNote" name="note" rows="2" maxlength="2000" placeholder="Why the change?"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="ui-btn ui-btn--gray" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="ui-btn crm-submit"><i class="fa-solid fa-check"></i> Update</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('public/js/admin-crm.js') }}?v={{ @filemtime(public_path('js/admin-crm.js')) }}"></script>
@endpush
