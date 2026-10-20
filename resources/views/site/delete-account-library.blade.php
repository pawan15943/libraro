<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account & Data Deletion Policy - Libraro Library Management</title>
    <meta name="description" content="Account and Data Deletion Policy for Libraro Library Management mobile application. Learn how library owners can request permanent account and data deletion.">
    <link rel="icon" href="{{ asset('public/img/favicon.ico') }}" type="image/x-icon">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- Scoped Custom Stylesheet with cache-busting -->
    <link rel="stylesheet" href="{{ asset('public/css/account-deletion.css') }}?v={{ time() }}">
</head>
<body class="account-deletion-body">

<div class="account-deletion-module">
    <div class="main-container">
        <div class="content-box">
            
            <!-- Brand Logo -->
            <div class="brand-logo-wrapper">
                <a href="{{ url('/') }}" title="Libraro">
                    <img src="{{ asset('public/img/libraro.webp') }}" alt="Libraro" class="brand-logo-img">
                </a>
            </div>

            <!-- Page Title & App Name -->
            <h1 class="page-title">Account &amp; Data Deletion Policy</h1>
            <p class="app-name-tag">App Name: <strong>Libraro - Library Management</strong></p>

            <!-- App Switcher Tabs -->
            <div class="app-switcher-wrapper">
                <a href="{{ url('delete-account/learner') }}" class="app-switch-btn">
                    <i class="fa-solid fa-user-graduate"></i> Libraro Learner App
                </a>
                <a href="{{ url('delete-account/library') }}" class="app-switch-btn active">
                    <i class="fa-solid fa-book-open-reader"></i> Libraro Library App
                </a>
            </div>

            <hr class="header-divider">

            <!-- Section 1: How to Delete Your Account -->
            <h4 class="section-title">
                <i class="fa-solid fa-trash-can"></i> How to Delete Your Account
            </h4>
            <p>If you are an authorized Library Owner wishing to permanently delete your <strong>Libraro Library Management</strong> account and associated library operational data, you can do so directly from our mobile application:</p>
            
            <ul class="steps-list">
                <li class="step-item">
                    <div class="step-number">1</div>
                    <div class="step-text">Open the <strong>Libraro</strong> app on your mobile device.</div>
                </li>
                <li class="step-item">
                    <div class="step-number">2</div>
                    <div class="step-text">Log in with your registered <strong>Primary Library Owner</strong> credentials.</div>
                </li>
                <li class="step-item">
                    <div class="step-number">3</div>
                    <div class="step-text">Go to the <strong>Profile</strong> or <strong>Settings</strong> section from the main navigation menu.</div>
                </li>
                <li class="step-item">
                    <div class="step-number">4</div>
                    <div class="step-text">Click on the <strong>"Delete Account"</strong> button.</div>
                </li>
                <li class="step-item">
                    <div class="step-number">5</div>
                    <div class="step-text">Enter your account password to verify ownership and confirm your request when prompted.</div>
                </li>
            </ul>

            <div class="support-note">
                <i class="fa-solid fa-circle-info me-1"></i>
                <em>Alternatively, you can contact our support team at <a href="mailto:support@libraro.in">support@libraro.in</a> or call <a href="tel:+918114479678">+91-8114479678</a> from your registered business email or mobile number to request account deletion on your behalf.</em>
            </div>

            <!-- Section 2: What Data Will Be Deleted -->
            <h4 class="section-title">
                <i class="fa-solid fa-eraser"></i> What Data Will Be Deleted
            </h4>
            <p>Upon account deletion, the following library and personal information will be permanently erased or deactivated from our active servers:</p>
            <ul class="data-list">
                <li><strong>Library &amp; owner profile:</strong> Primary Owner Name, Mobile Number, Official Business Email, and Library profile details.</li>
                <li><strong>Branch &amp; floor layouts:</strong> Branch details, floor plans, seat layouts, shift configurations, and subscription fee setups.</li>
                <li><strong>Staff &amp; operator accounts:</strong> Sub-user accounts, operational roles, operator credentials, and custom permissions.</li>
                <li><strong>Learner operational records:</strong> Enrolled learner profiles, seat allocations, and daily operational attendance logs linked to the library.</li>
                <li><strong>Security &amp; credentials:</strong> Active session tokens, push notification device tokens, API authentication keys, and login credentials.</li>
            </ul>

            <!-- Section 3: What Data May Be Retained -->
            <h4 class="section-title">
                <i class="fa-solid fa-shield-halved"></i> What Data May Be Retained
            </h4>
            <p>For legal, tax, and auditing purposes, we are required to retain certain transactional data even after your account is deleted. This includes:</p>
            <ul class="data-list">
                <li><strong>Invoices &amp; subscription records:</strong> Subscription payment receipts, GST tax invoices, and accounting ledgers (for financial and tax compliance).</li>
                <li><strong>Payment transaction records:</strong> Payment gateway transaction references and order identifiers.</li>
                <li><strong>Regulatory audit logs:</strong> Fraud prevention, dispute resolution, and security access logs as required by applicable laws.</li>
            </ul>

            <!-- Section 4: Data Retention Period -->
            <h4 class="section-title">
                <i class="fa-solid fa-clock-rotate-left"></i> Data Retention Period
            </h4>
            <p>The retained transactional data mentioned above is kept securely in our encrypted archives for a minimum period of <strong>7 years</strong>, as required by local laws and tax regulations. After this mandatory retention period expires, the data is permanently and securely destroyed.</p>

            <!-- Support & Assistance Box -->
            <div class="assistance-card">
                <div class="assistance-header">
                    <i class="fa-solid fa-headset"></i>
                    <h6>Need Help or Support with Account Deletion?</h6>
                </div>
                <p class="mb-2">If you have any questions or are unable to access the app, our support team is available to assist you:</p>
                <div class="assistance-contacts">
                    <a href="mailto:support@libraro.in" class="contact-pill">
                        <i class="fa-solid fa-envelope"></i> support@libraro.in
                    </a>
                    <a href="tel:+918114479678" class="contact-pill">
                        <i class="fa-solid fa-phone"></i> +91-8114479678
                    </a>
                    <span class="contact-pill text-muted">
                        <i class="fa-solid fa-location-dot"></i> Kota, Rajasthan 324005
                    </span>
                </div>
            </div>

            <!-- Back Button -->
            <div class="action-footer">
                <a href="{{ url('/') }}" class="btn-back">
                    <i class="fa-solid fa-arrow-left"></i> Back to Libraro
                </a>
            </div>

        </div>
    </div>
</div>

</body>
</html>
