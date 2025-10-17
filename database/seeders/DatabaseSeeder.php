<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Transaction;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Cristobal',
                'email' => 'cristobal@example.com',
                'password' => Hash::make('123456'),
                'saldo_inicial' => 5000
            ],
            [
                'name' => 'María',
                'email' => 'maria@example.com',
                'password' => Hash::make('123456'),
                'saldo_inicial' => 3000
            ],
            [
                'name' => 'Juan',
                'email' => 'juan@example.com',
                'password' => Hash::make('123456'),
                'saldo_inicial' => 2000
            ]
        ];

        foreach ($users as $userData) {
            User::factory()->create($userData);
        }

        $allUsers = User::all();

        for ($i = 0; $i < 10; $i++) {
            $sender = $allUsers->random();
            $receiver = $allUsers->where('id', '!=', $sender->id)->random();

            Transaction::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'amount' => fake()->randomFloat(2, 10, 500),
                'description' => fake()->sentence(5),
                'status' => 'completed',
                'transaction_hash' => Str::uuid(),
                'completed_at' => Carbon::now()->subDays(rand(0, 5))
            ]);
        }
    }
}
