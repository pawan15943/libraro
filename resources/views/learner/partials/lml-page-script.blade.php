{{-- Shared by the members-style list pages (history, future bookings, seat history): copy buttons, profile photo preview,
     then lb-members.js (AJAX filters / pagination, card ⇄ list layout). Row actions keep their global handlers from learner/script. --}}
<script>
$(function () {
    $(document).on('click', '.lml .copy-action-btn', function (e) {
        e.preventDefault();
        var val = $(this).data('copy');
        if (!val || !navigator.clipboard) return;
        navigator.clipboard.writeText(String(val)).then(function () { if (window.lpToast) window.lpToast('Copied ' + val); });
    });

    var $modal = $('#imageViewModal'), $img = $('#modalImage');
    $(document).on('click', '.lml .view-image', function (e) {
        e.preventDefault();
        var src = $(this).attr('href') || $(this).find('img').attr('src');
        if (!src) return;
        $img.attr('src', src);
        $modal.css({ display: 'flex', opacity: '1' }).attr('aria-hidden', 'false');
    });
    $modal.on('click', function (e) {
        if (e.target === this || $(e.target).hasClass('close-modal')) {
            $modal.css({ opacity: '0', display: 'none' }).attr('aria-hidden', 'true');
            $img.attr('src', '');
        }
    });
});
</script>
<script src="{{ asset('public/js/lb-members.js') }}?v={{ @filemtime(public_path('js/lb-members.js')) }}" defer></script>
