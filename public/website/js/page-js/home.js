// ===============================
// ==> Hero Swiper
// ===============================
const heroSwiper = new Swiper(".heroSwiper", {
  loop: true,
  autoHeight: true,
  speed: 1000,
  autoplay: {
    delay: 2500,
    disableOnInteraction: true,
  },
  // navigation: {
  //   nextEl: ".swiper-button-next",
  //   prevEl: ".swiper-button-prev",
  // },
  pagination: {
    el: ".swiper-pagination",
    dynamicBullets: true,
    clickable: true,
  },
});

// ===============================
// ==> Info Swiper
// ===============================
const infoSlider = new Swiper(".infoSlider", {
  loop: true,
  autoHeight: true,
  speed: 1000,
  autoplay: {
    delay: 2500,
    disableOnInteraction: true,
  },
  spaceBetween: 20,
  slidesPerView: 1,
   breakpoints: {
    1024: {
      slidesPerView: 2,
    },
  },
});

// ===============================
// ==> Active Event Slider
// ===============================
const activeEventsSlider = new Swiper(".activeEventsSlider", {
  loop: true,
  autoHeight: true,
  speed: 1000,
  autoplay: {
    delay: 2500,
    disableOnInteraction: true,
  },
  spaceBetween: 30,
  grabCursor: true,
  pagination: {
    el: ".swiper-pagination",
    dynamicBullets: true,
    clickable: true,
  },
  slidesPerView: 2,
  breakpoints: {
    1024: {
      slidesPerView: 3,
    },
  },
});

// ===============================
// ==> TODAY DATE GET FUN
// ===============================
const dateBox = document.querySelector(".js-todays-date");
const seTodaysDate = () => {
  const date = new Date();
  const day = date.getDate();
  const month = date.toLocaleString("default", { month: "long" });
  const year = date.getFullYear();
  if (dateBox) dateBox.innerHTML = `${day}-${month},<br> ${year}`;
};

if (dateBox) seTodaysDate();

// ===============================
// ==> Home Gallery Load More
// ===============================
document.addEventListener("DOMContentLoaded", () => {
  const galleryConfig = window.HOME_GALLERY;
  const loadMoreBtn = document.getElementById("homeGalleryLoadMoreBtn");
  const loadMoreWrap = document.getElementById("homeGalleryLoadMoreWrap");
  const galleryGrid = document.getElementById("homeGalleryGrid");

  if (!galleryConfig || !loadMoreBtn || !galleryGrid) return;

  let offset = Number(galleryConfig.initialCount || 0);
  let isLoading = false;

  const updateButtonVisibility = () => {
    if (!loadMoreWrap) return;

    if (offset >= Number(galleryConfig.totalCount || 0)) {
      loadMoreWrap.style.display = "none";
    }
  };

  loadMoreBtn.addEventListener("click", async () => {
    if (isLoading) return;

    isLoading = true;
    loadMoreBtn.disabled = true;
    loadMoreBtn.textContent = "Loading...";

    try {
      const response = await fetch(
        `${galleryConfig.loadMoreUrl}?offset=${encodeURIComponent(offset)}`,
        {
          headers: {
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
          },
        }
      );

      if (!response.ok) {
        throw new Error("Failed to load more images.");
      }

      const data = await response.json();

      if (data.html) {
        galleryGrid.insertAdjacentHTML("beforeend", data.html);
      }

      offset = Number(data.next_offset || offset);

      if (!data.has_more || !data.loaded_count) {
        updateButtonVisibility();
      }
    } catch (error) {
      console.error("Home gallery load more failed:", error);
    } finally {
      isLoading = false;
      loadMoreBtn.disabled = false;
      loadMoreBtn.textContent = "Load More Images";
      updateButtonVisibility();
    }
  });

  updateButtonVisibility();
});

// ===============================
// ==> Past Events Load More
// ===============================
document.addEventListener("DOMContentLoaded", () => {
  const pastEventsConfig = window.HOME_PAST_EVENTS;
  const loadMoreBtn = document.getElementById("homePastEventsLoadMoreBtn");
  const loadMoreWrap = document.getElementById("homePastEventsLoadMoreWrap");
  const eventsGrid = document.getElementById("homePastEventsGrid");

  if (!pastEventsConfig || !loadMoreBtn || !eventsGrid) return;

  let offset = Number(pastEventsConfig.initialCount || 0);
  let isLoading = false;

  const updateButtonVisibility = () => {
    if (!loadMoreWrap) return;

    if (offset >= Number(pastEventsConfig.totalCount || 0)) {
      loadMoreWrap.style.display = "none";
    }
  };

  loadMoreBtn.addEventListener("click", async () => {
    if (isLoading) return;

    isLoading = true;
    loadMoreBtn.disabled = true;
    loadMoreBtn.textContent = "Loading...";

    try {
      const response = await fetch(
        `${pastEventsConfig.loadMoreUrl}?offset=${encodeURIComponent(offset)}`,
        {
          headers: {
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
          },
        }
      );

      if (!response.ok) {
        throw new Error("Failed to load more past events.");
      }

      const data = await response.json();

      if (data.html) {
        eventsGrid.insertAdjacentHTML("beforeend", data.html);
      }

      offset = Number(data.next_offset || offset);

      if (!data.has_more || !data.loaded_count) {
        updateButtonVisibility();
      }
    } catch (error) {
      console.error("Past events load more failed:", error);
    } finally {
      isLoading = false;
      loadMoreBtn.disabled = false;
      loadMoreBtn.textContent = "Load More";
      updateButtonVisibility();
    }
  });

  updateButtonVisibility();
});
