@extends('admin.layout')

@section('title', 'Backend Data Entry')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1">Backend Data Entry</h2>
            <p class="mb-0">Register user and create application (No emails will be sent)</p>
            <div class="accent-line"></div>
        </div>
    </div>

    @if(session('success') && session('credentials'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <h5 class="alert-heading">Success!</h5>
        <p>{{ session('success') }}</p>
        <hr>
        <h6>Login Credentials (Please save these):</h6>
        <div class="bg-light p-3 rounded mb-2">
            <p class="mb-1"><strong>Registration ID:</strong> {{ session('credentials.registration_id') }}</p>
            <p class="mb-1"><strong>Email:</strong> {{ session('credentials.email') }}</p>
            <p class="mb-1"><strong>Password:</strong> <code>{{ session('credentials.password') }}</code></p>
            <p class="mb-1"><strong>Email OTP:</strong> <code>{{ session('credentials.email_otp') }}</code></p>
            <p class="mb-1"><strong>Mobile OTP:</strong> <code>{{ session('credentials.mobile_otp') }}</code></p>
            <p class="mb-0"><strong>Application ID:</strong> {{ session('credentials.application_id') }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.backend-data-entry.store') }}" enctype="multipart/form-data" id="backendDataEntryForm">
        @csrf
        <input type="hidden" name="email_otp" id="emailOtpInput" value="">
        <input type="hidden" name="mobile_otp" id="mobileOtpInput" value="">
        <input type="hidden" name="generated_password" id="generatedPasswordInput" value="">

        <!-- Registration Section -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
            <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                <h5 class="mb-0">User Registration Details</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Registration Type <span class="text-danger">*</span></label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="registration_type" id="reg_type_entity" value="entity" {{ old('registration_type', 'entity') === 'entity' ? 'checked' : '' }} required>
                                <label class="form-check-label" for="reg_type_entity">Entity</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="registration_type" id="reg_type_individual" value="individual" {{ old('registration_type') === 'individual' ? 'checked' : '' }} required>
                                <label class="form-check-label" for="reg_type_individual">Individual</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="fullname" class="form-label" id="fullnameLabel">Full Name (As per PAN) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('fullname') is-invalid @enderror" id="fullname" name="fullname" value="{{ old('fullname') }}" required>
                        @error('fullname')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="dateofbirth" class="form-label" id="dateofbirthLabel">Date of Birth (As per PAN) <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('dateofbirth') is-invalid @enderror" id="dateofbirth" name="dateofbirth" value="{{ old('dateofbirth') }}" required>
                        @error('dateofbirth')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="pancardno" class="form-label">PAN Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control @error('pancardno') is-invalid @enderror" id="pancardno" name="pancardno" value="{{ old('pancardno') }}" placeholder="ABCDE1234F" maxlength="10" required>
                            <button type="button" class="btn btn-outline-primary" id="verifyPanBtn" onclick="verifyPan()">Verify PAN</button>
                        </div>
                        <div id="panVerificationStatus" class="mt-2" style="display: none;"></div>
                        @error('pancardno')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                        <div id="emailOtpDisplay" class="mt-2">
                            <small class="text-muted"><strong>Email OTP (will be generated):</strong> <code id="emailOtpValue" class="text-primary">---</code></small>
                        </div>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="mobile" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control @error('mobile') is-invalid @enderror" id="mobile" name="mobile" value="{{ old('mobile') }}" placeholder="10-digit mobile number" maxlength="10" required>
                        <div id="mobileOtpDisplay" class="mt-2">
                            <small class="text-muted"><strong>Mobile OTP (will be generated):</strong> <code id="mobileOtpValue" class="text-primary">---</code></small>
                        </div>
                        @error('mobile')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <strong>Note:</strong> OTPs and password will be generated automatically and displayed below the fields. No emails will be sent. These credentials will be shown again after successful submission.
                        </div>
                        <div class="bg-light p-3 rounded">
                            <p class="mb-1"><strong>Generated Password (will be shown):</strong> <code id="generatedPassword" class="text-primary">---</code></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Application Section -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
            <div class="card-header bg-success text-white" style="border-radius: 16px 16px 0 0;">
                <h5 class="mb-0">IX Application Details</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Member Type -->
                    <div class="col-md-6">
                        <label class="form-label">Member Type <span class="text-danger">*</span></label>
                        <select name="member_type" id="memberType" class="form-select" required>
                            <option value="">Select Member Type</option>
                            <option value="isp" {{ old('member_type') === 'isp' ? 'selected' : '' }}>ISP</option>
                            <option value="cdn" {{ old('member_type') === 'cdn' ? 'selected' : '' }}>CDN</option>
                            <option value="vno" {{ old('member_type') === 'vno' ? 'selected' : '' }}>VNO</option>
                            <option value="govt" {{ old('member_type') === 'govt' ? 'selected' : '' }}>Government Entity</option>
                            <option value="others" {{ old('member_type') === 'others' ? 'selected' : '' }}>Others</option>
                        </select>
                        <input type="text" name="member_type_other" id="memberTypeOther" class="form-control mt-2 d-none" placeholder="Specify member type" value="{{ old('member_type_other') }}">
                    </div>

                    <!-- Location -->
                    <div class="col-md-6">
                        <label class="form-label">NIXI Location <span class="text-danger">*</span></label>
                        <select name="location_id" id="locationSelect" class="form-select" required>
                            <option value="">Select Location</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" data-node-type="{{ $location->node_type }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }} ({{ ucfirst($location->node_type) }} - {{ $location->state }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Representative Details -->
                    <div class="col-12">
                        <h6 class="mb-3 text-primary">Authorized Representative Details</h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Representative Name <span class="text-danger">*</span></label>
                        <input type="text" name="representative_name" class="form-control" value="{{ old('representative_name') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Representative PAN <span class="text-danger">*</span></label>
                        <input type="text" name="representative_pan" class="form-control" value="{{ old('representative_pan') }}" placeholder="ABCDE1234F" maxlength="10" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Representative Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="representative_dob" class="form-control" value="{{ old('representative_dob') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Representative Email <span class="text-danger">*</span></label>
                        <input type="email" name="representative_email" class="form-control" value="{{ old('representative_email') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Representative Mobile <span class="text-danger">*</span></label>
                        <input type="tel" name="representative_mobile" class="form-control" value="{{ old('representative_mobile') }}" placeholder="10-digit mobile number" maxlength="10" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">GSTIN <span class="text-danger">*</span></label>
                        <input type="text" name="gstin" id="gstin" class="form-control" value="{{ old('gstin') }}" placeholder="15-character GSTIN" maxlength="15" required>
                        <small class="text-muted">GSTIN will be auto-verified for backend entry</small>
                    </div>

                    <!-- Port & Billing -->
                    <div class="col-12">
                        <h6 class="mb-3 text-primary mt-3">Port & Billing Details</h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Port Capacity <span class="text-danger">*</span></label>
                        <select name="port_capacity" id="portCapacitySelect" class="form-select" required>
                            <option value="">Select capacity</option>
                            @foreach($portPricings as $nodeType => $entries)
                                <optgroup label="{{ ucfirst($nodeType) }} nodes">
                                    @foreach($entries as $pricing)
                                        <option value="{{ $pricing->port_capacity }}" data-node-type="{{ $nodeType }}" data-arc="{{ $pricing->price_arc }}" data-mrc="{{ $pricing->price_mrc }}" data-quarterly="{{ $pricing->price_quarterly }}" {{ old('port_capacity') == $pricing->port_capacity ? 'selected' : '' }}>
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
                                <input class="form-check-input" type="radio" name="billing_plan" id="planArc" value="arc" {{ old('billing_plan') === 'arc' ? 'checked' : '' }} required>
                                <label class="form-check-label" for="planArc">Annual (ARC)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="billing_plan" id="planMrc" value="mrc" {{ old('billing_plan') === 'mrc' ? 'checked' : '' }} required>
                                <label class="form-check-label" for="planMrc">Monthly (MRC)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="billing_plan" id="planQuarterly" value="quarterly" {{ old('billing_plan') === 'quarterly' ? 'checked' : '' }} required>
                                <label class="form-check-label" for="planQuarterly">Quarterly</label>
                            </div>
                        </div>
                    </div>

                    <!-- IP Prefix -->
                    <div class="col-12">
                        <h6 class="mb-3 text-primary mt-3">IP Prefix Information</h6>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Number of IP Prefixes <span class="text-danger">*</span></label>
                        <input type="number" min="1" max="500" name="ip_prefix_count" class="form-control" value="{{ old('ip_prefix_count') }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">IP Prefix Allocation Source <span class="text-danger">*</span></label>
                        <select name="ip_prefix_source" id="ipPrefixSource" class="form-select" required>
                            <option value="">Select Source</option>
                            <option value="irinn" {{ old('ip_prefix_source') === 'irinn' ? 'selected' : '' }}>IRINN</option>
                            <option value="apnic" {{ old('ip_prefix_source') === 'apnic' ? 'selected' : '' }}>APNIC</option>
                            <option value="others" {{ old('ip_prefix_source') === 'others' ? 'selected' : '' }}>Others</option>
                        </select>
                    </div>

                    <div class="col-md-4 d-none" id="ipPrefixProviderWrapper">
                        <label class="form-label">Provider Name</label>
                        <input type="text" name="ip_prefix_provider" class="form-control" placeholder="Enter provider" value="{{ old('ip_prefix_provider') }}">
                    </div>

                    <!-- Peering Connectivity -->
                    <div class="col-12">
                        <h6 class="mb-3 text-primary mt-3">Peering Connectivity</h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Member's Pre-NIXI peering connectivity <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="pre_peering_connectivity" id="prePeeringNone" value="none" {{ old('pre_peering_connectivity') === 'none' ? 'checked' : '' }} required>
                                <label class="form-check-label" for="prePeeringNone">None</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="pre_peering_connectivity" id="prePeeringSingle" value="single" {{ old('pre_peering_connectivity') === 'single' ? 'checked' : '' }} required>
                                <label class="form-check-label" for="prePeeringSingle">Single</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="pre_peering_connectivity" id="prePeeringMultiple" value="multiple" {{ old('pre_peering_connectivity') === 'multiple' ? 'checked' : '' }} required>
                                <label class="form-check-label" for="prePeeringMultiple">Multiple</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">AS Number used for peering in the NIXI</label>
                        <input type="text" name="asn_number" class="form-control" placeholder="e.g., AS131269" value="{{ old('asn_number') }}">
                    </div>

                    <!-- Router Details -->
                    <div class="col-12">
                        <h6 class="mb-3 text-primary mt-3">Dedicated Router Details (Optional)</h6>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Height in U</label>
                        <input type="number" min="1" max="50" name="router_height_u" class="form-control" value="{{ old('router_height_u') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Make & Model</label>
                        <input type="text" name="router_make_model" class="form-control" value="{{ old('router_make_model') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Serial Number</label>
                        <input type="text" name="router_serial_number" class="form-control" value="{{ old('router_serial_number') }}">
                    </div>

                    <!-- Documents -->
                    <div class="col-12">
                        <h6 class="mb-3 text-primary mt-3">Required Documents</h6>
                        <p class="text-muted small">Upload clear PDF copies. Maximum size per document is 10 MB.</p>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Signed Agreement with NIXI <span class="text-danger">*</span></label>
                        <input type="file" name="agreement_file" class="form-control" accept="application/pdf" required>
                    </div>

                    <div class="col-md-6" id="ispLicenseContainer" style="display: none;">
                        <label class="form-label">ISP License <span class="text-danger">*</span></label>
                        <input type="file" name="license_isp_file" id="licenseIspFile" class="form-control" accept="application/pdf">
                    </div>

                    <div class="col-md-6" id="vnoLicenseContainer" style="display: none;">
                        <label class="form-label">VNO License <span class="text-danger">*</span></label>
                        <input type="file" name="license_vno_file" id="licenseVnoFile" class="form-control" accept="application/pdf">
                    </div>

                    <div class="col-md-6" id="cdnDeclarationContainer" style="display: none;">
                        <label class="form-label">CDN Declaration <span class="text-danger">*</span></label>
                        <input type="file" name="cdn_declaration_file" id="cdnDeclarationFile" class="form-control" accept="application/pdf">
                    </div>

                    <div class="col-md-6" id="generalDeclarationContainer" style="display: none;">
                        <label class="form-label">General Declaration <span class="text-danger">*</span></label>
                        <input type="file" name="general_declaration_file" id="generalDeclarationFile" class="form-control" accept="application/pdf">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Whois Details <span class="text-danger">*</span></label>
                        <input type="file" name="whois_details_file" class="form-control" accept="application/pdf" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">PAN Document <span class="text-danger">*</span></label>
                        <input type="file" name="pan_document_file" class="form-control" accept="application/pdf" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">GSTIN Document <span class="text-danger">*</span></label>
                        <input type="file" name="gstin_document_file" class="form-control" accept="application/pdf" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">MSME (Udyog/Udyam) Certificate</label>
                        <input type="file" name="msme_document_file" class="form-control" accept="application/pdf">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Certificate of Incorporation <span class="text-danger">*</span></label>
                        <input type="file" name="incorporation_document_file" class="form-control" accept="application/pdf" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Authorized Representative Document <span class="text-danger">*</span></label>
                        <input type="file" name="authorized_rep_document_file" class="form-control" accept="application/pdf" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Register User & Create Application</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // Generate OTPs and password immediately for display
    function generateOtp() {
        return String(Math.floor(100000 + Math.random() * 900000));
    }

    function generatePassword() {
        const length = 12;
        const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        const lowercase = 'abcdefghijklmnopqrstuvwxyz';
        const numbers = '0123456789';
        const special = '!@#$%^&*';
        const all = uppercase + lowercase + numbers + special;

        let password = '';
        password += uppercase[Math.floor(Math.random() * uppercase.length)];
        password += lowercase[Math.floor(Math.random() * lowercase.length)];
        password += numbers[Math.floor(Math.random() * numbers.length)];
        password += special[Math.floor(Math.random() * special.length)];

        for (let i = 4; i < length; i++) {
            password += all[Math.floor(Math.random() * all.length)];
        }

        return password.split('').sort(() => Math.random() - 0.5).join('');
    }

    // Generate and display OTPs and password on page load
    let emailOtp = generateOtp();
    let mobileOtp = generateOtp();
    let generatedPassword = generatePassword();

    document.getElementById('emailOtpValue').textContent = emailOtp;
    document.getElementById('mobileOtpValue').textContent = mobileOtp;
    document.getElementById('generatedPassword').textContent = generatedPassword;

    // Regenerate on input change
    document.getElementById('email').addEventListener('input', function() {
        if (this.value) {
            emailOtp = generateOtp();
            document.getElementById('emailOtpValue').textContent = emailOtp;
            document.getElementById('emailOtpInput').value = emailOtp;
        }
    });

    document.getElementById('mobile').addEventListener('input', function() {
        if (this.value) {
            mobileOtp = generateOtp();
            document.getElementById('mobileOtpValue').textContent = mobileOtp;
            document.getElementById('mobileOtpInput').value = mobileOtp;
        }
    });

    // Update hidden inputs on form submit
    document.getElementById('backendDataEntryForm').addEventListener('submit', function() {
        document.getElementById('emailOtpInput').value = emailOtp;
        document.getElementById('mobileOtpInput').value = mobileOtp;
        document.getElementById('generatedPasswordInput').value = generatedPassword;
    });

    // Initialize hidden inputs
    document.getElementById('emailOtpInput').value = emailOtp;
    document.getElementById('mobileOtpInput').value = mobileOtp;
    document.getElementById('generatedPasswordInput').value = generatedPassword;

    // PAN Verification
    async function verifyPan() {
        const panNo = document.getElementById('pancardno').value.trim().toUpperCase();
        const fullName = document.getElementById('fullname').value.trim();
        const dob = document.getElementById('dateofbirth').value;

        if (!panNo || !/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/.test(panNo)) {
            alert('Please enter a valid PAN number');
            return;
        }

        if (!fullName) {
            alert('Please enter full name');
            return;
        }

        if (!dob) {
            alert('Please enter date of birth');
            return;
        }

        const btn = document.getElementById('verifyPanBtn');
        const statusDiv = document.getElementById('panVerificationStatus');
        
        btn.disabled = true;
        btn.textContent = 'Verifying...';
        statusDiv.style.display = 'block';
        statusDiv.className = 'alert alert-info';
        statusDiv.innerHTML = '<i class="bi bi-hourglass-split"></i> Verifying PAN...';

        try {
            const response = await fetch('{{ route("admin.backend-data-entry.verify-pan") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    pancardno: panNo,
                    fullname: fullName,
                    dateofbirth: dob
                })
            });

            const data = await response.json();

            if (data.success) {
                statusDiv.className = 'alert alert-success';
                statusDiv.innerHTML = '<i class="bi bi-check-circle"></i> PAN verified successfully!';
            } else {
                statusDiv.className = 'alert alert-danger';
                statusDiv.innerHTML = '<i class="bi bi-x-circle"></i> ' + (data.message || 'PAN verification failed');
            }
        } catch (error) {
            statusDiv.className = 'alert alert-danger';
            statusDiv.innerHTML = '<i class="bi bi-x-circle"></i> Error verifying PAN: ' + error.message;
        } finally {
            btn.disabled = false;
            btn.textContent = 'Verify PAN';
        }
    }

    // Member Type change handler
    document.getElementById('memberType').addEventListener('change', function() {
        const memberType = this.value;
        const ispContainer = document.getElementById('ispLicenseContainer');
        const vnoContainer = document.getElementById('vnoLicenseContainer');
        const cdnContainer = document.getElementById('cdnDeclarationContainer');
        const generalContainer = document.getElementById('generalDeclarationContainer');
        const memberTypeOther = document.getElementById('memberTypeOther');

        // Hide all first
        ispContainer.style.display = 'none';
        vnoContainer.style.display = 'none';
        cdnContainer.style.display = 'none';
        generalContainer.style.display = 'none';
        memberTypeOther.classList.add('d-none');

        // Show relevant fields
        if (memberType === 'isp') {
            ispContainer.style.display = 'block';
            document.getElementById('licenseIspFile').required = true;
        } else if (memberType === 'vno') {
            vnoContainer.style.display = 'block';
            document.getElementById('licenseVnoFile').required = true;
        } else if (memberType === 'cdn') {
            cdnContainer.style.display = 'block';
            document.getElementById('cdnDeclarationFile').required = true;
        } else if (memberType !== 'isp' && memberType !== 'vno' && memberType !== 'cdn') {
            generalContainer.style.display = 'block';
            document.getElementById('generalDeclarationFile').required = true;
        }

        if (memberType === 'others') {
            memberTypeOther.classList.remove('d-none');
            memberTypeOther.required = true;
        } else {
            memberTypeOther.required = false;
        }
    });

    // IP Prefix Source change handler
    document.getElementById('ipPrefixSource').addEventListener('change', function() {
        const providerWrapper = document.getElementById('ipPrefixProviderWrapper');
        if (this.value === 'others') {
            providerWrapper.classList.remove('d-none');
            providerWrapper.querySelector('input').required = true;
        } else {
            providerWrapper.classList.add('d-none');
            providerWrapper.querySelector('input').required = false;
        }
    });

    // Registration type change handler
    document.querySelectorAll('input[name="registration_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const fullnameLabel = document.getElementById('fullnameLabel');
            const dateofbirthLabel = document.getElementById('dateofbirthLabel');
            
            if (this.value === 'individual') {
                fullnameLabel.textContent = 'Full Name (As per PAN) *';
                dateofbirthLabel.textContent = 'Date of Birth (As per PAN) *';
            } else {
                fullnameLabel.textContent = 'Entity Name (As per PAN) *';
                dateofbirthLabel.textContent = 'Date of Incorporation (As per PAN) *';
            }
        });
    });
</script>
@endpush
@endsection
