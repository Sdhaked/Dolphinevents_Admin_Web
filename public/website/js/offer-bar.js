// --------------------------------------
// ========> Debounce Utility 🥗 -------
// --------------------------------------
export const debounce = (func, delay = 100) => {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), delay);
    };
};
// --------------------------------------
// ========> TEXT TO SPEACH VOICE 🥗 ---
// --------------------------------------
const speak = (text) => {
    const utter = new SpeechSynthesisUtterance(text);
    utter.lang = "en-US";

    // Set pitch and rate for a sweeter, faster tone
    utter.pitch = 1.3; // higher = more lively/sweet
    utter.rate = 1; // faster speaking
    utter.volume = 1; // full volume

    function setVoice() {
        const voices = speechSynthesis.getVoices();

        // Filter for English female voices
        const femaleVoices = voices.filter(
            (voice) =>
                voice.lang.startsWith("en") &&
                /female|samantha|zira|google|amy|emma|joanna/i.test(voice.name),
        );

        if (femaleVoices.length > 0) {
            utter.voice = femaleVoices[0];
        } else {
            // fallback to any English voice
            utter.voice =
                voices.find((voice) => voice.lang.startsWith("en")) || null;
        }

        // 🔥 Cancel any ongoing or queued speech
        speechSynthesis.cancel();

        // ✅ Start fresh
        speechSynthesis.speak(utter);
    }

    // Wait for voices to load (only needed once per session)
    if (speechSynthesis.getVoices().length === 0) {
        speechSynthesis.onvoiceschanged = setVoice;
    } else {
        setVoice();
    }
};

// --------------------------------------
// ========> SETUP CANVAS 🥗 ------------
// --------------------------------------
const canvas = document?.getElementById("confetti-canvas");
const ctx = canvas?.getContext("2d");

canvas.width = window.innerWidth;
canvas.height = window.innerHeight;

const confetti = [];

function random(min, max) {
    return Math.random() * (max - min) + min;
}

// === Create particles at (x, y)
function createConfettiParticles(x, y, count = 300) {
    for (let i = 0; i < count; i++) {
        const angle = random(0, 2 * Math.PI); // 360° spread
        const velocity = random(3, 10);
        const vx = Math.cos(angle) * velocity;
        const vy = Math.sin(angle) * velocity;

        confetti.push({
            x,
            y,
            vx,
            vy,
            gravity: 0.1,
            drag: 0.98,
            rotation: random(0, 2 * Math.PI),
            spin: random(-0.2, 0.2),
            size: random(6, 12),
            shape: Math.random() > 0.5 ? "rect" : "circle",
            color: `hsl(${Math.random() * 360}, 100%, ${random(50, 70)}%)`,
            opacity: 1,
            decay: random(0.006, 0.01),
        });
    }
}

// === Update particle physics
function updateConfetti() {
    for (let i = confetti.length - 1; i >= 0; i--) {
        const c = confetti[i];
        c.vx *= c.drag;
        c.vy *= c.drag;
        c.vy += c.gravity;
        c.x += c.vx;
        c.y += c.vy;
        c.rotation += c.spin;
        c.opacity -= c.decay;

        if (c.opacity <= 0) {
            confetti.splice(i, 1);
        }
    }
}

// === Draw each frame
function drawConfetti() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    confetti.forEach((c) => {
        ctx.save();
        ctx.globalAlpha = Math.max(0, c.opacity);
        ctx.translate(c.x, c.y);
        ctx.rotate(c.rotation);

        ctx.fillStyle = c.color;
        if (c.shape === "rect") {
            ctx.fillRect(-c.size / 2, -c.size / 2, c.size, c.size * 0.6);
        } else {
            ctx.beginPath();
            ctx.arc(0, 0, c.size / 2, 0, 2 * Math.PI);
            ctx.fill();
        }

        ctx.restore();
    });
}

// === Animation loop
function animateConfetti() {
    updateConfetti();
    drawConfetti();
    requestAnimationFrame(animateConfetti);
}
animateConfetti();

// === Reusable function: Confetti around any DOM element
function blastConfettiFromElement(element, particleCount = 300) {
    const rect = element.getBoundingClientRect();
    const x = rect.left + rect.width / 2;
    const y = rect.top + rect.height / 2;
    createConfettiParticles(x, y, particleCount);
}

// === Make available globally (optional)
window.blastConfettiFromElement = blastConfettiFromElement;

// === Optional: Update canvas size on window resize
window.addEventListener("resize", () => {
    if (canvas) {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    } else return;
});

// --------------------------------------
// ========> OFFER BAR 🥗 --------------
// --------------------------------------
// ===> Create Offer Bar
export const createOfferBar = (main, slabs) => {
    const offerdiv = document.createElement("div");
    offerdiv.classList.add("offer-bar", "overflow-hidden");
    offerdiv.setAttribute("data-aos", "fade-up");
    offerdiv.innerHTML = `
    <div class="offer-content">
      <div class="offer-text" id="offerText"></div>
      <div class="progress-container">
        <div class="offer-zone">
          <div class="progress-track">
            <div class="progress-fill" id="progressFill"></div>
          </div>
          <div class="offer-tags"></div>
        </div>
        <div class="progress-labels"></div>
      </div>
    </div>
  `;
    main.appendChild(offerdiv);

    // Call layout creator
    createOfferBarFun(main, slabs);
};

// ===> Setup Offer Bar Layout (Static Elements)
const createOfferBarFun = (main, slabs) => {
    const lastSlab = slabs[slabs.length - 1];
    const requiredTickets = lastSlab.minTickets || 0;

    // Labels (first and last slab)
    const progressLabels = main.querySelector(".progress-labels");
    if (progressLabels) {
        // progressLabels.innerHTML = slabs
        //   .map((slab, i) => {
        //     if (i === 0 || i === slabs.length - 1) {
        //       if (i === 0) {
        //         return `<span>${i} Tickets</span>`;
        //       }
        //       return `<span>${slab.minTickets} Tickets</span>`;
        //     }
        //     return "";
        //   })
        //   .join("");
        progressLabels.innerHTML = `<span>0 Tickets</span> <span>${requiredTickets} Tickets</span>`;
    }

    // Offer tags
    const offertags = main.querySelector(".offer-tags");
    if (offertags) {
        const fragment = document.createDocumentFragment();
        slabs.forEach((ele, i) => {
            const offertagsSpan = document.createElement("span");
            const percentage = (ele.minTickets / requiredTickets) * 100;
            offertagsSpan.classList.add("offer-value", `offer-v-${i}`);
            offertagsSpan.style.left = `calc(${Math.min(percentage, 100)}% - 0.8rem)`;
            offertagsSpan.innerHTML = `<span>${ele.offer}%</span>`;
            fragment.appendChild(offertagsSpan);
        });
        offertags.appendChild(fragment);
    }
};

// ===> Cached State to Avoid Re-renders
const previousOfferState = {
    currentTickets: null,
    activeSlabs: [],
    progressWidth: null,
};

// ===> Update Offer Bar
export const offerBarFun = (currentTickets, slabs, container) => {
    const offerText = container.querySelector("#offerText");
    const progressFill = container.querySelector("#progressFill");
    const lastSlab = slabs[slabs.length - 1];
    const requiredTickets = lastSlab.minTickets;

    const progress = Math.min(
        (currentTickets / requiredTickets) * 100,
        100,
    ).toFixed(2);

    if (previousOfferState.progressWidth !== progress) {
        progressFill.style.width = `${progress}%`;
        previousOfferState.progressWidth = progress;
    }

    const nextSlab = slabs.find((slab) => currentTickets < slab.minTickets);
    let newText = "";

    if (nextSlab) {
        const ticketsNeeded = nextSlab.minTickets - currentTickets;
        newText = `You're <span class="amount">${ticketsNeeded} Ticket${
            ticketsNeeded > 1 ? "s" : ""
        }</span> away from <span class="highlight">${nextSlab.offer}% off</span>!!`;
        if (container.classList.contains("completed")) {
            container.classList.remove("completed");
        }
    } else {
        const highestOffer = lastSlab.offer;
        newText = `🎉 Congratulations! <br/>You've unlocked max discount of <span class="highlight">${highestOffer}% off</span>!!`;
        container.classList.add("completed");
    }

    if (offerText.innerHTML !== newText) {
        offerText.innerHTML = newText;
    }

    // Update offer tags
    slabs.forEach((ele, i) => {
        const offertagsSpan = container.querySelector(`.offer-v-${i}`);
        const isActive = currentTickets >= ele.minTickets;
        const wasActive = previousOfferState.activeSlabs.includes(i);

        if (isActive && !wasActive) {
            offertagsSpan.classList.add("active");
            previousOfferState.activeSlabs.push(i);
            setTimeout(() => blastConfettiFromElement(offertagsSpan, 300), 300); //blast animation
            speak(`congratulations you've unlocked ${ele?.offer}% discount!`);
        } else if (!isActive && wasActive) {
            offertagsSpan.classList.remove("active");
            previousOfferState.activeSlabs =
                previousOfferState.activeSlabs.filter((idx) => idx !== i);
        }
    });

    previousOfferState.currentTickets = currentTickets;
};
