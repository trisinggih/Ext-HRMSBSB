<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;
use Exception;

class SyncLocalOrgToOnline extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hrms:sync-local-org-to-online
                            {--online-host= : Online database host (default: from config)}
                            {--online-port= : Online database port (default: from config)}
                            {--online-db= : Online database name (default: from config)}
                            {--online-user= : Online database username (default: from config)}
                            {--online-pass= : Online database password (default: from config)}
                            {--no-backup : Skip creating SQL backup before sync}
                            {--dry-run : Simulate sync without modifying online database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize departments, divisions, positions, and their references from local database to online production database';

    public function handle()
    {
        $this->info("=========================================================");
        $this->info("  HRMS: Sync Local Organization to Online Database       ");
        $this->info("=========================================================");

        $isDryRun = (bool)$this->option('dry-run');
        if ($isDryRun) {
            $this->warn("MODE: DRY-RUN (Simulasi, tidak akan menulis perubahan ke database online)");
        }

        // Connection configs
        $localConfig = [
            'host'     => config('database.connections.mysql.host', '127.0.0.1'),
            'port'     => config('database.connections.mysql.port', '3306'),
            'database' => config('database.connections.mysql.database', 'hrms_bsb_db'),
            'username' => config('database.connections.mysql.username', 'root'),
            'password' => config('database.connections.mysql.password', ''),
        ];

        $onlineConfig = [
            'host'     => $this->option('online-host') ?: config('database.connections.cloud.host', '76.13.16.13'),
            'port'     => (int)($this->option('online-port') ?: config('database.connections.cloud.port', '3306')),
            'database' => $this->option('online-db') ?: config('database.connections.cloud.database', 'ext_hrms'),
            'username' => $this->option('online-user') ?: config('database.connections.cloud.username', 'hrms'),
            'password' => $this->option('online-pass') !== null ? $this->option('online-pass') : config('database.connections.cloud.password', 'Hrms123!Secure'),
        ];

        // 1. Establish PDO connections
        try {
            $localPdo = new PDO(
                "mysql:host={$localConfig['host']};port={$localConfig['port']};dbname={$localConfig['database']};charset=utf8mb4",
                $localConfig['username'],
                $localConfig['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
            $this->info("✓ Terhubung ke Local DB: {$localConfig['database']} ({$localConfig['host']})");
        } catch (Exception $e) {
            $this->error("Gagal terhubung ke database lokal: " . $e->getMessage());
            return 1;
        }

        try {
            $onlinePdo = new PDO(
                "mysql:host={$onlineConfig['host']};port={$onlineConfig['port']};dbname={$onlineConfig['database']};charset=utf8mb4",
                $onlineConfig['username'],
                $onlineConfig['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 5,
                ]
            );
            $this->info("✓ Terhubung ke Online DB: {$onlineConfig['database']} ({$onlineConfig['host']})");
        } catch (Exception $e) {
            $this->error("Gagal terhubung ke database online: " . $e->getMessage());
            return 1;
        }

        // 2. Read local data
        $localDepts = $localPdo->query("SELECT * FROM `departments` ORDER BY `id`")->fetchAll();
        $localDivs  = $localPdo->query("SELECT * FROM `divisions` ORDER BY `id`")->fetchAll();
        $localPos   = $localPdo->query("SELECT * FROM `positions` ORDER BY `id`")->fetchAll();
        $localAssignments = $localPdo->query("SELECT `id`, `employee_id`, `department_id`, `division_id`, `position_id` FROM `employee_assignments` ORDER BY `id`")->fetchAll();
        $localJobs = $localPdo->query("SELECT `id`, `department_id`, `position_id` FROM `recruitment_jobs` ORDER BY `id`")->fetchAll();

        // Read online counts
        $onlineDeptCount = (int)$onlinePdo->query("SELECT COUNT(*) FROM `departments`")->fetchColumn();
        $onlineDivCount  = (int)$onlinePdo->query("SELECT COUNT(*) FROM `divisions`")->fetchColumn();
        $onlinePosCount  = (int)$onlinePdo->query("SELECT COUNT(*) FROM `positions`")->fetchColumn();
        $onlineAssCount  = (int)$onlinePdo->query("SELECT COUNT(*) FROM `employee_assignments`")->fetchColumn();
        $onlineJobCount  = (int)$onlinePdo->query("SELECT COUNT(*) FROM `recruitment_jobs`")->fetchColumn();

        $this->line("");
        $this->info("Ringkasan Data Saat Ini:");
        $this->line(sprintf("  %-25s | Local: %4d | Online: %4d", "departments", count($localDepts), $onlineDeptCount));
        $this->line(sprintf("  %-25s | Local: %4d | Online: %4d", "divisions", count($localDivs), $onlineDivCount));
        $this->line(sprintf("  %-25s | Local: %4d | Online: %4d", "positions", count($localPos), $onlinePosCount));
        $this->line(sprintf("  %-25s | Local: %4d | Online: %4d", "employee_assignments", count($localAssignments), $onlineAssCount));
        $this->line(sprintf("  %-25s | Local: %4d | Online: %4d", "recruitment_jobs", count($localJobs), $onlineJobCount));
        $this->line("");

        // 3. Backup online tables if requested
        if (!$isDryRun && !$this->option('no-backup')) {
            $timestamp = date('Ymd_His');
            $backupFile = base_path("ext_hrms_online_org_backup_{$timestamp}.sql");
            $this->info("Membuat backup tabel online ke: " . basename($backupFile) . " ...");

            $backupTables = ['departments', 'divisions', 'positions', 'employee_assignments', 'recruitment_jobs'];
            $sqlBackup = "-- HRMS Online Org Backup\n-- Date: " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($backupTables as $table) {
                $rows = $onlinePdo->query("SELECT * FROM `$table`")->fetchAll();
                if (empty($rows)) continue;

                $cols = array_keys($rows[0]);
                $colNames = implode(', ', array_map(fn($c) => "`$c`", $cols));
                $sqlBackup .= "-- Data for table `$table` (" . count($rows) . " rows)\n";

                foreach ($rows as $row) {
                    $escapedVals = array_map(function ($val) use ($onlinePdo) {
                        return $val === null ? 'NULL' : $onlinePdo->quote((string)$val);
                    }, array_values($row));
                    $sqlBackup .= "INSERT INTO `$table` ($colNames) VALUES (" . implode(', ', $escapedVals) . ");\n";
                }
                $sqlBackup .= "\n";
            }

            $sqlBackup .= "SET FOREIGN_KEY_CHECKS=1;\n";
            file_put_contents($backupFile, $sqlBackup);
            $this->info("✓ Backup selesai (" . round(strlen($sqlBackup) / 1024, 2) . " KB)");
            $this->line("");
        }

        if ($isDryRun) {
            $this->warn("[DRY-RUN] Simulasi perubahan yang akan dilakukan:");
            $this->line("  1. Online `departments` akan diganti menjadi " . count($localDepts) . " departemen lokal.");
            $this->line("  2. Online `divisions` akan diganti menjadi " . count($localDivs) . " divisi lokal.");
            $this->line("  3. Online `positions` akan diganti menjadi " . count($localPos) . " jabatan lokal.");
            $this->line("  4. Online `employee_assignments` (" . count($localAssignments) . " baris) akan disinkronkan department_id, division_id, position_id ke nilai lokal.");
            $this->line("  5. Online `recruitment_jobs` (" . count($localJobs) . " baris) akan disinkronkan department_id, position_id ke nilai lokal.");
            $this->warn("[DRY-RUN] Tidak ada data yang diubah.");
            return 0;
        }

        // 4. Perform Synchronization
        $startTime = microtime(true);
        $this->info("Memulai sinkronisasi ke database online...");

        $onlinePdo->exec("SET FOREIGN_KEY_CHECKS=0;");
        $onlinePdo->beginTransaction();

        try {
            // A. Sync departments
            $this->line("➔ Menyinkronkan tabel `departments`...");
            $onlinePdo->exec("DELETE FROM `departments`");
            if (!empty($localDepts)) {
                $deptCols = array_keys($localDepts[0]);
                $colList = implode(', ', array_map(fn($c) => "`$c`", $deptCols));
                $placeholders = implode(', ', array_fill(0, count($deptCols), '?'));
                $stmt = $onlinePdo->prepare("INSERT INTO `departments` ($colList) VALUES ($placeholders)");

                foreach ($localDepts as $row) {
                    $stmt->execute(array_values($row));
                }
            }
            $this->info("   ✓ `departments` diperbarui: " . count($localDepts) . " baris.");

            // B. Sync divisions
            $this->line("➔ Menyinkronkan tabel `divisions`...");
            $onlinePdo->exec("DELETE FROM `divisions`");
            if (!empty($localDivs)) {
                $divCols = array_keys($localDivs[0]);
                $colList = implode(', ', array_map(fn($c) => "`$c`", $divCols));
                $placeholders = implode(', ', array_fill(0, count($divCols), '?'));
                $stmt = $onlinePdo->prepare("INSERT INTO `divisions` ($colList) VALUES ($placeholders)");

                foreach ($localDivs as $row) {
                    $stmt->execute(array_values($row));
                }
            }
            $this->info("   ✓ `divisions` diperbarui: " . count($localDivs) . " baris.");

            // C. Sync positions
            $this->line("➔ Menyinkronkan tabel `positions`...");
            $onlinePdo->exec("DELETE FROM `positions`");
            if (!empty($localPos)) {
                $posCols = array_keys($localPos[0]);
                $colList = implode(', ', array_map(fn($c) => "`$c`", $posCols));
                $placeholders = implode(', ', array_fill(0, count($posCols), '?'));
                $stmt = $onlinePdo->prepare("INSERT INTO `positions` ($colList) VALUES ($placeholders)");

                foreach ($localPos as $row) {
                    $stmt->execute(array_values($row));
                }
            }
            $this->info("   ✓ `positions` diperbarui: " . count($localPos) . " baris.");

            // D. Sync employee_assignments (update department_id, division_id, position_id)
            $this->line("➔ Menyinkronkan penempatan pada `employee_assignments`...");
            $stmtAss = $onlinePdo->prepare("
                UPDATE `employee_assignments` 
                SET `department_id` = ?, `division_id` = ?, `position_id` = ? 
                WHERE `id` = ?
            ");
            $assUpdated = 0;
            foreach ($localAssignments as $la) {
                $stmtAss->execute([
                    $la['department_id'],
                    $la['division_id'],
                    $la['position_id'],
                    $la['id'],
                ]);
                $assUpdated++;
            }
            $this->info("   ✓ `employee_assignments` diperbarui: {$assUpdated} baris.");

            // E. Sync recruitment_jobs
            $this->line("➔ Menyinkronkan penempatan pada `recruitment_jobs`...");
            $stmtJob = $onlinePdo->prepare("
                UPDATE `recruitment_jobs` 
                SET `department_id` = ?, `position_id` = ? 
                WHERE `id` = ?
            ");
            $jobUpdated = 0;
            foreach ($localJobs as $lj) {
                $stmtJob->execute([
                    $lj['department_id'],
                    $lj['position_id'],
                    $lj['id'],
                ]);
                $jobUpdated++;
            }
            $this->info("   ✓ `recruitment_jobs` diperbarui: {$jobUpdated} baris.");

            $onlinePdo->commit();
            $onlinePdo->exec("SET FOREIGN_KEY_CHECKS=1;");
            $this->info("✓ Transaksi berhasil di-commit!");

        } catch (Exception $e) {
            $onlinePdo->rollBack();
            $onlinePdo->exec("SET FOREIGN_KEY_CHECKS=1;");
            $this->error("✗ Terjadi kesalahan, transaksi dibatalkan: " . $e->getMessage());
            return 1;
        }

        // 5. Verification
        $this->line("");
        $this->info("Memverifikasi konsistensi data setelah sinkronisasi...");

        $verDept = (int)$onlinePdo->query("SELECT COUNT(*) FROM `departments`")->fetchColumn();
        $verDiv  = (int)$onlinePdo->query("SELECT COUNT(*) FROM `divisions`")->fetchColumn();
        $verPos  = (int)$onlinePdo->query("SELECT COUNT(*) FROM `positions`")->fetchColumn();

        $locDeptIds = array_column($localDepts, 'id');
        $locDivIds  = array_column($localDivs, 'id');
        $locPosIds  = array_column($localPos, 'id');

        $danglingDept = (int)$onlinePdo->query("
            SELECT COUNT(*) FROM `employee_assignments` 
            WHERE `department_id` IS NOT NULL 
              AND `department_id` NOT IN (" . implode(',', $locDeptIds) . ")
        ")->fetchColumn();

        $danglingDiv = (int)$onlinePdo->query("
            SELECT COUNT(*) FROM `employee_assignments` 
            WHERE `division_id` IS NOT NULL 
              AND `division_id` NOT IN (" . implode(',', $locDivIds) . ")
        ")->fetchColumn();

        $danglingPos = (int)$onlinePdo->query("
            SELECT COUNT(*) FROM `employee_assignments` 
            WHERE `position_id` IS NOT NULL 
              AND `position_id` NOT IN (" . implode(',', $locPosIds) . ")
        ")->fetchColumn();

        $duration = round(microtime(true) - $startTime, 2);

        $this->line("");
        $this->info("=========================================================");
        $this->info("  HASIL SINKRONISASI ORGANISASI KE ONLINE:");
        $this->line("  - Departments : {$verDept} (harus " . count($localDepts) . ")");
        $this->line("  - Divisions   : {$verDiv} (harus " . count($localDivs) . ")");
        $this->line("  - Positions   : {$verPos} (harus " . count($localPos) . ")");
        $this->line("  - Dangling Dept refs di assignments: {$danglingDept}");
        $this->line("  - Dangling Div refs di assignments : {$danglingDiv}");
        $this->line("  - Dangling Pos refs di assignments : {$danglingPos}");
        $this->line("  - Waktu Eksekusi: {$duration} detik");
        $this->info("=========================================================");

        if ($danglingDept === 0 && $danglingDiv === 0 && $danglingPos === 0) {
            $this->info("✓ SUKSES: Data organisasi online kini 100% identik dengan lokal!");
            return 0;
        } else {
            $this->warn("! Peringatan: Terdapat referensi yang tidak cocok.");
            return 1;
        }
    }
}
