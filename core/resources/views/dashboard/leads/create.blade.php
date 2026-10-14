@extends('dashboard.layouts.master')
@section('title', 'Upload Leads')

@push("after-styles")
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* ==========================================================================
       LEADGER COMMAND CENTER - UPLOAD LEADS
       ========================================================================== */
    .ul-page {
        padding: 20px 24px 40px 24px;
        background: #F8FAFC;
        min-height: calc(100vh - 72px);
        font-family: var(--lg-font-sans);
        color: #0F172A;
    }

    /* --- Page Header --- */
    .ul-page-header {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        padding: 20px 24px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 16px;
    }
    .ul-header-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .ul-header-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: #EFF6FF;
        border: 1px solid #BFDBFE;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--lg-accent);
        font-size: 22px;
        flex-shrink: 0;
    }
    .ul-header-titles {
        display: flex;
        flex-direction: column;
    }
    .ul-breadcrumb {
        font-size: 14px;
        color: #64748B;
        font-weight: 500;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .ul-breadcrumb a {
        color: var(--lg-accent);
        text-decoration: none;
    }
    .ul-breadcrumb a:hover {
        text-decoration: underline;
    }
    .ul-page-title {
        font-size: 28px;
        font-weight: 700;
        color: #0F172A;
        margin: 0;
        line-height: 1.25;
        letter-spacing: -0.015em;
    }
    .ul-page-subtitle {
        font-size: 13.5px;
        color: #64748B;
        font-weight: 400;
        margin-top: 3px;
    }
    .ul-header-right {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    /* --- 4-Step Progress Section --- */
    .ul-steps-card {
        display: flex;
        align-items: center;
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        padding: 16px 28px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        overflow-x: auto;
    }
    .ul-step-item {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1;
        min-width: 140px;
    }
    .ul-step-item:not(:last-child)::after {
        content: '';
        flex: 1;
        height: 2px;
        background: #E2E8F0;
        margin: 0 20px;
        min-width: 30px;
    }
    .ul-step-item.active:not(:last-child)::after {
        background: #DBEAFE;
    }
    .ul-step-circle {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        font-size: 13px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border: 2px solid #E2E8F0;
        color: #94A3B8;
        background: #F8FAFD;
        transition: all 0.2s ease;
    }
    .ul-step-item.active .ul-step-circle {
        background: var(--lg-accent);
        border-color: var(--lg-accent);
        color: #FFFFFF;
        box-shadow: 0 3px 10px rgba(23, 105, 255, 0.3);
    }
    .ul-step-text {
        font-size: 13.5px;
        font-weight: 600;
        color: #94A3B8;
        white-space: nowrap;
    }
    .ul-step-item.active .ul-step-text {
        color: var(--lg-accent);
    }

    /* --- Full-Width Main Upload Card --- */
    .ul-main-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
        padding: 32px 36px;
        width: 100%;
    }

    .ul-card-top-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 20px;
        margin-bottom: 26px;
        border-bottom: 1px solid #F1F5F9;
    }
    .ul-card-title {
        font-size: 16px;
        font-weight: 700;
        color: #0F172A;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .ul-card-title .title-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #EFF6FF;
        color: var(--lg-accent);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }
    .ul-step-badge {
        background: #EFF6FF;
        color: var(--lg-accent);
        border: 1px solid #DBEAFE;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12.5px;
        font-weight: 600;
    }

    /* --- Drag & Drop Zone --- */
    .ul-dropzone {
        border: 2px dashed #BFDBFE;
        border-radius: 14px;
        background: #F8FAFC;
        padding: 60px 24px;
        text-align: center;
        transition: all 0.2s ease;
        position: relative;
        margin-bottom: 28px;
    }
    .ul-dropzone:hover, .ul-dropzone.drag-over {
        border-color: var(--lg-accent);
        background: #F0F7FF;
        box-shadow: 0 0 0 4px rgba(23, 105, 255, 0.08);
    }
    .ul-dropzone-icon {
        width: 72px;
        height: 72px;
        border-radius: 18px;
        background: linear-gradient(135deg, var(--lg-accent), #0F52CC);
        color: #FFFFFF;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        margin: 0 auto 18px;
        box-shadow: 0 6px 20px rgba(23, 105, 255, 0.25);
    }
    .ul-dropzone-title {
        font-size: 19px;
        font-weight: 700;
        color: #0F172A;
        margin-bottom: 6px;
        letter-spacing: -0.2px;
    }
    .ul-dropzone-sub {
        font-size: 13.5px;
        color: #64748B;
        margin-bottom: 24px;
    }
    .ul-dropzone-actions {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        flex-wrap: wrap;
        position: relative;
        z-index: 10;
    }

    /* --- File Selected Bar --- */
    .ul-file-selected {
        display: none;
        align-items: center;
        gap: 12px;
        background: #ECFDF5;
        border: 1px solid #A7F3D0;
        border-radius: 10px;
        padding: 12px 18px;
        margin-bottom: 24px;
    }
    .ul-file-selected .file-name {
        font-size: 13.5px;
        font-weight: 600;
        color: #059669;
        flex: 1;
    }
    .ul-file-selected .file-remove {
        color: #DC2626;
        cursor: pointer;
        font-size: 14px;
        background: none;
        border: none;
        padding: 0;
        transition: opacity 0.15s;
    }
    .ul-file-selected .file-remove:hover {
        opacity: 0.8;
    }

    /* --- Campaign Selection Section --- */
    .ul-campaign-section {
        margin-top: 10px;
    }
    .ul-field-label {
        font-size: 12px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .ul-select-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }
    .ul-select {
        width: 100%;
        height: 44px;
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        padding: 0 36px 0 16px;
        font-size: 14px;
        color: #0F172A;
        background: #FFFFFF;
        outline: none;
        transition: all 0.15s ease;
        appearance: none;
        -webkit-appearance: none;
        cursor: pointer;
    }
    .ul-select:focus {
        border-color: var(--lg-accent);
        box-shadow: 0 0 0 3px rgba(23, 105, 255, 0.12);
    }
    .ul-select-arrow {
        position: absolute;
        right: 14px;
        pointer-events: none;
        color: #64748B;
    }

    /* --- Buttons --- */
    .ul-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 42px;
        padding: 0 20px;
        border-radius: 8px;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none !important;
        transition: all 0.15s ease;
        border: none;
        white-space: nowrap;
        user-select: none;
    }
    .ul-btn-primary {
        background: var(--lg-accent);
        color: #FFFFFF !important;
        border: 1px solid var(--lg-accent);
        box-shadow: 0 2px 6px rgba(23, 105, 255, 0.22);
    }
    .ul-btn-primary:hover {
        background: #0F52CC;
        border-color: #0F52CC;
        box-shadow: 0 4px 10px rgba(23, 105, 255, 0.3);
        transform: translateY(-1px);
    }
    .ul-btn-secondary {
        background: #FFFFFF;
        color: #334155 !important;
        border: 1px solid #CBD5E1;
    }
    .ul-btn-secondary:hover {
        background: #F1F5F9;
        color: #0F172A !important;
        border-color: #94A3B8;
    }

    /* --- Bottom Actions Bar --- */
    .ul-footer-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 12px;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid #F1F5F9;
    }

    .ul-alert {
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        margin-bottom: 20px;
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }
    .ul-alert-success { background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; }
    .ul-alert-error { background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; }

    @media (max-width: 768px) {
        .ul-page { padding: 16px; }
        .ul-main-card { padding: 20px; }
        .ul-dropzone { padding: 40px 16px; }
        .ul-steps-card { padding: 14px 16px; }
        .ul-step-item:not(:last-child)::after { margin: 0 10px; min-width: 15px; }
    }
</style>
@endpush

@section('content')
<div class="ul-page">
    <!-- Clean LeadGER Page Header -->
    <div class="ul-page-header">
        <div class="ul-header-left">
            <div class="ul-header-icon-box">
                <i class="fa fa-cloud-upload-alt"></i>
            </div>
            <div class="ul-header-titles">
                <h1 class="ul-page-title">Upload leads</h1>
                <div class="ul-page-subtitle">File &rarr; map columns &rarr; validate &rarr; import</div>
            </div>
        </div>
        <div class="ul-header-right">
            <a href="{{ route('leads') }}" class="ul-btn ul-btn-secondary">
                <i class="fa fa-list"></i> Lead list
            </a>
        </div>
    </div>

    <!-- 4-Step Progress Section -->
    <div class="ul-steps-card">
        <div class="ul-step-item active">
            <div class="ul-step-circle">1</div>
            <div class="ul-step-text">Select file</div>
        </div>
        <div class="ul-step-item">
            <div class="ul-step-circle">2</div>
            <div class="ul-step-text">Map columns</div>
        </div>
        <div class="ul-step-item">
            <div class="ul-step-circle">3</div>
            <div class="ul-step-text">Validate</div>
        </div>
        <div class="ul-step-item">
            <div class="ul-step-circle">4</div>
            <div class="ul-step-text">Import</div>
        </div>
    </div>

    @if(session('doneMessage'))
        <div class="ul-alert ul-alert-success"><i class="fa fa-check-circle mt-1"></i><span>{{ session('doneMessage') }}</span></div>
    @endif
    @if(isset($errors) && $errors->any())
        <div class="ul-alert ul-alert-error"><i class="fa fa-exclamation-circle mt-1"></i><div>@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div></div>
    @endif

    <!-- ONLY ONE Large Centered Upload Lead File Container -->
    <form id="lgLeadUploadForm" action="{{ route('leadsImport') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="ul-main-card">
            <div class="ul-card-top-header">
                <h2 class="ul-card-title">
                    <span class="title-icon"><i class="fa fa-upload"></i></span>
                    <span>Upload Lead File</span>
                </h2>
                <span class="ul-step-badge">Step 1 of 4</span>
            </div>

            <!-- Hidden Native File Input (Directly bound to label) -->
            <input type="file" name="file" id="bulk_file_input" accept=".csv,.xlsx,.xls" style="display: none;" onchange="handleBulkFileSelect(this)">

            <!-- Centered Large Drag & Drop Box -->
            <div class="ul-dropzone" id="dropZone">
                <div class="ul-dropzone-icon">
                    <i class="fa fa-cloud-upload-alt"></i>
                </div>
                <div class="ul-dropzone-title">Drag and drop a lead file here</div>
                <div class="ul-dropzone-sub">CSV or Excel (.csv, .xlsx, .xls), up to 20 MB</div>
                <div class="ul-dropzone-actions">
                    <!-- Native Label for File Input -->
                    <label for="bulk_file_input" class="ul-btn ul-btn-primary" style="margin: 0; cursor: pointer;">
                        <i class="fa fa-folder-open"></i> Select file
                    </label>
                    <!-- Direct Sample File Loader & Downloader -->
                    <button type="button" class="ul-btn ul-btn-secondary" onclick="useSampleFile()">
                        <i class="fa fa-file-download"></i> Use sample file
                    </button>
                </div>
            </div>

            <!-- File Selected Notification Bar -->
            <div class="ul-file-selected" id="fileSelectedBar" style="display: none;">
                <i class="fa fa-file-csv" style="color:#059669;font-size:20px;"></i>
                <span class="file-name" id="fileSelectedName"></span>
                <button type="button" class="file-remove" onclick="clearBulkFile()" title="Remove file"><i class="fa fa-times-circle"></i></button>
            </div>

            <!-- Add Leads to Campaign Section -->
            <div class="ul-campaign-section">
                <label class="ul-field-label">
                    <i class="fa fa-bullhorn" style="color:var(--lg-accent);"></i> Add leads to campaign
                </label>
                <div class="ul-select-wrap">
                    <select name="campaign_id" id="bulk_campaign_select" class="ul-select" required>
                        <option value="">-- Select campaign --</option>
                        @foreach($Campaigns as $singleCampaign)
                            <option value="{{ $singleCampaign->id }}" {{ $singleCampaign->id == $CampaignId ? 'selected' : '' }}>{{ $singleCampaign->name }}</option>
                        @endforeach
                    </select>
                    <i class="fa fa-chevron-down ul-select-arrow"></i>
                </div>
            </div>

            <!-- Bottom Action Controls -->
            <div class="ul-footer-actions">
                <a href="{{ route('leads') }}" class="ul-btn ul-btn-secondary">Cancel</a>
                <button type="button" class="ul-btn ul-btn-primary" onclick="proceedToMapping()">
                    Continue <i class="fa fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push("after-scripts")
<script type="text/javascript">
let currentUploadedFile = null;

const dropZone = document.getElementById('dropZone');
if (dropZone) {
    ['dragenter', 'dragover'].forEach(e => {
        dropZone.addEventListener(e, ev => {
            ev.preventDefault();
            ev.stopPropagation();
            dropZone.classList.add('drag-over');
        });
    });
    ['dragleave', 'drop'].forEach(e => {
        dropZone.addEventListener(e, ev => {
            ev.preventDefault();
            ev.stopPropagation();
            dropZone.classList.remove('drag-over');
        });
    });
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.remove('drag-over');
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
            handleBulkFileSelect({ files: e.dataTransfer.files });
        }
    });
}

function handleBulkFileSelect(input) {
    const files = input.files;
    if (!files || !files[0]) return;
    const file = files[0];
    const allowed = ['csv', 'xlsx', 'xls'];
    const ext = file.name.split('.').pop().toLowerCase();
    
    if (!allowed.includes(ext)) {
        if (typeof toastr !== 'undefined') toastr.error('Please select a CSV or Excel file (.csv, .xlsx, .xls).');
        return;
    }
    if (file.size > 20 * 1024 * 1024) {
        if (typeof toastr !== 'undefined') toastr.error('File exceeds 20 MB limit.');
        return;
    }

    currentUploadedFile = file;
    document.getElementById('fileSelectedName').textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
    document.getElementById('fileSelectedBar').style.display = 'flex';
    if (typeof toastr !== 'undefined') toastr.success('File ready: ' + file.name);
}

function useSampleFile() {
    const sampleCsvData = "NAME,DESIGNATION,PHONE-NUMBER,PHONE-NUMBER2,COMPANY,DESCRIPTION,COUNTRY,PIN CODE,CITY,STATE,ADDRESS,EMAIL,WEBSITE,LEAD VALUE,POSITION,TAGS,USER_ID\n" +
        "Rajesh,,9154936351,9154936351,Ametecs,,India,500014,Hyderabad,Telangana,Hyderabad,xyz@gmail.com,xyz.com,,,,\n" +
        "Sai kiran,,9154118359,9154118359,Ametecs,,India,500014,Hyderabad,Telangana,Hyderabad,xyz@gmail.com,xyz.com,,,,\n" +
        "Madhu,,9154936355,9154936355,Ametecs,,India,500014,Hyderabad,Telangana,Hyderabad,xyz@gmail.com,xyz.com,,,,\n" +
        "Vinitha ,,9121018749,9121018749,Ametecs,,India,500014,Hyderabad,Telangana,Hyderabad,xyz@gmail.com,,,,,\n" +
        "Ashish,,9154167650,9154167650,Ametecs,,India,500014,Hyderabad,Telangana,Hyderabad,xyz@gmail.com,,,,,\n" +
        "Sravani,,7386566623,,Ametecs,,India,500015,Hyderabad,Telangana,Hyderabad,xyz@gmail.com,,,,,\n" +
        "Akshitha,,7386566634,,Ametecs,,India,500016,Hyderabad,Telangana,Hyderabad,xyz@gmail.com,,,,,\n" +
        "Feroz,,7386566637,,Ametecs,,India,500017,Hyderabad,Telangana,Hyderabad,xyz@gmail.com,,,,,";
    
    const blob = new Blob([sampleCsvData], { type: 'text/csv;charset=utf-8;' });
    const sampleFile = new File([blob], "sample_leads.csv", { type: "text/csv" });
    
    currentUploadedFile = sampleFile;
    document.getElementById('fileSelectedName').textContent = 'sample_leads.csv (Sample file loaded)';
    document.getElementById('fileSelectedBar').style.display = 'flex';
    
    // Assign to native input
    const fileInput = document.getElementById('bulk_file_input');
    const container = new DataTransfer();
    container.items.add(sampleFile);
    fileInput.files = container.files;

    // Download sample CSV
    const downloadLink = document.createElement('a');
    downloadLink.href = URL.createObjectURL(blob);
    downloadLink.setAttribute('download', 'sample_leads.csv');
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);

    if (typeof toastr !== 'undefined') {
        toastr.success('Sample file loaded and downloaded!');
    }
}

function clearBulkFile() {
    currentUploadedFile = null;
    const fi = document.getElementById('bulk_file_input');
    if (fi) fi.value = '';
    document.getElementById('fileSelectedBar').style.display = 'none';
    document.getElementById('fileSelectedName').textContent = '';
}

function proceedToMapping() {
    const fileInput = document.getElementById('bulk_file_input');
    const file = currentUploadedFile || (fileInput && fileInput.files && fileInput.files[0]);
    const campaign = document.getElementById('bulk_campaign_select').value;
    
    if (!file) {
        if (typeof toastr !== 'undefined') toastr.error('Please select or drop a lead file to upload.');
        return;
    }
    if (!campaign) {
        if (typeof toastr !== 'undefined') toastr.error('Please select a campaign.');
        return;
    }

    if (currentUploadedFile && fileInput.files.length === 0) {
        try {
            const container = new DataTransfer();
            container.items.add(currentUploadedFile);
            fileInput.files = container.files;
        } catch(e) {}
    }

    document.getElementById('lgLeadUploadForm').submit();
}
</script>
@endpush
