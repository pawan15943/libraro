@extends('layouts.library')

@section('title', 'How to use')

@section('content')

<style>
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
        border: none;
        box-shadow: 1px 0 5px #00000021;
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
    }
</style>


<h2 class="mb-4 text-center fw-bold">Libraro — Operations Guide</h2>

<!-- Tabs -->
<ul class="nav nav-tabs justify-content-center" id="langTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="english-tab" data-bs-toggle="tab"
            data-bs-target="#english" type="button" role="tab">
            English
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="hindi-tab" data-bs-toggle="tab"
            data-bs-target="#hindi" type="button" role="tab">
            हिन्दी
        </button>
    </li>
</ul>

<div class="tab-content mt-3" id="langTabsContent">

    <!-- ENGLISH TAB -->
    <div class="tab-pane fade show active" id="english" role="tabpanel">
        <div class="accordion" id="engAccordion">

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
                        <pre class="usage">{{ $content->usage_english }}</pre>
                    </div>
                </div>
            </div>

            @endforeach

        </div>
    </div>

    <!-- HINDI TAB -->
    <div class="tab-pane fade" id="hindi" role="tabpanel">
        <div class="accordion" id="hinAccordion">

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
                        <pre class="usage">{{ $content->usage_hindi }}</pre>
                    </div>
                </div>
            </div>

            @endforeach

        </div>
    </div>

</div>


@endsection