@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid px-3">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-1">{{ $title }} — {{ $period->name }}</h4>
                    <p class="text-muted mb-0">Read-only archive view</p>
                </div>
                <a href="{{ route('period.archive.show', $period) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Period
                </a>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    @if($type === 'sales' || $type === 'sales_returns' || $type === 'product_bookings')
                                        <th>Customer</th>
                                        <th>Ref</th>
                                        <th>Net Amount</th>
                                        <th>Date</th>
                                    @elseif($type === 'purchases')
                                        <th>Invoice No</th>
                                        <th>Net Amount</th>
                                        <th>Purchase Date</th>
                                    @elseif($type === 'purchase_returns')
                                        <th>Return Invoice</th>
                                        <th>Net Amount</th>
                                        <th>Return Date</th>
                                    @elseif($type === 'inward_gatepasses')
                                        <th>Gatepass No</th>
                                        <th>Invoice No</th>
                                        <th>Net Amount</th>
                                        <th>Date</th>
                                    @elseif($type === 'inward_returns')
                                        <th>Return Invoice</th>
                                        <th>Date</th>
                                    @elseif($type === 'expense_vouchers')
                                        <th>EVID</th>
                                        <th>Total Amount</th>
                                        <th>Entry Date</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $row)
                                <tr>
                                    <td>{{ $row->id }}</td>
                                    @if($type === 'sales' || $type === 'sales_returns')
                                        <td>{{ Str::limit($row->customer ?? '—', 30) }}</td>
                                        <td>{{ $row->invoice_no ?? $row->reference ?? '—' }}</td>
                                        <td>{{ number_format((float)($row->total_net ?? 0), 2) }}</td>
                                        <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d M Y') }}</td>
                                    @elseif($type === 'product_bookings')
                                        <td>{{ Str::limit($row->customer ?? '—', 30) }}</td>
                                        <td>{{ $row->reference ?? '—' }}</td>
                                        <td>{{ number_format((float)($row->total_net ?? 0), 2) }}</td>
                                        <td>{{ $row->booking_date ? \Carbon\Carbon::parse($row->booking_date)->format('d M Y') : \Carbon\Carbon::parse($row->created_at)->format('d M Y') }}</td>
                                    @elseif($type === 'purchases')
                                        <td>{{ $row->invoice_no ?? '—' }}</td>
                                        <td>{{ number_format((float)($row->net_amount ?? 0), 2) }}</td>
                                        <td>{{ $row->purchase_date ? \Carbon\Carbon::parse($row->purchase_date)->format('d M Y') : '—' }}</td>
                                    @elseif($type === 'purchase_returns')
                                        <td>{{ $row->return_invoice ?? '—' }}</td>
                                        <td>{{ number_format((float)($row->net_amount ?? 0), 2) }}</td>
                                        <td>{{ $row->return_date ? \Carbon\Carbon::parse($row->return_date)->format('d M Y') : '—' }}</td>
                                    @elseif($type === 'inward_gatepasses')
                                        <td>{{ $row->gatepass_no ?? '—' }}</td>
                                        <td>{{ $row->invoice_no ?? '—' }}</td>
                                        <td>{{ number_format((float)($row->net_amount ?? 0), 2) }}</td>
                                        <td>{{ $row->gatepass_date ? \Carbon\Carbon::parse($row->gatepass_date)->format('d M Y') : '—' }}</td>
                                    @elseif($type === 'inward_returns')
                                        <td>{{ $row->return_invoice ?? '—' }}</td>
                                        <td>{{ $row->return_date ? \Carbon\Carbon::parse($row->return_date)->format('d M Y') : '—' }}</td>
                                    @elseif($type === 'expense_vouchers')
                                        <td>{{ $row->evid ?? '—' }}</td>
                                        <td>{{ number_format((float)($row->total_amount ?? 0), 2) }}</td>
                                        <td>{{ $row->entry_date ? \Carbon\Carbon::parse($row->entry_date)->format('d M Y') : \Carbon\Carbon::parse($row->created_at)->format('d M Y') }}</td>
                                    @endif
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Koi record nahi mila</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($records->hasPages())
                <div class="card-footer">
                    {{ $records->links() }}
                </div>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection
