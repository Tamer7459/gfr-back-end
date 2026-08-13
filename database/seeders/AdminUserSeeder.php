<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * إنشاء حسابات تجريبية بجميع الأدوار.
     */
    public function run(): void
    {
        $users = [
            [
                'name'     => 'Admin',
                'email'    => 'admin@gmail.com',
                'password' => 'password123',
                'role'     => 'admin',
            ],
            [
                'name'     => 'Researcher',
                'email'    => 'researcher@gmail.com',
                'password' => 'password123',
                'role'     => 'researcher',
            ],
            [
                'name'     => 'Professor',
                'email'    => 'professor@gmail.com',
                'password' => 'password123',
                'role'     => 'professor',
            ],
            [
                'name'     => 'Reviewer',
                'email'    => 'reviewer@gmail.com',
                'password' => 'password123',
                'role'     => 'reviewer',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name'     => $user['name'],
                    'password' => $user['password'],
                    'role'     => $user['role'],
                ]
            );
        }
    }
}