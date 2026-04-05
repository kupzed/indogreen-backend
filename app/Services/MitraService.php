<?php

namespace App\Services;

use App\Models\Mitra;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MitraService
{
    public function getPaginatedMitras(array $filters, int $perPage = 10, string $sortBy = 'created', string $sortDir = 'desc'): LengthAwarePaginator
    {
        $query = Mitra::query()->filter($filters);

        $dir = in_array(strtolower($sortDir), ['asc', 'desc'], true) ? strtolower($sortDir) : 'desc';

        switch ($sortBy) {
            case 'created':
            default:
                $query->orderBy('id', $dir);
                break;
        }

        return $query->paginate($perPage);
    }

    public function getMitraDetail(Mitra $mitra): Mitra
    {
        return $mitra;
    }

    public function getFormDependencies(): array
    {
        return [
            'kategori_options' => [
                ['value' => 'pribadi', 'label' => 'Pribadi'],
                ['value' => 'perusahaan', 'label' => 'Perusahaan'],
                ['value' => 'customer', 'label' => 'Customer'],
                ['value' => 'vendor', 'label' => 'Vendor'],
            ]
        ];
    }

    public function createMitra(array $data): Mitra
    {
        $data['is_pribadi'] = isset($data['is_pribadi']) ? filter_var($data['is_pribadi'], FILTER_VALIDATE_BOOLEAN) : false;
        $data['is_perusahaan'] = isset($data['is_perusahaan']) ? filter_var($data['is_perusahaan'], FILTER_VALIDATE_BOOLEAN) : false;
        $data['is_customer'] = isset($data['is_customer']) ? filter_var($data['is_customer'], FILTER_VALIDATE_BOOLEAN) : false;
        $data['is_vendor'] = isset($data['is_vendor']) ? filter_var($data['is_vendor'], FILTER_VALIDATE_BOOLEAN) : false;

        return Mitra::create($data);
    }

    public function updateMitra(Mitra $mitra, array $data): Mitra
    {
        $data['is_pribadi'] = isset($data['is_pribadi']) ? filter_var($data['is_pribadi'], FILTER_VALIDATE_BOOLEAN) : $mitra->is_pribadi;
        $data['is_perusahaan'] = isset($data['is_perusahaan']) ? filter_var($data['is_perusahaan'], FILTER_VALIDATE_BOOLEAN) : $mitra->is_perusahaan;
        $data['is_customer'] = isset($data['is_customer']) ? filter_var($data['is_customer'], FILTER_VALIDATE_BOOLEAN) : $mitra->is_customer;
        $data['is_vendor'] = isset($data['is_vendor']) ? filter_var($data['is_vendor'], FILTER_VALIDATE_BOOLEAN) : $mitra->is_vendor;

        $mitra->update($data);

        return $mitra;
    }

    public function deleteMitra(Mitra $mitra): bool
    {
        return $mitra->delete();
    }
}
