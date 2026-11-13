@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-solid fa-book" href="#components">Components</x-ui.button>
@endsection

@section('content')
<x-ui.banner>Every admin screen is built from these pieces — <code>resources/views/components/ui</code>, <code>resources/css/admin/ds.css</code> and Tailwind utilities (tokens: <code>bg-tint text-label-3 rounded-xl shadow-1</code>). Use them instead of writing new CSS.</x-ui.banner>

<x-ui.section label="Colour">
    <x-ui.card>
        <div class="ui-grid ui-grid--3">
            @foreach([
                ['Tint (brand navy)', '--tint'], ['Label', '--label'], ['Secondary label', '--label-3'], ['Background', '--bg'],
                ['Green', '--green'], ['Red', '--red'], ['Orange', '--orange'], ['Purple', '--purple'], ['Teal', '--teal'],
            ] as [$name, $var])
                <div class="ui-inline">
                    <span style="width:40px;height:40px;border-radius:12px;background:var({{ $var }});box-shadow:var(--sh-0)"></span>
                    <div><div class="ui-headline">{{ $name }}</div><div class="ui-footnote ui-mono">{{ $var }}</div></div>
                </div>
            @endforeach
        </div>
    </x-ui.card>
</x-ui.section>

<x-ui.section label="Type">
    <x-ui.card>
        <div class="ui-stack">
            <p class="ui-large-title">Large title 30</p>
            <p class="ui-title-1">Title 1 · 24</p>
            <p class="ui-title-2">Title 2 · 20</p>
            <p class="ui-title-3">Title 3 · 17</p>
            <div class="ui-headline">Headline · 15 semibold</div>
            <div class="ui-body">Body · 15 — SF Pro on Apple devices, Inter everywhere else.</div>
            <div class="ui-subhead">Subhead · 13 secondary</div>
            <div class="ui-caption">Caption · 11</div>
        </div>
    </x-ui.card>
</x-ui.section>

<div id="components"></div>
<x-ui.section label="Buttons">
    <x-ui.card>
        <div class="ui-btn-group mb-3">
            <x-ui.button icon="fa-solid fa-plus">Filled</x-ui.button>
            <x-ui.button variant="tinted">Tinted</x-ui.button>
            <x-ui.button variant="gray">Gray</x-ui.button>
            <x-ui.button variant="outline">Outline</x-ui.button>
            <x-ui.button variant="plain">Plain</x-ui.button>
            <x-ui.button variant="destructive" icon="fa-regular fa-trash-can">Delete</x-ui.button>
            <x-ui.button variant="success" icon="fa-solid fa-check">Approve</x-ui.button>
        </div>
        <div class="ui-btn-group">
            <x-ui.button size="sm">Small</x-ui.button>
            <x-ui.button>Medium</x-ui.button>
            <x-ui.button size="lg">Large</x-ui.button>
            <x-ui.button variant="gray" icon="fa-solid fa-pen" label="Edit" />
            <x-ui.button pill variant="tinted" icon="fa-solid fa-phone">Pill</x-ui.button>
            <x-ui.button class="is-loading">Saving</x-ui.button>
            <x-ui.button disabled>Disabled</x-ui.button>
        </div>
    </x-ui.card>
</x-ui.section>

<x-ui.section label="Form controls" note="Filled controls; focus draws the tint ring. Errors come from the validation bag automatically.">
    <x-ui.card>
        <div class="grid gap-4 md:grid-cols-2">
            <div><x-ui.input name="ds_name" label="Library name" placeholder="e.g. Gyan Deep Library" required /></div>
            <div><x-ui.input name="ds_mobile" label="Mobile" prefix="+91" type="tel" hint="10 digit mobile number" /></div>
            <div><x-ui.input name="ds_search" label="Search" icon="fa-solid fa-magnifying-glass" placeholder="Name, mobile, city" /></div>
            <div><x-ui.select name="ds_stage" label="Stage" :options="['new' => 'New', 'interested' => 'Interested', 'won' => 'Enrolled']" placeholder="Any stage" /></div>
            <div class="md:col-span-2"><x-ui.textarea name="ds_notes" label="Notes" placeholder="What did they say?" /></div>
            <div>
                <span class="ui-label">Priority</span>
                <x-ui.segmented name="ds_prio" :options="['hot' => 'Hot', 'warm' => 'Warm', 'cold' => 'Cold']" value="warm" block />
            </div>
            <div class="ui-stack md:pt-[26px]" style="gap:14px">
                <x-ui.switch name="ds_active" checked>Active</x-ui.switch>
                <div class="ui-inline" style="gap:18px">
                    <x-ui.check name="ds_a" checked>Checkbox</x-ui.check>
                    <x-ui.check name="ds_r" type="radio" checked>Radio</x-ui.check>
                </div>
            </div>
        </div>
    </x-ui.card>
</x-ui.section>

<x-ui.section label="Stats">
    <div class="ui-stats ui-stats--4">
        <x-ui.stat label="Libraries" value="311" note="215 joined this year" icon="fa-solid fa-building-columns" href="#" />
        <x-ui.stat label="Paid" value="42" note="14% of all" icon="fa-solid fa-circle-check" tone="green" />
        <x-ui.stat label="Overdue" value="86" note="follow-ups" icon="fa-regular fa-clock" tone="red" alert />
        <x-ui.stat label="Revenue" value="₹45,735" icon="fa-solid fa-indian-rupee-sign">
            <span class="ui-meter" style="width:70px"><i style="width:62%"></i></span> 62% of target
        </x-ui.stat>
    </div>
</x-ui.section>

<div class="grid gap-4 md:grid-cols-2">
    <div class="min-w-0">
        <x-ui.section label="List">
            <x-ui.card flush title="Recently joined" subtitle="Inset grouped rows">
                <x-slot:actions><x-ui.button size="sm" variant="plain">See all</x-ui.button></x-slot:actions>
                <ul class="ui-list">
                    <li><x-ui.row title="Gyan Deep Library" subtitle="Pro plan · 2 days ago" href="#" chevron>
                        <x-slot:leading><x-ui.avatar name="Gyan Deep" /></x-slot:leading>
                        <x-slot:trailing><x-ui.badge tone="green" dot pill>Active</x-ui.badge></x-slot:trailing>
                    </x-ui.row></li>
                    <li><x-ui.row title="Shiv Study Point" subtitle="No plan · 1 week ago" href="#" chevron>
                        <x-slot:leading><x-ui.avatar name="Shiv Study" soft /></x-slot:leading>
                        <x-slot:trailing><x-ui.badge pill>Inactive</x-ui.badge></x-slot:trailing>
                    </x-ui.row></li>
                    <li><x-ui.row title="Settings-style row" subtitle="With an icon tile" icon="fa-solid fa-gear" href="#" chevron /></li>
                </ul>
            </x-ui.card>
        </x-ui.section>
    </div>
    <div class="min-w-0">
        <x-ui.section label="Badges & menus">
            <x-ui.card>
                <div class="ui-inline mb-3">
                    @foreach(['gray', 'blue', 'green', 'red', 'orange', 'yellow', 'purple', 'teal', 'indigo', 'solid'] as $t)
                        <x-ui.badge :tone="$t">{{ ucfirst($t) }}</x-ui.badge>
                    @endforeach
                    <x-ui.badge color="#0891b2" dot pill>Custom</x-ui.badge>
                </div>
                <div class="ui-inline">
                    <x-ui.menu label="Actions">
                        <x-ui.menu-item icon="fa-regular fa-eye" href="#">View</x-ui.menu-item>
                        <x-ui.menu-item icon="fa-solid fa-pen" href="#">Edit</x-ui.menu-item>
                        <div class="dropdown-divider"></div>
                        <x-ui.menu-item icon="fa-regular fa-trash-can" danger>Delete</x-ui.menu-item>
                    </x-ui.menu>
                    <x-ui.button variant="tinted" data-bs-toggle="modal" data-bs-target="#dsSheet" icon="fa-regular fa-window-maximize">Open sheet</x-ui.button>
                    <x-ui.button variant="gray" onclick="toastr.success('Saved on the timeline.', 'Done')">Toast</x-ui.button>
                </div>
            </x-ui.card>
        </x-ui.section>
    </div>
</div>

<x-ui.section label="Table">
    <x-ui.card flush>
        <x-ui.table>
            <x-slot:head><th>Agent</th><th class="is-num">Calls</th><th class="is-num">Connected</th><th>Status</th><th class="is-end"></th></x-slot:head>
            @foreach([['Priya Verma', 48, 28, 'green', 'On a call'], ['Sana Khan', 45, 23, 'orange', 'Break'], ['Arjun Mehta', 43, 25, 'gray', 'Offline']] as [$n, $c, $k, $tone, $s])
                <tr>
                    <td><div class="ui-inline"><x-ui.avatar :name="$n" size="sm" /><div><div class="ui-table__title">{{ $n }}</div><div class="ui-table__sub">{{ round($k / $c * 100) }}% connect rate</div></div></div></td>
                    <td class="is-num">{{ $c }}</td>
                    <td class="is-num">{{ $k }}</td>
                    <td><x-ui.badge :tone="$tone" dot pill>{{ $s }}</x-ui.badge></td>
                    <td class="is-end"><x-ui.button size="sm" variant="gray" icon="fa-solid fa-chevron-right" label="Open" /></td>
                </tr>
            @endforeach
        </x-ui.table>
    </x-ui.card>
</x-ui.section>

<x-ui.section label="Empty state & banners">
    <div class="grid gap-4 md:grid-cols-2">
        <div class="min-w-0"><x-ui.card><x-ui.empty icon="fa-solid fa-mug-hot" title="You're all caught up" text="No follow-ups are due right now."><x-ui.button variant="tinted">Call new leads</x-ui.button></x-ui.empty></x-ui.card></div>
        <div class="min-w-0">
            <x-ui.banner tone="success" dismissible>Lead added.</x-ui.banner>
            <x-ui.banner tone="warning">3 follow-ups are overdue.</x-ui.banner>
            <x-ui.banner tone="danger">Enter a valid 10 digit mobile number.</x-ui.banner>
            <x-ui.card><span class="ui-skeleton" style="height:14px;width:60%;margin-bottom:10px"></span><span class="ui-skeleton" style="height:14px;width:85%"></span></x-ui.card>
        </div>
    </div>
</x-ui.section>

<x-ui.sheet id="dsSheet" title="Log call" subtitle="Gyan Deep Library · +91 98765 43210">
    <x-ui.input name="ds_sheet_note" label="Notes" placeholder="What did they say?" />
    <span class="ui-label">Result</span>
    <x-ui.segmented name="ds_res" :options="['ok' => 'Connected', 'no' => 'No answer', 'busy' => 'Busy']" value="ok" block />
    <x-slot:footer>
        <x-ui.button variant="gray" data-bs-dismiss="modal">Cancel</x-ui.button>
        <x-ui.button icon="fa-solid fa-check" data-bs-dismiss="modal">Save call</x-ui.button>
    </x-slot:footer>
</x-ui.sheet>
@endsection
