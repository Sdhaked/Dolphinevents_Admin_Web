export const wingCreator = (data = [], wing = "W", stadium) => {
    if (!stadium) return;
    let currentRow = "";
    let rowDiv;
    let wingBox = document.createElement("div");
    wingBox.classList.add(`${wing}`);

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
                    ele.name = wing;
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
                    // ele.value = `${wing}-${item.row}${item.seat}`;
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

// wingCreator(mydata, "LW", document.querySelector(".stadium"));
// wingCreator(mydata, "CRL", document.querySelector(".stadium"));
// wingCreator(mydata, "CRW", document.querySelector(".stadium"));
// wingCreator(mydata, "RW", document.querySelector(".stadium"));
