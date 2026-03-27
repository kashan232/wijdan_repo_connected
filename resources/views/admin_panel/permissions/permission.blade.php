 @extends('admin_panel.layout.app')
 @section('content')

 <div class="main-content">
     <div class="main-content-inner">
         <div class="container">
             <div class="row">
                 <div class="col-lg-12">
                     <div class="d-flex justify-content-between align-items-center mb-3">
                         <h3>Permissions</h3>
                     </div>
                     <div class="border mt-1 shadow rounded " style="background-color: white;">
                         <div class="col-lg-12 m-auto">
                             <div class="table-responsive mt-5 mb-5 ">
                                 <table id="default-datatable" class="table ">
                                     <thead class="text-center">
                                         <tr>
                                             <th class="text-center">Id</th>
                                             <th class="text-center">Name</th>
                                         </tr>
                                     </thead>
                                     <tbody class="text-center">
                                         @foreach ($permissions as $permission)
                                         <tr>
                                             {{-- <span class="d-none edit-id">{{ $permission->id }}</span> --}}
                                             <td class="d-none">
                                                 <input type="hidden" class="edit-id" value="{{ $permission->id }}">
                                             </td>
                                             <td class="id">{{ $permission->id }}</td>
                                             <td class="name">{{ $permission->name }}</td>
                                             
                                         </tr>
                                         @endforeach
                                     </tbody>
                                 </table>
                             </div>

                         </div>

                     </div>
                 </div>

             </div>
         </div>
     </div>
 </div>
 </div>
 </div>

 <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
     <div class="modal-dialog">
         <div class="modal-content">
             <div class="modal-header">
                 <h5 class="modal-title" id="exampleModalLabel">Add Permissions</h5>
             </div>
             <div class="modal-body">
                 <form class="myform" action="{{ route('permissions.store') }}" method="POST">
                     @csrf
                     <input type="hidden" name="edit_id" id="id" />
                     <div class="mb-3">
                         <label for="title" class="form-label">Name</label>
                         <input type="text" name="name" class="form-control" id="name" />
                     </div>
             </div>
             <div class="modal-footer">
                 <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                 <input type="submit" class="btn btn-primary save-btn">
             </div>
             </form>
         </div>
     </div>
 </div>
 <!-- DataTable CSS -->
 <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

 <!-- jQuery -->
 <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

 <!-- DataTable JS -->
 <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
 <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
 <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
 <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
 <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
 <script src="{{ asset('assets/js/mycode.js') }}"> </script>
 <script>
     $(document).on('submit', '.myform', function(e) {
         // alert("as");
         e.preventDefault();
         var formdata = new FormData(this);
         url = $(this).attr('action');
         method = $(this).attr('method');
         $(this).find(':submit').attr('disabled', true);
         myAjax(url, formdata, method);
     });
     $(document).on('click', '.edit-btn', function() {
         $(".modal-title").text("Edit Permissions");
         var tr = $(this).closest("tr");
         var id = tr.find(".edit-id").val();
         // alert(id);
         var name = tr.find(".name").text();
         // var address = tr.find(".address").text();
         // var number = tr.find(".number").text();
         // var email = tr.find(".email").text().trim(); 
         $('#id').val(id);
         $('#name').val(name)
         $("#exampleModal").modal("show")


     });


     function confirmedBox(element, event) {
         event.preventDefault(); // Stop immediate redirect

         const message = element.getAttribute('data-msg') || 'Are you sure?';
         const url = element.getAttribute('href');

         Swal.fire({
             title: 'Confirm Deletion',
             text: message,
             icon: 'warning',
             showCancelButton: true,
             confirmButtonText: 'Yes, delete it!',
             cancelButtonText: 'Cancel',
             confirmButtonColor: '#d33',
             cancelButtonColor: '#3085d6'
         }).then((result) => {
             if (result.isConfirmed) {
                 // Redirect manually after confirmation
                 window.location.href = url;
             }
         });
     }

     $(document).on('click', '#reset-form', function() {
         // alert("sd");
         // Manually clear inputs
         $('#id').val('');
         $('#name').val('');
         $("#exampleModal").modal("show")
     });
 </script>
 @if(session('success'))
 <script>
     Swal.fire({
         icon: 'success',
         title: 'Success',
         text: "{{ session('success') }}",
         timer: 2000,
         showConfirmButton: false
     });
 </script>
 @endif
 <script>
     $(document).ready(function() {
         $('#default-datatable').DataTable({
             "pageLength": 10,
             "lengthMenu": [5, 10, 25, 50, 100],
             "order": [
                 [0, 'desc']
             ],
             "language": {
                 "search": "Search Permissions:",
                 "lengthMenu": "Show _MENU_ entries"
             }
         });
     });
 </script>

 @endsection