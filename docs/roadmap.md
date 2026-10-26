# Development Roadmap — Ext-HRMS

Kerjakan **bertahap**. Tiap modul: Build → Test → Fix → Commit. Commit jelas (`feat:`/`fix:`/`docs:`).

## Phase 1 — Desain (SELESAI)
- [x] System Architecture (`docs/architecture.md`)
- [x] Database ERD (`docs/erd.md`)
- [x] Database Design (`docs/database.md`)
- [x] Migration Plan
- [x] API Documentation (`docs/api.md`)
- [x] Web Module Plan
- [x] Mobile Module Plan
- [x] Development Roadmap (`docs/roadmap.md` ini)
- [x] Security Plan (`docs/security.md`)
- [x] Testing Plan (`docs/testing.md`)

## Phase 2 — Fondasi Backend
- Setup Laravel + MySQL (+ kredensial baru)
- Auth (admins + employees), Token Sanctum
- Role & Permission (RBAC)
- Seeder role/permission/admin default

## Phase 3 — Master Company
- Company, Branch, Department, Division, Position, Employee
- Struktur organisasi + Organization Chart (Web)

## Phase 4 — Attendance & Face
- Shift management, Employee shift, schedule (weekly/monthly/rotating)
- Attendance: clock in/out/break, GPS + radius 20m, validasi
- Face Registration & Verification (ML Kit), liveness
- Late request otomatis

## Phase 5 — Leave & Izin
- Cuti (tahunan/melahirkan/menikah/khusus/bersama/tanpa gaji/custom)
- Leave balance, quota, carry forward, expired
- Izin, Sakit (upload surat dokter), Terlambat
- Shift Swap & Workday Swap + approval

## Phase 6 — Employee Ekstra
- Family & Dependent, Documents
- Reward, Warning (SP1/SP2/SP3), Termination/PHK + Exit Clearance

## Phase 7 — Task Management
- My Tasks, create/assign, due date, priority, progress, attachment, comment, status.

## Phase 8 — Payroll & Pajak
- Payroll configurable (basic, allowance, overtime, bonus, komisi, reward, deduction)
- Terintegrasi attendance/late/leave/overtime
- Tax (PPh21, NPWP, PTKP, brackets — configurable + effective date)
- BPJS configurable
- Payslip + download PDF

## Phase 9 — Notifikasi & Dashboard
- In-App + Push (Leave, sakit, izin, shift change, swap, late, payroll, task, warning, announcement)
- Dashboard Web & Dashboard Mobile

## Phase 10 — Final
- Testing menyeluruh, Documentation final, Review = SELESAI
- Deploy Web ke VPS, push semua ke GitHub `trisinggih/Ext-HRMS`

## Prioritas
1. Architecture & Database → 2. Auth → 3. Company/Employee → 4. Attendance+GEO+Face → 5. Leave → 6. Payroll
