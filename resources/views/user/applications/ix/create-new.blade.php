@extends('user.layout')

@section('title', 'New IX Application')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1" style="color:#1f2937;">New IX Application</h2>
            <p class="text-muted mb-0">Fill in the required details to submit your new IX application.</p>
        </div>
        <a href="{{ route('user.applications.index') }}" class="btn btn-outline-secondary">Back to Applications</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if($previousData)
                <div class="alert alert-info mb-4">
                    <i class="bi bi-info-circle"></i> We've pre-filled some details from your previous application. Please review and update as needed.
                </div>
            @endif

            <form id="newIxApplicationForm" method="POST" action="{{ route('user.applications.ix.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="application_id" id="applicationIdInput" value="">

                {{-- Representative Person Details --}}
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Representative Person Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="representative_name" id="representativeName" class="form-control" 
                                    value="{{ $previousData['representative']['name'] ?? '' }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">PAN <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" name="representative_pan" id="representativePan" class="form-control" 
                                        value="{{ $previousData['representative']['pan'] ?? '' }}" 
                                        placeholder="ABCDE1234F" maxlength="10" required>
                                    <button type="button" class="btn btn-outline-primary" id="verifyPanBtn">Verify</button>
                                </div>
                                <div id="panVerifyStatus" class="mt-2"></div>
                                <input type="hidden" name="pan_verified" id="panVerified" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                <input type="date" name="representative_dob" id="representativeDob" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mobile <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" name="representative_mobile" id="representativeMobile" class="form-control" 
                                        value="{{ $previousData['representative']['mobile'] ?? '' }}" 
                                        placeholder="10 digit mobile number" maxlength="10" required>
                                    <button type="button" class="btn btn-outline-primary" id="sendMobileOtpBtn">Send OTP</button>
                                </div>
                                <div id="mobileOtpSection" class="mt-2 d-none">
                                    <input type="text" id="mobileOtp" class="form-control mb-2" placeholder="Enter OTP" maxlength="6">
                                    <button type="button" class="btn btn-sm btn-success" id="verifyMobileOtpBtn">Verify OTP</button>
                                </div>
                                <div id="mobileVerifyStatus" class="mt-2"></div>
                                <input type="hidden" name="mobile_verified" id="mobileVerified" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="email" name="representative_email" id="representativeEmail" class="form-control" 
                                        value="{{ $previousData['representative']['email'] ?? '' }}" required>
                                    <button type="button" class="btn btn-outline-primary" id="sendEmailOtpBtn">Send OTP</button>
                                </div>
                                <div id="emailOtpSection" class="mt-2 d-none">
                                    <input type="text" id="emailOtp" class="form-control mb-2" placeholder="Enter OTP" maxlength="6">
                                    <button type="button" class="btn btn-sm btn-success" id="verifyEmailOtpBtn">Verify OTP</button>
                                </div>
                                <div id="emailVerifyStatus" class="mt-2"></div>
                                <input type="hidden" name="email_verified" id="emailVerified" value="0">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- NIXI Location --}}
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">NIXI Location</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Select Location <span class="text-danger">*</span></label>
                                <select name="location_id" id="locationSelect" class="form-select" required>
                                    <option value="">Select Location</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}"
                                            data-node-type="{{ $location->node_type }}"
                                            data-state="{{ $location->state }}"
                                            {{ $previousData && $previousData['location_id'] == $location->id ? 'selected' : '' }}>
                                            {{ $location->name }} ({{ ucfirst($location->node_type) }} - {{ $location->state }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Port Capacity & Billing Plan --}}
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Port Capacity & Billing Plan</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Port Capacity <span class="text-danger">*</span></label>
                                <select name="port_capacity" id="portCapacitySelect" class="form-select" required>
                                    <option value="">Select capacity</option>
                                    @foreach($portPricings as $nodeType => $entries)
                                        <optgroup label="{{ ucfirst($nodeType) }} nodes">
                                            @foreach($entries as $pricing)
                                                <option value="{{ $pricing->port_capacity }}" 
                                                    data-node-type="{{ $nodeType }}"
                                                    {{ $previousData && $previousData['port_capacity'] == $pricing->port_capacity ? 'selected' : '' }}>
                                                    {{ $pricing->port_capacity }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Billing Plan <span class="text-danger">*</span></label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="billing_plan" id="planArc" value="arc" 
                                            {{ $previousData && $previousData['billing_plan'] == 'arc' ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="planArc">Annual (ARC)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="billing_plan" id="planMrc" value="mrc"
                                            {{ $previousData && $previousData['billing_plan'] == 'mrc' ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="planMrc">Monthly (MRC)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="billing_plan" id="planQuarterly" value="quarterly"
                                            {{ $previousData && $previousData['billing_plan'] == 'quarterly' ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="planQuarterly">Quarterly</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Number of Prefixes --}}
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">IP Prefix Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Number of IP Prefixes <span class="text-danger">*</span></label>
                                <input type="number" name="ip_prefix_count" id="ipPrefixCount" class="form-control" 
                                    value="{{ $previousData['ip_prefix_count'] ?? '' }}" min="1" required>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- GSTIN for Billing --}}
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">GSTIN (For Billing)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">GSTIN <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" name="gstin" id="gstin" class="form-control" 
                                        value="{{ $previousData['gstin'] ?? '' }}" 
                                        placeholder="15 character GSTIN" maxlength="15" required>
                                    <button type="button" class="btn btn-outline-primary" id="verifyGstinBtn">Verify</button>
                                </div>
                                <div id="gstinVerifyStatus" class="mt-2"></div>
                                <input type="hidden" name="gstin_verified" id="gstinVerified" value="0">
                                <input type="hidden" name="gstin_verification_id" id="gstinVerificationId" value="">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Documents --}}
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Documents</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">New GST Document (if applicable)</label>
                                <input type="file" name="new_gst_document" id="newGstDocument" class="form-control" accept=".pdf">
                                <small class="text-muted">Upload PDF file (max 10MB)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('user.applications.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Submit Application</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '{{ csrf_token() }}';
    let panRequestId = null;
    let gstinVerificationId = null;

    // PAN Verification
    document.getElementById('verifyPanBtn').addEventListener('click', function() {
        const pan = document.getElementById('representativePan').value.trim().toUpperCase();
        const name = document.getElementById('representativeName').value.trim();
        const dob = document.getElementById('representativeDob').value;

        if (!pan || !name || !dob) {
            alert('Please fill Name, DOB and PAN before verification.');
            return;
        }

        const panRegex = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;
        if (!panRegex.test(pan)) {
            alert('Please enter a valid PAN (e.g. ABCDE1234F).');
            return;
        }

        this.disabled = true;
        this.textContent = 'Verifying...';
        const statusEl = document.getElementById('panVerifyStatus');
        statusEl.innerHTML = '<span class="badge bg-warning">Verifying...</span>';
        const btn = this;

        fetch('{{ route("user.applications.ix.verify-representative-pan") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                pan: pan,
                name: name,
                dob: dob
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                panRequestId = data.request_id;
                checkPanStatus();
            } else {
                statusEl.innerHTML = '<span class="badge bg-danger">' + data.message + '</span>';
                btn.disabled = false;
                btn.textContent = 'Verify';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            statusEl.innerHTML = '<span class="badge bg-danger">Error verifying PAN</span>';
            btn.disabled = false;
            btn.textContent = 'Verify';
        });
    });

    function checkPanStatus() {
        if (!panRequestId) return;

        fetch('{{ route("user.applications.ix.check-representative-pan-status") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                request_id: panRequestId
            })
        })
        .then(response => response.json())
        .then(data => {
            const statusEl = document.getElementById('panVerifyStatus');
            const btn = document.getElementById('verifyPanBtn');

            if (data.success) {
                statusEl.innerHTML = '<span class="badge bg-success">' + data.message + '</span>';
                document.getElementById('panVerified').value = '1';
                btn.disabled = false;
                btn.textContent = 'Verified';
            } else if (data.status === 'in_progress' || data.status === 'pending') {
                setTimeout(checkPanStatus, 2000);
            } else {
                statusEl.innerHTML = '<span class="badge bg-danger">' + data.message + '</span>';
                btn.disabled = false;
                btn.textContent = 'Verify';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            setTimeout(checkPanStatus, 2000);
        });
    }

    // Mobile OTP
    document.getElementById('sendMobileOtpBtn').addEventListener('click', function() {
        const mobile = document.getElementById('representativeMobile').value.trim();
        if (!mobile || mobile.length !== 10) {
            alert('Please enter a valid 10-digit mobile number.');
            return;
        }

        this.disabled = true;
        this.textContent = 'Sending...';

        fetch('{{ route("user.applications.ix.send-mobile-otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ mobile: mobile })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('mobileOtpSection').classList.remove('d-none');
                this.textContent = 'OTP Sent';
            } else {
                alert(data.message);
                this.disabled = false;
                this.textContent = 'Send OTP';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error sending OTP');
            this.disabled = false;
            this.textContent = 'Send OTP';
        });
    });

    document.getElementById('verifyMobileOtpBtn').addEventListener('click', function() {
        const mobile = document.getElementById('representativeMobile').value.trim();
        const otp = document.getElementById('mobileOtp').value.trim();

        if (!otp || otp.length !== 6) {
            alert('Please enter a valid 6-digit OTP.');
            return;
        }

        this.disabled = true;
        this.textContent = 'Verifying...';

        fetch('{{ route("user.applications.ix.verify-mobile-otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ mobile: mobile, otp: otp })
        })
        .then(response => response.json())
        .then(data => {
            const statusEl = document.getElementById('mobileVerifyStatus');
            if (data.success) {
                statusEl.innerHTML = '<span class="badge bg-success">' + data.message + '</span>';
                document.getElementById('mobileVerified').value = '1';
                document.getElementById('mobileOtpSection').classList.add('d-none');
            } else {
                statusEl.innerHTML = '<span class="badge bg-danger">' + data.message + '</span>';
                this.disabled = false;
                this.textContent = 'Verify OTP';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error verifying OTP');
            this.disabled = false;
            this.textContent = 'Verify OTP';
        });
    });

    // Email OTP
    document.getElementById('sendEmailOtpBtn').addEventListener('click', function() {
        const email = document.getElementById('representativeEmail').value.trim();
        if (!email) {
            alert('Please enter a valid email address.');
            return;
        }

        this.disabled = true;
        this.textContent = 'Sending...';

        fetch('{{ route("user.applications.ix.send-email-otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ email: email })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('emailOtpSection').classList.remove('d-none');
                this.textContent = 'OTP Sent';
            } else {
                alert(data.message);
                this.disabled = false;
                this.textContent = 'Send OTP';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error sending OTP');
            this.disabled = false;
            this.textContent = 'Send OTP';
        });
    });

    document.getElementById('verifyEmailOtpBtn').addEventListener('click', function() {
        const email = document.getElementById('representativeEmail').value.trim();
        const otp = document.getElementById('emailOtp').value.trim();

        if (!otp || otp.length !== 6) {
            alert('Please enter a valid 6-digit OTP.');
            return;
        }

        this.disabled = true;
        this.textContent = 'Verifying...';

        fetch('{{ route("user.applications.ix.verify-email-otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ email: email, otp: otp })
        })
        .then(response => response.json())
        .then(data => {
            const statusEl = document.getElementById('emailVerifyStatus');
            if (data.success) {
                statusEl.innerHTML = '<span class="badge bg-success">' + data.message + '</span>';
                document.getElementById('emailVerified').value = '1';
                document.getElementById('emailOtpSection').classList.add('d-none');
            } else {
                statusEl.innerHTML = '<span class="badge bg-danger">' + data.message + '</span>';
                this.disabled = false;
                this.textContent = 'Verify OTP';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error verifying OTP');
            this.disabled = false;
            this.textContent = 'Verify OTP';
        });
    });

    // GSTIN Verification
    document.getElementById('verifyGstinBtn').addEventListener('click', function() {
        const gstin = document.getElementById('gstin').value.trim().toUpperCase();

        if (!gstin || gstin.length !== 15) {
            alert('Please enter a valid 15-character GSTIN.');
            return;
        }

        this.disabled = true;
        this.textContent = 'Verifying...';
        const statusEl = document.getElementById('gstinVerifyStatus');
        statusEl.innerHTML = '<span class="badge bg-warning">Verifying...</span>';
        const btn = this;

        fetch('{{ route("user.applications.ix.verify-gstin") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ gstin: gstin })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                gstinVerificationId = data.verification_id;
                document.getElementById('gstinVerificationId').value = data.verification_id;
                checkGstinStatus();
            } else {
                statusEl.innerHTML = '<span class="badge bg-danger">' + data.message + '</span>';
                btn.disabled = false;
                btn.textContent = 'Verify';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            statusEl.innerHTML = '<span class="badge bg-danger">Error verifying GSTIN</span>';
            btn.disabled = false;
            btn.textContent = 'Verify';
        });
    });

    function checkGstinStatus() {
        if (!gstinVerificationId) return;

        fetch('{{ route("user.applications.ix.check-gstin-status") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                verification_id: gstinVerificationId
            })
        })
        .then(response => response.json())
        .then(data => {
            const statusEl = document.getElementById('gstinVerifyStatus');
            const btn = document.getElementById('verifyGstinBtn');

            if (data.success && data.is_verified) {
                statusEl.innerHTML = '<span class="badge bg-success">' + data.message + '</span>';
                document.getElementById('gstinVerified').value = '1';
                btn.disabled = false;
                btn.textContent = 'Verified';
            } else if (data.status === 'in_progress' || data.status === 'pending') {
                setTimeout(checkGstinStatus, 2000);
            } else {
                statusEl.innerHTML = '<span class="badge bg-danger">' + data.message + '</span>';
                btn.disabled = false;
                btn.textContent = 'Verify';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            setTimeout(checkGstinStatus, 2000);
        });
    }

    // Form submission validation
    document.getElementById('newIxApplicationForm').addEventListener('submit', function(e) {
        if (document.getElementById('panVerified').value !== '1') {
            e.preventDefault();
            alert('Please verify PAN before submitting.');
            return false;
        }
        if (document.getElementById('mobileVerified').value !== '1') {
            e.preventDefault();
            alert('Please verify mobile number before submitting.');
            return false;
        }
        if (document.getElementById('emailVerified').value !== '1') {
            e.preventDefault();
            alert('Please verify email before submitting.');
            return false;
        }
        if (document.getElementById('gstinVerified').value !== '1') {
            e.preventDefault();
            alert('Please verify GSTIN before submitting.');
            return false;
        }
    });
});
</script>
@endpush
@endsection

