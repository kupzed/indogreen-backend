<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityAttachment;
use App\Models\Mitra;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ActivityService
{
    /**
     * Store a newly created Activity and handle file uploads.
     *
     * @param array $data
     * @param array $files
     * @param array $names
     * @param array $descs
     * @return Activity
     */
    public function createActivity(array $data, array $files = [], array $names = [], array $descs = []): Activity
    {
        return DB::transaction(function () use ($data, $files, $names, $descs) {
            $activity = Activity::create($data);

            $this->handleNewAttachments($activity, $files, $names, $descs);

            return $activity->load(['project', 'mitra', 'attachments']);
        });
    }

    /**
     * Update an Activity, managing old and new attachments.
     *
     * @param Activity $activity
     * @param array $data
     * @param array $files
     * @param array $names
     * @param array $descs
     * @param array $removedIds
     * @param array $existingIds
     * @param array $existingNames
     * @param array $existingDescs
     * @return Activity
     */
    public function updateActivity(
        Activity $activity,
        array $data,
        array $files = [],
        array $names = [],
        array $descs = [],
        array $removedIds = [],
        array $existingIds = [],
        array $existingNames = [],
        array $existingDescs = []
    ): Activity {
        return DB::transaction(function () use (
            $activity, $data, $files, $names, $descs,
            $removedIds, $existingIds, $existingNames, $existingDescs
        ) {
            // 1) Remove obsolete attachments
            if (!empty($removedIds)) {
                $this->removeAttachments($activity->id, $removedIds);
            }

            // 2) Update main data
            $activity->update($data);

            // 3) Update descriptions/names of existing attachments
            $this->updateExistingAttachments($activity->id, $existingIds, $existingNames, $existingDescs);

            // 4) Add new attachments
            $this->handleNewAttachments($activity, $files, $names, $descs);

            return $activity->load(['project', 'mitra', 'attachments']);
        });
    }

    /**
     * Delete an activity along with all its files.
     *
     * @param Activity $activity
     * @return void
     */
    public function deleteActivity(Activity $activity): void
    {
        foreach ($activity->attachments as $att) {
            if ($att->file_path && Storage::disk('public')->exists($att->file_path)) {
                Storage::disk('public')->delete($att->file_path);
            }
        }
        $activity->delete();
    }

    /**
     * Get vendor list formatted for specific project.
     *
     * @param int|null $projectId
     * @return \Illuminate\Database\Eloquent\Collection|array
     */
    public function getVendorOptions(?int $projectId)
    {
        if (!$projectId) {
            return [];
        }

        $vendorIds = Activity::where('project_id', $projectId)
            ->where('jenis', 'Vendor')
            ->whereNotNull('mitra_id')
            ->pluck('mitra_id')
            ->unique()
            ->values();

        return Mitra::whereIn('id', $vendorIds)->get(['id', 'nama']);
    }

    /**
     * Handle saving new attachment files.
     */
    private function handleNewAttachments(Activity $activity, array $files, array $names, array $descs): void
    {
        foreach ($files as $i => $file) {
            if (!$file) continue;

            $path = $file->store('attachments/activities/' . $activity->id, 'public');
            $displayName = $names[$i] ?? $file->getClientOriginalName();
            $desc = $descs[$i] ?? null;

            $activity->attachments()->create([
                'name'        => $displayName,
                'description' => $desc,
                'file_path'   => $path,
                'mime'        => $file->getClientMimeType(),
                'size'        => $file->getSize(),
            ]);
        }
    }

    /**
     * Remove obsolete attachments by their ID.
     */
    private function removeAttachments(int $activityId, array $removedIds): void
    {
        $toDelete = ActivityAttachment::whereIn('id', $removedIds)
            ->where('activity_id', $activityId)
            ->get();

        foreach ($toDelete as $att) {
            if ($att->file_path && Storage::disk('public')->exists($att->file_path)) {
                Storage::disk('public')->delete($att->file_path);
            }
            $att->delete();
        }
    }

    /**
     * Update existing attachments name and description
     */
    private function updateExistingAttachments(int $activityId, array $existingIds, array $existingNames, array $existingDescs): void
    {
        $existingIds   = array_values($existingIds);
        $existingNames = array_values($existingNames);
        $existingDescs = array_values($existingDescs);

        foreach ($existingIds as $i => $attId) {
            $att = ActivityAttachment::where('id', $attId)
                ->where('activity_id', $activityId)
                ->first();

            if ($att) {
                if (array_key_exists($i, $existingNames)) {
                    $att->name = $existingNames[$i];
                }
                if (array_key_exists($i, $existingDescs)) {
                    $att->description = $existingDescs[$i];
                }
                $att->save();
            }
        }
    }
}
