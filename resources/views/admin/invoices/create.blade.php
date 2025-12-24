@extends('admin.layout')

@section('title', 'Generate Invoice - ' . $application->application_id)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1>Generate Invoice</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.applications') }}">Applications</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.applications.show', $application->id) }}">{{ $application->application_id }}</a></li>
                <li class="breadcrumb-item active">Generate Invoice</li>
            </ol>
        </nav>
    </div>
</div>

@if(isset($invoiceData['error']))
<div class="alert alert-danger">
    {{ $invoiceData['error'] }}
</div>
@else
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Invoice for: {{ $application->application_id }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.applications.ix-account.generate-invoice.store', $application->id) }}" id="invoiceForm">
                    @csrf

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="billing_start_date" class="form-label">Billing Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="billing_start_date" name="billing_start_date" value="{{ $invoiceData['billing_start_date'] }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="billing_end_date" class="form-label">Billing End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="billing_end_date" name="billing_end_date" value="{{ $invoiceData['billing_end_date'] }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="due_date" name="due_date" value="{{ $invoiceData['due_date'] }}" required>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="billing_period" class="form-label">Billing Period</label>
                            <input type="text" class="form-control" id="billing_period" name="billing_period" value="{{ $invoiceData['billing_period'] }}" placeholder="e.g., 2025-01, 2025-Q1">
                        </div>
                    </div>

                    <hr class="my-4">

                    <h5 class="mb-3">Line Items / Particulars</h5>
                    <div id="lineItemsContainer">
                        @foreach($invoiceData['segments'] as $index => $segment)
                        <div class="line-item-row mb-3 p-3 border rounded">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small">Description <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" name="line_items[{{ $index }}][description]" value="{{ $segment['description'] ?? ('IX Service - ' . ($segment['capacity'] ?? '') . ' Port Capacity (' . ($segment['plan_label'] ?? $segment['plan'] ?? '') . ')') }}" required>
                                    @if(isset($segment['start']) && isset($segment['end']))
                                    <small class="text-muted">Period: {{ $segment['start'] }} to {{ $segment['end'] }} ({{ $segment['days'] ?? 0 }} days)</small>
                                    @endif
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Quantity</label>
                                    <input type="number" class="form-control form-control-sm quantity-input" name="line_items[{{ $index }}][quantity]" value="{{ $segment['quantity'] ?? 1 }}" step="0.01" min="0">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Rate</label>
                                    <input type="number" class="form-control form-control-sm rate-input" name="line_items[{{ $index }}][rate]" value="{{ $segment['rate'] ?? $segment['amount_full'] ?? 0 }}" step="0.01" min="0">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Amount</label>
                                    <input type="number" class="form-control form-control-sm amount-input" name="line_items[{{ $index }}][amount]" value="{{ $segment['amount'] ?? $segment['amount_prorated'] ?? 0 }}" step="0.01" min="0">
                                </div>
                            </div>
                            @if($index > 0)
                            <button type="button" class="btn btn-sm btn-danger mt-2 remove-line-item">Remove</button>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary mt-2" id="addLineItem">+ Add Line Item</button>

                    <hr class="my-4">

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="amount" class="form-label">Amount (Base) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="amount" name="amount" value="{{ $invoiceData['amount'] }}" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label for="gst_amount" class="form-label">GST Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="gst_amount" name="gst_amount" value="{{ $invoiceData['gst_amount'] }}" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label for="total_amount" class="form-label">Total Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="total_amount" name="total_amount" value="{{ $invoiceData['final_total_amount'] }}" step="0.01" min="0" required>
                        </div>
                    </div>

                    @if($invoiceData['has_carry_forward'])
                    <div class="alert alert-info mb-4">
                        <strong>Carry Forward Amount:</strong> ₹{{ number_format($invoiceData['carry_forward_amount'], 2) }}
                        <input type="hidden" name="carry_forward_amount" value="{{ $invoiceData['carry_forward_amount'] }}">
                        <input type="hidden" name="has_carry_forward" value="1">
                    </div>
                    @endif

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('admin.applications.show', $application->id) }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Generate Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let lineItemIndex = {{ count($invoiceData['segments']) }};

    // Add line item
    document.getElementById('addLineItem').addEventListener('click', function() {
        const container = document.getElementById('lineItemsContainer');
        const newItem = document.createElement('div');
        newItem.className = 'line-item-row mb-3 p-3 border rounded';
        newItem.innerHTML = `
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label small">Description <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" name="line_items[${lineItemIndex}][description]" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Quantity</label>
                    <input type="number" class="form-control form-control-sm quantity-input" name="line_items[${lineItemIndex}][quantity]" value="1" step="0.01" min="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Rate</label>
                    <input type="number" class="form-control form-control-sm rate-input" name="line_items[${lineItemIndex}][rate]" value="0" step="0.01" min="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Amount</label>
                    <input type="number" class="form-control form-control-sm amount-input" name="line_items[${lineItemIndex}][amount]" value="0" step="0.01" min="0">
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-danger mt-2 remove-line-item">Remove</button>
        `;
        container.appendChild(newItem);
        lineItemIndex++;
        attachLineItemListeners(newItem);
    });

    // Remove line item
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-line-item')) {
            if (confirm('Are you sure you want to remove this line item?')) {
                e.target.closest('.line-item-row').remove();
            }
        }
    });

    // Calculate amount from quantity * rate
    function attachLineItemListeners(itemRow) {
        const quantityInput = itemRow.querySelector('.quantity-input');
        const rateInput = itemRow.querySelector('.rate-input');
        const amountInput = itemRow.querySelector('.amount-input');

        function calculateAmount() {
            const quantity = parseFloat(quantityInput.value) || 0;
            const rate = parseFloat(rateInput.value) || 0;
            amountInput.value = (quantity * rate).toFixed(2);
            updateTotal();
        }

        if (quantityInput) quantityInput.addEventListener('input', calculateAmount);
        if (rateInput) rateInput.addEventListener('input', calculateAmount);
    }

    // Attach listeners to existing line items
    document.querySelectorAll('.line-item-row').forEach(item => {
        attachLineItemListeners(item);
    });

    // Auto-calculate total amount
    function updateTotal() {
        let total = 0;
        document.querySelectorAll('.amount-input').forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        document.getElementById('amount').value = total.toFixed(2);
        const gst = parseFloat(document.getElementById('gst_amount').value) || 0;
        document.getElementById('total_amount').value = (total + gst).toFixed(2);
    }

    const amountInput = document.getElementById('amount');
    const gstInput = document.getElementById('gst_amount');
    const totalInput = document.getElementById('total_amount');

    function calculateTotal() {
        const amount = parseFloat(amountInput.value) || 0;
        const gst = parseFloat(gstInput.value) || 0;
        totalInput.value = (amount + gst).toFixed(2);
    }

    if (amountInput) amountInput.addEventListener('input', calculateTotal);
    if (gstInput) gstInput.addEventListener('input', calculateTotal);

    // Update total when line item amounts change
    document.querySelectorAll('.amount-input').forEach(input => {
        input.addEventListener('input', updateTotal);
    });
});
</script>
@endsection

