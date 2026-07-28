/**
 * Main JavaScript
 * Smart Instructor Coordination and Workload Management System
 * Framework-free (vanilla JS only)
 */

document.addEventListener('DOMContentLoaded', function () {

  // ----- Confirm before delete -----
  document.querySelectorAll('.btn-delete, [data-confirm]').forEach(function (element) {
    element.addEventListener('click', function (e) {
      var message = this.getAttribute('data-confirm') || 'Are you sure you want to delete this item?';
      if (!confirm(message)) {
        e.preventDefault();
        return false;
      }
    });
  });

  // ----- Dropdown menus (topbar) -----
  document.querySelectorAll('.menu-toggle').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var list = btn.nextElementSibling;
      if (!list || !list.classList.contains('menu-list')) return;

      var isOpen = !list.hidden;

      // Close all menus first
      document.querySelectorAll('.menu-list').forEach(function (el) {
        el.hidden = true;
      });

      list.hidden = isOpen;
    });
  });

  // Close menus when clicking outside
  document.addEventListener('click', function () {
    document.querySelectorAll('.menu-list').forEach(function (el) {
      el.hidden = true;
    });
  });

  // ----- Mobile topbar panel -----
  var toggle = document.getElementById('topbarToggle');
  var panel = document.getElementById('topbarPanel');
  if (toggle && panel) {
    toggle.addEventListener('click', function (e) {
      e.stopPropagation();
      panel.classList.toggle('open');
    });
  }

});

/**
 * Simple form validation helper
 */
function validateForm(formId) {
  var form = document.getElementById(formId);
  if (!form) return true;

  var isValid = true;
  var requiredFields = form.querySelectorAll('[required]');

  requiredFields.forEach(function (field) {
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
 * Show loading state on button (no icon fonts)
 */
function showLoading(button) {
  if (!button) return;
  button.disabled = true;
  button.setAttribute('data-original-html', button.innerHTML);
  button.innerHTML = 'Processing...';
}

/**
 * Restore button after loading (optional)
 */
function hideLoading(button) {
  if (!button) return;
  button.disabled = false;
  var original = button.getAttribute('data-original-html');
  if (original) {
    button.innerHTML = original;
  }
}

/**
 * Format date helper (YYYY-MM-DD for input[type=date])
 */
function formatDateForInput(date) {
  var d = new Date(date);
  var month = '' + (d.getMonth() + 1);
  var day = '' + d.getDate();
  var year = d.getFullYear();

  if (month.length < 2) month = '0' + month;
  if (day.length < 2) day = '0' + day;

  return [year, month, day].join('-');
}