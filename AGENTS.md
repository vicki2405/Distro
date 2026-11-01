# Antigravity & Agent Directive: Mandatory Skill Activation

Dokumen ini adalah aturan wajib (**System Directive**) yang **HARUS DIPATUHI** oleh setiap AI Coding Agent dalam proyek ini. Sebelum merencanakan, membuat, atau memodifikasi kode, AI WAJIB membaca dan menerapkan standar dari **Skills** terkait yang terpasang di sistem (`~/.gemini/config/skills/` dan `.claude/skills/`).

---

## 1. Matriks Aktivasi Skill Wajib (Skill Activation Matrix)

Setiap kali menerima tugas, AI wajib mengidentifikasi domain pekerjaan dan mengaktifkan skill terkait berikut sebelum menulis kode:

| Domain Tugas | Skill Utama yang Wajib Diaktifkan | Lokasi File Pedoman | Standar yang Harus Diterapkan |
| :--- | :--- | :--- | :--- |
| **Frontend & UI/UX**<br>*(Vue 3, Inertia, Tailwind, Komponen)* | `ui-ux-pro-max`<br>`agency-ui-designer`<br>`agency-frontend-developer`<br>`design-system` | `~/.gemini/config/skills/ui-ux-pro-max/SKILL.md`<br>`~/.gemini/config/skills/agency-ui-designer/SKILL.md`<br>`~/.gemini/config/skills/design-system/SKILL.md` | - Terapkan visual hierarchy, spacing, dan color palette profesional.<br>- Komponen Vue 3 harus reaktif, clean, modular, dan aksesibel (a11y).<br>- Gunakan utility Tailwind CSS yang konsisten, modern, dan hindari anti-pattern. |
| **Backend & Laravel**<br>*(Controller, Model, Service, Eloquent)* | `agency-senior-developer`<br>`laravel-best-practices`<br>`agency-backend-architect` | `.claude/skills/laravel-best-practices/SKILL.md`<br>`~/.gemini/config/skills/agency-senior-developer/SKILL.md`<br>`~/.gemini/config/skills/agency-backend-architect/SKILL.md` | - Cegah N+1 queries (gunakan eager loading).<br>- Gunakan Form Requests untuk validasi.<br>- Terapkan arsitektur clean Laravel (Action/Service jika kompleks, Eloquent best practices).<br>- Gunakan tipe eksplisit PHP 8.3/8.5. |
| **Testing & QA**<br>*(Feature Tests, Unit Tests, PHPUnit)* | `testing-best-practices`<br>`agency-test-automation-engineer` | `.claude/skills/testing-best-practices/SKILL.md`<br>`~/.gemini/config/skills/agency-test-automation-engineer/SKILL.md` | - Gunakan prinsip Arrange-Act-Assert.<br>- Utamakan Feature Test untuk setiap endpoint/halaman.<br>- Gunakan Model Factories dan fakes (`Event::fake()`, `Mail::fake()`). |
| **Keamanan & Validasi**<br>*(Auth, RBAC, Sanitasi, Secrets)* | `agency-application-security-engineer`<br>`agency-secrets-credential-hygiene-engineer` | `~/.gemini/config/skills/agency-application-security-engineer/SKILL.md`<br>`~/.gemini/config/skills/agency-secrets-credential-hygiene-engineer/SKILL.md` | - Lindungi CSRF, XSS, dan SQL Injection.<br>- Pastikan otorisasi via Policies/Gates.<br>- Jangan pernah menyimpan secret/kredensial secara hardcoded di kode. |
| **Deployment & Infrastruktur**<br>*(Cloud, Docker, Produksi)* | `deploying-to-cloud`<br>`agency-devops-automator` | `.claude/skills/deploying-to-cloud/SKILL.md`<br>`~/.gemini/config/skills/agency-devops-automator/SKILL.md` | - Ikuti panduan zero-downtime deployment, build commands, dan ephemeral filesystem. |

---

## 2. Protokol Eksekusi AI Sebelum Menulis Kode

Sebelum membuat atau mengubah file (`write_file` / `edit_file`):
1. **Identifikasi Domain**: Periksa apakah tugas berhubungan dengan UI, Backend, Database, Keamanan, atau Testing.
2. **Baca Pedoman Terkait**: Jika ada keraguan pada pola arsitektur, baca `SKILL.md` yang relevan dari direktori `~/.gemini/config/skills/` atau `.claude/skills/`.
3. **Patuhi Gaya Kode Proyek**:
   - Jika membuat komponen frontend, ikuti standar modern **Vue 3 (`<script setup>`) + Tailwind CSS**.
   - Jika membuat backend, terapkan **Laravel 13 idiomatis** dan pastikan kode di-format dengan **Laravel Pint**.
4. **Wajib Tampilkan Rencana Perubahan Sebelum Eksekusi (Review Gate)**:
   - AI **WAJIB** memperlihatkan rincian rencana perubahan kode (berupa ringkasan perubahan, cuplikan/draft kode yang akan ditambahkan/diubah, atau diff) kepada pengguna terlebih dahulu.
   - Pengguna harus diberi kesempatan membaca apa saja yang akan dimodifikasi sebelum perubahan tersebut benar-benar dituliskan ke dalam file proyek.

---

## 3. Aturan Lingkungan & Runtime Proyek

1. **Seluruh Perintah Harus Melalui Laravel Sail**:
   Semua perintah PHP, Artisan, Composer, Node, dan Testing **wajib** menggunakan awalan `vendor/bin/sail`:
   ```bash
   vendor/bin/sail artisan [command]
   vendor/bin/sail composer [command]
   vendor/bin/sail npm run [command]
   vendor/bin/sail test
   ```
2. **Pembersihan & Format Kode**:
   Setelah selesai mengedit file PHP, jalankan format kode standar:
   ```bash
   vendor/bin/sail bin pint --format agent
   ```
3. **Database & Idempotensi**:
   Setiap seeder atau migrasi harus bersifat idempoten (contoh: gunakan `firstOrCreate` / `updateOrCreate`) agar aman dijalankan berulang kali.
