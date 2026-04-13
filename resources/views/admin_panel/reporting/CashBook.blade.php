@extends('admin_panel.layout.app')
@section('content')
<style>
    /* Professional Card Look */
    .cashbook-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        border: 2px solid #000;
        margin: 20px 0;
    }

    .cashbook-header {
        background: #000;
        color: #fff;
        padding: 15px;
        text-align: center;
    }

    .cashbook-body {
        padding: 20px;
    }

    /* Balance Styling: Left Aligned & Bold */
    .balance-container {
        display: flex;
        justify-content: flex-start;
        text-align: left;
    }

    .balance-item {
        padding: 10px 15px;
        border-left: 8px solid #000;
        background: #f8f9fa;
        width: 100%;
        text-align: right;
    }

    .balance-item .label {
        display: block;
        font-size: 15px;
        font-weight: 800;
        color: #555;
        text-transform: uppercase;
    }

    .balance-item .value {
        font-size: 28px;
        /* Balanced size */
        font-weight: 900;
        color: #000;
        font-family: sans-serif;
    }

    /* Professional Table Header */
    .professional-cash-table {
        border: 2px solid #000;
        margin-bottom: 0;
        width: 100%;
    }

    .professional-cash-table thead th {
        background: #2c3e50 !important;
        color: #ffffff !important;
        font-weight: 800;
        font-size: 15px;
        /* Chota font size */
        text-transform: uppercase;
        padding: 10px;
        border: 2px solid #000 !important;
    }

    .professional-cash-table td {
        padding: 8px 12px;
        border: 1px solid #000;
        vertical-align: middle;
        font-size: 14px;
        /* Professional standard size */
    }

    /* Column Separator */
    .separator {
        width: 10px;
        background: #000;
        padding: 0 !important;
        border: none !important;
    }

    .entry-name {
        font-weight: 600;
        color: #000;
    }

    .entry-name small {
        color: #666;
        font-size: 11px;
    }

    .entry-amount {
        font-weight: 800;
        text-align: right;
        font-size: 15px;
    }

    /* Totals Styling */
    .total-summary-row td {
        background: #eeeeee;
        border-top: 2px solid #000 !important;
        font-weight: 800 !important;
        font-size: 16px;
    }

    .grand-footer-row td {
        background: #d1d1d1;
        border: 2px solid #000 !important;
        font-size: 16px;
        font-weight: 800;
    }

    .text-danger {
        color: #ff0000 !important;
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="cashbook-card">
                <div class="cashbook-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <h3 class="m-0 font-weight-bold">DAILY CASH BOOK</h3>
                        <form method="GET" action="{{ route('cashbook') }}" id="dateFilterForm" class="d-flex gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <label class="text-white mb-0">Start Date:</label>
                                <input type="date" name="start_date" class="form-control form-control-sm" 
                                       value="{{ $startDate ?? now()->subDays(30)->format('Y-m-d') }}" 
                                       onchange="document.getElementById('dateFilterForm').submit()">
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <label class="text-white mb-0">View Date:</label>
                                <input type="date" name="date" class="form-control form-control-sm" 
                                       value="{{ $selectedDate ?? date('Y-m-d') }}" 
                                       onchange="document.getElementById('dateFilterForm').submit()">
                            </div>
                        </form>
                    </div>
                </div>
                <div class="cashbook-body">

                    <div class="balance-container mb-3">
                        <div class="balance-item">
                            <span class="label">Opening Balance</span>
                            <span class="value">{{ number_format($openingBalance, 0) }}</span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table professional-cash-table">
                            <thead>
                                <tr>
                                    <th width="38%">RECEIPTS (Debit)</th>
                                    <th width="12%" class="text-end">Amount</th>
                                    <th class="separator"></th>
                                    <th width="38%">PAYMENTS (Credit)</th>
                                    <th width="12%" class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i = 0; $i < $maxRows; $i++)
                                    <tr>
                                    {{-- RECEIPTS --}}
                                    <td class="entry-name">
                                        {{ $receipts[$i]['title'] ?? '-' }} <br>
                                        <small>{{ $receipts[$i]['ref'] ?? '-' }}</small>
                                    </td>

                                    <td class="entry-amount">
                                        {{ isset($receipts[$i]) ? number_format($receipts[$i]['amount'],0) : '-' }}
                                    </td>

                                    <td class="separator"></td>

                                    {{-- PAYMENTS --}}
                                    <td class="entry-name">
                                        {{ $payments[$i]['title'] ?? '-' }} <br>
                                        <small>{{ $payments[$i]['ref'] ?? '-' }}</small>
                                    </td>

                                    <td class="entry-amount text-danger">
                                        {{ isset($payments[$i]) ? number_format($payments[$i]['amount'],0) : '-' }}
                                    </td>
                                    </tr>
                                    @endfor

                                    <tr class="total-summary-row">
                                        <td class="text-uppercase">Total Receipts</td>
                                        <td class="text-end">{{ number_format($totalReceipts,0) }}</td>

                                        <td class="separator"></td>

                                        <td class="text-uppercase">Total Payments</td>
                                        <td class="text-end">{{ number_format($totalPayments,0) }}</td>
                                    </tr>
                            </tbody>

                            <tfoot>
                                <tr class="grand-footer-row">
                                    <td colspan="2" class="text-center">
                                        Grand Total: {{ number_format($totalReceipts, 0) }}
                                    </td>

                                    <td class="separator"></td>

                                    <td colspan="2" class="text-center">
                                        Grand Total: {{ number_format($totalPayments, 0) }}
                                    </td>
                                </tr>
                            </tfoot>


                        </table>
                    </div>

                    <div class="balance-container mt-3">
                        <div class="balance-item">
                            <span class="label">Closing Balance</span>
                            <span class="value">
                                {{ number_format($closingBalance, 0) }}
                            </span>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
