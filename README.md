# Sistem Kehadiran Wajah Sekolah

Sistem kehadiran harian menggunakan pengecaman wajah, dibina dengan **PHP + SQLite + Tailwind CSS**. Pelajar hanya perlu memandang tablet di pintu masuk, dan skrin akan memaparkan *"Selamat pagi, Ahmad"*.

Sistem ini berjalan **100% offline** di rangkaian sekolah. Tiada perkhidmatan awan diperlukan.

## Ciri-ciri

- **Mod kiosk** untuk tablet: jam besar, kamera, kad ucapan berwarna (Hadir / Lewat / Sudah direkod) dan bunyi notifikasi
- **Papan pemuka** langsung: jumlah hadir, lewat dan tidak hadir, serta imbasan terkini
- **Daftar pelajar** dengan beberapa sampel wajah, berserta notis PDPA dan pengesahan persetujuan ibu bapa/penjaga
- **Senarai pelajar**: carian, tapisan ikut kelas, hapus pelajar
- **Laporan** harian ikut tarikh dan kelas, eksport **PDF** (A4, ruang tandatangan) dan **CSV**
- **Tetapan** melalui dashboard: nama sekolah, motto, had masa lewat, ambang padanan, bilangan sampel, paparan kiosk, tukar PIN dan muat turun sandaran
- Antara muka dalam **Bahasa Melayu**

## Cara ia berfungsi

```
Tablet (pelayar)                               Pelayan (PHP)
┌────────────────────────────┐   embedding    ┌───────────────────────────┐
│ face-api.js mengesan wajah │ ── 128 nombor ─►│ Cosine similarity dengan  │
│ dan menjana "cap jari"     │   (bukan gambar)│ semua pelajar dalam SQLite│
│ wajah                      │ ◄── nama ────── │ Simpan rekod kehadiran    │
└────────────────────────────┘   + status      └───────────────────────────┘
```

1. Model AI ([face-api.js](https://github.com/vladmandic/face-api)) berjalan dalam pelayar tablet.
2. Hanya **embedding**, iaitu 128 nombor, dihantar ke pelayan. **Gambar tidak dihantar dan tidak disimpan.**
3. PHP membandingkan embedding itu dengan semua sampel pelajar. Jika skor tertinggi melebihi ambang, kehadiran direkod (sekali sehari bagi setiap pelajar).
4. Imbasan selepas had masa lewat ditanda **Lewat**.

## Keperluan

- PHP 8.1 atau lebih baharu, dengan sambungan `pdo_sqlite` (sudah tersedia dalam XAMPP)
- Apache dengan `.htaccess` dibenarkan (`AllowOverride All`)
- **HTTPS**: pelayar hanya membenarkan akses kamera melalui HTTPS (atau `localhost`)
- Tablet atau komputer dengan kamera dan pelayar moden (Chrome, Edge, Safari)

## Pemasangan (XAMPP)

1. Salin folder ini ke `C:\xampp\htdocs\face`.
2. Hidupkan **Apache** dalam XAMPP Control Panel.
3. Buka `https://localhost/face/` dan log masuk dengan PIN lalai **`1234`**.
4. **Tukar PIN segera** di halaman **Tetapan**.
5. Daftar pelajar di halaman **Daftar Pelajar**.
6. Di tablet, buka `https://IP-PELAYAN/face/kiosk.php`. Klik dua kali pada skrin untuk mod skrin penuh.

Pangkalan data `data/kehadiran.sqlite` dicipta secara automatik pada kali pertama sistem dibuka.

Jika sijil HTTPS ialah *self-signed*, setiap tablet perlu menerima amaran sijil sekali sahaja.

## Pemasangan (Docker / Dokploy)

`Dockerfile` disediakan. Pangkalan data disimpan di `/data` (ditetapkan melalui env `DB_PATH`).

```bash
docker build -t kehadiran-wajah .
docker run -d -p 8080:80 -v kehadiran-data:/data --name kehadiran kehadiran-wajah
```

Di **Dokploy**:

1. Buat **Application**. Sumber boleh dari Git, atau muat naik ZIP melalui **Drop**.
2. Build Type: **Dockerfile**.
3. **Mounts**: tambah volume dengan mount path `/data`. Wajib, supaya data tidak hilang semasa redeploy.
4. **Domains**: port `80`, hidupkan HTTPS.
5. Jalankan **satu replika sahaja**, kerana SQLite ialah satu fail.

## Tetapan

Kebanyakan tetapan diubah melalui halaman **Tetapan** di dashboard dan disimpan dalam pangkalan data. [config.php](config.php) hanya mengandungi nilai lalai.

| Tetapan | Lalai | Keterangan |
|---|---|---|
| Had masa lewat | 07:30 | Imbasan selepas masa ini ditanda Lewat |
| Ambang padanan | 0.90 | 0.80–0.99. Naikkan jika ada salah kenal, turunkan jika pelajar kerap "tidak dikenali" |
| Bilangan sampel | 5 | Sampel wajah semasa pendaftaran (3–10) |
| Tempoh kad kiosk | 4 saat | Tempoh kad ucapan dipaparkan |
| Bunyi | Hidup | Bunyi notifikasi di kiosk |

Selepas mengubah tetapan, muat semula skrin kiosk di tablet.

## Struktur fail

```
├── kiosk.php          Skrin imbasan untuk tablet
├── index.php          Papan pemuka
├── daftar.php         Daftar pelajar + sampel wajah
├── pelajar.php        Senarai pelajar
├── laporan.php        Laporan harian + eksport CSV
├── laporan_pdf.php    Laporan PDF
├── tetapan.php        Tetapan sistem, tukar PIN, sandaran
├── api.php            API JSON (imbas, daftar, hapus, statistik)
├── db.php             Sambungan SQLite, skema, fungsi bantuan
├── config.php         Nilai lalai
├── auth.php, login.php, logout.php, layout.php
├── assets/
│   ├── face.js        Kod kongsi: muat model, kamera, embedding
│   ├── models/        Model face-api (offline)
│   └── vendor/        face-api.js dan Tailwind (offline)
├── lib/fpdf/          Pustaka PDF
├── data/              Pangkalan data SQLite (tidak dimasukkan ke Git)
└── Dockerfile
```

## Sandaran

- Halaman **Tetapan → Muat Turun Sandaran** menghasilkan salinan pangkalan data.
- Untuk memulihkan, gantikan fail `data/kehadiran.sqlite` (atau `/data/kehadiran.sqlite` di Docker) dengan fail sandaran.

## Keselamatan dan privasi

- Data wajah disimpan sebagai **nombor sahaja**. Tiada gambar disimpan atau dihantar.
- Folder `data/` dan `lib/`, serta fail `.sqlite`, disekat daripada diakses terus melalui `.htaccess`.
- Halaman admin dilindungi PIN. PIN yang ditukar disimpan dalam bentuk hash.
- Dapatkan **persetujuan ibu bapa/penjaga** sebelum mendaftarkan wajah pelajar, selaras dengan prinsip Akta Perlindungan Data Peribadi 2010 (Akta 709).
- Jika sistem perlu diakses dari luar sekolah, gunakan VPN (contohnya Tailscale) atau Cloudflare Tunnel dengan Cloudflare Access. **Jangan dedahkan halaman admin terus ke internet.**

## Pustaka pihak ketiga

| Pustaka | Lesen |
|---|---|
| [@vladmandic/face-api](https://github.com/vladmandic/face-api) 1.7.13 | MIT |
| [Tailwind CSS](https://tailwindcss.com) 3.4 (Play CDN, disimpan setempat) | MIT |
| [FPDF](http://www.fpdf.org) 1.86 | Bebas digunakan (lihat `lib/fpdf/LICENSE.txt`) |
