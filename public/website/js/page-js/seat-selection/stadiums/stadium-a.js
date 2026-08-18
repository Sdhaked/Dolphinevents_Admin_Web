
import {
  lwdata,
  clwdata,
  crwdata,
  rwdata,
} from "../../../wings-data.js";

const wingCreator = ({ data = [], stadium }) => {
  if (!stadium) return;
  let currentRow = "";
  let rowDiv;
  let wingBox = document.createElement("div");
  wingBox.classList.add(`${data[0]?.wing || "W"}`);

  data?.length > 0 &&
    data.forEach((item) => {
      let ticketType = String(item.ticketType || "")
        .split(" ")
        .join("-")
        .toLowerCase();
      if (ticketType || item.gap) {
        // Start a new row div if row changes OR this is the first element
        if (item.row && item.row !== currentRow) {
          if (rowDiv) {
            wingBox.append(rowDiv); // append previous row div
          }
          rowDiv = document.createElement("div");
          currentRow = item.row;
        }

        let ele;
        if (item?.gap === "gap") {
          // Gap element
          ele = document.createElement("div");
          ele.classList.add("gp");
        } else {
          // Seat checkbox
          ele = document.createElement("input");
          ele.type = "checkbox";
          ele.name = item?.wing || "W";
          ele.classList.add(ticketType);
          if (item.accent) {
            ele.style.accentColor = item.accent;
            ele.style.boxShadow = `${item.accent} 0px 0px 0px 1px`;
          }
          ele.checked = item.booked || false;
          if (item.booked) {
            ele.setAttribute("disabled", true);
          }
          ele.value = item?.id;
        }

        rowDiv.appendChild(ele);
      }
    });

  // Append the last row div
  if (rowDiv && data?.length > 0) {
    wingBox.appendChild(rowDiv);
  }
  stadium.appendChild(wingBox);
};
const stadiumDiv = document.querySelector(".stadium");

wingCreator({ data: lwdata, stadium: stadiumDiv }); // left wing
wingCreator({ data: clwdata, stadium: stadiumDiv }); // center left wing
wingCreator({ data: crwdata, stadium: stadiumDiv }); // center right wing
wingCreator({ data: rwdata, stadium: stadiumDiv }); // right wing
