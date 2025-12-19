@extends('admin.layout')

@section('title', 'Update Application')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1">Update Application</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.applications') }}">Applications</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.applications.show', $application->id) }}">{{ $application->application_id }}</a></li>
                    <li class="breadcrumb-item active">Update</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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

    <form method="POST" action="{{ route('admin.applications.update', $application->id) }}" enctype="multipart/form-data">
        @csrf

        <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
            <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                <h5 class="mb-0">Application Information</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-12">
                        <p><strong>Application ID:</strong> {{ $application->application_id }}</p>
                        <p><strong>User:</strong> {{ $application->user->fullname }} ({{ $application->user->email }})</p>
                    </div>
                </div>
            </div>
        </div>

        @if($application->application_type === 'IX')
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
            <div class="card-header bg-success text-white" style="border-radius: 16px 16px 0 0;">
                <h5 class="mb-0">Update Application Details</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    @php
                        $representative = $applicationData['representative'] ?? [];
                        $portSelection = $applicationData['port_selection'] ?? [];
                        $ipPrefix = $applicationData['ip_prefix'] ?? [];
                    @endphp

                    <div class="col-12">
                        <h6 class="mb-3 text-primary">Representative Details</h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Representative Name</label>
                        <input type="text" name="representative_name" class="form-control" value="{{ old('representative_name', $representative['name'] ?? '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Representative Email</label>
                        <input type="email" name="representative_email" class="form-control" value="{{ old('representative_email', $representative['email'] ?? '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Representative Mobile</label>
                        <input type="tel" name="representative_mobile" class="form-control" value="{{ old('representative_mobile', $representative['mobile'] ?? '') }}" maxlength="10">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">GSTIN</label>
                        <input type="text" name="gstin" class="form-control" value="{{ old('gstin', $applicationData['gstin'] ?? '') }}" maxlength="15">
                    </div>

                    <div class="col-12">
                        <h6 class="mb-3 text-primary mt-3">Port & Billing Details</h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Port Capacity</label>
                        <input type="text" name="port_capacity" class="form-control" value="{{ old('port_capacity', $portSelection['capacity'] ?? '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Billing Plan</label>
                        <select name="billing_plan" class="form-select">
                            <option value="">Select Plan</option>
                            <option value="arc" {{ old('billing_plan', $portSelection['billing_plan'] ?? '') === 'arc' ? 'selected' : '' }}>Annual (ARC)</option>
                            <option value="mrc" {{ old('billing_plan', $portSelection['billing_plan'] ?? '') === 'mrc' ? 'selected' : '' }}>Monthly (MRC)</option>
                            <option value="quarterly" {{ old('billing_plan', $portSelection['billing_plan'] ?? '') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">IP Prefix Count</label>
                        <input type="number" name="ip_prefix_count" class="form-control" value="{{ old('ip_prefix_count', $ipPrefix['count'] ?? '') }}" min="1" max="500">
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
            <div class="card-header bg-info text-white" style="border-radius: 16px 16px 0 0;">
                <h5 class="mb-0">Update Documents</h5>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-3">Upload new documents to replace existing ones or add missing documents. Maximum size per document is 10 MB.</p>
                
                <div class="row g-3">
                    @php
                        $documentNames = [
                            'agreement_file' => 'Signed Agreement with NIXI',
                            'license_isp_file' => 'ISP License',
                            'license_vno_file' => 'VNO License',
                            'cdn_declaration_file' => 'CDN Declaration',
                            'general_declaration_file' => 'General Declaration',
                            'whois_details_file' => 'Whois Details',
                            'pan_document_file' => 'PAN Document',
                            'gstin_document_file' => 'GSTIN Document',
                            'msme_document_file' => 'MSME (Udyog/Udyam) Certificate',
                            'incorporation_document_file' => 'Certificate of Incorporation',
                            'authorized_rep_document_file' => 'Authorized Representative Document',
                        ];
                    @endphp

                    @foreach($documentNames as $key => $label)
                    <div class="col-md-6">
                        <label class="form-label">{{ $label }}</label>
                        <input type="file" name="{{ $key }}" class="form-control" accept="application/pdf">
                        @if(isset($documents[$key]) && \Illuminate\Support\Facades\Storage::disk('public')->exists($documents[$key]))
                            <small class="text-success d-block mt-1">
                                <i class="bi bi-check-circle"></i> Current document exists
                                <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => $key]) }}" target="_blank" class="ms-2">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </small>
                        @else
                            <small class="text-muted d-block mt-1">
                                <i class="bi bi-x-circle"></i> No document uploaded
                            </small>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <div class="d-flex justify-content-between">
            <a href="{{ route('admin.applications.show', $application->id) }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Application</button>
        </div>
    </form>
</div>
@endsection
