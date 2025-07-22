<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\Mitra;

class ProjectSeeder extends Seeder
{
    public function run(): void
    // {
    //     $customerIds = Customer::pluck('id')->toArray();
    //     Project::create([
    //         'name' => 'Lorem Sample Project One',
    //         'description' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Itaque eum reiciendis dicta veritatis rerum at sit nostrum aliquam corrupti voluptates libero voluptas accusantium iste eius inventore numquam, harum omnis dolores.',
    //         'status' => 'Prospect',
    //         'start_date' => '2025-05-19',
    //         'customer_id' => $customerIds[0] ?? null,
    //     ]);

    //     Project::create([
    //         'name' => 'Sample Lorem Project Two',
    //         'description' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Velit cum excepturi distinctio? Laboriosam assumenda, tenetur laudantium alias tempora temporibus molestias ab nemo laborum corporis voluptates porro odit, at neque. Sed.',
    //         'status' => 'Ongoing',
    //         'start_date' => '2025-05-20',
    //         'customer_id' => $customerIds[1] ?? null,
    //     ]);
        
    //     Project::create([
    //         'name' => 'Project Sample Lorem Three',
    //         'description' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Cupiditate animi perferendis a eos, tempora fugiat illum ad quae neque maiores deserunt ipsum repellat, exercitationem, temporibus vitae? Odit dignissimos impedit ad.',
    //         'status' => 'Cancel',
    //         'start_date' => '2025-06-02',
    //         'finish_date' => '2025-06-20',
    //         'customer_id' => $customerIds[2] ?? null,
    //     ]);
        
    //     Project::create([
    //         'name' => 'Lorem Project Sample Four',
    //         'description' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Officiis blanditiis dolor deserunt! Aliquam hic molestiae sapiente? Quibusdam minima maxime, pariatur adipisci et magni voluptas dolorum aliquam similique dolores. Laboriosam, unde!',
    //         'status' => 'Complete',
    //         'start_date' => '2024-06-03',
    //         'finish_date' => '2024-09-03',
    //         'customer_id' => $customerIds[0] ?? null,
    //     ]);
        
    // }
    {
        $mitraIds = Mitra::where('is_customer', true)->pluck('id')->toArray();
        Project::create([
            'name' => 'INDOGREEN',
            'description' => 'Project Internal Indogreen',
            'status' => 'Prospect',
            'start_date' => '2023-05-19',
            'mitra_id' => 1,
        ]);

        \App\Models\Project::factory(15)->create();
    }
} 