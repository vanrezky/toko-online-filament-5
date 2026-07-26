<?php

namespace App\Services;

use App\Models\FlashsaleReservation;
use App\Models\ProductFlashsale;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class FlashsaleReservationService
{
    /** @param Collection<int, array{item: mixed, pricing: array}> $resolvedItems */
    public function reserve(Transaction $transaction, Collection $resolvedItems): void
    {
        $reservations = $resolvedItems
            ->filter(fn (array $entry) => $entry['pricing']['flashsale_product_id'] !== null)
            ->groupBy(fn (array $entry) => $entry['pricing']['flashsale_product_id'])
            ->map(fn (Collection $entries) => (int) $entries->sum(fn (array $entry) => $entry['item']->quantity));

        foreach ($reservations as $flashsaleProductId => $quantity) {
            $flashsaleProduct = ProductFlashsale::query()->lockForUpdate()->findOrFail($flashsaleProductId);

            if ($flashsaleProduct->stock < $quantity) {
                abort(422, 'Kuota flashsale untuk salah satu produk tidak mencukupi.');
            }

            $flashsaleProduct->decrement('stock', $quantity);
            $transaction->flashsaleReservations()->create([
                'product_flashsale_id' => $flashsaleProduct->id,
                'quantity' => $quantity,
            ]);
        }
    }

    public function release(Transaction $transaction): void
    {
        $transaction->loadMissing('flashsaleReservations');

        foreach ($transaction->flashsaleReservations->whereNull('released_at') as $reservation) {
            $updated = FlashsaleReservation::query()
                ->whereKey($reservation->id)
                ->whereNull('released_at')
                ->update(['released_at' => now(), 'updated_at' => now()]);

            if ($updated === 1) {
                ProductFlashsale::query()->whereKey($reservation->product_flashsale_id)
                    ->increment('stock', $reservation->quantity);
            }
        }
    }
}
