// Create On Search Select Component JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize create on search select components
    initializeCreateOnSearchSelect();
});

function initializeCreateOnSearchSelect() {
    const components = document.querySelectorAll('[data-create-on-search-select]');

    components.forEach(component => {
        setupCreateOnSearchSelect(component);
    });
}

function setupCreateOnSearchSelect(component) {
    const select = component.querySelector('select');
    const createButton = component.querySelector('.create-on-search-select__create-button');

    if (!select || !createButton) return;

    // Handle create button click
    createButton.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        openCreateModal(component);
    });

    // Handle select search functionality
    if (select.hasAttribute('data-searchable')) {
        setupSearchFunctionality(select);
    }
}

function openCreateModal(component) {
    const modal = component.querySelector('.create-on-search-select__modal');
    if (!modal) return;

    // Show modal
    modal.style.display = 'flex';
    modal.classList.add('create-on-search-select__fade-enter-active');

    const modalContent = modal.querySelector('.create-on-search-select__modal-content');
    if (modalContent) {
        modalContent.classList.add('create-on-search-select__scale-enter-active');
    }

    // Focus first input
    const firstInput = modal.querySelector('input, textarea, select');
    if (firstInput) {
        setTimeout(() => firstInput.focus(), 100);
    }

    // Setup modal event listeners
    setupModalEventListeners(component, modal);
}

function closeCreateModal(component) {
    const modal = component.querySelector('.create-on-search-select__modal');
    if (!modal) return;

    const modalContent = modal.querySelector('.create-on-search-select__modal-content');

    // Add exit animations
    modal.classList.remove('create-on-search-select__fade-enter-active');
    modal.classList.add('create-on-search-select__fade-exit-active');

    if (modalContent) {
        modalContent.classList.remove('create-on-search-select__scale-enter-active');
        modalContent.classList.add('create-on-search-select__scale-exit-active');
    }

    // Hide modal after animation
    setTimeout(() => {
        modal.style.display = 'none';
        modal.classList.remove('create-on-search-select__fade-exit-active');
        if (modalContent) {
            modalContent.classList.remove('create-on-search-select__scale-exit-active');
        }

        // Clear form data
        clearModalForm(modal);
    }, 150);
}

function setupModalEventListeners(component, modal) {
    const cancelButton = modal.querySelector('[data-action="cancel"]');
    const submitButton = modal.querySelector('[data-action="submit"]');
    const modalContent = modal.querySelector('.create-on-search-select__modal-content');

    // Cancel button
    if (cancelButton) {
        cancelButton.addEventListener('click', () => closeCreateModal(component));
    }

    // Submit button
    if (submitButton) {
        submitButton.addEventListener('click', () => handleCreateSubmit(component));
    }

    // Click outside to close
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeCreateModal(component);
        }
    });

    // Prevent modal content clicks from closing modal
    if (modalContent) {
        modalContent.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }

    // Escape key to close
    document.addEventListener('keydown', function escapeHandler(e) {
        if (e.key === 'Escape') {
            closeCreateModal(component);
            document.removeEventListener('keydown', escapeHandler);
        }
    });
}

function handleCreateSubmit(component) {
    const modal = component.querySelector('.create-on-search-select__modal');
    const form = modal.querySelector('form') || modal;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    // Get data from inputs if no form
    if (!modal.querySelector('form')) {
        const inputs = modal.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            if (input.name || input.id) {
                const key = input.name || input.id;
                data[key] = input.value;
            }
        });
    }

    // Trigger create option event
    const createEvent = new CustomEvent('createOption', {
        detail: { data },
        bubbles: true
    });

    component.dispatchEvent(createEvent);
}

function addOptionToSelect(select, option) {
    const optionElement = document.createElement('option');
    optionElement.value = option.value;
    optionElement.textContent = option.label;
    optionElement.selected = true;

    select.appendChild(optionElement);

    // Trigger change event
    const changeEvent = new Event('change', { bubbles: true });
    select.dispatchEvent(changeEvent);
}

function clearModalForm(modal) {
    const inputs = modal.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        if (input.type === 'checkbox' || input.type === 'radio') {
            input.checked = false;
        } else {
            input.value = '';
        }
    });
}

// Export functions for external use
window.CreateOnSearchSelect = {
    initialize: initializeCreateOnSearchSelect,
    openModal: openCreateModal,
    closeModal: closeCreateModal,
    addOption: addOptionToSelect
};
