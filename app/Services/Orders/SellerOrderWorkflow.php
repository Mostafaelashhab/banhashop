<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SellerOrderStatus;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\SellerOrder;
use App\Models\User;
use App\Services\Catalog\OfferInventoryService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The seller-side order lifecycle, with the customer-facing order status
 * derived from its parts rather than stored twice.
 */
class SellerOrderWorkflow
{
    public function __construct(private readonly OfferInventoryService $inventory) {}

    public function transition(
        SellerOrder $sellerOrder,
        SellerOrderStatus $target,
        ?User $actor = null,
        ?string $reason = null,
    ): SellerOrder {
        if (! $sellerOrder->canTransitionTo($target)) {
            throw new RuntimeException('لا يمكن تغيير حالة الطلب من "'.$sellerOrder->status->label().'" إلى "'.$target->label().'".');
        }

        return DB::transaction(function () use ($sellerOrder, $target, $actor, $reason) {
            $now = Carbon::now();
            $sellerOrder->status = $target;

            match ($target) {
                SellerOrderStatus::Accepted => $this->onAccepted($sellerOrder, $now),
                SellerOrderStatus::Preparing => null,
                SellerOrderStatus::Shipped => $this->onShipped($sellerOrder, $now),
                SellerOrderStatus::Delivered => $this->onDelivered($sellerOrder, $now),
                SellerOrderStatus::Rejected => $this->onCancelled($sellerOrder, $now, $reason, rejected: true),
                SellerOrderStatus::Cancelled => $this->onCancelled($sellerOrder, $now, $reason, rejected: false),
                default => null,
            };

            $sellerOrder->save();

            OrderEvent::create([
                'order_id' => $sellerOrder->order_id,
                'seller_order_id' => $sellerOrder->id,
                'user_id' => $actor?->id,
                'status' => $target->value,
                'note' => $reason ?: $target->label(),
            ]);

            $this->syncParentOrder($sellerOrder->order()->with('sellerOrders')->first());

            return $sellerOrder;
        });
    }

    private function onAccepted(SellerOrder $sellerOrder, Carbon $now): void
    {
        $sellerOrder->accepted_at = $now;
        $sellerOrder->seller()->increment('accepted_orders_count');
    }

    private function onShipped(SellerOrder $sellerOrder, Carbon $now): void
    {
        $sellerOrder->shipped_at = $now;
        $sellerOrder->shipment()->update(['status' => 'in_transit', 'picked_up_at' => $now]);
    }

    private function onDelivered(SellerOrder $sellerOrder, Carbon $now): void
    {
        $sellerOrder->delivered_at = $now;
        $sellerOrder->shipment()->update(['status' => 'delivered', 'delivered_at' => $now]);
    }

    /** Rejections and cancellations put the reserved stock back on the shelf. */
    private function onCancelled(SellerOrder $sellerOrder, Carbon $now, ?string $reason, bool $rejected): void
    {
        if ($rejected) {
            $sellerOrder->rejected_at = $now;
            $sellerOrder->rejection_reason = $reason;
        } else {
            $sellerOrder->cancelled_at = $now;
        }

        $sellerOrder->seller()->increment('cancelled_orders_count');

        foreach ($sellerOrder->items()->with('sellerOffer')->get() as $item) {
            if ($item->sellerOffer !== null) {
                $this->inventory->restore($item->sellerOffer, $item->quantity);
            }
        }
    }

    /**
     * The customer's order status is a projection of its seller orders. It is
     * never set by hand, so it cannot drift out of sync with fulfilment.
     */
    public function syncParentOrder(Order $order): void
    {
        $statuses = $order->sellerOrders->pluck('status');

        if ($statuses->isEmpty()) {
            return;
        }

        $dead = [SellerOrderStatus::Rejected, SellerOrderStatus::Cancelled];
        $allDead = $statuses->every(fn (SellerOrderStatus $s) => in_array($s, $dead, true));
        $liveStatuses = $statuses->reject(fn (SellerOrderStatus $s) => in_array($s, $dead, true));

        $status = match (true) {
            $allDead => OrderStatus::Cancelled,
            $liveStatuses->every(fn (SellerOrderStatus $s) => $s === SellerOrderStatus::Delivered) => OrderStatus::Completed,
            $liveStatuses->contains(SellerOrderStatus::Shipped) => OrderStatus::InDelivery,
            $liveStatuses->contains(fn (SellerOrderStatus $s) => in_array($s, [SellerOrderStatus::Accepted, SellerOrderStatus::Preparing], true)) => OrderStatus::Confirmed,
            default => OrderStatus::Placed,
        };

        $order->status = $status;

        if ($status === OrderStatus::Completed && $order->completed_at === null) {
            $order->completed_at = Carbon::now();

            // Cash on delivery settles the moment the goods land.
            if ($order->payment_method === PaymentMethod::Cod) {
                $order->payment_status = PaymentStatus::Paid;
                $order->payments()->update(['status' => PaymentStatus::Paid->value, 'paid_at' => Carbon::now()]);
            }
        }

        if ($status === OrderStatus::Cancelled && $order->cancelled_at === null) {
            $order->cancelled_at = Carbon::now();
        }

        $order->save();
    }
}
