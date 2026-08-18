import { createOfferBar, offerBarFun, debounce } from "../../offer-bar.js";

// =============================
// ---->  Manage Offer Bar
// =============================
// ======> Variables
// --Discount Slabs
const slabs = [
  {
    minTickets: 2,
    offer: 10,
  },
  {
    minTickets: 10,
    offer: 15,
  },
  {
    minTickets: 15,
    offer: 20,
  },
];

//--  Main Comp where to creat offer bar
const offerComp = document.querySelector(".offer-comp");

// CREATE DEBOUNCED FUNCTION ONCE
const debouncedOfferBarFun = debounce((selectedTickets) => {
  offerBarFun(selectedTickets, slabs, offerComp.querySelector(".offer-bar"));
}, 1); // just pass [slabs] array or obj from php

// INITIAL CALL
if (offerComp) {
  createOfferBar(offerComp, slabs); // just pass [slabs] array or obj from php
  debouncedOfferBarFun(0);
}

// ===> 🔴 Use this fun() to update offer bar on total seat selected no. change
// debouncedOfferBarFun(pass-total-seat-selected-number);

// ================xxxxxxxxxxxxxxxxxxxx Dummy trigger btns
// let selectedTickets = 0;
// let dmy = document.querySelector("#dummy-btn");
// dmy.addEventListener("click", function () {
//   if (selectedTickets >= 20) return;
//   selectedTickets = selectedTickets + 1;
//   dmy.innerHTML = `+ ${selectedTickets} ticket`;
//   debouncedOfferBarFun(selectedTickets);
// });

// let dmy2 = document.querySelector("#dummy-btn2");
// dmy2.addEventListener("click", function () {
//   if (selectedTickets <= 0) return;
//   selectedTickets = selectedTickets - 1;
//   dmy.innerHTML = `+ ${selectedTickets} ticket`;
//   debouncedOfferBarFun(selectedTickets);
// });