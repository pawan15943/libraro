
<div class="row">
    <div class="d-flex bradcrumb">
        <h4>{{ $pageTitle }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">@php
                @endphp
                @foreach($breadcrumb as $name => $link)
                <li class="breadcrumb-item"><a href="{{ $link }}">{{ $name }}</a></li>
                @endforeach
            </ol>
        </nav>
    </div>
</div>