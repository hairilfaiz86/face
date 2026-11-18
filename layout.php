<?php
function kepala_html(string $tajuk): void
{ ?>
<!doctype html>
<html lang="ms">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($tajuk) ?> · <?= h(SINGKATAN) ?></title>
<link rel="icon" href="assets/ikon.svg" type="image/svg+xml">
<script src="assets/vendor/tailwind.js"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        biru: { 50:'#eef4ff',100:'#d9e6ff',200:'#bcd2ff',500:'#3b6fe0',600:'#2552c4',700:'#1d409c',800:'#18337a',900:'#122659',950:'#0b1838' },
        emas: { 300:'#fde68a',400:'#fcd34d',500:'#f5b301',600:'#d99a00' },
      },
      fontFamily: { sans: ['"Segoe UI"','Roboto','"Helvetica Neue"','Arial','sans-serif'] },
    },
  },
};
</script>
<style>
  .tanpa-skrol { scrollbar-width: none; } .tanpa-skrol::-webkit-scrollbar { display: none; }
  .corak { background-image: radial-gradient(rgba(255,255,255,.08) 1px, transparent 1px); background-size: 18px 18px; }
</style>
</head>
<?php }

function atas(string $tajuk, string $aktif = ''): void
{
    kepala_html($tajuk);
    $menu = [
        'index'   => ['Papan Pemuka', 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10'],
        'daftar'  => ['Daftar Pelajar', 'M12 4v16m8-8H4'],
        'pelajar' => ['Senarai Pelajar', 'M17 20h5v-2a4 4 0 00-5-3.9M9 20H2v-2a4 4 0 015-3.9m10-6.1a4 4 0 11-8 0 4 4 0 018 0z'],
        'laporan' => ['Laporan', 'M9 17v-6h6v6m-9 4h12a2 2 0 002-2V5a2 2 0 00-2-2H6a2 2 0 00-2 2v14a2 2 0 002 2z'],
        'tetapan' => ['Tetapan', 'M10.3 4.3a1.7 1.7 0 013.4 0 1.7 1.7 0 002.6 1.1 1.7 1.7 0 012.4 2.4 1.7 1.7 0 001 2.6 1.7 1.7 0 010 3.4 1.7 1.7 0 00-1 2.6 1.7 1.7 0 01-2.4 2.4 1.7 1.7 0 00-2.6 1 1.7 1.7 0 01-3.4 0 1.7 1.7 0 00-2.6-1 1.7 1.7 0 01-2.4-2.4 1.7 1.7 0 00-1-2.6 1.7 1.7 0 010-3.4 1.7 1.7 0 001-2.6 1.7 1.7 0 012.4-2.4 1.7 1.7 0 002.6-1.1zM15 12a3 3 0 11-6 0 3 3 0 016 0z'],
    ];
    ?>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
<header class="bg-biru-900 corak text-white shadow-lg">
  <div class="max-w-6xl mx-auto px-4 py-4 flex items-center gap-3">
    <img src="assets/ikon.svg" alt="" class="w-11 h-11">
    <div class="flex-1 min-w-0">
      <p class="text-emas-400 text-xs font-semibold tracking-widest uppercase">Sistem Kehadiran Wajah</p>
      <h1 class="text-lg sm:text-xl font-bold truncate"><?= h(NAMA_SEKOLAH) ?></h1>
    </div>
    <a href="kiosk.php" target="_blank" class="hidden sm:inline-flex items-center gap-2 bg-emas-500 hover:bg-emas-400 text-biru-950 font-semibold px-4 py-2 rounded-xl transition">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h1l2-3h8l2 3h1a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3.5"/></svg>
      Buka Kiosk
    </a>
    <a href="logout.php" class="text-sm text-biru-200 hover:text-white px-2">Log keluar</a>
  </div>
  <nav class="max-w-6xl mx-auto px-4 flex gap-1 overflow-x-auto tanpa-skrol">
    <?php foreach ($menu as $k => [$label, $ikon]): $on = $k === $aktif; ?>
      <a href="<?= $k ?>.php" class="flex items-center gap-2 whitespace-nowrap px-4 py-3 text-sm font-medium rounded-t-xl transition <?= $on ? 'bg-slate-100 text-biru-900' : 'text-biru-100 hover:bg-white/10' ?>">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $ikon ?>"/></svg>
        <?= $label ?>
      </a>
    <?php endforeach; ?>
    <a href="kiosk.php" target="_blank" class="sm:hidden flex items-center whitespace-nowrap px-4 py-3 text-sm font-semibold text-emas-400">Kiosk ↗</a>
  </nav>
</header>
<main class="max-w-6xl mx-auto px-4 py-6 sm:py-8">
<?php }

function bawah(): void
{ ?>
</main>
<footer class="text-center text-xs text-slate-400 pb-6">
  &copy; <?= date('Y') ?> <?= h(NAMA_SEKOLAH) ?> · Data wajah disimpan sebagai nombor sahaja, tiada gambar disimpan.
</footer>
</body>
</html>
<?php }
