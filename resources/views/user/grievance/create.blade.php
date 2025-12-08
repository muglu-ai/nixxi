@extends('user.layout')

@section('title', 'Submit Grievance')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary">
                <h4 class="mb-0">Submit Grievance / Feedback</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('user.grievance.store') }}" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="">Select Type</option>
                            <option value="technical" {{ old('type') === 'technical' ? 'selected' : '' }}>Technical</option>
                            <option value="billing" {{ old('type') === 'billing' ? 'selected' : '' }}>Billing</option>
                            <option value="general_complaint" {{ old('type') === 'general_complaint' ? 'selected' : '' }}>General Complaint</option>
                            <option value="feedback" {{ old('type') === 'feedback' ? 'selected' : '' }}>Feedback</option>
                            <option value="suggestion" {{ old('type') === 'suggestion' ? 'selected' : '' }}>Suggestion</option>
                            <option value="request" {{ old('type') === 'request' ? 'selected' : '' }}>Request</option>
                            <option value="enquiry" {{ old('type') === 'enquiry' ? 'selected' : '' }}>Enquiry</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" name="subject" id="subject" class="form-control @error('subject') is-invalid @enderror" 
                               value="{{ old('subject') }}" placeholder="Brief subject (optional)">
                        @error('subject')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="description" rows="6" 
                                  class="form-control @error('description') is-invalid @enderror" 
                                  required placeholder="Please describe your grievance, feedback, or enquiry in detail...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Minimum 10 characters required.</small>
                    </div>

                    <div class="mb-3">
                        <label for="priority" class="form-label">Priority</label>
                        <select name="priority" id="priority" class="form-select @error('priority') is-invalid @enderror">
                            <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                            <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                        </select>
                        @error('priority')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="attachments" class="form-label">Attachments</label>
                        <input type="file" name="attachments[]" id="attachments" 
                               class="form-control @error('attachments.*') is-invalid @enderror" 
                               multiple accept="image/*,.pdf,.doc,.docx">
                        @error('attachments.*')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">You can attach images, PDFs, or documents. Maximum file size: 10MB per file.</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576 6.636 10.07Zm-1.138-1.138L13.713 1.5 5.498 8.932Z"/>
                            </svg>
                            Submit
                        </button>
                        <a href="{{ route('user.grievance.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

