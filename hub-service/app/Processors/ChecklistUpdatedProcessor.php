<?php

namespace App\Processors;

use Illuminate\Support\Facades\Log;

class ChecklistUpdatedProcessor extends BaseProcessor
{
    public function process(array $data): void
    {
        try {
            $checklistId = $data['id'] ?? null;
            if (! $checklistId) {
                throw new \InvalidArgumentException('Missing checklist id');
            }

            $payload = [
                'id' => $checklistId,
                'attributes' => $data,
            ];

            // Update cached checklist
            $key = $this->cacheKey('checklist', $checklistId);
            $this->updateCache($key, $payload, 300);

            // Invalidate related summaries
            $this->invalidateCache('checklists:summary');

            // Broadcast to a country-scoped channel if provided
            $country = $data['country'] ?? 'global';
            $channel = sprintf('checklist.%s', $country);
            $this->broadcast($channel, $payload);

            Log::info('Processed ChecklistUpdated', ['id' => $checklistId]);
        } catch (\Throwable $e) {
            Log::error('ChecklistUpdatedProcessor error', ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        }
    }
}
