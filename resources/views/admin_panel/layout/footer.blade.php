    {{--  @yield('scripts')  --}}

    <footer>
        <div class="footer-area">
            <p>&copy; Copyright 2025. All right reserved. Wijdan.</p>
        </div>
    </footer>
    </div>
   
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="{{ asset('assets/js/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>


<script src="{{ asset('assets/js/owl.carousel.min.js') }}"></script>


<script src="{{ asset('assets/js/metisMenu.min.js') }}"></script>


<script src="{{ asset('assets/js/jquery.slimscroll.min.js') }}"></script>


<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>


<script src="{{ asset('assets/js/jquery.slicknav.min.js') }}"></script>


<script src="{{ asset('assets/vendors/am-charts/js/ammap.js') }}"></script>
<script src="{{ asset('assets/vendors/am-charts/js/worldLow.js') }}"></script>
<script src="{{ asset('assets/vendors/am-charts/js/continentsLow.js') }}"></script>
<script src="{{ asset('assets/vendors/am-charts/js/light.js') }}"></script>
<script src="{{ asset('assets/js/am-maps.js') }}"></script>


<script src="{{ asset('assets/vendors/charts/morris-bundle/raphael.min.js') }}"></script>
<script src="{{ asset('assets/vendors/charts/morris-bundle/morris.js') }}"></script>


<script src="{{ asset('assets/vendors/charts/charts-bundle/Chart.bundle.js') }}"></script>


<script src="{{ asset('assets/vendors/charts/c3charts/c3.min.js') }}"></script>
<script src="{{ asset('assets/vendors/charts/c3charts/d3-5.4.0.min.js') }}"></script>


<script src="{{ asset('assets/vendors/data-table/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/vendors/data-table/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('assets/vendors/data-table/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('assets/vendors/data-table/js/responsive.bootstrap.min.js') }}"></script>


<script src="{{ asset('assets/vendors/charts/sparkline/jquery.sparkline.js') }}"></script>


<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>


<script src="{{ asset('assets/js/home.js') }}"></script>
<script src="{{ asset('assets/js/main.js') }}"></script>


    <script>

        $(document).ready(function() {
            $('#datatable').DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, "All"]
                ],
                order: [], // 👈 disable DataTables default order
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search.."
                }
            });
        });
        function showAlert(title, text, icon) {
            Swal.fire({
                title: title,
                html: text,
                icon: icon,
            });
        }


        function logoutAndDeleteFunction(e) {
            var msg = e.getAttribute("data-msg");
            var method = e.getAttribute("data-method");
            var url = e.getAttribute("data-url");

            swal.fire({
                    title: "Are you sure?",
                    text: msg,
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: 'continue',
                    cancelButtonText: 'cancel',
                    dangerMode: true,
                })
                .then((result) => {
                    if (result.isConfirmed) {
                        yourFunction(url, method);
                    } else {
                        swal("Your account is safe!");
                    }
                });

        }

        function yourFunction(url, method) {
            $.ajax({
                url: url,
                type: method,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response['reload'] != undefined) {
                        showAlert("Success", response.success, "success");
                        window.location.reload();
                    }
                    if (response['redirect'] != undefined) {
                        showAlert("Success", response.success, "success");
                        window.location.href = response['redirect'];
                    }
                },
                error: function(xhr, status, error) {
                    // Handle errors
                }
            });
        }

        function multipleerrorshandle(errors) {
            let message = '';
            for (var errorkey in errors) {
                message += "<span style='color:red'>" + errors[errorkey] + "</span><br>";
            }
            showAlert('Errors', message, 'error');
        }

        function ajaxErrorHandling(data, msg) {
            if (data.hasOwnProperty("responseJSON")) {
                var resp = data.responseJSON;
                if (resp.message == 'CSRF token mismatch.') {
                    showAlert("Page has been expired and will reload in 2 seconds", "Page Expired!", "error");
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                    return;
                }
                if (resp.error) {
                    var msg = (resp.error == '') ? 'Something went wrong!' : resp.error;
                    showAlert(msg, "Error!", "error");
                    return;
                }
                if (resp.message != 'The given data was invalid.') {
                    showAlert(resp.message, "Error!", "error");
                    return;
                }
                multipleerrorshandle(resp.errors);
            } else {
                showAlert(msg + "!", "Error!", 'error');
            }
            return;
        }
        //post
        function myAjax(url, formData, method = 'post', callback) {
            $.ajax({
                url: url,
                method: method,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: formData,
                contentType: false,
                processData: false,
                dataType: "json",
                complete: function(data) {},
                success: function(data) {
                    if (data['reload'] != undefined) {
                        showAlert("Success", data.success, "success");
                        window.location.reload();
                        return false;
                    }
                    if (data['redirect'] != undefined) {
                        showAlert("Success", data.success, "success");
                        window.location.href = data['redirect'];
                        return false;
                    }
                    if (data['error'] !== undefined) {
                        var text = "<span style='color:red'>" + data['error'] + "</span>";
                        showAlert('Error', text, 'error');
                        return false;
                    }
                    if (data['errors'] !== undefined) {
                        multipleerrorshandle(data['errors'])
                        return false;
                    }

                    callback(data)
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    ajaxErrorHandling(jqXHR, errorThrown);
                },

            });
        }
    </script>

    @if(auth()->check() && auth()->user()->email === 'admin@admin.com')
    <script>
        // Poll for Inward Notifications every 10 seconds
        setInterval(function(){
            $.get("{{ route('notifications.check_inwards') }}", function(data){
                if(data && data.length > 0){
                    
                    let count = data.length;
                    let last = data[0];
                    let vendorName = last.vendor ? last.vendor.name : 'Unknown';
                    let warehouseName = last.warehouse ? last.warehouse.warehouse_name : (last.receive_type == 'shop' ? 'Shop' : 'N/A');
                    
                    let msg = `New Inward (${last.invoice_no || last.id})\nVendor: ${vendorName}\nDest: ${warehouseName}`;
                    
                    // Show Notification
                    Swal.fire({
                        title: 'New Inward Created!',
                        text: msg,
                        icon: 'info',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: true,
                        confirmButtonText: 'Check Now',
                        showCancelButton: true,
                        cancelButtonText: 'Dismiss',
                        timer: 15000,
                        timerProgressBar: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "{{ route('InwardGatepass.home') }}";
                        }
                    });

                    // Mark as notified immediately to prevent repeat
                    let ids = [];
                    data.forEach(function(item){ ids.push(item.id); });

                    $.ajax({
                        url: "{{ route('notifications.mark_inwards') }}",
                        type: "POST",
                        data: { 
                            ids: ids, 
                            _token: "{{ csrf_token() }}" 
                        },
                        success: function(res){
                            console.log('Marked notified: ', ids);
                        }
                    });
                }
            });
        }, 10000); 
    </script>

    <script>
        // Poll for Stock Transfer Notifications every 12 seconds
        setInterval(function(){
            $.get("{{ route('notifications.check_transfers') }}", function(data){
                if(data && data.length > 0){
                    let last = data[0];
                    let from = last.from_warehouse ? last.from_warehouse.warehouse_name : 'Shop';
                    let to = last.to_warehouse ? last.to_warehouse.warehouse_name : (last.transfer_to == 'shop' ? 'Shop' : 'N/A');
                    
                    let msg = `New Stock Transfer!\nFrom: ${from}\nTo: ${to}`;
                    
                    // Show Notification
                    Swal.fire({
                        title: 'Stock Transfer Alert!',
                        text: msg,
                        icon: 'info',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: true,
                        confirmButtonText: 'Check Now',
                        showCancelButton: true,
                        cancelButtonText: 'Dismiss',
                        timer: 15000,
                        timerProgressBar: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "{{ route('stock_transfers.index') }}";
                        }
                    });

                    // Mark as notified
                    let ids = [];
                    data.forEach(function(item){ ids.push(item.id); });

                    $.ajax({
                        url: "{{ route('notifications.mark_transfers') }}",
                        type: "POST",
                        data: { 
                            ids: ids, 
                            _token: "{{ csrf_token() }}" 
                        },
                        success: function(res){ 
                            console.log('Marked transfer notified: ', ids); 
                        }
                    });
                }
            });
        }, 12000); 
    </script>
    @endif

