<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCheckerRequest;
use App\Http\Requests\UpdateCheckerRequest;
use App\Models\TicketChecker;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TicketCheckerController extends Controller
{
    protected int $perPage;

    public function __construct()
    {
        $this->perPage = config('constants.pagination.per_page', 10);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $event_id = session('active_event_id');

        $checkers = TicketChecker::with('creator', 'event')
        ->where('event_id', $event_id)
        ->when($request->filled('search'), function ($query) use ($request)  {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        })
        ->orderBy('id', 'desc')
        ->paginate($this->perPage);

        if ($request->ajax()) {
            return view('admin.ticket_checker._partials.table', compact('checkers'))->render();
        }

        return view('admin.ticket_checker.index', compact('checkers'));
    }

    /**
     * Show the form for creating a new resource.
    */
    public function create()
    {
        $events = Event::orderBy('title', 'asc')->get();
        return view('admin.ticket_checker.create');
    }

    /**
     * Store a newly created resource in storage.
    */
    public function store(CreateCheckerRequest $request)
    {
        $event_id = session('active_event_id');

        try {

            $chackemail = TicketChecker::where('email', $request->email)->first();
            if ($chackemail) {
                $msg = "This email is already registered as a checker in event: ". $chackemail->event->title;
                if($event_id == $chackemail->event->id)
                {
                    $msg = "This email is already registered as a checker in this event.";
                }
                if ($request->ajax()) 
                {

                    return response()->json([
                        'success' => false,
                        'message' => $msg
                    ], 422);
                }
               
                return redirect()->back()->withInput()->withErrors(['email' => $msg]);
            }

            TicketChecker::create([
                'event_id' => $event_id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'plain_password' => $request->password,
                'created_by' => Auth::user()->id ?? null
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket Checker created successfully!',
                    'redirect' => route('admin.checkers.index')
                ]);
            }

            return redirect()->route('admin.checkers.index')->with('success', 'Ticket Checker created successfully!');
        } catch (\Exception $e) {
            Log::error('TicketChecker module failed to create checker', [
                'module' => 'TicketChecker',
                'error'  => $e->getMessage(),
                'trace'  => $e->getTraceAsString(),
                'input'  => $request->all(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong while creating checker.'
                ], 500);
            }

            return redirect()->route('admin.checkers.index')->with('error', 'Something went wrong while creating checker.');
        }
    }

    /**
     * Show the specified resource.
    */
    public function show($id)
    {

        $checker = TicketChecker::with('creator')->where('id', $id)->first();
    
        if (!$checker) {
            abort(404);
        }

        return view('admin.ticket_checker.show', compact('checker'));
    }

    /**
     * Show the form for editing the specified resource.
    */
    public function edit($id)
    {
        
        $event_id = session('active_event_id');
        $checker = TicketChecker::where('id', $id)->first();

        if (!$checker) {
            abort(404);
        }

        return view('admin.ticket_checker.edit', compact('checker'));
    }

    /**
     * Update the specified resource in storage.
    */
    public function update(UpdateCheckerRequest $request, $id)
    {
        $event_id = session('active_event_id');
        $checker = TicketChecker::where('id', $id)->first();

        if (!$checker) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Checker not found.'
                ], 404);
            }
            abort(404);
        }

        try {
            $checker->update([
                'event_id' => $event_id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'plain_password' => $request->password
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Checker updated successfully!',
                    'redirect' => route('admin.checkers.view', $id)
                ]);
            }

            return redirect()->route('admin.checkers.view', $id)->with('success', 'Details Updated!');
        } catch (\Exception $e) {
            Log::error('TicketChecker module failed to update checker', [
                'module' => 'TicketChecker',
                'error'  => $e->getMessage(),
                'trace'  => $e->getTraceAsString(),
                'input'  => $request->all(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong while updating checker.'
                ], 500);
            }

            return redirect()->route('admin.checkers.index')->with('error', 'Something went wrong while updating checker.');
        }
    }

    /**
     * Remove the specified resource from storage.
    */
    public function destroy(Request $request, $id)
    {
        try {
            $checker = TicketChecker::where('id', $id)->first();

            if (!$checker) {
                abort(404);
            }

            //  $checker->delete();
            $checker->forceDelete();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket checker deleted successfully.'
                ], 200);
            }

            return redirect()->route('admin.checkers.index')->with('success', 'Ticket checker deleted successfully.');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
                'error' => $e->getMessage()
            ], 500);
        }

   
    }


}
