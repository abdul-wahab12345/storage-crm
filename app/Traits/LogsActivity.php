<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;

trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            static::writeLog($model, 'created', [], $model->getAttributes());
        });

        static::updated(function ($model) {
            static::writeLog($model, 'updated', $model->getOriginal(), $model->getChanges());
        });

        static::deleted(function ($model) {
            static::writeLog($model, 'deleted', $model->getAttributes(), []);
        });
    }

    protected static function writeLog($model, string $action, array $old, array $new): void
    {
        try {
            $user = auth()->user();

            $changes = null;
            if ($action === 'updated' && ! empty($new)) {
                $exclude = ['updated_at', 'created_at', 'remember_token', 'password'];
                $filteredNew = array_diff_key($new, array_flip($exclude));
                $filteredOld = array_intersect_key($old, $filteredNew);
                if (! empty($filteredNew)) {
                    $changes = ['old' => $filteredOld, 'new' => $filteredNew];
                }
            }

            ActivityLog::create([
                'model_type'  => class_basename($model),
                'model_id'    => $model->getKey(),
                'action'      => $action,
                'changes'     => $changes,
                'user_id'     => $user?->id,
                'user_name'   => $user?->name ?? 'System',
                'ip_address'  => request()?->ip(),
                'created_at'  => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('ActivityLog: failed to write log — ' . $e->getMessage());
        }
    }
}
