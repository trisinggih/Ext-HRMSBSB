<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Division;
use App\Models\Position;
use App\Models\Employee;
use App\Models\EmployeeAssignment;

class ImportEmployeeCsv extends Command
{
    protected $signature   = 'import:employee-csv {file=data.csv}';
    protected $description = 'Import employee data from CSV file with full relation resolution';

    // Cache maps keyed by unique keys to avoid duplicate DB queries
    private array $companyMap    = [];
    private array $branchMap     = [];
    private array $departmentMap = [];
    private array $divisionMap   = [];
    private array $positionMap   = [];
    private array $employeeMap   = []; // employee_id (NIP) => DB id

    public function handle(): int
    {
        $filePath = base_path($this->argument('file'));

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info("Reading CSV: {$filePath}");

        // ─── Parse CSV (semicolon-delimited, may contain quoted multiline fields) ────
        $rows = $this->parseCsv($filePath);
        $this->info("Total rows: " . count($rows));

        DB::transaction(function () use ($rows) {

            // ── Pass 1: Create Employee records (without supervisor) so IDs exist ──
            $this->info("Pass 1: Creating employees…");
            foreach ($rows as $i => $row) {
                $nip = trim($row['employee_id (NIP)'] ?? '');
                if (!$nip) continue;

                $this->upsertEmployee($row);
                if ($i % 10 === 0) $this->output->write('.');
            }
            $this->newLine();

            // Reload employee NIP → id map
            $this->buildEmployeeMap();

            // ── Pass 2: Create / link assignments & set supervisor ───────────────
            $this->info("Pass 2: Creating assignments & linking supervisors…");
            foreach ($rows as $row) {
                $nip = trim($row['employee_id (NIP)'] ?? '');
                if (!$nip) continue;

                $this->createAssignment($row);
                $this->linkSupervisor($row);
            }
        });

        $this->newLine();
        $this->info('✅ Import complete!');

        // Summary
        $this->table(
            ['Entity', 'Count'],
            [
                ['Companies',   Company::count()],
                ['Branches',    Branch::count()],
                ['Departments', Department::count()],
                ['Divisions',   Division::count()],
                ['Positions',   Position::count()],
                ['Employees',   Employee::count()],
                ['Assignments', EmployeeAssignment::count()],
            ]
        );

        return 0;
    }

    // ────────────────────────────────────────────────────────────────────────────
    // CSV Parser (handles semicolons + quoted multiline fields)
    // ────────────────────────────────────────────────────────────────────────────
    private function parseCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        $headers = null;
        $rows = [];

        // Read raw content, normalise line endings
        $content = file_get_contents($filePath);
        $content = str_replace("\r\n", "\n", $content);
        $content = str_replace("\r", "\n", $content);

        // Use PHP's built-in CSV parser with semicolon separator
        $tmpFile = tempnam(sys_get_temp_dir(), 'csv_');
        file_put_contents($tmpFile, $content);

        $fh = fopen($tmpFile, 'r');
        $headers = null;

        while (($cols = fgetcsv($fh, 0, ';')) !== false) {
            if ($headers === null) {
                $headers = array_map('trim', $cols);
                continue;
            }

            // Pad short rows
            while (count($cols) < count($headers)) {
                $cols[] = '';
            }

            $row = array_combine($headers, $cols);

            // Skip blank rows
            $nip = trim($row['employee_id (NIP)'] ?? '');
            if (!$nip) continue;

            $rows[] = $row;
        }

        fclose($fh);
        @unlink($tmpFile);

        return $rows;
    }

    // ────────────────────────────────────────────────────────────────────────────
    // Master data resolvers (get-or-create)
    // ────────────────────────────────────────────────────────────────────────────

    private function resolveCompany(string $name): Company
    {
        $name = trim($name);
        
        if (strtolower($name) === 'digital marketing' || strtolower($name) === 'opmc') {
            $name = 'OPMC';
        } else if (strtolower($name) === 'jangeby office' || strtolower($name) === 'jangeby central') {
            $name = 'Jangeby Office';
        } else if (stripos($name, 'peby motor') !== false) {
            $name = 'Peby Motor';
        } else {
            // Default any unknown or unsynced company to Jangeby Office
            $name = 'Jangeby Office';
        }

        if (isset($this->companyMap[$name])) return $this->companyMap[$name];

        $company = Company::firstOrCreate(
            ['name' => $name],
            ['legal_name' => $name, 'is_active' => true, 'group_id' => 1]
        );

        $this->companyMap[$name] = $company;
        return $company;
    }

    private function resolveBranch(string $branchName, Company $company): Branch
    {
        $branchName = trim($branchName);
        
        // Match the branch to the company
        if ($company->name === 'OPMC') {
            $branchName = 'OPMC';
        } else if ($company->name === 'Jangeby Office') {
            $branchName = 'Jangeby Office';
        } else if ($company->name === 'Peby Motor') {
            // keep the specific peby motor branch if provided, otherwise default
            if (stripos($branchName, 'peby motor') === false) {
                $branchName = 'Peby Motor 1';
            }
        }

        $key = $company->id . '|' . $branchName;
        if (isset($this->branchMap[$key])) return $this->branchMap[$key];

        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $branchName), 0, 4));
        if (stripos($branchName, 'peby motor') !== false) {
            preg_match('/peby motor\s*(\w+)/i', $branchName, $m);
            $code = 'PM' . strtoupper($m[1] ?? '');
        }
        if (strtolower($branchName) == 'jangeby office') $code = 'JO';
        if (strtolower($branchName) == 'opmc') $code = 'OPMC';

        $branch = Branch::firstOrCreate(
            ['company_id' => $company->id, 'name' => $branchName],
            ['code' => $code, 'is_active' => true]
        );
        $this->branchMap[$key] = $branch;
        return $branch;
    }

    private function resolveDepartment(string $deptName, Branch $branch): Department
    {
        $key = $branch->id . '|' . trim($deptName);
        if (isset($this->departmentMap[$key])) return $this->departmentMap[$key];

        $acronym = $this->generateAcronym(trim($deptName));
        $code = ($branch->code ? $branch->code . '-' : '') . $acronym;

        $dept = Department::firstOrCreate(
            ['branch_id' => $branch->id, 'name' => trim($deptName)],
            ['code' => $code, 'is_active' => true]
        );
        $this->departmentMap[$key] = $dept;
        return $dept;
    }

    private function resolveDivision(string $divName, Department $dept, Branch $branch): Division
    {
        $key = $dept->id . '|' . trim($divName);
        if (isset($this->divisionMap[$key])) return $this->divisionMap[$key];

        $acronym = $this->generateAcronym(trim($divName));
        $code = ($dept->code ? $dept->code . '-' : '') . $acronym;

        $div = Division::firstOrCreate(
            ['branch_id' => $branch->id, 'department_id' => $dept->id, 'name' => trim($divName)],
            ['code' => $code, 'is_active' => true]
        );
        $this->divisionMap[$key] = $div;
        return $div;
    }

    private function resolvePosition(string $posName, Division $div, Department $dept, Branch $branch, Company $company): Position
    {
        $key = $div->id . '|' . trim($posName);
        if (isset($this->positionMap[$key])) return $this->positionMap[$key];

        $acronym = $this->generateAcronym(trim($posName), true);
        $code = ($div->code ? $div->code . '-' : '') . $acronym;

        $pos = Position::firstOrCreate(
            ['division_id' => $div->id, 'name' => trim($posName)],
            [
                'company_id'    => $company->id,
                'branch_id'     => $branch->id,
                'department_id' => $dept->id,
                'code'          => $code,
                'is_active'     => true,
            ]
        );
        $this->positionMap[$key] = $pos;
        return $pos;
    }

    private function generateAcronym(string $string, bool $isPos = false): string
    {
        if (!$string) return 'XX';
        $words = explode(' ', preg_replace('/[^A-Za-z0-9 ]/', '', $string));
        if (count($words) == 1) {
            $word = strtoupper($words[0]);
            return substr($word, 0, min(3, strlen($word)));
        }
        $code = '';
        foreach($words as $w) {
            if ($w) $code .= strtoupper($w[0]);
        }
        return substr($code, 0, 4);
    }

    // ────────────────────────────────────────────────────────────────────────────
    // Employee upsert
    // ────────────────────────────────────────────────────────────────────────────

    private function upsertEmployee(array $row): void
    {
        $nip       = trim($row['employee_id (NIP)'] ?? '');
        $name      = trim($row['name'] ?? '');
        $email     = trim($row['email'] ?? '');
        $phone     = trim($row['phone'] ?? '');
        $gender    = $this->mapGender(trim($row['gender'] ?? ''));
        $address   = trim($row['address'] ?? '');
        $ktp       = trim($row['ktp_number'] ?? '');
        $npwp      = trim($row['npwp'] ?? '');
        $bpjsKes   = trim($row['bpjs_kesehatan'] ?? '');
        $bpjsTk    = trim($row['bpjs_ketenagakerjaan'] ?? '');
        $birthDate = $this->parseDate(trim($row['birth_date'] ?? ''));

        $employeeData = [
            'name'                  => $name,
            'phone'                 => $phone ?: null,
            'gender'                => $gender,
            'address'               => $address ?: null,
            'ktp_number'            => $ktp ?: null,
            'npwp'                  => ($npwp && !in_array($npwp, ['0', '-'])) ? $npwp : null,
            'bpjs_kesehatan'        => $bpjsKes ?: null,
            'bpjs_ketenagakerjaan'  => $bpjsTk ?: null,
            'birth_date'            => $birthDate,
            'is_active'             => true,
            'password'              => Hash::make('Password123!'),
        ];

        // Ensure email is always set (NOT NULL constraint)
        if ($email) {
            $employeeData['email'] = $email;
        } else {
            $employeeData['email'] = strtolower(preg_replace('/[^a-zA-Z0-9]/', '.', $nip)) . '@noemail.local';
        }

        try {
            Employee::updateOrCreate(
                ['employee_id' => $nip],
                $employeeData
            );
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Email already taken by another employee — use NIP-based dummy email
            $dummyEmail = strtolower(preg_replace('/[^a-zA-Z0-9]/', '.', $nip)) . '@noemail.local';
            $employeeData['email'] = $dummyEmail;
            Employee::updateOrCreate(
                ['employee_id' => $nip],
                $employeeData
            );
            $this->warn("\n  ⚠ Duplicate email '{$email}' for NIP {$nip}, used dummy: {$dummyEmail}");
        }
    }

    private function buildEmployeeMap(): void
    {
        $this->employeeMap = Employee::pluck('id', 'employee_id')->all();
    }

    // ────────────────────────────────────────────────────────────────────────────
    // Assignment creation
    // ────────────────────────────────────────────────────────────────────────────

    private function createAssignment(array $row): void
    {
        $nip            = trim($row['employee_id (NIP)'] ?? '');
        $companyName    = trim($row['company_name'] ?? '');
        $branchName     = trim($row['branch_name'] ?? '');
        $deptName       = trim($row['department_name'] ?? '');
        $divName        = trim($row['division_name'] ?? '');
        $posName        = trim($row['position_name'] ?? '');
        $joinDate       = $this->parseDate(trim($row['join_date'] ?? ''));
        $empStatus      = $this->mapEmploymentStatus(trim($row['employment_status'] ?? ''));

        if (!$nip || !$companyName) return;

        $employeeDbId = $this->employeeMap[$nip] ?? null;
        if (!$employeeDbId) return;

        try {
            $company    = $this->resolveCompany($companyName);
            $branch     = $this->resolveBranch($branchName ?: $companyName, $company);
            $dept       = $this->resolveDepartment($deptName ?: 'Umum', $branch);
            $div        = $this->resolveDivision($divName ?: $deptName ?: 'Umum', $dept, $branch);
            $pos        = $this->resolvePosition($posName ?: 'Staff', $div, $dept, $branch, $company);

            // Only create if not already assigned to this position/branch
            $existing = EmployeeAssignment::where('employee_id', $employeeDbId)
                ->where('branch_id', $branch->id)
                ->where('position_id', $pos->id)
                ->first();

            if (!$existing) {
                EmployeeAssignment::create([
                    'employee_id'       => $employeeDbId,
                    'company_id'        => $company->id,
                    'branch_id'         => $branch->id,
                    'department_id'     => $dept->id,
                    'division_id'       => $div->id,
                    'position_id'       => $pos->id,
                    'join_date'         => $joinDate,
                    'employment_status' => $empStatus,
                    'is_primary'        => true,
                ]);
            }
        } catch (\Throwable $e) {
            $this->warn("  Skipping assignment for {$nip}: " . $e->getMessage());
        }
    }

    // ────────────────────────────────────────────────────────────────────────────
    // Supervisor linking (Pass 2)
    // ────────────────────────────────────────────────────────────────────────────

    private function linkSupervisor(array $row): void
    {
        $nip       = trim($row['employee_id (NIP)'] ?? '');
        $atasanNip = trim($row['atasan_id'] ?? '');

        if (!$nip || !$atasanNip || $nip === $atasanNip) return;

        $employeeDbId = $this->employeeMap[$nip] ?? null;
        $supervisorId = $this->employeeMap[$atasanNip] ?? null;

        if (!$employeeDbId || !$supervisorId) return;

        // Update the primary assignment with the supervisor
        EmployeeAssignment::where('employee_id', $employeeDbId)
            ->where('is_primary', true)
            ->update(['supervisor_id' => $supervisorId]);
    }

    // ────────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────────

    private function parseDate(string $raw): ?string
    {
        if (!$raw) return null;

        // Try formats: d/m/Y, d/m/y, Y-m-d, m/d/Y
        $formats = ['d/m/Y', 'd/m/y', 'Y-m-d', 'm/d/Y'];
        foreach ($formats as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $raw);
            if ($dt) {
                // Sanity check: reject obviously wrong years
                $year = (int) $dt->format('Y');
                if ($year > 1900 && $year <= 2030) {
                    return $dt->format('Y-m-d');
                }
            }
        }

        return null;
    }

    private function mapEmploymentStatus(string $raw): string
    {
        return match (strtolower($raw)) {
            'tetap', 'permanent'       => 'permanent',
            'kontrak', 'contract'      => 'contract',
            'percobaan', 'probation'   => 'probation',
            'magang', 'intern'         => 'intern',
            default                    => 'intern',
        };
    }

    private function mapGender(string $raw): ?string
    {
        return match (strtolower($raw)) {
            'laki-laki', 'male', 'pria', 'l' => 'male',
            'perempuan', 'female', 'wanita', 'p' => 'female',
            default => null,
        };
    }
}
