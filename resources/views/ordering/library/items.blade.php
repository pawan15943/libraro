@extends('layouts.library')

@section('title', 'Menu & Items')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-opform.css') }}?v={{ @filemtime(public_path('css/lb-opform.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-ordering.css') }}?v={{ @filemtime(public_path('css/lb-ordering.css')) }}">
@endpush

@section('content')
@php
    $inr = fn ($n) => '₹' . number_format((float) $n, (float) $n == floor((float) $n) ? 0 : 2);
    $payShort = ['online' => 'Online', 'offline' => 'Counter', 'pay_later' => 'Pay later'];
    $reopen = $errors->any() ? (string) old('form_kind', '') : '';
@endphp

{{-- Category: add / edit --}}
<div class="modal fade lpg-modal" id="odCatModal" tabindex="-1" aria-labelledby="odCatTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <form id="odCatForm" method="POST" action="{{ route('ordering.category.store') }}" novalidate>
                @csrf
                <input type="hidden" name="_method" value="POST" data-od-method>
                <input type="hidden" name="form_kind" value="category">
                <input type="hidden" name="form_id" value="{{ old('form_id', 'new') }}" data-od-id>
                <div class="modal-header">
                    <span class="lpg-ic is-ok"><x-lb-icon name="shapes" /></span>
                    <div>
                        <h2 id="odCatTitle">New category</h2>
                        <small>Learners browse the menu by category / श्रेणी</small>
                    </div>
                    <button type="button" class="lpg-close" data-bs-dismiss="modal" aria-label="Close"><x-lb-icon name="x" /></button>
                </div>
                <div class="modal-body">
                    @if ($errors->any() && $reopen === 'category')
                        <div class="lef-alert od-alert" role="alert">{{ $errors->first() }}</div>
                    @endif
                    <div class="lef-f">
                        <label for="odCatName">Category name <i>*</i></label>
                        <div class="lef-in"><x-lb-icon name="tag" /><input type="text" id="odCatName" name="name" maxlength="80" placeholder="e.g. Beverages" value="{{ $reopen === 'category' ? old('name') : '' }}" required></div>
                    </div>
                    <div class="lef-f od-mt">
                        <label>Icon</label>
                        <div class="od-icons" role="radiogroup" aria-label="Icon">
                            @foreach ($icons as $ic)
                                <label title="{{ $ic }}"><input type="radio" name="icon" value="{{ $ic }}" @checked(($reopen === 'category' ? old('icon') : 'shapes') === $ic)><span><x-lb-icon :name="$ic" /></span></label>
                            @endforeach
                        </div>
                    </div>
                    <div class="lef-f od-mt">
                        <label>Payment methods allowed</label>
                        <div class="od-checks">
                            <label><input type="checkbox" name="allow_online" value="1" checked><span>Online (UPI QR)</span></label>
                            <label><input type="checkbox" name="allow_offline" value="1" checked><span>Pay at counter</span></label>
                            <label><input type="checkbox" name="allow_pay_later" value="1" checked><span>Pay later</span></label>
                        </div>
                        <p class="lef-hint">A cart with items from many categories only shows the methods every category allows.</p>
                    </div>
                    <div class="lef-grid od-mt">
                        <div class="lef-f">
                            <label for="odCatSort">Position</label>
                            <div class="lef-in"><x-lb-icon name="list" /><input type="number" id="odCatSort" name="sort_order" min="0" max="999" placeholder="1"></div>
                        </div>
                    </div>
                    <label class="od-switch">
                        <input type="checkbox" class="form-check-input" role="switch" name="is_service" value="1">
                        <span><b>Service request</b><small>Charging, locker, first-aid help… Learners send a request instead of buying a product.</small></span>
                    </label>
                    <label class="od-switch">
                        <input type="checkbox" class="form-check-input" role="switch" name="is_enabled" value="1" checked>
                        <span><b>Show to learners</b><small>Turn off to hide the whole category for a while.</small></span>
                    </label>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lp-btn lp-btn--ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="lp-btn"><x-lb-icon name="check" />Save category</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Item: add / edit --}}
<div class="modal fade lpg-modal" id="odItemModal" tabindex="-1" aria-labelledby="odItemTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <form id="odItemForm" method="POST" action="{{ route('ordering.item.store') }}" enctype="multipart/form-data" novalidate>
                @csrf
                <input type="hidden" name="_method" value="POST" data-od-method>
                <input type="hidden" name="form_kind" value="item">
                <input type="hidden" name="form_id" value="{{ old('form_id', 'new') }}" data-od-id>
                <div class="modal-header">
                    <span class="lpg-ic is-ok"><x-lb-icon name="coffee" /></span>
                    <div>
                        <h2 id="odItemTitle">New item</h2>
                        <small>A product or service learners can order / ऑर्डर आइटम</small>
                    </div>
                    <button type="button" class="lpg-close" data-bs-dismiss="modal" aria-label="Close"><x-lb-icon name="x" /></button>
                </div>
                <div class="modal-body">
                    @if ($errors->any() && $reopen === 'item')
                        <div class="lef-alert od-alert" role="alert">{{ $errors->first() }}</div>
                    @endif
                    <div class="lef-f">
                        <label for="odItemCat">Category <i>*</i></label>
                        <div class="lef-in"><x-lb-icon name="shapes" />
                            <select id="odItemCat" name="order_category_id" required>
                                @foreach ($categories as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}{{ $c->is_enabled ? '' : ' (hidden)' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="lef-f od-mt">
                        <label for="odItemName">Item name <i>*</i></label>
                        <div class="lef-in"><x-lb-icon name="tag" /><input type="text" id="odItemName" name="name" maxlength="120" placeholder="e.g. Veg Sandwich" required></div>
                    </div>
                    <div class="lef-grid od-mt">
                        <div class="lef-f">
                            <label for="odItemPrice">Price <i>*</i></label>
                            <div class="lef-in"><span class="lef-pre">₹</span><input type="number" id="odItemPrice" name="price" min="0" step="any" inputmode="decimal" placeholder="60" required></div>
                        </div>
                        <div class="lef-f">
                            <label for="odItemUnit">Price per</label>
                            <div class="lef-in"><x-lb-icon name="hash" /><input type="text" id="odItemUnit" name="price_unit" maxlength="30" placeholder="e.g. page, plate"></div>
                        </div>
                    </div>
                    <div class="lef-f od-mt">
                        <label for="odItemDesc">Description</label>
                        <div class="lef-in od-ta"><textarea id="odItemDesc" name="description" maxlength="500" rows="2" placeholder="Short line learners see under the name"></textarea></div>
                    </div>
                    <div class="lef-f od-mt">
                        <label for="odItemImg">Photo</label>
                        <div class="od-img">
                            <span class="od-img-th" data-od-thumb><x-lb-icon name="image" /></span>
                            <input type="file" id="odItemImg" name="image" accept="image/jpeg,image/png,image/webp">
                            <label class="od-img-rm" hidden><input type="checkbox" name="remove_image" value="1"> Remove photo</label>
                        </div>
                        <p class="lef-hint">JPG, PNG or WebP, up to 2 MB.</p>
                    </div>
                    <div class="lef-f od-mt">
                        <label>Can be ordered for</label>
                        <div class="od-checks">
                            @foreach ($modes as $k => $label)
                                <label><input type="checkbox" name="modes[]" value="{{ $k }}" checked><span>{{ $label }}</span></label>
                            @endforeach
                        </div>
                    </div>
                    <label class="od-switch">
                        <input type="checkbox" class="form-check-input" role="switch" name="is_available" value="1" checked>
                        <span><b>Available now</b><small>Out of stock? Turn off — learners see it greyed out and cannot add it.</small></span>
                    </label>
                    <label class="od-switch">
                        <input type="checkbox" class="form-check-input" role="switch" name="is_featured" value="1">
                        <span><b>Featured</b><small>Shown first on the learner's ordering page.</small></span>
                    </label>
                    <label class="od-switch">
                        <input type="checkbox" class="form-check-input" role="switch" name="needs_upload" value="1">
                        <span><b>Learner uploads a file</b><small>For printouts / photocopy: the learner attaches the document and picks copies, colour and sides.</small></span>
                    </label>
                    <label class="od-switch">
                        <input type="checkbox" class="form-check-input" role="switch" name="is_enabled" value="1" checked>
                        <span><b>Show in menu</b><small>Turn off to hide the item without deleting it.</small></span>
                    </label>
                    <input type="hidden" name="sort_order" value="0">
                </div>
                <div class="modal-footer">
                    <button type="button" class="lp-btn lp-btn--ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="lp-btn"><x-lb-icon name="check" />Save item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="lpg od">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <p>Everything learners can order from their seat, a cafeteria table or the counter — food, stationery, printing and services. Mark an item unavailable in one tap when it runs out.</p>
        </div>
        <div class="lpg-head-r">
            <button type="button" class="lp-btn lp-btn--ghost" data-od-cat-new><x-lb-icon name="shapes" />New category</button>
            @if ($categories->isNotEmpty())
                <button type="button" class="lp-btn" data-od-item-new><x-lb-icon name="plus" />New item</button>
            @endif
        </div>
    </header>

    @unless ($settings->is_enabled)
        <a class="od-note" href="{{ route('ordering.settings') }}">
            <x-lb-icon name="info" />
            <span><b>Ordering is off for learners.</b> Add your items, then switch it on in Order Settings.</span>
            <x-lb-icon name="chevron-right" />
        </a>
    @endunless

    <div class="lpg-stats">
        <div class="lpg-stat"><span class="lpg-stat-ic"><x-lb-icon name="list" /></span><span>Items</span><b>{{ $stats['items'] }}</b><small>in {{ $stats['categories'] }} visible {{ \Illuminate\Support\Str::plural('category', $stats['categories']) }}</small></div>
        <div class="lpg-stat is-ok"><span class="lpg-stat-ic"><x-lb-icon name="circle-check" /></span><span>Available</span><b>{{ $stats['available'] }}</b><small>learners can order these</small></div>
        <div class="lpg-stat {{ $stats['off'] ? 'is-bad' : '' }}"><span class="lpg-stat-ic"><x-lb-icon name="ban" /></span><span>Unavailable</span><b>{{ $stats['off'] }}</b><small>out of stock right now</small></div>
    </div>

    @if ($categories->isEmpty())
        <section class="lpg-empty">
            <span><x-lb-icon name="coffee" /></span>
            <b>No category yet</b>
            <p>Start with a category like Food or Stationery, then add its items.</p>
            <button type="button" class="lp-btn" data-od-cat-new><x-lb-icon name="plus" />Create a category</button>
        </section>
    @else
        <div class="od-bar">
            <div class="od-chips" role="tablist" aria-label="Categories">
                <button type="button" class="od-chip is-on" data-od-filter="all">All <em>{{ $stats['items'] }}</em></button>
                @foreach ($categories as $c)
                    <button type="button" class="od-chip {{ $c->is_enabled ? '' : 'is-off' }}" data-od-filter="{{ $c->id }}"><x-lb-icon :name="$c->icon ?: 'shapes'" />{{ $c->name }} <em>{{ $c->items->count() }}</em></button>
                @endforeach
            </div>
            <label class="od-search"><x-lb-icon name="search" /><input type="search" placeholder="Search items" data-od-search aria-label="Search items"></label>
        </div>

        @foreach ($categories as $c)
            @php
                $catData = [
                    'id' => $c->id, 'name' => $c->name, 'icon' => $c->icon, 'sort_order' => $c->sort_order,
                    'is_enabled' => $c->is_enabled, 'is_service' => $c->is_service,
                    'allow_online' => $c->allow_online, 'allow_offline' => $c->allow_offline, 'allow_pay_later' => $c->allow_pay_later,
                    'url' => route('ordering.category.update', $c->id),
                ];
            @endphp
            <section class="od-cat {{ $c->is_enabled ? '' : 'is-off' }}" data-od-cat="{{ $c->id }}">
                <header class="od-cat-h">
                    <span class="od-cat-ic"><x-lb-icon :name="$c->icon ?: 'shapes'" /></span>
                    <div class="od-cat-t">
                        <h3>{{ $c->name }}</h3>
                        <div class="od-tags">
                            @unless ($c->is_enabled)<span class="lpg-tag is-bad">Hidden</span>@endunless
                            @if ($c->is_service)<span class="lpg-tag is-acc">Service</span>@endif
                            <span class="lpg-tag">{{ collect($c->allowedPayments())->map(fn ($p) => $payShort[$p])->implode(' · ') }}</span>
                        </div>
                    </div>
                    <div class="od-cat-a">
                        <button type="button" class="od-ibtn" data-od-item-new="{{ $c->id }}" title="Add item to {{ $c->name }}" aria-label="Add item to {{ $c->name }}"><x-lb-icon name="plus" /></button>
                        <button type="button" class="od-ibtn" data-od-cat-edit="{{ json_encode($catData) }}" title="Edit category" aria-label="Edit {{ $c->name }}"><x-lb-icon name="pencil" /></button>
                        <form method="POST" action="{{ route('ordering.category.toggle', $c->id) }}">@csrf
                            <button class="od-ibtn" title="{{ $c->is_enabled ? 'Hide' : 'Show' }}" aria-label="{{ $c->is_enabled ? 'Hide' : 'Show' }} {{ $c->name }}"><x-lb-icon :name="$c->is_enabled ? 'eye-off' : 'eye'" /></button>
                        </form>
                        <form method="POST" action="{{ route('ordering.category.destroy', $c->id) }}" data-od-delete="{{ $c->name }} and its {{ $c->items->count() }} {{ \Illuminate\Support\Str::plural('item', $c->items->count()) }}">@csrf @method('DELETE')
                            <button class="od-ibtn is-bad" title="Delete" aria-label="Delete {{ $c->name }}"><x-lb-icon name="trash" /></button>
                        </form>
                    </div>
                </header>

                @if ($c->items->isEmpty())
                    <p class="od-none">No items yet. <button type="button" class="od-link" data-od-item-new="{{ $c->id }}">Add the first one</button></p>
                @else
                    <ul class="od-items">
                        @foreach ($c->items as $it)
                            @php
                                $itemData = [
                                    'id' => $it->id, 'order_category_id' => $it->order_category_id, 'name' => $it->name,
                                    'description' => $it->description, 'price' => (float) $it->price, 'price_unit' => $it->price_unit,
                                    'modes' => $it->modes, 'is_enabled' => $it->is_enabled, 'is_available' => $it->is_available,
                                    'is_featured' => $it->is_featured, 'needs_upload' => $it->needs_upload,
                                    'sort_order' => $it->sort_order, 'image' => $it->imageUrl(),
                                    'url' => route('ordering.item.update', $it->id),
                                ];
                            @endphp
                            <li class="od-item {{ $it->is_available ? '' : 'is-na' }} {{ $it->is_enabled ? '' : 'is-off' }}" data-od-name="{{ mb_strtolower($it->name . ' ' . $it->description) }}">
                                <span class="od-th">@if ($it->image)<img src="{{ $it->imageUrl() }}" alt="" loading="lazy">@else<x-lb-icon :name="$c->icon ?: 'shapes'" />@endif</span>
                                <div class="od-it">
                                    <b>{{ $it->name }}</b>
                                    @if ($it->description)<small>{{ $it->description }}</small>@endif
                                    <div class="od-tags">
                                        @if ($it->is_featured)<span class="lpg-tag is-acc">Featured</span>@endif
                                        @if ($it->needs_upload)<span class="lpg-tag">File upload</span>@endif
                                        @if (! empty($it->modes))<span class="lpg-tag">{{ collect($it->modes)->map(fn ($m) => \Illuminate\Support\Str::before($modes[$m] ?? $m, ' '))->implode(' · ') }} only</span>@endif
                                        @unless ($it->is_enabled)<span class="lpg-tag is-bad">Hidden</span>@endunless
                                    </div>
                                </div>
                                <span class="od-price">{{ $inr($it->price) }}@if ($it->price_unit)<small>/{{ $it->price_unit }}</small>@endif</span>
                                <label class="od-avail" title="Available now">
                                    <input type="checkbox" class="form-check-input" role="switch" @checked($it->is_available) data-od-avail="{{ route('ordering.item.availability', $it->id) }}" aria-label="{{ $it->name }} available">
                                    <span>{{ $it->is_available ? 'In stock' : 'Out' }}</span>
                                </label>
                                <div class="od-it-a">
                                    <button type="button" class="od-ibtn" data-od-item-edit="{{ json_encode($itemData) }}" title="Edit" aria-label="Edit {{ $it->name }}"><x-lb-icon name="pencil" /></button>
                                    <form method="POST" action="{{ route('ordering.item.destroy', $it->id) }}" data-od-delete="{{ $it->name }}">@csrf @method('DELETE')
                                        <button class="od-ibtn is-bad" title="Delete" aria-label="Delete {{ $it->name }}"><x-lb-icon name="trash" /></button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach
        <p class="od-none od-noresult" hidden>No item matches your search.</p>
    @endif
</div>

<script>
(function () {
    var csrf = @json(csrf_token());
    var urls = { catStore: @json(route('ordering.category.store')), itemStore: @json(route('ordering.item.store')) };
    var $ = function (s, r) { return (r || document).querySelector(s); };
    var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

    function openModal(el) { if (window.bootstrap && bootstrap.Modal) bootstrap.Modal.getOrCreateInstance(el).show(); else if (window.jQuery) jQuery(el).modal('show'); }
    function clearErrors(form) { $$('.od-alert', form).forEach(function (x) { x.remove(); }); }
    function setChecked(form, name, on) { var i = form.querySelector('[name="' + name + '"]'); if (i) i.checked = !!on; }

    // ---- category modal
    var catModal = $('#odCatModal'), catForm = $('#odCatForm');
    function fillCat(d) {
        clearErrors(catForm);
        catForm.action = d ? d.url : urls.catStore;
        $('[data-od-method]', catForm).value = d ? 'PUT' : 'POST';
        $('[data-od-id]', catForm).value = d ? d.id : 'new';
        $('#odCatTitle').textContent = d ? 'Edit category' : 'New category';
        catForm.name.value = d ? d.name : '';
        catForm.sort_order.value = d ? d.sort_order : '';
        $$('input[name="icon"]', catForm).forEach(function (r) { r.checked = r.value === (d ? d.icon : 'shapes'); });
        setChecked(catForm, 'allow_online', d ? d.allow_online : true);
        setChecked(catForm, 'allow_offline', d ? d.allow_offline : true);
        setChecked(catForm, 'allow_pay_later', d ? d.allow_pay_later : true);
        setChecked(catForm, 'is_service', d ? d.is_service : false);
        setChecked(catForm, 'is_enabled', d ? d.is_enabled : true);
    }
    $$('[data-od-cat-new]').forEach(function (b) { b.addEventListener('click', function () { fillCat(null); openModal(catModal); }); });
    $$('[data-od-cat-edit]').forEach(function (b) { b.addEventListener('click', function () { fillCat(JSON.parse(b.getAttribute('data-od-cat-edit'))); openModal(catModal); }); });

    // ---- item modal
    var itemModal = $('#odItemModal'), itemForm = $('#odItemForm'), thumb = $('[data-od-thumb]', itemForm), rm = $('.od-img-rm', itemForm);
    var thumbEmpty = thumb ? thumb.innerHTML : '';
    function showThumb(src) { if (!thumb) return; thumb.innerHTML = src ? '<img src="' + src + '" alt="">' : thumbEmpty; }
    function fillItem(d, catId) {
        if (!itemForm) return;
        clearErrors(itemForm);
        itemForm.action = d ? d.url : urls.itemStore;
        $('[data-od-method]', itemForm).value = d ? 'PUT' : 'POST';
        $('[data-od-id]', itemForm).value = d ? d.id : 'new';
        $('#odItemTitle').textContent = d ? 'Edit item' : 'New item';
        if (d) itemForm.order_category_id.value = d.order_category_id; else if (catId) itemForm.order_category_id.value = catId;
        itemForm.name.value = d ? d.name : '';
        itemForm.price.value = d ? d.price : '';
        itemForm.price_unit.value = d && d.price_unit ? d.price_unit : '';
        itemForm.description.value = d && d.description ? d.description : '';
        itemForm.sort_order.value = d ? d.sort_order : 0;
        itemForm.image.value = '';
        $$('input[name="modes[]"]', itemForm).forEach(function (c) { c.checked = !d || !d.modes || d.modes.indexOf(c.value) !== -1; });
        setChecked(itemForm, 'is_available', d ? d.is_available : true);
        setChecked(itemForm, 'is_featured', d ? d.is_featured : false);
        setChecked(itemForm, 'needs_upload', d ? d.needs_upload : false);
        setChecked(itemForm, 'is_enabled', d ? d.is_enabled : true);
        setChecked(itemForm, 'remove_image', false);
        showThumb(d && d.image);
        if (rm) rm.hidden = !(d && d.image);
    }
    $$('[data-od-item-new]').forEach(function (b) { b.addEventListener('click', function () { fillItem(null, b.getAttribute('data-od-item-new')); openModal(itemModal); }); });
    $$('[data-od-item-edit]').forEach(function (b) { b.addEventListener('click', function () { fillItem(JSON.parse(b.getAttribute('data-od-item-edit'))); openModal(itemModal); }); });
    if (itemForm) itemForm.image.addEventListener('change', function () {
        var f = this.files && this.files[0];
        if (f) { var r = new FileReader(); r.onload = function (e) { showThumb(e.target.result); }; r.readAsDataURL(f); }
    });

    // ---- one-tap availability
    $$('[data-od-avail]').forEach(function (sw) {
        sw.addEventListener('change', function () {
            var on = sw.checked, row = sw.closest('.od-item'), txt = sw.parentNode.querySelector('span');
            var body = new FormData(); body.append('_token', csrf); body.append('is_available', on ? 1 : 0);
            sw.disabled = true;
            fetch(sw.getAttribute('data-od-avail'), { method: 'POST', body: body, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { if (!r.ok) throw r; return r.json(); })
                .then(function (j) { row.classList.toggle('is-na', !j.is_available); txt.textContent = j.is_available ? 'In stock' : 'Out'; })
                .catch(function () { sw.checked = !on; if (window.toastr) toastr.error('Could not save. Please try again.'); })
                .finally(function () { sw.disabled = false; });
        });
    });

    // ---- filter by category + search
    var active = 'all', q = '';
    function applyFilter() {
        var shown = 0;
        $$('[data-od-cat]').forEach(function (sec) {
            var catOk = active === 'all' || sec.getAttribute('data-od-cat') === active, any = false;
            $$('.od-item', sec).forEach(function (li) {
                var ok = catOk && (!q || li.getAttribute('data-od-name').indexOf(q) !== -1);
                li.hidden = !ok; if (ok) { any = true; shown++; }
            });
            sec.hidden = !catOk || (q && !any);
        });
        var nr = $('.od-noresult'); if (nr) nr.hidden = !(q && shown === 0);
    }
    $$('[data-od-filter]').forEach(function (chip) {
        chip.addEventListener('click', function () {
            active = chip.getAttribute('data-od-filter');
            $$('[data-od-filter]').forEach(function (c) { c.classList.toggle('is-on', c === chip); });
            applyFilter();
        });
    });
    var s = $('[data-od-search]'); if (s) s.addEventListener('input', function () { q = s.value.trim().toLowerCase(); applyFilter(); });

    // ---- delete confirm
    $$('[data-od-delete]').forEach(function (fm) {
        fm.addEventListener('submit', function (e) {
            e.preventDefault();
            var what = fm.getAttribute('data-od-delete');
            if (window.Swal) {
                Swal.fire({ title: 'Delete ' + what + '?', text: 'Past orders keep their details.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#de3b3d', confirmButtonText: 'Delete', cancelButtonText: 'Cancel' })
                    .then(function (r) { if (r.isConfirmed) fm.submit(); });
            } else if (confirm('Delete ' + what + '?')) { fm.submit(); }
        });
    });

    // ---- reopen the form after a failed save, with what was typed
    @if ($reopen === 'category' || $reopen === 'item')
        document.addEventListener('DOMContentLoaded', function () {
            var form = @json($reopen) === 'category' ? catForm : itemForm, old = @json(old());
            var id = old.form_id && old.form_id !== 'new' ? old.form_id : null;
            if (form === catForm) { fillCat(null); } else { fillItem(null); }
            if (id) {
                form.action = (form === catForm ? @json(url('library/ordering/categories')) : @json(url('library/ordering/items'))) + '/' + id;
                $('[data-od-method]', form).value = 'PUT'; $('[data-od-id]', form).value = id;
            }
            Object.keys(old).forEach(function (k) {
                if (k === '_token' || k === '_method' || k === 'form_id') return;
                var els = form.querySelectorAll('[name="' + k + '"], [name="' + k + '[]"]');
                els.forEach(function (el) {
                    if (el.type === 'checkbox' || el.type === 'radio') el.checked = Array.isArray(old[k]) ? old[k].indexOf(el.value) !== -1 : String(old[k]) === el.value;
                    else if (el.type !== 'file' && el.type !== 'hidden') el.value = old[k];
                });
            });
            // unticked boxes are not posted: clear them
            ['is_enabled', 'is_service', 'allow_online', 'allow_offline', 'allow_pay_later', 'is_available', 'is_featured', 'needs_upload'].forEach(function (k) {
                if (!(k in old)) setChecked(form, k, false);
            });
            if (form === itemForm && !('modes' in old)) $$('input[name="modes[]"]', form).forEach(function (c) { c.checked = false; });
            var alert = document.createElement('div'); alert.className = 'lef-alert od-alert'; alert.setAttribute('role', 'alert'); alert.textContent = @json($errors->first());
            if (!$('.od-alert', form)) $('.modal-body', form).prepend(alert);
            openModal(form === catForm ? catModal : itemModal);
        });
    @endif
})();
</script>
@endsection
