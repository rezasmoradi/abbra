<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Reserve;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use function Illuminate\Database\Eloquent\Factories\factoryForModel;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(50)->create()->each(function ($user) {
            $service = Service::factory(1)->createOne();
            $user->reserves()->create([
                'customer_id' => $user->id,
                'service_id' => $service->id,
                'reserved_at' => fake()->dateTimeBetween('-90 days'),
            ]);
        });
    }
}
