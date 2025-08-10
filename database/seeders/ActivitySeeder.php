<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\Activity;
use App\Models\Mitra;

class ActivitySeeder extends Seeder
{
    public function run(): void
    // {
    //     $project1 = Project::first();
    //     $project2 = Project::find(2);
    //     $project3 = Project::find(3);
    //     $customer1 = Customer::find(1);
    //     $customer2 = Customer::find(2);
    //     $customer3 = Customer::find(3);
    //     $mitraVendor = Mitra::where('kategori', 'vendor')->first();

    //     if ($project1 && $customer1) {
    //         Activity::create([
    //             'name' => 'Laporan Pengeluaran Bulanan',
    //             'description' => 'Laporan pengeluaran bulanan untuk keperluan internal perusahaan.',
    //             'project_id' => $project1->id,
    //             'kategori' => 'Expense Report',
    //             'jenis' => 'Internal',
    //             'customer_id' => null,
    //             'activity_date' => '2025-05-13',
    //             'attachment' => null,
    //             'mitra_id' => 1,
    //         ]);

    //         Activity::create([
    //             'name' => 'Penerbitan Invoice Proyek A',
    //             'description' => 'Penerbitan invoice untuk pembayaran proyek A kepada customer.',
    //             'project_id' => $project1->id,
    //             'kategori' => 'Invoice',
    //             'jenis' => 'Customer',
    //             'customer_id' => $project1->customer_id,
    //             'activity_date' => '2025-05-15',
    //             'attachment' => null,
    //             'mitra_id' => null,
    //         ]);
            
    //         Activity::create([
    //             'name' => 'Pengajuan Penawaran Harga',
    //             'description' => 'Pengajuan quotation untuk kebutuhan vendor.',
    //             'project_id' => $project1->id,
    //             'kategori' => 'Quotation',
    //             'jenis' => 'Vendor',
    //             'customer_id' => null,
    //             'activity_date' => '2025-05-17',
    //             'attachment' => null,
    //             'mitra_id' => $mitraVendor ? $mitraVendor->id : 2,
    //         ]);
    //     }

    //     if ($project2 && $customer2) {
    //         Activity::create([
    //             'name' => 'Pembuatan Purchase Order',
    //             'description' => 'Pembuatan PO untuk pembelian barang dari vendor.',
    //             'project_id' => $project2->id,
    //             'kategori' => 'Purchase Order',
    //             'jenis' => 'Vendor',
    //             'customer_id' => null,
    //             'activity_date' => '2025-06-01',
    //             'attachment' => null,
    //             'mitra_id' => $mitraVendor ? $mitraVendor->id : 2,
    //         ]);

    //         Activity::create([
    //             'name' => 'Penerbitan Faktur Pajak',
    //             'description' => 'Faktur pajak diterbitkan untuk transaksi customer.',
    //             'project_id' => $project2->id,
    //             'kategori' => 'Faktur Pajak',
    //             'jenis' => 'Customer',
    //             'customer_id' => $project2->customer_id,
    //             'activity_date' => '2025-06-05',
    //             'attachment' => null,
    //             'mitra_id' => null,
    //         ]);
    //     }

    //     if ($project3 && $customer3) {
    //         Activity::create([
    //             'name' => 'Laporan Teknis Proyek',
    //             'description' => 'Laporan teknis untuk progres proyek.',
    //             'project_id' => $project3->id,
    //             'kategori' => 'Laporan Teknis',
    //             'jenis' => 'Internal',
    //             'customer_id' => null,
    //             'activity_date' => '2025-06-10',
    //             'attachment' => null,
    //             'mitra_id' => 1,
    //         ]);
    //     }
    // }
    {
        \App\Models\Activity::factory(1032)->create();
    }
} 