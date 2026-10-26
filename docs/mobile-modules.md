# Mobile Module Plan — Ext-HRMS (Android, Kotlin)

- **Bahasa:** Kotlin, UI Native **XML**.
- **Kotlin Multiplatform (KMP)** untuk shared business logic, networking, repository, model
  (bila memungkinkan; kebutuhan utama di Android).
- **Java Native** dapat digunakan untuk integrasi Android tertentu bila diperlukan.
- Bos yang **build APK sendiri**; saya sediakan struktur, kode, dan konfigurasi Gradle yang benar.

## Layar
1. **Splash & Login** (tabel `employees`) — email/ID + password; forgot password; change password.
2. **Dashboard** — Clock In/Out, current shift, attendance status, working hours, location status,
   pending tasks, leave balance, latest notifications.
3. **Attendance** — Clock In / Clock Out / Break / Return Break; history; validasi GPS + radius ≤20m.
4. **Face** — Registration & Verification + Liveness (Google **ML Kit** Face Detection).
   - Flow: Register → Camera → Face Detection → Liveness → Save secure template.
   - Fitur: multiple face, blur detection, low light detection. Bukan sekadar foto statis.
5. **Shift** — jadwal (weekly/monthly/rotating); request **Shift Swap** & **Workday Swap** + status approval.
6. **Leave / Izin / Sakit / Terlambat** — balance, request (tanggal + alasan), upload surat dokter, history.
7. **Location Tracking** — start/stop tracking, kirim lokasi periodik (transparan sesuai kebijakan).
8. **My Tasks** — list, update progress, upload evidence, comment.
9. **Payslip** — lihat payroll period, komponen, download PDF payslip.
10. **Profile** — data employee lengkap, keluarga/dependent, dokumen, device management, logout.

## Teknis
- Retrofit (networking) + KMP shared module (repository/model/logic).
- ViewModel + Coroutines (Clean Architecture: presentation/domain/data).
- ML Kit Face Detection untuk face; liveness via deteksi gerakan.
- GPS via FusedLocationProvider; validasi radius pakai Haversine di sisi client + server.
- Token auth disimpan aman (EncryptedSharedPreferences).
