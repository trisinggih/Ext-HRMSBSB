# Security Plan — Ext-HRMS

## Identitas & Autentikasi
- **Password hashing** (bcrypt/argon2) — tidak pernah plaintext.
- **Token authentication** (Laravel Sanctum) untuk Web & Mobile.
- **Device management** di Mobile — token/device dapat di-revoke.

## Otorisasi
- **RBAC** untuk `admins` (Super Admin, HRD, PayrollManager, Supervisor, Administrator).
- **Permission management** per route/aksi.
- Mobile dibatasi ke data milik employee bersangkutan (ownership check).

## Perlindungan Data
- **Enkripsi data sensitif** (kolom terpilih: NPWP, BPJS, dokumen pribadi) memakai Laravel Encryption / app key.
- **Template wajah** (employee_faces) disimpan aman (representasi feature vector / terenkripsi), bukan foto mentah sebagai validasi.

## API
- **Rate limit** pada endpoint auth & pengiriman (mis. 60/menit; lebih ketat saat login/face).
- **Input validation** di semua request (FormRequest + validasi).
- **SQL Injection protection** — pakai Eloquent/Query Builder + prepared statement.
- **File upload validation** — ukuran, tipe/MIME, ekstensi; simpan di storage privat (bukan public).

## Audit & Logging
- **Audit log** untuk aksi sensitif (ubah payroll, pajak, approval, termination).
- **Activity log** untuk aktivitas normal (login, create/update).
- Catat actor, aksi, data, Waktu, IP.

## Secret Handling
- `.env` untuk secret nyata; **`.env.example`** untuk template (tanpa nilai nyata).
- **Jangan commit**: `.env`, `node_modules/`, `vendor/`, `build/`, secret key, credential.
- Ganti kredensial default (DB, admin) dengan yang baru.

## Infrastruktur (deploy Web ke VPS)
- HTTPS (SSL) untuk web.
- Jangan expose secret; gunakan env production.
- Batasi akses DB & file storage.
