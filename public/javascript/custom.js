function showAdminMainLoader() {
    const loader = document.querySelector(".preloder");

    if (loader) {
        loader.style.display = "grid";
    }
}

function hideAdminMainLoader() {
    const loader = document.querySelector(".preloder");

    if (loader) {
        loader.style.display = "none";
    }
}

async function chooseEvent(e) {
    const selectElement = e.target;
    const eventId = selectElement.value;

    if (!eventId) return;

    // Check if this dropdown is part of the duplicate event form
    const isDuplicateForm = selectElement.closest('.duplicate-event-form');

    if (isDuplicateForm) {
        // If it's the duplicate form, just allow the selection without switching the active session
        console.log("Event selected for duplication ID:", eventId);
        return; 
    }

    showAdminMainLoader();
    selectElement.disabled = true;

    try {
        // Default behavior: Set the active event for the admin session
        const response = await fetch('/admin/events/set-current', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ event_id: eventId })
        });

        if (!response.ok) {
            throw new Error('Unable to switch event.');
        }

        // Get the current event data to determine type
        const eventResponse = await fetch('/admin/events/get-current');

        if (!eventResponse.ok) {
            throw new Error('Unable to load selected event.');
        }

        const eventData = await eventResponse.json();

        // Update menu visibility based on event type
        if (typeof updateMenuVisibility === "function") {
            updateMenuVisibility(eventData?.event?.type ?? eventData?.type);
        }

        // Redirect to dashboard instead of reloading current page
        window.location.href = '/admin';
    } catch (error) {
        console.error('Error switching event:', error);
        hideAdminMainLoader();
        selectElement.disabled = false;

        if (typeof createNotification === "function") {
            createNotification("error", "Failed to switch event. Please try again.", "");
        }
    }
}

function updateMenuVisibility(eventType) {
    // Get menu items
    const ticketCounterSimple = document.querySelector('.ticket-counter-simple');
    const ticketCounterSeat = document.querySelector('.ticket-counter-seat');
    const createTicketSimple = document.querySelector('.create-ticket-simple');
    const createTicketSeat = document.querySelector('.create-ticket-seat');

    if (eventType === 1) {
        // Simple booking system
        if (ticketCounterSimple) ticketCounterSimple.style.display = 'block';
        if (ticketCounterSeat) ticketCounterSeat.style.display = 'none';
        if (createTicketSimple) createTicketSimple.style.display = 'block';
        if (createTicketSeat) createTicketSeat.style.display = 'none';
    } else if (eventType === 2) {
        // Seat booking system
        if (ticketCounterSimple) ticketCounterSimple.style.display = 'none';
        if (ticketCounterSeat) ticketCounterSeat.style.display = 'block';
        if (createTicketSimple) createTicketSimple.style.display = 'none';
        if (createTicketSeat) createTicketSeat.style.display = 'block';
    }
}

// Make updateMenuVisibility available globally
window.updateMenuVisibility = updateMenuVisibility;
