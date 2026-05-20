<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $subtotal = $this->products->sum(fn($p) => ($p->price - $p->discount) * $p->quantity);
        $total = $subtotal + $this->shipping_cost + $this->cod_fee;

        $shippingGroups = $this->shippingDetails->groupBy('warehouse_id')->map(function ($items, $warehouseId) {
            $firstItem = $items->first();
            $warehouse = $firstItem->warehouse;

            return [
                'warehouse_id' => $warehouseId,
                'warehouse_name' => $warehouse?->name,
                'courier_code' => $firstItem->courier_code,
                'courier_name' => $firstItem->courier_name,
                'estimation' => $firstItem->estimation,
                'price' => $items->sum('price'),
                'is_pickup' => $firstItem->courier_code === 'PICKUP',
                'warehouse_address' => $warehouse ? implode(', ', array_filter([
                    $warehouse->address,
                    $warehouse->village?->name,
                    $warehouse->district?->name,
                    $warehouse->province?->name,
                    $warehouse->postal_code,
                ])) : null,
            ];
        })->values();

        $hasDelivery = $shippingGroups->contains(fn ($group) => ! $group['is_pickup']);

        return [
            'id' => $this->uuid,
            'code' => $this->code,
            'timelimit' => $this->timelimit,
            'weight' => $this->weight,
            'shipping_cost' => $this->shipping_cost,
            'cod_fee' => $this->cod_fee,
            'payment_method' => $this->payment_method,
            'payment_type' => $this->payment_type,
            'installment_plan_id' => $this->installment_plan_id,
            'installment_plan' => $this->installment?->installmentPlan ? [
                'tenor' => $this->installment->installmentPlan->tenor,
                'fee_percentage' => $this->installment->installmentPlan->fee_percentage,
                'monthly_amount' => $this->installment->monthly_amount,
            ] : null,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'subtotal' => $subtotal,
            'total' => $total,
            'address' => $hasDelivery ? [
                'name' => $this->address?->name,
                'phone' => $this->address?->phone,
                'full_address' => $this->address?->address,
                'village' => $this->address?->village?->name,
                'sub_district' => $this->address?->subDistrict?->name,
                'district' => $this->address?->district?->name,
                'province' => $this->address?->province?->name,
                'postal_code' => $this->address?->postal_code,
            ] : null,
            'shipping_groups' => $shippingGroups,
            'products' => OrderItemResource::collection($this->products),
        ];
    }
}
