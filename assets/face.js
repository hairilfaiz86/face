// Kod kongsi pengesanan wajah — semua berjalan dalam pelayar, tiada gambar dihantar.
const Wajah = {
  sedia: false,
  stream: null,

  async muat(base = 'assets/models') {
    try { await faceapi.tf.setBackend('webgl'); } catch (e) { /* guna backend lalai */ }
    await faceapi.tf.ready();
    await Promise.all([
      faceapi.nets.tinyFaceDetector.loadFromUri(base),
      faceapi.nets.faceLandmark68Net.loadFromUri(base),
      faceapi.nets.faceRecognitionNet.loadFromUri(base),
    ]);
    this.sedia = true;
  },

  async kamera(video) {
    if (!navigator.mediaDevices?.getUserMedia) {
      throw new Error('Kamera tidak disokong. Pastikan halaman dibuka melalui HTTPS.');
    }
    this.stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
      audio: false,
    });
    video.srcObject = this.stream;
    await video.play();
  },

  /** Kesan satu wajah dan pulangkan embedding 128 nombor (atau null). */
  async embedding(video, saizMin = 0.18) {
    if (!this.sedia || video.readyState < 2) return null;
    const r = await faceapi
      .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 }))
      .withFaceLandmarks()
      .withFaceDescriptor();
    if (!r) return null;
    const box = r.detection.box;
    return {
      descriptor: Array.from(r.descriptor, (x) => Math.round(x * 1e6) / 1e6),
      box,
      skor: r.detection.score,
      terlaluJauh: box.width < video.videoWidth * saizMin,
    };
  },

  /** Lukis kotak wajah pada kanvas (dicerminkan seperti video). */
  lukis(canvas, video, box, warna = '#facc15') {
    const ctx = canvas.getContext('2d');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    if (!box) return;
    ctx.strokeStyle = warna;
    ctx.lineWidth = 4;
    const r = 18, x = box.x, y = box.y, w = box.width, h = box.height;
    ctx.beginPath();
    ctx.roundRect ? ctx.roundRect(x, y, w, h, r) : ctx.rect(x, y, w, h);
    ctx.stroke();
  },

  async post(url, data) {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    });
    return res.json();
  },
};
