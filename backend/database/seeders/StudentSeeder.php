<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('user_id', '0272320005101147')->first();

        if ($user) {
            Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'department' => 'CSE',
                    'semester' => '7th',
                    'status' => 'Active',
                ]
            );
        }
    }
}