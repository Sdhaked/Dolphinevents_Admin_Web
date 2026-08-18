// ===============================
// ==> Info Swiper
// ===============================
if (document.querySelector(".infoSlider")) {
  const infoSlider = new Swiper(".infoSlider", {
    loop: true,
    autoHeight: true,
    speed: 1000,
    autoplay: {
      delay: 2500,
      disableOnInteraction: true,
    },
  });
}

// ===============================
// ==> Timer Fun
// ===============================
const createTimer = (startDate, endDate, output) => {

  const currentDateTime = new Date();
  const diff = startDate - currentDateTime;

  // Calculation part
  let days = Math.floor(diff / (1000 * 60 * 60 * 24));
  let hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
  let minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
  let seconds = Math.floor((diff % (1000 * 60)) / 1000);

  const timer = () => {
    const interval = setInterval(() => {
      seconds--;

      if (seconds < 0) {
        seconds = 59; // Reset seconds to 59
        minutes--; // Decrement minutes
      }

      if (minutes < 0) {
        minutes = 59; // Reset minutes to 59
        hours--; // Decrement hours
      }

      if (hours < 0) {
        hours = 23; // Reset hours to 23
        days--; // Decrement days
      }
      if (days < 0) {
        clearInterval(interval); // Stop the timer
        output.innerHTML = currentDateTime < endDate ? `Event Started` : `Event Ended`;
        return;
      }

      // Display the updated time
      output.innerHTML = `<div class="t-box"><span>${days}</span> <span>Day</span></div> 
      <div class="t-box"><span>${hours
        .toString()
        .padStart(2, "0")}</span> <span>Hr</span></div>
      <div class="t-box"><span>${minutes
        .toString()
        .padStart(2, "0")}</span> <span>Min</span></div>
      <div class="t-box"><span>${seconds
        .toString()
        .padStart(2, "0")}</span> <span>Sec</span></div>`;
    }, 1000);
  };

  // Start the timer
  timer();
};

// Timer 1

//let eventEndDate = new Date("2025-09-17T12:00:00");
//if (output) createTimer(eventEndDate, output);

// Timer  2

//if (output) createTimer(eventEndDate, output2);

// =====> 🔴 ✋ Apko is code ko use karna ha apni sund mat chalana idhar
// let dateTimeString = `${GetDate.value}T${GetTime.value}`;
// let eventEndDate = new Date(dateTimeString);
// createTimer(eventEndDate, output);
