<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;
use Exception;

class SyncOnlineToLocal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hrms:sync-online-to-local
                            {--from-local-online : Use local hrms_online database as the cloud source}
                            {--online-host= : Online database host (default: 76.13.16.13)}
                            {--online-port= : Online database port (default: 3306)}
                            {--online-db= : Online database name (default: ext_hrms)}
                            {--online-user= : Online database username (default: hrms)}
                            {--online-pass= : Online database password}
                            {--tables= : Comma-separated specific table names to sync}
                            {--with-locations : Include full employee_locations (GPS tracking)}
                            {--recent-locations-days=30 : Sync employee_locations only for the last N days}
                            {--dry-run : Simulate sync without writing to local database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize shifts, attendance, approvals, and operational data from online database to local database with new schema adaptation';

    /**
     * Online database config credentials.
     */
    protected array $onlineConfig = [
        'host' => '76.13.16.13',
        'port' => 3306,
        'database' => 'ext_hrms',
        'username' => 'hrms',
        'password' => 'Hrms123!Secure',
    ];

    /**
     * Tables prioritized for synchronization.
     */
    protected array $syncTables = [
        // 1. Organization & Core
        'companies',
        'company_groups',
        'branches',
        'departments',
        'divisions',
        'positions',
        'job_levels',
        'provinces',
        'cities',
        'assets',
        'roles',
        'permissions',
        'role_permission',
        'admins',
        'admin_role',

        // 2. Shifts & Patterns
        'shifts',
        'employee_shift_patterns',
        'employee_shifts',

        // 3. Employee Master & Setup
        'employees',
        'employee_devices',
        'employee_assignments',
        'employee_assignment_payroll_components',
        'employee_salary_components',
        'employee_approval_lines',
        'employee_terminations',

        // 4. Approval Workflows & Requests
        'approval_workflows',
        'approval_steps',
        'approval_requests',
        'approval_logs',

        // 5. Attendance & Permissions / Requests
        'attendance',
        'attendance_logs',
        'location_tracking_sessions',
        'late_requests',
        'sick_leave_requests',
        'permission_requests',
        'overtime_requests',
        'workday_swap_requests',
        'leave_types',
        'leave_balances',
        'loan_requests',
        'reimbursements',

        // 6. Tasks, KPI & Operations
        'check_points',
        'tasks',
        'task_assignments',
        'kpi_periods',
        'kpi_templates',
        'kpi_indicators',
        'kpi_template_indicators',
        'kpi_rating_scales',
        'kpi_rating_levels',
        'recruitment_jobs',
        'candidates',
        'candidate_applications',
        'candidate_documents',
        'tests',
        'test_questions',

        // 7. General & Informational
        'announcements',
        'announcement_reads',
        'notifications',
        'activity_logs',
        'cms_sections',
        'cms_settings',
        'cms_products',
        'system_settings',
        'payroll_periods',
        'payroll_settings',
        'payroll_components',
        'payroll_rules',
        'ptkp_settings',
        'bpjs_settings',
        'tax_brackets',
        'tax_ter_rates',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("=========================================================");
        $this->info("  HRMS: Sync Online Database to Local (Schema-Adaptive)  ");
        $this->info("=========================================================");

        $isDryRun = (bool)$this->option('dry-run');
        if ($isDryRun) {
            $this->warn("MODE: DRY-RUN (Tidak akan menulis perubahan ke database lokal)");
        }

        // Apply CLI overrides for online connection
        if ($this->option('from-local-online')) {
            $this->onlineConfig['host'] = '127.0.0.1';
            $this->onlineConfig['port'] = 3306;
            $this->onlineConfig['database'] = 'hrms_online';
            $this->onlineConfig['username'] = 'root';
            $this->onlineConfig['password'] = '';
        } else {
            if ($this->option('online-host')) $this->onlineConfig['host'] = $this->option('online-host');
            if ($this->option('online-port')) $this->onlineConfig['port'] = (int)$this->option('online-port');
            if ($this->option('online-db'))   $this->onlineConfig['database'] = $this->option('online-db');
            if ($this->option('online-user')) $this->onlineConfig['username'] = $this->option('online-user');
            if ($this->option('online-pass') !== null) $this->onlineConfig['password'] = $this->option('online-pass');
        }

        // 1. Connect to online and local DB
        try {
            $onlinePdo = new PDO(
                "mysql:host={$this->onlineConfig['host']};port={$this->onlineConfig['port']};dbname={$this->onlineConfig['database']};charset=utf8mb4",
                $this->onlineConfig['username'],
                $this->onlineConfig['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 5,
                ]
            );
            $this->info("✓ Terhubung ke Online DB: {$this->onlineConfig['database']} ({$this->onlineConfig['host']})");
        } catch (Exception $e) {
            $this->error("Gagal terhubung ke database online: " . $e->getMessage());
            
            // Check if local hrms_online exists as fallback
            try {
                $testLocalOnline = new PDO("mysql:host=127.0.0.1;port=3306;dbname=hrms_online", "root", "");
                $this->warn("! Terdeteksi database 'hrms_online' di localhost (snapshot cloud). Gunakan opsi '--from-local-online' untuk menyinkronkan dari database ini.");
            } catch (Exception $ex) {}

            return 1;
        }

        try {
            $localHost = config('database.connections.mysql.host', '127.0.0.1');
            $localPort = config('database.connections.mysql.port', '3306');
            $localDb   = config('database.connections.mysql.database', 'hrms_bsb_db');
            $localUser = config('database.connections.mysql.username', 'root');
            $localPass = config('database.connections.mysql.password', '');

            $localPdo = new PDO(
                "mysql:host={$localHost};port={$localPort};dbname={$localDb};charset=utf8mb4",
                $localUser,
                $localPass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
            $this->info("✓ Terhubung ke Local DB: {$localDb} ({$localHost})");
        } catch (Exception $e) {
            $this->error("Gagal terhubung ke database lokal: " . $e->getMessage());
            return 1;
        }

        // Determine list of tables to sync
        $tablesToSync = $this->syncTables;
        if ($this->option('tables')) {
            $tablesToSync = array_map('trim', explode(',', $this->option('tables')));
        }

        // Include employee_locations if requested
        if ($this->option('with-locations')) {
            $tablesToSync[] = 'employee_locations';
        }

        $onlineAllTables = $onlinePdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $localAllTables  = $localPdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        $tablesToSync = array_filter($tablesToSync, function ($t) use ($onlineAllTables, $localAllTables) {
            return in_array($t, $onlineAllTables) && in_array($t, $localAllTables);
        });

        $this->info("Total tabel yang akan disinkronkan: " . count($tablesToSync));
        $this->line("");

        // Disable Foreign Keys during sync
        if (!$isDryRun) {
            $localPdo->exec("SET FOREIGN_KEY_CHECKS=0;");
        }

        $totalSyncedRows = 0;
        $totalTablesSuccess = 0;
        $startTime = microtime(true);

        foreach ($tablesToSync as $table) {
            try {
                // Get column names
                $onlineCols = array_column($onlinePdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(), 'Field');
                $localCols  = array_column($localPdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(), 'Field');

                // Get common columns
                $commonCols = array_values(array_intersect($onlineCols, $localCols));
                if (empty($commonCols)) {
                    $this->warn("[- SKIP] Tabel `$table`: Tidak ada kolom yang cocok.");
                    continue;
                }

                // Check count in online
                $onlineCount = (int)$onlinePdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                $localCount  = (int)$localPdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();

                if ($onlineCount === 0) {
                    $this->line(sprintf("  %-32s | Online: 0 | Local: %d (Skip, online kosong)", $table, $localCount));
                    continue;
                }

                $newColsInLocal = array_diff($localCols, $onlineCols);
                $newColsNote = !empty($newColsInLocal) ? " (Ada kolom baru lokal: " . implode(',', $newColsInLocal) . ")" : "";

                $this->line(sprintf("➔ %-32s | Online: %6d | Local: %6d%s", $table, $onlineCount, $localCount, $newColsNote));

                if ($isDryRun) {
                    continue;
                }

                // Build UPSERT SQL
                $colListSql = implode(', ', array_map(fn($c) => "`$c`", $commonCols));
                $placeholders = implode(', ', array_fill(0, count($commonCols), '?'));
                $updateList = implode(', ', array_map(fn($c) => "`$c` = VALUES(`$c`)", $commonCols));

                $insertSql = "INSERT INTO `$table` ($colListSql) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updateList";
                $stmtInsert = $localPdo->prepare($insertSql);

                // Fetch data from online and execute with transaction for high performance
                $syncedInTable = 0;

                // Special handling for employee_locations to avoid downloading 440k rows if not specified
                $whereClause = "";
                if ($table === 'employee_locations' && !$this->option('with-locations')) {
                    $days = (int)$this->option('recent-locations-days');
                    $dateLimit = date('Y-m-d', strtotime("-{$days} days"));
                    $whereClause = " WHERE recorded_at >= '{$dateLimit}' OR created_at >= '{$dateLimit}'";
                }

                $selectSql = "SELECT $colListSql FROM `$table`" . $whereClause;
                $queryStmt = $onlinePdo->query($selectSql);

                $localPdo->beginTransaction();
                try {
                    while ($row = $queryStmt->fetch()) {
                        $values = array_values($row);
                        $stmtInsert->execute($values);
                        $syncedInTable++;
                        $totalSyncedRows++;
                    }
                    $localPdo->commit();
                } catch (Exception $txEx) {
                    $localPdo->rollBack();
                    throw $txEx;
                }

                // Verify new local count
                $newLocalCount = (int)$localPdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                $this->info("   ✓ Sukses sinkron `$table`: {$syncedInTable} baris diproses. (Total lokal sekarang: {$newLocalCount})");
                $totalTablesSuccess++;

            } catch (Exception $e) {
                $this->error("   ✗ Error pada tabel `$table`: " . $e->getMessage());
            }
        }

        // Re-enable Foreign Keys
        if (!$isDryRun) {
            $localPdo->exec("SET FOREIGN_KEY_CHECKS=1;");
        }

        $duration = round(microtime(true) - $startTime, 2);

        $this->line("");
        $this->info("=========================================================");
        $this->info("  SINKRONISASI SELESAI!");
        $this->info("  Tabel Berhasil: {$totalTablesSuccess}");
        $this->info("  Total Baris Diproses: {$totalSyncedRows}");
        $this->info("  Waktu Eksekusi: {$duration} detik");
        $this->info("=========================================================");

        return 0;
    }
}
