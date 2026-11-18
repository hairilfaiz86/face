<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';

$ralat = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (semak_pin((string)($_POST['pin'] ?? ''))) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        header('Location: index.php');
        exit;
    }
    sleep(1);
    $ralat = 'PIN tidak tepat.';
}
kepala_html('Log Masuk');
?>
<body class="min-h-screen bg-biru-950 corak flex items-center justify-center p-4">
  <form method="post" class="w-full max-w-sm bg-white rounded-3xl shadow-2xl p-8 text-center">
    <img src="assets/ikon.svg" class="w-20 h-20 mx-auto -mt-16 drop-shadow-xl" alt="">
    <p class="mt-4 text-xs font-semibold tracking-widest uppercase text-emas-600">Sistem Kehadiran Wajah</p>
    <h1 class="text-xl font-bold text-biru-900"><?= h(NAMA_SEKOLAH) ?></h1>
    <label class="block mt-6 text-left text-sm font-medium text-slate-600">PIN Admin</label>
    <input name="pin" type="password" inputmode="numeric" autofocus required
           class="mt-1 w-full text-center text-2xl tracking-[.5em] rounded-xl border border-slate-300 px-4 py-3 focus:outline-none focus:ring-4 focus:ring-biru-200 focus:border-biru-500">
    <?php if ($ralat): ?><p class="mt-3 text-sm text-rose-600"><?= h($ralat) ?></p><?php endif; ?>
    <button class="mt-6 w-full bg-biru-800 hover:bg-biru-700 text-white font-semibold py-3 rounded-xl transition">Masuk</button>
    <a href="kiosk.php" class="block mt-4 text-sm text-biru-600 hover:underline">Pergi ke Kiosk →</a>
  </form>
</body>
</html>
