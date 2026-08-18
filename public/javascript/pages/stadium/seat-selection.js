/**
 * stadium.js
 * Handles stadium rendering for Admin (Add/Edit) and Website (Booking)
 */

const wingCreator = ({ data = [], stadium, otherIds = [], currentIds = [], heldIds = [], assignments = {}, targetId = null }) => {
    if (!stadium || !data || data.length === 0) return;

    let currentRow = "";
    let rowDiv;
    let wingBox = document.createElement("div");
    wingBox.classList.add(`${data[0]?.wing || "W"}`);

    data.forEach((item) => {
        if (item.row && item.row !== currentRow) {
            if (rowDiv) wingBox.appendChild(rowDiv);
            rowDiv = document.createElement("div");
            currentRow = item.row;
        }

        let ele;
        if (item.is_gap) { 
            ele = document.createElement("div");
            ele.classList.add("gp");
        } else {
            ele = document.createElement("input");
            ele.type = "checkbox";
            ele.name = "selected_seats[]";
            ele.value = item.id;
            ele.classList.add("form-check-input");

            // --- DATA EXTRACTION ---
            const assignment = assignments[item.id] || null;
            // A seat is physically booked if the database says is_booked = 1
            const isPhysicallyBooked = (assignment && assignment.is_booked == 1) || item.is_booked == 1;
            // A seat is held if its ID is in the heldIds array passed from the controller
            const isHeldByOther = heldIds.map(String).includes(String(item.id));
            
            const isTakenByOtherTicketType = otherIds.includes(item.id);
           
            const isCurrentTicketTypeSeat = currentIds.includes(item.id);
           

            // --- COLOR LOGIC ---
            const displayColor = (assignment?.ticket_type_color) || item.accent_color;
            if (displayColor) {
                ele.style.borderColor = displayColor;
                ele.style.setProperty("--seat-color", displayColor);
                // ele.style.setProperty('border', `1px solid ${displayColor}`, 'important');
            }

            // --- WEBSITE TARGETING LOGIC ---
            if (targetId) {
                // ENABLE ONLY IF:
                // 1. Matches target ticket type
                // 2. Not physically booked
                // 3. Not held by another user session
                if (assignment && assignment.ticket_type_id == targetId && !isPhysicallyBooked && !isHeldByOther) {
                    ele.disabled = false;
                } else {
                    ele.disabled = true;
                    ele.classList.add("seat-locked");
                    
                    // Specific styling for different locked states
                    if (isPhysicallyBooked) {
                        ele.classList.add("seat-booked");
                        ele.checked = true;
                        ele.style.opacity = "0.5"; // Keep it visible but looking "filled"
                    } else if (isHeldByOther) {
                        ele.classList.add("seat-locked");
                        ele.title = "This seat is currently in someone else's cart";
                    } else {
                        ele.style.opacity = "0.4"; // Other ticket types
                    }
                }
            } 
            
            // --- ADMIN & GENERAL STATE LOGIC ---
            else {
                if (isCurrentTicketTypeSeat) {
                    ele.checked = true;
                }

                if (isTakenByOtherTicketType || isPhysicallyBooked || isHeldByOther) {
                    ele.checked = true; 
                    ele.disabled = true;
                    ele.classList.add("seat-occupied");
                }
            }
            
            if (assignment?.title) ele.setAttribute('data-tippy-content', assignment.title);
            ele.setAttribute('data-seat-label', `${item.wing}-${item.row}${item.seat_number}`);
        }

        if (rowDiv) rowDiv.appendChild(ele);
    });

    if (rowDiv) wingBox.appendChild(rowDiv);
    stadium.appendChild(wingBox);
};

document.addEventListener("DOMContentLoaded", () => {
    const stadiumDiv = document.querySelector(".stadium");
    
    if (window.stadiumData && stadiumDiv) {
        const { 
            lwdata, clwdata, crwdata, rwdata, 
            otherIds = [], 
            currentIds = [],
            heldSeatIds = [], // Extracted from our new bridge key
            seatAssignments = {},
            targetTicketTypeId = null 
        } = window.stadiumData;
        
        const config = { 
            stadium: stadiumDiv, 
            otherIds: otherIds, 
            currentIds: currentIds,
            heldIds: heldSeatIds, 
            assignments: seatAssignments,
            targetId: targetTicketTypeId 
        };
        
        wingCreator({ data: lwdata, ...config });
        wingCreator({ data: clwdata, ...config });
        wingCreator({ data: crwdata, ...config });
        wingCreator({ data: rwdata, ...config });

        if (targetTicketTypeId) {
            stadiumDiv.addEventListener("change", updateSeatCounter);
            updateSeatCounter();

            const seatSelectionForm = document.querySelector("[data-seat-selection-form]");
            const seatSelectionError = document.querySelector("[data-seat-selection-error]");

            const hideSeatSelectionError = () => {
                if (!seatSelectionError) return;
                seatSelectionError.textContent = "";
                seatSelectionError.hidden = true;
            };

            const showSeatSelectionError = (message) => {
                if (!seatSelectionError) return;
                seatSelectionError.textContent = message;
                seatSelectionError.hidden = false;
            };

            stadiumDiv.addEventListener("change", () => {
                const selectedSeats = document.querySelectorAll('.stadium input[type="checkbox"]:checked:not(:disabled)');
                if (selectedSeats.length > 0) {
                    hideSeatSelectionError();
                }
            });

            if (seatSelectionForm) {
                seatSelectionForm.addEventListener("submit", (event) => {
                    const selectedSeats = document.querySelectorAll('.stadium input[type="checkbox"]:checked:not(:disabled)');

                    if (selectedSeats.length === 0) {
                        event.preventDefault();
                        showSeatSelectionError("Please select at least one seat to continue.");
                    }
                });
            }
        }
    }
});

function updateSeatCounter() {
    const selected = document.querySelectorAll('.stadium input[type="checkbox"]:checked:not(:disabled)');
    const countDisplay = document.querySelector(".selected-seats .text-prim");
    const labelDisplay = document.querySelector(".selected-seats span:not(.text-prim)");

    if (countDisplay) countDisplay.innerText = selected.length;
    if (labelDisplay) {
        const labels = Array.from(selected).map(s => `[ ${s.getAttribute('data-seat-label')} ]`);
        labelDisplay.innerText = labels.length > 0 ? labels.join(" ") : "None";
    }
}
