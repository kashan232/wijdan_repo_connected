@extends('admin_panel.layout.app')

@section('content')
<div class="container-fluid mt-4">

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
    @endif

    <div class="mb-3">
        <button class="btn btn-primary" data-toggle="modal" data-target="#addAccountModal">➕ Add New Account</button>
        <button class="btn btn-outline-secondary" data-toggle="modal" data-target="#addHeadModal">➕ Add Head</button>
    </div>

    <div class="table-responsive">
        <table id="productTable" class="table table-striped table-bordered align-middle nowrap" style="width:100%">

            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Account Code</th>
                    <th>Expense Head</th>
                    <th>Account Title</th>
                    <th>closing Balance</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($accounts as $key => $account)
                <tr>
                    <td>{{ $key+1 }}</td>
                    <td>{{ $account->account_code }}</td>
                    <td>{{ $account->head->name }}</td>
                    <td>{{ $account->title }}</td>
                    <td><strong class="text-danger"> {{ $account->opening_balance  }} </strong></td> <!-- Display debit amount -->
                    <td>
                        @if($account->status)
                        <span class="badge bg-success">Active</span>
                        @else
                        <span class="badge bg-danger">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary btn-edit-account"
                            data-toggle="modal"
                            data-target="#addAccountModal"
                            data-id="{{ $account->id }}"
                            data-head="{{ $account->head_id }}"
                            data-code="{{ $account->account_code }}"
                            data-title="{{ $account->title }}"
                            data-balance="{{ $account->opening_balance }}"
                            data-status="{{ $account->status }}">
                            Edit
                        </button>

                        <button class="btn btn-sm btn-danger btn-delete-account"
                            data-id="{{ $account->id }}">
                            Delete
                        </button>
                    </td>

                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>


<!-- Add Account Modal -->
<div class="modal fade" id="addAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('coa.account.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="accountModalTitle">Add New Account</h5>
                <button type="button" class="btn-close" data-dismiss="modal"></button>
            </div>
            <input type="hidden" name="account_id" id="account_id">
            <div class="modal-body">
                <div class="mb-3">
                    <label>Select Head</label>
                    <select name="head_id" class="form-control" required>
                        <option value="">Select Head</option>
                        @foreach($heads as $head)
                        <option value="{{ $head->id }}">{{ $head->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label>Account Code</label>
                    <input type="text" name="account_code" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Account Title</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Opening Balance</label>
                    <input type="number" step="0.01" name="opening_balance" class="form-control" value="0.00">
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" name="status" type="checkbox" value="on" checked>
                    <label class="form-check-label">Active</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success" id="accountSubmitBtn">
                    Add Account
                </button>
            </div>
        </form>
    </div>
</div>




<!-- Add Head Modal -->
<div class="modal fade" id="addHeadModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('coa.head.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Add Head</h5>
                <button type="button" class="btn-close" data-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Head Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-secondary">Add Head</button>
            </div>
        </form>
    </div>
</div>

<form id="deleteAccountForm" method="POST">
    @csrf
    @method('DELETE')
</form>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        document.querySelectorAll('.btn-delete-account').forEach(btn => {
            btn.addEventListener('click', function() {

                let accountId = this.dataset.id;
                let form = document.getElementById('deleteAccountForm');
                form.action = `/coa/account/${accountId}`;

                Swal.fire({
                    title: 'Are you sure?',
                    text: "This account will be permanently deleted!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });

            });
        });

    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        document.querySelectorAll('.btn-edit-account').forEach(btn => {
            btn.addEventListener('click', function() {

                document.getElementById('accountModalTitle').innerText = 'Edit Account';
                document.getElementById('accountSubmitBtn').innerText = 'Update Account';
                document.getElementById('accountSubmitBtn').classList.remove('btn-success');
                document.getElementById('accountSubmitBtn').classList.add('btn-primary');

                document.getElementById('account_id').value = this.dataset.id;
                document.querySelector('[name="head_id"]').value = this.dataset.head;
                document.querySelector('[name="account_code"]').value = this.dataset.code;
                document.querySelector('[name="title"]').value = this.dataset.title;
                document.querySelector('[name="opening_balance"]').value = this.dataset.balance;
                document.querySelector('[name="status"]').checked = this.dataset.status == 1;
            });
        });

        // Reset modal when closed
        document.getElementById('addAccountModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('accountModalTitle').innerText = 'Add New Account';
            document.getElementById('accountSubmitBtn').innerText = 'Add Account';
            document.getElementById('accountSubmitBtn').classList.remove('btn-primary');
            document.getElementById('accountSubmitBtn').classList.add('btn-success');

            this.querySelector('form').reset();
            document.getElementById('account_id').value = '';
        });

    });

      $(document).ready(function() {
        $('#productTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [
                [10, 25, 50, -1],
                [10, 25, 50, "All"]
            ],
            order: [
                [1, 'desc'] // ✅ Invoice No / ID column ke basis pe latest pehle
            ],
            columnDefs: [{
                    targets: 0,
                    orderable: false
                } // S.No column sortable nahi
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search Expense..."
            }
        });
    });

</script>

@endsection
