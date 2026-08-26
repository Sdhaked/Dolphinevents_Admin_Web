<?php

namespace App\Http\Controllers\Admin\Pages;

use App\Http\Controllers\Controller;
use App\Models\ContactPageContent;
use App\Models\ContactSocialLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $content = ContactPageContent::find(1);
        $social = ContactSocialLink::visible()->orderBy('id')->take(4)->get();

        return view('admin.pages.contact', compact('content', 'social'));
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
    public function store(Request $request)
    {
        $request->validate([
            'platform' => ['nullable', 'array'],
            'platform.*' => ['nullable', Rule::in(array_map('strval', array_keys(config('entities.social_options', []))))],
            'url' => ['nullable', 'array'],
            'url.*' => ['nullable', 'url', 'max:2048'],
        ]);

        $socialErrors = [];
        $usedPlatforms = [];

        foreach ($request->input('url', []) as $index => $url) {
            $url = trim((string) $url);
            $platform = $request->input("platform.$index");

            if (blank($url)) {
                continue;
            }

            if (blank($platform)) {
                $socialErrors["platform.$index"] = 'Please select platform for this social link.';
                continue;
            }

            if (in_array((string) $platform, $usedPlatforms, true)) {
                $socialErrors["platform.$index"] = 'This social platform is already selected.';
                continue;
            }

            $usedPlatforms[] = (string) $platform;
        }

        if ($socialErrors) {
            return back()->withErrors($socialErrors)->withInput();
        }

        $page = ContactPageContent::find(1);

        // $breadcrumb_image_path
        $breadcrumb_image_path = $page?->breadcrumb_image_path;
        if ($request->hasFile('breadcrumb_image_path')) {
            $breadcrumb_image_path = $this->replacePublicFile(
                $breadcrumb_image_path,
                $request->file('breadcrumb_image_path'),
                'pages/contact/'
            );
        }

        DB::transaction(function () use ($request, $breadcrumb_image_path) {
            ContactPageContent::updateOrCreate(
                ['id' => 1],
                [
                    'breadcrumb_image_path' => $breadcrumb_image_path,
                    'breadcrumb_image_alt' => $request->input('breadcrumb_image_alt') ?? 'breadcrumb-image',
                    'breadcrumb_heading_type' => $request->input('breadcrumb_heading_type'),
                    'breadcrumb_heading_text' => $request->input('breadcrumb_heading_text'),
                    'breadcrumb_description' => $request->input('breadcrumb_description'),
                    'phone_prefix_1' => $request->input('phone_prefix_1'),
                    'phone_number_1' => $request->input('phone_number_1'),
                    'phone_prefix_2' => $request->input('phone_prefix_2'),
                    'phone_number_2' => $request->input('phone_number_2'),
                    'email' => $request->input('email'),
                    'address' => $request->input('address'),
                    'map_link' => $request->input('map_link'),
                    'map_embed_link' => $request->input('map_embed_link'),
                    'meta_data' => json_encode($request->input('meta_data'))
                ]
            );

            $savedSocialLinkIds = [];

            foreach (range(1, 4) as $index) {
                $platform = $request->input("platform.$index");
                $url = trim((string) $request->input("url.$index"));

                if (blank($url)) {
                    continue;
                }

                $socialLinkId = $request->input("social_link_id.$index");
                $socialLink = $socialLinkId
                    ? ContactSocialLink::find($socialLinkId)
                    : ContactSocialLink::where('platform', $platform)->first();

                $socialLink ??= new ContactSocialLink();

                $socialLink->fill([
                    'platform' => $platform,
                    'url' => $url,
                ])->save();

                $savedSocialLinkIds[] = $socialLink->id;
            }

            ContactSocialLink::when(
                $savedSocialLinkIds,
                fn ($query) => $query->whereNotIn('id', $savedSocialLinkIds)
            )->delete();
        });

        return redirect()->route('admin.pages.contact.index')->with('success', "Contact page content updated successfully.");
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
