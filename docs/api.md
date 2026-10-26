# API Documentation — Ext-HRMS

- **Base URL (dev):** `/api/v1`
- **Format respons:**
```json
{ "success": true, "message": "Success", "data": {} }
```
- **Auth:** Token Sanctum. Header: `Authorization: Bearer <token>`
- **Rate limit:** diaktifkan per endpoint auth agar aman.

## Auth (Web Admin — tabel `admins`)
| Method | Endpoint                 | Keterangan              |
|--------|--------------------------|-------------------------|
| POST   | /auth/login              | Login admin             |
| POST   | /auth/logout             | Logout admin            |
| GET    | /admin/profile           | Profil admin            |

## Auth (Mobile — tabel `employees`)
| Method | Endpoint                 | Keterangan                     |
|--------|--------------------------|-------------------------------|
| POST   | /auth/employee/login     | Login employee                |
| POST   | /auth/employee/logout    | Logout employee               |
| POST   | /auth/forgot-password    | Lupa password                 |
| POST   | /auth/change-password    | Ganti password                |
| GET    | /auth/devices            | Daftar device                 |
| DELETE | /auth/devices/{id}       | Hapus/revoke device           |

## Employee
| Method | Endpoint                 | Keterangan                    |
|--------|--------------------------|-------------------------------|
| GET    | /employee/profile        | Profil employee               |
| PUT    | /employee/profile        | Update profil                 |
| GET    | /employee/families       | Keluarga & dependent          |

## Master Company
| Method | Endpoint                 |
|--------|--------------------------|
| CRUD   | /companies               |
| CRUD   | /branches                |
| CRUD   | /departments             |
| CRUD   | /divisions               |
| CRUD   | /positions               |
| CRUD   | /employees               |
| GET    | /organization-chart      | Chart organisasi (Web) |

## Face Registration
| Method | Endpoint                          |
|--------|-----------------------------------|
| POST   | /face/register    (deteksi+live) |
| POST   | /face/verify      (verifikasi)   |
| POST   | /face/liveness    (liveness)     |

## Attendance
| Method | Endpoint                       |
|--------|--------------------------------|
| POST   | /attendance/clock-in           |
| POST   | /attendance/clock-out          |
| POST   | /attendance/break             |
| POST   | /attendance/return-break       |
| GET    | /attendance/history            |

## Shift
| CRUD   | /shifts , /employee-shifts , /shift-schedules ; POST /shift-swap/request ; POST /workday-swap/request |

## Leave & Izin
| Method | Endpoint            |
|--------|---------------------|
| GET    | /leave/balance       |
| POST   | /leave/request       |
| CRUD   | /leave-types         |
| POST   | /permission/request  |
| POST   | /sick-leave/request  |
| POST   | /late/request        |

## Location Tracking
| Method | Endpoint             |
|--------|----------------------|
| POST   | /location/start      |
| POST   | /location/stop       |
| POST   | /location/update     |
| GET    | /location/history    |

## Tasks
| Method | Endpoint          |
|--------|-------------------|
| GET    | /tasks            |
| POST   | /tasks            |
| PUT    | /tasks/{id}       |
| POST   | /tasks/{id}/comment |

## Reward & Disiplin
| CRUD | /rewards ; /warnings ; /terminations ; /clearances |

## Payroll & Pajak
| Method | Endpoint                 |
|--------|--------------------------|
| GET    | /payroll/payslips        |
| CRUD   | /payroll/settings        |
| CRUD   | /payroll/components      |
| CRUD   | /payroll/runs            |
| CRUD   | /tax/rules , /tax/brackets |
| POST   | /payslip/download        | (PDF)

## Approval Engine
| Method | Endpoint                 |
|--------|--------------------------|
| CRUD   | /approval/workflows      |
| CRUD   | /approval/steps          |
| GET    | /approval/requests       |
| POST   | /approval/requests/{id}/approve |
| POST   | /approval/requests/{id}/reject  |

## Notifikasi
| Method | Endpoint            |
|--------|---------------------|
| GET    | /notifications      |
| PUT    | /notifications/{id}/read |
| POST   | /announcements      |
