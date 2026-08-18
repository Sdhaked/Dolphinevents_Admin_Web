// Dynamic ticket data will be loaded from the select options
let currentTicketData = null;
let appliedCoupon = null;
const CURRENCY_SYMBOL = window.APP_CURRENCY || "$";

function formatCurrency(amount, suffix = "/-") {
    const numericAmount = Number.parseFloat(amount) || 0;
    return `${CURRENCY_SYMBOL}${numericAmount.toFixed(2)}${suffix}`;
}

function formatNegativeCurrency(amount, suffix = "/-") {
    return `-${formatCurrency(amount, suffix)}`;
}

function formatZeroCurrency(suffix = "/-") {
    return `${CURRENCY_SYMBOL}0${suffix}`;
}

// Function to get ticket data from selected option
function getTicketData(selectElement) {
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    if (!selectedOption || !selectedOption.value) return null;

    const bulkDiscountsJson = selectedOption.getAttribute('data-bulk-discounts');
    let bulkDiscounts = [];
    try {
        bulkDiscounts = bulkDiscountsJson ? JSON.parse(bulkDiscountsJson) : [];
    } catch (e) {
        // Error parsing bulk discounts
    }

    return {
        id: selectedOption.value,
        title: selectedOption.getAttribute('data-title'),
        price: parseFloat(selectedOption.getAttribute('data-price')) || 0,
        available: parseInt(selectedOption.getAttribute('data-available')) || 0,
        taxEnabled: selectedOption.getAttribute('data-tax-enabled') === '1',
        taxValue: parseFloat(selectedOption.getAttribute('data-tax-value')) || 0,
        taxLabel: selectedOption.getAttribute('data-tax-label'),
        extraChargesEnabled: selectedOption.getAttribute('data-extra-charges-enabled') === '1',
        extraChargesValue: parseFloat(selectedOption.getAttribute('data-extra-charges-value')) || 0,
        extraChargesLabel: selectedOption.getAttribute('data-extra-charges-label'),
        bulkDiscountEnabled: selectedOption.getAttribute('data-bulk-discount-enabled') === '1',
        bulkDiscounts: bulkDiscounts
    };
}

// Function to update quantity dropdown based on available tickets
function updateQuantityOptions(availableTickets) {
    const ticketQtySelect = document.getElementsByName("ticketqty")[0];
    if (!ticketQtySelect) return;

    // Store current selection
    const currentValue = ticketQtySelect.value;

    // Clear all options except the default empty one
    ticketQtySelect.innerHTML = '<option value="" selected>0</option>';

    // Add options up to available tickets or maximum 20
    const maxQty = Math.min(availableTickets, 20);

    for (let i = 1; i <= maxQty; i++) {
        const option = document.createElement('option');
        option.value = i;
        option.textContent = i;
        ticketQtySelect.appendChild(option);
    }

    // Restore selection if still valid
    if (currentValue && parseInt(currentValue) <= maxQty) {
        ticketQtySelect.value = currentValue;
    } else {
        ticketQtySelect.value = "";
    }
}

// Function to update bill based on selected ticket type and quantity
function updateCustomerBill() {
    const ticketTypeSelect = document.getElementsByName("tickettype")[0];
    const ticketQtySelect = document.getElementsByName("ticketqty")[0];

    const selectedTicketTypeId = ticketTypeSelect.value;

    // Handle ticket type selection
    if (selectedTicketTypeId) {
        // Get ticket data from the selected option
        currentTicketData = getTicketData(ticketTypeSelect);
        if (!currentTicketData) {
            resetBill();
            return;
        }

        // Update quantity options based on available tickets
        updateQuantityOptions(currentTicketData.available);
    } else {
        // Reset quantity options to default when no ticket type selected
        updateQuantityOptions(20);
        resetBill();
        return;
    }

    let selectedQty = parseInt(ticketQtySelect.value) || 0;

    // Auto-set quantity to 1 when ticket type is selected and qty is 0
    if (selectedTicketTypeId && selectedQty === 0) {
        ticketQtySelect.value = '1';
        selectedQty = 1;
    }

    if (selectedQty === 0) {
        // Don't reset bill completely, just clear calculations but keep ticket type
        const ticketNameSpan = document.querySelector('.bill-table tbody tr:first-child h6 span');
        if (ticketNameSpan) {
            ticketNameSpan.innerHTML = `${currentTicketData.title} - ${formatCurrency(currentTicketData.price, '')}PP`;
        }

        // Clear other bill items but don't disable the container
        const priceRow = document.querySelector('.bill-table tbody tr:nth-child(2)');
        if (priceRow) {
            const priceInfo = priceRow.querySelector('th p');
            const priceTotal = priceRow.querySelector('td');
            if (priceInfo && priceTotal) {
                priceInfo.innerHTML = `<strong>${formatCurrency(currentTicketData.price)}</strong> <i class="fa-solid fa-xmark mx-2" style="color: #6c757d;"></i> <strong>0</strong> pcs`;
                priceTotal.innerHTML = `<strong style="color: #007bff;">${formatZeroCurrency()}</strong>`;
            }
        }

        // Hide bulk discount and reset total
        const bulkDiscountRow = document.querySelector('.bill-table tbody tr:nth-child(3)');
        if (bulkDiscountRow) {
            bulkDiscountRow.style.display = 'none';
        }

        // Hide tax, extra charges, and coupon
        const taxRow = document.getElementById('taxRow');
        const extraChargesRow = document.getElementById('extraChargesRow');
        const couponRow = document.getElementById('couponRow');
        if (taxRow) taxRow.style.display = 'none';
        if (extraChargesRow) extraChargesRow.style.display = 'none';
        if (couponRow) couponRow.style.display = 'none';

        const totalRow = document.querySelector('.bill-table tbody tr:last-child');
        if (totalRow) {
            const totalAmount = totalRow.querySelector('td');
            if (totalAmount) {
                totalAmount.innerHTML = `<strong style="color: var(--color-primary); font-size: 1.1em;">${formatZeroCurrency()}</strong>`;
            }
        }

        // Update promotion message
        updatePromotionMessage(currentTicketData, 0);
        setBillContainerState(true);
        return;
    }

    const ticketPrice = currentTicketData.price;
    const subtotal = ticketPrice * selectedQty;

    // Update ticket name in bill header with price
    const ticketNameSpan = document.querySelector('.bill-table tbody tr:first-child h6 span');
    if (ticketNameSpan) {
        ticketNameSpan.innerHTML = `${currentTicketData.title} - ${formatCurrency(ticketPrice, '')}PP`;
    }

    // Update subtotal row (now the 2nd row after header)
    const priceRow = document.querySelector('.bill-table tbody tr:nth-child(2)');
    if (priceRow) {
        const priceInfo = priceRow.querySelector('th p');
        const priceTotal = priceRow.querySelector('td');
        if (priceInfo && priceTotal) {
            priceInfo.innerHTML = `<strong>${formatCurrency(ticketPrice)}</strong> <i class="fa-solid fa-xmark mx-2" style="color: #6c757d;"></i> <strong>${selectedQty}</strong> pcs`;
            priceTotal.innerHTML = `<strong style="color: #007bff;">${formatCurrency(subtotal)}</strong>`;
        }
    }

    // Check and apply bulk discount
    let bulkDiscountAmount = 0;
    const bulkDiscountRow = document.querySelector('.bill-table tbody tr:nth-child(3)');

    // Find the best applicable bulk discount
    let applicableBulkDiscount = null;
    if (currentTicketData.bulkDiscountEnabled && currentTicketData.bulkDiscounts.length > 0) {
        // Sort by min_order_qty and find the highest applicable discount
        const sortedDiscounts = currentTicketData.bulkDiscounts
            .filter(discount => selectedQty >= discount.min_order_qty)
            .sort((a, b) => b.min_order_qty - a.min_order_qty);

        if (sortedDiscounts.length > 0) {
            applicableBulkDiscount = sortedDiscounts[0];
        }
    }

    if (applicableBulkDiscount && bulkDiscountRow) {
        bulkDiscountAmount = parseFloat(((subtotal * applicableBulkDiscount.discount_percentage) / 100).toFixed(2));
        bulkDiscountRow.style.display = 'table-row';

        const discountInfo = bulkDiscountRow.querySelector('th p');
        const discountAmount = bulkDiscountRow.querySelector('td');
        if (discountInfo && discountAmount) {
            discountInfo.textContent = `${applicableBulkDiscount.discount_percentage}% off`;
            discountAmount.innerHTML = `<span class="text-danger">${formatNegativeCurrency(bulkDiscountAmount)}</span>`;
        }
    } else if (bulkDiscountRow) {
        bulkDiscountRow.style.display = 'none';
        bulkDiscountAmount = 0;
    }

    // Calculate taxes
    let taxAmount = 0;
    const taxRow = document.getElementById('taxRow');
    if (currentTicketData.taxEnabled && currentTicketData.taxValue > 0 && taxRow) {
        const beforeTaxAmount = subtotal - bulkDiscountAmount;
        taxAmount = parseFloat(((beforeTaxAmount * currentTicketData.taxValue) / 100).toFixed(2));
        taxRow.style.display = 'table-row';

        const taxLabel = taxRow.querySelector('.tax-label');
        const taxAmountCell = taxRow.querySelector('.tax-amount');
        if (taxLabel && taxAmountCell) {
            taxLabel.textContent = `${currentTicketData.taxLabel || 'Tax'} ${currentTicketData.taxValue}%`;
            taxAmountCell.innerHTML = `<strong style="color: #007bff;">${formatCurrency(taxAmount)}</strong>`;
        }
    } else if (taxRow) {
        taxRow.style.display = 'none';
        taxAmount = 0;
    }

    // Calculate extra charges
    let extraChargesAmount = 0;
    const extraChargesRow = document.getElementById('extraChargesRow');
    if (currentTicketData.extraChargesEnabled && currentTicketData.extraChargesValue > 0 && extraChargesRow) {
        const beforeChargesAmount = subtotal - bulkDiscountAmount;
        extraChargesAmount = parseFloat(((beforeChargesAmount * currentTicketData.extraChargesValue) / 100).toFixed(2));
        extraChargesRow.style.display = 'table-row';

        const extraChargesLabel = extraChargesRow.querySelector('.extra-charges-label');
        const extraChargesAmountCell = extraChargesRow.querySelector('.extra-charges-amount');
        if (extraChargesLabel && extraChargesAmountCell) {
            extraChargesLabel.textContent = `${currentTicketData.extraChargesLabel || 'Extra Charges'} ${currentTicketData.extraChargesValue}%`;
            extraChargesAmountCell.innerHTML = `<strong style="color: #007bff;">${formatCurrency(extraChargesAmount)}</strong>`;
        }
    } else if (extraChargesRow) {
        extraChargesRow.style.display = 'none';
        extraChargesAmount = 0;
    }

    // Calculate coupon discount
    let couponDiscountAmount = 0;
    const couponRow = document.getElementById('couponRow');
    if (appliedCoupon && couponRow) {
        // Calculate coupon discount on the amount after bulk discount but before tax/charges
        const beforeCouponAmount = subtotal - bulkDiscountAmount;
        couponDiscountAmount = parseFloat(((beforeCouponAmount * appliedCoupon.discount_percentage) / 100).toFixed(2));

        couponRow.style.display = 'table-row';

        const couponLabel = couponRow.querySelector('.coupon-label');
        const couponAmountCell = couponRow.querySelector('.coupon-amount');
        if (couponLabel && couponAmountCell) {
            couponLabel.textContent = `[${appliedCoupon.code}] ${appliedCoupon.discount_percentage}% off`;
            couponAmountCell.innerHTML = `<span class="text-danger">${formatNegativeCurrency(couponDiscountAmount)}</span>`;
        }
    } else if (couponRow) {
        couponRow.style.display = 'none';
        couponDiscountAmount = 0;
    }

    // Calculate final total (subtract bulk discount and coupon discount, add tax and extra charges)
    const finalTotal = subtotal - bulkDiscountAmount - couponDiscountAmount + taxAmount + extraChargesAmount;

    // Update total amount
    const totalRow = document.querySelector('.bill-table tbody tr:last-child');
    if (totalRow) {
        const totalAmount = totalRow.querySelector('td');
        if (totalAmount) {
            totalAmount.innerHTML = `<strong style="color: var(--color-primary); font-size: 1.1em;">${formatCurrency(finalTotal)}</strong>`;
        }
    }

    // Update bulk discount promotion message
    updatePromotionMessage(currentTicketData, selectedQty);

    // Enable bill container
    setBillContainerState(true);
}

// Function to update promotion message for bulk discount
function updatePromotionMessage(ticketData, currentQty) {
    const promoMsg = document.querySelector('.promote-msg');

    if (!ticketData || !ticketData.bulkDiscountEnabled || !ticketData.bulkDiscounts.length) {
        if (promoMsg) {
            promoMsg.style.display = 'none';
        }
        return;
    }

    // Find the next applicable bulk discount
    const nextDiscount = ticketData.bulkDiscounts
        .filter(discount => currentQty < discount.min_order_qty)
        .sort((a, b) => a.min_order_qty - b.min_order_qty)[0];

    // Check if user already has a bulk discount applied
    const currentDiscount = ticketData.bulkDiscounts
        .filter(discount => currentQty >= discount.min_order_qty)
        .sort((a, b) => b.min_order_qty - a.min_order_qty)[0];

    if (promoMsg) {
        if (currentDiscount) {
            // User already has a discount
            promoMsg.style.display = 'block';
            promoMsg.innerHTML = `
                <p class="text-success"><b>Congratulations!</b> You got <b>${currentDiscount.discount_percentage}% Bulk Ticket Discount</b></p>
                <p>Perfect choice! You're saving money with bulk discount.</p>
            `;
        } else if (nextDiscount) {
            // User can get a discount by adding more tickets
            const ticketsNeeded = nextDiscount.min_order_qty - currentQty;
            promoMsg.style.display = 'block';
            promoMsg.innerHTML = `
                <p><b>${ticketsNeeded} Tickets</b> away from <b>${nextDiscount.discount_percentage}% Bulk Ticket Discount</b></p>
                <p>Add ${ticketsNeeded} more tickets to get ${nextDiscount.discount_percentage}% discount!</p>
            `;
        } else {
            promoMsg.style.display = 'none';
        }
    }
}

// Function to reset bill to default state
function resetBill() {
    const ticketNameSpan = document.querySelector('.bill-table tbody tr:first-child h6 span');
    if (ticketNameSpan) {
        ticketNameSpan.innerHTML = 'Select Ticket';
    }

    const priceRow = document.querySelector('.bill-table tbody tr:nth-child(2)');
    if (priceRow) {
        const priceInfo = priceRow.querySelector('th p');
        const priceTotal = priceRow.querySelector('td');
        if (priceInfo && priceTotal) {
            priceInfo.innerHTML = `${formatZeroCurrency()} <i class="fa-solid fa-xmark mx-2"></i> 0 pcs`;
            priceTotal.textContent = formatZeroCurrency();
        }
    }

    // Hide bulk discount row
    const bulkDiscountRow = document.querySelector('.bill-table tbody tr:nth-child(3)');
    if (bulkDiscountRow) {
        bulkDiscountRow.style.display = 'none';
    }

    // Hide tax, extra charges, and coupon rows
    const taxRow = document.getElementById('taxRow');
    const extraChargesRow = document.getElementById('extraChargesRow');
    const couponRow = document.getElementById('couponRow');
    if (taxRow) taxRow.style.display = 'none';
    if (extraChargesRow) extraChargesRow.style.display = 'none';
    if (couponRow) couponRow.style.display = 'none';

    // Reset total
    const totalRow = document.querySelector('.bill-table tbody tr:last-child');
    if (totalRow) {
        const totalAmount = totalRow.querySelector('td');
        if (totalAmount) {
            totalAmount.textContent = formatZeroCurrency();
        }
    }

    // Hide promotion message
    const promoMsg = document.querySelector('.promote-msg');
    if (promoMsg) {
        promoMsg.style.display = 'none';
    }

    // Reset quantity to 0 when no ticket selected and reset options to default
    const ticketQtySelect = document.getElementsByName("ticketqty")[0];
    if (ticketQtySelect) {
        ticketQtySelect.value = "";
        // Reset quantity options to full range when no ticket type selected
        updateQuantityOptions(20);
    }

    // Reset current ticket data
    currentTicketData = null;

    // Make bill container appear disabled
    setBillContainerState(false);
}

// Function to apply coupon
function applyCoupon() {
    const couponCode = document.getElementById('coupon').value.trim();
    const ticketTypeSelect = document.getElementsByName("tickettype")[0];
    const ticketQtySelect = document.getElementsByName("ticketqty")[0];

    // Clear previous status
    document.getElementById('couponStatus').style.display = 'none';
    document.getElementById('couponSuccess').style.display = 'none';
    document.getElementById('couponError').style.display = 'none';

    if (!couponCode) {
        showCouponError('Please enter a coupon code');
        return;
    }

    if (!currentTicketData || !ticketTypeSelect.value) {
        showCouponError('Please select a ticket type first');
        return;
    }

    const selectedQty = parseInt(ticketQtySelect.value) || 0;
    if (selectedQty === 0) {
        showCouponError('Please select ticket quantity first');
        return;
    }

    // Get current event ID from the form
    const form = document.querySelector('form[data-event-id]');
    const eventId = form ? form.getAttribute('data-event-id') : null;

    if (!eventId) {
        showCouponError('Event information not found');
        return;
    }

    // Calculate current subtotal (before any discounts)
    const subtotal = currentTicketData.price * selectedQty;

    // Show loading state
    const applyCouponBtn = document.getElementById('applyCouponBtn');
    const originalBtnText = applyCouponBtn.innerHTML;
    applyCouponBtn.innerHTML = 'Applying...';
    applyCouponBtn.disabled = true;

    // Make API call to validate coupon
    fetch('/api/v1/validate-coupon', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            code: couponCode,
            event_id: eventId,
            ticket_type_id: currentTicketData.id,
            total_amount: subtotal
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove any previously applied coupon
            appliedCoupon = {
                code: couponCode,
                title: data.coupon.title,
                associate_name: data.coupon.associate_name,
                discount_percentage: data.discount_percentage,
                discount_amount: parseFloat(data.discount_amount)
            };

            showCouponSuccess(`Coupon "${couponCode}" applied successfully!`);
            updateCustomerBill(); // Recalculate the bill with coupon applied
        } else {
            showCouponError(data.message || 'Failed to apply coupon');
        }
    })
    .catch(error => {
        showCouponError('Failed to apply coupon. Please try again.');
    })
    .finally(() => {
        // Restore button state
        applyCouponBtn.innerHTML = originalBtnText;
        applyCouponBtn.disabled = false;
    });
}

// Function to show coupon success message
function showCouponSuccess(message) {
    document.getElementById('couponStatus').style.display = 'block';
    document.getElementById('couponSuccess').style.display = 'block';
    document.getElementById('couponError').style.display = 'none';
    document.getElementById('couponSuccess').innerHTML = message + ' <i class="fa-solid fa-circle-check"></i>';
}

// Function to show coupon error message
function showCouponError(message) {
    document.getElementById('couponStatus').style.display = 'block';
    document.getElementById('couponSuccess').style.display = 'none';
    document.getElementById('couponError').style.display = 'block';
    document.getElementById('couponError').innerHTML = message + ' <i class="fa-solid fa-circle-xmark"></i>';
}

// Function to remove applied coupon
function removeCoupon() {
    appliedCoupon = null;
    document.getElementById('coupon').value = '';
    document.getElementById('couponStatus').style.display = 'none';
    document.getElementById('couponSuccess').style.display = 'none';
    document.getElementById('couponError').style.display = 'none';
    updateCustomerBill(); // Recalculate the bill without coupon
}

// Function to enable/disable bill container appearance
function setBillContainerState(enabled) {
    const billContainer = document.querySelector('.bill-table').closest('div');
    const billTable = document.querySelector('.bill-table');
    const billHeader = document.querySelector('h4.hd-md.text-center.text-uppercase');

    if (enabled) {
        // Enable - normal appearance
        if (billContainer) {
            billContainer.style.opacity = '1';
            billContainer.style.pointerEvents = 'auto';
        }
        if (billTable) {
            billTable.style.filter = 'none';
        }
        if (billHeader) {
            billHeader.style.color = '';
        }
    } else {
        // Disable - faded appearance
        if (billContainer) {
            billContainer.style.opacity = '0.5';
            billContainer.style.pointerEvents = 'none';
        }
        if (billTable) {
            billTable.style.filter = 'grayscale(100%)';
        }
        if (billHeader) {
            billHeader.style.color = '#ccc';
        }
    }
}

// Function to handle buy ticket with confirmation
function handleBuyTicket(event) {
    const form = document.querySelector('form[data-event-id]');

    if (!form) {
        createNotification("error", "Error!", "Form not found. Please refresh the page.");
        return;
    }

    // Prevent any default behavior
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    // Validate form
    if (!form.checkValidity()) {
        form.classList.add('was-validated');

        // Force visual update
        setTimeout(() => {
            const invalidFields = form.querySelectorAll(':invalid');
            invalidFields.forEach(field => {
                field.classList.add('is-invalid');
            });
        }, 10);

        return;
    }

    // Get form values
    const ticketTypeSelect = document.getElementsByName("tickettype")[0];
    const ticketQtySelect = document.getElementsByName("ticketqty")[0];
    const customerName = document.getElementsByName("cname")[0].value;
    const customerEmail = document.getElementsByName("cemail")[0].value;
    const customerMobile = document.getElementsByName("cmobile")[0].value;
    const couponCode = document.getElementsByName("coupon")[0].value;

    if (!currentTicketData || !ticketTypeSelect.value) {
        createNotification("error", "Error!", "Please select a ticket type.");
        return;
    }

    const selectedQty = parseInt(ticketQtySelect.value) || 0;
    if (selectedQty === 0) {
        createNotification("error", "Error!", "Please select ticket quantity.");
        return;
    }

    // Validate selected quantity against available tickets
    if (selectedQty > currentTicketData.available) {
        createNotification("error", "Error!", `Only ${currentTicketData.available} tickets are available. You cannot select ${selectedQty} tickets.`);
        return;
    }

    // Calculate pricing
    const ticketPrice = currentTicketData.price;
    const subtotal = ticketPrice * selectedQty;

    // Find applicable bulk discount
    let bulkDiscountAmount = 0;
    let bulkDiscountPercentage = 0;
    if (currentTicketData.bulkDiscountEnabled && currentTicketData.bulkDiscounts.length > 0) {
        const applicableDiscount = currentTicketData.bulkDiscounts
            .filter(discount => selectedQty >= discount.min_order_qty)
            .sort((a, b) => b.min_order_qty - a.min_order_qty)[0];

        if (applicableDiscount) {
            bulkDiscountPercentage = applicableDiscount.discount_percentage;
            bulkDiscountAmount = parseFloat(((subtotal * bulkDiscountPercentage) / 100).toFixed(2));
        }
    }

    // Calculate coupon discount
    let couponDiscountAmount = 0;
    if (appliedCoupon) {
        const beforeCouponAmount = subtotal - bulkDiscountAmount;
        couponDiscountAmount = parseFloat(((beforeCouponAmount * appliedCoupon.discount_percentage) / 100).toFixed(2));
    }

    // Calculate tax
    let taxAmount = 0;
    if (currentTicketData.taxEnabled && currentTicketData.taxValue > 0) {
        const beforeTaxAmount = subtotal - bulkDiscountAmount - couponDiscountAmount;
        taxAmount = parseFloat(((beforeTaxAmount * currentTicketData.taxValue) / 100).toFixed(2));
    }

    // Calculate extra charges
    let extraChargesAmount = 0;
    if (currentTicketData.extraChargesEnabled && currentTicketData.extraChargesValue > 0) {
        const beforeChargesAmount = subtotal - bulkDiscountAmount - couponDiscountAmount;
        extraChargesAmount = parseFloat(((beforeChargesAmount * currentTicketData.extraChargesValue) / 100).toFixed(2));
    }

    const finalTotal = subtotal - bulkDiscountAmount - couponDiscountAmount + taxAmount + extraChargesAmount;

    // Build confirmation message with billing details
    let confirmationHtml = `
        <div style="text-align: left; font-size: 13px;">
            <h5 style="margin-bottom: 12px; color: var(--color-primary); font-size: 16px;">Order Summary</h5>

            <div style="padding: 10px; border-radius: 5px; margin-bottom: 10px;">
                <h6 style="margin-bottom: 8px; border-bottom: 1px solid #dee2e6; padding-bottom: 5px; font-size: 14px;">Customer Information</h6>
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span><strong>Name:</strong></span>
                    <span>${customerName}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span><strong>Email:</strong></span>
                    <span>${customerEmail}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span><strong>Mobile:</strong></span>
                    <span>${customerMobile}</span>
                </div>
            </div>

            <div style="padding: 10px; border-radius: 5px; margin-bottom: 10px;">
                <h6 style="margin-bottom: 8px; border-bottom: 1px solid #dee2e6; padding-bottom: 5px; font-size: 14px;">Ticket Details</h6>
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span><strong>Ticket Type:</strong></span>
                    <span>${currentTicketData.title}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span><strong>Price per Ticket:</strong></span>
                    <span>${formatCurrency(ticketPrice, '')}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span><strong>Quantity:</strong></span>
                    <span>${selectedQty}</span>
                </div>
            </div>

            <div style="padding: 10px; border-radius: 5px;">
                <h6 style="margin-bottom: 8px; border-bottom: 1px solid #dee2e6; padding-bottom: 5px; font-size: 14px;">Billing Summary</h6>
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span><strong>Subtotal:</strong></span>
                    <span>${formatCurrency(subtotal)}</span>
                </div>`;

    if (bulkDiscountAmount > 0) {
        confirmationHtml += `
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px; color: #28a745;">
                    <span><strong>Bulk Discount (${bulkDiscountPercentage}%):</strong></span>
                    <span>${formatNegativeCurrency(bulkDiscountAmount)}</span>
                </div>`;
    }

    if (appliedCoupon && couponDiscountAmount > 0) {
        confirmationHtml += `
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px; color: #28a745;">
                    <span><strong>Coupon [${appliedCoupon.code}] (${appliedCoupon.discount_percentage}%):</strong></span>
                    <span>${formatNegativeCurrency(couponDiscountAmount)}</span>
                </div>`;
    }

    if (taxAmount > 0) {
        confirmationHtml += `
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span><strong>${currentTicketData.taxLabel || 'Tax'} (${currentTicketData.taxValue}%):</strong></span>
                    <span>${formatCurrency(taxAmount)}</span>
                </div>`;
    }

    if (extraChargesAmount > 0) {
        confirmationHtml += `
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span><strong>${currentTicketData.extraChargesLabel || 'Extra Charges'} (${currentTicketData.extraChargesValue}%):</strong></span>
                    <span>${formatCurrency(extraChargesAmount)}</span>
                </div>`;
    }

    confirmationHtml += `
                <hr style="margin: 8px 0;">
                <div style="display: flex; justify-content: space-between; font-size: 15px; color: var(--color-primary);">
                    <span><strong>Total Amount:</strong></span>
                    <span><strong>${formatCurrency(finalTotal)}</strong></span>
                </div>
            </div>
        </div>
    `;

    // Show confirmation dialog using the global confirmation system
    createConfirmation({
        title: 'Confirm Ticket Purchase',
        message: confirmationHtml,
        confirmText: 'Confirm Purchase',
        cancelText: 'Cancel',
        confirmClass: 'btn-success',
        onConfirm: () => {
            // Show processing alert
            Swal.fire({
                title: 'Processing...',
                text: 'Processing your ticket purchase. Please wait...',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            // Get event ID from form
            const eventId = form.getAttribute('data-event-id');

            // Prepare data for API call
            const purchaseData = {
                event_id: eventId,
                ticket_type_id: currentTicketData.id,
                quantity: selectedQty,
                customer_name: customerName,
                customer_email: customerEmail,
                customer_mobile: customerMobile,
                coupon_code: appliedCoupon ? appliedCoupon.code : null,
                billing_details: {
                    subtotal: parseFloat(subtotal.toFixed(2)),
                    bulk_discount_amount: parseFloat(bulkDiscountAmount.toFixed(2)),
                    bulk_discount_percentage: bulkDiscountPercentage,
                    coupon_discount_amount: parseFloat(couponDiscountAmount.toFixed(2)),
                    coupon_discount_percentage: appliedCoupon ? appliedCoupon.discount_percentage : 0,
                    tax_amount: parseFloat(taxAmount.toFixed(2)),
                    tax_percentage: currentTicketData.taxEnabled ? currentTicketData.taxValue : 0,
                    tax_label: currentTicketData.taxEnabled ? currentTicketData.taxLabel : null,
                    extra_charges_amount: parseFloat(extraChargesAmount.toFixed(2)),
                    extra_charges_percentage: currentTicketData.extraChargesEnabled ? currentTicketData.extraChargesValue : 0,
                    extra_charges_label: currentTicketData.extraChargesEnabled ? currentTicketData.extraChargesLabel : null,
                    final_total: parseFloat(finalTotal.toFixed(2))
                }
            };

            // Make API call to purchase ticket
            fetch(`/admin/events/${eventId}/ticket-counter/purchase`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify(purchaseData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success alert with ticket details
                    Swal.fire({
                        title: 'Purchase Successful!',
                        html: `
                            <div style="text-align: left;">
                                <p><strong>Ticket Number:</strong> ${data.ticket.ticket_number}</p>
                                <p><strong>Customer:</strong> ${data.ticket.customer_name}</p>
                                <p><strong>Quantity:</strong> ${data.ticket.quantity}</p>
                                <p><strong>Total Amount:</strong> ${formatCurrency(data.ticket.final_total, '')}</p>
                                <p><strong>Purchase Date:</strong> ${data.ticket.purchased_at}</p>
                            </div>
                        `,
                        icon: 'success',
                        confirmButtonText: 'OK',
                        allowOutsideClick: false
                    }).then(() => {
                        // Reset form and refresh page after user clicks OK
                        form.reset();
                        form.classList.remove('was-validated');
                        // Reset applied coupon
                        appliedCoupon = null;
                        removeCoupon();
                        resetBill();
                        window.location.reload();
                    });
                } else {
                    // Show error alert
                    Swal.fire({
                        title: 'Purchase Failed!',
                        text: data.message || 'Failed to purchase ticket. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                console.error('Purchase error:', error);
                Swal.fire({
                    title: 'Purchase Failed!',
                    text: 'An error occurred while purchasing the ticket. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
        },
        onCancel: () => {
            console.log('Purchase cancelled');
        }
    });
}

// Add event listeners when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    const ticketTypeSelect = document.getElementsByName("tickettype")[0];
    const ticketQtySelect = document.getElementsByName("ticketqty")[0];
    const buyTicketBtn = document.getElementById('buyTicketBtn');
    const applyCouponBtn = document.getElementById('applyCouponBtn');
    const couponInput = document.getElementById('coupon');

    if (ticketTypeSelect) {
        ticketTypeSelect.addEventListener('change', function() {
            // Remove coupon when ticket type changes
            if (appliedCoupon) {
                removeCoupon();
            }
            updateCustomerBill();
        });
    }

    if (ticketQtySelect) {
        ticketQtySelect.addEventListener('change', updateCustomerBill);
    }

    if (buyTicketBtn) {
        buyTicketBtn.addEventListener('click', handleBuyTicket);
    }

    if (applyCouponBtn) {
        applyCouponBtn.addEventListener('click', applyCoupon);
    }

    // Allow Enter key to apply coupon
    if (couponInput) {
        couponInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyCoupon();
            }
        });

        // Clear coupon status when user starts typing new code
        couponInput.addEventListener('input', function() {
            if (appliedCoupon) {
                removeCoupon();
            }
        });
    }

    // Initialize bill with default state
    resetBill();

    // Initialize quantity options to default range
    updateQuantityOptions(20);
});

const checkData = () => {
  let couponCode = document.getElementsByName("coupon")[0].value.trim();
  let ticketType = document.getElementsByName("tickettype")[0].value.trim();
  let ticketQty = document.getElementsByName("ticketqty")[0];
  let cname = document.getElementsByName("cname")[0].value.trim();
  let cemail = document.getElementsByName("cemail")[0].value.trim();
  let cph = document.getElementsByName("cmobile")[0].value.trim();

  let values = [couponCode, ticketType, cname, cemail, cph];
  if (ticketQty) values.push(ticketQty.value.trim());
  return values.every((value) => value === "");
};

window.addEventListener("beforeunload", (event) => {
  if (!checkData()) {
    event.preventDefault();
    event.returnValue = "";
  }
});


// ======================================
// ========> Car Slots
// ======================================
const addSloteBtn = document.querySelector("#car-slot-btn-js");
const slotHolder = document.querySelector(".car-slots-container");
const selectedSlot = document.querySelector(".selected-slot");
let slotsStock = 5;
let ActiveSlots = 0;

addSloteBtn?.addEventListener("click", () => {
  const slot = `
  <div class="car-slot-item">
      <input type="text" class="form-control"
          placeholder="Enter Car Number" id="carji-slot-${
            ActiveSlots + 1
          }" required>
      <button type="button" class="btn-sm danger-outline-btn delete-slot">
        <i class="fa-regular fa-trash-can"></i>
      </button>
  </div>
    `;
  slotHolder.insertAdjacentHTML("afterbegin", slot);

  slotChecker();
});

const slotChecker = () => {
  const getSlots = document.querySelectorAll(
    ".car-slots-container .car-slot-item",
  ).length;

  ActiveSlots = getSlots;
  if (selectedSlot) selectedSlot.textContent = ActiveSlots;

  if (slotsStock <= ActiveSlots) {
    addSloteBtn.setAttribute("disabled", "true");
  } else {
    addSloteBtn.removeAttribute("disabled");
  }
};

// DELETE SLOT (EVENT DELEGATION)
slotHolder?.addEventListener("click", (e) => {
  const deleteBtn = e.target.closest(".delete-slot");
  if (!deleteBtn) return;

  const slotItem = deleteBtn.closest(".car-slot-item");
  slotItem.remove();

  slotChecker();
});
