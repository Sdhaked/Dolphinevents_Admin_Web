<?php

namespace App\Http\Controllers\Admin\Event;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupportRequest;
use App\Models\Event;
use App\Models\EventSupport;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $event_id = session('active_event_id');
        $event = Event::where('id', $event_id)->with('support', 'support.socialLinks')->first();
        if (!$event) {
            abort(404);
        }

        $support = $event->support;
        $social = $event->support->socialLinks ?? collect();

        return view('admin.events.support.index', compact('event', 'support', 'social'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSupportRequest $request)
    {
        $event_id = session('active_event_id');
        $event = Event::find($event_id);
        if (!$event) {
            return back()->withErrors(['not-found' => 'Event not found']);
        }

        // Save Event Support Details
        $eventSupport = EventSupport::updateOrCreate(
            [
                'event_id' => $event_id,
            ],
            [
                'phone_prefix' => $request->input('phone_prefix'),
                'phone_number' => $request->input('phone_number'),
                'secondary_phone_prefix' => $request->input('secondary_phone_prefix'),
                'secondary_phone_number' => $request->input('secondary_phone_number'),
                'email' => $request->input('email'),
                'address' => $request->input('address'),
            ]
        );

        // save social links
        if ($request->has('platform')) {
            foreach ($request->input('platform') as $index => $platform) {
                $eventSupport->socialLinks()->updateOrCreate([
                    'platform' => $platform,
                    'url' => $request->input('url')[$index],
                ]);
            }
        }

        return redirect()->route('admin.event.support.index')->with('success_message', 'Support details updated.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
