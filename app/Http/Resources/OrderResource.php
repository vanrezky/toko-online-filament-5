<?php

namespace App\Http\Resources;

use App\Enums\CourierCode;
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
        $subtotal = (float) $this->subtotal;
        $total = (float) $this->total_amount;
        $cancelledAt = (($this->status?->value ?? (string) $this->status) === 'cancelled') ? $this->updated_at : null;

        $shippingGroups = $this->shippingDetails->groupBy('warehouse_id')->map(function ($items, $warehouseId) {
            $firstItem = $items->first();
            $warehouse = $firstItem->warehouse;

            return [
                'warehouse_id' => $warehouseId,
                'warehouse_name' => $warehouse?->name,
                'warehouse_contact_name' => $warehouse?->contact_name,
                'warehouse_contact_phone' => $warehouse?->contact_phone,
                'courier_code' => $firstItem->courier_code,
                'courier_name' => $firstItem->courier_name,
                'estimation' => $firstItem->estimation,
                'price' => $items->sum('price'),
                'is_pickup' => strtolower((string) $firstItem->courier_code) === CourierCode::PICKUP->value,
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
            'receipt_code' => $this->receipt_code,
            'billing_status' => $this->billing_status,
            'billing_due_date' => $this->billing_due_date,
            'delivery_date' => $this->delivery_date,
            'complete_date' => $this->complete_date,
            'cancelled_at' => $cancelledAt,
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
            'product_discount' => $this->product_discount,
            'voucher_discount' => $this->voucher_discount,
            'total_discount' => $this->total_discount,
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
            'vouchers' => $this->vouchers->map(fn ($voucher) => [
                'voucher_code' => $voucher->voucher_code,
                'voucher_name' => $voucher->voucher_name,
                'voucher_type' => $voucher->voucher_type,
                'discount_amount' => (float) $voucher->discount_amount,
            ])->values(),
        ];
    }
}
