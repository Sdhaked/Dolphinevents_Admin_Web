<?php

namespace App\Services;

use App\Models\TicketCounter;
use App\Models\TicketCounterService;
use App\Models\TicketCounterServicePass;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ServicePassService
{
    public function ensurePassesForBooking(TicketCounter $booking): void
    {
        if (!Schema::hasTable('ticket_counter_service_passes')) {
            return;
        }

        $booking->loadMissing('services.passes');

        foreach ($booking->services as $service) {
            $this->ensurePassesForService($service);
        }

        $booking->setRelation('services', $booking->services()->with('passes')->orderBy('id')->get());
    }

    public function ensurePassesForService(TicketCounterService $service): void
    {
        if (!Schema::hasTable('ticket_counter_service_passes')) {
            return;
        }

        $quantity = max(0, (int) $service->quantity);
        if ($quantity <= 0) {
            return;
        }

        if (blank($service->service_code)) {
            $service->forceFill(['service_code' => $this->uniqueServiceCode()])->save();
        }

        $existingPasses = $service->passes()->orderBy('unit_number')->orderBy('id')->get();

        for ($unit = $existingPasses->count() + 1; $unit <= $quantity; $unit++) {
            $preferredCode = null;
            if (filled($service->service_code)) {
                $preferredCode = $quantity > 1
                    ? $service->service_code . '-' . str_pad((string) $unit, 2, '0', STR_PAD_LEFT)
                    : (string) $service->service_code;
            }

            $code = $this->uniqueServiceCode($preferredCode);

            TicketCounterServicePass::create([
                'ticket_counter_service_id' => $service->id,
                'ticket_counter_id' => $service->ticket_counter_id,
                'event_id' => $service->event_id,
                'event_service_id' => $service->event_service_id,
                'service_code' => $code,
                'unit_number' => $unit,
                'status' => TicketCounterServicePass::STATUS_UNUSED,
            ]);
        }
    }

    private function uniqueServiceCode(?string $preferredCode = null): string
    {
        if (filled($preferredCode) && !TicketCounterServicePass::where('service_code', $preferredCode)->exists()) {
            return $preferredCode;
        }

        do {
            $serviceCode = 'SV-' . strtoupper(Str::random(10));
        } while (TicketCounterServicePass::where('service_code', $serviceCode)->exists());

        return $serviceCode;
    }
}
