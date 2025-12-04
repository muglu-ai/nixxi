@extends('user.layout')

@section('title', 'KYC Verification')

@push('styles')
<style>
    .kyc-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1050;
    }
    .kyc-modal {
        background: #ffffff;
        border-radius: 12px;
        max-width: 900px;
        width: 100%;
        max-height: 90vh;
        overflow: hidden;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }
    .kyc-modal-header {
        padding: 16px 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .kyc-steps {
        display: flex;
        gap: 8px;
    }
    .kyc-step {
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 12px;
        border: 1px solid #d1d5db;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .kyc-step-active {
        background-color: #10b981;
        color: #ffffff;
        border-color: #10b981;
    }
    .kyc-step-completed {
        background-color: #ecfdf5;
        color: #065f46;
        border-color: #6ee7b7;
    }
    .kyc-modal-body {
        padding: 16px 24px 20px 24px;
        overflow-y: auto;
        max-height: calc(90vh - 130px);
    }
    .kyc-modal-footer {
        padding: 12px 24px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #f9fafb;
    }
    .kyc-readonly {
        background-color: #ecfdf5 !important;
        border-color: #10b981 !important;
        cursor: not-allowed !important;
    }
    .kyc-badge-verified {
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 999px;
        background-color: #ecfdf5;
        color: #16a34a;
        border: 1px solid #6ee7b7;
    }
    .kyc-badge-pending {
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 999px;
        background-color: #fefce8;
        color: #a16207;
        border: 1px solid #facc15;
    }
</style>
@endpush

@section('content')
<div class="kyc-modal-backdrop">
    <div class="kyc-modal">
        <div class="kyc-modal-header">
            <div>
                <h5 class="mb-0">Complete Your KYC</h5>
                <small class="text-muted">Please complete KYC in 2 quick steps to continue using the portal.</small>
            </div>
            <div class="kyc-steps">
                <div id="kycStepIndicator1" class="kyc-step kyc-step-active">
                    <span class="badge bg-light text-dark border">1</span>
                    <span>Organisation Details</span>
                </div>
                <div id="kycStepIndicator2" class="kyc-step">
                    <span class="badge bg-light text-dark border">2</span>
                    <span>Authorised Representative</span>
                </div>
            </div>
        </div>

        <form id="kycForm">
            @csrf
            <input type="hidden" id="gst_verification_id" name="gst_verification_id" value="{{ $kyc->gst_verification_id }}">
            <input type="hidden" id="gst_verified" name="gst_verified" value="{{ $kyc->gst_verified ? '1' : '0' }}">
            <input type="hidden" id="udyam_verification_id" name="udyam_verification_id" value="{{ $kyc->udyam_verification_id }}">
            <input type="hidden" id="udyam_verified" name="udyam_verified" value="{{ $kyc->udyam_verified ? '1' : '0' }}">
            <input type="hidden" id="mca_verification_id" name="mca_verification_id" value="{{ $kyc->mca_verification_id }}">
            <input type="hidden" id="mca_verified" name="mca_verified" value="{{ $kyc->mca_verified ? '1' : '0' }}">

            <input type="hidden" id="contact_name_pan_dob_verified" name="contact_name_pan_dob_verified" value="{{ $kyc->contact_name_pan_dob_verified ? '1' : '0' }}">
            <input type="hidden" id="contact_email_verified" name="contact_email_verified" value="{{ $kyc->contact_email_verified ? '1' : '0' }}">
            <input type="hidden" id="contact_mobile_verified" name="contact_mobile_verified" value="{{ $kyc->contact_mobile_verified ? '1' : '0' }}">

            <div class="kyc-modal-body">
                {{-- Step 1: Organisation Details --}}
                <div id="kycStep1">
                    <h6 class="mb-3">Step 1: Organisation Details</h6>

                    <div class="alert alert-info py-2">
                        <small>
                            Please verify <strong>at least one</strong> of GSTIN, UDYAM or CIN.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Are you MSME? <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="is_msme" id="isMsmeYes" value="1" {{ $kyc->is_msme === true ? 'checked' : '' }} required>
                                <label class="form-check-label" for="isMsmeYes">Yes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="is_msme" id="isMsmeNo" value="0" {{ $kyc->is_msme === false ? 'checked' : (is_null($kyc->is_msme) ? 'checked' : '') }} required>
                                <label class="form-check-label" for="isMsmeNo">No</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">GSTIN</label>
                        <div class="input-group">
                            <input type="text"
                                   class="form-control"
                                   id="gstin"
                                   name="gstin"
                                   maxlength="15"
                                   value="{{ $kyc->gstin }}"
                                   placeholder="Enter 15 character GSTIN">
                            <button class="btn btn-outline-success" type="button" id="verifyGstBtn">Verify GST</button>
                        </div>
                        <small id="gstStatus" class="form-text text-muted">
                            Verify GSTIN if applicable.
                        </small>
                    </div>

                    <div class="mb-3" id="udyamContainer" style="display: none;">
                        <label class="form-label mb-1">UDYAM Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text"
                                   class="form-control"
                                   id="udyam_number"
                                   name="udyam_number"
                                   value="{{ $kyc->udyam_number }}">
                            <button class="btn btn-outline-secondary" type="button" id="verifyUdyamBtn">Verify UDYAM</button>
                        </div>
                        <small id="udyamStatus" class="form-text text-muted">
                            Provide UDYAM number for MSME verification.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label mb-1">CIN (MCA) Number (Optional)</label>
                        <div class="input-group">
                            <input type="text"
                                   class="form-control"
                                   id="cin"
                                   name="cin"
                                   value="{{ $kyc->cin }}">
                            <button class="btn btn-outline-secondary" type="button" id="verifyMcaBtn">Verify CIN</button>
                        </div>
                        <small id="mcaStatus" class="form-text text-muted">
                            Provide CIN if the organisation is registered with MCA.
                        </small>
                    </div>
                </div>

                {{-- Step 2: Authorised Representative --}}
                <div id="kycStep2" style="display: none;">
                    <h6 class="mb-3">Step 2: Authorised Representative</h6>

                    <div class="alert alert-info py-2">
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text"
                                   class="form-control"
                                   id="contact_name"
                                   name="contact_name"
                                   value="{{ $kyc->contact_name }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date of Birth</label>
                            <input type="date"
                                   class="form-control"
                                   id="contact_dob"
                                   name="contact_dob"
                                   value="{{ $kyc->contact_dob ? $kyc->contact_dob->format('Y-m-d') : '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">PAN</label>
                            <input type="text"
                                   class="form-control"
                                   id="contact_pan"
                                   name="contact_pan"
                                   maxlength="10"
                                   value="{{ $kyc->contact_pan }}">
                        </div>
                        <div class="col-md-6 d-flex flex-column justify-content-end align-items-start">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span id="panVerifyStatus" class="kyc-badge-pending">
                                    PAN / Name / DOB verification required after any change.
                                </span>
                                <button class="btn btn-outline-primary btn-sm" type="button" id="verifyPanNameBtn">
                                    Verify
                                </button>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <div class="input-group">
                                <input type="email"
                                       class="form-control"
                                       id="contact_email"
                                       name="contact_email"
                                       value="{{ $kyc->contact_email }}">
                                <button class="btn btn-outline-primary" type="button" id="emailSendOtpBtn">
                                    Send OTP
                                </button>
                            </div>
                            <div class="input-group mt-2 d-none" id="emailOtpRow">
                                <input type="text"
                                       class="form-control"
                                       id="email_otp_input"
                                       maxlength="6"
                                       placeholder="Enter 6-digit OTP">
                                <button class="btn btn-outline-success" type="button" id="emailVerifyOtpBtn">
                                    Verify
                                </button>
                            </div>
                            <small id="emailOtpStatus" class="form-text text-muted"></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile</label>
                            <div class="input-group">
                                <input type="tel"
                                       class="form-control"
                                       id="contact_mobile"
                                       name="contact_mobile"
                                       maxlength="10"
                                       value="{{ $kyc->contact_mobile }}">
                                <button class="btn btn-outline-primary" type="button" id="mobileSendOtpBtn">
                                    Send OTP
                                </button>
                            </div>
                            <div class="input-group mt-2 d-none" id="mobileOtpRow">
                                <input type="text"
                                       class="form-control"
                                       id="mobile_otp_input"
                                       maxlength="6"
                                       placeholder="Enter 6-digit OTP">
                                <button class="btn btn-outline-success" type="button" id="mobileVerifyOtpBtn">
                                    Verify
                                </button>
                            </div>
                            <small id="mobileOtpStatus" class="form-text text-muted"></small>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="mb-3">
                        <label class="form-label">Billing Address (from verified IDs)</label>
                        <select id="billingAddressSelect" name="billing_address_select" class="form-select">
                            <option value="">Select a billing address</option>
                        </select>
                        <input type="hidden" id="billing_address_source" name="billing_address_source" value="">
                        <input type="hidden" id="billing_address" name="billing_address" value="">
                    </div>

                    <div id="billingAddressCard" class="card border-0 shadow-sm d-none">
                        <div class="card-body py-2">
                            <small class="text-muted d-block mb-1">Selected Billing Address</small>
                            <div id="billingAddressLabel" class="fw-semibold small mb-1"></div>
                            <div id="billingAddressText" class="small"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="kyc-modal-footer">
                <div>
                    <small id="kycOverallStatus" class="text-muted">
                        Complete all verifications to submit KYC.
                    </small>
                </div>
                <div>
                    <button type="button" class="btn btn-secondary btn-sm me-2" id="prevStepBtn" disabled>Back</button>
                    <button type="button" class="btn btn-success btn-sm" id="nextStepBtn">Next</button>
                    <button type="submit" class="btn btn-success btn-sm d-none" id="submitKycBtn">Submit KYC</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const kycState = {
        currentStep: 1,
        billingAddresses: [], // {key, source, label, address}
        companyName: null, // canonical company name from first verified doc
        emailOtpSent: false,
        mobileOtpSent: false,
    };

    function setStep(step) {
        kycState.currentStep = step;

        document.getElementById('kycStep1').style.display = step === 1 ? 'block' : 'none';
        document.getElementById('kycStep2').style.display = step === 2 ? 'block' : 'none';

        document.getElementById('kycStepIndicator1').classList.toggle('kyc-step-active', step === 1);
        document.getElementById('kycStepIndicator1').classList.toggle('kyc-step-completed', step === 2);
        document.getElementById('kycStepIndicator2').classList.toggle('kyc-step-active', step === 2);

        document.getElementById('prevStepBtn').disabled = (step === 1);
        document.getElementById('nextStepBtn').classList.toggle('d-none', step === 2);
        document.getElementById('submitKycBtn').classList.toggle('d-none', step === 1);
    }

    document.getElementById('nextStepBtn').addEventListener('click', function () {
        const gstVerified = document.getElementById('gst_verified').value === '1';
        const udyamVerified = document.getElementById('udyam_verified').value === '1';
        const mcaVerified = document.getElementById('mca_verified').value === '1';

        if (!gstVerified && !udyamVerified && !mcaVerified) {
            alert('Please verify at least one of GSTIN, UDYAM or CIN before continuing to the next step.');
            return;
        }
        setStep(2);
    });

    document.getElementById('prevStepBtn').addEventListener('click', function () {
        setStep(1);
    });

    function refreshBillingAddressOptions() {
        const select = document.getElementById('billingAddressSelect');
        const card = document.getElementById('billingAddressCard');
        const labelEl = document.getElementById('billingAddressLabel');
        const textEl = document.getElementById('billingAddressText');

        // Reset select
        while (select.options.length > 1) {
            select.remove(1);
        }

        kycState.billingAddresses.forEach((item) => {
            const opt = document.createElement('option');
            opt.value = item.key;
            opt.textContent = item.label;
            select.appendChild(opt);
        });

        if (kycState.billingAddresses.length === 0) {
            card.classList.add('d-none');
            document.getElementById('billing_address_source').value = '';
            document.getElementById('billing_address').value = '';
        } else {
            // If there is only one address, pre-select it
            if (kycState.billingAddresses.length === 1) {
                const only = kycState.billingAddresses[0];
                select.value = only.key;
                document.getElementById('billing_address_source').value = only.source;
                document.getElementById('billing_address').value = JSON.stringify({
                    source: only.source,
                    label: only.label,
                    address: only.address,
                });
                labelEl.textContent = only.label;
                textEl.textContent = only.address;
                card.classList.remove('d-none');
            }
        }
    }

    document.getElementById('billingAddressSelect').addEventListener('change', function () {
        const selectedKey = this.value;
        const card = document.getElementById('billingAddressCard');
        const labelEl = document.getElementById('billingAddressLabel');
        const textEl = document.getElementById('billingAddressText');

        if (!selectedKey) {
            card.classList.add('d-none');
            document.getElementById('billing_address_source').value = '';
            document.getElementById('billing_address').value = '';
            return;
        }

        const selected = kycState.billingAddresses.find(item => item.key === selectedKey);
        if (selected) {
            document.getElementById('billing_address_source').value = selected.source;
            document.getElementById('billing_address').value = JSON.stringify({
                source: selected.source,
                label: selected.label,
                address: selected.address,
            });
            labelEl.textContent = selected.label;
            textEl.textContent = selected.address;
            card.classList.remove('d-none');
        }
    });

    // GST verification (uses existing IRINN verification endpoints)
    document.getElementById('verifyGstBtn').addEventListener('click', function () {
        const gstinInput = document.getElementById('gstin');
        const gstin = gstinInput.value.trim().toUpperCase();
        const statusEl = document.getElementById('gstStatus');

        if (!gstin || gstin.length !== 15) {
            alert('Please enter a valid 15 character GSTIN.');
            return;
        }

        this.disabled = true;
        this.textContent = 'Verifying...';
        statusEl.textContent = 'Initiating GST verification...';
        statusEl.className = 'form-text text-info';

        fetch('{{ route("user.applications.irin.verify-gst") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ gstin: gstin })
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to start GST verification.');
                }

                const requestId = data.request_id;
                statusEl.textContent = 'GST verification in progress...';
                pollVerificationStatus('gstin', requestId, (result) => {
                    if (result.is_verified) {
                        let verificationData = result.verification_data || {};
                        const companyName = verificationData.company_name || verificationData.legal_name || verificationData.trade_name || null;

                        // Company name consistency check
                        if (companyName) {
                            const normalized = companyName.trim().toLowerCase();
                            if (kycState.companyName && kycState.companyName !== normalized) {
                                alert('Company name from GST does not match previously verified document. Please verify correct details.');
                                document.getElementById('gst_verified').value = '0';
                                statusEl.textContent = 'Company name mismatch. GST verification rejected.';
                                statusEl.className = 'form-text text-danger';
                                return;
                            }
                            kycState.companyName = normalized;
                        }

                        document.getElementById('gst_verified').value = '1';
                        document.getElementById('gst_verification_id').value = data.verification_id;
                        gstinInput.readOnly = true;
                        gstinInput.classList.add('kyc-readonly');
                        const btn = document.getElementById('verifyGstBtn');
                        btn.disabled = true;
                        btn.textContent = 'Verified';
                        btn.classList.remove('btn-outline-success');
                        btn.classList.add('btn-success');
                        statusEl.textContent = 'GSTIN verified successfully.';
                        statusEl.className = 'form-text text-success';
                        document.getElementById('kycStepIndicator1').classList.add('kyc-step-completed');

                        // Add GST address to billing options if available
                        if (verificationData && verificationData.primary_address) {
                            const existingIndex = kycState.billingAddresses.findIndex(item => item.source === 'gstin');
                            const entry = {
                                key: 'gstin:' + (verificationData.gstin || gstin),
                                source: 'gstin',
                                label: 'GSTIN - ' + (verificationData.gstin || gstin),
                                address: verificationData.primary_address,
                            };
                            if (existingIndex >= 0) {
                                kycState.billingAddresses[existingIndex] = entry;
                            } else {
                                kycState.billingAddresses.push(entry);
                            }
                            refreshBillingAddressOptions();
                        }
                    } else {
                        document.getElementById('gst_verified').value = '0';
                        statusEl.textContent = result.message || 'GSTIN verification failed.';
                        statusEl.className = 'form-text text-danger';
                    }
                });
            })
            .catch(error => {
                console.error(error);
                statusEl.textContent = error.message || 'An error occurred while verifying GSTIN.';
                statusEl.className = 'form-text text-danger';
            })
            .finally(() => {
                this.disabled = false;
                this.textContent = 'Verify GST';
            });
    });

    function pollVerificationStatus(type, requestId, callback, retries = 0, maxRetries = 10) {
        if (retries > maxRetries) {
            callback({ is_verified: false, message: 'Verification timeout. Please try again.' });
            return;
        }

        fetch('{{ route("user.applications.irin.check-verification-status") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ type: type, request_id: requestId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'completed') {
                    callback(data);
                } else if (data.status === 'failed') {
                    callback({ is_verified: false, message: data.message || 'Verification failed.' });
                } else {
                    setTimeout(() => {
                        pollVerificationStatus(type, requestId, callback, retries + 1, maxRetries);
                    }, 2000);
                }
            })
            .catch(error => {
                console.error(error);
                callback({ is_verified: false, message: 'Error while checking verification status.' });
            });
    }

    // UDYAM verification
    document.getElementById('verifyUdyamBtn').addEventListener('click', function () {
        const udyamInput = document.getElementById('udyam_number');
        const udyam = udyamInput.value.trim();
        const statusEl = document.getElementById('udyamStatus');

        if (!udyam) {
            alert('Please enter UDYAM number to verify.');
            return;
        }

        this.disabled = true;
        this.textContent = 'Verifying...';
        statusEl.textContent = 'UDYAM verification in progress...';
        statusEl.className = 'form-text text-info';

        fetch('{{ route("user.applications.irin.verify-udyam") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ uam_number: udyam })
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to start UDYAM verification.');
                }

                const requestId = data.request_id;
                pollVerificationStatus('udyam', requestId, (result) => {
                    if (result.is_verified) {
                        const verificationData = result.verification_data || {};
                        const companyName = verificationData.company_name || null;

                        if (companyName) {
                            const normalized = companyName.trim().toLowerCase();
                            if (kycState.companyName && kycState.companyName !== normalized) {
                                alert('Company name from UDYAM does not match previously verified document. Please verify correct details.');
                                document.getElementById('udyam_verified').value = '0';
                                statusEl.textContent = 'Company name mismatch. UDYAM verification rejected.';
                                statusEl.className = 'form-text text-danger';
                                return;
                            }
                            if (!kycState.companyName) {
                                kycState.companyName = normalized;
                            }
                        }

                        document.getElementById('udyam_verified').value = '1';
                        document.getElementById('udyam_verification_id').value = data.verification_id;
                        udyamInput.readOnly = true;
                        udyamInput.classList.add('kyc-readonly');
                        const btn = document.getElementById('verifyUdyamBtn');
                        btn.disabled = true;
                        btn.textContent = 'Verified';
                        btn.classList.remove('btn-outline-secondary');
                        btn.classList.add('btn-success');
                        statusEl.textContent = 'UDYAM verified successfully.';
                        statusEl.className = 'form-text text-success';

                        if (verificationData && verificationData.primary_address) {
                            const existingIndex = kycState.billingAddresses.findIndex(item => item.source === 'udyam');
                            const entry = {
                                key: 'udyam:' + (verificationData.uam_number || udyam),
                                source: 'udyam',
                                label: 'UDYAM - ' + (verificationData.uam_number || udyam),
                                address: verificationData.primary_address,
                            };
                            if (existingIndex >= 0) {
                                kycState.billingAddresses[existingIndex] = entry;
                            } else {
                                kycState.billingAddresses.push(entry);
                            }
                            refreshBillingAddressOptions();
                        }
                    } else {
                        document.getElementById('udyam_verified').value = '0';
                        statusEl.textContent = result.message || 'UDYAM verification failed.';
                        statusEl.className = 'form-text text-danger';
                    }
                });
            })
            .catch(error => {
                console.error(error);
                statusEl.textContent = error.message || 'An error occurred while verifying UDYAM.';
                statusEl.className = 'form-text text-danger';
            })
            .finally(() => {
                this.disabled = false;
                this.textContent = 'Verify UDYAM';
            });
    });

    // MCA CIN verification
    document.getElementById('verifyMcaBtn').addEventListener('click', function () {
        const cinInput = document.getElementById('cin');
        const cin = cinInput.value.trim();
        const statusEl = document.getElementById('mcaStatus');

        if (!cin) {
            alert('Please enter CIN to verify.');
            return;
        }

        this.disabled = true;
        this.textContent = 'Verifying...';
        statusEl.textContent = 'CIN verification in progress...';
        statusEl.className = 'form-text text-info';

        fetch('{{ route("user.applications.irin.verify-mca") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ cin: cin })
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to start CIN verification.');
                }

                const requestId = data.request_id;
                pollVerificationStatus('mca', requestId, (result) => {
                    if (result.is_verified) {
                        const verificationData = result.verification_data || {};
                        const companyName = verificationData.company_name || null;

                        if (companyName) {
                            const normalized = companyName.trim().toLowerCase();
                            if (kycState.companyName && kycState.companyName !== normalized) {
                                alert('Company name from CIN does not match previously verified document. Please verify correct details.');
                                document.getElementById('mca_verified').value = '0';
                                statusEl.textContent = 'Company name mismatch. CIN verification rejected.';
                                statusEl.className = 'form-text text-danger';
                                return;
                            }
                            if (!kycState.companyName) {
                                kycState.companyName = normalized;
                            }
                        }

                        document.getElementById('mca_verified').value = '1';
                        document.getElementById('mca_verification_id').value = data.verification_id;
                        cinInput.readOnly = true;
                        cinInput.classList.add('kyc-readonly');
                        const btn = document.getElementById('verifyMcaBtn');
                        btn.disabled = true;
                        btn.textContent = 'Verified';
                        btn.classList.remove('btn-outline-secondary');
                        btn.classList.add('btn-success');
                        statusEl.textContent = 'CIN verified successfully.';
                        statusEl.className = 'form-text text-success';

                        if (verificationData && verificationData.primary_address) {
                            const existingIndex = kycState.billingAddresses.findIndex(item => item.source === 'mca');
                            const entry = {
                                key: 'mca:' + (verificationData.cin || cin),
                                source: 'mca',
                                label: 'CIN - ' + (verificationData.cin || cin),
                                address: verificationData.primary_address,
                            };
                            if (existingIndex >= 0) {
                                kycState.billingAddresses[existingIndex] = entry;
                            } else {
                                kycState.billingAddresses.push(entry);
                            }
                            refreshBillingAddressOptions();
                        }
                    } else {
                        document.getElementById('mca_verified').value = '0';
                        statusEl.textContent = result.message || 'CIN verification failed.';
                        statusEl.className = 'form-text text-danger';
                    }
                });
            })
            .catch(error => {
                console.error(error);
                statusEl.textContent = error.message || 'An error occurred while verifying CIN.';
                statusEl.className = 'form-text text-danger';
            })
            .finally(() => {
                this.disabled = false;
                this.textContent = 'Verify CIN';
            });
    });

    // PAN / Name / DOB verification (re-use registration PAN API + status polling)
    document.getElementById('verifyPanNameBtn').addEventListener('click', function () {
        const nameInput = document.getElementById('contact_name');
        const dobInput = document.getElementById('contact_dob');
        const panInput = document.getElementById('contact_pan');
        const statusEl = document.getElementById('panVerifyStatus');

        const fullname = nameInput.value.trim();
        const dateofbirth = dobInput.value;
        const pancardno = panInput.value.trim().toUpperCase();

        if (!fullname || !dateofbirth || !pancardno) {
            alert('Please fill Name, DOB and PAN before verification.');
            return;
        }

        // Basic PAN format check before hitting API
        const panRegex = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;
        if (!panRegex.test(pancardno)) {
            alert('Please enter a valid PAN (e.g. ABCDE1234F).');
            return;
        }

        this.disabled = true;
        this.textContent = 'Verifying...';
        statusEl.textContent = 'Verifying...';
        statusEl.className = 'kyc-badge-pending';

        const btn = this;

        // First create PAN verification task
        fetch('{{ route("register.verify.pan") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                fullname: fullname,
                dateofbirth: dateofbirth,
                pancardno: pancardno
            })
        })
            .then(response => response.text())
            .then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('Unexpected response from server while initiating PAN verification.');
                }
                if (!data.success || !data.request_id) {
                    throw new Error(data.message || 'Failed to initiate PAN verification.');
                }

                statusEl.textContent = 'Verifying PAN...';

                // Poll PAN status using existing register.check.pan.status endpoint
                pollKycPanStatus(data.request_id, (result) => {
                    if (result.success) {
                        document.getElementById('contact_name_pan_dob_verified').value = '1';
                        nameInput.readOnly = true;
                        dobInput.readOnly = true;
                        panInput.readOnly = true;
                        nameInput.classList.add('kyc-readonly');
                        dobInput.classList.add('kyc-readonly');
                        panInput.classList.add('kyc-readonly');
                        btn.disabled = true;
                        btn.textContent = 'Verified';
                        btn.classList.remove('btn-outline-primary');
                        btn.classList.add('btn-success');
                        statusEl.textContent = 'PAN / Name / DOB verified.';
                        statusEl.className = 'kyc-badge-verified';
                    } else {
                        document.getElementById('contact_name_pan_dob_verified').value = '0';
                        statusEl.textContent = result.message || 'PAN verification failed.';
                        statusEl.className = 'kyc-badge-pending';
                        btn.disabled = false;
                        btn.textContent = 'Verify';
                    }
                });
            })
            .catch(error => {
                console.error(error);
                statusEl.textContent = error.message || 'Error while verifying PAN details.';
                statusEl.className = 'kyc-badge-pending';
                btn.disabled = false;
                btn.textContent = 'Verify';
            });
    });

    // Poll PAN verification status for KYC (uses RegisterController@checkPanStatus)
    function pollKycPanStatus(requestId, callback, retries = 0, maxRetries = 15) {
        fetch('{{ route("register.check.pan.status") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ request_id: requestId })
        })
            .then(response => response.text())
            .then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    callback({
                        success: false,
                        message: 'Unexpected response from server while checking PAN verification status.',
                    });
                    return;
                }
                if (data.status === 'completed') {
                    callback(data);
                } else if (data.status === 'failed') {
                    callback({
                        success: false,
                        message: data.message || 'PAN verification failed.',
                    });
                } else if (retries < maxRetries) {
                    setTimeout(() => pollKycPanStatus(requestId, callback, retries + 1, maxRetries), 2000);
                } else {
                    callback({
                        success: false,
                        message: 'PAN verification timeout. Please try again.',
                    });
                }
            })
            .catch(error => {
                console.error(error);
                callback({
                    success: false,
                    message: 'Error while checking PAN verification status.',
                });
            });
    }

    // Email OTP - Send
    document.getElementById('emailSendOtpBtn').addEventListener('click', function () {
        const emailInput = document.getElementById('contact_email');
        const email = emailInput.value.trim();
        const statusEl = document.getElementById('emailOtpStatus');
        const otpRow = document.getElementById('emailOtpRow');

        if (!email) {
            alert('Please enter email to verify.');
            return;
        }

        this.disabled = true;
        this.textContent = 'Sending...';
        statusEl.textContent = 'Sending OTP to email...';

        fetch('{{ route("register.send.email.otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ email: email })
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to send OTP.');
                }

                kycState.emailOtpSent = true;
                otpRow.classList.remove('d-none');
                statusEl.textContent = 'OTP sent to your email. Enter it below and click Verify.';
                statusEl.className = 'form-text text-info';
            })
            .catch(error => {
                console.error(error);
                statusEl.textContent = error.message || 'Error while sending email OTP.';
                statusEl.className = 'form-text text-danger';
            })
            .finally(() => {
                this.disabled = false;
                this.textContent = 'Send OTP';
            });
    });

    // Email OTP - Verify (OTP or Master OTP)
    document.getElementById('emailVerifyOtpBtn').addEventListener('click', function () {
        const emailInput = document.getElementById('contact_email');
        const email = emailInput.value.trim();
        const statusEl = document.getElementById('emailOtpStatus');
        const otpInput = document.getElementById('email_otp_input');
        const otpRow = document.getElementById('emailOtpRow');

        const otp = otpInput.value.trim();
        if (!otp || otp.length !== 6) {
            alert('Please enter a valid 6-digit OTP.');
            return;
        }

        this.disabled = true;
        this.textContent = 'Verifying...';

        fetch('{{ route("register.verify.email.otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ email: email, otp: otp, master_otp: otp })
        })
            .then(response => response.json())
            .then(data => {
                if (data && data.success) {
                    document.getElementById('contact_email_verified').value = '1';
                    emailInput.readOnly = true;
                    emailInput.classList.add('kyc-readonly');
                    emailInput.classList.add('bg-success', 'text-black');
                    emailInput.value = email;
                    otpRow.classList.add('d-none');
                    const sendBtn = document.getElementById('emailSendOtpBtn');
                    sendBtn.disabled = true;
                    sendBtn.textContent = 'Verified';
                    sendBtn.classList.remove('btn-outline-primary');
                    sendBtn.classList.add('btn-success');
                    statusEl.textContent = 'Email verified.';
                    statusEl.className = 'form-text text-success';
                } else {
                    document.getElementById('contact_email_verified').value = '0';
                    statusEl.textContent = (data && data.message) ? data.message : 'Email verification failed.';
                    statusEl.className = 'form-text text-danger';
                }
            })
            .catch(error => {
                console.error(error);
                statusEl.textContent = error.message || 'Error while verifying email.';
                statusEl.className = 'form-text text-danger';
            })
            .finally(() => {
                this.disabled = false;
                this.textContent = 'Verify';
            });
    });

    // Mobile OTP - Send
    document.getElementById('mobileSendOtpBtn').addEventListener('click', function () {
        const mobileInput = document.getElementById('contact_mobile');
        const mobile = mobileInput.value.trim();
        const statusEl = document.getElementById('mobileOtpStatus');
        const otpRow = document.getElementById('mobileOtpRow');

        if (!mobile || mobile.length !== 10) {
            alert('Please enter valid 10-digit mobile to verify.');
            return;
        }

        this.disabled = true;
        this.textContent = 'Sending...';
        statusEl.textContent = 'Sending OTP to mobile...';

        fetch('{{ route("register.send.mobile.otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ mobile: mobile })
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to send OTP.');
                }

                kycState.mobileOtpSent = true;
                otpRow.classList.remove('d-none');
                statusEl.textContent = 'OTP sent to your mobile. Enter it below and click Verify.';
                statusEl.className = 'form-text text-info';
            })
            .catch(error => {
                console.error(error);
                statusEl.textContent = error.message || 'Error while sending mobile OTP.';
                statusEl.className = 'form-text text-danger';
            })
            .finally(() => {
                this.disabled = false;
                this.textContent = 'Send OTP';
            });
    });

    // Mobile OTP - Verify (OTP or Master OTP)
    document.getElementById('mobileVerifyOtpBtn').addEventListener('click', function () {
        const mobileInput = document.getElementById('contact_mobile');
        const mobile = mobileInput.value.trim();
        const statusEl = document.getElementById('mobileOtpStatus');
        const otpInput = document.getElementById('mobile_otp_input');
        const otpRow = document.getElementById('mobileOtpRow');

        const otp = otpInput.value.trim();
        if (!otp || otp.length !== 6) {
            alert('Please enter a valid 6-digit OTP.');
            return;
        }

        this.disabled = true;
        this.textContent = 'Verifying...';

        fetch('{{ route("register.verify.mobile.otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ mobile: mobile, otp: otp, master_otp: otp })
        })
            .then(response => response.json())
            .then(data => {
                if (data && data.success) {
                    document.getElementById('contact_mobile_verified').value = '1';
                    mobileInput.readOnly = true;
                    mobileInput.classList.add('kyc-readonly');
                    mobileInput.classList.add('bg-success', 'text-black');
                    mobileInput.value = mobile;
                    otpRow.classList.add('d-none');
                    const sendBtn = document.getElementById('mobileSendOtpBtn');
                    sendBtn.disabled = true;
                    sendBtn.textContent = 'Verified';
                    sendBtn.classList.remove('btn-outline-primary');
                    sendBtn.classList.add('btn-success');
                    statusEl.textContent = 'Mobile verified.';
                    statusEl.className = 'form-text text-success';
                } else {
                    document.getElementById('contact_mobile_verified').value = '0';
                    statusEl.textContent = (data && data.message) ? data.message : 'Mobile verification failed.';
                    statusEl.className = 'form-text text-danger';
                }
            })
            .catch(error => {
                console.error(error);
                statusEl.textContent = error.message || 'Error while verifying mobile.';
                statusEl.className = 'form-text text-danger';
            })
            .finally(() => {
                this.disabled = false;
                this.textContent = 'Verify';
            });
    });

    // Final submit
    document.getElementById('kycForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const gstVerified = document.getElementById('gst_verified').value === '1';
        const udyamVerified = document.getElementById('udyam_verified').value === '1';
        const mcaVerified = document.getElementById('mca_verified').value === '1';
        const namePanDobVerified = document.getElementById('contact_name_pan_dob_verified').value === '1';
        const emailVerified = document.getElementById('contact_email_verified').value === '1';
        const mobileVerified = document.getElementById('contact_mobile_verified').value === '1';

        if ((!gstVerified && !udyamVerified && !mcaVerified) || !namePanDobVerified || !emailVerified || !mobileVerified) {
            alert('Please complete all required verifications (at least one of GSTIN/UDYAM/CIN, PAN details, Email and Mobile) before submitting KYC.');
            return;
        }

        const formData = new FormData(this);
        const payload = {};
        formData.forEach((value, key) => {
            payload[key] = value;
        });

        document.getElementById('submitKycBtn').disabled = true;
        document.getElementById('submitKycBtn').textContent = 'Submitting...';

        fetch('{{ route("user.kyc.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('KYC submitted successfully.');
                    window.location.href = '{{ route("user.dashboard") }}';
                } else {
                    throw new Error(data.message || 'Failed to submit KYC.');
                }
            })
            .catch(error => {
                console.error(error);
                alert(error.message || 'Error while submitting KYC.');
            })
            .finally(() => {
                document.getElementById('submitKycBtn').disabled = false;
                document.getElementById('submitKycBtn').textContent = 'Submit KYC';
            });
    });

    // Handle MSME question - show/hide UDYAM field
    const isMsmeYes = document.getElementById('isMsmeYes');
    const isMsmeNo = document.getElementById('isMsmeNo');
    const udyamContainer = document.getElementById('udyamContainer');
    const udyamNumberInput = document.getElementById('udyam_number');
    const verifyUdyamBtn = document.getElementById('verifyUdyamBtn');

    function toggleUdyamField() {
        if (isMsmeYes.checked) {
            udyamContainer.style.display = 'block';
            udyamNumberInput.setAttribute('required', 'required');
        } else {
            udyamContainer.style.display = 'none';
            udyamNumberInput.removeAttribute('required');
            udyamNumberInput.value = '';
            document.getElementById('udyam_verified').value = '0';
            document.getElementById('udyam_verification_id').value = '';
        }
    }

    // Initialize on page load
    if (isMsmeYes.checked) {
        udyamContainer.style.display = 'block';
        udyamNumberInput.setAttribute('required', 'required');
    }

    isMsmeYes.addEventListener('change', toggleUdyamField);
    isMsmeNo.addEventListener('change', toggleUdyamField);

    // Initial state
    setStep(1);
</script>
@endpush


