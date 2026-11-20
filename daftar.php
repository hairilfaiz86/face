<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
perlu_admin();
atas('Daftar Pelajar', 'daftar');
$kelas = senarai_kelas();
?>
<h2 class="text-2xl font-bold text-biru-900 mb-1">Daftar Pelajar Baharu</h2>
<p class="text-slate-500 mb-6">Isi maklumat, kemudian ambil <?= BIL_SAMPEL ?> sampel wajah. Minta pelajar gerakkan kepala sedikit antara sampel.</p>

<div class="grid lg:grid-cols-5 gap-6">
  <form id="borang" class="lg:col-span-2 bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6 space-y-4" autocomplete="off">
    <div>
      <label class="text-sm font-medium text-slate-600">Nama penuh <span class="text-rose-500">*</span></label>
      <input name="nama" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-4 focus:ring-biru-100 focus:border-biru-500">
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="text-sm font-medium text-slate-600">No. murid</label>
        <input name="no_murid" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-4 focus:ring-biru-100 focus:border-biru-500">
      </div>
      <div>
        <label class="text-sm font-medium text-slate-600">Kelas <span class="text-rose-500">*</span></label>
        <input name="kelas" required list="senaraiKelas" placeholder="cth: 5 BESTARI" class="mt-1 w-full uppercase rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-4 focus:ring-biru-100 focus:border-biru-500">
        <datalist id="senaraiKelas"><?php foreach ($kelas as $k): ?><option value="<?= h($k) ?>"><?php endforeach; ?></datalist>
      </div>
    </div>

    <div>
      <div class="flex justify-between text-sm mb-1.5"><span class="font-medium text-slate-600">Sampel wajah</span><span id="kiraan" class="tabular-nums text-slate-500">0 / <?= BIL_SAMPEL ?></span></div>
      <div class="flex gap-1.5" id="titik">
        <?php for ($i = 0; $i < BIL_SAMPEL; $i++): ?><div class="h-2.5 flex-1 rounded-full bg-slate-200 transition"></div><?php endfor; ?>
      </div>
    </div>

    <div class="rounded-xl border border-biru-100 bg-biru-50/60 p-4 text-sm text-slate-600">
      <p class="flex items-center gap-2 font-semibold text-biru-900">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l8 3v6c0 4.5-3.4 8.3-8 9-4.6-.7-8-4.5-8-9V6l8-3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/></svg>
        Perlindungan Data Peribadi
      </p>
      <p class="mt-1.5 leading-relaxed">Data wajah ialah data peribadi yang sensitif. Sistem ini mengendalikannya selaras dengan prinsip <b>Akta Perlindungan Data Peribadi 2010 (Akta 709)</b>:</p>
      <ul class="mt-2 space-y-1 list-disc list-inside marker:text-biru-500">
        <li><b>Gambar tidak disimpan.</b> Hanya ciri wajah dalam bentuk nombor (embedding) direkodkan.</li>
        <li>Digunakan <b>untuk merekod kehadiran sahaja</b>, bukan untuk tujuan lain.</li>
        <li>Disimpan di pelayan sekolah dan <b>tidak dikongsi</b> dengan pihak ketiga.</li>
        <li>Data dipadam sepenuhnya apabila rekod pelajar dihapuskan, contohnya apabila pelajar berpindah.</li>
      </ul>
      <label class="mt-3 flex items-start gap-2.5 cursor-pointer rounded-lg bg-white p-3 ring-1 ring-slate-200 hover:ring-biru-200">
        <input type="checkbox" name="persetujuan" id="persetujuan" required class="mt-0.5 w-4 h-4 accent-biru-700 shrink-0">
        <span class="text-slate-700">Saya mengesahkan bahawa <b>persetujuan ibu bapa/penjaga</b> telah diperoleh untuk mendaftarkan data wajah pelajar ini.</span>
      </label>
    </div>

    <div id="mesej" class="hidden text-sm rounded-xl px-4 py-3"></div>

    <div class="flex gap-3 pt-2">
      <button type="button" id="semula" class="px-4 py-3 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200">Semula</button>
      <button type="submit" id="simpan" disabled class="flex-1 bg-biru-800 hover:bg-biru-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-semibold py-3 rounded-xl transition">Simpan Pelajar</button>
    </div>
  </form>

  <section class="lg:col-span-3">
    <div class="relative aspect-[4/3] bg-biru-950 rounded-2xl overflow-hidden shadow-lg">
      <video id="video" class="absolute inset-0 w-full h-full object-cover -scale-x-100" playsinline muted></video>
      <canvas id="kanvas" class="absolute inset-0 w-full h-full object-cover -scale-x-100"></canvas>
      <div id="denyar" class="absolute inset-0 bg-white opacity-0 pointer-events-none transition duration-200"></div>
      <div id="status" class="absolute top-3 left-3 bg-black/60 text-white text-sm px-3 py-1.5 rounded-full">Memuatkan model AI…</div>
    </div>
    <button id="ambil" disabled class="mt-4 w-full flex items-center justify-center gap-2 bg-emas-500 hover:bg-emas-400 disabled:opacity-50 text-biru-950 font-bold text-lg py-4 rounded-2xl shadow transition">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h1l2-3h8l2 3h1a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3.5"/></svg>
      Ambil Sampel
    </button>
    <p class="text-xs text-slate-400 mt-2 text-center">Hanya nombor embedding disimpan — gambar tidak dihantar ke pelayan.</p>
  </section>
</div>

<script src="assets/vendor/face-api.min.js"></script>
<script src="assets/face.js"></script>
<script>
const BIL = <?= (int)BIL_SAMPEL ?>;
const $ = (id) => document.getElementById(id);
const video = $('video'), kanvas = $('kanvas'), btnAmbil = $('ambil'), btnSimpan = $('simpan');
let sampel = [], terakhir = null, sibuk = false;

function kemas() {
  $('kiraan').textContent = `${sampel.length} / ${BIL}`;
  [...$('titik').children].forEach((el, i) => el.className = `h-2.5 flex-1 rounded-full transition ${i < sampel.length ? 'bg-emerald-500' : 'bg-slate-200'}`);
  btnSimpan.disabled = sampel.length < BIL;
  btnAmbil.disabled = sampel.length >= BIL || !Wajah.sedia;
}
function mesej(teks, jenis) {
  const el = $('mesej');
  el.textContent = teks;
  el.className = `text-sm rounded-xl px-4 py-3 ${jenis === 'ok' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'}`;
}

async function pratonton() {
  if (!sibuk) {
    terakhir = await Wajah.embedding(video, 0.2);
    Wajah.lukis(kanvas, video, terakhir?.box, terakhir && !terakhir.terlaluJauh ? '#22c55e' : '#facc15');
    $('status').textContent = !terakhir ? 'Wajah tidak dikesan' : terakhir.terlaluJauh ? 'Dekatkan wajah' : 'Wajah dikesan ✓';
  }
  setTimeout(pratonton, 200);
}

btnAmbil.onclick = async () => {
  sibuk = true;
  const w = await Wajah.embedding(video, 0.2);
  sibuk = false;
  if (!w) return mesej('Wajah tidak dikesan. Pastikan pencahayaan mencukupi.', 'ralat');
  if (w.terlaluJauh) return mesej('Wajah terlalu jauh dari kamera.', 'ralat');
  sampel.push(w.descriptor);
  $('denyar').classList.replace('opacity-0', 'opacity-70');
  setTimeout(() => $('denyar').classList.replace('opacity-70', 'opacity-0'), 150);
  $('mesej').classList.add('hidden');
  kemas();
};

$('semula').onclick = () => { sampel = []; $('mesej').classList.add('hidden'); kemas(); };

$('borang').onsubmit = async (e) => {
  e.preventDefault();
  const f = new FormData(e.target);
  btnSimpan.disabled = true;
  btnSimpan.textContent = 'Menyimpan…';
  try {
    const r = await Wajah.post('api.php?t=daftar', {
      nama: f.get('nama'), no_murid: f.get('no_murid'), kelas: f.get('kelas'), sampel,
      persetujuan: $('persetujuan').checked,
    });
    if (r.ok) {
      mesej(r.mesej, 'ok');
      e.target.nama.value = ''; e.target.no_murid.value = '';
      $('persetujuan').checked = false;
      sampel = [];
      e.target.nama.focus();
    } else mesej(r.mesej || 'Gagal menyimpan.', 'ralat');
  } catch (err) { mesej('Tiada sambungan ke pelayan.', 'ralat'); }
  btnSimpan.textContent = 'Simpan Pelajar';
  kemas();
};

(async () => {
  try {
    await Wajah.muat();
    $('status').textContent = 'Membuka kamera…';
    await Wajah.kamera(video);
    kemas();
    pratonton();
  } catch (e) {
    $('status').textContent = e.name === 'NotAllowedError' ? 'Akses kamera ditolak' : (e.message || 'Gagal memulakan');
    $('status').classList.add('bg-rose-600');
  }
})();
</script>
<?php bawah();
