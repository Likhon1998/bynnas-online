<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Order;
use App\Models\User;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;

/**
 * Leads / social inquiries. Conversion reuses the shared customer and order services,
 * so a converted lead produces a normal customer and a normal online order.
 */
class LeadService
{
    public function __construct(
        protected OrderCreationService $orders,
        protected StaffNotifier $notifier,
    ) {}

    public function create(int $shopId, array $data, ?User $by = null): Lead
    {
        $lead = DB::transaction(function () use ($shopId, $data, $by) {
            $lead = Lead::create(array_merge($data, [
                'shop_id' => $shopId,
                'status' => $data['status'] ?? 'new',
                'created_by' => $by?->id,
            ]));

            if ($lead->phone && ! $lead->customer_id) {
                $customer = $this->orders->findByPhone($shopId, $lead->phone);
                if ($customer) {
                    $lead->update(['customer_id' => $customer->id]);
                }
            }

            $this->log($lead, 'system', 'Lead added from '.$lead->sourceLabel().'.', $by);

            return $lead;
        });

        $this->notifier->send(
            $shopId,
            'lead',
            'New lead: '.$lead->name,
            $lead->sourceLabel().($lead->phone ? ' · '.$lead->phone : '')
                .($lead->assigned_to ? ' · assigned to '.($lead->assignee?->name ?? 'staff') : ''),
            route('leads.show', $lead),
            'lead_new:'.$lead->id,
            'manage leads',
            array_filter([$lead->assigned_to]),
            $by?->id,
        );

        return $lead;
    }

    /** Change the assignee and tell the new owner. */
    public function assign(Lead $lead, ?int $userId, ?User $by = null): Lead
    {
        if ((int) $lead->assigned_to === (int) $userId) {
            return $lead;
        }

        $lead->update(['assigned_to' => $userId]);
        $lead->load('assignee');
        $this->log($lead, 'system', $userId ? 'Assigned to '.$lead->assignee?->name.'.' : 'Unassigned.', $by, ['assigned_to' => $userId]);

        if ($userId && $userId !== $by?->id) {
            $this->notifier->sendToUser(
                $lead->shop_id,
                $userId,
                'lead',
                'Lead assigned to you: '.$lead->name,
                $lead->sourceLabel().($lead->phone ? ' · '.$lead->phone : '')
                    .($lead->follow_up_at ? ' · follow up '.$lead->follow_up_at->format('d M, h:i A') : ''),
                route('leads.show', $lead),
                'lead_assigned:'.$lead->id.':'.$userId,
            );
        }

        return $lead;
    }

    public function changeStatus(Lead $lead, string $status, ?string $note, ?User $by = null, ?string $lostReason = null): Lead
    {
        if ($lead->status === $status && ! $note) {
            return $lead;
        }

        $from = $lead->status;
        $updates = ['status' => $status];
        if ($status === 'lost') {
            $updates['lost_reason'] = $lostReason ?: $note;
        }
        if ($status !== 'follow_up' && $status !== $from && in_array($status, Lead::CLOSED_STATUSES, true)) {
            $updates['follow_up_at'] = null;
        }
        $lead->update($updates);

        $body = $from === $status
            ? $note
            : Lead::STATUSES[$from].' → '.Lead::STATUSES[$status].($note ? ': '.$note : '');
        $this->log($lead, 'status', $body, $by, ['from' => $from, 'to' => $status]);

        return $lead;
    }

    /** Record a real contact attempt (call / message / note) and optionally schedule a follow-up. */
    public function logContact(Lead $lead, string $type, ?string $body, ?string $followUpAt, ?User $by = null): Lead
    {
        $updates = [];
        if (in_array($type, ['call', 'message'], true)) {
            $updates['last_contacted_at'] = now();
            if ($lead->status === 'new') {
                $updates['status'] = 'contacted';
            }
        }
        if ($followUpAt) {
            $updates['follow_up_at'] = $followUpAt;
            if (! $lead->isClosed()) {
                $updates['status'] = 'follow_up';
            }
        }
        if ($updates) {
            $lead->update($updates);
        }

        $this->log($lead, $type, $body, $by);
        if ($followUpAt) {
            $this->log($lead, 'follow_up', 'Follow-up on '.$lead->follow_up_at->format('d M Y, h:i A'), $by);
        }

        return $lead;
    }

    /** Lead → Customer (no order yet). Requires a phone so the customer can be matched later. */
    public function convertToCustomer(Lead $lead, ?string $address, ?User $by = null): Customer
    {
        if (! $lead->phone) {
            throw new OrderCreationException('Add a phone number before creating a customer.');
        }

        // An existing customer with this phone is linked as-is; their saved details are not overwritten.
        $customer = $this->orders->findByPhone($lead->shop_id, $lead->phone);
        if (! $customer) {
            $customer = $this->orders->resolveCustomer($lead->shop_id, [
                'name' => $lead->name,
                'phone' => $lead->phone,
                'address' => trim((string) $address),
                'email' => $lead->email,
            ]);
        } elseif (! $customer->address && filled($address)) {
            $customer->update(['address' => trim((string) $address)]);
        }

        $lead->update(['customer_id' => $customer->id]);
        $this->log($lead, 'system', 'Linked to customer '.$customer->name.'.', $by, ['customer_id' => $customer->id]);

        return $customer;
    }

    /**
     * Lead → Order through the shared OrderCreationService.
     *
     * @param  list<array{id: int, qty: int}>  $items
     */
    public function convertToOrder(Lead $lead, array $items, array $options, ?User $by = null): Order
    {
        if (! $lead->phone) {
            throw new OrderCreationException('Add a phone number before placing an order.');
        }

        return DB::transaction(function () use ($lead, $items, $options, $by) {
            // Row lock: a double-submit must not place two orders for one lead.
            $locked = Lead::whereKey($lead->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'lost') {
                throw new OrderCreationException('This lead is marked lost. Change its status before placing an order.');
            }

            $openOrder = Order::where('lead_id', $locked->id)
                ->when($locked->order_id, fn ($q) => $q->orWhere('id', $locked->order_id))
                ->get()
                ->first(fn (Order $o) => ! OrderStatus::isVoid($o->workflowStatus()));
            if ($openOrder) {
                throw new OrderCreationException('This lead already has order '.$openOrder->invoice_no.'.');
            }

            $result = $this->orders->place($locked->shop_id, [
                'name' => $locked->name,
                'phone' => $locked->phone,
                'address' => (string) $options['address'],
                'email' => $locked->email,
            ], $items, null, [
                'zone' => $options['zone'] ?? null,
                'payment_method' => $options['payment_method'] ?? null,
                'note' => $options['note'] ?? null,
                'attribution' => $this->attributionFor($locked),
                'landing_page_id' => $locked->landing_page_id,
                'lead_id' => $locked->id,
                'context' => ['lead_id' => $locked->id, 'placed_by' => $by?->id],
            ]);

            return $result['order'];
        });
    }

    /**
     * Called for every new online order: connect it to the lead it came from
     * (explicit lead_id, otherwise the most recent open lead with the same phone).
     */
    public function linkOrder(Order $order, ?int $leadId = null): ?Lead
    {
        $lead = null;
        $leadId ??= $order->lead_id;

        if ($leadId) {
            $lead = Lead::forShop($order->shop_id)->find($leadId);
        }

        if (! $lead && $order->delivery_phone) {
            $lead = $this->findOpenByPhone($order->shop_id, $order->delivery_phone);
        }

        if (! $lead) {
            return null;
        }

        DB::transaction(function () use ($lead, $order) {
            $lead->update([
                'status' => 'converted',
                'customer_id' => $order->customer_id,
                'order_id' => $lead->order_id ?? $order->id,
                'converted_at' => $lead->converted_at ?? now(),
                'follow_up_at' => null,
            ]);

            if ((int) $order->lead_id !== (int) $lead->id) {
                $order->forceFill(['lead_id' => $lead->id])->saveQuietly();
            }

            $this->log($lead, 'conversion', 'Order '.$order->invoice_no.' placed (৳'.format_taka_number((float) $order->total_amount).').', null, ['order_id' => $order->id]);
        });

        return $lead;
    }

    public function findOpenByPhone(int $shopId, string $phone): ?Lead
    {
        $normalized = Customer::normalizePhone($phone);
        if (strlen($normalized) < 8) {
            return null;
        }

        return Lead::forShop($shopId)->open()
            ->where('phone', 'like', '%'.substr($normalized, -6))
            ->latest('id')
            ->get()
            ->first(fn (Lead $l) => Customer::normalizePhone($l->phone) === $normalized);
    }

    /** Order attribution for a lead: its campaign when linked, otherwise the platform it came from. */
    public function attributionFor(Lead $lead): array
    {
        if ($lead->campaign && (int) $lead->campaign->shop_id === (int) $lead->shop_id) {
            return [
                'campaign_id' => $lead->campaign->id,
                'utm_source' => $lead->campaign->source,
                'utm_medium' => $lead->campaign->medium,
                'utm_campaign' => $lead->campaign->utm_campaign,
            ];
        }

        return [
            'utm_source' => $lead->source,
            'utm_medium' => in_array($lead->source, ['whatsapp', 'messenger', 'facebook', 'instagram', 'tiktok'], true) ? 'message' : null,
        ];
    }

    public function log(Lead $lead, string $type, ?string $body, ?User $by = null, array $meta = []): LeadActivity
    {
        return LeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => $by?->id,
            'type' => $type,
            'body' => $body,
            'meta' => $meta ?: null,
        ]);
    }
}
