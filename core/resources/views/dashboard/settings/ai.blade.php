@extends('dashboard.layouts.master')
@section('title', 'AI & Call Intelligence Settings')

@section('content')
<div class="padding">
    <div class="box">
        <div class="box-header d-flex align-items-center justify-content-between p-3 border-bottom" style="background: #0A1426; color: #FFFFFF; border-radius: 6px 6px 0 0;">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(20, 100, 244, 0.2); display: grid; place-items: center; color: var(--lg-accent); font-size: 20px;">
                    <i class="fas fa-brain"></i>
                </div>
                <div>
                    <h5 class="m-0 font-weight-bold" style="color: #FFFFFF; font-size: 16px;">AI & Call Intelligence</h5>
                    <small style="color: #94A3B8;">Configure automated call recording analysis powered by Sarvam AI</small>
                </div>
            </div>
            <div>
                <a href="{{ route('aiUsage') }}" class="btn btn-sm btn-primary" style="background: var(--lg-accent); border-color: var(--lg-accent); font-weight: 600;">
                    <i class="fas fa-chart-line me-1"></i> View AI Usage
                </a>
            </div>
        </div>

        <div class="box-body p-4" style="background: #F8FAFC;">
            @if(session('doneMessage'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('doneMessage') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('errorMessage'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>{{ session('errorMessage') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Quick Metrics Header Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small font-weight-bold text-uppercase">Calls Analyzed</div>
                                <div class="h3 font-weight-bold text-dark mt-1 mb-0">{{ number_format($totalAnalyzed) }}</div>
                            </div>
                            <div class="rounded-circle bg-light-primary p-3 text-primary">
                                <i class="fas fa-phone-volume fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small font-weight-bold text-uppercase">Processed Audio</div>
                                <div class="h3 font-weight-bold text-dark mt-1 mb-0">{{ $totalDurationMins }} <span class="small font-weight-normal text-muted">mins</span></div>
                            </div>
                            <div class="rounded-circle bg-light-info p-3 text-info">
                                <i class="fas fa-microphone-alt fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small font-weight-bold text-uppercase">Month AI Charges</div>
                                <div class="h3 font-weight-bold text-dark mt-1 mb-0">₹{{ number_format($totalCost, 2) }}</div>
                            </div>
                            <div class="rounded-circle bg-light-success p-3 text-success">
                                <i class="fas fa-wallet fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Form -->
            <form action="{{ route('aiSettingsUpdate') }}" method="POST">
                @csrf
                
                <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white overflow-hidden">
                    <div class="card-header bg-white border-bottom p-3">
                        <h6 class="m-0 font-weight-bold text-dark d-flex align-items-center gap-2">
                            <i class="fas fa-sliders-h text-primary"></i> LeadGER AI Call Intelligence Settings
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        
                        <!-- Enable AI Switch -->
                        <div class="d-flex align-items-center justify-content-between p-3 rounded-3 mb-4" style="background: #F1F5F9; border: 1px solid #E2E8F0;">
                            <div>
                                <h6 class="font-weight-bold m-0 text-dark" style="font-size: 15px;">Enable AI Analysis</h6>
                                <p class="text-muted small m-0 mt-1">Automatically analyze completed call recordings using AI speech-to-text and NLP models.</p>
                            </div>
                            <div class="form-check form-switch fs-4 mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="ai_enabled" name="ai_enabled" value="1" {{ $settings->ai_enabled ? 'checked' : '' }} onchange="toggleAiFields(this.checked)" style="cursor: pointer; width: 3em; height: 1.5em;">
                            </div>
                        </div>

                        <!-- Provider Selection -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <label class="form-label font-weight-bold text-dark m-0">AI Provider</label>
                                    @php $lgSvReady = (new \App\Services\SarvamAiService())->isConfigured(); @endphp
                                    {{-- the real state of this company's own key (License Info tab) --}}
                                    <span class="badge {{ $lgSvReady ? 'bg-success' : 'bg-warning' }} text-white" style="font-size: 11px; padding: 4px 8px; border-radius: 6px;">
                                        <i class="fas {{ $lgSvReady ? 'fa-check-circle' : 'fa-exclamation-triangle' }} me-1"></i> {{ $lgSvReady ? 'API Key Connected' : 'No valid API key' }}
                                    </span>
                                </div>
                                <select name="provider" class="form-select border-1" style="border-radius: 8px; padding: 10px;" id="ai_provider">
                                    <option value="Sarvam AI" selected>Sarvam AI (Indian Languages & Accent Optimized)</option>
                                </select>
                                <div class="mt-2 d-flex align-items-center justify-content-between">
                                    <small class="text-muted"><i class="fas fa-shield-alt text-success me-1"></i> Company key: <a href="{{ route('usersEdit', Auth::id()) }}#license-info">Profile → License Info</a></small>
                                    <button type="button" class="btn btn-xs btn-outline-primary" id="btn-test-sarvam" onclick="testSarvamApi()">
                                        <i class="fas fa-bolt me-1"></i> Test Sarvam API
                                    </button>
                                </div>
                                <div id="sarvam-test-result" class="mt-2 p-2 rounded small" style="display: none;"></div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label font-weight-bold text-dark">Provider Rate (₹ / min)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light font-weight-bold">₹</span>
                                    <input type="number" step="0.0001" min="0.0001" name="price_per_unit" class="form-control" value="{{ number_format($settings->price_per_unit ?? 0.1500, 4, '.', '') }}" style="border-radius: 0 8px 8px 0; padding: 10px;" required>
                                </div>
                                <small class="text-muted">Dynamic client billing rate</small>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label font-weight-bold text-dark">Billing Mode</label>
                                <input type="text" class="form-control bg-light" value="Selective Usage (Pre-Confirm)" readonly style="border-radius: 8px; padding: 10px;">
                                <input type="hidden" name="billing_mode" value="Usage Based">
                                <input type="hidden" name="pricing_unit" value="minute">
                                <input type="hidden" name="currency" value="INR">
                                <input type="hidden" name="client_billing_enabled" value="1">
                            </div>
                        </div>

                        <!-- AI Features (Visible when ON) -->
                        <div id="ai_features_section" style="{{ $settings->ai_enabled ? '' : 'display: none;' }}">
                            <hr class="my-4 text-muted" style="opacity: 0.15;">
                            
                            <h6 class="font-weight-bold text-dark mb-3">AI Features</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-3 col-6">
                                    <div class="form-check p-3 rounded-3 border bg-light" style="cursor: pointer;">
                                        <input class="form-check-input" type="checkbox" name="transcript_enabled" id="feat_transcript" value="1" {{ $settings->transcript_enabled ? 'checked' : '' }}>
                                        <label class="form-check-label font-weight-bold text-dark ms-1" for="feat_transcript" style="cursor: pointer;">
                                            <i class="fas fa-file-alt text-primary me-1"></i> Call Transcript
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="form-check p-3 rounded-3 border bg-light">
                                        <input class="form-check-input" type="checkbox" name="summary_enabled" id="feat_summary" value="1" {{ $settings->summary_enabled ? 'checked' : '' }}>
                                        <label class="form-check-label font-weight-bold text-dark ms-1" for="feat_summary" style="cursor: pointer;">
                                            <i class="fas fa-compress-alt text-info me-1"></i> Call Summary
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="form-check p-3 rounded-3 border bg-light">
                                        <input class="form-check-input" type="checkbox" name="interest_enabled" id="feat_interest" value="1" {{ $settings->interest_enabled ? 'checked' : '' }}>
                                        <label class="form-check-label font-weight-bold text-dark ms-1" for="feat_interest" style="cursor: pointer;">
                                            <i class="fas fa-fire text-warning me-1"></i> Customer Interest
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="form-check p-3 rounded-3 border bg-light">
                                        <input class="form-check-input" type="checkbox" name="sentiment_enabled" id="feat_sentiment" value="1" {{ $settings->sentiment_enabled ? 'checked' : '' }}>
                                        <label class="form-check-label font-weight-bold text-dark ms-1" for="feat_sentiment" style="cursor: pointer;">
                                            <i class="fas fa-smile text-success me-1"></i> Sentiment Analysis
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="form-check p-3 rounded-3 border bg-light">
                                        <input class="form-check-input" type="checkbox" name="intent_enabled" id="feat_intent" value="1" {{ $settings->intent_enabled ? 'checked' : '' }}>
                                        <label class="form-check-label font-weight-bold text-dark ms-1" for="feat_intent" style="cursor: pointer;">
                                            <i class="fas fa-bullseye text-danger me-1"></i> Intent Detection
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="form-check p-3 rounded-3 border bg-light">
                                        <input class="form-check-input" type="checkbox" name="key_points_enabled" id="feat_key_points" value="1" {{ $settings->key_points_enabled ? 'checked' : '' }}>
                                        <label class="form-check-label font-weight-bold text-dark ms-1" for="feat_key_points" style="cursor: pointer;">
                                            <i class="fas fa-list-ul text-purple me-1"></i> Key Points
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="form-check p-3 rounded-3 border bg-light">
                                        <input class="form-check-input" type="checkbox" name="next_action_enabled" id="feat_next_action" value="1" {{ $settings->next_action_enabled ? 'checked' : '' }}>
                                        <label class="form-check-label font-weight-bold text-dark ms-1" for="feat_next_action" style="cursor: pointer;">
                                            <i class="fas fa-forward text-primary me-1"></i> Next Action
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="form-check p-3 rounded-3 border bg-light">
                                        <input class="form-check-input" type="checkbox" name="auto_disposition_enabled" id="feat_auto_disp" value="1" {{ $settings->auto_disposition_enabled ? 'checked' : '' }}>
                                        <label class="form-check-label font-weight-bold text-dark ms-1" for="feat_auto_disp" style="cursor: pointer;">
                                            <i class="fas fa-magic text-warning me-1"></i> Automatic Disposition
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Analysis Trigger -->
                            <div class="p-3 rounded-3 bg-light border">
                                <label class="form-label font-weight-bold text-dark mb-2">Analysis Trigger Mode</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="analysis_trigger" id="trig_manual" value="manual" {{ !$settings->auto_analysis_enabled ? 'checked' : '' }}>
                                        <label class="form-check-label font-weight-bold text-dark" for="trig_manual" style="cursor: pointer;">
                                            Manual (Trigger analysis per call manually)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="analysis_trigger" id="trig_auto" value="auto" {{ $settings->auto_analysis_enabled ? 'checked' : '' }}>
                                        <label class="form-check-label font-weight-bold text-dark" for="trig_auto" style="cursor: pointer;">
                                            Automatically after call completes
                                        </label>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Off State Info Box -->
                        <div id="ai_off_info" class="alert alert-warning border-0 rounded-3 mt-3 m-0" style="{{ $settings->ai_enabled ? 'display: none;' : '' }}">
                            <div class="d-flex gap-2">
                                <i class="fas fa-info-circle fa-lg mt-1 text-warning"></i>
                                <div>
                                    <strong>AI Call Intelligence is currently OFF</strong>
                                    <ul class="mb-0 mt-1 ps-3 text-muted small">
                                        <li>No audio recordings will be sent to Sarvam AI.</li>
                                        <li>No AI background processing jobs will be created.</li>
                                        <li>No AI usage charges will be generated.</li>
                                        <li>Existing call recording and playback functionality will continue normally.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer bg-white border-top p-3 d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary px-4 py-2" style="background: var(--lg-accent); border-color: var(--lg-accent); font-weight: 600; border-radius: 8px;">
                            <i class="fas fa-save me-1"></i> Save AI Settings
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
function toggleAiFields(enabled) {
    const featuresSec = document.getElementById('ai_features_section');
    const offInfo = document.getElementById('ai_off_info');
    if (enabled) {
        featuresSec.style.display = 'block';
        offInfo.style.display = 'none';
    } else {
        featuresSec.style.display = 'none';
        offInfo.style.display = 'block';
    }
}

function testSarvamApi() {
    var btn = $('#btn-test-sarvam');
    var resDiv = $('#sarvam-test-result');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Testing...');
    resDiv.hide().removeClass('alert-success alert-danger alert');
    
    $.ajax({
        type: 'POST',
        url: "{{ route('testSarvamConnection') }}",
        data: { _token: "{{ csrf_token() }}" },
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).html('<i class="fas fa-bolt me-1"></i> Test Sarvam API');
            if (res.status === 'success') {
                resDiv.addClass('alert alert-success d-block').html(
                    '<div class="font-weight-bold text-success"><i class="fas fa-check-circle me-1"></i> ' + res.message + '</div>' +
                    '<div class="mt-1" style="font-size:11.5px; color:#1E293B;">' +
                    'Request ID: <code>' + res.request_id + '</code> | Latency: <strong>' + res.latency_ms + 'ms</strong> | Lang: <strong>' + res.language_code + '</strong><br>' +
                    'Live STT Output: <em>"' + res.sample_transcript + '"</em>' +
                    '</div>'
                ).show();
            } else {
                resDiv.addClass('alert alert-danger d-block').html(
                    '<strong class="text-danger"><i class="fas fa-times-circle me-1"></i> Test Failed:</strong> ' + res.message
                ).show();
            }
        },
        error: function(xhr) {
            btn.prop('disabled', false).html('<i class="fas fa-bolt me-1"></i> Test Sarvam API');
            var err = 'Connection to Sarvam AI failed.';
            if (xhr.responseJSON && xhr.responseJSON.message) err = xhr.responseJSON.message;
            resDiv.addClass('alert alert-danger d-block').html(
                '<strong class="text-danger"><i class="fas fa-times-circle me-1"></i> Error:</strong> ' + err
            ).show();
        }
    });
}
</script>
@endsection
