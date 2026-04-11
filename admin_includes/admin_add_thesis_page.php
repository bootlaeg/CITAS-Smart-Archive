<?php
/**
 * Admin - Add New Thesis Page
 * Full page for adding thesis with AI classification
 */

require_once '../db_includes/db_connect.php';
require_login();
require_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Thesis - Citas Smart Archive</title>
    <link rel="icon" type="image/png" href="../img/CITAS_logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-orange: #E67E22;
            --primary-dark: #D35400;
            --light-cream: #FFF8F0;
            --text-dark: #2C3E50;
            --text-gray: #7F8C8D;
            --border-light: #ECF0F1;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background-color: #FAFAFA;
            color: var(--text-dark);
        }

        .header {
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--primary-dark) 100%);
            padding: 1rem 2rem;
            box-shadow: 0 2px 8px rgba(230, 126, 34, 0.15);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: white;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .nav-right {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .nav-right a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .nav-right a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .main-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-title {
            color: var(--primary-orange);
            font-size: 2rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .form-section-title {
            color: var(--primary-orange);
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-light);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-message {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            display: none;
        }

        .alert-success {
            background: #D5F4E6;
            color: #27AE60;
            border: 1px solid #27AE60;
        }

        .alert-danger {
            background: #F8D7DA;
            color: #E74C3C;
            border: 1px solid #E74C3C;
        }

        .alert-info {
            background: #D1ECF1;
            color: #0C5460;
            border: 1px solid #0C5460;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: flex-end;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary-orange);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #7F8C8D;
            color: white;
        }

        .btn-secondary:hover {
            background: #5D6D7B;
        }

        .btn-info {
            background: #3498DB;
            color: white;
        }

        .btn-info:hover {
            background: #2980B9;
        }

        .hidden-section {
            display: none;
        }

        .visible-section {
            display: block;
        }

        .classification-marker {
            background: #FFD700;
            padding: 2rem;
            border: 3px solid red;
            border-radius: 8px;
            margin: 2rem 0;
            text-align: center;
        }

        .classification-marker h4 {
            color: red;
            margin: 0;
        }

        /* Disabled section styles */
        .section-disabled {
            opacity: 0.6;
            pointer-events: none;
            background-color: #f8f9fa;
        }

        .section-disabled .form-control,
        .section-disabled .form-select,
        .section-disabled textarea {
            background-color: #e9ecef;
            cursor: not-allowed;
        }

        .section-disabled input:disabled,
        .section-disabled select:disabled,
        .section-disabled textarea:disabled {
            opacity: 0.7;
        }

        .disabled-badge {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.85rem;
            margin-left: 0.5rem;
        }

        .enabled-badge {
            display: inline-block;
            background: #27AE60;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.85rem;
            margin-left: 0.5rem;
        }

        .upload-section {
            border: 2px solid var(--primary-orange);
            background: #fff8f0;
        }

        .extraction-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .extraction-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
        }

        .extraction-success {
            color: #27AE60;
            display: none;
        }

        .extraction-loading {
            color: #0C5460;
        }

        /* Drag and Drop Upload Area Styles */
        .file-upload-area {
            border: 2px dashed var(--primary-orange);
            border-radius: 12px;
            padding: 2.5rem 2rem;
            text-align: center;
            background-color: #FFF8F0;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .file-upload-area:hover {
            background-color: rgba(230, 126, 34, 0.08);
            border-color: var(--primary-dark);
        }

        .file-upload-area.drag-over {
            background-color: rgba(230, 126, 34, 0.15);
            border-color: var(--primary-dark);
            transform: scale(1.01);
            box-shadow: 0 4px 12px rgba(230, 126, 34, 0.25);
        }

        .file-upload-icon {
            font-size: 3rem;
            color: var(--primary-orange);
            margin-bottom: 0.75rem;
        }

        .file-upload-text {
            color: var(--text-dark);
            margin: 0.5rem 0;
            font-size: 1rem;
        }

        .file-upload-text strong {
            color: var(--primary-orange);
        }

        .file-upload-text-small {
            color: var(--text-gray);
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        #thesisFile {
            display: none;
        }

        .file-selected-info {
            margin-top: 1rem;
            padding: 0.75rem 1rem;
            background-color: #E8F5E9;
            border-left: 4px solid #4CAF50;
            border-radius: 4px;
            display: none;
            align-items: center;
            gap: 0.5rem;
        }

        .file-selected-info.show {
            display: flex;
        }

        .file-selected-info i {
            color: #4CAF50;
            font-size: 1.1rem;
        }

        .file-selected-info span {
            color: #2E7D32;
            font-weight: 500;
        }

        /* Mobile Responsive Design */
        @media (max-width: 768px) {
            .button-group {
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
                margin-top: 2rem;
                justify-content: stretch;
            }

            .btn {
                width: 100%;
                padding: 0.75rem 1rem;
                justify-content: center;
                border-radius: 6px;
            }

            .form-container {
                padding: 1rem;
            }

            .header-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            h1 {
                font-size: 1.5rem;
            }

            .form-group label {
                font-size: 0.95rem;
            }

            .info-box {
                padding: 1rem;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .button-group {
                flex-direction: column;
                gap: 0.5rem;
            }

            .btn {
                padding: 0.65rem 0.75rem;
                font-size: 0.9rem;
            }

            .header {
                padding: 0.75rem 1rem;
            }

            .form-container {
                padding: 0.75rem;
            }

            h1 {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="header-content">
        <div class="logo">
            <i class="fas fa-book-open"></i>
            <span>Citas Smart Archive</span>
        </div>
        <div class="nav-right">
            <a href="../admin.php"><i class="fas fa-arrow-left me-2"></i>Back to Admin</a>
            <a href="#" onclick="handleLogout(event)"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </div>
    </div>
</header>

<!-- Main Content -->
<div class="main-container">
    <h1 class="page-title">
        <i class="fas fa-plus-circle"></i>Add New Thesis
    </h1>

    <div class="alert-message" id="alertMessage"></div>

    <!-- QUICK UPLOAD SECTION - AT TOP, ALWAYS ENABLED -->
    <div class="form-container upload-section" id="uploadSection">
        <div class="form-section-title">
            <i class="fas fa-rocket"></i>Quick Upload
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label"><strong>Upload Thesis File (PDF, DOC, DOCX)</strong></label>
                
                <!-- Drag and Drop Upload Area -->
                <div class="file-upload-area" id="fileUploadArea">
                    <div class="file-upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="file-upload-text">
                        <strong>Drop your file here</strong><br>
                        or click to browse
                    </div>
                    <div class="file-upload-text-small">
                        Supported: PDF, DOC, DOCX (Max 20MB)
                    </div>
                </div>

                <!-- Hidden File Input -->
                <input type="file" class="form-control" id="thesisFile" name="thesis_file" accept=".pdf,.doc,.docx" required>

                <!-- File Selected Info -->
                <div class="file-selected-info" id="fileSelectedInfo">
                    <i class="fas fa-check-circle"></i>
                    <span id="selectedFileName"></span>
                </div>

                <!-- Extraction Status -->
                <div class="extraction-status" id="extractionStatus" style="display: none;">
                    <i class="fas fa-spinner fa-spin extraction-spinner extraction-loading"></i>
                    <span class="extraction-loading">Extracting metadata...</span>
                    <span class="extraction-success" style="display: none;"><i class="fas fa-check-circle me-2"></i>Metadata extracted!</span>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label"><strong>Status</strong></label>
                <select class="form-select" id="thesisStatus" name="status" required>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="archived">Archived</option>
                </select>
            </div>
        </div>

        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Select a thesis file to automatically extract title, author, year, and abstract. Fast and easy!
        </div>
    </div>

    <!-- THESIS INFORMATION SECTION - DISABLED INITIALLY -->
    <div class="form-container section-disabled" id="thesisFormSection">
        <div class="form-section-title">
            <i class="fas fa-file-alt"></i>Thesis Information
            <span class="disabled-badge" id="thesisBadge">Awaiting File Upload</span>
        </div>

        <form id="addThesisForm" enctype="multipart/form-data">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label"><strong>Thesis Title</strong></label>
                    <input type="text" class="form-control" id="thesisTitle" name="title" placeholder="Auto-extracted from file" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><strong>Author Name</strong></label>
                    <input type="text" class="form-control" id="thesisAuthor" name="author" placeholder="Auto-extracted from file" disabled>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label"><strong>Course</strong></label>
                    <select class="form-select" id="thesisCourse" name="course" disabled required>
                        <option value="">Select Course</option>
                        <option value="BSIT">BSIT - Bachelor of Science in Information Technology</option>
                        <option value="BMMA">BMMA - Bachelor of Multimedia Arts</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><strong>Year</strong></label>
                    <input type="text" class="form-control" id="thesisYear" name="year" placeholder="Auto-extracted from file" disabled>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label"><strong>Document Type <span style="color: red;">*</span></strong></label>
                    <select class="form-select" id="documentType" name="document_type" disabled required>
                        <option value="">Select Document Type</option>
                        <option value="journal">Journal (10-20 pages)</option>
                        <option value="book">Book</option>
                        <option value="thesis">Thesis</option>
                        <option value="report">Report</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><strong>Page Count</strong></label>
                    <input type="number" class="form-control" id="pageCount" name="page_count" placeholder="Auto-detected from file" disabled readonly>
                    <small class="text-muted d-block mt-1" id="pageCountWarning" style="display:none; color: #e74c3c;"></small>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label"><strong>Abstract / Description</strong></label>
                <textarea class="form-control" id="thesisAbstract" name="abstract" rows="5" placeholder="Auto-extracted from file" disabled required></textarea>
            </div>
        </form>
    </div>

    <!-- CLASSIFICATION SECTION - DISABLED INITIALLY -->
    <div class="form-container section-disabled" id="classificationSection">
        <div class="form-section-title">
            <i class="fas fa-brain"></i>AI Classification
            <span class="disabled-badge" id="classificationBadge">Awaiting File Upload</span>
        </div>

        <div id="classificationStatus" class="alert alert-info alert-message" style="display: block;">
            <i class="fas fa-info-circle me-2"></i>Ready to generate classification or enter manually. Click "Generate Classification" to analyze with AI.
        </div>

        <!-- Classification Results Display -->
        <div id="classificationResults" style="display: none; margin-bottom: 2rem;">
            <h6 class="mb-3">Classification Results:</h6>
            <div id="resultsContent"></div>
        </div>

        <!-- Classification Form -->
        <form id="classificationForm">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label"><strong>Subject Category</strong></label>
                    <input type="text" class="form-control" id="classifSubjectCategory" disabled>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label"><strong>Research Method</strong></label>
                    <input type="text" class="form-control" id="classifResearchMethod" disabled>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label"><strong>Keywords (comma-separated)</strong></label>
                <textarea class="form-control" id="classifKeywords" rows="2" placeholder="keyword1, keyword2, keyword3" disabled></textarea>
            </div>

            <div class="mb-3" style="display: none;">
                <label class="form-label"><strong>References (JSON, optional)</strong></label>
                <textarea class="form-control" id="classifReferences" rows="2" placeholder="[]" style="font-family: monospace;" disabled></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label"><strong>Related Thesis IDs (JSON, optional)</strong> <span class="badge bg-secondary" title="This feature is planned for future implementation">Coming Soon</span></label>
                <textarea class="form-control" id="classifRelatedTheses" rows="2" placeholder="[]" style="font-family: monospace; background-color: #f8f9fa; color: #999;" disabled></textarea>
                <small class="text-muted d-block mt-1">🔮 Auto-suggestion of related theses will be available in a future update. This field is reserved for future development.</small>
            </div>
        </form>
    </div>

    <!-- Action Buttons -->
    <div class="form-container">
        <div class="button-group">
            <a href="../admin.php" class="btn btn-secondary">
                <i class="fas fa-times me-2"></i>Cancel
            </a>
            <button type="button" class="btn btn-info" id="generateClassificationBtn" onclick="generateClassification()">
                <i class="fas fa-brain me-2"></i>Generate Classification
            </button>
            <button type="button" class="btn btn-primary" onclick="submitForm()">
                <i class="fas fa-save me-2"></i>Save Thesis & Classification
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="thesis_classification_ui.js"></script>
<script src="script.js"></script>

<script>
// ============================================
// GLOBAL STATE
// ============================================
let currentUploadedFile = null;
let classificationGenerated = false;  // Track if Generate Classification was clicked
let classificationData = {
    keywords: [],
    citations: []
};  // Store extracted classification data

// ============================================
// FILE UPLOAD & EXTRACTION HANDLER
// ============================================
function handleFileUploadWithExtraction(event) {
    const file = event.target.files[0];
    
    if (!file) {
        console.log('No file selected');
        return;
    }
    
    console.log('=== FILE SELECTED ===');
    console.log('File:', file.name, 'Size:', file.size, 'Type:', file.type);
    
    // Store file for later use
    currentUploadedFile = file;
    
    // Show extraction status
    const extractionStatus = document.getElementById('extractionStatus');
    extractionStatus.style.display = 'flex';
    extractionStatus.querySelector('.extraction-loading').style.display = 'inline';
    extractionStatus.querySelector('.extraction-success').style.display = 'none';
    
    showAlert('📂 Extracting metadata from file...', 'info');
    
    // Extract metadata
    const formData = new FormData();
    formData.append('file', file);
    
    fetch('../ai_includes/extract_metadata.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Metadata extraction response:', data);
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to extract metadata');
        }
        
        const metadata = data.data;
        console.log('✅ Metadata extracted:', metadata);
        
        // Populate Thesis Information fields
        document.getElementById('thesisTitle').value = metadata.title || '';
        document.getElementById('thesisAuthor').value = metadata.author || '';
        document.getElementById('thesisYear').value = metadata.year || '';
        document.getElementById('thesisAbstract').value = metadata.abstract || '';
        document.getElementById('pageCount').value = metadata.page_count || '';
        
        // Auto-populate course based on extracted degree
        if (metadata.degree) {
            const degree = metadata.degree.toUpperCase();
            console.log('✓ Degree detected:', degree);
            
            // Map degree to course code
            if (degree.includes('INFORMATION TECHNOLOGY') || degree.includes('IT')) {
                document.getElementById('thesisCourse').value = 'BSIT';
                console.log('✓ Course auto-set to BSIT');
            } else if (degree.includes('MULTIMEDIA') || degree.includes('MULTIMEDIA ARTS') || degree.includes('MMA')) {
                document.getElementById('thesisCourse').value = 'BMMA';
                console.log('✓ Course auto-set to BMMA');
            }
        }
        
        console.log('✓ Fields populated with extracted data');
        
        // If page count extracted, show it
        if (metadata.page_count) {
            console.log('✓ Page count detected:', metadata.page_count);
        }
        
        // Enable thesis section
        enableSection('thesisFormSection');
        
        // Keep classification disabled until Generate Classification is clicked
        
        // Update extraction status
        extractionStatus.querySelector('.extraction-loading').style.display = 'none';
        extractionStatus.querySelector('.extraction-success').style.display = 'inline-flex';
        extractionStatus.querySelector('.extraction-success').style.alignItems = 'center';
        
        showAlert('✅ File processed! Metadata extracted. Select a course and click "Generate Classification".', 'success');
    })
    .catch(error => {
        console.error('❌ Metadata extraction error:', error);
        console.error('Error Stack:', error.stack);
        extractionStatus.style.display = 'none';
        showAlert('❌ Error extracting metadata: ' + error.message, 'danger');
    });
}

// ============================================
// ENABLE/DISABLE SECTION FUNCTIONS
// ============================================
function enableSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (!section) {
        console.error('Section not found:', sectionId);
        return;
    }
    
    // Remove the disabled class
    section.classList.remove('section-disabled');
    
    // Remove disabled attribute from ALL inputs/fields in this section
    const inputs = section.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.removeAttribute('disabled');
        input.disabled = false;
    });
    
    // Update badge
    const badge = section.querySelector('[id$="Badge"]');
    if (badge) {
        badge.className = 'enabled-badge';
        badge.textContent = '✓ Ready';
    }
    
    console.log('✓ Section enabled:', sectionId);
}

function disableAllSections() {
    const sections = ['thesisFormSection', 'classificationSection'];
    sections.forEach(sectionId => {
        const section = document.getElementById(sectionId);
        if (section) {
            section.classList.add('section-disabled');
            
            // Update badges
            const badge = section.querySelector('[id$="Badge"]');
            if (badge) {
                badge.className = 'disabled-badge';
                badge.textContent = 'Awaiting File Upload';
            }
        }
    });
    console.log('✓ All sections disabled');
}

function showAlert(message, type = 'success') {
    const alertDiv = document.getElementById('alertMessage');
    alertDiv.className = `alert-message alert-${type}`;
    alertDiv.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${message}`;
    alertDiv.style.display = 'block';
    setTimeout(() => {
        alertDiv.style.display = 'none';
    }, 5000);
}

function showSuccessNotificationModal() {
    const modal = new bootstrap.Modal(document.getElementById('aiEnhancementSuccessModal'), {
        backdrop: 'static',
        keyboard: false
    });
    
    console.log('🎉 Displaying AI Enhancement Success Notification Modal');
    modal.show();
    
    // Auto-close after 5 seconds
    setTimeout(() => {
        modal.hide();
    }, 5000);
}

function clearClassificationData() {
    // Reset all classification fields to empty/default state
    document.getElementById('classifSubjectCategory').value = '';
    document.getElementById('classifResearchMethod').value = '';
    document.getElementById('classifKeywords').value = '';
    document.getElementById('classifReferences').value = '[]';
    document.getElementById('classifRelatedTheses').value = '[]';
    currentClassification = null;
    console.log('✅ Classification data cleared for fresh generation');
}

function generateClassification() {
    const thesisTitle = document.getElementById('thesisTitle').value;
    const abstract = document.getElementById('thesisAbstract').value;
    const file = currentUploadedFile;

    console.log('=== GENERATING CLASSIFICATION ===');
    console.log('Title:', thesisTitle);
    console.log('Abstract:', abstract);
    console.log('File:', file?.name);

    if (!thesisTitle || !abstract) {
        showAlert('Please fill in Thesis Title and Abstract first', 'danger');
        return;
    }

    if (!file) {
        showAlert('Please upload a thesis file first', 'danger');
        return;
    }

    // Clear old classification data
    clearClassificationData();
    
    // Enable classification section
    enableSection('classificationSection');

    const generateBtn = document.getElementById('generateClassificationBtn');
    const originalBtnHTML = generateBtn.innerHTML;
    
    // Show loading state
    generateBtn.disabled = true;
    generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>STEP 1: Extracting Keywords...';

    showAlert('STEP 1: Extracting keywords from document...', 'info');

    // ============================================
    // STEP 1: Direct Extraction
    // ============================================
    
    console.log('📤 STEP 1: Calling keyword extraction endpoint...');
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('abstract', abstract);

    fetch('../ai_includes/extract_keywords_simple.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('📨 Step 1 response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('📨 Step 1 response data:', data);
        
        if (!data.success) {
            throw new Error(data.error || 'Keyword extraction failed');
        }

        const extractedKeywords = data.keywords || [];
        const extractedCitations = data.citations || {};
        const extractedAuthor = data.author || '';
        
        console.log('✅ STEP 1 SUCCESS');
        console.log('   Keywords found:', extractedKeywords.length);
        console.log('   Keywords:', extractedKeywords);
        console.log('   Citations found:', data.citation_count);
        console.log('   Citations:', extractedCitations);
        console.log('   Author extracted:', extractedAuthor || '(empty)');
        
        // Display Step 1 results
        displayExtractionResults(extractedKeywords, extractedCitations, file);
        
        // Set keywords in form
        document.getElementById('classifKeywords').value = extractedKeywords.join(', ');
        
        // Set author in form if extracted
        if (extractedAuthor) {
            const authorField = document.getElementById('thesisAuthor');
            if (authorField && !authorField.value) {
                // Only update if field is empty
                authorField.value = extractedAuthor;
                console.log('✓ Author field updated from extraction:', extractedAuthor);
            }
        }
        
        // Set references in form (store all raw references)
        if (extractedCitations.raw && extractedCitations.raw.length > 0) {
            document.getElementById('classifReferences').value = JSON.stringify(extractedCitations.raw);
        }
        
        // ✅ ENABLE SAVE BUTTON - Classification has been generated
        classificationGenerated = true;
        classificationData.keywords = extractedKeywords;
        classificationData.citations = extractedCitations.raw || [];
        classificationData.author = extractedAuthor;
        
        const saveBtn = document.querySelector('button[onclick="submitForm()"]');
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.title = 'Save thesis with extracted classification';
            console.log('✓ Save button ENABLED (classification generated)');
        }
        
        showAlert('✅ STEP 1 Complete: ' + extractedKeywords.length + ' keywords and ' + data.citation_count + ' citations extracted from document', 'success');
        
        // Update button to show enhancement option
        generateBtn.disabled = false;
        generateBtn.innerHTML = '<i class="fas fa-wand-magic-sparkles me-2"></i>Enhance with AI (Step 2)';
        generateBtn.onclick = () => enhanceWithAI(file, thesisTitle, abstract, extractedKeywords, extractedAuthor);
    })
    .catch(error => {
        console.error('❌ STEP 1 ERROR:', error);
        console.error('Error details:', error.stack);
        generateBtn.disabled = false;
        generateBtn.innerHTML = originalBtnHTML;
        showAlert('Error: ' + error.message, 'danger');
    });
}

function displayExtractionResults(keywords, citations, file) {
    console.log('🎨 Displaying extraction results');
    console.log('   Keywords:', keywords);
    console.log('   Citations:', citations);
    console.log('   File:', file.name);
    
    const resultsDiv = document.getElementById('classificationResults');
    const resultsContent = document.getElementById('resultsContent');
    
    // Capitalize each word in keywords for professional display
    const capitalizedKeywords = keywords.map(kw => {
        return kw.split(' ')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
            .join(' ');
    });
    const keywordsList = capitalizedKeywords.map(kw => `<span class="badge bg-info me-2 mb-2">${kw}</span>`).join('');
    
    // Build references display with multiple formats
    let referencesHtml = '';
    if (citations && citations.raw && citations.raw.length > 0) {
        referencesHtml = `
        <div class="mt-3">
            <h6 class="mb-2">📚 Raw References Found (${citations.raw.length}):</h6>
            
            <!-- Raw URLs List -->
            <div class="mb-3">
                <ol class="list-group list-group-numbered">
                    ${citations.raw.map((url, index) => {
                        return `<li class="list-group-item">
                            <small><a href="${url}" target="_blank" class="text-decoration-none">${url}</a></small>
                        </li>`;
                    }).join('')}
                </ol>
            </div>
        </div>`;
    } else {
        referencesHtml = `<div class="mt-3 text-muted"><small><em>No references found in document</em></small></div>`;
    }
    
    resultsContent.innerHTML = `
        <div class="mb-3">
            <h6 class="mb-2">📄 Extracted from Document</h6>
            <p class="text-muted small">File: ${file.name} • Keywords: ${keywords.length} • References: ${citations && citations.raw ? citations.raw.length : 0}</p>
            <div class="mt-2">
                ${keywordsList}
            </div>
            ${referencesHtml}
        </div>
        <div class="alert alert-info mb-0">
            <i class="fas fa-info-circle me-2"></i>
            Keywords and references have been extracted from your document. You can now enhance them with AI-generated classification or proceed with these values.
        </div>
    `;
    
    resultsDiv.style.display = 'block';
    console.log('✓ Extraction results displayed on page');
}

function enhanceWithAI(file, title, abstract, documentKeywords, extractedAuthor = '') {
    const generateBtn = document.getElementById('generateClassificationBtn');
    const originalBtnHTML = generateBtn.innerHTML;
    
    console.log('=== STEP 2: ENHANCE WITH AI ===');
    console.log('File:', file.name);
    console.log('Title:', title);
    console.log('Abstract:', abstract);
    console.log('Document Keywords:', documentKeywords);
    console.log('Extracted Author from Step 1:', extractedAuthor || '(none)');
    
    generateBtn.disabled = true;
    generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>STEP 2: Enhancing with AI...';
    
    showAlert('STEP 2: Polishing text and enhancing with AI-generated classification (this may take 10-30 seconds)...', 'info');

    let polishedTitle = title;
    let polishedAbstract = abstract;

    // ============================================
    // STEP 2A: Polish Title and Abstract
    // ============================================
    
    console.log('🧹 STEP 2A: Polishing title and abstract text...');
    
    fetch('../ai_includes/polish_abstract.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ 
            title: title,
            abstract: abstract 
        })
    })
    .then(response => response.json())
    .then(polishResponse => {
        if (polishResponse.success) {
            console.log('✅ Text polishing completed');
            
            // Update title if it was polished
            if (polishResponse.title && polishResponse.title !== title) {
                polishedTitle = polishResponse.title;
                const titleField = document.getElementById('thesisTitle');
                if (titleField) {
                    titleField.value = polishedTitle;
                    console.log('✓ Title field updated with polished text');
                    console.log('  Before:', title.substring(0, 50));
                    console.log('  After:', polishedTitle.substring(0, 50));
                }
            }
            
            // Update abstract if it was polished
            if (polishResponse.abstract && polishResponse.abstract !== abstract) {
                polishedAbstract = polishResponse.abstract;
                const abstractField = document.getElementById('thesisAbstract');
                if (abstractField) {
                    abstractField.value = polishedAbstract;
                    console.log('✓ Abstract field updated with polished text');
                    console.log('  Before:', abstract.substring(0, 50));
                    console.log('  After:', polishedAbstract.substring(0, 50));
                }
            }
            
            // Log any errors that occurred
            if (polishResponse.errors && polishResponse.errors.length > 0) {
                console.warn('⚠️  Polishing warnings:', polishResponse.errors);
            }
        } else {
            console.warn('⚠️  Text polishing warning:', polishResponse.error);
        }
    })
    .catch(error => {
        console.warn('⚠️  Text polishing error (continuing anyway):', error);
    })
    .then(() => {
        // Continue to STEP 2B: AI Classification after polishing completes
        return performAIClassification(generateBtn, originalBtnHTML, polishedTitle, polishedAbstract, documentKeywords, extractedAuthor);
    })
    .catch(error => {
        console.error('❌ STEP 2 failed:', error);
        generateBtn.disabled = false;
        generateBtn.innerHTML = originalBtnHTML;
        showAlert('❌ Enhancement Error: ' + error.message, 'danger');
    });
}

function performAIClassification(generateBtn, originalBtnHTML, polishedTitle, polishedAbstract, documentKeywords, extractedAuthor = '') {
    // ============================================
    // STEP 2B: AI Enhancement & Classification
    // ============================================
    
    console.log('🧠 STEP 2B: Getting AI classification...');
    
    const aiData = {
        document_text: polishedAbstract,
        abstract: polishedAbstract,
        title: polishedTitle
    };
    
    console.log('📤 Request payload:', aiData);
    
    const startTime = Date.now();
    
    return fetch('../ai_includes/generate_ai_classification.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(aiData)
    })
    .then(response => {
        const elapsed = ((Date.now() - startTime) / 1000).toFixed(2);
        console.log('📨 Response arrived after ' + elapsed + 's');
        console.log('📨 Response status:', response.status);
        console.log('📨 Response headers:', response.headers);
        return response.text().then(text => {
            console.log('📨 Raw response text:', text.substring(0, 500) + (text.length > 500 ? '...' : ''));
            return JSON.parse(text);
        });
    })
    .then(aiResponse => {
        console.log('📨 Parsed AI response:', aiResponse);
        
        if (!aiResponse.success) {
            console.error('❌ AI response indicates failure');
            console.error('Error from server:', aiResponse.error);
            throw new Error(aiResponse.error || 'AI enhancement failed - check console for details');
        }
        
        console.log('✅ AI response successful');
        console.log('   Subject Category:', aiResponse.subject_category);
        console.log('   Research Method:', aiResponse.research_method);
        console.log('   Keywords:', aiResponse.keywords);
        console.log('   Author:', aiResponse.author || '(not extracted)');
        
        // Check if fields are empty
        const hasError = !aiResponse.subject_category || !aiResponse.research_method || aiResponse.keywords.length === 0;
        
        if (hasError) {
            console.warn('⚠️  WARNING: Some fields are empty!');
            alert('ALERT: AI returned incomplete data. This may mean:\n' +
                  '1. Ollama service is not responding\n' +
                  '2. Ollama is processing too slowly\n' +
                  '3. The model is not loaded\n\n' +
                  'Check the browser console (F12) for error details.');
        }
        
        // Display AI results
        displayAIResults(aiResponse);
        
        // Update form fields with AI data
        console.log('🔄 Updating form fields...');
        
        const subjectField = document.getElementById('classifSubjectCategory');
        subjectField.value = aiResponse.subject_category || '';
        console.log('✓ Subject Category updated:', subjectField.value || '(empty)');
        
        const methodField = document.getElementById('classifResearchMethod');
        methodField.value = aiResponse.research_method || '';
        console.log('✓ Research Method updated:', methodField.value || '(empty)');
        
        // Use AI-generated keywords only (replace document keywords with AI refined ones)
        const keywordsField = document.getElementById('classifKeywords');
        if (aiResponse.keywords && aiResponse.keywords.length > 0) {
            // Capitalize each word in each keyword
            const capitalizedKeywords = aiResponse.keywords.map(kw => {
                return kw.split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
                    .join(' ');
            });
            keywordsField.value = capitalizedKeywords.join(', ');
            console.log('✓ Keywords replaced with AI-generated keywords (capitalized):', keywordsField.value);
        } else {
            // Fallback to document keywords if AI returns empty
            keywordsField.value = documentKeywords.join(', ');
            console.log('✓ Keywords kept from document (AI returned empty):', keywordsField.value);
        }
        
        // Update References field with AI-generated references
        if (aiResponse.citations && aiResponse.citations.length > 0) {
            const referencesField = document.getElementById('classifReferences');
            // Store as JSON array
            referencesField.value = JSON.stringify(aiResponse.citations);
            console.log('✓ References updated:', referencesField.value);
            
            // Update classification data for saving
            classificationData.citations = aiResponse.citations;
        }
        
        // Update classification data with AI-enhanced values
        classificationData.keywords = (aiResponse.keywords && aiResponse.keywords.length > 0) ? aiResponse.keywords : documentKeywords;
        
        // Handle author from Step 2 AI response
        if (aiResponse.author) {
            const authorField = document.getElementById('thesisAuthor');
            if (authorField) {
                // Prefer AI-extracted author over Step 1 extraction
                authorField.value = aiResponse.author;
                console.log('✓ Author field updated from AI extraction:', aiResponse.author);
                classificationData.author = aiResponse.author;
            }
        } else if (extractedAuthor) {
            // Fallback to Step 1 extracted author if AI didn't extract one
            classificationData.author = extractedAuthor;
            console.log('✓ Author kept from Step 1 extraction:', extractedAuthor);
        }
        
        showAlert('✅ STEP 2 Complete: AI classification received', 'success');
        
        console.log('✅ STEP 2 COMPLETE - All fields populated');
        
        // Show success notification modal
        showSuccessNotificationModal();
        
        // Reset button
        generateBtn.disabled = false;
        generateBtn.innerHTML = '<i class="fas fa-brain me-2"></i>Generate Classification';
        generateBtn.onclick = generateClassification;
    })
    .catch(error => {
        console.error('❌ AI enhancement error:', error);
        console.error('Error details:', error.stack);
        generateBtn.disabled = false;
        generateBtn.innerHTML = originalBtnHTML;
        
        const elapsed = ((Date.now() - startTime) / 1000).toFixed(2);
        console.error('Total time elapsed:', elapsed + 's');
        
        showAlert('❌ AI Enhancement Error: ' + error.message + '\n\nMake sure Ollama is running and mistral model is loaded. Check console for details.', 'danger');
    });
}

function displayAIResults(aiData) {
    console.log('🎨 Displaying AI results');
    
    const resultsDiv = document.getElementById('classificationResults');
    const resultsContent = document.getElementById('resultsContent');
    
    // Capitalize each word in keywords for professional display
    const capitalizedKeywords = (aiData.keywords || []).map(kw => {
        return kw.split(' ')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
            .join(' ');
    });
    const keywordsList = capitalizedKeywords.map(kw => `<span class="badge bg-success me-2 mb-2">${kw}</span>`).join('');
    
    const htmlContent = `
        <div class="mt-3 pt-3 border-top">
            <h6 class="mb-2">🧠 AI-Generated Classification</h6>
            <div class="row mb-2">
                <div class="col-md-6">
                    <strong>Subject Category:</strong>
                    <p>${aiData.subject_category || 'N/A'}</p>
                </div>
                <div class="col-md-6">
                    <strong>Research Method:</strong>
                    <p>${aiData.research_method || 'N/A'}</p>
                </div>
            </div>
            <div class="mb-3">
                <strong>AI Keywords:</strong>
                <div class="mt-2">
                    ${keywordsList}
                </div>
            </div>
        </div>
    `;
    
    resultsContent.innerHTML = resultsContent.innerHTML + htmlContent;
    console.log('✓ AI results displayed on page');
}

function populateClassificationForm(classification) {
    // This function is kept for compatibility but main logic is in STEP 1 & 2
    console.log('populateClassificationForm called with:', classification);
    
    if (!classification) {
        console.error('Classification is null/undefined!');
        return;
    }
}

function submitForm() {
    try {
        // Check if classification has been generated
        if (!classificationGenerated) {
            showAlert('⚠️ Please click "Generate Classification" first before saving', 'warning');
            return;
        }

        // Check if a file was uploaded
        const file = currentUploadedFile;
        if (!file) {
            showAlert('❌ Please upload a thesis file first', 'danger');
            return;
        }

        console.log('=== SAVING THESIS & CLASSIFICATION ===');
        console.log('File to upload:', file.name);

        // Helper function to safely get element value
        const getElementValue = (id, defaultValue = '') => {
            const element = document.getElementById(id);
            if (!element) {
                console.warn(`Warning: Element with id '${id}' not found`);
                return defaultValue;
            }
            return element.value || defaultValue;
        };

        // Collect thesis data
        const documentType = getElementValue('documentType');
        const pageCount = getElementValue('pageCount');
        
        showAlert('💾 Uploading file and saving thesis...', 'info');
        console.log('🚀 Starting file upload...');
        
        // First, upload the file
        const uploadFormData = new FormData();
        uploadFormData.append('file', file);
        uploadFormData.append('title', getElementValue('thesisTitle'));
        uploadFormData.append('author', getElementValue('thesisAuthor'));
        uploadFormData.append('course', getElementValue('thesisCourse'));
        uploadFormData.append('year', getElementValue('thesisYear'));

        // Upload file to uploads directory
        fetch('../client_includes/upload_thesis_file.php', {
            method: 'POST',
            body: uploadFormData
        })
        .then(uploadResponse => handleUploadResponse(uploadResponse, getElementValue, documentType, pageCount))
        .then(thesisData => saveThesisToDatabase(thesisData))
        .catch(error => {
            console.error('❌ Error:', error);
            showAlert('❌ Error: ' + error.message, 'danger');
        });
    } catch (error) {
        console.error('❌ Unexpected error in submitForm:', error);
        showAlert('❌ Unexpected error: ' + error.message, 'danger');
    }
}

async function handleUploadResponse(response, getElementValue, documentType, pageCount) {
    console.log('📨 Upload response status:', response.status);
    
    const text = await response.text();
    console.log('📨 Upload response text:', text);
    
    let uploadData;
    try {
        uploadData = JSON.parse(text);
    } catch (e) {
        throw new Error('Invalid upload response: ' + text);
    }
    
    if (!uploadData.success) {
        throw new Error(uploadData.error || 'File upload failed');
    }
    
    console.log('✅ File uploaded successfully:', uploadData.file_path);
    
    const thesisData = {
        // Thesis Info
        title: getElementValue('thesisTitle'),
        author: getElementValue('thesisAuthor'),
        course: getElementValue('thesisCourse'),
        year: parseInt(getElementValue('thesisYear', '0')) || new Date().getFullYear(),
        abstract: getElementValue('thesisAbstract'),
        status: getElementValue('thesisStatus', 'approved'),
        
        // Document Info
        document_type: documentType,
        page_count: pageCount ? parseInt(pageCount) : null,
        file_path: uploadData.file_path,
        file_type: uploadData.file_type,
        file_size: uploadData.file_size,
        
        // Classification Data
        subject_category: getElementValue('classifSubjectCategory'),
        research_method: getElementValue('classifResearchMethod'),
        
        // Keywords and Citations
        keywords: getElementValue('classifKeywords', '')
            .split(',')
            .map(k => k.trim())
            .filter(k => k.length > 0),
        
        citations: (() => {
            try {
                const citationsValue = getElementValue('classifReferences', '[]');
                if (citationsValue && citationsValue.startsWith('[')) {
                    return JSON.parse(citationsValue);
                }
                return [];
            } catch (e) {
                console.warn('Error parsing citations:', e);
                return [];
            }
        })()
    };

    console.log('📤 Data to save:', thesisData);
    
    // Validate required fields
    if (!thesisData.title || !thesisData.abstract || !thesisData.course) {
        throw new Error('Please fill in Title, Abstract, and Course');
    }
    
    // Validate document type
    if (!documentType) {
        throw new Error('Please select a Document Type');
    }
    
    // Validate page count for journals
    if (documentType === 'journal') {
        if (!pageCount || pageCount < 10 || pageCount > 20) {
            throw new Error('Journal documents must be between 10-20 pages. Current: ' + (pageCount || 'Not specified'));
        }
    }
    
    return thesisData;
}

async function saveThesisToDatabase(thesisData) {
    console.log('🚀 Sending request to save_thesis_classification.php');
    
    const response = await fetch('./save_thesis_classification.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(thesisData)
    });
    
    console.log('📨 Response status:', response.status);
    
    const text = await response.text();
    console.log('📨 Response text:', text);
    
    let data;
    try {
        data = JSON.parse(text);
    } catch (e) {
        throw new Error('Invalid response from server: ' + text);
    }
    
    if (data.success) {
        console.log('✅ Thesis saved with ID:', data.thesis_id);
        showAlert('✅ Thesis and classification saved successfully!', 'success');
        
        // Clear localStorage
        localStorage.removeItem('thesisFormData');
        console.log('✓ Form data cleared from localStorage');
        
        // Redirect after 2 seconds
        setTimeout(() => {
            window.location.href = '../admin.php';
        }, 2000);
    } else {
        throw new Error(data.error || 'Unknown error occurred');
    }
}

// ============================================
// DRAG AND DROP FILE UPLOAD
// ============================================
function setupDragAndDrop() {
    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('thesisFile');
    const fileSelectedInfo = document.getElementById('fileSelectedInfo');

    if (!fileUploadArea || !fileInput) {
        console.error('File upload area or input not found');
        return;
    }

    // Click to browse
    fileUploadArea.addEventListener('click', () => {
        fileInput.click();
    });

    // Drag over
    fileUploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.stopPropagation();
        fileUploadArea.classList.add('drag-over');
        console.log('📦 File dragged over upload area');
    });

    // Drag leave
    fileUploadArea.addEventListener('dragleave', (e) => {
        e.preventDefault();
        e.stopPropagation();
        fileUploadArea.classList.remove('drag-over');
        console.log('📦 File left upload area');
    });

    // Drop
    fileUploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        fileUploadArea.classList.remove('drag-over');
        
        const files = e.dataTransfer.files;
        
        if (files.length > 0) {
            const file = files[0];
            console.log('📥 File dropped:', file.name);
            
            // Validate file
            if (!validateDroppedFile(file)) {
                return;
            }
            
            // Set the file to input and trigger change event
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            fileInput.files = dataTransfer.files;
            
            // Show file selected info
            showFileSelectedInfo(file.name);
            
            // Trigger change event to start extraction
            const event = new Event('change', { bubbles: true });
            fileInput.dispatchEvent(event);
        }
    });

    // Also handle file input change from regular file picker
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            showFileSelectedInfo(fileInput.files[0].name);
        }
    });
}

function validateDroppedFile(file) {
    // Allowed file types
    const allowedTypes = ['application/pdf', 'application/msword', 
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    const allowedExtensions = ['.pdf', '.doc', '.docx'];
    
    // Check file extension
    const fileName = file.name.toLowerCase();
    const hasValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));
    
    if (!hasValidExtension) {
        console.error('❌ Invalid file type:', file.type);
        showAlert('❌ Invalid file type. Please upload PDF, DOC, or DOCX files only.', 'danger');
        return false;
    }
    
    // Check file size (20MB)
    const maxSize = 20 * 1024 * 1024;
    if (file.size > maxSize) {
        console.error('❌ File too large:', file.size);
        showAlert('❌ File is too large. Maximum size is 20MB.', 'danger');
        return false;
    }
    
    console.log('✅ File validation passed:', file.name);
    return true;
}

function showFileSelectedInfo(fileName) {
    const fileSelectedInfo = document.getElementById('fileSelectedInfo');
    const selectedFileName = document.getElementById('selectedFileName');
    
    if (fileSelectedInfo && selectedFileName) {
        selectedFileName.textContent = '📄 ' + fileName + ' selected';
        fileSelectedInfo.classList.add('show');
        console.log('✓ File info displayed:', fileName);
    }
}

// Load everything on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Page loaded. Sections start disabled, waiting for file selection.');
    
    // Initialize all sections as disabled
    disableAllSections();
    
    // Disable save button initially - only enable after Generate Classification is clicked
    const saveBtn = document.querySelector('button[onclick="submitForm()"]');
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.title = 'Click "Generate Classification" first to enable saving';
        console.log('✓ Save button disabled (requires Generate Classification)');
    }
    
    // Restore form data from localStorage
    restoreFormData();
    
    // Add auto-save to form fields
    const fieldsToSave = ['thesisCourse'];
    fieldsToSave.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('change', saveFormData);
            field.addEventListener('input', saveFormData);
        }
    });
    
    // Add validation for document type and page count
    const documentTypeSelect = document.getElementById('documentType');
    const pageCountInput = document.getElementById('pageCount');
    const pageCountWarning = document.getElementById('pageCountWarning');
    
    function validateJournalPageCount() {
        const docType = documentTypeSelect.value;
        const pageCount = pageCountInput.value;
        
        if (docType === 'journal') {
            if (!pageCount) {
                pageCountWarning.textContent = '⚠️ Page count required for journals';
                pageCountWarning.style.display = 'block';
            } else if (pageCount < 10 || pageCount > 20) {
                pageCountWarning.textContent = `⚠️ Journal must be 10-20 pages (current: ${pageCount})`;
                pageCountWarning.style.display = 'block';
            } else {
                pageCountWarning.style.display = 'none';
            }
        } else {
            pageCountWarning.style.display = 'none';
        }
    }
    
    if (documentTypeSelect) {
        documentTypeSelect.addEventListener('change', validateJournalPageCount);
    }
    if (pageCountInput) {
        pageCountInput.addEventListener('change', validateJournalPageCount);
        pageCountInput.addEventListener('input', validateJournalPageCount);
    }
    
    // Add file upload handler for automatic extraction
    const fileInput = document.getElementById('thesisFile');
    if (fileInput) {
        fileInput.addEventListener('change', handleFileUploadWithExtraction);
    }

    // Setup drag and drop functionality
    setupDragAndDrop();
});

// Save form data to localStorage
function saveFormData() {
    const formData = {
        thesisTitle: document.getElementById('thesisTitle').value,
        thesisAuthor: document.getElementById('thesisAuthor').value,
        thesisCourse: document.getElementById('thesisCourse').value,
        thesisYear: document.getElementById('thesisYear').value,
        thesisAbstract: document.getElementById('thesisAbstract').value,
        thesisStatus: document.getElementById('thesisStatus').value,
        timestamp: new Date().toISOString()
    };
    localStorage.setItem('thesisFormData', JSON.stringify(formData));
    console.log('✅ Form data saved to localStorage');
}

// Restore form data from localStorage
function restoreFormData() {
    const savedData = localStorage.getItem('thesisFormData');
    if (savedData) {
        try {
            const formData = JSON.parse(savedData);
            document.getElementById('thesisTitle').value = formData.thesisTitle || '';
            document.getElementById('thesisAuthor').value = formData.thesisAuthor || '';
            document.getElementById('thesisCourse').value = formData.thesisCourse || '';
            document.getElementById('thesisYear').value = formData.thesisYear || '';
            document.getElementById('thesisAbstract').value = formData.thesisAbstract || '';
            document.getElementById('thesisStatus').value = formData.thesisStatus || '';
            console.log('✅ Form data restored from localStorage (saved at ' + formData.timestamp + ')');
        } catch (e) {
            console.error('Error restoring form data:', e);
        }
    } else {
        console.log('ℹ️ No saved form data found in localStorage');
    }
}

// Handle file upload - validate only, no automatic extraction
function handleFileUploadValidation(event) {
    const file = event.target.files[0];
    const statusDiv = document.getElementById('fileUploadStatus');
    
    if (!file) {
        statusDiv.innerHTML = '';
        return;
    }
    
    // Validate file size (20MB)
    const maxSize = 20 * 1024 * 1024;
    if (file.size > maxSize) {
        statusDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>File exceeds 20MB limit</div>';
        event.target.value = '';
        return;
    }
    
    // Validate file type
    const allowedExtensions = ['pdf', 'doc', 'docx'];
    const extension = file.name.split('.').pop().toLowerCase();
    
    if (!allowedExtensions.includes(extension)) {
        statusDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Unsupported file type. Allowed: PDF, DOC, DOCX</div>';
        event.target.value = '';
        return;
    }
    
    // Show success message
    statusDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>File selected: ' + file.name + '</div>';
}
</script>

<!-- AI Enhancement Success Notification Modal -->
<div class="modal fade" id="aiEnhancementSuccessModal" tabindex="-1" aria-labelledby="aiEnhancementSuccessLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none;">
                <h5 class="modal-title" id="aiEnhancementSuccessLabel">
                    <i class="fas fa-check-circle me-2"></i>AI Enhancement Complete!
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div style="font-size: 3rem; color: #28a745; margin-bottom: 1rem;">
                    <i class="fas fa-wand-magic-sparkles"></i>
                </div>
                <h6 class="text-dark mb-3">Step 2 Successfully Completed!</h6>
                <p class="text-muted mb-0">
                    <strong>AI-generated classification has been applied:</strong>
                </p>
                <ul class="text-start small text-muted mt-3" style="list-style: none; padding: 0;">
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Title and abstract polished</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Subject category analyzed</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Keywords generated</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Research method identified</li>
                </ul>
                <p class="text-muted small mt-3 mb-0">
                    <em>You can now review and save your thesis.</em>
                </p>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #dee2e6;">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                    <i class="fas fa-thumbs-up me-2"></i>Great! Let's Continue
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    #aiEnhancementSuccessModal .fa-wand-magic-sparkles {
        animation: sparkle 0.8s ease-in-out;
    }
    
    @keyframes sparkle {
        0% { transform: scale(0.8); opacity: 0; }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); opacity: 1; }
    }
</style>

</script>

</body>
</html>
