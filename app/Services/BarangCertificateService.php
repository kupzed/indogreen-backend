<?php

namespace App\Services;

use App\Models\BarangCertificate;
use App\Models\Mitra;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BarangCertificateService
{
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

    public function getBarangCertificateDetail(BarangCertificate $barangCertificate): BarangCertificate
    {
        return $barangCertificate->load(['mitra', 'certificates']);
    }

    public function getFormDependencies(): array
    {
        return [
            'mitras' => Mitra::select('id', 'nama')->get()
        ];
    }

    public function createBarangCertificate(array $data): BarangCertificate
    {
        return BarangCertificate::create($data);
    }

    public function updateBarangCertificate(BarangCertificate $barangCertificate, array $data): BarangCertificate
    {
        $barangCertificate->update($data);
        
        return $barangCertificate;
    }

    public function deleteBarangCertificate(BarangCertificate $barangCertificate): void
    {
        $barangCertificate->delete();
    }
}
