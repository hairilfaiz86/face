<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
perlu_admin();

if (empty($_SESSION['token'])) $_SESSION['token'] = bin2hex(random_bytes(16));

// ---- Muat turun sandaran pangkalan data ----
if (isset($_GET['sandaran'])) {
    $tmp = tempnam(sys_get_temp_dir(), 'kh');
    @unlink($tmp);
    db()->exec('VACUUM INTO ' . db()->quote($tmp));
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="sandaran_kehadiran_' . date('Ymd_His') . '.sqlite"');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    unlink($tmp);
    exit;
}

$ralat = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['token'], $_POST['token'] ?? '')) {
        $ralat[] = 'Sesi tamat. Sila cuba lagi.';
    } elseif (($_POST['borang'] ?? '') === 'umum') {
        $d = [
            'nama_sekolah'   => trim($_POST['nama_sekolah'] ?? ''),
            'singkatan'      => strtoupper(trim($_POST['singkatan'] ?? '')),
            'motto'          => trim($_POST['motto'] ?? ''),
            'had_lewat'      => $_POST['had_lewat'] ?? '',
            'ambang_padanan' => (float)($_POST['ambang_padanan'] ?? 0),
            'bil_sampel'     => (int)($_POST['bil_sampel'] ?? 0),
            'tempoh_kad'     => (int)($_POST['tempoh_kad'] ?? 0),
            'bunyi'          => isset($_POST['bunyi']) ? '1' : '0',
        ];
        if ($d['nama_sekolah'] === '') $ralat[] = 'Nama sekolah wajib diisi.';
        if ($d['singkatan'] === '') $d['singkatan'] = mb_substr($d['nama_sekolah'], 0, 6);
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $d['had_lewat'])) $ralat[] = 'Format masa lewat tidak sah.';
        if ($d['ambang_padanan'] < 0.80 || $d['ambang_padanan'] > 0.99) $ralat[] = 'Ambang padanan mesti antara 0.80 dan 0.99.';
        if ($d['bil_sampel'] < 3 || $d['bil_sampel'] > 10) $ralat[] = 'Bilangan sampel mesti antara 3 dan 10.';
        if ($d['tempoh_kad'] < 2 || $d['tempoh_kad'] > 15) $ralat[] = 'Tempoh paparan mesti antara 2 dan 15 saat.';
        if (!$ralat) {
            $d['ambang_padanan'] = number_format($d['ambang_padanan'], 2, '.', '');
            simpan_tetapan($d);
            $_SESSION['kilat'] = 'Tetapan berjaya disimpan.';
            header('Location: tetapan.php');
            exit;
        }
    } elseif (($_POST['borang'] ?? '') === 'pin') {
        $baru = $_POST['pin_baru'] ?? '';
        if (!semak_pin($_POST['pin_semasa'] ?? '')) $ralat[] = 'PIN semasa tidak tepat.';
        elseif (!preg_match('/^\d{4,8}$/', $baru)) $ralat[] = 'PIN baharu mesti 4–8 digit nombor.';
        elseif ($baru !== ($_POST['pin_sahkan'] ?? '')) $ralat[] = 'Pengesahan PIN tidak sepadan.';
        else {
            simpan_tetapan(['pin_hash' => password_hash($baru, PASSWORD_DEFAULT)]);
            $_SESSION['kilat'] = 'PIN admin berjaya ditukar.';
            header('Location: tetapan.php');
            exit;
        }
    }
}

$t = $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['borang'] ?? '') === 'umum'
    ? array_merge(semua_tetapan(), array_intersect_key($_POST, TETAPAN_LALAI), ['bunyi' => isset($_POST['bunyi']) ? '1' : '0'])
    : semua_tetapan();
$pinLalai = semak_pin(PIN_LALAI);
$kilat = $_SESSION['kilat'] ?? '';
unset($_SESSION['kilat']);

$saizDb = is_file(FAIL_DB) ? filesize(FAIL_DB) : 0;
$bilPelajar = (int)db()->query('SELECT COUNT(*) FROM pelajar')->fetchColumn();
$bilRekod = (int)db()->query('SELECT COUNT(*) FROM kehadiran')->fetchColumn();

$input = 'mt-1 w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-4 focus:ring-biru-100 focus:border-biru-500';

function kad_mula(string $tajuk, string $sub): void
{ ?>
  <section class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200">
    <div class="px-6 py-4 border-b border-slate-100">
      <h3 class="font-semibold text-biru-900"><?= $tajuk ?></h3>
      <p class="text-sm text-slate-500"><?= $sub ?></p>
    </div>
    <div class="p-6 space-y-5">
<?php }
function kad_tamat(): void { echo "</div></section>"; }

atas('Tetapan', 'tetapan');
?>
<h2 class="text-2xl font-bold text-biru-900 mb-1">Tetapan Sistem</h2>
<p class="text-slate-500 mb-6">Perubahan berkuat kuasa serta-merta. Muat semula skrin kiosk selepas menyimpan.</p>

<?php if ($kilat): ?>
  <div class="mb-5 rounded-xl bg-emerald-50 text-emerald-700 px-4 py-3 font-medium">✓ <?= h($kilat) ?></div>
<?php endif; ?>
<?php if ($ralat): ?>
  <div class="mb-5 rounded-xl bg-rose-50 text-rose-700 px-4 py-3"><?= implode('<br>', array_map('h', $ralat)) ?></div>
<?php endif; ?>
<?php if ($pinLalai): ?>
  <div class="mb-5 rounded-xl bg-emas-300/40 text-biru-950 px-4 py-3">⚠️ PIN admin masih menggunakan nilai lalai. Sila tukar di bahagian <a href="#keselamatan" class="font-semibold underline">Keselamatan</a>.</div>
<?php endif; ?>

<div class="grid lg:grid-cols-3 gap-6 items-start">
<form method="post" class="lg:col-span-2 space-y-6">
  <input type="hidden" name="token" value="<?= h($_SESSION['token']) ?>">
  <input type="hidden" name="borang" value="umum">

  <?php kad_mula('Maklumat Sekolah', 'Dipaparkan di dashboard, kiosk dan laporan.'); ?>
    <div>
      <label class="text-sm font-medium text-slate-600">Nama sekolah</label>
      <input name="nama_sekolah" value="<?= h($t['nama_sekolah']) ?>" required maxlength="100" class="<?= $input ?>">
    </div>
    <div class="grid sm:grid-cols-3 gap-4">
      <div>
        <label class="text-sm font-medium text-slate-600">Singkatan</label>
        <input name="singkatan" value="<?= h($t['singkatan']) ?>" maxlength="12" class="<?= $input ?> uppercase">
      </div>
      <div class="sm:col-span-2">
        <label class="text-sm font-medium text-slate-600">Motto <span class="text-slate-400 font-normal">(dipaparkan di kiosk)</span></label>
        <input name="motto" value="<?= h($t['motto']) ?>" maxlength="120" class="<?= $input ?>">
      </div>
    </div>
  <?php kad_tamat(); ?>

  <?php kad_mula('Waktu Kehadiran', 'Imbasan selepas masa ini akan ditanda sebagai Lewat.'); ?>
    <div class="sm:w-1/2">
      <label class="text-sm font-medium text-slate-600">Had masa lewat</label>
      <input type="time" name="had_lewat" value="<?= h(substr($t['had_lewat'], 0, 5)) ?>" required class="<?= $input ?> text-lg">
    </div>
  <?php kad_tamat(); ?>

  <?php kad_mula('Pengecaman Wajah', 'Laraskan ketepatan padanan wajah.'); ?>
    <div>
      <div class="flex items-center justify-between">
        <label class="text-sm font-medium text-slate-600">Ambang padanan</label>
        <span class="flex items-center gap-2">
          <span id="labelAmbang" class="text-xs font-semibold px-2.5 py-1 rounded-full"></span>
          <output id="nilaiAmbang" class="font-bold tabular-nums text-biru-900 text-lg"></output>
        </span>
      </div>
      <input type="range" id="ambang" name="ambang_padanan" min="0.80" max="0.99" step="0.01" value="<?= h($t['ambang_padanan']) ?>" class="w-full mt-3 accent-biru-700">
      <div class="flex justify-between text-xs text-slate-400 mt-1"><span>0.80 · Longgar</span><span>Ketat · 0.99</span></div>
      <p class="text-sm text-slate-500 mt-3 bg-slate-50 rounded-xl p-3 leading-relaxed">
        <b>Naikkan</b> jika ada pelajar tersalah kenal sebagai orang lain.
        <b>Turunkan</b> jika pelajar berdaftar kerap "tidak dikenali". Cadangan: 0.88–0.92.
      </p>
    </div>
    <div class="sm:w-1/2">
      <label class="text-sm font-medium text-slate-600">Bilangan sampel semasa pendaftaran</label>
      <select name="bil_sampel" class="<?= $input ?> bg-white">
        <?php for ($i = 3; $i <= 10; $i++): ?><option value="<?= $i ?>" <?= (int)$t['bil_sampel'] === $i ? 'selected' : '' ?>><?= $i ?> sampel<?= $i === 5 ? ' (cadangan)' : '' ?></option><?php endfor; ?>
      </select>
      <p class="text-xs text-slate-400 mt-1">Lebih banyak sampel = lebih tepat, tetapi pendaftaran lebih lama.</p>
    </div>
  <?php kad_tamat(); ?>

  <?php kad_mula('Skrin Kiosk', 'Paparan pada tablet di pintu masuk.'); ?>
    <div class="grid sm:grid-cols-2 gap-5 items-end">
      <div>
        <label class="text-sm font-medium text-slate-600">Tempoh kad ucapan dipaparkan</label>
        <div class="flex items-center gap-2 mt-1">
          <input type="number" name="tempoh_kad" min="2" max="15" value="<?= h($t['tempoh_kad']) ?>" class="<?= $input ?> mt-0 w-24 text-center">
          <span class="text-slate-500">saat</span>
        </div>
      </div>
      <label class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 px-4 py-3 cursor-pointer hover:bg-slate-50">
        <span><span class="block text-sm font-medium text-slate-700">Bunyi notifikasi</span><span class="text-xs text-slate-400">Bunyi “ting” apabila berjaya</span></span>
        <input type="checkbox" name="bunyi" value="1" <?= $t['bunyi'] === '1' ? 'checked' : '' ?> class="peer sr-only">
        <span class="relative w-12 h-7 rounded-full bg-slate-300 peer-checked:bg-emerald-500 transition after:absolute after:top-1 after:left-1 after:w-5 after:h-5 after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-5"></span>
      </label>
    </div>
  <?php kad_tamat(); ?>

  <div class="sticky bottom-4 flex justify-end">
    <button class="bg-biru-800 hover:bg-biru-700 text-white font-semibold px-8 py-3 rounded-xl shadow-lg transition">Simpan Tetapan</button>
  </div>
</form>

<div class="space-y-6">
  <form method="post" id="keselamatan" class="scroll-mt-6">
    <input type="hidden" name="token" value="<?= h($_SESSION['token']) ?>">
    <input type="hidden" name="borang" value="pin">
    <?php kad_mula('Keselamatan', 'Tukar PIN untuk masuk halaman admin.'); ?>
      <div>
        <label class="text-sm font-medium text-slate-600">PIN semasa</label>
        <input type="password" name="pin_semasa" inputmode="numeric" required class="<?= $input ?> tracking-widest">
      </div>
      <div>
        <label class="text-sm font-medium text-slate-600">PIN baharu <span class="text-slate-400 font-normal">(4–8 digit)</span></label>
        <input type="password" name="pin_baru" inputmode="numeric" pattern="\d{4,8}" required class="<?= $input ?> tracking-widest">
      </div>
      <div>
        <label class="text-sm font-medium text-slate-600">Sahkan PIN baharu</label>
        <input type="password" name="pin_sahkan" inputmode="numeric" pattern="\d{4,8}" required class="<?= $input ?> tracking-widest">
      </div>
      <button class="w-full bg-slate-800 hover:bg-slate-700 text-white font-semibold py-2.5 rounded-xl">Tukar PIN</button>
    <?php kad_tamat(); ?>
  </form>

  <?php kad_mula('Data & Sandaran', 'Simpan salinan pangkalan data secara berkala.'); ?>
    <dl class="grid grid-cols-3 gap-2 text-center">
      <div class="bg-slate-50 rounded-xl p-3"><dt class="text-xs text-slate-500">Pelajar</dt><dd class="font-bold text-biru-900"><?= $bilPelajar ?></dd></div>
      <div class="bg-slate-50 rounded-xl p-3"><dt class="text-xs text-slate-500">Rekod</dt><dd class="font-bold text-biru-900"><?= $bilRekod ?></dd></div>
      <div class="bg-slate-50 rounded-xl p-3"><dt class="text-xs text-slate-500">Saiz</dt><dd class="font-bold text-biru-900"><?= number_format($saizDb / 1024, 0) ?> KB</dd></div>
    </dl>
    <a href="?sandaran=1" class="flex items-center justify-center gap-2 w-full bg-emerald-600 hover:bg-emerald-500 text-white font-semibold py-2.5 rounded-xl">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
      Muat Turun Sandaran
    </a>
    <p class="text-xs text-slate-400">Untuk memulihkan, gantikan fail <code>data/kehadiran.sqlite</code> dengan fail sandaran.</p>
  <?php kad_tamat(); ?>
</div>
</div>

<script>
const ambang = document.getElementById('ambang');
function kemasAmbang() {
  const v = parseFloat(ambang.value);
  document.getElementById('nilaiAmbang').textContent = v.toFixed(2);
  const [teks, cls] = v < 0.86 ? ['Longgar', 'bg-rose-100 text-rose-700'] : v <= 0.93 ? ['Seimbang', 'bg-emerald-100 text-emerald-700'] : ['Ketat', 'bg-biru-100 text-biru-800'];
  const l = document.getElementById('labelAmbang');
  l.textContent = teks; l.className = 'text-xs font-semibold px-2.5 py-1 rounded-full ' + cls;
}
ambang.addEventListener('input', kemasAmbang); kemasAmbang();
</script>
<?php bawah();
