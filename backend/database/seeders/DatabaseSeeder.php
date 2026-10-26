<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Division;
use App\Models\Position;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ===== ROLE & PERMISSION =====
        $roles = ['super_admin', 'hrd', 'payroll_manager', 'supervisor', 'administrator'];
        foreach ($roles as $r) {
            Role::firstOrCreate(['slug' => $r], ['name' => ucwords(str_replace('_', ' ', $r)), 'slug' => $r]);
        }

        $permissionGroups = [
            'dashboard' => ['view_dashboard'],
            'company' => ['view_company', 'create_company', 'edit_company', 'delete_company'],
            'branch' => ['view_branch', 'create_branch', 'edit_branch', 'delete_branch'],
            'department' => ['view_department', 'create_department', 'edit_department', 'delete_department'],
            'division' => ['view_division', 'create_division', 'edit_division', 'delete_division'],
            'position' => ['view_position', 'create_position', 'edit_position', 'delete_position'],
            'employee' => ['view_employee', 'create_employee', 'edit_employee', 'delete_employee'],
            'attendance' => ['view_attendance', 'manage_attendance'],
            'shift' => ['view_shift', 'manage_shift'],
            'leave' => ['view_leave', 'approve_leave'],
            'payroll' => ['view_payroll', 'manage_payroll', 'approve_payroll'],
            'tax' => ['view_tax', 'manage_tax'],
            'bpjs' => ['view_bpjs', 'manage_bpjs'],
            'approval' => ['manage_approval'],
            'user' => ['manage_users', 'manage_roles'],
        ];
        foreach ($permissionGroups as $permissions) {
            foreach ($permissions as $p) {
                Permission::firstOrCreate(['slug' => $p], ['name' => ucwords(str_replace('_', ' ', $p)), 'slug' => $p]);
            }
        }

        // Super Admin dapat semua permission
        $super = Role::where('slug', 'super_admin')->first();
        if ($super) {
            $super->permissions()->sync(Permission::pluck('id'));
        }
        // HRD dapat sebagian besar
        $hrd = Role::where('slug', 'hrd')->first();
        if ($hrd) {
            $hrd->permissions()->sync(
                Permission::whereIn('slug', [
                    'view_dashboard','view_employee','create_employee','edit_employee','delete_employee',
                    'view_attendance','manage_attendance','view_shift','manage_shift','view_leave','approve_leave',
                    'view_company','view_branch','view_department','view_division','view_position',
                ])->pluck('id')
            );
        }

        // ===== DEFAULT ADMIN =====
        $admin = Admin::firstOrCreate(
            ['email' => 'admin@ext-hrms.com'],
            [
                'name' => 'Super Admin',
                'email' => 'admin@ext-hrms.com',
                'password' => Hash::make('admin123'),
                'role' => 'super_admin',
                'is_active' => true,
            ]
        );
        $admin->roles()->sync([$super->id] ?? []);

        // ===== MASTER COMPANY SAMPLE =====
        $company = Company::firstOrCreate(
            ['name' => 'BSB Village'],
            [
                'legal_name' => 'BSB Village Semarang',
                'email' => 'info@bsbvillage.com',
                'phone' => '081225566775',
                'address' => 'Jl. Boulevard Raya BSB Village No. 1, Kel. Bubakan, Kec. Mijen',
                'city' => 'Kota Semarang',
                'province' => 'Jawa Tengah',
                'is_active' => true,
            ]
        );

        $branch = Branch::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'BSB Village Semarang'],
            [
                'code' => 'BSB-VIL',
                'address' => 'Jl. Boulevard Raya BSB Village No. 1, Kel. Bubakan, Kec. Mijen',
                'city' => 'Kota Semarang',
                'province' => 'Jawa Tengah',
                'postal_code' => '50212',
                'latitude' => -7.0877000,
                'longitude' => 110.3201000,
                'attendance_radius' => 100,
                'phone' => '081225566775',
                'email' => 'info@bsbvillage.com',
                'is_active' => true,
            ]
        );

        $dept = Department::firstOrCreate(
            ['branch_id' => $branch->id, 'name' => 'Human Resource'],
            ['code' => 'HR', 'description' => 'Divisi SDM', 'is_active' => true]
        );

        $div = Division::firstOrCreate(
            ['department_id' => $dept->id, 'name' => 'HR Operations'],
            ['code' => 'HRO', 'is_active' => true]
        );

        $pos = Position::firstOrCreate(
            ['name' => 'HR Staff'],
            ['division_id' => $div->id, 'branch_id' => $branch->id, 'code' => 'HR-ST', 'base_salary' => 5000000, 'is_active' => true]
        );

        // ===== DEFAULT EMPLOYEE (mobile login test) =====
        Employee::firstOrCreate(
            ['email' => 'karyawan@ext-hrms.com'],
            [
                'employee_id' => 'EMP-0001',
                'name' => 'Karyawan Uji',
                'email' => 'karyawan@ext-hrms.com',
                'phone' => '081234567890',
                'password' => Hash::make('password123'),
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'department_id' => $dept->id,
                'division_id' => $div->id,
                'position_id' => $pos->id,
                'join_date' => now(),
                'employment_status' => 'permanent',
                'is_active' => true,
            ]
        );
    }
}
