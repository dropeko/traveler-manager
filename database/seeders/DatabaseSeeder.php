<?php

namespace Database\Seeders;

use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => '123',
        ]);

        $users = [
            User::factory()->create([
                'name' => 'Test User 1',
                'email' => 'test1@example.com',
                'password' => '123',
                'role' => User::ROLE_USER,
            ]),
            User::factory()->create([
                'name' => 'Test User 2',
                'email' => 'test2@example.com',
                'password' => '123',
                'role' => User::ROLE_USER,
            ]),
            User::factory()->create([
                'name' => 'Test User 3',
                'email' => 'test3@example.com',
                'password' => '123',
                'role' => User::ROLE_USER,
            ]),
        ];

        foreach ($users as $i => $user) {
            TravelOrder::factory()
                ->for($user)
                ->create([
                    'requester_name' => $user->name,
                    'status' => TravelOrder::STATUS_REQUESTED,
                ]);

            $second = ($i % 2 === 0)
                ? TravelOrder::factory()->approved()
                : TravelOrder::factory()->cancelled();

            $second
                ->for($user)
                ->create([
                    'requester_name' => $user->name,
                ]);
        }
    }
}