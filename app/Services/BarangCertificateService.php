<?php

namespace App\Services;

use App\Models\BarangCertificate;

class BarangCertificateService
{
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
