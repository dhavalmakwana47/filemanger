@props(['ndaContent'])

<div class="modal fade" id="ndaModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <!-- Header with Company Logo and Title -->
            <div class="modal-header p-3 bg-gradient-primary text-white" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                <div class="d-flex align-items-center w-100">
                    @if(isset($setting) && $setting->company && $setting->company->logo)
                        <img src="{{ asset('storage/' . $setting->company->logo) }}" 
                             alt="{{ $setting->company->name }}" 
                             class="me-3" 
                             style="height: 30px; width: auto; max-width: 120px; object-fit: contain; flex-shrink: 0;">
                    @endif
                    <h5 class="modal-title m-0 p-0 fw-bold" style="font-size: 1.1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        NON-DISCLOSURE AGREEMENT
                    </h5>
                </div>
            </div>
            
            <!-- Body with Custom Scrollbar -->
            <div class="modal-body p-0">
                <!-- Agreement Content -->
                <div class="nda-content p-4" style="max-height: 60vh; overflow-y: auto; border-bottom: 1px solid #e9ecef;">
                    <div class="agreement-content" style="font-family: 'Arial', sans-serif; line-height: 1.7; color: #333;">
                        {!! $ndaContent !!}
                    </div>
                </div>
                
                <!-- Agreement Form -->
                <div class="p-4 bg-light">
                    <form id="ndaAgreementForm" action="{{ route('sign.nda.agreement') }}" method="POST" class="mb-0">
                        @csrf
                        <div class="form-check d-flex align-items-start">
                            <input class="form-check-input mt-1 me-3 @error('nda_agreement') is-invalid @enderror" 
                                   type="checkbox" id="agreeCheckbox" name="nda_agreement" required>
                            <div>
                                <label class="form-check-label fw-medium" for="agreeCheckbox">
                                    <span class="d-block mb-1">I, {{ auth()->user()->name ?? 'User' }}, hereby acknowledge and agree to the terms and conditions outlined in this Non-Disclosure Agreement.</span>
                                    <small class="text-muted d-block">By checking this box, you confirm that you have read, understood, and agree to be bound by all terms and conditions stated above.</small>
                                </label>
                                @error('nda_agreement')
                                    <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                            <div class="text-muted small">
                                <i class="fas fa-lock me-1"></i> Your information is secure and confidential
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary px-4" id="agreeButton" disabled>
                                    <i class="fas fa-signature me-2"></i> I Agree & Continue
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Footer with Additional Info -->
            <div class="modal-footer bg-light py-2">
                <div class="w-100 text-center small text-muted">
                    <p class="mb-0">By proceeding, you agree to our <a href="#" class="text-primary">Terms of Service</a> and <a href="#" class="text-primary">Privacy Policy</a></p>
                    <p class="mb-0">© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Custom scrollbar */
    .nda-content::-webkit-scrollbar {
        width: 6px;
    }
    .nda-content::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    .nda-content::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }
    .nda-content::-webkit-scrollbar-thumb:hover {
        background: #666;
    }
    
    /* Gradient background for header */
    .bg-gradient-primary {
        background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%) !important;
    }
    
    /* Better typography */
    .agreement-content h1, 
    .agreement-content h2, 
    .agreement-content h3 {
        color: #2c3e50;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .agreement-content p {
        margin-bottom: 1rem;
    }
    
    /* Better checkbox styling */
    .form-check-input:checked {
        background-color: #4361ee;
        border-color: #4361ee;
    }
    
    .form-check-input:focus {
        box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        border-color: #4361ee;
    }
</style>
@endpush

