// ==> Create Global Confirmation Modal
const createConfirmationModal = () => {
  // Check if modal already exists
  if (document.getElementById('globalConfirmModal')) {
    return;
  }

  const modalHTML = `
    <div class="modal fade" id="globalConfirmModal" tabindex="-1" aria-labelledby="globalConfirmModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h6 class="hd-md m-0" id="globalConfirmTitle">Confirm Action</h6>
            <button type="button" data-bs-dismiss="modal" aria-label="Close">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
          <div class="modal-body">
            <p class="mb-3" id="globalConfirmMessage">Are you sure you want to proceed?</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="globalConfirmCancel">Cancel</button>
            <button type="button" class="btn btn-danger" id="globalConfirmAction">Confirm</button>
          </div>
        </div>
      </div>
    </div>
  `;

  // Append modal to body
  document.body.insertAdjacentHTML('beforeend', modalHTML);
};

// Initialize modal when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  createConfirmationModal();
});

// ==> Global Confirmation Function
const createConfirmation = (options = {}) => {
  return new Promise((resolve, reject) => {
    // Default options
    const config = {
      title: 'Confirm Action',
      message: 'Are you sure you want to proceed?',
      confirmText: 'Confirm',
      cancelText: 'Cancel',
      confirmClass: 'btn-danger',
      onConfirm: null,
      onCancel: null,
      ...options
    };

    // Get modal elements
    const modal = document.getElementById('globalConfirmModal');
    const titleElement = document.getElementById('globalConfirmTitle');
    const messageElement = document.getElementById('globalConfirmMessage');
    const confirmButton = document.getElementById('globalConfirmAction');
    const cancelButton = document.getElementById('globalConfirmCancel');

    if (!modal) {
      console.error('Global confirmation modal not found');
      reject(new Error('Modal not found'));
      return;
    }

    // Update modal content
    titleElement.textContent = config.title;
    messageElement.innerHTML = config.message;
    confirmButton.textContent = config.confirmText;
    cancelButton.textContent = config.cancelText;

    // Update button class
    confirmButton.className = `btn ${config.confirmClass}`;

    // Create Bootstrap modal instance
    const bootstrapModal = new bootstrap.Modal(modal);

    // Handle confirm action
    const handleConfirm = () => {
      if (config.onConfirm && typeof config.onConfirm === 'function') {
        config.onConfirm();
      }
      bootstrapModal.hide();
      cleanup();
      resolve(true);
    };

    // Handle cancel action
    const handleCancel = () => {
      if (config.onCancel && typeof config.onCancel === 'function') {
        config.onCancel();
      }
      bootstrapModal.hide();
      cleanup();
      resolve(false);
    };

    // Cleanup function to remove event listeners
    const cleanup = () => {
      confirmButton.removeEventListener('click', handleConfirm);
      cancelButton.removeEventListener('click', handleCancel);
      modal.removeEventListener('hidden.bs.modal', handleCancel);
    };

    // Add event listeners
    confirmButton.addEventListener('click', handleConfirm);
    cancelButton.addEventListener('click', handleCancel);
    modal.addEventListener('hidden.bs.modal', handleCancel, { once: true });

    // Show modal
    bootstrapModal.show();
  });
};

// ==> Specific Delete Confirmation Function
const createDeleteConfirmation = (itemName, itemType = 'item') => {
  return createConfirmation({
    title: 'Confirm Delete',
    message: `Are you sure you want to delete ${itemType} <strong>${itemName}</strong>?`,
    confirmText: 'Delete',
    cancelText: 'Cancel',
    confirmClass: 'btn-danger'
  });
};

// ==> Usage Examples and Documentation
/*
Basic Usage:
-----------
createConfirmation({
  title: 'Confirm Delete',
  message: 'Are you sure you want to delete this item?',
  confirmText: 'Delete',
  cancelText: 'Cancel'
}).then(confirmed => {
  if (confirmed) {
    // User confirmed
  } else {
    // User cancelled
  }
});

Delete Usage:
------------
createDeleteConfirmation('John Doe', 'user').then(confirmed => {
  if (confirmed) {
    // Perform delete operation
    deleteUser();
  }
});

With Callbacks:
--------------
createConfirmation({
  title: 'Save Changes',
  message: 'Do you want to save your changes?',
  confirmText: 'Save',
  confirmClass: 'btn-success',
  onConfirm: () => {},
  onCancel: () => {}
});

Custom Styling:
--------------
createConfirmation({
  title: 'Warning',
  message: 'This action cannot be undone!',
  confirmText: 'Continue',
  confirmClass: 'btn-warning'
});
*/
