<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => '管理者ユーザー',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role' => 1,
            ],
            [
                'name' => '山田 太郎',
                'email' => 'taro@example.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
                'role' => 2,
            ],
            [
                'name' => '佐藤 花子',
                'email' => 'hanako@example.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
                'role' => 2,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate($user);
        }

        User::factory()->count(4)->create([
            'role' => 2,
        ]);

    }
}
