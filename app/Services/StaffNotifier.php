<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\StaffAlert;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Sends in-app alerts to staff of one shop: shop owners / admins always,
 * plus staff holding the given permission and any explicitly named users.
 */
class StaffNotifier
{
    /**
     * @param  list<int>  $alsoUserIds
     */
    public function send(
        int $shopId,
        string $kind,
        string $title,
        string $body,
        ?string $url = null,
        ?string $ref = null,
        ?string $permission = null,
        array $alsoUserIds = [],
        ?int $exceptUserId = null,
    ): int {
        $recipients = $this->recipients($shopId, $permission, $alsoUserIds)
            ->reject(fn (User $u) => $exceptUserId && $u->id === $exceptUserId);

        if ($recipients->isEmpty()) {
            return 0;
        }

        try {
            Notification::send($recipients, new StaffAlert($kind, $title, $body, $url, $ref, $shopId));
        } catch (\Throwable $e) {
            report($e);

            return 0;
        }

        return $recipients->count();
    }

    /** Alert one staff member of the shop (ignored when the user is not active staff of that shop). */
    public function sendToUser(int $shopId, int $userId, string $kind, string $title, string $body, ?string $url = null, ?string $ref = null): bool
    {
        $user = User::query()->staffMembers()->where('shop_id', $shopId)->whereKey($userId)->first();
        if (! $user || $user->is_suspended) {
            return false;
        }

        try {
            $user->notify(new StaffAlert($kind, $title, $body, $url, $ref, $shopId));
        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        return true;
    }

    /** @return Collection<int, User> */
    public function recipients(int $shopId, ?string $permission = null, array $alsoUserIds = []): Collection
    {
        $alsoUserIds = array_values(array_filter(array_map('intval', $alsoUserIds)));

        return User::query()
            ->staffMembers()
            ->where('shop_id', $shopId)
            ->where(fn ($q) => $q->whereNull('is_suspended')->orWhere('is_suspended', false))
            ->get()
            ->filter(fn (User $u) => $u->isAdminUser()
                || in_array($u->id, $alsoUserIds, true)
                || ($permission && $u->can($permission)))
            ->values();
    }

    /** True when an alert with this ref was already sent in the shop within the window. */
    public function recentlySent(int $shopId, string $ref, ?\DateTimeInterface $since = null, bool $unreadOnly = false): bool
    {
        return DatabaseNotification::query()
            ->where('type', StaffAlert::class)
            ->where('data', 'like', '%"ref":'.json_encode($ref).'%')
            ->where('data', 'like', '%"shop_id":'.$shopId.'%')
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->when($unreadOnly, fn ($q) => $q->whereNull('read_at'))
            ->exists();
    }
}
