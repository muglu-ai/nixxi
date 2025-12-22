@extends('user.layout')

@section('title', 'Request Plan Change')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1>Request Plan Change</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('user.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('user.applications.index') }}">Applications</a></li>
                <li class="breadcrumb-item"><a href="{{ route('user.applications.show', $application->id) }}">{{ $application->application_id }}</a></li>
                <li class="breadcrumb-item active">Request Plan Change</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Current Plan Details</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Port Capacity:</dt>
                    <dd class="col-sm-8"><strong>{{ $application->assigned_port_capacity ?? ($portSelection['capacity'] ?? 'N/A') }}</strong></dd>
                    <dt class="col-sm-4">Billing Plan:</dt>
                    <dd class="col-sm-8">{{ strtoupper($portSelection['billing_plan'] ?? 'N/A') }}</dd>
                    <dt class="col-sm-4">Current Amount:</dt>
                    <dd class="col-sm-8">₹{{ number_format($portSelection['amount'] ?? 0, 2) }}</dd>
                </dl>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Request New Plan</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('user.plan-change.store', $application->id) }}">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="new_port_capacity" class="form-label">New Port Capacity <span class="text-danger">*</span></label>
                        <select name="new_port_capacity" id="new_port_capacity" class="form-select @error('new_port_capacity') is-invalid @enderror" required>
                            <option value="">Select Port Capacity</option>
                            @foreach($availablePorts as $port)
                            <option value="{{ $port->port_capacity }}" 
                                data-arc="{{ $port->price_arc }}" 
                                data-mrc="{{ $port->price_mrc }}" 
                                data-quarterly="{{ $port->price_quarterly }}"
                                {{ old('new_port_capacity') === $port->port_capacity ? 'selected' : '' }}>
                                {{ $port->port_capacity }}
                            </option>
                            @endforeach
                        </select>
                        @error('new_port_capacity')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="new_billing_plan" class="form-label">New Billing Plan <span class="text-danger">*</span></label>
                        <select name="new_billing_plan" id="new_billing_plan" class="form-select @error('new_billing_plan') is-invalid @enderror" required>
                            <option value="">Select Billing Plan</option>
                            <option value="arc" {{ old('new_billing_plan') === 'arc' ? 'selected' : '' }}>Annual (ARC)</option>
                            <option value="mrc" {{ old('new_billing_plan') === 'mrc' ? 'selected' : '' }}>Monthly (MRC)</option>
                            <option value="quarterly" {{ old('new_billing_plan') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                        </select>
                        @error('new_billing_plan')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Amount:</label>
                        <div class="alert alert-info" id="new_amount_display">
                            Select port capacity and billing plan to see the amount.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Adjustment Amount:</label>
                        <div class="alert" id="adjustment_amount_display">
                            Select port capacity and billing plan to see the adjustment amount.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for Change <span class="text-danger">*</span></label>
                        <textarea name="reason" id="reason" rows="4" class="form-control @error('reason') is-invalid @enderror" required placeholder="Please provide a reason for this plan change (minimum 10 characters)">{{ old('reason') }}</textarea>
                        @error('reason')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Minimum 10 characters required.</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                        <a href="{{ route('user.applications.show', $application->id) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const portSelect = document.getElementById('new_port_capacity');
    const planSelect = document.getElementById('new_billing_plan');
    const newAmountDisplay = document.getElementById('new_amount_display');
    const adjustmentDisplay = document.getElementById('adjustment_amount_display');
    
    const currentAmount = {{ $portSelection['amount'] ?? 0 }};
    
    function updateAmounts() {
        const selectedOption = portSelect.options[portSelect.selectedIndex];
        const plan = planSelect.value;
        
        if (!selectedOption.value || !plan) {
            newAmountDisplay.textContent = 'Select port capacity and billing plan to see the amount.';
            newAmountDisplay.className = 'alert alert-info';
            adjustmentDisplay.textContent = 'Select port capacity and billing plan to see the adjustment amount.';
            adjustmentDisplay.className = 'alert';
            return;
        }
        
        const amount = parseFloat(selectedOption.getAttribute(`data-${plan}`)) || 0;
        const adjustment = amount - currentAmount;
        
        newAmountDisplay.textContent = `₹${amount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        newAmountDisplay.className = 'alert alert-success';
        
        if (adjustment > 0) {
            adjustmentDisplay.textContent = `+₹${adjustment.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})} (Upgrade - Additional payment required)`;
            adjustmentDisplay.className = 'alert alert-warning';
        } else if (adjustment < 0) {
            adjustmentDisplay.textContent = `₹${Math.abs(adjustment).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})} (Downgrade - Credit will be applied)`;
            adjustmentDisplay.className = 'alert alert-info';
        } else {
            adjustmentDisplay.textContent = '₹0.00 (No change)';
            adjustmentDisplay.className = 'alert alert-secondary';
        }
    }
    
    portSelect.addEventListener('change', updateAmounts);
    planSelect.addEventListener('change', updateAmounts);
    
    // Initial update if values are pre-selected
    if (portSelect.value && planSelect.value) {
        updateAmounts();
    }
});
</script>
@endpush
@endsection
