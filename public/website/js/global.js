const currentURL = window.location.pathname;
// ---------------------------------------
//         FUN() WINDOW ONSCROLL
// ---------------------------------------
window.addEventListener("scroll", () => {
  // -- Call Sticky Nav
  StickNavOnscroll();
});

// ---------------------------------------
//    SHOW / HIDE POPUPS & OFFCANVAS 🥗
// ---------------------------------------
// Function to Show the targeted Element
const showElement = (iD) => {
  let Target = document.querySelector(iD);
  Target.classList.toggle("show");
};

// ====> Function to Close the currently active Offcanvas / popup
const popupContainer = document.querySelectorAll(".pop-boxJS");
popupContainer.forEach((e) => {
  e.addEventListener("click", (event) => {
    const popup = e.querySelector(".popJS");
    if (!popup.contains(event.target) || event.target.closest(".btn-close")) {
      e.classList.remove("show");
      e.classList.add("hide");
      setTimeout(() => {
        e.classList.remove("hide");
      }, 1000);
    }
  });
});

// ===> Trigger Function To close modal of hide offcanvus
const clodeIt =(id)=>{
  if(!id){
    console.error("Id is required to clode modal or offcanvus.");
  }
  let e = document.querySelector(id);
  if(!e){
    console.error(`No such Element found with id ${id}`);
  }
  e.classList.remove("show");
  e.classList.add("hide");
  setTimeout(() => {
    e.classList.remove("hide");
  }, 1000);
}

// ==============================================
// Function ()=> POP UP MSG BOX
// ==============================================
const MSGbox = document.querySelectorAll(".popjs");

MSGbox?.forEach((msgBox) => {
  let closBtn = msgBox.querySelector("button.closeit");
  closBtn.addEventListener("click", () => {
    msgBox.classList.add("hide");
  });
});

// ---------------------------------------
//        FUN() BACK TO TOP 🥗
// ---------------------------------------
const topFunction = () => {
  document.body.scrollTop = 0; // For Safari
  document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
};

// ---------------------------------------
//      FUN() STICK NAV ON SCROLL 🥗
// ---------------------------------------
let NAVBAR = document.querySelector("header nav");
let navHeight = NAVBAR.offsetHeight;
let emptyNAV = document.querySelector("header .emptyNav");

const StickNavOnscroll = () => {
  // let heroSec = document.querySelector(".hero-sec");
  // let newsSec = document.querySelector(".news-sec");
  // let spaceHight = heroSec.offsetHeight + newsSec.offsetHeight;

  if (document.documentElement.scrollTop > 300) {
    NAVBAR.classList.add("fixTop");
    emptyNAV.style.height = `${navHeight}px`;
  } else {
    NAVBAR.classList.remove("fixTop");
    emptyNAV.style.height = "0px";
  }
};

// ---------------------------------------
//         FUN() ACTIVE NAV LINK 🥗
// ---------------------------------------
const navLinks = document.querySelectorAll(".navJS");
const myhome = document.querySelectorAll(".homejs");

navLinks.forEach((link) => {
  if (link.href.includes(`${currentURL}`) && currentURL != "/") {
    link.classList.add("active");
  }

  if (currentURL == "/") {
    link.classList.remove("active");
    myhome.forEach((e) => e.classList.add("active"));
  }
});

// ---------------------------------------
//   FUN() GET CURRENT YEAR 🥗
// ---------------------------------------
const date = new Date();
const year = date.getFullYear();
document.getElementById("year").innerHTML = year;

// ---------------------------------------
//        Fun() FORM VALIDATION 🥗
// ---------------------------------------
(() => {
  "use strict";

  // Fetch all the forms we want to apply custom validation styles to
  const forms = document.querySelectorAll(".needs-validation");

  // Loop over them and prevent submission
  Array.from(forms).forEach((form) => {
    form.addEventListener(
      "submit",
      (event) => {
        if (!form.checkValidity()) {
          console.log(!form.checkValidity());
          event.preventDefault();
          event.stopPropagation();
        }

        form.classList.add("was-validated");
      },
      false
    );
  });
})();

// ==============================================
// Function ()=> Preloader
// ==============================================
let loader = document.getElementById("preloader");
window.addEventListener("load", () => {
  loader.style.display = "none";
  document.body.style.overflow = "auto";
});