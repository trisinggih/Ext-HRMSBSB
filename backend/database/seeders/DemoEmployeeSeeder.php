<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class DemoEmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        $company = Company::first();
        $branches = Branch::where('company_id', $company->id)->get();
        if ($branches->isEmpty()) {
            $this->command->error('Please ensure there is at least 1 branch in the database.');
            return;
        }

        $department = Department::first();
        $division = Division::first();
        $position = Position::first();

        $password = Hash::make('password123');
        $genders = ['male', 'female'];

        // Create 20 employees
        for ($i = 1; $i <= 20; $i++) {
            $branch = $branches[$i % $branches->count()];

            Employee::create([
                'employee_id' => 'EMP-' . str_pad(rand(1000, 9999) . $i, 6, '0', STR_PAD_LEFT),
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'phone' => $faker->phoneNumber,
                'password' => $password,
                'birth_date' => $faker->dateTimeBetween('-40 years', '-20 years')->format('Y-m-d'),
                'gender' => $faker->randomElement($genders),
                'address' => $faker->address,
                'ktp_number' => $faker->nik,
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'department_id' => $department->id,
                'division_id' => $division->id,
                'position_id' => $position->id,
                'join_date' => $faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
                'employment_status' => $faker->randomElement(['permanent', 'contract']),
                'is_active' => true,
            ]);
        }
    }
}
