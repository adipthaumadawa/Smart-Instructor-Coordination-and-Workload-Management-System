/**
 * Main JavaScript
 * Smart Instructor Coordination and Workload Management System
 */

// Initialize tooltips and popovers when Bootstrap is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto focus first input in forms
    const firstInput = document.querySelector('form input:not([type="hidden"])');
    if (firstInput) {
        // Uncomment if you want auto-focus: firstInput.focus();
    }
    
    // Confirm before delete actions
    document.querySelectorAll('.btn-delete, [data-confirm]').forEach(function(element) {
        element.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure you want to delete this item?';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
});

/**
 * Simple form validation helper
 */
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(function(field) {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

/**
 * Show loading state on button
 */
function showLoading(button) {
    if (!button) return;
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing...';
}

/**
 * Format date helper (for JS)
 */
function formatDateForInput(date) {
    const d = new Date(date);
    let month = '' + (d.getMonth() + 1);
    let day = '' + d.getDate();
    const year = d.getFullYear();
    
    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;
    
    return [year, month, day].join('-');
}