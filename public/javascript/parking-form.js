
// ======================================
// ========> Car Slots
// ======================================
const addSloteBtn = document.querySelector("#car-slot-btn-js");
const slotHolder = document.querySelector(".car-slots-container");
const selectedSlot = document.querySelector(".selected-slot");
let slotsStock = 5;
let ActiveSlots = 0;

const normalizeCarNumber = (value) => value.toUpperCase();

addSloteBtn?.addEventListener("click", () => {
  const slot = `
  <div class="car-slot-item">
      <input type="text" class="form-control"
          placeholder="Enter Car Number" name="car_details[]" id="carji-slot-${
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

slotHolder?.addEventListener("input", (e) => {
  const carNumberInput = e.target.closest('input[name="car_details[]"]');
  if (!carNumberInput) return;

  const normalizedValue = normalizeCarNumber(carNumberInput.value);
  if (carNumberInput.value !== normalizedValue) {
    carNumberInput.value = normalizedValue;
  }
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
