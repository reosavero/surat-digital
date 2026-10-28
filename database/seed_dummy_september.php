<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Autoloader.php';
require_once __DIR__ . '/../helpers/Model.php';
require_once __DIR__ . '/../models/Setting.php';
require_once __DIR__ . '/../models/Disposisi.php';
require_once __DIR__ . '/../helpers/DisposisiExcelService.php';

$kodeSurat = 'SR-DUMMY-SEP2026';
$check = Database::fetch('SELECT id FROM surat WHERE kode_surat = ?', [$kodeSurat]);

if ($check) {
    $suratId = (int)$check['id'];
    echo "Dummy surat already exists with ID: {$suratId}\n";
} else {
    $meta = [
        'nomor_agenda' => '045/AGD/IX/2026',
        'tanggal_agenda' => '18 September 2026',
        'dari' => 'Bagian Program TVRI Jawa Timur',
        'hal_disposisi' => 'Liputan Khusus Dokumenter Budaya Jatim (Dummy Rekap)',
        'sifat_disposisi' => 'Penting'
    ];

    Database::query(
        "INSERT INTO surat (kode_surat, type, user_id, nomor_surat, perihal, tanggal_surat, lampiran, kepada, isi_surat, meta_data, status, created_at, updated_at) 
         VALUES (?, 'izin_produksi', 1, ?, ?, '2026-09-18', '1 (satu) Berkas', 'Kepala LPP TVRI Stasiun Jawa Timur', 'Permohonan izin produksi peliputan dokumenter seni budaya Jawa Timur.', ?, 'completed', '2026-09-18 09:30:00', '2026-09-18 14:20:00')",
        [
            $kodeSurat,
            'B-088/TU/TVRI-JATIM/IX/2026',
            'Liputan Khusus Dokumenter Budaya Jatim (Dummy September)',
            json_encode($meta)
        ]
    );

    $suratId = (int)Database::lastInsertId();

    $checklist = ['Ditindaklanjuti', 'Dikoordinasikan', 'Ketua Tim Program', 'Ketua Tim Berita'];

    Database::query(
        "INSERT INTO disposisi (surat_id, sekre_user_id, sifat_surat, catatan_sekre, sekre_approved_at, kepsta_user_id, checklist_json, catatan_kepsta, kepsta_signed_at, created_at, updated_at) 
         VALUES (?, 3, 'Penting', 'Mohon petunjuk dan arahan Bapak Kepala Stasiun.', '2026-09-18 10:15:00', 4, ?, 'Disetujui untuk dilaksanakan dengan memperhatikan SOP & anggaran.', '2026-09-18 14:20:00', '2026-09-18 10:00:00', '2026-09-18 14:20:00')",
        [
            $suratId,
            json_encode($checklist)
        ]
    );

    echo "Created dummy surat and disposisi ID: {$suratId}\n";
}

// Make sure 2026-09 is not in deleted periods
DisposisiExcelService::restorePeriod(2026, 9);

// Generate Excel file for September 2026
$filePath = DisposisiExcelService::generateExcelForPeriod(2026, 9);
echo "Generated Excel file at: {$filePath}\n";

$periods = DisposisiExcelService::getPeriods();
echo "Available periods count: " . count($periods) . "\n";
foreach ($periods as $p) {
    echo " - {$p['period_label']} (Total: {$p['total_surat']} Surat, File: {$p['filename']})\n";
}
