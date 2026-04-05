/**
 * AI Thesis Classification - Admin Integration
 * Handles classification workflow for thesis uploads
 */

console.log('✅ thesis_classification_ui.js loaded successfully!');

// Store thesis data for classification workflow
let currentThesisData = null;
let currentClassification = null;

/**
 * Initialize classification UI after successful thesis upload
 */
function initializeClassificationWorkflow(thesisId, thesisTitle) {
    console.log('🚀 initializeClassificationWorkflow called with ID:', thesisId, 'Title:', thesisTitle);
    currentThesisData = { thesisId, thesisTitle };
    
    try {
        // Hide form, show classification section
        const form = document.getElementById('addThesisForm');
        const section = document.getElementById('classificationSection');
        const title = document.getElementById('classificationThesisTitle');
        const status = document.getElementById('classificationStatus');
        const results = document.getElementById('classificationResults');
        const editForm = document.getElementById('classificationForm');
        
        console.log('📋 Found elements - form:', !!form, 'section:', !!section, 'title:', !!title);
        
        form.style.display = 'none';
        console.log('✅ Form hidden');
        
        section.style.display = 'block';
        console.log('✅ Classification section shown');
        
        title.textContent = thesisTitle;
        console.log('✅ Title updated');
        
        // Reset UI
        status.innerHTML = '<p class="text-muted">Ready to generate classification. Click "Generate Classification" to begin.</p>';
        console.log('✅ Status reset');
        
        results.style.display = 'none';
        editForm.style.display = 'none';
        console.log('✅ Results and form hidden');
        
        // Switch button visibility in footer
        document.getElementById('cancelBtn').style.display = 'none';
        document.getElementById('addThesisBtn').style.display = 'none';
        document.getElementById('skipClassificationBtn').style.display = 'block';
        document.getElementById('generateClassificationBtn').style.display = 'block';
        document.getElementById('saveClassificationBtn').style.display = 'none';
        console.log('✅ Buttons switched');
        
        console.log('🎉 Workflow initialization complete!');
    } catch (error) {
        console.error('❌ ERROR in initializeClassificationWorkflow:', error);
        console.error('Error details:', error.stack);
    }
}

/**
 * Generate AI classification for the thesis
 */
function generateClassification() {
    console.log('🧠 generateClassification called for thesis:', currentThesisData);
    
    const generateBtn = document.getElementById('generateClassificationBtn');
    const statusDiv = document.getElementById('classificationStatus');
    
    console.log('📌 Found button:', !!generateBtn, 'status div:', !!statusDiv);
    
    generateBtn.disabled = true;
    generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating...';
    statusDiv.innerHTML = '<p class="text-info"><i class="fas fa-hourglass-half me-2"></i>Analyzing thesis... This may take 10-30 seconds.</p>';
    
    console.log('📤 Sending POST to generate_classification.php with thesis_id:', currentThesisData.thesisId);
    
    const formData = new FormData();
    formData.append('thesis_id', currentThesisData.thesisId);
    
    fetch('generate_classification.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('📥 Response received, status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('✅ Response parsed:', data);
        generateBtn.disabled = false;
        generateBtn.innerHTML = '<i class="fas fa-brain me-2"></i>Generate Classification';
        
        if (data.success) {
            console.log('🎉 Classification successful!');
            currentClassification = data.classification;
            displayClassificationResults(data.classification);
            statusDiv.innerHTML = '<p class="text-success"><i class="fas fa-check-circle me-2"></i>Classification generated successfully. Review below and edit if needed.</p>';
        } else {
            console.error('❌ Classification failed:', data.error);
            statusDiv.innerHTML = '<p class="text-danger"><i class="fas fa-exclamation-circle me-2"></i>Error: ' + data.error + '</p>';
        }
    })
    .catch(error => {
        console.error('❌ Fetch error:', error);
        generateBtn.disabled = false;
        generateBtn.innerHTML = '<i class="fas fa-brain me-2"></i>Generate Classification';
        statusDiv.innerHTML = '<p class="text-danger"><i class="fas fa-exclamation-circle me-2"></i>Error: ' + error.message + '</p>';
    });
}

/**
 * Display classification results in editable form
 */
function displayClassificationResults(classification) {
    const resultsDiv = document.getElementById('classificationResults');
    const formDiv = document.getElementById('classificationForm');
    
    // Show results summary
    let resultsHTML = `
        <div class="classification-summary">
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="classification-card">
                        <strong>Subject Category:</strong>
                        <p class="text-primary">${classification.subject?.category || 'N/A'}</p>
                        <strong>Confidence:</strong>
                        <div class="progress">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: ${classification.subject?.confidence || 0}%">
                                ${(classification.subject?.confidence || 0).toFixed(1)}%
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="classification-card">
                        <strong>Complexity Level:</strong>
                        <p class="text-info">${classification.complexity?.level || 'N/A'}</p>
                        <strong>Confidence:</strong>
                        <div class="progress">
                            <div class="progress-bar bg-info" role="progressbar" 
                                 style="width: ${classification.complexity?.confidence || 0}%">
                                ${(classification.complexity?.confidence || 0).toFixed(1)}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="classification-card">
                        <strong>Research Method:</strong>
                        <p class="text-warning">${classification.research_method?.method || 'Not Identified'}</p>
                        <strong>Confidence:</strong>
                        <div class="progress">
                            <div class="progress-bar bg-warning" role="progressbar" 
                                 style="width: ${classification.research_method?.confidence || 0}%">
                                ${(classification.research_method?.confidence || 0).toFixed(1)}%
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="classification-card">
                        <strong>Keywords:</strong>
                        <div id="keywordsList" style="margin-top: 0.5rem;">
    `;
    
    if (classification.keywords && Array.isArray(classification.keywords)) {
        classification.keywords.slice(0, 5).forEach((kw, idx) => {
            const keyword = kw.keyword || kw;
            const relevance = kw.relevance || 0;
            resultsHTML += `
                <span class="badge bg-light text-dark me-2 mb-1" title="Relevance: ${relevance}%">
                    ${keyword}
                </span>
            `;
        });
    }
    
    resultsHTML += `
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    resultsDiv.innerHTML = resultsHTML;
    resultsDiv.style.display = 'block';
    
    // Populate edit form
    populateClassificationForm(classification);
    formDiv.style.display = 'block';
    
    // Show save button in footer
    document.getElementById('generateClassificationBtn').style.display = 'none';
    document.getElementById('saveClassificationBtn').style.display = 'block';
}

/**
 * Populate classification edit form
 */
function populateClassificationForm(classification) {
    document.getElementById('classifSubjectCategory').value = classification.subject?.category || '';
    document.getElementById('classifSubjectConfidence').value = (classification.subject?.confidence || 0).toFixed(2);
    document.getElementById('classifResearchMethod').value = classification.research_method?.method || '';
    document.getElementById('classifMethodConfidence').value = (classification.research_method?.confidence || 0).toFixed(2);
    document.getElementById('classifComplexityLevel').value = classification.complexity?.level || 'intermediate';
    document.getElementById('classifComplexityConfidence').value = (classification.complexity?.confidence || 0).toFixed(2);
    
    // Keywords as comma-separated
    let keywordString = '';
    if (classification.keywords && Array.isArray(classification.keywords)) {
        keywordString = classification.keywords.map(kw => kw.keyword || kw).join(', ');
    }
    document.getElementById('classifKeywords').value = keywordString;
    
    // Citations
    document.getElementById('classifCitations').value = JSON.stringify(classification.citations || [], null, 2);
    document.getElementById('classifRelatedTheses').value = JSON.stringify(classification.related_theses || [], null, 2);
}

/**
 * Save classification (either auto-generated or manually edited)
 */
function saveClassification() {
    const saveBtn = document.getElementById('saveClassificationBtn');
    const statusDiv = document.getElementById('classificationStatus');
    
    // Collect form data
    const subjectCategory = document.getElementById('classifSubjectCategory').value;
    const subjectConfidence = parseFloat(document.getElementById('classifSubjectConfidence').value) || 0;
    const researchMethod = document.getElementById('classifResearchMethod').value;
    const methodConfidence = parseFloat(document.getElementById('classifMethodConfidence').value) || 0;
    const complexityLevel = document.getElementById('classifComplexityLevel').value;
    const complexityConfidence = parseFloat(document.getElementById('classifComplexityConfidence').value) || 0;
    
    // Parse keywords (convert from comma-separated to array)
    const keywordsText = document.getElementById('classifKeywords').value;
    const keywords = keywordsText.split(',').map(kw => ({
        keyword: kw.trim(),
        relevance: 85
    })).filter(kw => kw.keyword);
    
    // Parse JSON fields
    let citations = [];
    let relatedTheses = [];
    
    try {
        const citText = document.getElementById('classifCitations').value.trim();
        if (citText) {
            citations = JSON.parse(citText);
        }
    } catch(e) {
        citations = [];
    }
    
    try {
        const relText = document.getElementById('classifRelatedTheses').value.trim();
        if (relText) {
            relatedTheses = JSON.parse(relText);
        }
    } catch(e) {
        relatedTheses = [];
    }
    
    // Validate required fields
    if (!subjectCategory) {
        alert('Please enter subject category');
        return;
    }
    
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
    statusDiv.innerHTML = '<p class="text-info">Saving classification...</p>';
    
    const formData = new FormData();
    formData.append('thesis_id', currentThesisData.thesisId);
    formData.append('subject_category', subjectCategory);
    formData.append('subject_confidence', subjectConfidence);
    formData.append('keywords', JSON.stringify(keywords));
    formData.append('research_method', researchMethod);
    formData.append('method_confidence', methodConfidence);
    formData.append('complexity_level', complexityLevel);
    formData.append('complexity_confidence', complexityConfidence);
    formData.append('citations', JSON.stringify(citations));
    formData.append('related_thesis_ids', JSON.stringify(relatedTheses));
    
    fetch('save_classification.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>Save Classification';
        
        if (data.success) {
            statusDiv.innerHTML = '<p class="text-success"><i class="fas fa-check-circle me-2"></i>' + data.message + '</p>';
            
            // Show success message and close after 2 seconds
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('addThesisModal'));
                modal.hide();
                location.reload();
            }, 2000);
        } else {
            statusDiv.innerHTML = '<p class="text-danger"><i class="fas fa-exclamation-circle me-2"></i>Error: ' + data.error + '</p>';
        }
    })
    .catch(error => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>Save Classification';
        statusDiv.innerHTML = '<p class="text-danger"><i class="fas fa-exclamation-circle me-2"></i>Error: ' + error.message + '</p>';
    });
}

/**
 * Close classification workflow and go back
 */
function closeClassificationWorkflow() {
    currentThesisData = null;
    currentClassification = null;
    document.getElementById('addThesisForm').style.display = 'block';
    document.getElementById('classificationSection').style.display = 'none';
    document.getElementById('addThesisForm').reset();
    document.getElementById('addThesismessage').style.display = 'none';
}

/**
 * Skip classification (save thesis without classification)
 */
function skipClassification() {
    if (confirm('Close upload dialog without saving classification?')) {
        const modal = bootstrap.Modal.getInstance(document.getElementById('addThesisModal'));
        modal.hide();
        location.reload();
    }
}
