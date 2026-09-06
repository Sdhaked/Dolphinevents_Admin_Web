<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AboutPageContent;
use App\Models\ContactPageContent;
use App\Models\Event;
use App\Models\EventArchivePageContent;
use App\Models\EventContestent;
use App\Models\EventGallery;
use App\Models\EventSlider;
use App\Models\EventSponsor;
use App\Models\Gallery;
use App\Models\HomePageContent;
use App\Models\PolicyPageContent;
use App\Models\Slider;
use App\Models\TermsPageContent;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function destroy(Request $request, string $target, ?int $id = null): JsonResponse
    {
        $transactionActive = false;

        try {
            [$record, $field, $deleteRecord, $label] = $this->resolveTarget($request, $target, $id);

            if (! $record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Media record not found or access denied.',
                ], 404);
            }

            $path = $record->getAttribute($field);

            if (! $path) {
                return response()->json([
                    'success' => true,
                    'message' => $label.' is already removed.',
                ]);
            }

            DB::beginTransaction();
            $transactionActive = true;

            if ($deleteRecord) {
                method_exists($record, 'forceDelete')
                    ? $record->forceDelete()
                    : $record->delete();
            } else {
                $record->setAttribute($field, null);
                $record->save();
            }

            if (
                $this->shouldDeletePublicFile($path, $record, $field)
                && ! Storage::disk('public')->delete($path)
            ) {
                throw new \RuntimeException($label.' file could not be deleted.');
            }

            DB::commit();
            $transactionActive = false;

            return response()->json([
                'success' => true,
                'message' => $label.' deleted successfully.',
                'record_deleted' => $deleteRecord,
            ]);
        } catch (\Throwable $e) {
            if ($transactionActive) {
                DB::rollBack();
            }

            Log::error('Admin media deletion failed', [
                'target' => $target,
                'record_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media.',
            ], 500);
        }
    }

    /**
     * @return array{0: Model|null, 1: string, 2: bool, 3: string}
     */
    private function resolveTarget(Request $request, string $target, ?int $id): array
    {
        $eventId = (int) $request->session()->get('active_event_id');

        return match ($target) {
            'event-featured-video' => [Event::find($eventId), 'featured_video', false, 'Featured video'],
            'event-thumbnail' => [Event::find($eventId), 'thumbnail', false, 'Video thumbnail'],
            'event-featured-image' => [Event::find($eventId), 'featured_image', false, 'Featured image'],
            'event-venue-layout-image' => [Event::find($eventId), 'venue_layout_image', false, 'Venue layout image'],
            'event-pdf-sponsor-image' => [Event::find($eventId), 'event_pdf_sponser_image', false, 'PDF sponsor image'],

            'home-hero-video' => [HomePageContent::find(1), 'hero_video_path', false, 'Hero video'],
            'home-about-image' => [HomePageContent::find(1), 'about_image_path', false, 'Home about image'],

            'about-breadcrumb-image' => [AboutPageContent::find(1), 'breadcrumb_image_path', false, 'Breadcrumb image'],
            'about-featured-image' => [AboutPageContent::find(1), 'about_featured_image_path', false, 'About featured image'],
            'about-owner-image-1' => [AboutPageContent::find(1), 'owner_image_1_path', false, 'Owner image 1'],
            'about-owner-image-2' => [AboutPageContent::find(1), 'owner_image_2_path', false, 'Owner image 2'],

            'contact-breadcrumb-image' => [ContactPageContent::find(1), 'breadcrumb_image_path', false, 'Contact breadcrumb image'],
            'policy-breadcrumb-image' => [PolicyPageContent::find(1), 'breadcrumb_image_path', false, 'Policy breadcrumb image'],
            'terms-breadcrumb-image' => [TermsPageContent::find(1), 'breadcrumb_image_path', false, 'Terms breadcrumb image'],
            'event-archive-breadcrumb-image' => [EventArchivePageContent::find(1), 'breadcrumb_image_path', false, 'Event archive breadcrumb image'],

            'profile-picture' => [$request->user(), 'profile_picture', false, 'Profile picture'],
            'ticket-type-featured-image' => [
                TicketType::where('event_id', $eventId)->find($id),
                'featured_image',
                false,
                'Ticket type image',
            ],
            'contestent-image' => [
                EventContestent::where('event_id', $eventId)->find($id),
                'image',
                false,
                'Contestent image',
            ],

            'gallery-record' => [Gallery::find($id), 'image_path', true, 'Gallery image'],
            'hero-slider-record' => [Slider::where('type', 1)->find($id), 'image', true, 'Hero slide'],
            'info-slider-record' => [Slider::where('type', 2)->find($id), 'image', true, 'Info slide'],
            'event-gallery-record' => [
                EventGallery::where('event_id', $eventId)->find($id),
                'image',
                true,
                'Event gallery image',
            ],
            'event-sponsor-record' => [
                EventSponsor::where('event_id', $eventId)->find($id),
                'image',
                true,
                'Event sponsor',
            ],
            'event-info-slider-record' => [
                EventSlider::where('event_id', $eventId)
                    ->where('type', EventSlider::TYPE_INFO)
                    ->find($id),
                'image',
                true,
                'Event info slide',
            ],
            default => [null, '', false, 'Media'],
        };
    }

    private function shouldDeletePublicFile(string $path, Model $currentRecord, string $currentField): bool
    {
        if (! Storage::disk('public')->exists($path)) {
            return false;
        }

        $eventQuery = Event::withTrashed()
            ->where(function ($query) use ($path) {
                $query->where('event_pdf_sponser_image', $path)
                    ->orWhere('featured_video', $path)
                    ->orWhere('thumbnail', $path)
                    ->orWhere('featured_image', $path)
                    ->orWhere('venue_layout_image', $path);
            });

        if ($currentRecord instanceof Event) {
            $eventQuery->where('id', '!=', $currentRecord->id);
        }

        if ($eventQuery->exists()) {
            return false;
        }

        $ticketTypeQuery = TicketType::withTrashed()->where('featured_image', $path);

        if ($currentRecord instanceof TicketType && $currentField === 'featured_image') {
            $ticketTypeQuery->where('id', '!=', $currentRecord->id);
        }

        return ! $ticketTypeQuery->exists();
    }
}
