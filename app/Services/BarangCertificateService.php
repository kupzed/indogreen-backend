<?php

namespace App\Services;

use App\Models\BarangCertificate;
use App\Models\Mitra;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BarangCertificateService
{
    /**
     * Get paginated barang certificates with filters.
     */
    public function getPaginatedBarangCertificates(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $allowed = [10, 25, 50, 100];
        if (!in_array($perPage, $allowed, true)) {
            $perPage = 10;
        }

        return BarangCertificate::with('mitra')
            ->filter($filters)
            ->paginate($perPage);
    }

    /**
     * Get barang certificate detail with relations.
     */
    public function getBarangCertificateDetail(BarangCertificate $barangCertificate): BarangCertificate
    {
        return $barangCertificate->load(['mitra', 'certificates']);
    }

    /**
     * Get form dependencies for barang certificate.
     */
    public function getFormDependencies(): array
    {
        return [
            'mitras' => Mitra::select('id', 'nama')->get()
        ];
    }
    /**
     * Create a new barang certificate.
     */
    public function createBarangCertificate(array $data): BarangCertificate
    {
        return BarangCertificate::create($data);
    }

    /**
     * Update an existing barang certificate.
     */
    public function updateBarangCertificate(BarangCertificate $barangCertificate, array $data): BarangCertificate
    {
        $barangCertificate->update($data);
        
        return $barangCertificate;
    }

    /**
     * Delete a barang certificate.
     */
    public function deleteBarangCertificate(BarangCertificate $barangCertificate): void
    {
        $barangCertificate->delete();
    }
}
