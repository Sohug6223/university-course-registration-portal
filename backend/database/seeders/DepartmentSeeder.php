<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            [
                'code' => 'CSE',
                'name' => 'Computer Science and Engineering',
                'status' => 'Active',
            ],
            [
                'code' => 'EEE',
                'name' => 'Electrical and Electronic Engineering',
                'status' => 'Active',
            ],
            [
                'code' => 'BBA',
                'name' => 'Business Administration',
                'status' => 'Active',
            ],
            [
                'code' => 'ENG',
                'name' => 'English',
                'status' => 'Active',
            ],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(
                ['code' => $department['code']],
                $department
            );
        }
    }
}