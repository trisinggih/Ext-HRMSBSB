# System Architecture — Ext-HRMS

HRMS Full Stack yang saling terintegrasi: **Web Admin (Vue.js) + Mobile Android (Kotlin)** berbagi
satu **Backend Laravel REST API** dan satu **Database MySQL** terpusat.

## Arsitektur Tingkat Tinggi

```
                          ┌──────────────────────────────┐
                          │      MySQL (terpusat)        │
                          │   (Laravel Migration + FK)   │
                          └──────────────▲───────────────┘
                                         │
                    ┌────────────────────┴────────────────────┐
                    │         Backend Laravel API             │
                    │         REST /api/v1                    │
                    │  · Token Auth (Sanctum)                 │
                    │  · RBAC (admins) + Employee auth        │
                    │  · Approval Engine (configurable)       │
                    │  · Payroll & Tax configurable           │
                    │  · Audit / Activity Log                 │
                    └───────┬────────────────────┬────────────┘
                            │                    │
               ┌────────────▼──────┐   ┌─────────▼─────────────┐
               │  WEB ADMIN (Vue)  │   │  MOBILE (Kotlin)     │
               │  Dashboard,       │   │  Clock In/Out, GPS,  │
               │  Master Data,     │   │  Face+ML Kit, Leave, │
               │  Payroll, Reports │   │  Payslip, Task       │
               └───────────────────┘   └──────────────────────┘
```

## Pola Per Request (Web Admin)
1. Login via tabel `admins` → terbitkan **token Sanctum**.
2. RBAC + Permission check per route.
3. Kontroller → Service layer (business logic) → Repository/Model → MySQL.
4. Semua mutasi tercatat di `audit_logs` / `activity_logs`.

## Pola Pergram Mobile (Employee)
1. Login via tabel `employees` → token auth + **device management**.
2. Clock In: GPS → validasi radius (≤20m) → Face Detection → Face Verification → Liveness → Clock In.
3. Data kirim ke API yang sama; tampilan di XML + Kotlin.

## Prinsip
- **Compact & Modular**: modul terpisah per domain, minim duplikasi.
- **Clean Architecture**: Controller → Service → Repository → Model.
- **Scalable**: versioned API (`/api/v1`), pindah ke production server VPS.
- **Configurable**: payroll, pajak, BPJS, approval semua berbasis tabel, bukan hardcode.
- **Tidak pernah** menyimpan secret di Git (gunakan `.env` + `.env.example`).
