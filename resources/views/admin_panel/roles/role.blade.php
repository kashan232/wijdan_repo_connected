 @extends('admin_panel.layout.app')
 @section('content')
 <style>
     #permission-checkbox-container {
         max-height: calc(100vh - 220px);
         overflow-y: auto;
     }
 </style>
 <div class="main-content">
     <div class="main-content-inner">
         <div class="container">
             <div class="row">
                 <div class="col-lg-12">
                     <div class="d-flex justify-content-between align-items-center mb-3">
                         <h3>Roles</h3>
                         <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal"
                             id="reset-form">Create</button>
                     </div>
                     <div class="border mt-1 shadow rounded " style="background-color: white;">
                         <div class="col-lg-12 m-auto">
                             <div class="table-responsive mt-5 mb-5 ">
                                 <table id="default-datatable" class="table ">
                                     <thead class="text-center">
                                         <tr>
                                             <th class="text-center">Id</th>
                                             <th class="text-center">Name</th>
                                             <th class="text-center">Permissions</th>
                                             <th class="text-center">Action</th>
                                             <th class="text-center d-none">Action</th>
                                         </tr>
                                     </thead>
                                     <tbody class="text-center">
                                         @foreach ($roles as $role)
                                         <tr>
                                             <span class="d-none" id="edit-id">{{ $role->id }}</span>
                                             <td class="d-none">
                                                 <input type="hidden" class="edit-id" value="{{ $role->id }}">
                                             </td>
                                             <td class="id">{{ $role->id }}</td>
                                             <td class="name">
                                                 {{ $role->name }}
                                             </td>
                                             <td>
                                                 @forelse ($role->getPermissionNames() as $permission)
                                                 <span class="badge bg-success fw-bold p-2 text-white mb-2">{{ $permission }}</span>
                                                 @empty
                                                 <span class="badge bg-danger fw-bold p-2 text-white">No Permission Assigned</span>
                                                 @endforelse
                                             </td>
                                             <td>
                                                 <button class="btn btn-info btn-sm edit-permission-btn p-1">
                                                     Edit Permissions
                                                 </button>
                                                 <button class="btn btn-primary btn-sm edit-btn p-1"
                                                     data-url="{{ route('roles.store') }}">
                                                     Edit
                                                 </button>
                                                 <a href="{{ route('roles.delete', $role->id) }}" class="btn btn-danger btn-sm delete-btn p-1"
                                                     data-url="{{ route('roles.delete', $role->id) }}"
                                                     data-msg="Are you sure you want to delete this Role"
                                                     data-method="DELETE"
                                                     onclick="confirmedBox(this, event)">
                                                     Delete
                                                 </a>
                                             </td>
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
                 <h5 class="modal-title" id="exampleModalLabel">Add Roles</h5>
             </div>
             <div class="modal-body">
                 <form class="myform" action="{{ route('roles.store') }}" method="POST">
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

 <div class="modal fade" id="edit-permission-modal" tabindex="-1">
     <div class="modal-dialog modal-fullscreen">
         <div class="modal-content">

             <div class="modal-header bg-dark text-white">
                 <h5 class="modal-title">
                     Update Role Permissions
                 </h5>
                 <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
             </div>

             <form action="{{ route('roles.update.permission') }}" method="POST">
                 @csrf

                 <div class="modal-body">

                     <input type="hidden" name="edit_id" id="edit-role-id" />

                     <!-- Role Name -->
                     <div class="mb-3">
                         <label class="form-label fw-bold">Role Name</label>
                         <input type="text" class="form-control" id="permission-modal-name" readonly>
                     </div>

                     <hr>

                     <!-- Permissions -->
                     <label class="form-label fw-bold mb-2">Permissions</label>

                     <div id="permission-checkbox-container" class="row g-2"></div>
                 </div>

                 <div class="modal-footer">
                     <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                         Cancel
                     </button>
                     <button type="submit" class="btn btn-primary">
                         <i class="fas fa-save me-1"></i> Save Changes
                     </button>
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
         e.preventDefault();
         var formdata = new FormData(this);
         url = $(this).attr('action');
         method = $(this).attr('method');
         $(this).find(':submit').attr('disabled', true);
         myAjax(url, formdata, method);
     });
     $(document).on('click', '.edit-btn', function() {

         var tr = $(this).closest("tr");
         var id = tr.find(".edit-id").val();
         var name = tr.find(".name").text();
         var address = tr.find(".address").text();
         var number = tr.find(".number").text();
         var email = tr.find(".email").text().trim();
         $('#id').val(id);
         $('#name').val(name.trim())
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
     const allPermissions = @json($allPermissions);

     $(document).on('click', '#reset-form', function() {
         // alert("sd");
         // Manually clear inputs
         $('#id').val('');
         $('#name').val('');
         $("#exampleModal").modal("show")
     });

     // update Permission
     $(document).on('click', '.edit-permission-btn', function() {
         var tr = $(this).closest("tr");
         var id = tr.find(".edit-id").val();
         var name = tr.find(".name").text();
         // var email = tr.find(".email").text();

         // get assigned roles from badges
         let assignedPermissions = [];
         tr.find('td:eq(3) .badge').each(function() {
             assignedPermissions.push($(this).text().trim());
         });

         // inject user info
         $('#permission-modal-name').val(name.trim());
         // $('#role-modal-email').val(email);
         // alert(id);
         $('#edit-role-id').val(id);

         // Extract assigned role names from badges
         // var assignedRoles = [];
         // tr.find('td:nth-child(4) .badge').each(function () {
         //     assignedRoles.push($(this).text().trim());
         // });


         // clear previous checkboxes
         $('#permission-checkbox-container').html('');

         allPermissions.forEach(function(permission) {

             let isChecked = assignedPermissions.includes(permission.name) ? 'checked' : '';

             $('#permission-checkbox-container').append(`
        <div class="col-12 col-sm-6 col-md-4 col-lg-2">
            <div class="form-check">
                <input class="form-check-input"
                       type="checkbox"
                       name="permissions[]"
                       value="${permission.name}"
                       ${isChecked}>
                <label class="form-check-label small">
                    ${permission.name}
                </label>
            </div>
        </div>
    `);
         });

         $("#edit-permission-modal").modal("show");
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
                 "search": "Search Roles:",
                 "lengthMenu": "Show _MENU_ entries"
             }
         });
     });
 </script>

 @endsection