const imagesMap = new Map(); // Store images for each gallery
const galleryMaxImages = 50;
const galleryMaxImageSize = 5 * 1024 * 1024;
const galleryAllowedTypes = ["image/jpeg", "image/png", "image/jpg", "image/webp"];

const toggleSubmitButton = (showTo, hasImages) => {
    const imageContainer = document.querySelector(`${showTo}`);
    const form = imageContainer ? imageContainer.closest(".gallery-form") : null;
    const submitWrap = form ? form.querySelector(".gallery-submit-wrap") : null;

    if (submitWrap) {
        submitWrap.style.display = hasImages ? "block" : "none";
    }
};

//---> Get Images
const imgSelectorFun = (inputImgs, gForm, showTo) => {
    const imagesData = imagesMap.get(showTo) || []; // Get existing images or initialize an empty array
    let image = inputImgs.files;

    for (let i = 0; i < image.length; i++) {
        if (imagesData.length >= galleryMaxImages) {
            if (typeof createNotification === "function") {
                createNotification("error", `Maximum ${galleryMaxImages} gallery images can be uploaded at a time.`, "");
            }
            break;
        }

        if (!galleryAllowedTypes.includes(image[i].type)) {
            if (typeof createNotification === "function") {
                createNotification("error", `${image[i].name} is not a supported image type.`, "");
            }
            continue;
        }

        if (image[i].size > galleryMaxImageSize) {
            if (typeof createNotification === "function") {
                createNotification("error", `${image[i].name} must be 5MB or smaller.`, "");
            }
            continue;
        }

        imagesData.push({
            name: image[i].name,
            url: URL.createObjectURL(image[i]),
            file: image[i],
        });
    }
    imagesMap.set(showTo, imagesData); // Save the images data for this gallery

    inputImgs.value = "";
    document.querySelector(`${showTo}`).innerHTML = showImgFun(
        imagesData,
        showTo
    );
    toggleSubmitButton(showTo, imagesData.length > 0);
};

//---> Show Images
const showImgFun = (yimages, showTo) => {
    let image = "";
    if (yimages.length > 0) {
        yimages.forEach((ele, i) => {
            image += `<div class="col img-card">
        <img src="${ele.url}">
        <span onclick="deleteImgFun(${i}, '${showTo}')">
          <i class="fa-solid fa-xmark"></i>
        </span>
      </div>`;
        });
        return image;
    } else {
        return `<div>
             <h6 class="empty-hd"><i class="fa-regular fa-images"></i> <br>
                        No Product Images are uploaded</h6>
            </div>`;
    }
};

//---> Delete Image
const deleteImgFun = (i, showTo) => {
    const imagesData = imagesMap.get(showTo); // Get the images for this gallery
    imagesData.splice(i, 1); // Modify the array
    imagesMap.set(showTo, imagesData); // Update the Map with modified images
    document.querySelector(`${showTo}`).innerHTML = showImgFun(
        imagesData,
        showTo
    );
    toggleSubmitButton(showTo, imagesData.length > 0);
};

// ==========================Main Setting
let galleryForm = document.querySelectorAll(".gallery-form");
galleryForm.forEach((gForm) => {
    let uploadImgBtn = gForm.querySelector("button");
    let inputImgs = gForm.querySelector("input");
    let showTo = gForm.getAttribute("showAt");

    uploadImgBtn.addEventListener("click", () => inputImgs.click());

    inputImgs.addEventListener("change", (event) =>
        imgSelectorFun(event.target, gForm, showTo)
    );

    toggleSubmitButton(showTo, (imagesMap.get(showTo) || []).length > 0);
});
