{{-- Calling CRM tabs: a horizontal scroller on phones --}}
@php
    $crmTabs = collect([
        ['crm.dashboard', 'Dashboard', 'fa-solid fa-chart-line', $crm['can']['dashboard'], null],
        ['crm.queue', 'Call queue', 'fa-solid fa-phone-volume', true, null],
        ['crm.reminders.index', 'Follow-ups', 'fa-regular fa-bell', true, $crm['dueCount'] ?: null],
        ['crm.leads.index', 'All leads', 'fa-solid fa-address-book', true, null],
        ['crm.calls.index', 'Call log', 'fa-solid fa-clock-rotate-left', true, null],
        ['crm.import', 'Import', 'fa-solid fa-file-arrow-up', $crm['can']['import'], null],
        ['crm.settings.index', 'Settings', 'fa-solid fa-sliders', $crm['can']['settings'], null],
    ])->filter(fn ($t) => $t[3])->map(fn ($t) => [
        'label' => $t[1],
        'href' => route($t[0]),
        'icon' => $t[2],
        'count' => $t[4],
        'active' => request()->routeIs($t[0])
            || ($t[0] === 'crm.leads.index' && request()->routeIs('crm.leads.*'))
            || ($t[0] === 'crm.import' && request()->routeIs('crm.import*'))
            || ($t[0] === 'crm.settings.index' && request()->routeIs('crm.settings.*')),
    ])->values()->all();
@endphp
<x-ui.tabs :items="$crmTabs" aria-label="Calling CRM" class="-mx-4 px-2 sm:mx-0 sm:px-0" />
<script>
    // phones: keep the current tab in view in the scrolling tab strip
    (function (nav) {
        var a = nav && nav.querySelector('.is-active');
        if (a && nav.scrollWidth > nav.clientWidth) nav.scrollLeft = a.offsetLeft - nav.offsetLeft - (nav.clientWidth - a.offsetWidth) / 2;
    })(document.currentScript.previousElementSibling);
</script>
