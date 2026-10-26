# Web Module Plan — Ext-HRMS (Vue.js)

Referensi tampilan: **Mekari Talenta** (modern, bersih, sidebar + topbar, kartu statistik, tabel padat).

## Layout
- **Sidebar** (collapse-able) + **Topbar** (search, notifikasi, profile admin).
- Tema terang, warna aksen (biru/hijau korporat), font rapi.
- Responsive; dibuka via browser.

## Modul & Halaman
1. **Dashboard** — statistik: Total/Active/New/Terminated Employee; Attendance (Present/Late/Absent);
   Leave & Sick; Payroll (current, total salary); Tasks (pending/in-progress/completed).
2. **Master Company** — companies, branches, departments, divisions, positions.
3. **Organization Chart** — visual Company→Branch→Dept→Division→Employee.
4. **Employee** — list + detail + form; upload foto/KTP/NPWP/BPJS; status employee; filter by branch/dept/status.
5. **Attendance** — rekap harian/bulanan, validasi GPS & face, alert terlambat, shift management + schedule.
6. **Leave / Izin / Sakit / Terlambat** — list pengajuan, approval, balance tiap employee.
7. **Shift Swap & Workday Swap** — list permintaan + approval.
8. **Location Tracking** — peta (Map) route & history employee.
9. **Tasks** — board/kanban (TODO, IN PROGRESS, REVIEW, DONE, CANCELED), assign, progress, evidence.
10. **Reward** — Employee of the Month, performance, bonus, appreciation; history.
11. **Warning & Termination** — SP1/SP2/SP3, termination + exit clearance checklist.
12. **Payroll** — periode, run payroll, komponen (base, allowance, overtime, bonus, komisi, reward, deduction),
    review & approve, preview payslip.
13. **Pajak & BPJS** — tax rules/brackets/employee setting, BPJS rate config.
14. **Approval Engine** — konfigurasi workflow/steps per jenis pengajuan.
15. **Users, Roles & Permissions** — RBAC admin.
16. **Notifikasi & Announcement**, **Activity/Audit Log**, **Settings**.

## Teknis
- Vue 3 + Vite + Vue Router + Pinia + Axios.
- UI kit: memakai komponen sendiri / Tailwind / Element Plus ringan agar compact & menarik.
- Terhubung ke API Laravel `/api/v1` yang sama dengan Mobile.
