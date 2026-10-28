<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Model.php';
require_once __DIR__ . '/../models/Surat.php';
require_once __DIR__ . '/../models/Disposisi.php';
require_once __DIR__ . '/../models/SuratLog.php';

try {
    echo "Seeding initial sample letters...\n";

    $suratModel = new Surat();

    // Sample 1: Surat Izin Prinsip (Submitted to KPP Bidang)
    $id1 = $suratModel->createSurat([
        'type' => 'izin_prinsip',
        'user_id' => 1,
        'perihal' => 'Permohonan Izin Prinsip Pelaksanaan Live Event Hari Jadi Jatim',
        'kepada' => 'Kepala Sub Bagian Tata Usaha',
        'lampiran' => '1 (satu) Berkas Proposal',
        'tanggal_surat' => date('Y-m-d'),
        'isi_surat' => 'Sehubungan dengan agenda Hari Jadi Provinsi Jawa Timur, bersama ini kami mengajukan permohonan Izin Prinsip untuk penyelenggaraan siaran langsung (live streaming & broadcast) dari lokasi Candi Wilwatikta Pandaan.',
        'meta_data' => [
            'pemohon_nama' => 'Budi Santoso',
            'pemohon_unit' => 'Subbagian Program & Penyiaran',
            'lokasi_kegiatan' => 'Candi Wilwatikta, Pandaan, Pasuruan',
            'waktu_kegiatan' => '20-22 Oktober 2026'
        ],
        'status' => 'submitted_to_kpp'
    ]);

    $suratModel->updateSurat($id1, [
        'nomor_surat' => 'B-015/TU/TVRI-JATIM/' . date('Y')
    ]);

    SuratLog::log($id1, 1, 'Surat Izin Prinsip Dibuat & Dikirim ke KPP Bidang', 'submitted_to_kpp', 'Surat berhasil dibuat oleh Admin Pembuat Surat dan diteruskan ke KPP Bidang untuk TTD & Paraf.');

    // Sample 2: Surat Izin Produksi (KPP Signed -> Sent to TU)
    $id2 = $suratModel->createSurat([
        'type' => 'izin_produksi',
        'user_id' => 1,
        'perihal' => 'Permohonan Izin Produksi Program Acara "Pesona Jawa Timur" Ep. 54',
        'kepada' => 'Kepala Sub Bagian Tata Usaha',
        'lampiran' => '1 (satu) Berkas Susunan Tim',
        'tanggal_surat' => date('Y-m-d', strtotime('-1 day')),
        'isi_surat' => 'Dalam rangka pembuatan episode terbaru program dokumenter Pesona Jawa Timur, tim liputan memohon izin produksi pengambilan gambar lokasi wisata Taman Nasional Bromo Tengger Semeru.',
        'meta_data' => [
            'nama_program' => 'Pesona Jawa Timur',
            'produser' => 'Rina Wijaya',
            'sutradara' => 'Dedi Kurniawan',
            'lokasi_syuting' => 'TN Bromo Tengger Semeru',
            'tanggal_mulai' => '25-27 September 2026',
            'tim_produksi' => '1. Rina (Produser), 2. Dedi (Sutradara), 3. Edo (Kamerawan)',
            'kpp_paraf' => '/assets/img/ttd_tu_stamp.png'
        ],
        'status' => 'kpp_signed'
    ]);

    $suratModel->updateSurat($id2, [
        'nomor_surat' => 'B-042/TU/TVRI-JATIM/' . date('Y'),
        'kpp_user_id' => 6,
        'kpp_signature' => '/assets/img/ttd_tu_stamp.png',
        'kpp_signed_at' => date('Y-m-d H:i:s')
    ]);

    SuratLog::log($id2, 1, 'Surat Izin Produksi Dibuat & Dikirim ke KPP Bidang', 'submitted_to_kpp', 'Surat dibuat oleh Admin.');
    SuratLog::log($id2, 6, 'TTD & Paraf KPP Bidang Dibubuhkan', 'kpp_signed', 'KPP Bidang telah membubuhkan TTD Surat Permohonan dan Paraf Nota Dinas. Diteruskan ke Tata Usaha.');

    echo "Sample data seeded successfully!\n";

} catch (Exception $e) {
    echo "Seeding failed: " . $e->getMessage() . "\n";
}
