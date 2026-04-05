// Sidebar
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const closeSidebar = document.getElementById('closeSidebar');

if (menuToggle && sidebar && overlay) {
  menuToggle.addEventListener('click', () => {
    sidebar.classList.add('active');
    overlay.style.display = 'block';
  });
}

if (closeSidebar && sidebar && overlay) {
  closeSidebar.addEventListener('click', () => {
    sidebar.classList.remove('active');
    overlay.style.display = 'none';
  });
}

if (overlay && sidebar) {
  overlay.addEventListener('click', () => {
    sidebar.classList.remove('active');
    overlay.style.display = 'none';
  });
}

// Modal
const authModal = document.getElementById('authModal');
const closeModal = document.getElementById('closeModal');
const loginForm = document.getElementById('loginForm');
const signupForm = document.getElementById('signupForm');
const loginTab = document.getElementById('loginTab');
const signupTab = document.getElementById('signupTab');

// Helper function to update sidebar content dynamically on successful AJAX login
function updateSidebar(name) {
  const sidebarFooter = document.querySelector('.sidebar-footer');
  
  // 1. Hide the Logged Out button state (and remove it to prevent conflicts)
  const outState = document.getElementById('loggedOutState');
  if (outState) outState.remove();

  // 2. Insert or update the Logged In state
  let inState = document.getElementById('loggedInState');

  if (!inState) {
    // If it doesn't exist (user was logged out when page loaded), create and insert it
    const newHtml = `
      <div id="loggedInState" class="text-white px-3">
          <p class="mb-2 fw-bold">Welcome, ${name}!</p>
          <a href="logout.php" id="logoutBtn" class="btn btn-danger w-75 fw-semibold">Logout</a>
      </div>
    `;
    sidebarFooter.insertAdjacentHTML('afterbegin', newHtml);
  } else {
    // If it exists (just updating the name after subsequent logins without a reload, though rare)
    inState.querySelector('p').innerHTML = `Welcome, ${name}!`;
  }
}

// ===== FORM HANDLERS =====

// Login Form Handler
if (loginForm && !window.USE_CUSTOM_LOGIN_HANDLER) {
  // Only bind the script.js login handler if a custom one isn't defined on the page
  loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // 1. Clear previous error message (if any)
    const existingError = document.querySelector('.alert-error');
    if(existingError) existingError.remove();

    const formData = new FormData(loginForm);

    try {
      // 2. Send data to the handler
      const response = await fetch('login_handler.php', {
        method: 'POST',
        body: formData
      });
      const result = await response.json();

      if (result.success) {
        // 3. Successful Login: Hide modal and update sidebar
        authModal.style.display = 'none';
        updateSidebar(result.full_name);
        
      } else {
        // 4. Failed Login: Display error and keep modal open
        const alertHtml = `
            <div class="alert-error text-center mb-3">
                ${result.message}
            </div>
        `;
        // Insert the error above the tabs in the modal
        const authTabs = document.getElementById('authTabs');
        authTabs.insertAdjacentHTML('beforebegin', alertHtml); 
      }
    } catch (error) {
      console.error('Error during AJAX login:', error);
      // Fallback error display
      const fallbackAlert = `
            <div class="alert-error text-center mb-3">
                A network or server error occurred. Please try again.
            </div>
        `;
        const authTabs = document.getElementById('authTabs');
        authTabs.insertAdjacentHTML('beforebegin', fallbackAlert); 
    }
  });
}

// Modal display logic (using delegated event listener for 'loginBtn')
document.addEventListener('click', (e) => {
  // Check if the clicked element (or its ancestor) is the loginBtn (which is inside the sidebar)
  if (e.target && e.target.id === 'loginBtn' && authModal) {
    authModal.style.display = 'flex';
    // Close sidebar when opening modal
    if (sidebar) sidebar.classList.remove('active');
    if (overlay) overlay.style.display = 'none';
  }
});

if (closeModal) {
  closeModal.addEventListener('click', () => {
    authModal.style.display = 'none';
  });
}

if (loginTab) {
  loginTab.addEventListener('click', (e) => {
    e.preventDefault();
    // Remove existing error message when switching tabs
    const existingError = document.querySelector('.alert-error');
    if(existingError) existingError.remove();
    
    loginForm.classList.add('active');
    signupForm.classList.remove('active');
    loginTab.classList.add('active');
    signupTab.classList.remove('active');
  });
}

if (signupTab) {
  signupTab.addEventListener('click', (e) => {
    e.preventDefault();
    // Remove existing error message when switching tabs
    const existingError = document.querySelector('.alert-error');
    if(existingError) existingError.remove();
    
    signupForm.classList.add('active');
    loginForm.classList.remove('active');
    signupTab.classList.add('active');
    loginTab.classList.remove('active');
    
    // CONSOLIDATED SCROLL FIX: Scroll signup form into view
    setTimeout(() => {
      signupForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 100);
  });
}

// Close modal when clicking outside
window.addEventListener('click', (e) => {
  if (e.target === authModal) authModal.style.display = 'none';
});

// Show login/signup modal when "View Thesis" is clicked (if not logged in)
document.querySelectorAll('.view-thesis-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    // Check if the loggedInState element is present on the page
    const inState = document.getElementById('loggedInState');
    
    // Only show modal if the loggedInState element does NOT exist
    if (!inState) {
        authModal.style.display = 'flex';
    } else {
        // User is logged in: Proceed to view thesis (This is a future feature)
        console.log('User is logged in. Proceed to view thesis details.');
        // TODO: In the future, this will redirect to view_thesis.php
    }
  });
});

// ----- Shared Animation Helpers -----
/**
 * Animate the auth modal on success (pulse) then clear animation class after done
 * @param {HTMLElement} modalEl - The modal content element
 */
function animateModalSuccess(modalEl) {
  if (!modalEl) return;
  modalEl.classList.add('login-success');
  setTimeout(() => modalEl.classList.remove('login-success'), 700);
}

/**
 * Animate the auth modal with a shake on error
 * @param {HTMLElement} modalEl - The modal content element
 */
function animateModalError(modalEl) {
  if (!modalEl) return;
  modalEl.classList.add('shake');
  setTimeout(() => modalEl.classList.remove('shake'), 450);
}

/**
 * Handle logout: play a logout fade animation and then redirect
 * @param {MouseEvent} event
 */
function handleLogout(event) {
  if (event) event.preventDefault();
  // Add class to body for fade animation
  document.body.classList.add('logout-animation');

  // If the logout link contains a spinner, add it
  let target = event && event.target ? event.target.closest('.logout') : null;
  if (target) {
    const orig = target.innerHTML;
    target.dataset._orig = orig;
    target.innerHTML = '<i class="fas fa-spinner"></i> Logging out...';
  }

  // Wait a short time for the animation, then go to logout script
  setTimeout(() => {
    window.location.href = 'client_includes/logout.php';
  }, 380);
}

// Export helper to global scope for inline onclick handlers
window.animateModalSuccess = animateModalSuccess;
window.animateModalError = animateModalError;
// Fallback open/close modal functions for pages that don't have the modal markup
function openAuthModal(event) {
  if (event && event.preventDefault) event.preventDefault();
  const overlayEl = document.getElementById('authModalOverlay');
  if (overlayEl) {
    overlayEl.classList.add('active');
    overlayEl.classList.remove('closing');
  } else {
    // If no modal exists on this page, go to homepage where the modal exists
    window.location.href = 'index.php#login';
  }
}

function closeAuthModal() {
  const overlayEl = document.getElementById('authModalOverlay');
  if (!overlayEl) return;
  overlayEl.classList.add('closing');
  setTimeout(() => {
    overlayEl.classList.remove('active');
    overlayEl.classList.remove('closing');
  }, 300);
}

// export to global scope
window.openAuthModal = openAuthModal;
window.closeAuthModal = closeAuthModal;
window.handleLogout = handleLogout;

