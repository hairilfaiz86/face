<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
perlu_admin();

$tarikh = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['tarikh'] ?? '') ? $_GET['tarikh'] : date('Y-m-d');
$kelasPilih = $_GET['kelas'] ?? '';

$rekod = data_laporan($tarikh, $kelasPilih);

if (isset($_GET['csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="kehadiran_' . $tarikh . ($kelasPilih ? '_' . preg_replace('/\W+/', '', $kelasPilih) : '') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM supaya Excel baca UTF-8
    fputcsv($out, ['Tarikh', 'Nama', 'No. Murid', 'Kelas', 'Masa', 'Status']);
    foreach ($rekod as $r) {
        fputcsv($out, [$tarikh, $r['nama'], $r['no_murid'], $r['kelas'], $r['masa'] ?? '', $r['status'] ? ucfirst($r['status']) : 'Tidak hadir']);
    }
    exit;
}

$hadir = count(array_filter($rekod, fn($r) => $r['status'] === 'hadir'));
$lewat = count(array_filter($rekod, fn($r) => $r['status'] === 'lewat'));
$tidak = count($rekod) - $hadir - $lewat;

atas('Laporan', 'laporan');
?>
<div class="flex flex-wrap items-end justify-between gap-3 mb-6">
  <div>
    <h2 class="text-2xl font-bold text-biru-900">Laporan Kehadiran</h2>
    <p class="text-slate-500"><?= h(date('d/m/Y', strtotime($tarikh))) ?><?= $kelasPilih ? ' · ' . h($kelasPilih) : ' · Semua kelas' ?></p>
  </div>
  <div class="flex flex-wrap gap-2">
    <a href="laporan_pdf.php?<?= h(http_build_query(['tarikh' => $tarikh, 'kelas' => $kelasPilih])) ?>" target="_blank" class="inline-flex items-center gap-2 bg-rose-600 hover:bg-rose-500 text-white font-semibold px-5 py-2.5 rounded-xl">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9l-6-6H7a2 2 0 00-2 2v14a2 2 0 002 2zM13 3v6h6M9 13h6M9 17h4"/></svg>
      Cetak PDF
    </a>
    <a href="?<?= h(http_build_query(['tarikh' => $tarikh, 'kelas' => $kelasPilih, 'csv' => 1])) ?>" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold px-5 py-2.5 rounded-xl">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
      Eksport CSV
    </a>
  </div>
</div>

<form class="flex flex-wrap gap-3 mb-5">
  <input type="date" name="tarikh" value="<?= h($tarikh) ?>" max="<?= date('Y-m-d') ?>" onchange="this.form.submit()" class="rounded-xl border border-slate-300 px-4 py-2.5 bg-white">
  <select name="kelas" onchange="this.form.submit()" class="rounded-xl border border-slate-300 px-4 py-2.5 bg-white">
    <option value="">Semua kelas</option>
    <?php foreach (senarai_kelas() as $k): ?><option <?= $k === $kelasPilih ? 'selected' : '' ?>><?= h($k) ?></option><?php endforeach; ?>
  </select>
</form>

<div class="grid grid-cols-3 gap-3 mb-5">
  <div class="bg-emerald-50 text-emerald-700 rounded-2xl p-4"><p class="text-sm">Hadir</p><p class="text-3xl font-black"><?= $hadir ?></p></div>
  <div class="bg-emas-300/40 text-biru-950 rounded-2xl p-4"><p class="text-sm">Lewat</p><p class="text-3xl font-black"><?= $lewat ?></p></div>
  <div class="bg-rose-50 text-rose-700 rounded-2xl p-4"><p class="text-sm">Tidak hadir</p><p class="text-3xl font-black"><?= $tidak ?></p></div>
</div>

<div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-biru-50 text-biru-900 text-left">
      <tr><th class="px-5 py-3 w-12">#</th><th class="px-5 py-3">Nama</th><th class="px-5 py-3">Kelas</th><th class="px-5 py-3">Masa</th><th class="px-5 py-3">Status</th></tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($rekod as $i => $r):
        [$lbl, $cls] = match ($r['status']) {
            'hadir' => ['Hadir', 'bg-emerald-100 text-emerald-700'],
            'lewat' => ['Lewat', 'bg-emas-300/60 text-biru-950'],
            default => ['Tidak hadir', 'bg-rose-100 text-rose-700'],
        }; ?>
        <tr class="hover:bg-slate-50">
          <td class="px-5 py-3 text-slate-400"><?= $i + 1 ?></td>
          <td class="px-5 py-3 font-medium"><?= h($r['nama']) ?></td>
          <td class="px-5 py-3"><?= h($r['kelas']) ?></td>
          <td class="px-5 py-3 tabular-nums"><?= $r['masa'] ? h(substr($r['masa'], 0, 5)) : '—' ?></td>
          <td class="px-5 py-3"><span class="text-xs font-semibold px-2.5 py-1 rounded-full <?= $cls ?>"><?= $lbl ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rekod): ?><tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">Tiada data.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php bawah();
