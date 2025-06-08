
<div class="row">
    <div class="d-flex bradcrumb">
        <h4>{{ $pageTitle ?? '' }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
               @if(isset($breadcrumb))
             
                @foreach($breadcrumb as $name => $link)
                <li class="breadcrumb-item"><a href="{{ $link }}">{{ $name }}</a></li>
                @endforeach
                   
               @else
                
               @endif
            </ol>
        </nav>
    </div>
</div>