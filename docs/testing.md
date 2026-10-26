# Testing Plan — Ext-HRMS

## Backend (Laravel)
- **Unit Test**: service logic (payroll calculation, tax bracket, radius attendance, leave balance).
- **Feature Test**: endpoint `/api/v1` (auth, employee, attendance, leave, payroll, approval).
- **RBAC test**: pastikan admin non-authorisasi ditolak.
- **Validation test**: request invalid ditolak sesuai aturan.
- **Rate limit test**: endpoint auth melampaui limit → 429.

## Web (Vue)
- Build sukses (Vite).
- Manual QA alur: login → dashboard → master data → attendance → leave → approval → payroll → payslip.
- Uji UI/UX agar rapi & compact (referensi Mekari Talenta).

## Mobile (Android)
- Menyediakan build yang dapat di-build Bos (Gradle).
- Test manual alur: login → pada → clock in/out (GPS+face) → leave → payslip → task.
- Pastikan permisografi (GPS, kamera, internet) diminta benar.

## Integrasi
- Web + Mobile memakai **API yang sama** → pastikan kontrak respons konsisten.
- Validasi radius & face dicek ganda (client + server).

## Kriteria Kelulusan
- Build sukses, tidak ada error.
- Skenario inti (auth, attendance, leave, payroll) lulus.
- Tidak ada secret yang masuk ke repo.
