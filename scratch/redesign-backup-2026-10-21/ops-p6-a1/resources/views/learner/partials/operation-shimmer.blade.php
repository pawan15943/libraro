{{-- Reusable Operation Pages Shimmer Skeleton Loader --}}
@php
    $shimmerType = $type ?? 'form';
@endphp

{{-- Instantly suppress full-page blocking overlay loader (#loaderone) so shimmer skeleton is front and center --}}
<style>
    #loaderone, #loader {
        display: none !important;
        opacity: 0 !important;
        visibility: hidden !important;
        pointer-events: none !important;
        z-index: -9999 !important;
    }
</style>
<script>
    try {
        var el1 = document.getElementById('loaderone');
        if (el1) { el1.style.display = 'none'; el1.remove(); }
        var el0 = document.getElementById('loader');
        if (el0) { el0.style.display = 'none'; el0.remove(); }
    } catch(e) {}
</script>

<div id="operationShimmer" class="op-shimmer-container type-{{ $shimmerType }}" aria-hidden="true">
    @if($shimmerType === 'form')
        {{-- 1. Hero Seat Header Card Skeleton (100% matches .learner-seat-header-card) --}}
        <div class="op-shimmer-hero-card">
            <div class="op-hero-main">
                <div class="op-hero-identity">
                    <div class="op-skeleton-box op-hero-avatar-box"></div>
                    <div class="op-hero-details">
                        <div class="op-hero-top-badges">
                            <span class="op-skeleton-box op-badge-status"></span>
                        </div>
                        <div class="op-skeleton-box op-hero-name"></div>
                        <div class="op-hero-subtags">
                            <span class="op-skeleton-box op-subtag-uid"></span>
                            <span class="op-skeleton-box op-subtag-meta"></span>
                        </div>
                    </div>
                </div>
                <span class="op-skeleton-box op-btn-back-pill"></span>
            </div>

            {{-- 4 Detail Glass Tiles matching live view --}}
            <div class="op-glass-info-grid">
                <div class="op-glass-tile">
                    <span class="op-skeleton-box op-glass-tile-icon"></span>
                    <div class="op-glass-tile-content">
                        <span class="op-skeleton-box op-glass-tile-label"></span>
                        <span class="op-skeleton-box op-glass-tile-value"></span>
                    </div>
                </div>
                <div class="op-glass-tile">
                    <span class="op-skeleton-box op-glass-tile-icon"></span>
                    <div class="op-glass-tile-content">
                        <span class="op-skeleton-box op-glass-tile-label"></span>
                        <span class="op-skeleton-box op-glass-tile-value"></span>
                    </div>
                </div>
                <div class="op-glass-tile">
                    <span class="op-skeleton-box op-glass-tile-icon"></span>
                    <div class="op-glass-tile-content">
                        <span class="op-skeleton-box op-glass-tile-label"></span>
                        <span class="op-skeleton-box op-glass-tile-value"></span>
                    </div>
                </div>
                <div class="op-glass-tile">
                    <span class="op-skeleton-box op-glass-tile-icon"></span>
                    <div class="op-glass-tile-content">
                        <span class="op-skeleton-box op-glass-tile-label"></span>
                        <span class="op-skeleton-box op-glass-tile-value"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Primary Form Card Skeleton (matches .edit-card) --}}
        <div class="op-shimmer-form-card">
            <div class="op-card-header-bar">
                <div class="op-header-title-wrap">
                    <span class="op-skeleton-box op-header-icon"></span>
                    <div class="op-header-text-cluster">
                        <span class="op-skeleton-box op-header-title"></span>
                        <span class="op-skeleton-box op-header-subtitle"></span>
                    </div>
                </div>
                <span class="op-skeleton-box op-header-badge"></span>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="op-field-group">
                        <span class="op-skeleton-box op-field-label"></span>
                        <span class="op-skeleton-box op-field-input"></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="op-field-group">
                        <span class="op-skeleton-box op-field-label"></span>
                        <span class="op-skeleton-box op-field-input"></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="op-field-group">
                        <span class="op-skeleton-box op-field-label"></span>
                        <span class="op-skeleton-box op-field-input"></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="op-field-group">
                        <span class="op-skeleton-box op-field-label"></span>
                        <span class="op-skeleton-box op-field-input"></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="op-field-group">
                        <span class="op-skeleton-box op-field-label"></span>
                        <span class="op-skeleton-box op-field-input"></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="op-field-group">
                        <span class="op-skeleton-box op-field-label"></span>
                        <span class="op-skeleton-box op-field-input"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. Secondary Form Card Skeleton --}}
        <div class="op-shimmer-form-card">
            <div class="op-card-header-bar">
                <div class="op-header-title-wrap">
                    <span class="op-skeleton-box op-header-icon"></span>
                    <div class="op-header-text-cluster">
                        <span class="op-skeleton-box op-header-title" style="width: 150px;"></span>
                        <span class="op-skeleton-box op-header-subtitle" style="width: 210px;"></span>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="op-field-group">
                        <span class="op-skeleton-box op-field-label"></span>
                        <span class="op-skeleton-box op-field-input"></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="op-field-group">
                        <span class="op-skeleton-box op-field-label"></span>
                        <span class="op-skeleton-box op-field-input"></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="op-field-group">
                        <span class="op-skeleton-box op-field-label"></span>
                        <span class="op-skeleton-box op-field-input"></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="op-field-group">
                        <span class="op-skeleton-box op-field-label"></span>
                        <span class="op-skeleton-box op-field-input"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. Action Button Bar Skeleton --}}
        <div class="op-form-actions-bar">
            <span class="op-skeleton-box op-btn-submit"></span>
        </div>

    @elseif($shimmerType === 'profile')
        {{-- Profile Show Skeleton (100% matches .learner-profile-wrapper) --}}
        <div class="op-shimmer-hero-card">
            <div class="op-hero-main">
                <div class="op-hero-identity">
                    <div class="op-skeleton-box op-hero-avatar-box"></div>
                    <div class="op-hero-details">
                        <div class="op-hero-top-badges">
                            <span class="op-skeleton-box op-badge-status"></span>
                        </div>
                        <div class="op-skeleton-box op-hero-name"></div>
                        <div class="op-hero-subtags">
                            <span class="op-skeleton-box op-subtag-uid"></span>
                            <span class="op-skeleton-box op-subtag-meta"></span>
                        </div>
                    </div>
                </div>
                <span class="op-skeleton-box op-btn-back-pill"></span>
            </div>

            <div class="op-glass-info-grid">
                <div class="op-glass-tile">
                    <span class="op-skeleton-box op-glass-tile-icon"></span>
                    <div class="op-glass-tile-content">
                        <span class="op-skeleton-box op-glass-tile-label"></span>
                        <span class="op-skeleton-box op-glass-tile-value"></span>
                    </div>
                </div>
                <div class="op-glass-tile">
                    <span class="op-skeleton-box op-glass-tile-icon"></span>
                    <div class="op-glass-tile-content">
                        <span class="op-skeleton-box op-glass-tile-label"></span>
                        <span class="op-skeleton-box op-glass-tile-value"></span>
                    </div>
                </div>
                <div class="op-glass-tile">
                    <span class="op-skeleton-box op-glass-tile-icon"></span>
                    <div class="op-glass-tile-content">
                        <span class="op-skeleton-box op-glass-tile-label"></span>
                        <span class="op-skeleton-box op-glass-tile-value"></span>
                    </div>
                </div>
                <div class="op-glass-tile">
                    <span class="op-skeleton-box op-glass-tile-icon"></span>
                    <div class="op-glass-tile-content">
                        <span class="op-skeleton-box op-glass-tile-label"></span>
                        <span class="op-skeleton-box op-glass-tile-value"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Profile Card Skeleton --}}
        <div class="op-shimmer-profile-card">
            <div class="op-profile-section">
                <div class="d-flex flex-column gap-2 mb-3">
                    <span class="op-skeleton-box" style="width: 140px; height: 18px; border-radius: 4px;"></span>
                    <span class="op-skeleton-box" style="width: 220px; height: 12px; border-radius: 3px;"></span>
                </div>
                <div class="op-profile-grid-3">
                    <div class="op-profile-field-box">
                        <span class="op-skeleton-box" style="width: 60px; height: 11px; border-radius: 3px;"></span>
                        <span class="op-skeleton-box" style="width: 120px; height: 18px; border-radius: 4px;"></span>
                    </div>
                    <div class="op-profile-field-box">
                        <span class="op-skeleton-box" style="width: 75px; height: 11px; border-radius: 3px;"></span>
                        <span class="op-skeleton-box" style="width: 130px; height: 18px; border-radius: 4px;"></span>
                    </div>
                    <div class="op-profile-field-box">
                        <span class="op-skeleton-box" style="width: 55px; height: 11px; border-radius: 3px;"></span>
                        <span class="op-skeleton-box" style="width: 110px; height: 18px; border-radius: 4px;"></span>
                    </div>
                </div>
            </div>

            <div class="op-profile-section">
                <div class="d-flex flex-column gap-2 mb-3">
                    <span class="op-skeleton-box" style="width: 160px; height: 18px; border-radius: 4px;"></span>
                    <span class="op-skeleton-box" style="width: 200px; height: 12px; border-radius: 3px;"></span>
                </div>
                <div class="op-profile-grid-3">
                    <div class="op-profile-field-box">
                        <span class="op-skeleton-box" style="width: 70px; height: 11px; border-radius: 3px;"></span>
                        <span class="op-skeleton-box" style="width: 140px; height: 18px; border-radius: 4px;"></span>
                    </div>
                    <div class="op-profile-field-box">
                        <span class="op-skeleton-box" style="width: 50px; height: 11px; border-radius: 3px;"></span>
                        <span class="op-skeleton-box" style="width: 120px; height: 18px; border-radius: 4px;"></span>
                    </div>
                    <div class="op-profile-field-box">
                        <span class="op-skeleton-box" style="width: 65px; height: 11px; border-radius: 3px;"></span>
                        <span class="op-skeleton-box" style="width: 130px; height: 18px; border-radius: 4px;"></span>
                    </div>
                </div>
            </div>
        </div>

    @elseif($shimmerType === 'settlement')
        {{-- Settlement Specific Skeleton (100% matches .settlement-main-card) --}}
        <div class="op-settlement-main-card">
            <div class="op-settlement-card-header">
                <div class="d-flex align-items-center gap-3">
                    <span class="op-skeleton-box" style="width: 42px; height: 42px; border-radius: 12px;"></span>
                    <div class="d-flex flex-column gap-2">
                        <span class="op-skeleton-box" style="width: 220px; height: 18px; border-radius: 4px;"></span>
                        <span class="op-skeleton-box" style="width: 160px; height: 13px; border-radius: 3px;"></span>
                    </div>
                </div>
                <span class="op-skeleton-box op-btn-back-pill d-none d-md-block"></span>
            </div>

            <div class="op-settlement-card-body">
                <div class="op-settlement-section-bar">
                    <span class="op-skeleton-box" style="width: 200px; height: 18px; border-radius: 4px;"></span>
                    <span class="op-skeleton-box" style="width: 140px; height: 18px; border-radius: 4px;"></span>
                </div>

                <div class="op-settlement-cards-container">
                    <div class="op-settlement-plan-card">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="op-skeleton-box" style="width: 70px; height: 22px; border-radius: 6px;"></span>
                            <span class="op-skeleton-box" style="width: 80px; height: 22px; border-radius: 6px;"></span>
                            <span class="op-skeleton-box" style="width: 120px; height: 22px; border-radius: 6px;"></span>
                        </div>
                        <div class="op-settlement-metric-row">
                            <div class="d-flex flex-column gap-1">
                                <span class="op-skeleton-box" style="width: 45px; height: 11px; border-radius: 3px;"></span>
                                <span class="op-skeleton-box" style="width: 70px; height: 18px; border-radius: 4px;"></span>
                            </div>
                            <div class="d-flex flex-column gap-1">
                                <span class="op-skeleton-box" style="width: 45px; height: 11px; border-radius: 3px;"></span>
                                <span class="op-skeleton-box" style="width: 70px; height: 18px; border-radius: 4px;"></span>
                            </div>
                            <div class="d-flex flex-column gap-1">
                                <span class="op-skeleton-box" style="width: 45px; height: 11px; border-radius: 3px;"></span>
                                <span class="op-skeleton-box" style="width: 70px; height: 18px; border-radius: 4px;"></span>
                            </div>
                        </div>
                    </div>
                    <div class="op-settlement-plan-card">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="op-skeleton-box" style="width: 70px; height: 22px; border-radius: 6px;"></span>
                            <span class="op-skeleton-box" style="width: 80px; height: 22px; border-radius: 6px;"></span>
                            <span class="op-skeleton-box" style="width: 120px; height: 22px; border-radius: 6px;"></span>
                        </div>
                        <div class="op-settlement-metric-row">
                            <div class="d-flex flex-column gap-1">
                                <span class="op-skeleton-box" style="width: 45px; height: 11px; border-radius: 3px;"></span>
                                <span class="op-skeleton-box" style="width: 70px; height: 18px; border-radius: 4px;"></span>
                            </div>
                            <div class="d-flex flex-column gap-1">
                                <span class="op-skeleton-box" style="width: 45px; height: 11px; border-radius: 3px;"></span>
                                <span class="op-skeleton-box" style="width: 70px; height: 18px; border-radius: 4px;"></span>
                            </div>
                            <div class="d-flex flex-column gap-1">
                                <span class="op-skeleton-box" style="width: 45px; height: 11px; border-radius: 3px;"></span>
                                <span class="op-skeleton-box" style="width: 70px; height: 18px; border-radius: 4px;"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <span class="op-skeleton-box" style="width: 220px; height: 46px; border-radius: 28px;"></span>
                </div>
            </div>
        </div>

    @elseif($shimmerType === 'payment')
        {{-- Payment / Pending Payment Specific Skeleton (100% matches .row.g-4 with .col-lg-9 and .col-lg-3) --}}
        <div class="row g-4">
            <div class="col-lg-9 order-2 order-md-1">
                {{-- 1. Learner Info dark navy card --}}
                <div class="op-payment-info-card">
                    <div class="op-inner-heading-pill">
                        <span class="op-skeleton-box" style="width: 110px; height: 16px; border-radius: 4px;"></span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <span class="op-skeleton-box-dark" style="width: 70px; height: 12px; margin-bottom: 6px; display: block;"></span>
                            <span class="op-skeleton-box-dark" style="width: 120px; height: 18px; display: block;"></span>
                        </div>
                        <div class="col-md-4">
                            <span class="op-skeleton-box-dark" style="width: 80px; height: 12px; margin-bottom: 6px; display: block;"></span>
                            <span class="op-skeleton-box-dark" style="width: 140px; height: 18px; display: block;"></span>
                        </div>
                        <div class="col-md-4">
                            <span class="op-skeleton-box-dark" style="width: 50px; height: 12px; margin-bottom: 6px; display: block;"></span>
                            <span class="op-skeleton-box-dark" style="width: 110px; height: 18px; display: block;"></span>
                        </div>
                        <div class="col-md-6">
                            <span class="op-skeleton-box-dark" style="width: 60px; height: 12px; margin-bottom: 6px; display: block;"></span>
                            <span class="op-skeleton-box-dark" style="width: 130px; height: 18px; display: block;"></span>
                        </div>
                        <div class="col-md-6">
                            <span class="op-skeleton-box-dark" style="width: 60px; height: 12px; margin-bottom: 6px; display: block;"></span>
                            <span class="op-skeleton-box-dark" style="width: 150px; height: 18px; display: block;"></span>
                        </div>
                    </div>
                </div>

                {{-- 2. Make Payment Form card --}}
                <div class="op-payment-form-card">
                    <div class="op-inner-heading-pill">
                        <span class="op-skeleton-box" style="width: 130px; height: 16px; border-radius: 4px;"></span>
                    </div>
                    <span class="op-skeleton-box" style="width: 100%; height: 38px; border-radius: 8px; margin-bottom: 1.5rem;"></span>

                    <span class="op-skeleton-box" style="width: 140px; height: 18px; border-radius: 4px; margin-bottom: 1rem; display: block;"></span>
                    <div class="row g-4 mb-4">
                        <div class="col-lg-4">
                            <div class="op-field-group">
                                <span class="op-skeleton-box op-field-label"></span>
                                <span class="op-skeleton-box op-field-input"></span>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="op-field-group">
                                <span class="op-skeleton-box op-field-label"></span>
                                <span class="op-skeleton-box op-field-input"></span>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="op-field-group">
                                <span class="op-skeleton-box op-field-label"></span>
                                <span class="op-skeleton-box op-field-input"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-lg-4">
                            <div class="op-field-group">
                                <span class="op-skeleton-box op-field-label"></span>
                                <span class="op-skeleton-box op-field-input"></span>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="op-field-group">
                                <span class="op-skeleton-box op-field-label"></span>
                                <span class="op-skeleton-box op-field-input"></span>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="op-field-group">
                                <span class="op-skeleton-box op-field-label"></span>
                                <span class="op-skeleton-box op-field-input"></span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <span class="op-skeleton-box" style="width: 140px; height: 42px; border-radius: 8px;"></span>
                    </div>
                </div>
            </div>

            {{-- Right Column Seat Info Card (.col-lg-3) --}}
            <div class="col-lg-3 order-1 order-md-2">
                <div class="op-payment-seat-card">
                    <span class="op-skeleton-box" style="width: 90px; height: 18px; border-radius: 4px;"></span>
                    <span class="op-skeleton-box" style="width: 80px; height: 80px; border-radius: 12px; margin: 0.5rem 0;"></span>
                    <span class="op-skeleton-box" style="width: 110px; height: 18px; border-radius: 4px;"></span>
                    <span class="op-skeleton-box" style="width: 140px; height: 32px; border-radius: 6px;"></span>
                    <span class="op-skeleton-box" style="width: 85px; height: 24px; border-radius: 20px;"></span>
                </div>
            </div>
    @elseif($shimmerType === 'transaction')
        {{-- Transaction Page Specific Skeleton --}}
        <div class="transaction-page op-shimmer-tx-wrap" style="max-width: 1180px; margin: 0 auto;">
            {{-- 1. Header Card Skeleton --}}
            <div class="transaction-header mb-3" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px 22px; display: flex; align-items: center; gap: 18px;">
                <span class="op-skeleton-box" style="width: 60px; height: 60px; border-radius: 14px; flex-shrink: 0;"></span>
                <div style="flex: 1;">
                    <span class="op-skeleton-box mb-2" style="width: 90px; height: 16px; border-radius: 4px; display: block;"></span>
                    <span class="op-skeleton-box mb-2" style="width: 190px; height: 22px; border-radius: 6px; display: block;"></span>
                    <span class="op-skeleton-box" style="width: 280px; height: 14px; border-radius: 4px; display: block;"></span>
                </div>
                <span class="op-skeleton-box" style="width: 90px; height: 32px; border-radius: 20px;"></span>
            </div>

            {{-- 2. Tabs Bar Skeleton --}}
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 4px; display: flex; gap: 6px; margin: 20px 0 18px; max-width: 620px;">
                <span class="op-skeleton-box" style="width: 110px; height: 36px; border-radius: 8px;"></span>
                <span class="op-skeleton-box" style="width: 120px; height: 36px; border-radius: 8px;"></span>
                <span class="op-skeleton-box" style="width: 130px; height: 36px; border-radius: 8px;"></span>
                <span class="op-skeleton-box" style="width: 135px; height: 36px; border-radius: 8px;"></span>
                <span class="op-skeleton-box" style="width: 95px; height: 36px; border-radius: 8px;"></span>
            </div>

            {{-- 3. Overview Hero Card Skeleton --}}
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; margin-bottom: 24px;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <span class="op-skeleton-box mb-2" style="width: 140px; height: 16px; border-radius: 4px; margin: 0 auto; display: block;"></span>
                    <span class="op-skeleton-box mb-2" style="width: 190px; height: 42px; border-radius: 8px; margin: 0 auto; display: block;"></span>
                    <span class="op-skeleton-box" style="width: 380px; height: 14px; border-radius: 4px; margin: 0 auto; display: block;"></span>
                </div>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
                    <span class="op-skeleton-box" style="width: 80%; height: 36px; margin: 0 auto;"></span>
                    <span class="op-skeleton-box" style="width: 80%; height: 36px; margin: 0 auto;"></span>
                    <span class="op-skeleton-box" style="width: 80%; height: 36px; margin: 0 auto;"></span>
                    <span class="op-skeleton-box" style="width: 80%; height: 36px; margin: 0 auto;"></span>
                </div>
            </div>

            {{-- 4. Next Payment Due Skeleton --}}
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px 20px; display: flex; align-items: center; gap: 16px; margin-bottom: 16px;">
                <span class="op-skeleton-box" style="width: 44px; height: 44px; border-radius: 10px;"></span>
                <div style="flex: 1;">
                    <span class="op-skeleton-box mb-1" style="width: 120px; height: 18px; border-radius: 4px; display: block;"></span>
                    <span class="op-skeleton-box" style="width: 220px; height: 12px; border-radius: 4px; display: block;"></span>
                </div>
                <span class="op-skeleton-box" style="width: 80px; height: 24px; border-radius: 6px;"></span>
            </div>

            {{-- 5. Transaction Detail Card Skeleton --}}
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px 20px;">
                <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 16px;">
                    <span class="op-skeleton-box" style="width: 44px; height: 44px; border-radius: 10px;"></span>
                    <div style="flex: 1;">
                        <span class="op-skeleton-box mb-1" style="width: 140px; height: 18px; border-radius: 4px; display: block;"></span>
                        <span class="op-skeleton-box" style="width: 200px; height: 12px; border-radius: 4px; display: block;"></span>
                    </div>
                    <span class="op-skeleton-box" style="width: 80px; height: 26px; border-radius: 20px;"></span>
                    <span class="op-skeleton-box" style="width: 90px; height: 28px; border-radius: 6px;"></span>
                </div>
                <span class="op-skeleton-box" style="width: 100%; height: 60px; border-radius: 12px;"></span>
            </div>
        </div>
    @endif
</div>

<script>
(function() {
    function killBlockingLoaders() {
        try {
            var el1 = document.getElementById('loaderone');
            if (el1) { el1.style.display = 'none'; el1.remove(); }
            var el0 = document.getElementById('loader');
            if (el0) { el0.style.display = 'none'; el0.remove(); }
            if (window.jQuery) {
                window.jQuery('#loaderone, #loader').remove();
            }
        } catch(e) {}
    }

    // Call immediately as early as possible
    killBlockingLoaders();

    var hasRevealed = false;
    function revealOpContent() {
        if (hasRevealed) return;
        hasRevealed = true;

        killBlockingLoaders();

        var shimmer = document.getElementById('operationShimmer');
        var content = document.getElementById('operationContent');

        if (shimmer) {
            shimmer.classList.add('fade-out');
            setTimeout(function() {
                shimmer.style.display = 'none';
            }, 250);
        }

        if (content) {
            content.classList.add('is-loaded');
        }
    }

    // Ensure shimmer is clearly displayed for 500ms before fading into live content
    if (document.readyState === 'complete') {
        setTimeout(revealOpContent, 500);
    } else {
        window.addEventListener('load', function() {
            setTimeout(revealOpContent, 500);
        });
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(revealOpContent, 500);
        });
    }

    // Safety fallback: guaranteed reveal within 900ms
    setTimeout(revealOpContent, 900);
})();
</script>
