{{-- Limo admin pages: only what the design system does not cover. Tables become stacked rows on phones. --}}
@push('styles')
<style>
    @media (max-width: 767.98px) {
        .lm-stack thead { display: none; }
        .lm-stack, .lm-stack tbody, .lm-stack tr, .lm-stack td { display: block; width: 100%; }
        .lm-stack tr { padding: 12px 16px; border-top: .5px solid var(--sep); }
        .lm-stack tbody tr:first-child { border-top: 0; }
        .lm-stack td { padding: 2px 0 !important; border: 0 !important; text-align: left !important; }
        .lm-stack td[data-label]::before { content: attr(data-label) " · "; color: var(--label-4); }
        .lm-stack td.lm-actions { padding-top: 10px !important; }
        .lm-stack tbody tr:hover td { background: transparent; }
    }
</style>
@endpush
