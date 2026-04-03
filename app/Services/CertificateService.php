<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateAttachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CertificateService
{
    /**
     * Store new certificate and handle files.
     */
    public function createCertificate(array $validatedData, array $files = [], array $names = [], array $descs = []): Certificate
    {
        return DB::transaction(function () use ($validatedData, $files, $names, $descs) {
            $certificate = Certificate::create($validatedData);

            $this->handleNewAttachments($certificate, $files, $names, $descs);

            return $certificate->load(['project', 'barangCertificate', 'attachments']);
        });
    }

    /**
     * Update existing certificate, remove specified files, update metadata for existing files, and attach new.
     */
    public function updateCertificate(
        Certificate $certificate, 
        array $validatedData,
        array $removedIds = [],
        array $existingIds = [],
        array $existingNames = [],
        array $existingDescs = [],
        array $files = [], 
        array $names = [], 
        array $descs = []
    ): Certificate {
        return DB::transaction(function () use (
            $certificate, $validatedData, 
            $removedIds, $existingIds, $existingNames, $existingDescs, 
            $files, $names, $descs
        ) {
            // 1) Hapus lampiran lama yang dipilih
            if (!empty($removedIds)) {
                $toDelete = CertificateAttachment::whereIn('id', $removedIds)
                    ->where('certificate_id', $certificate->id)
                    ->get();

                /** @var CertificateAttachment $att */
                foreach ($toDelete as $att) {
                    if ($att->file_path && Storage::disk('public')->exists($att->file_path)) {
                        Storage::disk('public')->delete($att->file_path);
                    }
                    $att->delete();
                }
            }

            // 2) Update data certificate
            $certificate->update($validatedData);

            // 3) Update NAMA & DESKRIPSI lampiran lama (jika ada)
            $existingIdsValues = array_values($existingIds);
            $existingNamesValues = array_values($existingNames);
            $existingDescsValues = array_values($existingDescs);

            foreach ($existingIdsValues as $i => $attId) {
                $att = CertificateAttachment::where('id', $attId)
                    ->where('certificate_id', $certificate->id)
                    ->first();

                if ($att) {
                    if (array_key_exists($i, $existingNamesValues)) {
                        $att->name = $existingNamesValues[$i];
                    }
                    if (array_key_exists($i, $existingDescsValues)) {
                        $att->description = $existingDescsValues[$i];
                    }
                    $att->save();
                }
            }

            // 4) Simpan lampiran baru (jika ada)
            $this->handleNewAttachments($certificate, $files, $names, $descs);

            return $certificate->load(['project', 'barangCertificate', 'attachments']);
        });
    }

    /**
     * Delete certificate and remove its physical attachments.
     */
    public function deleteCertificate(Certificate $certificate): void
    {
        foreach ($certificate->attachments as $att) {
            if ($att->file_path && Storage::disk('public')->exists($att->file_path)) {
                Storage::disk('public')->delete($att->file_path);
            }
        }
        
        if ($certificate->attachment && Storage::disk('public')->exists($certificate->attachment)) {
            Storage::disk('public')->delete($certificate->attachment);
        }

        $certificate->delete();
    }

    /**
     * Handle file uploads and persist metadata into CertificateAttachment.
     */
    protected function handleNewAttachments(Certificate $certificate, array $files, array $names, array $descs): void
    {
        foreach ($files as $i => $file) {
            if (!$file) continue;

            $path = $file->store('attachments/certificates/' . $certificate->id, 'public');
            $displayName = $names[$i] ?? $file->getClientOriginalName();
            $desc = $descs[$i] ?? null;

            $certificate->attachments()->create([
                'name'        => $displayName,
                'description' => $desc,
                'file_path'   => $path,
                'mime'        => $file->getClientMimeType(),
                'size'        => $file->getSize(),
            ]);
        }
    }
}
