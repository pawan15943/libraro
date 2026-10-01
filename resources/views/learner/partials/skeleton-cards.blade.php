<div id="learnerSkeletonContainer" class="learner-skeleton-container" aria-hidden="true">
    @for ($i = 0; $i < ($count ?? 5); $i++)
    <div class="row">
        <div class="col-lg-12">
            <div class="learner-card learner-skeleton-card">
                
                {{-- Desktop Skeleton Layout --}}
                <div class="desktop-only-section">
                    {{-- Top Row: Seat Box + Expiry on left, Actions on right --}}
                    <div class="learner-top-row">
                        <div class="learner-top-left d-flex align-items-center gap-2">
                            <span class="skeleton-box skeleton-seat-badge"></span>
                            <span class="skeleton-box skeleton-expiry-badge"></span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="skeleton-box skeleton-action-btn"></span>
                            <span class="skeleton-box skeleton-action-btn"></span>
                            <span class="skeleton-box skeleton-action-btn"></span>
                            <span class="skeleton-box skeleton-action-btn"></span>
                            <span class="skeleton-box skeleton-action-btn"></span>
                        </div>
                    </div>

                    {{-- Bottom Grid: 5 Columns --}}
                    <div class="learner-bottom-grid">
                        {{-- Col 1: Profile & Contact --}}
                        <div class="learner-profile-col d-flex align-items-center gap-2">
                            <div class="skeleton-box skeleton-avatar"></div>
                            <div class="d-flex flex-column gap-1 flex-grow-1" style="min-width: 0;">
                                <span class="skeleton-box skeleton-line-title"></span>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="skeleton-box skeleton-line-sub"></span>
                                    <span class="skeleton-box skeleton-action-btn" style="width: 20px; height: 20px; border-radius: 50%;"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Col 2: Subscription Info --}}
                        <div class="info-stat-block d-flex align-items-center gap-2">
                            <div class="skeleton-box skeleton-icon-box"></div>
                            <div class="d-flex flex-column gap-1 flex-grow-1" style="min-width: 0;">
                                <span class="skeleton-box skeleton-line-label"></span>
                                <span class="skeleton-box skeleton-line-val"></span>
                            </div>
                        </div>

                        {{-- Col 3: Plan Duration --}}
                        <div class="info-stat-block d-flex align-items-center gap-2">
                            <div class="skeleton-box skeleton-icon-box"></div>
                            <div class="d-flex flex-column gap-1 flex-grow-1" style="min-width: 0;">
                                <span class="skeleton-box skeleton-line-label"></span>
                                <span class="skeleton-box skeleton-line-val"></span>
                            </div>
                        </div>

                        {{-- Col 4: Payment Status --}}
                        <div class="info-stat-block d-flex align-items-center gap-2">
                            <div class="skeleton-box skeleton-icon-box"></div>
                            <div class="d-flex flex-column gap-1 flex-grow-1" style="min-width: 0;">
                                <span class="skeleton-box skeleton-line-label"></span>
                                <span class="skeleton-box skeleton-line-val"></span>
                            </div>
                        </div>

                        {{-- Col 5: Locker --}}
                        <div class="info-stat-block d-flex align-items-center gap-2">
                            <div class="skeleton-box skeleton-icon-box"></div>
                            <div class="d-flex flex-column gap-1 flex-grow-1" style="min-width: 0;">
                                <span class="skeleton-box skeleton-line-label"></span>
                                <span class="skeleton-box skeleton-line-val" style="width: 50px;"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Mobile Skeleton Layout --}}
                <div class="mobile-only-section">
                    {{-- 1. Profile Header --}}
                    <div class="mobile-profile-header">
                        <div class="mobile-profile-left">
                            <div class="skeleton-box skeleton-avatar"></div>
                            <div class="mobile-details-text flex-grow-1" style="min-width: 0;">
                                <div class="mobile-name-row d-flex align-items-center justify-content-between mb-1">
                                    <span class="skeleton-box skeleton-line-title" style="width: 120px; height: 16px;"></span>
                                    <span class="skeleton-box skeleton-seat-badge" style="width: 65px; height: 22px; border-radius: 5px;"></span>
                                </div>
                                <div class="detail-row d-flex align-items-center gap-2 mt-1">
                                    <span class="skeleton-box" style="width: 60px; height: 12px; border-radius: 3px;"></span>
                                    <span class="skeleton-box" style="width: 20px; height: 20px; border-radius: 4px;"></span>
                                    <span class="skeleton-box" style="width: 22px; height: 22px; border-radius: 50%;"></span>
                                    <span class="skeleton-box" style="width: 20px; height: 20px; border-radius: 4px;"></span>
                                    <span class="skeleton-box" style="width: 20px; height: 20px; border-radius: 4px;"></span>
                                </div>
                            </div>
                        </div>
                        <span class="skeleton-box" style="width: 16px; height: 16px; border-radius: 4px; margin-left: 6px;"></span>
                    </div>

                    {{-- 2. Expiry Banner --}}
                    <div class="skeleton-box skeleton-mobile-banner"></div>

                    {{-- 3. Actions Container & Full-Width Button --}}
                    <div class="skeleton-mobile-actions-wrap">
                        <div class="skeleton-mobile-actions-head">
                            <span class="skeleton-box" style="width: 55px; height: 13px; border-radius: 3px;"></span>
                            <span class="skeleton-box" style="width: 48px; height: 12px; border-radius: 3px;"></span>
                        </div>
                        <div class="skeleton-mobile-action-scroll">
                            <span class="skeleton-box skeleton-mobile-action-tile"></span>
                            <span class="skeleton-box skeleton-mobile-action-tile"></span>
                            <span class="skeleton-box skeleton-mobile-action-tile"></span>
                            <span class="skeleton-box skeleton-mobile-action-tile"></span>
                            <span class="skeleton-box skeleton-mobile-action-tile"></span>
                            <span class="skeleton-box skeleton-mobile-action-tile"></span>
                        </div>
                        <div class="skeleton-box skeleton-mobile-btn-full"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    @endfor
</div>
