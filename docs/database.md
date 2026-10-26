# Database Design — Ext-HRMS

Gunakan **Laravel Migration**, relasi dengan **Foreign Key**, **Index** untuk kolom yang sering
di-query, dan **Seeder** untuk data awal. Seluruh tabel dipusatkan di satu database MySQL.

> **ERD lengkap** ada di `docs/erd.md` (Mermaid source) dan `hrms/backend/database/schema/*.sql` (opsi ekspor).

## Ringkasan Modul & Tabel

### 1. Auth & RBAC
- `admins` — login Web. Role: Super Admin, HRD, PayrollManager, Supervisor, Administrator.
- `roles` , `permissions` , `role_permission` , `admin_role` , `admin_permission` (Spatie-style RBAC).

### 2. Master Company
- `companies` → `branches` → `departments` → `divisions` → `positions` → `employees`.
- Struktur organisasi: **Company → Branch → Department → Division → Employee**.

### 3. Employee
- `employees` — data induk karyawan (ID, nama, email, HP, foto, KTP, NPWP, BPJS, jabatan, join date, status).
- `employee_families` , `employee_documents` , `employee_faces` (template biometric), `employee_devices`.
- Status employee: Permanent, Contract, Probation, Internship, Outsourcing.

### 4. Attendance
- `attendance` , `attendance_logs`.
- `shifts` , `employee_shifts` , `shift_swap_requests` , `workday_swap_requests`.
- Tiap branch punya latitude, longitude, attendance_radius (default 20m).

### 5. Leave, Izin, Sakit, Terlambat
- `leave_types` , `leave_balances` , `leave_requests`.
- `permission_requests` , `sick_leave_requests` , `late_requests`.

### 6. Tracking & Task
- `employee_locations` (latitude, longitude, accuracy, timestamp).
- `tasks` , `task_assignments` , `task_comments`.

### 7. Reward & Disiplin
- `rewards` , `employee_warnings` (SP1/SP2/SP3, expired date, status).
- `employee_terminations` , `termination_clearances` (exit checklist).

### 8. Payroll
- `payroll_periods` , `payroll_settings` , `payroll_components` , `employee_payroll_settings` ,
  `payroll_runs` , `payroll_details`.
- Terintegrasi: attendance, late, leave, unpaid leave, overtime, reward, deduction.

### 9. Pajak & BPJS
- `tax_rules` , `tax_brackets` , `tax_employee_settings` (PPh21, NPWP, PTKP, effective date).
- BPJS Kesehatan, JHT, JKK, JKM, JP — rate configurable oleh Admin.

### 10. Approval Engine (configurable)
- `approval_workflows` , `approval_steps` , `approval_requests` , `approval_logs`.
- Workflow per jenis pengajuan, contoh: Employee → Supervisor → Manager → HR.

### 11. Notifikasi & Logs
- `notifications` , `announcements` .
- `activity_logs` , `audit_logs` .
