<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPermission
{
    /**
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array((string) $request->route()?->getName(), ['logout'], true)) {
            return $next($request);
        }

        if (!$this->permissionTablesReady()) {
            return $next($request);
        }

        $user = $request->user();
        $permissions = $this->permissionsForRequest($request);

        if (!$user || !$user->role || empty($permissions) || !$this->userHasAnyPermission((int) $user->role, $permissions)) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }

    private function permissionTablesReady(): bool
    {
        return Schema::hasTable('permissions') && Schema::hasTable('role_permissions');
    }

    /**
     * @return array<int, string>
     */
    private function permissionsForRequest(Request $request): array
    {
        $routeName = (string) $request->route()?->getName();

        return array_values(array_unique(match (true) {
            $routeName === 'profile' => ['profile-view-profile', 'profile-manage-profile'],
            $routeName === 'profile.edit' => ['profile-edit-profile', 'profile-manage-profile'],
            $routeName === 'profile.update' => ['profile-edit-profile', 'profile-manage-profile'],
            $routeName === 'profile.change.password' => [
                'profile-edit-profile',
                'profile-change-profile-password',
                'profile-manage-profile',
            ],

            $routeName === 'admin.media.destroy' => $this->mediaPermissions((string) $request->route('target')),
            $routeName === 'admin.dashboard.index' => ['dashboard-view-dashboard'],
            $routeName === 'admin.send.reminder' => ['events-send-event-reminder', 'events-manage-events'],
            $routeName === 'admin.settings.index' => ['settings-view-settings', 'settings-manage-settings'],

            str_starts_with($routeName, 'admin.users.') => $this->crudPermissions($routeName, 'admin.users.', 'users', 'users'),
            str_starts_with($routeName, 'admin.roles.') => $this->crudPermissions($routeName, 'admin.roles.', 'roles', 'roles'),
            str_starts_with($routeName, 'admin.permissions.') => $this->crudPermissions($routeName, 'admin.permissions.', 'permissions', 'permissions'),

            str_starts_with($routeName, 'admin.checkers.') => $this->crudPermissions($routeName, 'admin.checkers.', 'ticket-checkers', 'ticket-checkers'),
            str_starts_with($routeName, 'admin.ticket.types.') => $this->crudPermissions($routeName, 'admin.ticket.types.', 'ticket-types', 'ticket-types'),
            str_starts_with($routeName, 'admin.discount.coupons.') => $this->crudPermissions($routeName, 'admin.discount.coupons.', 'discount-coupons', 'discount-coupons'),
            str_starts_with($routeName, 'admin.bulk-discount.') => $this->crudPermissions($routeName, 'admin.bulk-discount.', 'bulk-discounts', 'bulk-discounts'),

            str_starts_with($routeName, 'admin.event.services.') => $this->eventServicePermissions($routeName),
            str_starts_with($routeName, 'admin.ticket.sold.') => $this->ticketSoldPermissions($routeName),
            str_starts_with($routeName, 'admin.ticket.failed.') => $this->ticketFailedPermissions($routeName),
            str_starts_with($routeName, 'ticket-counter.') || str_starts_with($routeName, 'admin.ticket.counter.api.') => $this->ticketCounterPermissions($routeName),

            str_starts_with($routeName, 'admin.pages.home.') => $this->pagePermissions($routeName, 'home-page-content'),
            str_starts_with($routeName, 'admin.pages.about.') => $this->pagePermissions($routeName, 'about-page-content'),
            str_starts_with($routeName, 'admin.pages.contact.') => $this->pagePermissions($routeName, 'contact-page-content'),
            str_starts_with($routeName, 'admin.pages.event_archive.') => $this->pagePermissions($routeName, 'event-archive-page-content'),
            str_starts_with($routeName, 'admin.pages.tickets.') => $this->pagePermissions($routeName, 'tickets-page-content'),
            str_starts_with($routeName, 'admin.pages.terms') => $this->pagePermissions($routeName, 'terms-page-content'),
            str_starts_with($routeName, 'admin.pages.policy') => $this->pagePermissions($routeName, 'policy-page-content'),

            str_starts_with($routeName, 'admin.sliders.hero.') => $this->crudPermissions($routeName, 'admin.sliders.hero.', 'main-hero-slider', 'main-hero-slider'),
            str_starts_with($routeName, 'admin.sliders.info.') => $this->crudPermissions($routeName, 'admin.sliders.info.', 'main-info-slider', 'main-info-slider'),
            str_starts_with($routeName, 'admin.gallery.') => $this->mainGalleryPermissions($routeName),

            str_starts_with($routeName, 'admin.events.') => $this->eventPermissions($routeName),
            str_starts_with($routeName, 'admin.sponsors.') => $this->crudPermissions($routeName, 'admin.sponsors.', 'sponsors', 'sponsors'),
            str_starts_with($routeName, 'admin.contestents.') => $this->crudPermissions($routeName, 'admin.contestents.', 'contestents', 'contestents'),
            str_starts_with($routeName, 'admin.event.sliders.info.') => $this->crudPermissions($routeName, 'admin.event.sliders.info.', 'event-info-slider', 'event-info-slider'),
            str_starts_with($routeName, 'admin.event.gallery.') => $this->eventGalleryPermissions($routeName),
            str_starts_with($routeName, 'admin.event.support.') => $this->eventSupportPermissions($routeName),

            default => [],
        }));
    }

    /**
     * @return array<int, string>
     */
    private function crudPermissions(string $routeName, string $prefix, string $module, string $resource): array
    {
        $action = explode('.', substr($routeName, strlen($prefix)))[0] ?? '';

        return match ($action) {
            'index', 'show', 'view' => [
                "{$module}-view-{$resource}",
                "{$module}-manage-{$resource}",
            ],
            'create', 'createSeats', 'store' => [
                "{$module}-create-{$resource}",
                "{$module}-manage-{$resource}",
            ],
            'edit', 'editSeats', 'update', 'activate' => [
                "{$module}-edit-{$resource}",
                "{$module}-manage-{$resource}",
            ],
            'destroy', 'delete-all' => [
                "{$module}-delete-{$resource}",
                "{$module}-manage-{$resource}",
            ],
            default => ["{$module}-manage-{$resource}"],
        };
    }

    /**
     * @return array<int, string>
     */
    private function eventPermissions(string $routeName): array
    {
        return match ($routeName) {
            'admin.events.list',
            'admin.events.show' => ['events-view-events', 'events-manage-events'],
            'admin.events.store' => ['events-create-events', 'events-manage-events'],
            'admin.events.duplicate' => ['events-duplicate-events', 'events-manage-events'],
            'admin.events.edit',
            'admin.events.update',
            'admin.events.editor.upload_image' => ['events-edit-events', 'events-manage-events'],
            'admin.events.set.current' => ['events-set-current-event', 'events-manage-events'],
            'admin.events.get.current' => ['events-view-current-event', 'events-set-current-event', 'events-manage-events'],
            'admin.events.destroy' => ['events-delete-events', 'events-manage-events'],
            default => ['events-manage-events'],
        };
    }

    /**
     * @return array<int, string>
     */
    private function pagePermissions(string $routeName, string $module): array
    {
        $isEditAction = str_ends_with($routeName, '.store');
        $actionPermissions = $isEditAction
            ? ["{$module}-edit-{$module}", "{$module}-manage-{$module}"]
            : ["{$module}-view-{$module}", "{$module}-manage-{$module}"];

        return array_merge($actionPermissions, [
            $isEditAction ? 'page-content-edit-page-content' : 'page-content-view-page-content',
            'page-content-manage-page-content',
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function ticketSoldPermissions(string $routeName): array
    {
        return match ($routeName) {
            'admin.ticket.sold.index',
            'admin.ticket.sold.show' => ['ticket-sold-view-sold-tickets', 'ticket-sold-manage-ticket-sold'],
            'admin.ticket.sold.export' => ['ticket-sold-export-sold-tickets', 'ticket-sold-manage-ticket-sold'],
            'admin.ticket.sold.regenerate_pdf' => ['ticket-sold-regenerate-sold-ticket-pdf', 'ticket-sold-manage-ticket-sold'],
            'admin.ticket.sold.resend_email' => ['ticket-sold-resend-sold-ticket-email', 'ticket-sold-manage-ticket-sold'],
            'admin.ticket.sold.destroy' => ['ticket-sold-delete-sold-tickets', 'ticket-sold-manage-ticket-sold'],
            'admin.ticket.sold.trash' => ['ticket-sold-view-ticket-sold-trash', 'ticket-sold-manage-ticket-sold-trash'],
            'admin.ticket.sold.restore' => ['ticket-sold-restore-sold-tickets', 'ticket-sold-manage-ticket-sold-trash'],
            'admin.ticket.sold.empty_trash' => ['ticket-sold-empty-ticket-sold-trash', 'ticket-sold-manage-ticket-sold-trash'],
            'admin.ticket.sold.force_delete' => [
                'ticket-sold-delete-trash-records',
                'ticket-sold-permanently-delete-sold-tickets',
                'ticket-sold-manage-ticket-sold-trash',
            ],
            default => ['ticket-sold-manage-ticket-sold'],
        };
    }

    /**
     * @return array<int, string>
     */
    private function ticketFailedPermissions(string $routeName): array
    {
        return match ($routeName) {
            'admin.ticket.failed.index',
            'admin.ticket.failed.show' => ['ticket-failed-view-failed-tickets', 'ticket-failed-manage-ticket-failed'],
            'admin.ticket.failed.refund' => ['ticket-failed-refund-failed-tickets', 'ticket-failed-manage-ticket-failed'],
            'admin.ticket.failed.destroy' => ['ticket-failed-delete-failed-tickets', 'ticket-failed-manage-ticket-failed'],
            default => ['ticket-failed-manage-ticket-failed'],
        };
    }

    /**
     * @return array<int, string>
     */
    private function ticketCounterPermissions(string $routeName): array
    {
        if (str_starts_with($routeName, 'admin.ticket.counter.api.')) {
            return [
                'ticket-counter-use-ticket-counter-apis',
                'ticket-counter-view-ticket-counter',
                'ticket-counter-create-ticket-counter-bookings',
                'ticket-counter-edit-ticket-counter-bookings',
                'ticket-counter-manage-ticket-counter',
            ];
        }

        return match ($routeName) {
            'ticket-counter.index',
            'ticket-counter.show' => ['ticket-counter-view-ticket-counter', 'ticket-counter-manage-ticket-counter'],
            'ticket-counter.create',
            'ticket-counter.store' => ['ticket-counter-create-ticket-counter-bookings', 'ticket-counter-manage-ticket-counter'],
            'ticket-counter.edit',
            'ticket-counter.update' => ['ticket-counter-edit-ticket-counter-bookings', 'ticket-counter-manage-ticket-counter'],
            'ticket-counter.destroy' => ['ticket-counter-delete-ticket-counter-bookings', 'ticket-counter-manage-ticket-counter'],
            default => ['ticket-counter-manage-ticket-counter'],
        };
    }

    /**
     * @return array<int, string>
     */
    private function eventServicePermissions(string $routeName): array
    {
        return match ($routeName) {
            'admin.event.services.index' => [
                'event-services-view-event-services',
                'event-services-manage-event-services',
                'ticket-types-manage-ticket-types',
            ],
            'admin.event.services.store' => [
                'event-services-create-event-services',
                'event-services-manage-event-services',
                'ticket-types-manage-ticket-types',
            ],
            'admin.event.services.update' => [
                'event-services-edit-event-services',
                'event-services-manage-event-services',
                'ticket-types-manage-ticket-types',
            ],
            'admin.event.services.destroy' => [
                'event-services-delete-event-services',
                'event-services-manage-event-services',
                'ticket-types-manage-ticket-types',
            ],
            default => [
                'event-services-manage-event-services',
                'ticket-types-manage-ticket-types',
            ],
        };
    }

    /**
     * @return array<int, string>
     */
    private function eventSupportPermissions(string $routeName): array
    {
        return str_ends_with($routeName, '.store')
            ? ['event-support-edit-event-support', 'event-support-manage-event-support']
            : ['event-support-view-event-support', 'event-support-manage-event-support'];
    }

    /**
     * @return array<int, string>
     */
    private function mainGalleryPermissions(string $routeName): array
    {
        return match ($routeName) {
            'admin.gallery.index' => ['main-gallery-view-main-gallery', 'main-gallery-manage-main-gallery'],
            'admin.gallery.store' => ['main-gallery-create-main-gallery', 'main-gallery-manage-main-gallery'],
            'admin.gallery.update' => ['main-gallery-edit-main-gallery', 'main-gallery-manage-main-gallery'],
            'admin.gallery.destroy',
            'admin.gallery.destroy_all' => ['main-gallery-delete-main-gallery', 'main-gallery-manage-main-gallery'],
            default => ['main-gallery-manage-main-gallery'],
        };
    }

    /**
     * @return array<int, string>
     */
    private function eventGalleryPermissions(string $routeName): array
    {
        return match ($routeName) {
            'admin.event.gallery.index' => ['event-gallery-view-event-gallery', 'event-gallery-manage-event-gallery'],
            'admin.event.gallery.store' => ['event-gallery-create-event-gallery', 'event-gallery-manage-event-gallery'],
            'admin.event.gallery.update' => ['event-gallery-edit-event-gallery', 'event-gallery-manage-event-gallery'],
            'admin.event.gallery.destroy' => ['event-gallery-delete-event-gallery', 'event-gallery-manage-event-gallery'],
            default => ['event-gallery-manage-event-gallery'],
        };
    }

    /**
     * @return array<int, string>
     */
    private function mediaPermissions(string $target): array
    {
        return match ($target) {
            'event-featured-video',
            'event-thumbnail',
            'event-featured-image',
            'event-venue-layout-image',
            'event-pdf-sponsor-image' => ['events-edit-events', 'events-manage-events'],

            'home-hero-video',
            'home-about-image' => [
                'home-page-content-edit-home-page-content',
                'home-page-content-manage-home-page-content',
                'page-content-edit-page-content',
                'page-content-manage-page-content',
            ],

            'about-breadcrumb-image',
            'about-featured-image',
            'about-owner-image-1',
            'about-owner-image-2' => [
                'about-page-content-edit-about-page-content',
                'about-page-content-manage-about-page-content',
                'page-content-edit-page-content',
                'page-content-manage-page-content',
            ],

            'contact-breadcrumb-image' => [
                'contact-page-content-edit-contact-page-content',
                'contact-page-content-manage-contact-page-content',
                'page-content-edit-page-content',
                'page-content-manage-page-content',
            ],

            'policy-breadcrumb-image' => [
                'policy-page-content-edit-policy-page-content',
                'policy-page-content-manage-policy-page-content',
                'page-content-edit-page-content',
                'page-content-manage-page-content',
            ],

            'terms-breadcrumb-image' => [
                'terms-page-content-edit-terms-page-content',
                'terms-page-content-manage-terms-page-content',
                'page-content-edit-page-content',
                'page-content-manage-page-content',
            ],

            'event-archive-breadcrumb-image' => [
                'event-archive-page-content-edit-event-archive-page-content',
                'event-archive-page-content-manage-event-archive-page-content',
                'page-content-edit-page-content',
                'page-content-manage-page-content',
            ],

            'profile-picture' => ['profile-edit-profile', 'profile-manage-profile'],
            'ticket-type-featured-image' => ['ticket-types-edit-ticket-types', 'ticket-types-manage-ticket-types'],
            'contestent-image' => ['contestents-edit-contestents', 'contestents-manage-contestents'],
            'gallery-record' => ['main-gallery-delete-main-gallery', 'main-gallery-manage-main-gallery'],
            'hero-slider-record' => ['main-hero-slider-delete-main-hero-slider', 'main-hero-slider-manage-main-hero-slider'],
            'info-slider-record' => ['main-info-slider-delete-main-info-slider', 'main-info-slider-manage-main-info-slider'],
            'event-gallery-record' => ['event-gallery-delete-event-gallery', 'event-gallery-manage-event-gallery'],
            'event-sponsor-record' => ['sponsors-delete-sponsors', 'sponsors-manage-sponsors'],
            'event-info-slider-record' => ['event-info-slider-delete-event-info-slider', 'event-info-slider-manage-event-info-slider'],
            default => [],
        };
    }

    /**
     * @param array<int, string> $permissions
     */
    private function userHasAnyPermission(int $roleId, array $permissions): bool
    {
        return Permission::query()
            ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_permissions.role_id', $roleId)
            ->whereIn('permissions.slug', $permissions)
            ->exists();
    }
}
