<!-- Customer Feedback Full Review Modal -->
<div class="modal fade" id="customerFeedbackModal" tabindex="-1" aria-labelledby="customerFeedbackModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content feedback-modal-card">
            <div class="modal-header border-0 pb-2 pt-3 px-4 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="feedback-modal-quote-pill">
                        <i class="fa-solid fa-quote-left"></i>
                    </span>
                    <div>
                        <h5 class="modal-title mb-0" id="customerFeedbackModalLabel" style="font-family: 'Outfit', sans-serif; font-weight: 600; color: #18225f; font-size: 1.15rem;">Customer Review</h5>
                        <span class="text-muted" style="font-size: 0.78rem;">Verified Testimonial</span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <!-- Rating & Score Pill -->
                <div class="mb-3 d-flex align-items-center gap-2">
                    <ul class="customer-ratings modal-stars-list mb-0" id="modalCustomerRating" aria-label="Rating">
                        <!-- Dynamic stars injected by JS -->
                    </ul>
                    <span class="badge border px-2 py-1" id="modalRatingScore" style="font-size: 0.78rem; font-weight: 600; background: #fff8e6; color: #b45309; border-color: #fde68a !important;">
                        5.0 / 5
                    </span>
                </div>

                <!-- Full Message Content -->
                <div class="feedback-modal-text-wrap p-3 rounded-3 mb-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                    <p class="mb-0" id="modalFeedbackText" style="font-family: 'Mulish', sans-serif; font-size: 0.98rem; line-height: 1.7; color: #334155; white-space: pre-line; word-break: break-word;">
                    </p>
                </div>

                <!-- Customer Info Card -->
                <div class="customer-info pt-2 border-top d-flex align-items-center gap-3">
                    <img src="{{ asset('public/img/user.png') }}" id="modalCustomerImg" alt="Customer" class="profile rounded-circle" style="width: 48px; height: 48px; object-fit: cover; flex-shrink: 0; border: 2px solid #e2e8f0;">
                    <div class="customer-details flex-grow-1" style="min-width: 0;">
                        <h4 id="modalCustomerName" style="font-family: 'Outfit', sans-serif; font-size: 1.05rem; font-weight: 600; color: #18225f; margin: 0 0 2px 0;"></h4>
                        <span id="modalCustomerSub" style="font-size: 0.82rem; color: #34939F; font-weight: 500; display: block;"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-3">
                <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal" style="font-weight: 600; font-size: 0.85rem; background: #18225f; border-color: #18225f;">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('load', function() {
    if (typeof jQuery === 'undefined') return;

    // Click handler to open the feedback modal with full text & details
    jQuery(document).on('click', '.btn-view-feedback-modal', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var $btn = jQuery(this);
        var fullText = $btn.attr('data-full-text') || '';
        var name = $btn.attr('data-customer-name') || 'Customer';
        var sub = $btn.attr('data-customer-sub') || 'Verified Partner';
        var img = $btn.attr('data-customer-image') || "{{ asset('public/img/user.png') }}";
        var rating = parseInt($btn.attr('data-rating')) || 5;

        jQuery('#modalFeedbackText').text(fullText);
        jQuery('#modalCustomerName').text(name);
        jQuery('#modalCustomerSub').text(sub);
        jQuery('#modalCustomerImg').attr('src', img);
        jQuery('#modalRatingScore').text(rating + '.0 / 5');

        var starsHtml = '';
        for (var i = 1; i <= 5; i++) {
            if (i <= rating) {
                starsHtml += '<li><i class="fa-solid fa-star star-active"></i></li>';
            } else {
                starsHtml += '<li><i class="fa-solid fa-star star-inactive"></i></li>';
            }
        }
        jQuery('#modalCustomerRating').html(starsHtml);

        var modalEl = document.getElementById('customerFeedbackModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            var myModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            myModal.show();
        }
    });

    // Check if message text is actually clamped (> 4 lines) and show button
    function checkFeedbackClamping() {
        jQuery('.feedback-box .message-clamp').each(function() {
            var el = this;
            var $box = jQuery(el).closest('.feedback-box');
            var $btn = $box.find('.btn-view-feedback-modal');
            if (el.scrollHeight > el.clientHeight + 1) {
                $btn.removeClass('d-none');
            }
        });
    }

    setTimeout(checkFeedbackClamping, 350);
    jQuery(window).on('resize', checkFeedbackClamping);

    if (jQuery('#clientsFeedbacks').length) {
        jQuery('#clientsFeedbacks').on('initialized.owl.carousel translated.owl.carousel resized.owl.carousel', function() {
            checkFeedbackClamping();
        });
    }
});
</script>

