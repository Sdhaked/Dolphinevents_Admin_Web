@extends('layouts.admin')

@section('head')
    <title>Dashboard</title>
    <meta name="description" content="lorem hdihf ffhefef e9fje9fje9fef jefje9 fefef.">

    <!----======== Head Files ======== -->
    @include('admin._partials.head.g-links')

    <!----======== CSS ======== -->
    @include('admin._partials.head.g-css-files')

    <!----======== JS ======== -->
    @include('admin._partials.head.g-js-files')
    
    @if(!$hasEvents)
    <style>
        .no-events-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 80vh;
            text-align: center;
        }

        .no-events-container .divider {
           width: 10rem;
           height: 1px;
           margin-bottom: 2rem;
           background: linear-gradient(
             to right,
             rgba(0, 0, 0, 0),
             rgba(0, 0, 0, 0.18),
             rgba(0, 0, 0, 0)
           );
        }

        html.dark .no-events-container .divider {
           background: linear-gradient(
             to right,
             rgba(255, 255, 255, 0),
             rgba(255, 255, 255, 0.25),
             rgba(255, 255, 255, 0)
           );
        }
        
        .highlight-message {
           margin-bottom: 2rem;
        }
        .highlight-message h1 span{
            color: var(--color-primary)
        }
        
        .create-event-btn {
          padding: 1rem 2rem;
          border-radius: 10px;
          font-size: 1.2rem;
          font-weight: bold;
          text-decoration: none;
          display: inline-block;
          transition: all 0.3s ease;
        }
        .create-event-btn {
          padding: 1rem 2rem;
          border-radius: 10px;
          font-size: 1.2rem;
          font-weight: bold;
          text-decoration: none;
          display: inline-block;
          transition: all 0.3s ease;
        }

        @media (max-width:1200px) {
          .create-event-btn {
            padding: 0.8rem 1.2rem;
            font-size: 1rem;
          }
        }
        @media (max-width:800px) {
          .create-event-btn {
            padding: 0.6rem 1rem;
            font-size: 0.8rem;
          }
        }
    </style>
    @endif
@endsection

@section('body')
    <!-- PRELOADER -->
    @include('admin._partials.preloader')

    <!-- SideBar (Nav Items) -->
    @include('admin._partials.sidebar')

    <!-- TOP HEADER -->
    @include('admin._partials.header')

    <!-- MAIN CONTENT 🥗 -->
    <section class="wrapper">
        <main class="dash-content">
            @if(!$hasEvents)
                <div class="style-box no-events-container">
                    <div class="highlight-message">
                        <h1 class="hd-xl">🎉 Welcome to Your <span>Event Management</span> System!</h1>
                        <p class="mb-0 hd-md">You haven't created any events yet. Let's get started by creating your first event!</p>
                    </div>
                    <div class="divider"></div>
                    <div>
                        <h3 class="hd-md mb-3">Ready to create your first event?</h3>
                        <p class="mb-4" style="font-size:10px;">Click the button below to start the step-by-step setup.</p>
                        
                        <button type="button" class="create-event-btn btn-prim" data-bs-toggle="modal" data-bs-target="#createeventModal">
                            <i class="fa-regular fa-square-plus me-2"></i> Create Your First Event
                        </button>
                    </div>
                </div>
            @else
            <!-- Breadcrumb -->
            @include('admin._partials.breadcrumb')

            <div class="HDandP">
                <h4 class="hd-lg">Hi, <span>{{ auth()->user()->name }}</span></h4>
                <p>Welcome to <span class="text-sec">[{{ $event->title ?? 'No Event Selected' }}]</span> admin panel | Event Type: <span
                        class="text-sec text-capitalize">[{{ isset($event) ? config('entities.event_types')[$event->type ?? 1] : 'N/A' }}]</span></p>
            </div>

            <!-- ++ Card Stats row ++  -->
            <div class="statsRow">

            @if($ticketStats ?? false)
            @foreach($ticketStats as $stat)
                <div class="statCard info">
                    <h5>{{ $stat->title }} Ticket</h5>
                    <div>
                        <div class="div-L">
                            <h4>{{ $stat->sold_count }} <i class="fa-solid fa-ticket"></i></h4>
                            <p>Confirmed Ticket Sold, out of {{ $stat->capacity }} Tickets</p>
                        </div>
                    </div>
                </div>
            @endforeach
            @endif

                <!-- Total Ticket Sold-->
                <div class="statCard info">
                    <h5>Total Ticket Sold</h5>
                    <div>
                        <div class="div-L">
                            <h4>{{ $totalSold ?? 0 }} <i class="fa-brands fa-angellist"></i></h4>
                            <p>Confirmed Ticket Sold, out of {{ $totalCapacity ?? 0 }} Tickets</p>
                        </div>
                    </div>
                </div>

                @if(($failedTicketStats ?? false) && $failedTicketStats->isNotEmpty())
                @foreach($failedTicketStats as $stat)
                <div class="statCard danger">
                    <h5>{{ $stat->title }} Ticket Failed</h5>
                    <div>
                        <div class="div-L">
                            <h4>{{ $stat->failed_count }} <i class="fa-solid fa-circle-xmark"></i></h4>
                            <p>Failed/Pending Tickets, out of {{ $stat->capacity }} Tickets</p>
                        </div>
                    </div>
                </div>
                @endforeach
                @endif

                <!-- Total Ticket Failed-->
                <div class="statCard danger">
                    <h5>Total Ticket Failed</h5>
                    <div>
                        <div class="div-L">
                            <h4>{{ $totalFailed ?? 0 }} <i class="fa-solid fa-circle-xmark"></i></h4>
                            <p>Failed/Pending Tickets</p>
                        </div>
                    </div>
                </div>

                <!-- Money Earned -->
                <div class="statCard success">
                    <img src="{{ asset('images/grapshbg/graph1.png') }}" alt="success">
                    <h5>Money Earned</h5>
                    <div>
                        <div class="div-L">
                            <h4><i class="fa-solid fa-sterling-sign"></i>{{ number_format($totalRevenue ?? 0, 2) }}/-</h4>
                            <p>Total Revenue generated by selling tickets</p>
                        </div>
                    </div>
                </div>
            </div>

            <hr style="margin: 2rem  0; color:var(--color-border-100);">

            <!-- Event reminder -->
            @if(isset($event))
            <div>
                <p>
                    Last Reminded at 
                    <b>
                        {{ $event->last_reminder_sent_at ? \Carbon\Carbon::parse($event->last_reminder_sent_at)->format('d M, Y | h:i A') : 'Never' }}
                    </b> 
                    by 
                    <b>{{ $event->last_reminded_by ?? 'N/A' }}</b>
                </p>
                <form id="reminderForm" action="{{ route('admin.send.reminder') }}" method="POST" style="display: none;">
                    @csrf
                </form>

                <button class="btn-md btn-prim" type="button" id="openReminderModal">
                    <i class="fa-solid fa-bullhorn me-1"></i> Give Event Reminder
                </button>
            </div>
            @endif
            @endif
        </main>
    </section>

    <div class="modal fade" id="sendReminderModal" tabindex="-1" aria-labelledby="sendReminderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="hd-sm m-0">Send Event Reminder</h6>
                    <button type="button" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Sure want to give Event Reminder to all the customers?</p>
                    <p class="mb-0 text-danger"><strong>This action will send the reminder immediately.</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-xs btn-sec-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-xs btn-sec" id="confirmReminderBtn">Send Reminder</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const openReminderModal = document.getElementById('openReminderModal');
        const sendReminderModalElement = document.getElementById('sendReminderModal');
        const confirmReminderBtn = document.getElementById('confirmReminderBtn');
        const reminderForm = document.getElementById('reminderForm');

        openReminderModal?.addEventListener('click', function() {
            const modal = new bootstrap.Modal(sendReminderModalElement);
            modal.show();
        });

        confirmReminderBtn?.addEventListener('click', function() {
            const confirmBtn = this;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';
            confirmBtn.disabled = true;
            reminderForm.submit();
        });
    </script>

@endsection
