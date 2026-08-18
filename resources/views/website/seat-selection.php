<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Select [Event name] Seats | Dolphinevent</title>
    <!-- #=======> Head Files -->
    <?php include './Assets/Components/Head/head-files.php';?>

    <!-- Animate CSS CDN -->
    <link rel="stylesheet" href="./Assets/Style/aos.css" />

    <!-- #=======> Call Style -->
    <?php include './Assets/Components/Head/g-css-files.php';?>

    <!-- conditional css -->
    <link rel="stylesheet" href="./Assets/Style/page-styling/booking.css" />
    <link rel="stylesheet" href="./Assets/Style/offer-bar.css" />

    <!-- #=======> Call JS -->
    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.1.min.js"
        integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous" defer></script>

    <!-- Animation JS CDN -->
    <script src="./Assets/JS/aos.js" defer></script>
    <script src="./Assets/JS/custom.aos.js" defer></script>

    <!-- Main JS Files -->
    <?php include './Assets/Components/Head/g-js-files.php';?>
</head>

<body>
    <!-- Preloader -->
    <?php include './Assets/Components/preloader.php';?>

    <!--########## 🥗 HEADER 🥗 ##########-->
    <?php include './Assets/Components/nav.php';?>

    <!-- Venue Layout Popup Modal -->
    <?php include './Assets/Components/venue-layout-popup.php';?>

    <!-- ==> Animation Canvas -->
    <!-- <canvas id="confetti-canvas"></canvas> -->

    <!-- MAIN BODY -->
    <main>

        <!--==================================================
                  Event Detail SECTION
        ======================================================-->
        <section class="container-fluid spc-y-half main-sec">
            <div class="container">
                <!-- Back Box -->
                <div class="back-box">
                    <button class="btm-md btn-link" onclick="history.back()"> <i
                            class="fa-solid fa-arrow-left-long i-mr"></i>Back</button>
                </div>

                <!-- Header -->
                <div class="head-box">
                    <div>
                        <h1 class="hd-prim" data-aos="fade-in">Select Seats</h1>
                        <h5 style="color: var(--color-text-200);">Event Name: <span
                                style="color: var(--my-primary);">Night Party 420</span></h5>
                        <h5 style="color: var(--color-text-200);">Choosed Ticket Type: <span style="color: blue;">[Garib
                                Niwas]</span></h5>
                    </div>


                    <div>
                        <button onclick="showElement(`#venue-layout-pop`)" class="btn-md btn-lite-outline hover-lite">
                            <i class="fa-solid fa-layer-group i-mr"></i> Venue Layout
                        </button>
                    </div>
                </div>

                <!-- Offer Bar -->
                <div class="offer-comp"></div>
                <button id="dummy-btn">+ 0 Tickets</button>
                <button id="dummy-btn2" style="margin-left: 20px;">-</button>

                <!-- Main Content -->
                <div class="grid-1 gap-col" data-aos="fade-up">
                    <!-- //stadium Layout -->
                    <div class="stadium-layout">
                        <div class="stadium"></div>
                    </div>
                    <div>
                        <p class="selected-seats">
                            Total Selected Seats: <span class="text-prim">0</span>
                        </p>

                        <p class="selected-seats">Selected Seats: <span>[ LW-A9 ]</span> <span> [LW-A9 ]</span></p>

                        <div class="select-info-box">
                            <div>
                                <div class="check-box selected-seat"><i class="fa-solid fa-check"></i></div>
                                <span>Seat Selected</span>
                            </div>
                            <div>
                                <div class="check-box empty-seat"></div>
                                <span>Empty Seat</span>
                            </div>
                            <div>
                                <div class="check-box booked-seat"><i class="fa-solid fa-check"></i></div>
                                <span>Booked Seat</span>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer -->
                <div class="next-box">
                    <a href="./checkout.php">
                        <button type="button" class="btn-md btn-prim hover-prim-outline">
                            Next <i class="fa-solid fa-arrow-right-long i-ml"></i>
                        </button>
                    </a>
                </div>
            </div>
        </section>
    </main>
    <?php
$mydata = [
    [
        "id" => 1,
        "row" => "A",
        "seat" => 7,
        "ticketType" => "xxy@",
        "accent" => "red",
        "disabled" => false,
        "booked" => false,
    ],
    [
        "id" => 2,
        "row" => "A",
        "seat" => 6,
        "ticketType" => "xxy@",
        "accent" => "red",
        "disabled" => false,
        "booked" => false,
    ],
    [
        "id" => 3,
        "row" => "A",
        "seat" => 5,
        "ticketType" => "xx",
        "accent" => "red",
        "disabled" => false,
        "booked" => false,
    ],
    [
        "id" => 5,
        "row" => "B",
        "seat" => 4,
        "ticketType" => "xxy@",
        "accent" => "red",
        "disabled" => false,
        "booked" => true,
    ],
    
];
?>

    <!-- ==========> 🟥 Map of Seat selection Data 🟥 -->
    <!-- [
    "id" = 1, // Seat ID
    "row" = "C", // Give Row number A-Z
    "seat" = 1, // Seat number 1 - 25...
    "ticketType" = "not-decided", // Ticket Type
    "accent" = null, // Ticket Color
    "booked" = false, // Checked + disabled / unchecked + not disabled
    ], -->

    <!-- note:- Default value mane set karke map me show kari ha -->

    <!-- ####### FOOTER ####### -->
    <?php include './Assets/Components/Footer.php';?>
    <script type="module">
    import {
        wingCreator
    } from "./Assets/JS/page-js/seat-selection.js";

    import {
        createOfferBar,
        offerBarFun,
        debounce
    } from "./Assets/JS/offer-bar.js";

    import {
        lwdata,
    } from "./Assets/JS/seat-data.js";

    // =============================
    // ---->  Create Stadium Layout
    // =============================
    // const lwData = <?php //echo json_encode($mydata); ?>;
    wingCreator(lwdata, "LW", document.querySelector(".stadium"));


    // =============================
    // ---->  Manage Offer Bar
    // =============================
    // ======> Variables
    // --Discount Slabs
    const slabs = [{
            minTickets: 2,
            offer: 10
        },
        {
            minTickets: 10,
            offer: 15
        },
        {
            minTickets: 20,
            offer: 20
        },
    ];

    //--  Main Comp where to creat offer bar
    const offerComp = document.querySelector(".offer-comp");

    // CREATE DEBOUNCED FUNCTION ONCE
    const debouncedOfferBarFun = debounce((selectedTickets) => {
        offerBarFun(selectedTickets, slabs, offerComp.querySelector(".offer-bar"));
    }, 200); // just pass [slabs] array or obj from php

    // INITIAL CALL
    if (offerComp) {
        createOfferBar(offerComp, slabs); // just pass [slabs] array or obj from php
        debouncedOfferBarFun(0);
    }

    // ===> 🔴 Use this fun() to update offer bar on total seat selected no. change
    // debouncedOfferBarFun(pass-total-seat-selected-number);

    // ================xxxxxxxxxxxxxxxxxxxx Dummy trigger btns
    let selectedTickets = 0;
    let dmy = document.querySelector("#dummy-btn");
    dmy.addEventListener("click", function() {
        if (selectedTickets >= 20) return;
        selectedTickets = selectedTickets + 1;
        dmy.innerHTML = `+ ${selectedTickets} ticket`;
        debouncedOfferBarFun(selectedTickets);
    });

    let dmy2 = document.querySelector("#dummy-btn2");
    dmy2.addEventListener("click", function() {
        if (selectedTickets <= 0) return;
        selectedTickets = selectedTickets - 1;
        dmy.innerHTML = `+ ${selectedTickets} ticket`;
        debouncedOfferBarFun(selectedTickets);
    });
    // ================xxxxxx end xxxxxxxxxxxxxx Dummy trigger btns
    </script>
</body>

</html>
