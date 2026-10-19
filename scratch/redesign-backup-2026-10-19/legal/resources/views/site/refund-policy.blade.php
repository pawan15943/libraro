@extends('lb.layouts.site')

@section('content')
    @include('lb.partials.page-hero', ['heading' => 'Refund Policy'])
    <div class="lb-page-body">
        <div class="lb-page-card lb-prose">
            <h6>Refund Policy</h6>
                            <ul>
                                <li>We have a strict no-refund policy for our library management software.</li>
                                <li>Refunds will only be considered in critical circumstances, such as:
                                    <ul>
                                        <li>Major technical issues that cannot be resolved by our support team.</li>
                                        <li>Failure of the software to function as described on our website.</li>
                                    </ul>
                                </li>
                                <li>All refund requests must be reviewed and approved by our team.</li>
                                <li>To request a refund under exceptional circumstances, please contact us at <a href="mailto:support@libraro.in">support@libraro.in</a>.</li>
                            </ul>
        </div>
    </div>
@endsection
