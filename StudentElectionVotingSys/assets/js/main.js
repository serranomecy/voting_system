// Student Election Voting System - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initModals();
    initForms();
    initVoting();
    initAlerts();
});

// Modal functionality
function initModals() {
    const modals = document.querySelectorAll('.modal');
    const closeButtons = document.querySelectorAll('.close');
    
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            modal.style.display = 'none';
        });
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        modals.forEach(modal => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
}

// Form validation and enhancement
function initForms() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#dc3545';
                } else {
                    field.style.borderColor = '#e1e5e9';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showAlert('Please fill in all required fields', 'error');
            }
        });
    });
}

// Voting interface functionality
function initVoting() {
    const candidateCards = document.querySelectorAll('.candidate-card');
    let selectedCandidate = null;
    
    candidateCards.forEach(card => {
        card.addEventListener('click', function() {
            // Remove previous selection
            candidateCards.forEach(c => c.classList.remove('selected'));
            
            // Add selection to clicked card
            this.classList.add('selected');
            selectedCandidate = this.dataset.candidateId;
            
            // Update hidden input if exists
            const hiddenInput = document.querySelector('input[name="candidate_id"]');
            if (hiddenInput) {
                hiddenInput.value = selectedCandidate;
            }
        });
    });
}

// Alert system
function initAlerts() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
}

// Utility functions
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    
    const icon = type === 'error' ? 'fas fa-exclamation-circle' : 
                 type === 'success' ? 'fas fa-check-circle' : 
                 'fas fa-info-circle';
    
    alertDiv.innerHTML = `
        <i class="${icon}"></i>
        ${message}
    `;
    
    // Insert at the top of main content
    const mainContent = document.querySelector('.main-content') || document.querySelector('.login-card');
    if (mainContent) {
        mainContent.insertBefore(alertDiv, mainContent.firstChild);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            alertDiv.style.opacity = '0';
            setTimeout(() => {
                alertDiv.remove();
            }, 300);
        }, 5000);
    }
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Confirm delete function
function confirmDelete(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// File upload preview
function previewFile(input, previewId) {
    const file = input.files[0];
    const preview = document.getElementById(previewId);
    
    if (file && preview) {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" style="max-width: 200px; max-height: 200px;">`;
            };
            reader.readAsDataURL(file);
        } else if (file.type === 'application/pdf') {
            preview.innerHTML = `<i class="fas fa-file-pdf" style="font-size: 3rem; color: #dc3545;"></i><br>PDF File Selected`;
        }
    }
}

// Loading state for buttons
function setLoading(button, loading = true) {
    if (loading) {
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        button.disabled = true;
    } else {
        button.innerHTML = button.dataset.originalText || 'Submit';
        button.disabled = false;
    }
}

 // Update time every second (live countdown)
        function updateTime() {
            const now = new Date();
            
            // Format date
            const month = now.toLocaleDateString('en-US', { month: 'short' });
            const day = now.toLocaleDateString('en-US', { day: '2-digit' });
            const year = now.getFullYear();
            
            // Format time with seconds
            const hours = String(now.getHours() % 12 || 12).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const ampm = now.getHours() >= 12 ? 'PM' : 'AM';
            
            const timeString = `${month} ${day}, ${year} | ${hours}:${minutes}:${seconds} ${ampm}`;
            
            const timeElement = document.getElementById('currentDateTime');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }
        
        // Update every second (1000ms)
        setInterval(updateTime, 1000);
        
        // Initial time update when page loads
        updateTime();

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showAlert('Copied to clipboard!', 'success');
    }).catch(() => {
        showAlert('Failed to copy to clipboard', 'error');
    });
}

// Search functionality
function initSearch() {
    const searchInput = document.querySelector('.search-input');
    const searchableRows = document.querySelectorAll('.searchable-row');
    
    if (searchInput && searchableRows.length > 0) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            searchableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
}

// Initialize search when DOM is loaded
document.addEventListener('DOMContentLoaded', initSearch);





