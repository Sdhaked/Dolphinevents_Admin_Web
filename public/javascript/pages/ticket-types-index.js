document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');

    if (searchInput) {
        // Auto-submit search after user stops typing (debounced)
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);

            searchTimeout = setTimeout(function() {
                if (searchInput.value.length === 0 || searchInput.value.length >= 2) {
                    performSearch();
                }
            }, 500); // Wait 500ms after user stops typing
        });

        // Handle Enter key press
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            }
        });

        // Function to perform search by updating URL
        function performSearch() {
            const currentUrl = new URL(window.location);

            if (searchInput.value.trim() === '') {
                currentUrl.searchParams.delete('search');
            } else {
                currentUrl.searchParams.set('search', searchInput.value.trim());
            }

            // Reset to first page when searching
            currentUrl.searchParams.delete('page');

            window.location.href = currentUrl.toString();
        }

    }
});

// Delete ticket type function
function deleteTicketType(ticketTypeId, ticketTypeTitle) {
    Swal.fire({
        title: 'Are you sure?',
        text: `Do you want to delete the ticket type "${ticketTypeTitle}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Get current event ID from URL
            const pathSegments = window.location.pathname.split('/');
            const currentEventId = pathSegments[3]; // /admin/events/{id}/ticket-types

            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/events/${currentEventId}/ticket-types/${ticketTypeId}/delete`;

            // Add CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrfToken) {
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken;
                form.appendChild(csrfInput);
            }

            // Add method override for DELETE
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);

            document.body.appendChild(form);
            form.submit();
        }
    });
}
