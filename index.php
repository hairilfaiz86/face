<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
perlu_admin();
atas('Papan Pemuka', 'index');

$kad = [
    ['jumlah', 'Jumlah Pelajar', 'bg-biru-800 text-white', 'text-biru-200'],
    ['hadir', 'Hadir', 'bg-emerald-600 text-white', 'text-emerald-100'],
    ['lewat', 'Lewat', 'bg-emas-500 text-biru-950', 'text-biru-900/70'],
    ['tidak_hadir', 'Belum / Tidak Hadir', 'bg-white text-rose-600 ring-1 ring-slate-200', 'text-slate-500'],
];
?>
<div class="flex flex-wrap items-end justify-between gap-2 mb-6">
  <div>
    <h2 class="text-2xl font-bold text-biru-900">Kehadiran Hari Ini</h2>
    <p class="text-slate-500" id="tarikhHariIni"></p>
  </div>
  <p class="text-xs text-slate-400">Dikemas kini setiap 5 saat · <span id="kemaskini">—</span></p>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
  <?php foreach ($kad as [$k, $label, $warna, $sub]): ?>
    <div class="rounded-2xl p-5 shadow-sm <?= $warna ?>">
      <p class="text-sm font-medium <?= $sub ?>"><?= $label ?></p>
      <p class="text-4xl font-black mt-2 tabular-nums" data-stat="<?= $k ?>">–</p>
    </div>
  <?php endforeach; ?>
</div>

<div class="grid lg:grid-cols-3 gap-6 mt-6">
  <section class="lg:col-span-2 bg-white rounded-2xl shadow-sm ring-1 ring-slate-200">
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
      <h3 class="font-semibold text-biru-900">Imbasan Terkini</h3>
      <span class="flex items-center gap-1.5 text-xs text-emerald-600"><span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>Langsung</span>
    </div>
    <ul id="terkini" class="divide-y divide-slate-100"></ul>
  </section>

  <section class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-5">
    <h3 class="font-semibold text-biru-900">Peratus Kehadiran</h3>
    <div class="relative w-44 h-44 mx-auto mt-4">
      <svg viewBox="0 0 36 36" class="w-full h-full -rotate-90">
        <circle cx="18" cy="18" r="15.9" fill="none" stroke="#e2e8f0" stroke-width="3.5"/>
        <circle id="cincin" cx="18" cy="18" r="15.9" fill="none" stroke="#1d409c" stroke-width="3.5" stroke-linecap="round" stroke-dasharray="0 100" class="transition-all duration-700"/>
      </svg>
      <div class="absolute inset-0 flex flex-col items-center justify-center">
        <span id="peratus" class="text-4xl font-black text-biru-900">0%</span>
        <span class="text-xs text-slate-500">hadir</span>
      </div>
    </div>
    <div class="grid grid-cols-2 gap-3 mt-6">
      <a href="daftar.php" class="text-center text-sm font-semibold bg-biru-50 text-biru-800 hover:bg-biru-100 rounded-xl py-3">+ Daftar Pelajar</a>
      <a href="laporan.php" class="text-center text-sm font-semibold bg-emas-300/50 text-biru-900 hover:bg-emas-300 rounded-xl py-3">Lihat Laporan</a>
    </div>
  </section>
</div>

<script>
const HARI = ['Ahad','Isnin','Selasa','Rabu','Khamis','Jumaat','Sabtu'];
const BULAN = ['Januari','Februari','Mac','April','Mei','Jun','Julai','Ogos','September','Oktober','November','Disember'];
const d = new Date();
document.getElementById('tarikhHariIni').textContent = `${HARI[d.getDay()]}, ${d.getDate()} ${BULAN[d.getMonth()]} ${d.getFullYear()}`;
const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

async function muat() {
  try {
    const r = await (await fetch('api.php?t=statistik')).json();
    if (!r.ok) { if (r.mesej) location.href = 'login.php'; return; }
    for (const k of ['jumlah','hadir','lewat','tidak_hadir']) document.querySelector(`[data-stat="${k}"]`).textContent = r[k];
    const p = r.jumlah ? Math.round((r.hadir + r.lewat) / r.jumlah * 100) : 0;
    document.getElementById('peratus').textContent = p + '%';
    document.getElementById('cincin').setAttribute('stroke-dasharray', `${p} 100`);
    document.getElementById('terkini').innerHTML = r.terkini.length ? r.terkini.map((x) => `
      <li class="flex items-center gap-3 px-5 py-3">
        <div class="w-10 h-10 rounded-full bg-biru-100 text-biru-800 font-bold flex items-center justify-center">${esc(x.nama.trim()[0] || '?')}</div>
        <div class="flex-1 min-w-0"><p class="font-medium truncate">${esc(x.nama)}</p><p class="text-xs text-slate-500">${esc(x.kelas)}</p></div>
        <span class="text-sm tabular-nums text-slate-500">${x.masa.slice(0,5)}</span>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full ${x.status === 'lewat' ? 'bg-emas-300/60 text-biru-950' : 'bg-emerald-100 text-emerald-700'}">${x.status === 'lewat' ? 'Lewat' : 'Hadir'}</span>
      </li>`).join('') : '<li class="px-5 py-10 text-center text-slate-400">Belum ada imbasan hari ini.</li>';
    document.getElementById('kemaskini').textContent = new Date().toLocaleTimeString('ms-MY');
  } catch (e) {}
}
muat(); setInterval(muat, 5000);
</script>
<?php bawah();
