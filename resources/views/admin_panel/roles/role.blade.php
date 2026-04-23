 @extends('admin_panel.layout.app')
 @section('content')
 <style>
     #permission-checkbox-container {
         max-height: calc(100vh - 280px);
         overflow-y: auto;
         overflow-x: hidden;
     }
     .permission-card {
         transition: all 0.2s ease;
         border: 1px solid #e0e0e0;
         cursor: pointer;
         border-radius: 8px;
     }
     .permission-card:hover {
         background-color: #f8f9fa !important;
         border-color: #0d6efd;
         transform: translateY(-2px);
         box-shadow: 0 4px 6px rgba(0,0,0,0.05);
     }
     .permission-card.active {
         background-color: #e7f1ff !important;
         border-color: #0d6efd;
     }
     .mx-minus-4 {
         margin-left: -1.5rem;
         margin-right: -1.5rem;
     }
     /* Custom Scrollbar */
     #permission-checkbox-container::-webkit-scrollbar {
         width: 6px;
     }
     #permission-checkbox-container::-webkit-scrollbar-track {
         background: #f1f1f1;
     }
     #permission-checkbox-container::-webkit-scrollbar-thumb {
         background: #ccc;
         border-radius: 10px;
     }
     #permission-checkbox-container::-webkit-scrollbar-thumb:hover {
         background: #999;
     }
 </style>

 <div class="main-content">
     <div class="main-content-inner">
         <div class="container-fluid">
             <div class="row">
                 <div class="col-lg-12">
                     <div class="d-flex justify-content-between align-items-center mb-4">
                         <h3 class="fw-bold text-dark">Role Management</h3>
                         <button type="button" class="btn btn-primary px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#exampleModal" id="reset-form">
                             <i class="fas fa-plus me-1"></i> Create Role
                         </button>
                     </div>
                     <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                         <div class="card-body p-0">
                             <div class="table-responsive">
                                 <table id="default-datatable" class="table table-hover align-middle mb-0">
                                     <thead class="bg-light">
                                         <tr>
                                             <th class="ps-4">Id</th>
                                             <th>Name</th>
                                             <th>Permissions</th>
                                             <th class="text-end pe-4">Action</th>
                                         </tr>
                                     </thead>
                                     <tbody>
                                         @foreach ($roles as $role)
                                         <tr>
                                             <td class="ps-4">
                                                 <span class="text-muted fw-medium">{{ $role->id }}</span>
                                                 <input type="hidden" class="edit-id" value="{{ $role->id }}">
                                             </td>
                                             <td class="name fw-bold text-dark">
                                                 {{ $role->name }}
                                             </td>
                                             <td>
                                                 <div class="d-flex flex-wrap gap-1">
                                                     @forelse ($role->getPermissionNames() as $permission)
                                                     <span class="badge bg-soft-success text-success border border-success-subtle px-2 py-1">{{ $permission }}</span>
                                                     @empty
                                                     <span class="badge bg-soft-danger text-danger border border-danger-subtle px-2 py-1">No Permission Assigned</span>
                                                     @endforelse
                                                 </div>
                                             </td>
                                             <td class="text-end pe-4">
                                                 <div class="btn-group shadow-sm">
                                                     <button class="btn btn-outline-info btn-sm edit-permission-btn" title="Edit Permissions">
                                                         <i class="fas fa-lock"></i>
                                                     </button>
                                                     <button class="btn btn-outline-primary btn-sm edit-btn" title="Edit Role">
                                                         <i class="fas fa-edit"></i>
                                                     </button>
                                                     <a href="{{ route('roles.delete', $role->id) }}" class="btn btn-outline-danger btn-sm"
                                                         data-msg="Are you sure you want to delete this Role?"
                                                         onclick="confirmedBox(this, event)" title="Delete Role">
                                                         <i class="fas fa-trash"></i>
                                                     </a>
                                                 </div>
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

 <!-- Add/Edit Role Modal -->
 <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
     <div class="modal-dialog modal-dialog-centered">
         <div class="modal-content border-0 shadow-lg">
             <div class="modal-header bg-primary text-white">
                 <h5 class="modal-title fw-bold" id="exampleModalLabel">Role Details</h5>
                 <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
             </div>
             <form class="myform" action="{{ route('roles.store') }}" method="POST">
                 <div class="modal-body p-4">
                     @csrf
                     <input type="hidden" name="edit_id" id="id" />
                     <div class="mb-3">
                         <label for="name" class="form-label fw-bold">Role Name</label>
                         <input type="text" name="name" class="form-control form-control-lg border-2" id="name" placeholder="Enter role name" required />
                     </div>
                 </div>
                 <div class="modal-footer bg-light border-0">
                     <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Cancel</button>
                     <button type="submit" class="btn btn-primary px-4 fw-bold save-btn">Save Role</button>
                 </div>
             </form>
         </div>
     </div>
 </div>

 <!-- Edit Permissions Modal -->
 <div class="modal fade" id="edit-permission-modal" tabindex="-1" aria-hidden="true">
     <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
         <div class="modal-content border-0 shadow-lg">
             <div class="modal-header bg-dark text-white py-3">
                 <div class="d-flex align-items-center">
                     <div class="bg-primary rounded-circle p-2 me-3 text-white">
                         <i class="fas fa-shield-alt"></i>
                     </div>
                     <div>
                         <h5 class="modal-title mb-0 fw-bold">Manage Permissions</h5>
                         <small class="text-info fw-bold" id="role-name-display"></small>
                     </div>
                 </div>
                 <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
             </div>

             <div class="modal-body bg-light px-4">
                 <form id="permission-form" action="{{ route('roles.update.permission') }}" method="POST">
                     @csrf
                     <input type="hidden" name="edit_id" id="edit-role-id" />

                     <!-- Sticky Search and Actions -->
                     <div class="sticky-top bg-light pt-2 pb-3 mb-3 border-bottom shadow-sm mx-minus-4 px-4" style="z-index: 1020;">
                         <div class="row align-items-center g-3">
                             <div class="col-md-6">
                                 <div class="input-group shadow-sm">
                                     <span class="input-group-text bg-white border-end-0">
                                         <i class="fas fa-search text-muted"></i>
                                     </span>
                                     <input type="text" id="permission-search" class="form-control border-start-0" placeholder="Search permissions by name...">
                                 </div>
                             </div>
                             <div class="col-md-6 text-md-end">
                                 <div class="btn-group shadow-sm">
                                     <button type="button" class="btn btn-outline-primary btn-sm" id="select-all-btn">
                                         <i class="fas fa-check-double me-1"></i> Select All
                                     </button>
                                     <button type="button" class="btn btn-outline-secondary btn-sm" id="deselect-all-btn">
                                         <i class="fas fa-times me-1"></i> Deselect All
                                     </button>
                                 </div>
                             </div>
                         </div>
                     </div>

                     <div id="permission-checkbox-container" class="row">
                         <!-- Content injected via JS -->
                     </div>
                 </form>
             </div>

             <div class="modal-footer bg-white border-top py-3">
                 <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                 <button type="submit" form="permission-form" class="btn btn-primary px-5 fw-bold shadow">
                     <i class="fas fa-save me-1"></i> Save Changes
                 </button>
             </div>
         </div>
     </div>
 </div>

 <!-- CSS for Badges -->
 <style>
    .bg-soft-success { background-color: rgba(25, 135, 84, 0.1); }
    .bg-soft-danger { background-color: rgba(220, 53, 69, 0.1); }
 </style>

 <!-- Scripts -->
 <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
 <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
 <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
 <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
 <script src="{{ asset('assets/js/mycode.js') }}"></script>

 <script>
     const allPermissions = @json($allPermissions);
     let currentAssigned = [];

     $(document).ready(function() {
         $('#default-datatable').DataTable({
             "pageLength": 10,
             "lengthMenu": [5, 10, 25, 50, 100],
             "order": [[0, 'desc']],
             "language": {
                 "search": "Filter Roles:",
                 "lengthMenu": "Show _MENU_ entries"
             }
         });
     });

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
         var name = tr.find(".name").text().trim();
         $('#id').val(id);
         $('#name').val(name);
         $("#exampleModal").modal("show");
     });

     $(document).on('click', '#reset-form', function() {
         $('#id').val('');
         $('#name').val('');
         $("#exampleModal").modal("show");
     });

     function confirmedBox(element, event) {
         event.preventDefault();
         const message = element.getAttribute('data-msg') || 'Are you sure?';
         const url = element.getAttribute('href');

         Swal.fire({
             title: 'Are you sure?',
             text: message,
             icon: 'warning',
             showCancelButton: true,
             confirmButtonColor: '#d33',
             cancelButtonColor: '#3085d6',
             confirmButtonText: 'Yes, delete it!'
         }).then((result) => {
             if (result.isConfirmed) {
                 window.location.href = url;
             }
         });
     }

     // update Permission
     $(document).on('click', '.edit-permission-btn', function() {
         var tr = $(this).closest("tr");
         var id = tr.find(".edit-id").val();
         var name = tr.find(".name").text().trim();

         let assignedPermissions = [];
         tr.find('td:eq(2) .badge').each(function() {
             assignedPermissions.push($(this).text().trim());
         });

         $('#role-name-display').text('Role: ' + name);
         $('#edit-role-id').val(id);

         currentAssigned = assignedPermissions; 
         $('#permission-search').val('');
         renderPermissions(assignedPermissions);

         $("#edit-permission-modal").modal("show");
     });

     function renderPermissions(assignedList = [], filter = '') {
         const container = $('#permission-checkbox-container');
         container.empty();

         const filtered = allPermissions.filter(p => 
            p.name.toLowerCase().includes(filter.toLowerCase())
         );

         if (filtered.length === 0) {
            container.append('<div class="col-12 text-center py-5"><p class="text-muted">No permissions found matching "'+filter+'"</p></div>');
            return;
         }

         filtered.forEach(function(permission) {
             let isChecked = assignedList.includes(permission.name) ? 'checked' : '';
             let cardClass = isChecked ? 'active shadow-sm' : '';

             container.append(`
                 <div class="col-12 col-sm-6 col-md-4 mb-3 permission-item">
                     <div class="card permission-card bg-white h-100 ${cardClass}">
                         <div class="card-body p-3">
                             <div class="form-check d-flex align-items-center mb-0">
                                 <input class="form-check-input permission-checkbox me-3 shadow-none overflow-hidden" 
                                        type="checkbox" 
                                        name="permissions[]" 
                                        value="${permission.name}" 
                                        id="perm_${permission.id}"
                                        ${isChecked}
                                        style="width: 1.2rem; height: 1.2rem; cursor: pointer;">
                                 <label class="form-check-label fw-medium stretched-link pe-2" for="perm_${permission.id}" style="cursor: pointer; font-size: 0.85rem; line-height: 1.2;">
                                     ${permission.name}
                                 </label>
                             </div>
                         </div>
                     </div>
                 </div>
             `);
         });
     }

     $(document).on('input', '#permission-search', function() {
         const searchQuery = $(this).val();
         syncStates();
         renderPermissions(currentAssigned, searchQuery);
     });

     function syncStates() {
        currentAssigned = [];
        $('.permission-checkbox:checked').each(function() {
            currentAssigned.push($(this).val());
        });
     }

     $(document).on('click', '#select-all-btn', function() {
         const searchQuery = $('#permission-search').val().toLowerCase();
         $('.permission-item:not(.d-none) .permission-checkbox').prop('checked', true).closest('.permission-card').addClass('active shadow-sm');
         syncStates();
     });

     $(document).on('click', '#deselect-all-btn', function() {
         $('.permission-item:not(.d-none) .permission-checkbox').prop('checked', false).closest('.permission-card').removeClass('active shadow-sm');
         syncStates();
     });

     $(document).on('change', '.permission-checkbox', function() {
        if ($(this).is(':checked')) {
            $(this).closest('.permission-card').addClass('active shadow-sm');
        } else {
            $(this).closest('.permission-card').removeClass('active shadow-sm');
        }
        syncStates();
     });
 </script>
 @if(session('success'))
 <script>
     Swal.fire({
         icon: 'success',
         title: 'Updated!',
         text: "{{ session('success') }}",
         timer: 2000,
         showConfirmButton: false,
         toast: true,
         position: 'top-end'
     });
 </script>
 @endif
 @endsection
