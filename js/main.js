// Form Validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });

    return isValid;
}

// Dynamic Menu Item Stock Update
function updateStockStatus(menuItemId, status) {
    const stockElement = document.getElementById(`stock-${menuItemId}`);
    if (stockElement) {
        stockElement.textContent = status ? 'In Stock' : 'Out of Stock';
        stockElement.className = status ? 'text-success' : 'text-danger';
    }
}

// Order Total Calculator
function calculateOrderTotal() {
    const items = document.querySelectorAll('.order-item');
    let total = 0;

    items.forEach(item => {
        const price = parseFloat(item.dataset.price) || 0;
        const quantity = parseInt(item.querySelector('.quantity-input')?.value) || 0;
        total += price * quantity;
    });

    const totalElement = document.getElementById('order-total');
    if (totalElement) {
        totalElement.textContent = total.toFixed(2);
    }
}

// Initialize Event Listeners
// Filter tables based on number of guests
function filterTables() {
    const guestsInput = document.querySelector('input[name="num_guests"]');
    const tableSelect = document.getElementById('tableSelect');
    
    if (!guestsInput || !tableSelect) return;
    
    guestsInput.addEventListener('change', function() {
        const numGuests = parseInt(this.value) || 0;
        const options = tableSelect.getElementsByTagName('option');
        
        for (let option of options) {
            if (option.value === '') continue; // Skip the placeholder option
            
            const seats = parseInt(option.dataset.seats) || 0;
            option.disabled = seats < numGuests;
            
            if (option.disabled && option.selected) {
                tableSelect.value = ''; // Reset selection if current table is too small
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize table filtering
    filterTables();
    
    // Existing event listeners
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this.id)) {
                e.preventDefault();
            }
        });
    });

    // Quantity change listeners
    const quantityInputs = document.querySelectorAll('.quantity-input');
    quantityInputs.forEach(input => {
        input.addEventListener('change', calculateOrderTotal);
    });
});
