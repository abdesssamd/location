<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    public static function log(
        string $action,
        ?Model $model = null,
        ?array $old = null,
        ?array $new = null,
        ?User $user = null,
        ?int $storeId = null
    ): Audit {
        $user ??= auth()->user();
        $storeId ??= $user?->is_super_admin ? null : ($user?->store_id ?? StoreContext::id());

        return Audit::create([
            'store_id' => $storeId,
            'user_id' => $user?->id,
            'action' => $action,
            'auditable_type' => $model?->getMorphClass(),
            'auditable_id' => $model?->getKey(),
            'old_values' => $old ? json_encode($old) : null,
            'new_values' => $new ? json_encode($new) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public static function created(Model $model, string $label = 'created'): void
    {
        static::log($label, $model, null, $model->getAttributes());
    }

    public static function updated(Model $model, array $old, string $label = 'updated'): void
    {
        static::log($label, $model, $old, $model->getChanges());
    }

    public static function deleted(Model $model, string $label = 'deleted'): void
    {
        static::log($label, $model, $model->getAttributes(), null);
    }
}