<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
     // Handle state and city dropdowns
     $('#stateid').on('change', function(event){
            event.preventDefault();
            var state_id = $(this).val();
            console.log(state_id);
            if(state_id){
                $.ajax({
                    url: '{{ route('cityGetStateWise') }}',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    },
                    type: 'GET',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        "state_id": state_id,
                    },
                    dataType: 'json',
                    success: function(html) {
                        if(html){
                            $("#cityid").empty();
                            $("#cityid").append('<option value="">Select City</option>');
                            $.each(html, function(key, value){
                                var selected = (key == "{{ old('city_id', isset($student) ? $student->city_id : '') }}") ? 'selected' : '';
                                $("#cityid").append('<option value="'+key+'" '+selected+'>'+value+'</option>');
                            });
                        }else{
                            $("#cityid").append('<option value="">Select City</option>');
                        }
                    }
                });
            } else {
                $("#cityid").empty();
                $("#cityid").append('<option value="">Select City</option>');
            }
    });

    $(document).on('click', '.delete-learners, .delete-masters', function() {
        var id = $(this).data('id');
        var url;

        // Determine which button was clicked and set the URL accordingly
        if ($(this).hasClass('delete-learners')) {
            url = '{{ route('library.learners.destroy', ':id') }}';
        } else if ($(this).hasClass('delete-masters')) {
            
            url = '{{ route('library.masters.destroy', ':id') }}';
        }
      
    
        url = url.replace(':id', id);  
       
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire(
                            'Deleted!',
                            response.message, // Show the message from the server response
                            'success'
                        ).then(() => {
                            location.reload(); // Optionally, you can refresh the page
                        });
                    },
                    error: function(xhr, status, error) {
                        let errorMessage = 'An error occurred while deleting the learners.';

                        // Optionally, you can parse the response for a more specific error message
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        Swal.fire(
                            'Error!',
                            errorMessage,
                            'error'
                        );
                    }
                });
            }

        });
    });
    
    $(document).ready(function() {
        let table = new DataTable('#datatable');
       
    });

    $(document).ready(function () {
        // Handle status selection
        $('.statusDropdown').on('change', function(event)  {
       
            var status = $(this).val();

            var rowId = $(this).data('row-id');
    
            var remark=null;
            // If clarification is selected, show the modal
            if (status == '3') {
                $('#clarificationModal').modal('show');
                $('#submitStatus').data('row-id', rowId); 
            } else {
            
                $('#clarificationModal').modal('hide');
                clarificationStatus(rowId,status,remark)
            }
            // Save the row ID in a hidden input field to submit later
            $('#submitStatus').data('row-id', rowId);
        
            
        });

        // Close the modal when clicking the close button
        $('.close-btn').click(function () {
            $('#clarificationModal').hide();
        });

        // Submit status using AJAX
        $('#submitStatus').click(function () {
            var rowId = $(this).data('row-id');
            var status = $('.statusDropdown[data-row-id="'+rowId+'"]').val();  // Get the status for the specific row
            var remark = $('#remarkText').val();
            
            if (status == '3' && remark.trim() === "") {
                alert('Please provide remarks for clarification.');
                return;
            }

            clarificationStatus(rowId, status, remark);
        });

        function clarificationStatus(rowId,status,remark){
            $.ajax({
                url: '{{ route('clarification.submit.status') }}', 
                method: 'POST',
                data: {
                    row_id: rowId, 
                    status: status,
                    remark: remark,
                    _token: '{{ csrf_token() }}' 
                },
                success: function (response) {
                console.log(response);
                    
                    $('.statusDropdown').val('');
                    $('#remarkText').val(''); 
                    $('#clarificationModal').modal('hide');
                    if (response.success) {
                        toastr.success('Status updated successfully!');
                        $('#datatable').DataTable().ajax.reload(null, false);
                    } else {
                        toastr.error('Failed to update status.');
                    }
                },
                error: function () {
                    alert('An error occurred. Please try again.');
                }
            });
        }
    });





  
</script>