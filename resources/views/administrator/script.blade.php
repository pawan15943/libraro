<script>
    function getFees(library_type, month) {
        $.ajax({
            url: '{{ route('get.subscription.fees')}}',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            },
            type: 'GET',
            data: {
                "_token": "{{ csrf_token() }}",
                "library_type": library_type,
                "month": month,
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Populate order summary
                    $('#amount_paid').text(response.fees);
                    $('#discount').text(response.discount);
                    $('#gst').text(response.gst);
                    $('#total_amount').text(response.paid_amount);

                    // Populate paid amount input field
                    $('#paid_amount').val(response.paid_amount);
                    $('#amount').val(response.fees);
                } else {
                    // Reset all fields if there's an error
                    $('#amount_paid, #discount, #gst, #total_amount').text('0');
                    $('#paid_amount').val('');
                    alert(response.message);
                }
            }
        });
    }
</script>