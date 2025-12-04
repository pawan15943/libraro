<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management Software Subscription Receipt</title>
    <style>
        body {
            font-size: 13px;
            color: #333;
            font-family: 'Roboto', sans-serif;
        }

        table,
        th,
        td {
            border: 1px solid black;
            border-collapse: collapse;
            padding: 10px 12px;
        }

        h2,
        h3,
        h4,
        h5,
        h6 {
            margin: 0px;
            font-weight: 700;
        }

        p {
            line-height: 22px;
            font-size: 13px;
        }

        b {
            color: #000;
        }

        .tab_title {
            font-size: 21px;
            font-family: 'Roboto', sans-serif;
        }

        .logo img {
            margin-top: 15px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: left;
        }

        .receipt_header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 30px;
        }

        .address_header h4 {
            color: #000;
            font-size: 25px;
            margin-bottom: 15px;
            font-weight: 700;
            font-family: 'Roboto', sans-serif;

        }

        .address_header .address {
            max-width: 270px;
            font-size: 14px;
            line-height: 22px;
        }

        .address_header a {
            color: #333;
            text-decoration: none;
        }

        .pdf_descContent li,
        .pdf_descContent p {
            line-height: 26px;
        }
    </style>
</head>

<body>
    
    <div class="receipt_wrapper">
        <!-- header -->
        <div class="receipt_header">
          
            <div class="logo" style="display: flex; gap:1rem; align-items:center;">
               @php
                if($branch_logo && file_exists(public_path($branch_logo))){
                    $logo = base64_encode(file_get_contents(public_path($branch_logo)));
                } else {
                    $logo = base64_encode(file_get_contents(public_path('img/logo-socials.png')));
                }
            @endphp

            <img src="data:image/png;base64,{{ $logo }}" style="width:80px;height:80px;border-radius:100%;">

               
            </div>
            <div class="address_header text-right">
                <h4 style="text-transform: uppercase; margin-top:1rem;"><?php echo isset($library_name) ? $library_name : ''; ?></h4>
                <div class="address">
                    <p>Address : <?php echo isset($library_address) ? $library_address : ''; ?></p>
                </div>
                <a href="mailto:<?php echo isset($library_email) ? $library_email : ''; ?>" title="Library Email Id">
                    Email: <?php echo isset($library_email) ? $library_email : ''; ?>
                </a><br>
                <a href="tel:<?php echo isset($library_mobile) ? $library_mobile : ''; ?>" title="Library Contact info">
                    Contact: <?php echo isset($library_mobile) ? $library_mobile : ''; ?>
                </a><br>
                @if($branch_slug)
                    <a href="{{url('library-detail/'.$branch_slug)}}" title="Library System">Website: {{url('library-detail/'.$branch_slug)}}</a>
                @else
                    <a href="https://www.libraro.in/" title="Library System">Website: https://www.libraro.in/</a>
                @endif
                
                
                <br>
            </div>
        </div>

        <!-- Main content-->
        <table>
            <thead class="text-center">
                <tr>
                    <th colspan="4" class="tab_title">Transaction Receipt</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="width:30%"><b> Plan:</b></td>
                    <td style="width:15%">
                        <?php echo isset($subscription) ? $subscription : ''; ?>
                    </td>
                    <td style="width:45%"><b>Plan Start Date:</b></td>
                    <td style="width:15%">
                        <?php echo isset($transactiondate) ? $transactiondate : ''; ?>
                    </td>
                </tr>
                <tr>
                    <td><b>Invoice Number:</b></td>
                    <td>
                        <?php echo isset($invoice_ref_no) ? $invoice_ref_no : ''; ?>
                    </td>
                    <td><b>Plan End Date:</b></td>
                    <td>
                        <?php echo isset($end_date) ? $end_date : ''; ?>
                    </td>
                </tr>
                <tr>
                    <td><b>Name</b></td>
                    <td colspan="3">
                        <?php echo isset($name) ? $name : ''; ?>
                    </td>
                </tr>
                <tr>
                    <td><b>Email Address:</b></td>
                    <td colspan="3">
                        <?php echo isset($email) ? $email : 'Not Updated Yet'; ?>
                    </td>
                </tr>
                <tr>
                    <td><b>Payment Type:</b></td>
                    <td>
                        <?php
                        if (isset($payment_mode)) {
                            if ($payment_mode == 1) {
                                echo 'Online';
                            } elseif ($payment_mode == 2) {
                                echo 'Offline';
                            } else {
                                echo 'Pay Later';
                            }
                        } else {
                            echo '';
                        }
                        ?>
                    </td>
                    <td><b>Amount Paid:</b></td>
                    <td>
                        <?php echo isset($paid_amount) ? $paid_amount : ''; ?>
                    </td>
                </tr>
                <tr>
                    <td><b>Total Amount:</b></td>
                    <td>
                        <?php echo isset($monthly_amount) ? $monthly_amount : ''; ?>
                    </td>
                    <td><b>Plan Duration:</b></td>
                    <td>
                        <b><?php echo isset($month) ? $month : ''; ?> Month</b>
                    </td>
                </tr>
                <tr>
                    <td colspan="4">
                        <h4>Terms & Conditions</h4>
                        <ul class="pdf_descContent">
                            <li>This receipt is not a VAT Invoice.</li>
                            <li>VAT Invoice will be provided upon request within 30 days.</li>
                            <li>This is a computer-generated receipt; no signature is required.</li>
                            <li>All subscription plans (Basic, Standard, and Premium) are non-refundable and non-transferable.</li>
                            <li>Plan upgrades are available at any time with additional charges applied.</li>
                        </ul>

                        <h4>Refund Policy</h4>
                        <ul class="pdf_descContent">
                            <li>No refunds will be issued once the subscription is activated. Please review your plan carefully before making a purchase.</li>
                        </ul>
                    </td>
                </tr>
            </tbody>
        </table>

        <table style="width: 100%;">
          <tr>
            <td>
              <p style="text-align: Center;"><b>Website :</b> www.libraro.in | <b>Call Us :</b> <a href="+91-8114479678">+91-8114479678</a></p>
              <p style="text-align: Center;"><b>HEAD OFFICE :</b> KOTA RAJASTHAN, 324005</p>
            </td>
          </tr>
        </table>
    </div>
</body>


</html>