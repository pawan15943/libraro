@extends('layouts.library')

@section('title', 'How to use')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@300;400;600;700&family=Mukta:wght@300;400;600&display=swap" rel="stylesheet">
<style>
    :root {
        --font-hindi: "Noto Sans Devanagari", "Mukta", "Hind", "Lohit Devanagari", "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
        --base-size: 16px;
    }

    /* Make tabs look like large pill buttons */
    .nav-tabs {
        border-bottom: none;
        margin-bottom: 1rem;
        gap: 10px;
    }

    button.accordion-button.collapsed {
        font-weight: 600;
        font-size: 1rem;
    }

    .nav-tabs .nav-link {
        background: #f8f9fa;
        border-radius: 50px;
        padding: 10px 25px;
        font-size: 18px;
        border: 1px solid #dee2e6;
        transition: 0.3s ease;
    }

    .nav-tabs .nav-link.active {
        background: #0d6efd;
        color: white !important;
        border-color: #0d6efd;
    }

    .nav-tabs .nav-link:hover {
        background: #e9ecef;
    }

    /* Accordion Styling */
    .accordion-item {
        border-radius: .5rem !important;
        margin-bottom: 13px;
        overflow: hidden;
        border: 1px solid #e3e3ff !important;
    }

    .accordion-button {
        font-size: 18px;
        padding: 15px 20px;
    }

    .accordion-button:not(.collapsed) {
        background-color: #e7f1ff;
        color: #0d6efd;
        box-shadow: none;
    }

    .accordion-body {
        font-size: 17px;
        line-height: 1.6;
        padding: 20px;
    }

    /* Pre text formatting */
    pre.usage {
        white-space: pre-wrap;
        font-family: 'Outfit', 'sans-sarif';
        font-size: .9rem;
        margin: 0;
        color: #403f3f;
        font-weight: 600;
    }

    .accordion-button:not(.collapsed) {
        background-color: #e7f1ff;
        color: navy;
        box-shadow: none;
        font-size: 1rem;
        font-weight: 500;
    }

    .nav-tabs .nav-link {
        background: white;
        color: black !important;
        border-color: white;
        border-radius: .5rem;
        font-family: 'Outfit', 'sans-sarif';
        font-size: .9rem;
    }

    .nav-tabs .nav-link.active {
        background: navy;
        color: white !important;
        border-color: navy;
        border-radius: .5rem;
        font-family: 'Outfit', 'sans-sarif';
        font-size: .9rem;
    }

    p.m-0.mb-4.descrpition {
        padding: 1rem;
        font-size: .9rem;
        font-family: 'Outfit', sans-serif;
        color: #3F51B5;
        background: #f0f0ff;
        border-radius: .5rem;
        margin-bottom: 1.5rem !important;
        font-weight: 400;
        border: 1px solid #e1e5ff;
    }

    button.accordion-button.collapsed {
        color: #000080;
        font-weight: 500;
    }
</style>

<div class="row justify-content-center mb-4">
    <div class="col-lg-8">
        <!-- Tabs -->
        <ul class="nav nav-tabs justify-content-center" id="langTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="english-tab" data-bs-toggle="tab"
                    data-bs-target="#english" type="button" role="tab">
                    Library Guide – English
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="hindi-tab" data-bs-toggle="tab"
                    data-bs-target="#hindi" type="button" role="tab">
                    Library Guide – Hindi
                </button>
            </li>
        </ul>

        <div class="tab-content mt-3" id="langTabsContent">

            <!-- ENGLISH TAB -->
            <div class="tab-pane fade show active" id="english" role="tabpanel">
                <div class="accordion" id="engAccordion">
                    <p class="m-0 mb-4 descrpition">Libraro helps you manage your library easily by organizing seats, learners, plans, payments, and daily operations in one place. Follow the steps below to use each feature smoothly.</p>

                    @foreach($howtoUseContent as $content)
                    @php $uid = 'eng'.$content->id; @endphp

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading-{{ $uid }}">
                            <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#collapse-{{ $uid }}">
                                {{ $content->operation_name }}
                            </button>
                        </h2>
                        <div id="collapse-{{ $uid }}" class="accordion-collapse collapse"
                            data-bs-parent="#engAccordion">
                            <div class="accordion-body">
                                <pre class="usage" style="font-weight: 500;">{{ $content->usage_english }}</pre>
                            </div>
                        </div>
                    </div>

                    @endforeach

                </div>
            </div>

            <!-- HINDI TAB -->
            <div class="tab-pane fade" id="hindi" role="tabpanel">
                <div class="accordion" id="hinAccordion">
                    <p class="m-0 mb-4 descrpition" style="font-family: var(--font-hindi);">Libraro आपकी लाइब्रेरी को आसानी से Manage करने में मदद करता है। यहाँ आप सीट, छात्र, प्लान, पेमेंट और रोज़मर्रा के सभी काम एक ही जगह से संभाल सकते हैं। नीचे दिए गए चरणों की मदद से आप हर फीचर को आसानी से उपयोग कर सकते हैं।</p>

                    @foreach($howtoUseContent as $content)
                    @php $uid = 'hin'.$content->id; @endphp

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading-{{ $uid }}">
                            <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#collapse-{{ $uid }}">
                                {{ $content->operation_name }}
                            </button>
                        </h2>
                        <div id="collapse-{{ $uid }}" class="accordion-collapse collapse"
                            data-bs-parent="#hinAccordion">
                            <div class="accordion-body">
                                <pre class="usage" style="font-family: var(--font-hindi);">{{ $content->usage_hindi }}</pre>
                            </div>
                        </div>
                    </div>

                    @endforeach

                </div>
            </div>

        </div>

    </div>
</div>




@endsection