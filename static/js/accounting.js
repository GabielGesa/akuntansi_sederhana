// Accounting Application JavaScript
// Client-side functionality and validation

document.addEventListener('DOMContentLoaded', function() {
    initializeApplication();
});

function initializeApplication() {
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize form validations
    initializeFormValidations();
    
    // Initialize currency formatting
    initializeCurrencyFormatting();
    
    // Initialize table enhancements
    initializeTableEnhancements();
    
    // Initialize dashboard animations
    initializeDashboardAnimations();
}

// Tooltip initialization
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Form validation enhancements
function initializeFormValidations() {
    const forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Real-time balance validation for journal forms
    const journalForms = document.querySelectorAll('#journalForm, #adjustingForm');
    journalForms.forEach(function(form) {
        form.addEventListener('input', validateJournalBalance);
    });
}

// Currency formatting
function initializeCurrencyFormatting() {
    const currencyInputs = document.querySelectorAll('input[type="number"][step="0.01"]');
    currencyInputs.forEach(function(input) {
        input.addEventListener('blur', function() {
            if (this.value) {
                const value = parseFloat(this.value);
                this.value = value.toFixed(2);
            }
        });
    });
}

// Table enhancements
function initializeTableEnhancements() {
    // Add hover effects to table rows
    const tables = document.querySelectorAll('.table');
    tables.forEach(function(table) {
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(function(row) {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'var(--bs-tertiary-bg)';
            });
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
    });
    
    // Sort table functionality
    addTableSortFunctionality();
}

// Dashboard animations
function initializeDashboardAnimations() {
    const cards = document.querySelectorAll('.dashboard-card');
    cards.forEach(function(card, index) {
        card.style.animationDelay = (index * 0.1) + 's';
        card.classList.add('animate-fade-in');
    });
}

// Journal balance validation
function validateJournalBalance() {
    const debitInputs = document.querySelectorAll('.debit-input');
    const creditInputs = document.querySelectorAll('.credit-input');
    
    let totalDebit = 0;
    let totalCredit = 0;
    
    debitInputs.forEach(function(input) {
        if (input.value) {
            totalDebit += parseFloat(input.value);
        }
    });
    
    creditInputs.forEach(function(input) {
        if (input.value) {
            totalCredit += parseFloat(input.value);
        }
    });
    
    const totalDebitElement = document.getElementById('totalDebit');
    const totalCreditElement = document.getElementById('totalCredit');
    const balanceAlert = document.getElementById('balanceAlert');
    
    if (totalDebitElement) {
        totalDebitElement.textContent = formatCurrency(totalDebit);
    }
    
    if (totalCreditElement) {
        totalCreditElement.textContent = formatCurrency(totalCredit);
    }
    
    if (balanceAlert) {
        if (Math.abs(totalDebit - totalCredit) < 0.01 && (totalDebit > 0 || totalCredit > 0)) {
            balanceAlert.classList.add('d-none');
        } else if (totalDebit > 0 || totalCredit > 0) {
            balanceAlert.classList.remove('d-none');
        }
    }
    
    // Visual feedback for balance status
    updateBalanceStatus(totalDebit, totalCredit);
}

// Update balance status visual feedback
function updateBalanceStatus(debit, credit) {
    const submitButton = document.querySelector('button[type="submit"]');
    if (submitButton) {
        if (Math.abs(debit - credit) < 0.01 && (debit > 0 || credit > 0)) {
            submitButton.classList.remove('btn-danger');
            submitButton.classList.add('btn-primary');
            submitButton.disabled = false;
        } else if (debit > 0 || credit > 0) {
            submitButton.classList.remove('btn-primary');
            submitButton.classList.add('btn-danger');
            submitButton.disabled = true;
        }
    }
}

// Currency formatting helper
function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
}

// Add table sorting functionality
function addTableSortFunctionality() {
    const sortableHeaders = document.querySelectorAll('.sortable');
    sortableHeaders.forEach(function(header) {
        header.addEventListener('click', function() {
            sortTable(this);
        });
        header.style.cursor = 'pointer';
        header.innerHTML += ' <i class="fas fa-sort text-muted"></i>';
    });
}

// Table sorting function
function sortTable(header) {
    const table = header.closest('table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const columnIndex = Array.from(header.parentNode.children).indexOf(header);
    const isNumeric = header.dataset.type === 'number';
    const currentDirection = header.dataset.direction || 'asc';
    const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
    
    rows.sort(function(a, b) {
        const aValue = a.children[columnIndex].textContent.trim();
        const bValue = b.children[columnIndex].textContent.trim();
        
        let comparison;
        if (isNumeric) {
            comparison = parseFloat(aValue.replace(/[^\d.-]/g, '')) - parseFloat(bValue.replace(/[^\d.-]/g, ''));
        } else {
            comparison = aValue.localeCompare(bValue);
        }
        
        return newDirection === 'asc' ? comparison : -comparison;
    });
    
    // Update DOM
    rows.forEach(function(row) {
        tbody.appendChild(row);
    });
    
    // Update header
    header.dataset.direction = newDirection;
    const icon = header.querySelector('i');
    icon.className = `fas ${newDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'}`;
}

// Confirmation dialogs
function confirmAction(message) {
    return new Promise(function(resolve) {
        if (confirm(message)) {
            resolve(true);
        } else {
            resolve(false);
        }
    });
}

// Loading states
function showLoading(element) {
    element.classList.add('loading');
    const spinner = document.createElement('span');
    spinner.className = 'loading-spinner me-2';
    element.prepend(spinner);
}

function hideLoading(element) {
    element.classList.remove('loading');
    const spinner = element.querySelector('.loading-spinner');
    if (spinner) {
        spinner.remove();
    }
}

// Form auto-save functionality
function initializeAutoSave() {
    const forms = document.querySelectorAll('form[data-autosave]');
    forms.forEach(function(form) {
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(function(input) {
            input.addEventListener('change', function() {
                saveFormData(form);
            });
        });
        
        // Load saved data
        loadFormData(form);
    });
}

function saveFormData(form) {
    const formData = new FormData(form);
    const data = {};
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    localStorage.setItem('form_' + form.id, JSON.stringify(data));
}

function loadFormData(form) {
    const savedData = localStorage.getItem('form_' + form.id);
    if (savedData) {
        const data = JSON.parse(savedData);
        for (let [key, value] of Object.entries(data)) {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) {
                input.value = value;
            }
        }
    }
}

// Print functionality
function printReport() {
    window.print();
}

// Export functionality
function exportToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.querySelectorAll('tr');
    const csvContent = [];
    
    rows.forEach(function(row) {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        cols.forEach(function(col) {
            rowData.push('"' + col.textContent.trim().replace(/"/g, '""') + '"');
        });
        csvContent.push(rowData.join(','));
    });
    
    const blob = new Blob([csvContent.join('\n')], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename || 'export.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(event) {
    // Ctrl + S for save
    if (event.ctrlKey && event.key === 's') {
        event.preventDefault();
        const submitButton = document.querySelector('button[type="submit"]');
        if (submitButton && !submitButton.disabled) {
            submitButton.click();
        }
    }
    
    // Ctrl + P for print
    if (event.ctrlKey && event.key === 'p') {
        event.preventDefault();
        printReport();
    }
    
    // Escape to close modals
    if (event.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(function(modal) {
            bootstrap.Modal.getInstance(modal).hide();
        });
    }
});

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = function() {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Initialize auto-save on load
initializeAutoSave();

// CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .animate-fade-in {
        animation: fadeIn 0.5s ease-out forwards;
    }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
    
    .animate-pulse {
        animation: pulse 2s ease-in-out infinite;
    }
`;
document.head.appendChild(style);

// Error handling
window.addEventListener('error', function(event) {
    console.error('JavaScript error:', event.error);
    // You could show a user-friendly error message here
});

// Page performance monitoring
if ('performance' in window) {
    window.addEventListener('load', function() {
        const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
        console.log('Page load time:', loadTime + 'ms');
    });
}
