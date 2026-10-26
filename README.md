# Ext-HRMS

HRMS Full Stack — **Web Admin (Vue.js)** + **Mobile Android (Kotlin)** terintegrasi penuh dengan
satu **Backend Laravel API** dan satu **Database MySQL** terpusat.

> Referensi tampilan: **Mekari Talenta** (modern, rapi, compact).

## Fitur Utama
- **Auth & RBAC**: Web (tabel `admins`), Mobile (tabel `employees`), Role & Permission.
- **Master Company**: Company → Branch → Department → Division → Employee + Organization Chart.
- **Employee Management** lengkap (data, KTP, NPWP, BPJS, status).
- **Face Registration & Verification** (ML Kit): liveness, blur & low-light detection.
- **Attendance**: Clock In/Out/Break, GPS + radius ≤20m, face verification, lintas hari (shift).
- **Shift Management** + Shift Swap & Workday Swap (approval configurable).
- **Leave / Izin / Sakit / Terlambat** (approval workflow).
- **Location Tracking** (map di Web).
- **Task Management** (TODO → DONE, evidence, komentar).
- **Reward, Warning (SP1-3), Termination/PHK** + Exit Clearance.
- **Payroll configurable** (basic, allowance, overtime, bonus, komisi, reward, deduction).
- **Tax configurable** (PPh21, NPWP, PTKP, brackets) + **BPJS configurable**.
- **Payslip + download PDF**.
- **Approval Engine** (configurable per jenis pengajuan).
- **Notifikasi** (in-app + push) & **Audit/Activity Log**.

## Struktur Repo
```
hrms/
├── backend/   # Laravel REST API (/api/v1)
├── web/       # Vue.js admin
├── mobile/    # Android Kotlin + XML (KMP shared)
├── docs/      # Dokumentasi
└── README.md
```

## Teknologi
| Layer    | Teknologi                                  |
|----------|--------------------------------------------|
| Backend  | Laravel, MySQL, Sanctum token, RBAC        |
| Web      | Vue 3, Vite, Pinia, Axios                  |
| Mobile   | Android Native Kotlin + XML, ML Kit, KMP   |

## Dokumentasi
- [Architecture](docs/architecture.md)
- [Database & ERD](docs/database.md), [ERD](docs/erd.md)
- [API](docs/api.md)
- [Web Modules](docs/web-modules.md), [Mobile Modules](docs/mobile-modules.md)
- [Roadmap](docs/roadmap.md)
- [Security](docs/security.md), [Testing](docs/testing.md)
- [Deployment](docs/deployment.md)

## Pengembangan
Sesuai roadmap bertahap; tiap modul: Build → Test → Fix → Commit.
Commit jelas: `feat:`, `fix:`, `docs:`.
