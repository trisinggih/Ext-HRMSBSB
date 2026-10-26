<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## Ext-HRMS — API Phase 4: Attendance, Shift & Face

Dokumentasi ini fokus pada modul **Phase 4** (Attendance & Shift & Face) di bawah prefix `/api/v1`. Seluruh response memakai format `{"success":true,"message":"...","data":{}}`.

### Endpoint Utama (auth: Sanctum)

**SHIFT MANAGEMENT** (token employee, ability `admin`)
- `GET/POST /api/v1/shifts` · `GET/PUT/DELETE /api/v1/shifts/{shift}` — CRUD shift
- `GET/POST /api/v1/employee-shifts` · `GET/PUT/DELETE /api/v1/employee-shifts/{employeeShift}` — jadwal shift per employee

**ATTENDANCE** (token employee, ability `employee`)
- `POST /api/v1/attendance/clock-in` — body: `latitude`, `longitude`, `accuracy`, `device_info?`, `face_token?`
- `POST /api/v1/attendance/clock-out` — set `clock_out_at` & hitung `working_hours`
- `POST /api/v1/attendance/break` · `POST /api/v1/attendance/return-break` — akumulasi `break_minutes`
- `GET /api/v1/attendance/history` — riwayat absensi (paginate, filter `from`/`to`/`status`)

**FACE** (token employee, ability `employee`)
- `POST /api/v1/face/register` — body: `face_template` (wajib), `face_image?`, `model_version?`
- `POST /api/v1/face/verify` — body: `face_token`
- `POST /api/v1/face/liveness` — dummy liveness
- `GET /api/v1/face` — daftar wajah terdaftar

### Aturan Validasi Clock-In
1. **Radius kantor**: jarak Haversine (`App\Support\GeoLocation`) dihitung dari koordinat `branch` (karyawan). Jika jarak > `branch.attendance_radius` (default **20 meter**) → **422** `"Di luar radius kantor"`.
2. **Akurasi GPS**: jika `accuracy` > 100 m → **422** `"Akurasi GPS terlalu rendah"`.
3. **Shift aktif**: dicocokkan via `employee_shifts` (aktif & efektif hari ini). Jika tidak ada → **422**.
4. **Status late**: jika `clock_in_at` melewati `shift.clock_in + late_tolerance_minutes` → status `late`.

### Catatan: Verifikasi Wajah (Face) — ML Kit

**Verifikasi wajah asli (ML Kit) berjalan di sisi client Android, bukan di backend.**

- Backend hanya **menyimpan template wajah** (`employee_faces.face_template`, disimpan apa adanya, tidak ada ekstraksi fitur) + endpoint validasi sederhana.
- `face/register` menyimpan template yang dikirim client.
- `face/verify` hanya melakukan **pencocokan string dummy** terhadap template terakhir — tidak ada model ML di server.
- `face/liveness` adalah **dummy success**; liveness sebenarnya dijalankan client via ML Kit.
- Untuk produksi, gunakan hasil ML Kit di client lalu kirim token/hash hasil verifikasi ke backend untuk validasi & pencatatan (`is_face_verified`).
