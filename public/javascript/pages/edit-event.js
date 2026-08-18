// =========> UTIL FUNCTIONS
// add 1 day in targeted date picker input field min value.
const plusOneDay = (date, targetEle) => {
    let newDate = new Date(date);
    newDate.setDate(newDate.getDate() + 1);
    const dateLimit = newDate.toISOString().split("T")[0];
    targetEle.min = dateLimit;
};

// -----------
const eventFromdate = document.querySelector("#fromDate");
const eventToDate = document.querySelector("#toDate");
const eventToTime = document.querySelector("#event-to-time");
const sellTillInp = document.querySelector("#sell-ticket-till");
// -----------
const handeleventtodate = () => {
    eventToDate.value = "";
    if (eventFromdate.value != "") {
        eventToDate.removeAttribute("disabled");
        plusOneDay(eventFromdate.value, eventToDate);
    } else {
        eventToDate.setAttribute("disabled", "true");
    }
};

handeleventtodate(); // initial call to set state on page load

// ========> ONE DAY PLUS DATE LIMIT fun()
eventFromdate?.addEventListener("change", handeleventtodate);

// ========> CAR PARKING ENABLE DISABLE fun()
const isParking = document.querySelector("#isParking");
const parkkingSlots = document.querySelector("#parking-slots");
const parkPrice = document.querySelector("#parking-price");

// --> Check Parking enabled?
const checkParkFun = () => {
    if (isParking.checked) {
        parkkingSlots.setAttribute("required", "true");
        parkPrice.setAttribute("required", "true");
    } else {
        parkkingSlots.removeAttribute("required");
        parkPrice.removeAttribute("required");
    }
};

isParking.addEventListener("change", checkParkFun);
checkParkFun(); // initial call

// ========> SELL TICKET TILL
const sellTillTimestemp = () => {
    let sellTill = null;
    if (eventToTime?.value && eventToDate?.value) {
        sellTill = `${eventToDate?.value}T${eventToTime?.value}`;
    } else if (eventToTime?.value && eventFromdate?.value) {
        sellTill = `${eventFromdate?.value}T${eventToTime?.value}`;
    }
    return sellTill;
};

const handelSellTillDate = (initialCall = false) => {
    if ((eventToDate?.value || eventFromdate?.value) && eventToTime?.value) {
        const maxRage = sellTillTimestemp();
        const currentDateTime = new Date();

        if (!maxRage) {
            console.warn("MaxRange value not recived");
            return;
        }

        const maxRangeDate = new Date(maxRage);

        if (maxRangeDate.getTime() > currentDateTime.getTime()) {
            if (initialCall === false || initialCall?.target) {
                sellTillInp.value = "";
            }
            sellTillInp.max = maxRage;

            // set min to current local datetime
            currentDateTime.setMinutes(
                currentDateTime.getMinutes() -
                    currentDateTime.getTimezoneOffset(),
            );
            sellTillInp.min = currentDateTime.toISOString().slice(0, 16);

            sellTillInp.readOnly = false;
            sellTillInp.style.opacity = 1;
        } else {
            // Past event: lock sell-till to max range but keep enabled for submit
            sellTillInp.readOnly = true;
            sellTillInp.style.opacity = 0.5;
            sellTillInp.value = maxRage;
        }
    } else {
        sellTillInp.readOnly = true;
        sellTillInp.style.opacity = 0.5;
        sellTillInp.value = "";
    }
};
handelSellTillDate(true);
eventToTime?.addEventListener("change", handelSellTillDate);
eventFromdate?.addEventListener("change", handelSellTillDate);
eventToDate?.addEventListener("change", handelSellTillDate);

// =========== PHP DEV JS =============
function confirmDelete() {
    const form = document.getElementById("delete-event-form");
    const formData = new FormData(form);
    const deleteBtn = document.querySelector("#deleteEventActionBtn");

    // Show loader
    if (deleteBtn) {
        deleteBtn.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
        deleteBtn.disabled = true;
    }

    fetch(form.action, {
        method: "POST",
        body: formData,
        headers: {
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
        },
    })
        .then((response) => {
            if (!response.ok) {
                return response.json().then((data) => {
                    throw new Error(data.message || "Server error");
                });
            }
            return response.json();
        })
        .then((data) => {
            if (data.success) {
                // Success - show notification and redirect
                createNotification("success", data.message, "");
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            } else {
                // Error - show notification
                createNotification("error", data.message, "");
                // Reset button and close modal
                deleteBtn.innerHTML = "Delete Event";
                deleteBtn.disabled = false;
                bootstrap.Modal.getInstance(
                    document.getElementById("deleteModal"),
                ).hide();
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            createNotification(
                "error",
                error.message || "Something went wrong. Please try again.",
                "",
            );
            // Reset button and close modal
            deleteBtn.innerHTML = "Delete Event";
            deleteBtn.disabled = false;
            bootstrap.Modal.getInstance(
                document.getElementById("deleteModal"),
            ).hide();
        });
}
