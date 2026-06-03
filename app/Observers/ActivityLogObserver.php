<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogObserver
{
    private array $excludedFields = ['password', 'remember_token', 'created_at', 'updated_at', 'deleted_at'];

    public function created(Model $model): void
    {
        $this->log('created', $model, null, $this->cleanData($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $original = $this->cleanData($model->getOriginal());
        $changes = $this->cleanData($model->getChanges());

        $filteredOriginal = array_intersect_key($original, $changes);
        $filteredChanges = array_diff_assoc($changes, $original);

        if (empty($filteredChanges)) {
            return;
        }

        $this->log('updated', $model, $filteredOriginal, $filteredChanges);
    }

    public function deleted(Model $model): void
    {
        $this->log('deleted', $model, $this->cleanData($model->getOriginal()), null);
    }

    public function forceDeleted(Model $model): void
    {
        $this->log('force_deleted', $model, $this->cleanData($model->getOriginal()), null);
    }

    public function restored(Model $model): void
    {
        $this->log('restored', $model, null, $this->cleanData($model->getAttributes()));
    }

    private function log(string $action, Model $model, ?array $oldData, ?array $newData): void
    {
        $request = request();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model' => get_class($model),
            'model_id' => $model->getKey(),
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    private function cleanData(array $data): array
    {
        return array_diff_key($data, array_flip($this->excludedFields));
    }
}
