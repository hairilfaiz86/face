<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/layout.php';
kepala_html('Kiosk Kehadiran');
?>
<body class="h-screen overflow-hidden bg-biru-950 text-white select-none">
<div class="h-full flex flex-col lg:flex-row">

  <!-- Panel kiri: identiti & jam -->
  <aside class="corak bg-biru-900 lg:w-[38%] px-6 py-5 lg:p-10 flex lg:flex-col items-center lg:items-start justify-between gap-4">
    <div class="flex items-center gap-3">
      <img src="assets/ikon.svg" class="w-12 h-12 lg:w-16 lg:h-16" alt="">
      <div>
        <p class="text-emas-400 text-xs lg:text-sm font-semibold tracking-widest uppercase">Kehadiran Harian</p>
        <h1 class="text-lg lg:text-3xl font-extrabold leading-tight"><?= h(NAMA_SEKOLAH) ?></h1>
        <?php if (MOTTO !== ''): ?><p class="hidden sm:block text-biru-200 text-xs lg:text-base italic mt-0.5">“<?= h(MOTTO) ?>”</p><?php endif; ?>
      </div>
    </div>
    <div class="text-right lg:text-left">
      <div id="jam" class="text-4xl lg:text-8xl font-black tabular-nums tracking-tight">--:--</div>
      <div id="tarikh" class="text-biru-200 lg:text-xl mt-1"></div>
    </div>
    <div class="hidden lg:block text-biru-200 text-sm leading-relaxed">
      <p class="font-semibold text-white mb-1">Cara guna</p>
      <ol class="list-decimal list-inside space-y-0.5">
        <li>Berdiri di hadapan tablet</li>
        <li>Pastikan wajah dalam bingkai</li>
        <li>Tunggu nama anda dipaparkan</li>
      </ol>
      <a href="index.php" class="inline-block mt-6 text-biru-500 hover:text-biru-200 text-xs">← Admin</a>
    </div>
  </aside>

  <!-- Panel kanan: kamera -->
  <section class="flex-1 relative flex items-center justify-center p-4 lg:p-10">
    <div class="relative w-full max-w-2xl aspect-[4/3] rounded-[2rem] overflow-hidden bg-black ring-4 ring-biru-800 shadow-2xl" id="bingkai">
      <video id="video" class="absolute inset-0 w-full h-full object-cover -scale-x-100" playsinline muted></video>
      <canvas id="kanvas" class="absolute inset-0 w-full h-full object-cover -scale-x-100"></canvas>
      <!-- bucu panduan -->
      <div class="pointer-events-none absolute inset-[18%] border-emas-400/80">
        <span class="absolute -top-1 -left-1 w-10 h-10 border-t-4 border-l-4 border-emas-400 rounded-tl-2xl"></span>
        <span class="absolute -top-1 -right-1 w-10 h-10 border-t-4 border-r-4 border-emas-400 rounded-tr-2xl"></span>
        <span class="absolute -bottom-1 -left-1 w-10 h-10 border-b-4 border-l-4 border-emas-400 rounded-bl-2xl"></span>
        <span class="absolute -bottom-1 -right-1 w-10 h-10 border-b-4 border-r-4 border-emas-400 rounded-br-2xl"></span>
      </div>
      <div id="status" class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-black/60 backdrop-blur px-5 py-2 rounded-full text-sm lg:text-base font-medium whitespace-nowrap">
        Memuatkan model AI…
      </div>
    </div>

    <!-- Kad keputusan -->
    <div id="kad" class="absolute inset-0 flex items-center justify-center p-6 opacity-0 pointer-events-none transition duration-300">
      <div id="kadDalam" class="w-full max-w-xl rounded-[2rem] p-8 lg:p-12 text-center shadow-2xl scale-90 transition duration-300">
        <div id="kadIkon" class="mx-auto w-20 h-20 lg:w-24 lg:h-24 rounded-full flex items-center justify-center bg-white/20 mb-5"></div>
        <p id="kadUcapan" class="text-xl lg:text-2xl font-medium opacity-90"></p>
        <h2 id="kadNama" class="text-3xl lg:text-5xl font-black mt-1 leading-tight"></h2>
        <p id="kadKelas" class="mt-3 text-lg opacity-90"></p>
        <span id="kadStatus" class="inline-block mt-5 px-5 py-2 rounded-full bg-white/25 font-semibold"></span>
      </div>
    </div>
  </section>
</div>

<script src="assets/vendor/face-api.min.js"></script>
<script src="assets/face.js"></script>
<script>
const $ = (id) => document.getElementById(id);
const video = $('video'), kanvas = $('kanvas'), statusEl = $('status');
const HARI = ['Ahad','Isnin','Selasa','Rabu','Khamis','Jumaat','Sabtu'];
const BULAN = ['Januari','Februari','Mac','April','Mei','Jun','Julai','Ogos','September','Oktober','November','Disember'];
const TEMPOH_KAD = <?= max(1, TEMPOH_KAD) * 1000 ?>, BUNYI = <?= BUNYI ? 'true' : 'false' ?>;

function kemasJam() {
  const d = new Date();
  $('jam').textContent = d.toLocaleTimeString('ms-MY', { hour: '2-digit', minute: '2-digit', hour12: false });
  $('tarikh').textContent = `${HARI[d.getDay()]}, ${d.getDate()} ${BULAN[d.getMonth()]} ${d.getFullYear()}`;
}
setInterval(kemasJam, 1000); kemasJam();

function ucapan() {
  const j = new Date().getHours();
  if (j < 12) return 'Selamat pagi';
  if (j < 14) return 'Selamat tengah hari';
  if (j < 19) return 'Selamat petang';
  return 'Selamat malam';
}

// Bunyi ringkas (offline, WebAudio)
let audio;
function bunyi(ok) {
  if (!BUNYI) return;
  try {
    audio = audio || new (window.AudioContext || window.webkitAudioContext)();
    const notes = ok ? [880, 1320] : [300, 220];
    notes.forEach((f, i) => {
      const o = audio.createOscillator(), g = audio.createGain();
      o.frequency.value = f; o.type = 'sine';
      g.gain.setValueAtTime(0.2, audio.currentTime + i * 0.13);
      g.gain.exponentialRampToValueAtTime(0.001, audio.currentTime + i * 0.13 + 0.25);
      o.connect(g).connect(audio.destination);
      o.start(audio.currentTime + i * 0.13); o.stop(audio.currentTime + i * 0.13 + 0.3);
    });
  } catch (e) {}
}

const IKON = {
  ok: '<svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>',
  jam: '<svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>',
  x: '<svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>',
};

let kadTimer;
function tunjukKad({ warna, ikon, ucap, nama, kelas, status }, ms = TEMPOH_KAD) {
  $('kadDalam').className = `w-full max-w-xl rounded-[2rem] p-8 lg:p-12 text-center shadow-2xl transition duration-300 scale-100 ${warna}`;
  $('kadIkon').innerHTML = ikon;
  $('kadUcapan').textContent = ucap;
  $('kadNama').textContent = nama;
  $('kadKelas').textContent = kelas || '';
  $('kadKelas').hidden = !kelas;
  $('kadStatus').textContent = status || '';
  $('kadStatus').hidden = !status;
  $('kad').classList.remove('opacity-0');
  clearTimeout(kadTimer);
  kadTimer = setTimeout(() => $('kad').classList.add('opacity-0'), ms);
}

let sibuk = false, rehatHingga = 0, gagalBerturut = 0;

async function gelung() {
  const kini = Date.now();
  if (!sibuk && kini >= rehatHingga) {
    sibuk = true;
    try {
      const w = await Wajah.embedding(video);
      if (!w) {
        Wajah.lukis(kanvas, video, null);
        statusEl.textContent = 'Sila hadapkan wajah ke kamera';
        gagalBerturut = 0;
      } else if (w.terlaluJauh) {
        Wajah.lukis(kanvas, video, w.box, '#93c5fd');
        statusEl.textContent = 'Sila dekatkan wajah';
      } else {
        Wajah.lukis(kanvas, video, w.box, '#facc15');
        statusEl.textContent = 'Mengenal pasti…';
        const r = await Wajah.post('api.php?t=imbas', { embedding: w.descriptor });
        if (r.ok && r.dikenali) {
          gagalBerturut = 0;
          const lewat = r.status === 'lewat';
          tunjukKad({
            warna: !r.baru ? 'bg-biru-600 text-white' : lewat ? 'bg-emas-500 text-biru-950' : 'bg-emerald-600 text-white',
            ikon: lewat ? IKON.jam : IKON.ok,
            ucap: ucapan() + ',',
            nama: r.nama,
            kelas: r.kelas,
            status: r.baru ? (lewat ? `Lewat · ${r.masa}` : `Hadir · ${r.masa}`) : `Sudah direkod pada ${r.masa}`,
          });
          bunyi(true);
          rehatHingga = Date.now() + TEMPOH_KAD + 500;
        } else if (r.ok) {
          // Elak paparan "tidak dikenali" kerana satu bingkai kabur
          if (++gagalBerturut >= 3) {
            tunjukKad({ warna: 'bg-rose-600 text-white', ikon: IKON.x, ucap: 'Maaf,', nama: 'Wajah tidak dikenali', status: 'Sila cuba lagi atau hubungi guru' }, 2500);
            bunyi(false);
            gagalBerturut = 0;
            rehatHingga = Date.now() + 3000;
          }
        } else {
          statusEl.textContent = r.mesej || 'Ralat pelayan';
          rehatHingga = Date.now() + 2000;
        }
      }
    } catch (e) {
      statusEl.textContent = 'Tiada sambungan ke pelayan';
      rehatHingga = Date.now() + 3000;
    }
    sibuk = false;
  }
  setTimeout(gelung, 250);
}

(async () => {
  try {
    await Wajah.muat();
    statusEl.textContent = 'Membuka kamera…';
    await Wajah.kamera(video);
    statusEl.textContent = 'Sedia';
    gelung();
  } catch (e) {
    statusEl.textContent = e.name === 'NotAllowedError' ? 'Akses kamera ditolak' : (e.message || 'Gagal memulakan');
    statusEl.classList.add('bg-rose-600');
    console.error(e);
  }
})();

// Skrin penuh apabila disentuh (mod kiosk)
document.body.addEventListener('dblclick', () => document.documentElement.requestFullscreen?.());
</script>
</body>
</html>
