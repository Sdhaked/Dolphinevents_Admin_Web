const addSloteBtn = document.querySelector("#car-slot-btn-js");
const slotHolder = document.querySelector(".car-slot-container");
const selectedSlot = document.querySelector(".selected-slot");
let slotsStock = 5;
let ActiveSlots = 0;

const normalizeCarNumber = (value) => value.toUpperCase();

addSloteBtn?.addEventListener("click", () => {
  const slot = `
    <div class="car-slot-item" data-aos="fade-up">
        <div>
            <label for="carji-slot-${ActiveSlots + 1}">Parking Slot *</label>
            <input type="text" class="form-control" id="carji-slot-${
              ActiveSlots + 1
            }" placeholder="Vehicle Number" required="">
        </div>
        <div>
            <button type="button" class="btn-sm btn-prim-outline hover-prim no-transform delete-slot">
                <i class="fa-regular fa-trash-can"></i>
            </button>
        </div>
    </div>
    `;
  slotHolder.insertAdjacentHTML("afterbegin", slot);

  slotChecker();
});

slotHolder?.addEventListener("input", (e) => {
  const carNumberInput = e.target.closest(".car-slot-item input");
  if (!carNumberInput) return;

  const normalizedValue = normalizeCarNumber(carNumberInput.value);
  if (carNumberInput.value !== normalizedValue) {
    carNumberInput.value = normalizedValue;
  }
});

const slotChecker = () => {
  const getSlots = document.querySelectorAll(
    ".car-slot-container .car-slot-item"
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
