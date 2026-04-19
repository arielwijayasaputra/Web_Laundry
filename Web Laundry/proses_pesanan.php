<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}
$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
$no_pesanan = $chars[rand(0, 25)] . $chars[rand(0, 25)] . '-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

$nama = htmlspecialchars(trim($_POST['nama'] ?? ''));
$telepon = htmlspecialchars(trim($_POST['telepon'] ?? ''));
$email = htmlspecialchars(trim($_POST['email'] ?? ''));
$alamat = htmlspecialchars(trim($_POST['alamat'] ?? ''));

$jenis_layanan = $_POST['jenis_layanan'] ?? '';
$jumlah = floatval($_POST['jumlah'] ?? 0);
$tgl_masuk = htmlspecialchars(trim($_POST['tgl_masuk'] ?? ''));
$tgl_ambil = htmlspecialchars(trim($_POST['tgl_ambil'] ?? ''));
$catatan = htmlspecialchars(trim($_POST['catatan'] ?? ''));

$pewangi = $_POST['pewangi'] ?? [];
$setrika_khusus = $_POST['setrika_khusus'] ?? 'tidak';
$pengiriman = $_POST['pengiriman'] ?? 'ambil_sendiri';
$express = $_POST['express'] ?? 'tidak';

$metode_bayar = $_POST['metode_bayar'] ?? '';
$no_rekening = htmlspecialchars(trim($_POST['no_rekening'] ?? ''));
$id_ewallet = htmlspecialchars(trim($_POST['id_ewallet'] ?? ''));

$layanan_data = [
    'dry_clean' => ['nama' => 'Cuci Kering (Dry Clean)', 'harga' => 3000, 'satuan' => 'kg'],
    'cuci_lipat' => ['nama' => 'Cuci Lipat (Wash & Fold)', 'harga' => 5000, 'satuan' => 'kg'],
    'cuci_setrika' => ['nama' => 'Cuci Setrika (Wash & Iron)', 'harga' => 6000, 'satuan' => 'kg'],
    'per_piece' => ['nama' => 'Cuci Satuan (Per Piece)', 'harga' => 4000, 'satuan' => 'item'],
    'sepatu' => ['nama' => 'Laundry Sepatu', 'harga' => 25000, 'satuan' => 'pasang'],
    'karpet' => ['nama' => 'Laundry Karpet', 'harga' => 30000, 'satuan' => 'm²'],
];

$layanan_info = $layanan_data[$jenis_layanan] ?? null;
$harga_satuan = $layanan_info ? $layanan_info['harga'] : 0;
$nama_layanan = $layanan_info ? $layanan_info['nama'] : '-';
$satuan = $layanan_info ? $layanan_info['satuan'] : '';

$is_kg = in_array($jenis_layanan, ['dry_clean', 'cuci_lipat', 'cuci_setrika']);

$biaya_layanan = $harga_satuan * $jumlah;
$harga_pewangi_per_satuan = match ($jenis_layanan) {
    'dry_clean', 'cuci_lipat', 'cuci_setrika' => 2000,
    'per_piece' => 1000,
    'sepatu' => 3000,
    'karpet' => 4000,
    default => 0,
};
$biaya_pewangi = !empty($pewangi) ? $harga_pewangi_per_satuan * $jumlah : 0;
if (in_array('Tanpa Pewangi', $pewangi)) {
    $biaya_pewangi = 0;
    $pewangi = [];
}
$biaya_setrika = ($setrika_khusus === 'ya') ? 5000 * $jumlah : 0;
$biaya_kirim = ($pengiriman === 'delivery') ? 5000 : 0;
$biaya_express = match ($express) {
    'besok' => 5000,
    'hari_sama' => 15000,
    default => 0,
};
$total = $biaya_layanan + $biaya_pewangi + $biaya_setrika + $biaya_kirim + $biaya_express;

function fmt($angka)
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

$label_pengiriman = ($pengiriman === 'delivery') ? 'Antar ke Alamat' : 'Ambil Sendiri di Toko';
$label_setrika = ($setrika_khusus === 'ya') ? 'Ya' : 'Tidak';
$label_pewangi = !empty($pewangi) ? implode(', ', array_map('htmlspecialchars', $pewangi)) : 'Tidak ada';
$label_express = match ($express) {
    'besok' => 'Express Besok (+Rp 5.000)',
    'hari_sama' => 'Express Hari Sama (+Rp 15.000)',
    default => 'Tidak (Reguler)',
};

$label_metode = [
    'tunai' => 'Tunai (Cash)',
    'transfer' => 'Transfer Bank',
    'ewallet' => 'E-Wallet (OVO, GoPay, Dana)',
    'qris' => 'QRIS',
];
$nama_metode = $label_metode[$metode_bayar] ?? '-';

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rincian Pesanan</title>
    <link rel="icon" href="logo.png" type="image/png" />
    <link rel="stylesheet" href="style.css" />
    <style>
        .result-wrap {
            max-width: 820px;
            margin: -24px auto 48px;
            padding: 0 16px;
            position: relative;
            z-index: 1;
        }

        .result-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 48px rgba(107, 79, 58, 0.13);
            overflow: hidden;
        }

        .success-banner {
            background: linear-gradient(135deg, #2d6a2d, #4caf50);
            color: #fff;
            padding: 28px 36px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .success-banner .icon {
            font-size: 2.4rem;
        }

        .success-banner h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            font-weight: 700;
        }

        .success-banner p {
            font-size: 0.88rem;
            opacity: 0.85;
            margin-top: 4px;
        }

        .detail-section {
            padding: 28px 36px;
            border-bottom: 1px solid var(--border);
        }

        .detail-section:last-of-type {
            border-bottom: none;
        }

        .detail-title {
            font-family: 'Playfair Display', serif;
            font-size: 1rem;
            color: var(--brown);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .detail-title .badge {
            background: var(--accent);
            color: #fff;
            border-radius: 6px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.65rem;
            font-weight: 600;
            padding: 3px 8px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px 24px;
        }

        .detail-item .d-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--brown-light);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 3px;
        }

        .detail-item .d-value {
            font-size: 0.93rem;
            color: var(--dark);
        }

        .detail-item.full {
            grid-column: span 2;
        }

        .total-result-box {
            background: linear-gradient(135deg, #0d3b6e, #1565c0);
            border-radius: 14px;
            padding: 22px 28px;
            color: #fff;
        }

        .total-result-box .t-label {
            font-size: 0.78rem;
            opacity: 0.75;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 600;
        }

        .total-result-box .t-value {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: #90caf9;
            font-weight: 700;
            margin-top: 4px;
        }

        .breakdown-list {
            margin-top: 14px;
            font-size: 0.82rem;
            opacity: 0.85;
        }

        .br-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
        }

        .br-row.total-row {
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            padding-top: 8px;
            margin-top: 4px;
            font-weight: 600;
        }

        .back-link {
            display: block;
            text-align: center;
            padding: 28px;
            background: var(--cream);
        }

        .back-link a {
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--brown);
            text-decoration: none;
            border: 1.5px solid var(--border);
            border-radius: 999px;
            padding: 12px 36px;
            transition: background 0.2s, border-color 0.2s;
            display: inline-block;
        }

        .back-link a:hover {
            background: #fff;
            border-color: var(--brown-light);
        }

        @media (max-width: 640px) {
            .detail-section {
                padding: 22px 18px;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .detail-item.full {
                grid-column: span 1;
            }

            .success-banner {
                padding: 22px 18px;
            }

            .back-link {
                padding: 22px 18px;
            }
        }
    </style>
</head>

<body>

    <header class="page-header page-header--clean">
        <span class="logo-icon">✨</span>
        <h1>Terima kasih telah memesan di WashUp Laundry</h1>
        <p class="subtitle">Layanan Cuci Profesional &amp; Terpercaya</p>
        <div class="profile-bar">
            <span>🏪 <strong>WashUp Laundry</strong></span>
            <span>📍 Jl. Diponegoro 17</span>
            <span>📞 085123456789</span>
            <span>✉️ washuplaundry@gmail.com</span>
        </div>
    </header>

    <div class="result-wrap">
        <div class="result-card">

            <div class="success-banner">
                <span class="icon">✅</span>
                <div>
                    <h2>Pesanan Berhasil Diterima!</h2>
                    <p style="margin-top:6px; font-size:1.2rem; opacity:0.7;">No. Pesanan:
                        <strong><?= $no_pesanan ?></strong>
                    </p>
                </div>
            </div>

            <div class="detail-section">
                <div class="detail-title"><span class="badge">A</span> Data Pelanggan</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="d-label">Nama Lengkap</div>
                        <div class="d-value"><?= $nama ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="d-label">Nomor Telepon</div>
                        <div class="d-value"><?= $telepon ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="d-label">Email</div>
                        <div class="d-value"><?= $email ?: '-' ?></div>
                    </div>
                    <div class="detail-item full">
                        <div class="d-label">Alamat Lengkap</div>
                        <div class="d-value"><?= $alamat ?></div>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <div class="detail-title"><span class="badge">B</span> Detail Pesanan</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="d-label">Nomor Pesanan</div>
                        <div class="d-value"><strong><?= $no_pesanan ?></strong></div>
                    </div>
                    <div class="detail-item">
                        <div class="d-label">Jenis Layanan</div>
                        <div class="d-value"><?= $nama_layanan ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="d-label">Jumlah</div>
                        <div class="d-value"><?= $jumlah ?> <?= $satuan ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="d-label">Tanggal Masuk</div>
                        <div class="d-value"><?= $tgl_masuk ?: 'tgl_masuk' ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="d-label">Tanggal Pengambilan</div>
                        <div class="d-value"><?= $tgl_ambil ?: 'tgl_ambil' ?></div>
                    </div>
                    <?php if ($catatan): ?>
                        <div class="detail-item full">
                            <div class="d-label">Catatan Tambahan</div>
                            <div class="d-value"><?= $catatan ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="detail-section">
                <div class="detail-title"><span class="badge">C</span> Pilihan Tambahan</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="d-label">Pewangi</div>
                        <div class="d-value"><?= $label_pewangi ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="d-label">Setrika Khusus</div>
                        <div class="d-value"><?= $label_setrika ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="d-label">Pengiriman</div>
                        <div class="d-value"><?= $label_pengiriman ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="d-label">Layanan Express</div>
                        <div class="d-value"><?= $label_express ?></div>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <div class="detail-title"><span class="badge">D</span> Ringkasan Pembayaran</div>
                <div class="total-result-box">
                    <div class="t-label">Total Pembayaran</div>
                    <div class="t-value"><?= fmt($total) ?></div>
                    <div class="breakdown-list">
                        <div class="br-row">
                            <span>Layanan (<?= $jumlah ?> <?= $satuan ?> × <?= fmt($harga_satuan) ?>)</span>
                            <span><?= fmt($biaya_layanan) ?></span>
                        </div>
                        <?php if ($biaya_pewangi > 0): ?>
                            <div class="br-row">
                                <span>Pewangi (<?= $jumlah ?>     <?= $satuan ?> ×
                                    <?= fmt($harga_pewangi_per_satuan) ?>)</span>
                                <span><?= fmt($biaya_pewangi) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($biaya_setrika > 0): ?>
                            <div class="br-row">
                                <span>Setrika Khusus (<?= $jumlah ?>     <?= $satuan ?> × Rp 5.000)</span>
                                <span><?= fmt($biaya_setrika) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($biaya_kirim > 0): ?>
                            <div class="br-row">
                                <span>Pengiriman ke Alamat</span>
                                <span><?= fmt($biaya_kirim) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($biaya_express > 0): ?>
                            <div class="br-row">
                                <span>Layanan Express</span>
                                <span><?= fmt($biaya_express) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="br-row total-row">
                            <span>TOTAL</span>
                            <span><?= fmt($total) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <div class="detail-title"><span class="badge">E</span> Metode Pembayaran</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="d-label">Metode</div>
                        <div class="d-value"><?= $nama_metode ?></div>
                    </div>
                    <?php if ($metode_bayar === 'transfer' && $no_rekening): ?>
                        <div class="detail-item">
                            <div class="d-label">Nomor Rekening</div>
                            <div class="d-value"><?= $no_rekening ?></div>
                        </div>
                    <?php elseif ($metode_bayar === 'ewallet' && $id_ewallet): ?>
                        <div class="detail-item">
                            <div class="d-label">ID E-Wallet</div>
                            <div class="d-value"><?= $id_ewallet ?></div>
                        </div>
                    <?php elseif ($metode_bayar === 'qris'): ?>
                        <div class="detail-item full">
                            <div class="d-label">Instruksi</div>
                            <div class="d-value">Tunjukkan kode QRIS kepada kasir atau scan melalui aplikasi e-wallet Anda.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="back-link">
                <a href="index.html">← Buat Pesanan Baru</a>
            </div>

        </div>
    </div>

</body>

</html>