<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lib/fpdf/fpdf.php';
perlu_admin();

$tarikh = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['tarikh'] ?? '') ? $_GET['tarikh'] : date('Y-m-d');
$kelasPilih = $_GET['kelas'] ?? '';
$rekod = data_laporan($tarikh, $kelasPilih);

$hadir = count(array_filter($rekod, fn($r) => $r['status'] === 'hadir'));
$lewat = count(array_filter($rekod, fn($r) => $r['status'] === 'lewat'));
$jumlah = count($rekod);
$tidak = $jumlah - $hadir - $lewat;
$peratus = $jumlah ? round(($hadir + $lewat) / $jumlah * 100, 1) : 0;

/** FPDF guna pengekodan Windows-1252 */
function t(?string $s): string
{
    return iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', (string)$s);
}

class LaporanPDF extends FPDF
{
    public string $subjudul = '';
    // Lebar lajur: #, Nama, No. Murid, Kelas, Masa, Status (jumlah 180mm)
    public array $lajur = [10, 72, 28, 30, 18, 22];

    function Header()
    {
        $this->SetFillColor(18, 38, 89);
        $this->Rect(0, 0, 210, 30, 'F');
        $this->SetFillColor(245, 179, 1);
        $this->Rect(0, 30, 210, 1.5, 'F');

        $this->SetXY(15, 8);
        $this->SetTextColor(252, 211, 77);
        $this->SetFont('Helvetica', 'B', 8);
        $this->Cell(120, 4, t('LAPORAN KEHADIRAN HARIAN'), 0, 2);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Helvetica', 'B', 15);
        $this->Cell(120, 8, t(NAMA_SEKOLAH), 0, 2);
        $this->SetFont('Helvetica', '', 9);
        $this->SetTextColor(188, 210, 255);
        $this->Cell(120, 5, t($this->subjudul), 0, 0);

        $this->SetY(40);
    }

    function Footer()
    {
        $this->SetY(-14);
        $this->SetDrawColor(226, 232, 240);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->Ln(2);
        $this->SetFont('Helvetica', '', 7.5);
        $this->SetTextColor(148, 163, 184);
        $this->Cell(120, 5, t('Dijana pada ' . date('d/m/Y H:i') . ' · Sistem Kehadiran Wajah ' . SINGKATAN), 0, 0);
        $this->Cell(60, 5, t('Halaman ' . $this->PageNo() . ' / {nb}'), 0, 0, 'R');
    }

    function KepalaJadual()
    {
        $this->SetFont('Helvetica', 'B', 9);
        $this->SetFillColor(238, 244, 255);
        $this->SetTextColor(18, 38, 89);
        $this->SetDrawColor(188, 210, 255);
        $label = ['#', 'Nama Pelajar', 'No. Murid', 'Kelas', 'Masa', 'Status'];
        foreach ($label as $i => $l) {
            $this->Cell($this->lajur[$i], 8, t($l), 'TB', 0, $i === 0 || $i >= 4 ? 'C' : 'L', true);
        }
        $this->Ln();
    }

    /** Pastikan ruang mencukupi; jika tidak, halaman baharu + ulang kepala jadual. */
    function Ruang(float $h, bool $kepala = true)
    {
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage();
            if ($kepala) $this->KepalaJadual();
        }
    }
}

$pdf = new LaporanPDF('P', 'mm', 'A4');
$pdf->subjudul = tarikh_melayu($tarikh) . '   |   ' . ($kelasPilih !== '' ? 'Kelas: ' . $kelasPilih : 'Semua kelas');
$pdf->SetTitle(t('Laporan Kehadiran ' . $tarikh), false);
$pdf->SetAuthor(t(NAMA_SEKOLAH), false);
$pdf->SetMargins(15, 40, 15);
$pdf->SetAutoPageBreak(true, 18);
$pdf->AliasNbPages();
$pdf->AddPage();

// ---- Kotak ringkasan ----
$kotak = [
    ['Jumlah Pelajar', $jumlah, [29, 64, 156], [255, 255, 255]],
    ['Hadir', $hadir, [5, 150, 105], [255, 255, 255]],
    ['Lewat', $lewat, [245, 179, 1], [11, 24, 56]],
    ['Tidak Hadir', $tidak, [225, 29, 72], [255, 255, 255]],
];
$lebar = 42; $jarak = 4; $y = $pdf->GetY();
foreach ($kotak as $i => [$label, $nilai, $bg, $fg]) {
    $x = 15 + $i * ($lebar + $jarak);
    $pdf->SetFillColor(...$bg);
    $pdf->Rect($x, $y, $lebar, 20, 'F');
    $pdf->SetTextColor(...$fg);
    $pdf->SetXY($x + 4, $y + 3);
    $pdf->SetFont('Helvetica', '', 8);
    $pdf->Cell($lebar - 8, 4, t($label));
    $pdf->SetXY($x + 4, $y + 8);
    $pdf->SetFont('Helvetica', 'B', 18);
    $pdf->Cell($lebar - 8, 9, (string)$nilai);
}
$pdf->SetY($y + 24);
$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(71, 85, 105);
$pdf->Cell(0, 5, t("Peratus kehadiran: {$peratus}%   ·   Had masa lewat: " . substr(HAD_LEWAT, 0, 5)), 0, 1);
$pdf->Ln(3);

// ---- Jadual ----
$pdf->KepalaJadual();
$kelasSemasa = null;
$bil = 0;
foreach ($rekod as $r) {
    // Baris pemisah kelas apabila memaparkan semua kelas
    if ($kelasPilih === '' && $r['kelas'] !== $kelasSemasa) {
        $pdf->Ruang(14);
        $kelasSemasa = $r['kelas'];
        $bil = 0;
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetFillColor(18, 38, 89);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(array_sum($pdf->lajur), 6.5, t('  ' . $kelasSemasa), 0, 1, 'L', true);
    }
    $pdf->Ruang(7);
    $bil++;
    $zebra = $bil % 2 === 0;
    $pdf->SetFillColor(248, 250, 252);
    $pdf->SetTextColor(30, 41, 59);
    $pdf->SetDrawColor(241, 245, 249);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell($pdf->lajur[0], 7, (string)$bil, 'B', 0, 'C', $zebra);
    $nama = t($r['nama']);
    while ($pdf->GetStringWidth($nama) > $pdf->lajur[1] - 2 && strlen($nama) > 3) $nama = substr($nama, 0, -4) . '...';
    $pdf->Cell($pdf->lajur[1], 7, $nama, 'B', 0, 'L', $zebra);
    $pdf->Cell($pdf->lajur[2], 7, t($r['no_murid'] ?: '-'), 'B', 0, 'L', $zebra);
    $pdf->Cell($pdf->lajur[3], 7, t($r['kelas']), 'B', 0, 'L', $zebra);
    $pdf->Cell($pdf->lajur[4], 7, $r['masa'] ? substr($r['masa'], 0, 5) : '-', 'B', 0, 'C', $zebra);

    [$lbl, $warna] = match ($r['status']) {
        'hadir' => ['Hadir', [5, 150, 105]],
        'lewat' => ['Lewat', [217, 119, 6]],
        default => ['Tidak hadir', [225, 29, 72]],
    };
    $pdf->SetFont('Helvetica', 'B', 8.5);
    $pdf->SetTextColor(...$warna);
    $pdf->Cell($pdf->lajur[5], 7, t($lbl), 'B', 1, 'C', $zebra);
}

if (!$rekod) {
    $pdf->SetFont('Helvetica', 'I', 10);
    $pdf->SetTextColor(148, 163, 184);
    $pdf->Cell(0, 20, t('Tiada data pelajar.'), 0, 1, 'C');
}

// ---- Ruang tandatangan ----
$pdf->Ruang(40, false);
$pdf->Ln(14);
$y = $pdf->GetY();
$pdf->SetTextColor(30, 41, 59);
$pdf->SetDrawColor(100, 116, 139);
foreach ([[15, 'Disediakan oleh:'], [120, 'Disahkan oleh:']] as [$x, $label]) {
    $pdf->SetXY($x, $y);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(75, 5, t($label));
    $pdf->Line($x, $y + 22, $x + 70, $y + 22);
    $pdf->SetXY($x, $y + 23);
    $pdf->SetFont('Helvetica', '', 8);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(75, 4, t('Nama / Cop / Tarikh'));
    $pdf->SetTextColor(30, 41, 59);
}

$nama = 'laporan_kehadiran_' . $tarikh . ($kelasPilih !== '' ? '_' . preg_replace('/\W+/', '', $kelasPilih) : '') . '.pdf';
$pdf->Output(isset($_GET['muat']) ? 'D' : 'I', $nama);
