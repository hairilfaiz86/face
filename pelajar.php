<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
perlu_admin();

$cari = trim($_GET['cari'] ?? '');
$kelasPilih = $_GET['kelas'] ?? '';
$sql = 'SELECT p.*, (SELECT COUNT(*) FROM wajah w WHERE w.pelajar_id = p.id) AS sampel,
        (SELECT COUNT(*) FROM kehadiran k WHERE k.pelajar_id = p.id) AS hari_hadir
        FROM pelajar p WHERE 1';
$arg = [];
if ($cari !== '') { $sql .= ' AND (p.nama LIKE ? OR p.no_murid LIKE ?)'; $arg[] = "%$cari%"; $arg[] = "%$cari%"; }
if ($kelasPilih !== '') { $sql .= ' AND p.kelas = ?'; $arg[] = $kelasPilih; }
$sql .= ' ORDER BY p.kelas, p.nama';
$st = db()->prepare($sql);
$st->execute($arg);
$senarai = $st->fetchAll();

atas('Senarai Pelajar', 'pelajar');
?>
<div class="flex flex-wrap items-end justify-between gap-3 mb-6">
  <div>
    <h2 class="text-2xl font-bold text-biru-900">Senarai Pelajar</h2>
    <p class="text-slate-500"><?= count($senarai) ?> pelajar dipaparkan</p>
  </div>
  <a href="daftar.php" class="bg-biru-800 hover:bg-biru-700 text-white font-semibold px-5 py-2.5 rounded-xl">+ Daftar Pelajar</a>
</div>

<form class="flex flex-wrap gap-3 mb-4">
  <input name="cari" value="<?= h($cari) ?>" placeholder="Cari nama atau no. murid…" class="flex-1 min-w-[12rem] rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-4 focus:ring-biru-100">
  <select name="kelas" onchange="this.form.submit()" class="rounded-xl border border-slate-300 px-4 py-2.5 bg-white">
    <option value="">Semua kelas</option>
    <?php foreach (senarai_kelas() as $k): ?><option <?= $k === $kelasPilih ? 'selected' : '' ?>><?= h($k) ?></option><?php endforeach; ?>
  </select>
  <button class="px-5 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 font-medium">Cari</button>
</form>

<div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-biru-50 text-biru-900 text-left">
      <tr><th class="px-5 py-3">Nama</th><th class="px-5 py-3">No. Murid</th><th class="px-5 py-3">Kelas</th><th class="px-5 py-3 text-center">Sampel</th><th class="px-5 py-3 text-center">Hari Hadir</th><th class="px-5 py-3"></th></tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($senarai as $p): ?>
        <tr class="hover:bg-slate-50" id="baris<?= $p['id'] ?>">
          <td class="px-5 py-3 font-medium"><?= h($p['nama']) ?></td>
          <td class="px-5 py-3 text-slate-500"><?= h($p['no_murid'] ?: '—') ?></td>
          <td class="px-5 py-3"><span class="bg-biru-100 text-biru-800 text-xs font-semibold px-2.5 py-1 rounded-full"><?= h($p['kelas']) ?></span></td>
          <td class="px-5 py-3 text-center tabular-nums"><?= (int)$p['sampel'] ?></td>
          <td class="px-5 py-3 text-center tabular-nums"><?= (int)$p['hari_hadir'] ?></td>
          <td class="px-5 py-3 text-right">
            <button onclick="hapus(<?= $p['id'] ?>, <?= h(json_encode($p['nama'])) ?>)" class="text-rose-600 hover:bg-rose-50 px-3 py-1.5 rounded-lg font-medium">Hapus</button>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$senarai): ?>
        <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">Tiada pelajar dijumpai.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
async function hapus(id, nama) {
  if (!confirm(`Hapus ${nama}? Data wajah dan rekod kehadiran pelajar ini juga akan dipadam.`)) return;
  const r = await (await fetch('api.php?t=hapus', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) })).json();
  if (r.ok) document.getElementById('baris' + id).remove(); else alert(r.mesej || 'Gagal');
}
</script>
<?php bawah();
