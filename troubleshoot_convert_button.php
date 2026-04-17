<?php
/**
 * Troubleshooting: Check Convert Button Status
 * This will help diagnose why the Convert button isn't working
 */

echo "<h2>Troubleshoot Convert to IMRaD Button</h2>";
echo "<p>Copy and paste this into your browser console (F12 → Console tab) and run it:</p>";
echo "<pre style='background: #222; color: #0f0; padding: 15px; border-radius: 5px; font-family: monospace;'>";
echo "// Check 1: Is window.currentThesisId set?
console.log('window.currentThesisId:', window.currentThesisId);
console.log('window.currentThesisFilePath:', window.currentThesisFilePath);

// Check 2: Is the button element found?
const btn = document.getElementById('convertToIMRaDBtn');
console.log('Button found:', !!btn);
console.log('Button disabled:', btn?.disabled);

// Check 3: Get button state
if (btn) {
    console.log('Button HTML:', btn.innerHTML);
    console.log('Button class:', btn.className);
}

// Check 4: Try to see form values
console.log('Title:', document.getElementById('thesisTitle')?.value);
console.log('File path field:', document.getElementById('filePath')?.value);

// Check 5: Test the function directly
console.log('convertToIMRaD function exists:', typeof convertToIMRaD === 'function');

// Then try clicking the button manually:
console.log('Manually triggering click...');
if (btn) {
    btn.click();
}
";
echo "</pre>";

echo "<h3>What to do:</h3>";
echo "<ol>";
echo "<li><strong>First, save the thesis</strong> by clicking 'Save Thesis & Classification' button</li>";
echo "<li>Wait for the success message (should say thesis was saved with ID)</li>";
echo "<li>The 'Convert to IMRaD' button should become enabled (turn green)</li>";
echo "<li>Then click 'Convert to IMRaD (Phase 2)'</li>";
echo "<li>If it still doesn't work, open browser console (F12) and paste the code above</li>";
echo "<li>Share the console output so I can see what's happening</li>";
echo "</ol>";

echo "<h3>Common Issues:</h3>";
echo "<ul>";
echo "<li><strong>Button is still disabled?</strong> → You haven't saved the thesis yet. Click 'Save Thesis & Classification' first.</li>";
echo "<li><strong>Button doesn't respond?</strong> → Check browser console (F12) for JavaScript errors</li>";
echo "<li><strong>Shows error about thesis_id?</strong> → The thesis wasn't saved properly. Try saving again.</li>";
echo "</ul>";
?>
