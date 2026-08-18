// -- ${Global Values}
const body = document.body;
let topHeader = document.querySelector(".header");
let dashContent = document.querySelector(".wrapper");
let headerHeight;
if (topHeader) headerHeight = `${topHeader.offsetHeight}px`;
// -----------------------------------------------

// ================================================
// ==========> SIDEBAR TOGGLER  🥗
// ================================================
const sidebar = body.querySelector(".side-nav");
const sidebarToggle = body.querySelector(".sidebar-toggle");
// let getStatus = localStorage.getItem("status");
// if (getStatus && getStatus === "close") {
//   sidebar.classList.toggle("close");
// }

// ==> Setting dynamic height as per header height
if (sidebar) sidebar.style.height = `calc(100dvh - ${headerHeight})`;
if (dashContent) dashContent.style.minHeight = `calc(100dvh - ${headerHeight})`;

sidebarToggle?.addEventListener("click", () => {
  sidebar.classList.toggle("close");
  if (sidebar.classList.contains("close")) {
    // localStorage.setItem("status", "close");
    sidebarToggle.innerHTML = `<i class="fa-solid fa-xmark"></i>`;
  } else {
    // localStorage.setItem("status", "open");
    sidebarToggle.innerHTML = `<i class="fa-solid fa-bars-staggered"></i>`;
  }
});

// ================================================
// ==========> DARK / LITE MODE TOGGLER  🥗
// ================================================
const themeToggleBtn = document.querySelector("header .mode .mode-toggle");
let Logo = document.querySelector("header .logo");

const setTheme = (theme) => {
  if (theme === "dark") {
    document.documentElement.classList.add("dark");
    localStorage.setItem("theme", "dark");
    if (Logo) Logo.setAttribute("src", "/images/logo-w.svg");
  } else {
    document.documentElement.classList.remove("dark");
    localStorage.setItem("theme", "light");
    if (Logo) Logo.setAttribute("src", "/images/logo.svg");
  }
};

// Avoid redeclaration of savedTheme
const savedTheme = localStorage.getItem("theme") || "dark";
setTheme(savedTheme);

// Toggle theme on button click
if (themeToggleBtn)
  themeToggleBtn.addEventListener("click", () => {
    const currentTheme =
      localStorage.getItem("theme") === "dark" ? "light" : "dark";
    setTheme(currentTheme);
  });

// ================================================
// ====> FIX HEADER ENPTY DIV HEIGHT SETTING  🥗
// ================================================
let headerSpcFiller = document.querySelector(".top-space");
if (headerSpcFiller) headerSpcFiller.style.height = headerHeight;

// ================================================
// ==> FEATURED BTN FUNCTION()  🥗
// ================================================
let featuredBtn = document.querySelectorAll(".featured");

featuredBtn.forEach((e) => {
  e.onclick = () => {
    const fonctionState = e.classList.toggle("active");
    e.innerHTML = fonctionState
      ? `<i class="fa-solid fa-star"></i>`
      : `<i class="fa-regular fa-star"></i>`;
  };
});

// ================================================
// ==> FORM VALIDATION  🥗
// ================================================
(() => {
  "use strict";
  // Fetch all the forms we want to apply custom Bootstrap validation styles to
  const forms = document.querySelectorAll(".needs-validation");

  // Loop over them and prevent submission
  Array.from(forms).forEach((form) => {
    form.addEventListener(
      "submit",
      (event) => {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }

        form.classList.add("was-validated");
      },
      false
    );
  });
})();

// ================================================
// ==> Upload File Size Restriction  🥗
// ================================================
const inpFiles = document.querySelectorAll("input[type=file]");
let MAX_SIZE_KB = 200; // Max size in kilobytes
inpFiles.forEach((input) => {
  input.addEventListener("change", (event) => {
    const file = event.target.files[0];
    if (!file) return;
    let getSize = event.target.dataset.maxFileSizeKb;
    if (getSize) {
      MAX_SIZE_KB = getSize;
    }
    const sizeInKB = file.size / 1024; // Convert bytes to KB

    if (sizeInKB > MAX_SIZE_KB) {
      if (typeof createNotification === "function") {
        createNotification(
          "error",
          `File size exceeds ${MAX_SIZE_KB}KB. Please upload a smaller file.`,
          ""
        );
      }
      input.value = ""; // Reset file input
    }
  });
});

// ================================================
// ==> Upload Btn  🥗
// ================================================
let uploadHolders = document.querySelectorAll(".upload-box");
const globalMediaDeleteModalElement = document.getElementById(
  "globalMediaDeleteModal",
);
const globalMediaDeleteConfirmBtn = document.getElementById(
  "globalMediaDeleteConfirmBtn",
);
const globalMediaDeleteMessage = document.getElementById(
  "globalMediaDeleteMessage",
);
let pendingMediaDeleteTrigger = null;

const hasSavedMedia = (removeBtn) =>
  removeBtn?.dataset.hasMedia === "1" &&
  Boolean(removeBtn?.dataset.deleteUrl);

const openGlobalMediaDeleteModal = (removeBtn) => {
  if (!globalMediaDeleteModalElement || !hasSavedMedia(removeBtn)) return;

  pendingMediaDeleteTrigger = removeBtn;
  const label = removeBtn.dataset.mediaLabel || "media";

  if (globalMediaDeleteMessage) {
    globalMediaDeleteMessage.textContent = `Are you sure you want to delete this ${label}?`;
  }

  const showConfirmation = () =>
    bootstrap.Modal.getOrCreateInstance(globalMediaDeleteModalElement).show();
  const parentModal = removeBtn.closest(".modal.show");

  if (parentModal) {
    parentModal.addEventListener("hidden.bs.modal", showConfirmation, {
      once: true,
    });
    bootstrap.Modal.getInstance(parentModal)?.hide();
  } else {
    showConfirmation();
  }
};

uploadHolders.forEach((ele) => {
  let UploadBtn = ele.querySelector("button");
  let inputField = ele.querySelector("input");
  let preview = ele.querySelector(".previewBox .preview");
  let removeBtn = ele.querySelector(".previewBox span");
  let defaultImg = preview.getAttribute("src");

  if (removeBtn && hasSavedMedia(removeBtn)) {
    removeBtn.style.display = "inline-block";
  }

  // Trigger Input field from btn
  if (UploadBtn) {
    UploadBtn.onclick = () => inputField.click();
  }

  //Reset Preview
  const resetPreview = () => {
    inputField.value = "";
    preview.setAttribute("src", defaultImg);
    if (preview.tagName === "VIDEO") preview.load();
    if (removeBtn) {
      removeBtn.style.display = hasSavedMedia(removeBtn)
        ? "inline-block"
        : "none";
    }
  };

  // Display Preview Function
  inputField.onchange = (event) => {
    if (event.target.files.length == 0) {
      resetPreview();
      return;
    }
    const getURL = URL.createObjectURL(event.target.files[0]);
    preview.setAttribute("src", getURL);
    if (removeBtn) {
      removeBtn.style.display = "inline-block";
    }
  };

  if (removeBtn) {
    removeBtn.onclick = (event) => {
      event.preventDefault();

      if (inputField.files?.length) {
        resetPreview();
        return;
      }

      if (hasSavedMedia(removeBtn)) {
        openGlobalMediaDeleteModal(removeBtn);
        return;
      }

      resetPreview();
    };

    removeBtn.addEventListener("keydown", (event) => {
      if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        removeBtn.click();
      }
    });
  }
});

globalMediaDeleteModalElement?.addEventListener("hidden.bs.modal", () => {
  pendingMediaDeleteTrigger = null;
});

globalMediaDeleteConfirmBtn?.addEventListener("click", async function () {
  if (!pendingMediaDeleteTrigger?.dataset.deleteUrl) return;

  const originalText = this.innerHTML;
  this.disabled = true;
  this.innerHTML =
    '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';

  try {
    const response = await fetch(
      pendingMediaDeleteTrigger.dataset.deleteUrl,
      {
        method: "DELETE",
        headers: {
          "X-CSRF-TOKEN": document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content"),
          Accept: "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
      },
    );
    const data = await response.json().catch(() => ({}));

    if (!response.ok || !data.success) {
      throw new Error(data.message || "Failed to delete media.");
    }

    bootstrap.Modal.getInstance(globalMediaDeleteModalElement)?.hide();

    if (typeof createNotification === "function") {
      createNotification("success", data.message, "");
    }

    setTimeout(() => window.location.reload(), 400);
  } catch (error) {
    console.error("Media deletion failed:", error);

    if (typeof createNotification === "function") {
      createNotification(
        "error",
        error.message || "Failed to delete media.",
        "",
      );
    }
  } finally {
    this.disabled = false;
    this.innerHTML = originalText;
  }
});

// ================================================
// ==> PASSWORD SHOW / HIDE FUNCTION  🥗
// ================================================
let PassBox = document.querySelectorAll(".passBox");
PassBox.forEach((e) => {
  let PassInput = e.querySelector("input");
  let PassBtn = e.querySelector("button");
  let PassBtni = e.querySelector("button i");

  PassBtn.onclick = () => {
    // toggle the type attribute
    const passType =
      PassInput.getAttribute("type") === "password" ? "text" : "password";

    PassInput.setAttribute("type", passType);

    // toggle the eye icon
    PassBtni.classList.toggle("fa-eye");
    PassBtni.classList.toggle("fa-eye-slash");
  };
});

// ================================================
// ==> NAV LINK ACTIVE FUNCTION()  🥗
// ================================================
//Scroll to top the active menue
const scrollActiveFun = (ele) => {
  const sidebar = document.querySelector(".side-nav .menu-items");
  const activeItemRect = ele.getBoundingClientRect();
  const sidebarRect = sidebar.getBoundingClientRect();

  const isOutOfView =
    activeItemRect.top < sidebarRect.top ||
    activeItemRect.bottom > sidebarRect.bottom;

  if (isOutOfView) {
    ele.scrollIntoView({
      behavior: "smooth", // Smooth scrolling
      block: "start", // Align the item at the top of the container
    });
  }
};

//Active the dropdown
const dropdownCheckerfun = () => {
  const Dropdowns = document.querySelectorAll(".side-nav .nav-ul .dropdown");

  for (const e of Dropdowns) {
    const activeItem = e.querySelector(".nav-link.active");
    if (activeItem) {
      e.classList.add("active");
      const toggleBtn = e.querySelector(".dropdown-toggle");
      toggleBtn.classList.add("show");
      toggleBtn.setAttribute("aria-expanded", "true");
      const dropdownMenu = e.querySelector(".dropdown-menu");
      dropdownMenu.classList.add("show");
      dropdownMenu.setAttribute("aria-expanded", "true");
      dropdownMenu.setAttribute("style", "display: block;");

      //Now call the scroll function
      scrollActiveFun(toggleBtn);
      break;
    }
  }
};

//Active Nav Item based on pg URL
const NavLinkActivatorFun = () => {
  
  const navLinks = document.querySelectorAll(".navJS");
  
  for (const e of navLinks) {
   
    // if (e.href.includes(`${currentURL}`)) {
    if (e.href === window.location.href) {
      e.classList.add("active");
      
      let chotu = document.querySelector(".side-nav .navJS.active");
      
      //check it active link is in dropdown?
      if (
        e.parentElement.parentElement.classList.contains("dropdown-menu") &&
        chotu
      ) {
        dropdownCheckerfun();
        break;
      } else if (chotu) {
        scrollActiveFun(chotu);
      }
      break;
    }
  }
};
NavLinkActivatorFun();
// document.addEventListener("DOMContentLoaded", () => {
//     console.log("DOM Ready, navJS count:", document.querySelectorAll(".navJS").length);
// });

// window.addEventListener("load", () => {
//     console.log("Window Loaded, navJS count:", document.querySelectorAll(".navJS").length);
// });

// ---------------------------------------
//      Fun() PRELOADER ðŸ¥—
// ---------------------------------------
let loader = document.querySelector(".preloder");
window.addEventListener("load", function () {
  loader.style.display = "none";
});

// ---------------------------------------
//      Fun() HTML Processer 🥗
// ---------------------------------------
const HTMLProcesser = (content) => {
  let lines = content.split(/\n+/);

  // Process each line for bold, italic, and underline
  let processedContent = lines
    .filter((line) => line.trim() !== "")
    .map((line) => {
      // Bold: **text** or __text__
      line = line.replace(/(\*\*|__)(.*?)\1/g, "<strong>$2</strong>");

      // Italic: *text* or _text_
      line = line.replace(/(\*|_)(.*?)\1/g, "<em>$2</em>");

      // Underline: ~~text~~
      line = line.replace(/~~(.*?)~~/g, "<u>$1</u>");

      // Wrap line in <p> tags
      return `<p>${line}</p>`;
    })
    .join("");

  return processedContent;
};
// =====> How to attach the editor?
// HTMLProcesser(textarea.value);
// Ex.
// let editor = document.querySelector("#editor");
// let submitBtn = document.querySelector("button");
// submitBtn.addEventListener("click", () => {
//   let content = editor.value;
//   let processedContent = HTMLProcesser(content);
// });

// ---------------------------------------
//      Fun() Event Create Selection 🥗
// ---------------------------------------
const eventTabRadioBtns = document.querySelectorAll(
  '.form-tabs input[name="choose-what"]'
);
const RadioNew = Array.from(eventTabRadioBtns).find((radio) => radio.value === "create-new");
const RadioDuplicate = Array.from(eventTabRadioBtns).find((radio) => radio.value === "duplicate-event");
const newEventForm = document.querySelector(".new-event-form");
const duplicateEventForm = document.querySelector(".duplicate-event-form");

const takeEventDesign = () => {
  if (!newEventForm || !duplicateEventForm) return;
  const isNewSelected = RadioNew ? RadioNew.checked : !RadioDuplicate?.checked;
  newEventForm.classList.toggle("d-none", !isNewSelected);
  duplicateEventForm.classList.toggle("d-none", isNewSelected);
};

// Initialize view based on the current selection
if (eventTabRadioBtns.length) {
  takeEventDesign();

  // Use event delegation or direct listeners
  for (const radio of eventTabRadioBtns) {
    radio.addEventListener("change", takeEventDesign);
  }
}

// -------------------------------------------------------
//   Fun() Create New Event Booking Stsyem Selection 🥗
// -------------------------------------------------------
// Select the radio buttons and the dropdown
const radioButtons = document.querySelectorAll('input[name="ticket-system"]');
const selectElement = document.getElementById("dsc48");
const selectPatternBox = document.querySelector(".choose-pattern-fd54");

// Add change event listeners to the radio buttons
radioButtons.forEach((radio) => {
  radio.addEventListener("change", () => {
    if (radio.value === "seat-booking-system" && radio.checked) {
      selectElement.setAttribute("required", "");
      selectPatternBox.classList.remove("d-none");
    } else {
      selectElement.removeAttribute("required");
      selectPatternBox.classList.add("d-none");
    }
  });
});
