<?php
/**
 * Admin - Scanner/Camera Page
 * Page for capturing thesis documents using camera or uploading images
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
    <title>Document Scanner - CITAS Smart Archive</title>
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
            flex-wrap: wrap;
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

        .btn-success {
            background: #27AE60;
            color: white;
        }

        .btn-success:hover {
            background: #229954;
        }

        .scanner-section {
            border: 2px solid var(--primary-orange);
            background: #fff8f0;
        }

        /* Camera Capture Styles */
        .camera-container {
            position: relative;
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        #cameraStream {
            width: 100%;
            height: auto;
            display: block;
        }

        .camera-controls {
            position: absolute;
            bottom: 1rem;
            left: 0;
            right: 0;
            display: flex;
            gap: 1rem;
            justify-content: center;
            align-items: center;
            padding: 0 1rem;
        }

        .camera-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .capture-btn {
            background: var(--primary-orange);
            color: white;
            box-shadow: 0 4px 12px rgba(230, 126, 34, 0.4);
        }

        .capture-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.1);
        }

        .close-camera-btn {
            background: rgba(231, 76, 60, 0.8);
            color: white;
            width: auto;
            padding: 0.5rem 1rem;
            border-radius: 6px;
        }

        .close-camera-btn:hover {
            background: rgb(192, 57, 43);
        }

        /* File Upload Styles */
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

        #imageFile {
            display: none;
        }

        /* Image Preview Styles */
        .image-preview {
            margin-top: 2rem;
        }

        .preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .preview-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            background: #f0f0f0;
            aspect-ratio: 1;
        }

        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .preview-item .delete-btn {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: rgba(231, 76, 60, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .preview-item .delete-btn:hover {
            background: rgb(192, 57, 43);
            transform: scale(1.1);
        }

        .tabs-container {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--border-light);
        }

        .tab-btn {
            background: none;
            border: none;
            padding: 1rem 1.5rem;
            color: var(--text-gray);
            font-weight: 600;
            cursor: pointer;
            position: relative;
            transition: color 0.3s;
        }

        .tab-btn.active {
            color: var(--primary-orange);
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--primary-orange);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .captured-image-display {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Mobile Responsive Design */
        @media (max-width: 768px) {
            .button-group {
                flex-direction: column;
                gap: 0.75rem;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .form-container {
                padding: 1rem;
            }

            .header-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .tabs-container {
                gap: 0;
            }

            .tab-btn {
                flex: 1;
                padding: 0.75rem;
                font-size: 0.9rem;
                text-align: center;
            }

            .camera-controls {
                gap: 0.5rem;
            }

            .camera-btn {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 0.75rem 1rem;
            }

            .header-content {
                flex-direction: column;
                gap: 0.75rem;
            }

            .logo {
                font-size: 1rem;
            }

            .page-title {
                font-size: 1.25rem;
            }

            .form-container {
                padding: 0.75rem;
            }

            .preview-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }

            .camera-container {
                max-width: 100%;
            }
        }

        .hidden {
            display: none !important;
        }

        .no-images-message {
            text-align: center;
            padding: 2rem;
            color: var(--text-gray);
        }

        .no-images-message i {
            font-size: 3rem;
            color: var(--border-light);
            margin-bottom: 1rem;
            display: block;
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="header-content">
        <div class="logo">
            <i class="fas fa-book-open"></i>
            <span>CITAS Smart Archive</span>
        </div>
        <div class="nav-right">
            <a href="admin_add_thesis_page.php"><i class="fas fa-arrow-left me-2"></i>Back to Add Thesis</a>
            <a href="../admin.php"><i class="fas fa-home me-2"></i>Admin Home</a>
            <a href="#" onclick="handleLogout(event)"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </div>
    </div>
</header>

<!-- Main Content -->
<div class="main-container">
    <h1 class="page-title">
        <i class="fas fa-camera"></i>Document Scanner
    </h1>

    <div id="alertMessage" class="alert-message"></div>

    <!-- Scanner Section -->
    <div class="form-container scanner-section">
        <!-- Tabs -->
        <div class="tabs-container">
            <button class="tab-btn active" onclick="switchTab('camera')">
                <i class="fas fa-camera me-2"></i>Camera Capture
            </button>
            <button class="tab-btn" onclick="switchTab('upload')">
                <i class="fas fa-cloud-upload-alt me-2"></i>Upload Images
            </button>
            <button class="tab-btn" onclick="switchTab('gallery')">
                <i class="fas fa-images me-2"></i>Captured Images
            </button>
        </div>

        <!-- CAMERA CAPTURE TAB -->
        <div id="camera" class="tab-content active">
            <div class="form-section-title">
                <i class="fas fa-camera"></i>Capture Document with Camera
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Position your document clearly in the frame and click the capture button. You can take multiple pictures.
            </div>

            <div class="row mb-3">
                <div class="col-12">
                    <div class="camera-container" id="cameraContainer">
                        <video id="cameraStream" autoplay playsinline></video>
                        <div class="camera-controls">
                            <button type="button" class="camera-btn capture-btn" onclick="captureImage()" id="captureBtn" title="Capture Image">
                                <i class="fas fa-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-12">
                    <small class="text-muted">
                        <i class="fas fa-mobile-alt me-2"></i>
                        Grant camera permission when prompted. Camera quality affects OCR accuracy.
                    </small>
                </div>
            </div>

            <div class="button-group">
                <button type="button" class="btn btn-secondary" onclick="stopCamera()" id="stopCameraBtn" style="display: none;">
                    <i class="fas fa-times me-2"></i>Stop Camera
                </button>
            </div>
        </div>

        <!-- FILE UPLOAD TAB -->
        <div id="upload" class="tab-content">
            <div class="form-section-title">
                <i class="fas fa-cloud-upload-alt"></i>Upload Image Files
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Upload clear images of your thesis pages. Multiple images can be combined into one document.
            </div>

            <div class="row mb-3">
                <div class="col-12">
                    <label class="form-label"><strong>Select Image Files (JPG, PNG)</strong></label>
                    
                    <!-- Drag and Drop Upload Area -->
                    <div class="file-upload-area" id="fileUploadArea">
                        <div class="file-upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="file-upload-text">
                            <strong>Drop your images here</strong><br>
                            or click to browse
                        </div>
                        <div class="file-upload-text-small">
                            Supported: JPG, PNG (Max 20MB per file)
                        </div>
                    </div>

                    <!-- Hidden File Input -->
                    <input type="file" class="form-control" id="imageFile" name="image_files" accept="image/*" multiple>
                </div>
            </div>
        </div>

        <!-- GALLERY TAB -->
        <div id="gallery" class="tab-content">
            <div class="form-section-title">
                <i class="fas fa-images"></i>Captured/Uploaded Images
            </div>

            <div id="imageGallery" class="image-preview">
                <div class="no-images-message">
                    <i class="fas fa-image"></i>
                    <p>No images captured or uploaded yet. Start by using Camera Capture or Upload Images.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="form-container">
        <div class="button-group">
            <a href="admin_add_thesis_page.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Add Thesis
            </a>
            <button type="button" class="btn btn-success" onclick="downloadAsImageSet()" id="downloadBtn" style="display: none;">
                <i class="fas fa-download me-2"></i>Download Images as ZIP
            </button>
            <button type="button" class="btn btn-primary" onclick="processAndReturn()" id="processBtn" style="display: none;">
                <i class="fas fa-check me-2"></i>Process Images
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ============================================
// GLOBAL STATE
// ============================================
let capturedImages = []; // Array to store captured image data
let cameraStream = null;
let currentTab = 'camera';
let cameraActive = false;

// ============================================
// ALERT MANAGEMENT
// ============================================
function showAlert(message, type = 'info') {
    const alertBox = document.getElementById('alertMessage');
    alertBox.innerHTML = message;
    alertBox.className = 'alert-message alert-' + type;
    alertBox.style.display = 'block';
    
    if (type !== 'danger') {
        setTimeout(() => {
            alertBox.style.display = 'none';
        }, 4000);
    }
}

// ============================================
// TAB SWITCHING
// ============================================
function switchTab(tabName) {
    // Hide all tabs
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    // Remove active class from all buttons
    const buttons = document.querySelectorAll('.tab-btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    
    // Show selected tab
    document.getElementById(tabName).classList.add('active');
    
    // Add active class to clicked button
    event.target.closest('.tab-btn').classList.add('active');
    
    currentTab = tabName;
    
    // Initialize camera when switching to camera tab
    if (tabName === 'camera' && !cameraActive) {
        initializeCamera();
    }
    
    // Update gallery when switching to gallery tab
    if (tabName === 'gallery') {
        updateGallery();
    }
}

// ============================================
// CAMERA FUNCTIONS
// ============================================
async function initializeCamera() {
    try {
        const video = document.getElementById('cameraStream');
        
        // Request camera access
        cameraStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' }, // Use rear camera on mobile
            audio: false
        });
        
        video.srcObject = cameraStream;
        cameraActive = true;
        
        document.getElementById('stopCameraBtn').style.display = 'inline-flex';
        showAlert('✅ Camera initialized successfully', 'success');
        
    } catch (error) {
        console.error('Camera error:', error);
        
        if (error.name === 'NotAllowedError') {
            showAlert('❌ Camera permission denied. Please allow camera access in your browser settings.', 'danger');
        } else if (error.name === 'NotFoundError') {
            showAlert('❌ No camera found on this device.', 'danger');
        } else {
            showAlert('❌ Error accessing camera: ' + error.message, 'danger');
        }
    }
}

function stopCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
        cameraActive = false;
    }
    
    document.getElementById('stopCameraBtn').style.display = 'none';
    showAlert('📹 Camera stopped', 'info');
}

function captureImage() {
    if (!cameraActive) {
        showAlert('❌ Camera is not active', 'danger');
        return;
    }
    
    const video = document.getElementById('cameraStream');
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    const context = canvas.getContext('2d');
    context.drawImage(video, 0, 0);
    
    // Convert canvas to blob and store
    canvas.toBlob(blob => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const imageData = {
                id: Date.now(),
                src: e.target.result,
                blob: blob,
                timestamp: new Date().toLocaleString()
            };
            
            capturedImages.push(imageData);
            showAlert(`✅ Image captured! (${capturedImages.length} total)`, 'success');
            
            // Update button visibility
            updateButtonVisibility();
        };
        reader.readAsDataURL(blob);
    });
}

// ============================================
// FILE UPLOAD FUNCTIONS
// ============================================
function setupFileUpload() {
    const uploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('imageFile');
    
    // Click to browse
    uploadArea.addEventListener('click', () => fileInput.click());
    
    // Drag and drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('drag-over');
    });
    
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('drag-over');
    });
    
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('drag-over');
        handleFiles(e.dataTransfer.files);
    });
    
    // File input change
    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });
}

function handleFiles(files) {
    Array.from(files).forEach(file => {
        if (!file.type.startsWith('image/')) {
            showAlert('⚠️ Only image files are supported', 'danger');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = (e) => {
            const imageData = {
                id: Date.now() + Math.random(),
                src: e.target.result,
                blob: file,
                timestamp: new Date().toLocaleString(),
                fileName: file.name
            };
            
            capturedImages.push(imageData);
            updateButtonVisibility();
            showAlert(`✅ ${file.name} added (${capturedImages.length} total)`, 'success');
        };
        reader.readAsDataURL(file);
    });
}

// ============================================
// GALLERY FUNCTIONS
// ============================================
function updateGallery() {
    const gallery = document.getElementById('imageGallery');
    
    if (capturedImages.length === 0) {
        gallery.innerHTML = `
            <div class="no-images-message">
                <i class="fas fa-image"></i>
                <p>No images captured or uploaded yet. Start by using Camera Capture or Upload Images.</p>
            </div>
        `;
        return;
    }
    
    let html = `<p style="color: var(--text-gray); margin-bottom: 1rem;">Total images: ${capturedImages.length}</p>`;
    html += '<div class="preview-grid">';
    
    capturedImages.forEach(image => {
        html += `
            <div class="preview-item">
                <img src="${image.src}" alt="Captured image">
                <button type="button" class="delete-btn" onclick="deleteImage(${image.id})" title="Delete image">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        `;
    });
    
    html += '</div>';
    gallery.innerHTML = html;
}

function deleteImage(imageId) {
    capturedImages = capturedImages.filter(img => img.id !== imageId);
    showAlert('🗑️ Image deleted', 'info');
    updateGallery();
    updateButtonVisibility();
}

// ============================================
// BUTTON MANAGEMENT
// ============================================
function updateButtonVisibility() {
    const downloadBtn = document.getElementById('downloadBtn');
    const processBtn = document.getElementById('processBtn');
    
    if (capturedImages.length > 0) {
        downloadBtn.style.display = 'inline-flex';
        processBtn.style.display = 'inline-flex';
    } else {
        downloadBtn.style.display = 'none';
        processBtn.style.display = 'none';
    }
}

// ============================================
// ACTION FUNCTIONS
// ============================================
function downloadAsImageSet() {
    if (capturedImages.length === 0) {
        showAlert('❌ No images to download', 'danger');
        return;
    }
    
    showAlert('📦 Preparing download...', 'info');
    
    // For now, trigger download of first image as example
    // In production, you might use JSZip library to create actual ZIP
    const firstImage = capturedImages[0];
    const link = document.createElement('a');
    link.href = firstImage.src;
    link.download = `thesis_document_${Date.now()}.png`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showAlert('✅ Download started!', 'success');
}

function processAndReturn() {
    if (capturedImages.length === 0) {
        showAlert('❌ No images to process', 'danger');
        return;
    }
    
    // Store images in sessionStorage for next page
    sessionStorage.setItem('scannedImages', JSON.stringify(
        capturedImages.map(img => ({
            id: img.id,
            src: img.src,
            fileName: img.fileName || `image_${img.id}.png`,
            timestamp: img.timestamp
        }))
    ));
    
    showAlert('✅ Images saved! Redirecting...', 'success');
    
    // Redirect back to add thesis page
    setTimeout(() => {
        window.location.href = 'admin_add_thesis_page.php';
    }, 1500);
}

// ============================================
// LOGOUT HANDLER
// ============================================
function handleLogout(event) {
    event.preventDefault();
    
    if (confirm('Are you sure you want to logout?')) {
        fetch('../db_includes/logout.php', {
            method: 'POST'
        })
        .then(() => {
            window.location.href = '../index.php';
        });
    }
}

// ============================================
// INITIALIZATION
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    setupFileUpload();
    
    // Initialize camera when page loads
    initializeCamera();
    
    // Cleanup camera on page unload
    window.addEventListener('beforeunload', () => {
        stopCamera();
    });
});
</script>

</body>
</html>
