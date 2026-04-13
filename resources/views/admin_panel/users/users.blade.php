 @extends('admin_panel.layout.app')
 @section('content')
     
<div class="main-content">
    <div class="main-content-inner">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Users</h3>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal"
                id="reset-form">Create</button>
        </div>
        <div class="border mt-1 shadow rounded " style="background-color: white;">
            <div class="col-lg-12 m-auto">
   <div class="table-responsive mt-5 mb-5 ">
    <table id="default-datatable" class="table">
        <thead class="text-center">
            <tr>
                <th class="text-center">Id</th>
                <th class="text-center">Name</th>
                <th class="text-center">Email</th>
                <th class="text-center">Roles</th>
                <th class="text-center">Opening Balance (Today)</th>
                <th class="text-center">Action</th>
                <th class="text-center d-none">Action</th>
            </tr>
        </thead>
        <tbody class="text-center">
                @foreach ($users as $key => $user)
                        <tr>
                            {{-- <span class="d-none" id="edit-id">{{ $user->id }}</span> --}}
                            <td class="d-none">
                                <input type="hidden" class="edit-id" value="{{ $user->id }}">
                            </td>
                            <th scope="row" class="id">{{ $user->id }}</th>
                            <td class="name">{{ $user->name }}</td>
                            <td class="email">{{ $user->email }}</td>
                            <td>
                                {{-- @foreach ($user->getRoleNames() as $role)
                                    <span class="badge bg-primary">{{ $role }}</span>
                                @endforeach --}}

                                {{-- <form action="{{ route('users.update.roles', $user->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')

                                    <h5 class="mb-3">Assign Roles to {{ $user->name }}</h5>
                                    @forelse ($allRoles as $key1 => $role)
                                        <div class="form-check d-flex" style="align-items:baseline !important;margin-left:140px !important;">
                                            <input class="form-check-input"
                                                type="checkbox"
                                                name="roles[]"
                                                value="{{ $role->name }}"
                                                {{ $user->hasRole($role->name) ? 'checked' : '' }}><br>
                                            <label class="form-check-label m-0 p-0">
                                                {{ $role->name }}
                                            </label>
                                        </div>
                                    @empty

                                    @endforelse

                                    <button type="submit" class="btn btn-warning mt-3 btn-sm p-1">Update Roles</button>
                                </form> --}}
                                @forelse ($user->getRoleNames() as $role)
                                    <span class="badge bg-success fw-bold p-2 text-white mb-2">{{ $role }}</span>
                                @empty
                                    <span class="badge bg-danger fw-bold p-2 text-white">No Role Assigned</span>
                                @endforelse
                            </td>
                            <td>
                                @if($user->today_opening)
                                    <span class="badge bg-secondary fs-6">
                                        {{ number_format($user->today_opening->amount, 2) }}
                                    </span>
                                @else
                                    <span class="text-muted">0.00</span>
                                @endif
                                <input type="hidden" class="opening-balance-val" value="{{ $user->today_opening->amount ?? 0 }}">
                                <input type="hidden" class="opening-balance-note" value="{{ $user->today_opening->note ?? '' }}">
                            </td>       
                            <td>
                                <button class="btn btn-info btn-sm edit-role-btn p-1">
                                    Edit Roles
                                </button>
                                <button class="btn btn-primary btn-sm edit-btn p-1"
                                    data-url="{{ route('users.store') }}">
                                    Edit
                                </button>
                                <a href="{{ route('users.delete', $user->id) }}" class="btn btn-danger btn-sm delete-btn p-1"
                                data-url="{{ route('users.delete', $user->id) }}"
                                data-msg="Are you sure you want to delete this Role"
                                data-method="DELETE"
                                onclick="confirmedBox(this, event)">
                                Delete
                                </a>
                                <button class="btn btn-warning btn-sm opening-balance-btn p-1"
                                    data-userid="{{ $user->id }}"
                                    data-username="{{ $user->name }}">
                                    Op. Balance
                                </button>
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
                    <h5 class="modal-title" id="exampleModalLabel">Users</h5>
                </div>
                <div class="modal-body">
                    <form class="myform" action="{{ route('users.store') }}" method="POST">
                        @csrf
                            <input type="hidden" name="edit_id" id="id" />
                        <div class="mb-3">
                            <label for="title" class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" id="name" />
                        </div>
                        <div class="mb-3">
                            <label for="title" class="form-label">Email</label>
                            <input type="text" name="email" class="form-control" id="email" />
                        </div>
                        <div class="mb-3">
                            <label for="title" class="form-label">Pasword</label>
                            <input type="text" name="password" class="form-control" id="password" />
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <input type="submit" class="btn btn-primary save-btn">
                </div>
                </form>
            </div>
        </div>
    </div> 
    <div class="modal fade" id="edit-role-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Update Roles</h5>
                </div>
                <form class="" action="{{ route('users.update.roles') }}" method="POST">
                    <div class="modal-body">
                            @csrf
                            <input type="hidden" name="edit_id" id="edit-role-id" />
                            <div class="mb-3">
                                <label for="title" class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" id="role-modal-name" readonly />
                            </div>
                            <div class="mb-3">
                                <label for="title" class="form-label">Email</label>
                                <input type="text" name="email" class="form-control" id="role-modal-email" readonly />
                            </div>
                                <label for="title" class="form-label">Roles</label>
                            <div class="mb-3" id="role-checkbox-container">
                                <!-- Checkboxes will be injected via JS here -->
                            </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <input type="submit" class="btn btn-primary save-btn">
                    </div>
                </form>
            </div>
        </div>
    </div> 

    <!-- Opening Balance Modal -->
    <div class="modal fade" id="openingBalanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Set Daily Opening Cash</h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('users.store_opening_balance') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="ob-user-id">
                        
                        <div class="mb-3">
                            <label class="fw-bold">User: <span id="ob-user-name" class="text-primary"></span></label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Opening Balance Amount</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required placeholder="0.00">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Note (Optional)</label>
                            <textarea name="note" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Balance</button>
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
    <script  src="{{ asset('assets/js/mycode.js') }}">  </script>
 <script>
    $(document).on('submit', '.myform', function(e) {
        e.preventDefault();
        var formdata = new FormData(this);
        url = $(this).attr('action');
        method = $(this).attr('method');
        $(this).find(':submit').attr('disabled', true);
        myAjax(url, formdata, method);
    });
    $(document).on('click', '.edit-btn', function () {

        var tr = $(this).closest("tr");
        var id = tr.find(".edit-id").val();
        // alert(id+"hit");
        var name = tr.find(".name").text();
        var email = tr.find(".email").text();
        $('#id').val(id);
        $('#name').val(name)
        $('#email').val(email)
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

     $(document).on('click', '#reset-form', function () {
        // alert("sd");
        // Manually clear inputs
        $('#id').val('');
        $('#name').val('');
        $('#email').val('');
        $('#password').val('');
        $("#exampleModal").modal("show")
    });
    const allRoles = @json($allRoles);
    // update role
    $(document).on('click', '.edit-role-btn', function () {
        var tr = $(this).closest("tr");
        var id = tr.find(".edit-id").val();
        var name = tr.find(".name").text();
        var email = tr.find(".email").text();

        // get assigned roles from badges
        let assignedRoles = [];
        tr.find('td:eq(3) .badge').each(function () {
            assignedRoles.push($(this).text().trim());
        });

        // inject user info
        $('#role-modal-name').val(name);
        $('#role-modal-email').val(email);
        // alert(id);
        $('#edit-role-id').val(id);

        // Extract assigned role names from badges
        // var assignedRoles = [];
        // tr.find('td:nth-child(4) .badge').each(function () {
        //     assignedRoles.push($(this).text().trim());
        // });

        
        // clear previous checkboxes
        $('#role-checkbox-container').html('');

        // allRoles must be available in JS
        allRoles.forEach(function (role) {
            let isChecked = assignedRoles.includes(role.name) ? 'checked' : '';
            $('#role-checkbox-container').append(`
                <div class="form-check d-flex align-items-center mb-1" style="margin-left:140px;">
                    <input class="form-check-input me-2" type="checkbox" name="roles[]" value="${role.name}" ${isChecked}>
                    <label class="form-check-label pt-1">${role.name}</label>
                </div>
            `);
        });

        $("#edit-role-modal").modal("show");
    });

    $(document).on('click', '.opening-balance-btn', function() {
        let id = $(this).data('userid');
        let name = $(this).data('username');
        let tr = $(this).closest('tr');
        let amount = tr.find('.opening-balance-val').val();
        let note = tr.find('.opening-balance-note').val();
        
        $('#ob-user-id').val(id);
        $('#ob-user-name').text(name);
        // If amount is 0, show empty or 0; typically input[type=number] handles 0 fine, but user might want empty for new entry.
        // But for edit, we want the value.
        $('input[name="amount"]').val(amount == 0 ? '' : amount);
        $('textarea[name="note"]').val(note);

        $('#openingBalanceModal').modal('show');
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
            "order": [[0, 'desc']],
            "language": {
                "search": "Search Users:",
                "lengthMenu": "Show _MENU_ entries"
            }
        });
    });
</script>


 @endsection
