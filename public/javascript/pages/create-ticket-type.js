// ================================================
// =====> TAX / EXTRA CHARGES CONTROL  🥗
// ================================================
let checkTax = document.querySelector("#enable-tax");
let taxLabel = document.querySelector("#tax-lable");
let taxValue = document.querySelector("#tax-value");
let checkExtraCharges = document.querySelector("#enable-extra-charges");
let extraChargesLabel = document.querySelector("#extra-charges-label");
let extraChargesValue = document.querySelector("#extra-charges-value");

checkTax.addEventListener("click", () => {
  if (checkTax.checked) {
    taxLabel.setAttribute("required", "true");
    taxValue.setAttribute("required", "true");
  } else {
    taxLabel.removeAttribute("required");
    taxValue.removeAttribute("required");
  }
});
checkExtraCharges.addEventListener("click", () => {
  if (checkExtraCharges.checked) {
    extraChargesLabel.setAttribute("required", "true");
    extraChargesValue.setAttribute("required", "true");
  } else {
    extraChargesLabel.removeAttribute("required");
    extraChargesValue.removeAttribute("required");
  }
});

// ================================================
// =====> BULK DISCOUNT VALIDATION
// ================================================
let createMinOrderQty = document.querySelector("#createMinOrderQty");
let createDiscountPercentage = document.querySelector("#createDiscountPercentage");
let editMinOrderQty = document.querySelector("#minOrderQty");
let editDiscountPercentage = document.querySelector("#discountPercentage");
const TicketPrice = document.querySelector("#ticket_price");
const enable_bulk_discount = document.querySelector("#enable_bulk_discount");
const add_bulk_discount_btn = document.querySelector("#addBulkDiscountBtn");
const bulk_discount_toggle = document.querySelector("#bulkDiscountToggle");
const bulkDiscountDisabledMessage = "Add a valid ticket price first to enable bulk discount.";


const bulkDiscountHandeler = () => 
{
   if(!enable_bulk_discount || !add_bulk_discount_btn) {console.error("Bulk discount elements not found"); return;}
   
   if(!TicketPrice.value || TicketPrice.value <= 0) {
    enable_bulk_discount.checked = false;
    enable_bulk_discount.disabled = true;
    add_bulk_discount_btn.disabled = true;
    enable_bulk_discount.title = bulkDiscountDisabledMessage;
    if (bulk_discount_toggle) {
      bulk_discount_toggle.title = bulkDiscountDisabledMessage;
    }
   }else{
    enable_bulk_discount.disabled = false;
    add_bulk_discount_btn.disabled = false;
    enable_bulk_discount.removeAttribute("title");
    if (bulk_discount_toggle) {
      bulk_discount_toggle.removeAttribute("title");
    }
   }
}

bulkDiscountHandeler(); // Initial check on page load
TicketPrice && TicketPrice?.addEventListener("input", bulkDiscountHandeler);


if (createMinOrderQty) {
  createMinOrderQty.addEventListener("input", function() {
    if (!this.value) this.classList.remove('is-invalid');
    else validateSequence();
  });
}

if (createDiscountPercentage) {
  createDiscountPercentage.addEventListener("input", function() {
    if (!this.value) this.classList.remove('is-invalid');
    else validateSequence();
  });
}

if (editMinOrderQty) {
  editMinOrderQty.addEventListener("input", function() {
    if (!this.value) this.classList.remove('is-invalid');
    else validateEditPageSequence();
  });
}

if (editDiscountPercentage) {
  editDiscountPercentage.addEventListener("input", function() {
    if (!this.value) this.classList.remove('is-invalid');
    else validateEditPageSequence();
  });
}

function activebulkdiscount() 
{

}

// ================================================
// =====> REAL-TIME SEQUENCE VALIDATION  🥗
// ================================================
function validateSequence() {
 const totaltickets = document.querySelector("#totaltickets");
  const minQtyInput = document.querySelector("#createMinOrderQty");
  const discountInput = document.querySelector("#createDiscountPercentage");
  const minQtyErrorDiv = document.querySelector(".minQtyErr");
  const discountErrorDiv = document.querySelector(".discountErr");
  
  // Get max values from table
  const { maxQty, maxDiscount } = getMaxValues();
  const currentQty = parseInt(minQtyInput.value);
  const totalTicketsValue = totaltickets ? parseInt(totaltickets.value) : 20;
  const minorderQtyValue = totalTicketsValue > 20 ? 20 : totalTicketsValue;

  console.log(`Max Qty: ${maxQty}, Max Discount: ${maxDiscount}, Min Order Qty Value: ${minorderQtyValue} , currentQty: ${currentQty}` ); // Debugging alert
  // Validate Min Order Qty
  if (minQtyInput && minQtyErrorDiv) {
    
    if (!currentQty || currentQty <= 0) {
      minQtyErrorDiv.innerHTML = "";
      minQtyInput.classList.remove("is-invalid");
    } else if (maxQty > 0 && currentQty <= maxQty) {
      minQtyErrorDiv.innerHTML = `Min order quantity must be greater than ${maxQty}`;
      minQtyInput.classList.add("is-invalid");
    } 
    else if(currentQty > minorderQtyValue ) 
    {
      minQtyErrorDiv.innerHTML = `Please enter a minimum order quantity less than or equal to ${minorderQtyValue}.`;
      minQtyInput.classList.add("is-invalid");
    }
    else {
      minQtyErrorDiv.innerHTML = "";
      minQtyInput.classList.remove("is-invalid");
    }
  }
  else if(currentQty > minorderQtyValue ) 
  {
    if (typeof createNotification === "function") {
      createNotification(
        "error",
        `Minimum order quantity must be less than or equal to ${minorderQtyValue}.`,
        ""
      );
    }
    minQtyErrorDiv.innerHTML = `Please enter a minimum order quantity less than or equal to ${minorderQtyValue}.`;
    minQtyInput.classList.add("is-invalid");
  }
  
  // Validate Discount Percentage
  if (discountInput && discountErrorDiv) {
    const currentDiscount = parseFloat(discountInput.value);
    
    if (!currentDiscount || currentDiscount <= 0) {
      discountErrorDiv.innerHTML = "";
      discountInput.classList.remove("is-invalid");
    } else if (maxDiscount > 0 && currentDiscount <= maxDiscount) {
      discountErrorDiv.innerHTML = `Discount percentage must be greater than ${maxDiscount}%`;
      discountInput.classList.add("is-invalid");
    } 
     else if (currentDiscount > 100) {
      discountErrorDiv.innerHTML = `Discount percentage must be less than 100%`;
      discountInput.classList.add("is-invalid");
    }
    else {
      discountErrorDiv.innerHTML = "";
      discountInput.classList.remove("is-invalid");
    }
  }
   else if (currentDiscount > 100) {
      discountErrorDiv.innerHTML = `Discount percentage must be less than 100%`;
      discountInput.classList.add("is-invalid");
    }
}

// ================================================
// =====> GET MAX VALUES FROM TABLE  🥗
// ================================================
function getMaxValues() {
  let maxQty = 0;
  let maxDiscount = 0;
  
  document.querySelectorAll("#bulkDiscountTableBody tr").forEach(row => {
    const qtyCell = row.querySelector(".minQty");
    const discountCell = row.querySelector(".discountVal");
    
    if (qtyCell) {
      const qty = parseInt(qtyCell.textContent.trim());
      if (!isNaN(qty) && qty > maxQty) {
        maxQty = qty;
      }
    }
    
    if (discountCell) {
      const discountText = discountCell.textContent.trim();
      const discount = parseFloat(discountText.replace('%', '').replace(' off', ''));
      if (!isNaN(discount) && discount > maxDiscount) {
        maxDiscount = discount;
      }
    }
  });
  
  return { maxQty, maxDiscount };
}

// ================================================
// =====> GET MAX VALUES FOR EDIT PAGE  🥗
// ================================================
function getMaxValuesForEdit() {
  let maxQty = 0;
  let maxDiscount = 0;
  
  document.querySelectorAll("#bulkDiscountTableBody tr").forEach(row => {
    const qtyCell = row.querySelector("td:nth-child(2) div:last-child");
    const discountCell = row.querySelector("td:nth-child(3) div:last-child");
    
    if (qtyCell) {
      const qty = parseInt(qtyCell.textContent.trim());
      if (!isNaN(qty) && qty > maxQty) {
        maxQty = qty;
      }
    }
    
    if (discountCell) {
      const discountText = discountCell.textContent.trim();
      const discount = parseFloat(discountText.replace('%', '').replace(' off', ''));
      if (!isNaN(discount) && discount > maxDiscount) {
        maxDiscount = discount;
      }
    }
  });
  
  return { maxQty, maxDiscount };
}

// ================================================
// =====> EDIT PAGE SEQUENCE VALIDATION  🥗
// ================================================
function validateEditPageSequence() {
  const totaltickets = document.querySelector("#totaltickets");
  const minQtyInput = document.querySelector("#minOrderQty");
  const discountInput = document.querySelector("#discountPercentage");
  const minQtyErrorDiv = document.querySelector(".minQtyErr");
  const discountErrorDiv = document.querySelector(".discountErr");
  
  if (!minQtyInput || !discountInput) return;
  
  // Get max values from table using edit page function
  const { maxQty, maxDiscount } = getMaxValuesForEdit();
  // Validate Min Order Qty
  if (minQtyInput && minQtyErrorDiv) {
    const currentQty = parseInt(minQtyInput.value);
    const totalTicketsValue = totaltickets ? parseInt(totaltickets.value) : 20;
    const minorderQtyValue = totalTicketsValue > 20 ? 20 : totalTicketsValue;

    if (!currentQty || currentQty <= 0) {
      minQtyErrorDiv.innerHTML = "";
      minQtyInput.classList.remove("is-invalid");
    } else if (maxQty > 0 && currentQty <= maxQty) {
      minQtyErrorDiv.innerHTML = `Min order quantity must be greater than ${maxQty}`;
      minQtyInput.classList.add("is-invalid");
    } 
    else  if(currentQty > minorderQtyValue ) 
    {
      minQtyErrorDiv.innerHTML = `Please enter a minimum order quantity less than or equal to ${minorderQtyValue}.`;
      minQtyInput.classList.add("is-invalid");
    }
    else {
      minQtyErrorDiv.innerHTML = "";
      minQtyInput.classList.remove("is-invalid");
    }
  }
  
  // Validate Discount Percentage
  if (discountInput && discountErrorDiv) {
    const currentDiscount = parseFloat(discountInput.value);
    
    if (!currentDiscount || currentDiscount <= 0) {
      discountErrorDiv.innerHTML = "";
      discountInput.classList.remove("is-invalid");
    } else if (maxDiscount > 0 && currentDiscount <= maxDiscount) {
      discountErrorDiv.innerHTML = `Discount percentage must be greater than ${maxDiscount}%`;
      discountInput.classList.add("is-invalid");
    } 
    else if (currentDiscount > 100) {
      discountErrorDiv.innerHTML = `Discount percentage must be less than 100%`;
      discountInput.classList.add("is-invalid");
    }
    else {
      discountErrorDiv.innerHTML = "";
      discountInput.classList.remove("is-invalid");
    }
  }
}

// ================================================
// =====> EDIT MODAL SEQUENCE VALIDATION  🥗
// ================================================
function validateEditSequence() {
  const editMinQtyInput = document.querySelector("#edit_min_qty");
  const editErrorDiv = document.querySelector("#editMinQtyErr");
  const editId = document.querySelector("#edit_bulk_id").value;
  
  if (!editMinQtyInput || !editErrorDiv) return;
  
  const currentValue = parseInt(editMinQtyInput.value);
  
  if (!currentValue || currentValue <= 0) {
    editErrorDiv.textContent = "";
    editMinQtyInput.classList.remove("is-invalid");
    return;
  }
  
  // Get existing quantities from table (excluding current editing item)
  const existingQtys = [];
  document.querySelectorAll("#bulkDiscountTableBody tr").forEach(row => {
    const editBtn = row.querySelector(".action-btn.edit");
    const qtyCell = row.querySelector("td:nth-child(2) div:last-child");
    
    if (editBtn && qtyCell && editBtn.dataset.id !== editId) {
      const qty = parseInt(qtyCell.textContent.trim());
      if (!isNaN(qty)) {
        existingQtys.push(qty);
      }
    }
  });
  
  if (existingQtys.length > 0) {
    existingQtys.sort((a, b) => a - b);
    
    // Find valid range
    let lowerBound = 0;
    let upperBound = Infinity;
    
    for (let i = 0; i < existingQtys.length; i++) {
      if (currentValue > existingQtys[i]) {
        lowerBound = existingQtys[i];
      } else {
        upperBound = existingQtys[i];
        break;
      }
    }
    
    if (currentValue <= lowerBound) {
      editErrorDiv.textContent = `Min order quantity must be greater than ${lowerBound} to maintain sequence.`;
      editMinQtyInput.classList.add("is-invalid");
      return;
    }
    
    if (currentValue >= upperBound) {
      editErrorDiv.textContent = `Min order quantity must be less than ${upperBound} to maintain sequence.`;
      editMinQtyInput.classList.add("is-invalid");
      return;
    }
  }
  
  // Clear error if validation passes
  editErrorDiv.textContent = "";
  editMinQtyInput.classList.remove("is-invalid");
}

// Add event listener for edit modal input
document.addEventListener("DOMContentLoaded", function() {
  const editMinQtyInput = document.querySelector("#edit_min_qty");
  if (editMinQtyInput) {
    editMinQtyInput.addEventListener("input", function() {
      if (!this.value) this.classList.remove('is-invalid');
      else validateEditSequence();
    });
  }
});
