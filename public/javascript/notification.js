// ==> Create Notification Holder
const mywrapper = document.querySelector(".wrapper");
const createHolder = () => {
  const notificationHolder = document.createElement("div");
  notificationHolder.className = "notification-container";
  mywrapper.appendChild(notificationHolder);
};
if (mywrapper) createHolder();
// -----------------------------

const notificationHolder = document.querySelector(".notification-container");

// ==> Notification Timer Fun()
const createTimer = (notification) => {
  const count = notificationHolder.childElementCount;

  const delay = 6000 + parseInt(`${count * 200}`);

  setTimeout(() => {
    notification.classList.remove("show");
    notification.classList.add("hide");

    setTimeout(() => {
      notification.remove();
      if (notificationHolder.childElementCount == 0) {
        notificationHolder.style.display = "none";
      }
    }, 500);
  }, delay);
};

// ==> Notification Types Icons
const typeIcons = {
  success: "fa-regular fa-circle-check",
  info: "fa-solid fa-circle-info",
  warning: "fa-solid fa-triangle-exclamation",
  error: "fa-solid fa-triangle-exclamation",
};

// ==> Notification Types Classes
const typeClass = {
  success: "alert-success",
  info: "alert-primary",
  warning: "alert-warning",
  error: "alert-danger",
};

// ===> Create Notification
const createNotification = (type, boldmsg = "", msg = "") => {
  if (!notificationHolder) return;
  notificationHolder.style.display = "block";
  const notification = document.createElement("div");

  notification.classList.add(
    "alert",
    typeClass[type] ? typeClass[type] : "alert-dark",
    "alert-dismissible",
    "fade",
    "show"
  );

  let icon = typeIcons[type] ? typeIcons[type] : "fa-solid fa-font-awesome";

  let bMsg = boldmsg ? `<b>${boldmsg}</b>` : "";
  notification.role = "alert";
  const notificationData = `
                <i class="${icon} me-1"></i> ${bMsg} ${msg}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
  notification.innerHTML = notificationData;

  notificationHolder.insertBefore(notification, notificationHolder.firstChild);

  createTimer(notification);
};

// --------------
// types= >[success, info, warning, error]
// --> use it like this
// createNotification("type", "your bold msg", "discription");
// ---------------
