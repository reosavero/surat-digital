<?php
/**
 * Halaman Revisi Surat - Surat Digital TVRI Jawa Timur
 * Displays all letters with status = 'revisi' created by admin user.
 */
$title = $title ?? 'Daftar Surat Perlu Revisi - Surat Digital TVRI Jawa Timur';
$suratList = $suratList ?? [];
$stats = $stats ?? [];
?>

<div style="max-width: 1200px; margin: 0 auto; padding-bottom: 40px;">
    <!-- Page Header Banner -->
    <div class="page-header-banner" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: #FFF7ED; color: #EA580C; display: flex; align-items: center; justify-content: center; font-size: 20px; border: 1px solid #FFEDD5; box-shadow: 0 2px 6px rgba(234, 88, 12, 0.1);">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </div>
                <h1 style="font-size: 22px; font-weight: 800; color: var(--text-heading); margin: 0;">
                    Daftar Surat Masuk (Perlu Revisi)
                </h1>
            </div>
            <p style="font-size: 13.5px; color: var(--text-muted); margin: 0;">
                Klik card surat di bawah ini untuk melihat catatan revisi dan melakukan perbaikan input data surat.
            </p>
        </div>
    </div>

    <!-- Letter Cards List (Consistent Grid Card Style matching KTU Dashboard) -->
    <?php if (empty($suratList)): ?>
        <div style="background: #FFFFFF; border-radius: 18px; border: 1.5px dashed #CBD5E1; padding: 56px 24px; text-align: center; box-shadow: 0 4px 16px rgba(0,0,0,0.02);">
            <div style="width: 68px; height: 68px; border-radius: 50%; background: #F0FDF4; color: #16A34A; display: flex; align-items: center; justify-content: center; font-size: 34px; margin: 0 auto 18px; border: 1.5px solid #DCFCE7;">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <h3 style="font-size: 18px; font-weight: 800; color: #1E293B; margin: 0 0 8px;">Tidak Ada Surat Perlu Revisi</h3>
            <p style="font-size: 14px; color: #64748B; margin: 0 auto 24px; max-width: 480px; line-height: 1.5;">
                Semua surat yang Anda buat telah disetujui atau sedang dalam proses verifikasi workflow.
            </p>
            <a href="<?= BASE_URL ?>/surat" class="btn-btn btn-btn-primary" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none; padding: 10px 22px; border-radius: 12px;">
                <i class="bi bi-file-earmark-text"></i> Lihat Semua Surat
            </a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: flex-start;">
            <?php foreach ($suratList as $surat): ?>
                <?php
                $type = $surat['type'] ?? '';
                $katSurat = $surat['meta']['kategori_surat'] ?? '';
                if ($type === 'nota_dinas' || $katSurat === 'Surat Nota Dinas') {
                    $editUrl = BASE_URL . '/surat-nota-dinas?id=' . $surat['id'];
                } elseif ($type === 'izin_produksi' || str_contains(strtolower($katSurat), 'produksi')) {
                    $editUrl = BASE_URL . '/surat-produksi?id=' . $surat['id'];
                } else {
                    $editUrl = BASE_URL . '/surat-prinsip?id=' . $surat['id'];
                }
                ?>
                <a href="<?= $editUrl ?>" style="width: 360px; max-width: 100%; text-decoration: none; color: inherit; display: block;">
                    <div class="card-panel-variant" style="padding: 22px; border-radius: 16px; background: #FFFFFF; border: 1.5px solid #E2E8F0; transition: all 0.25s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.03); display: flex; flex-direction: column; justify-content: space-between; height: 100%; cursor: pointer;"
                         onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='#EA580C'; this.style.boxShadow='0 12px 24px -8px rgba(234, 88, 12, 0.2)';"
                         onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='#E2E8F0'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.03)';">
                        <div>
                            <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin-bottom: 12px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?= htmlspecialchars($surat['perihal']) ?>
                            </h3>

                            <div style="font-size: 13px; color: #475569; margin-bottom: 6px; display: flex; flex-direction: column; gap: 5px;">
                                <div><i class="bi bi-tag-fill" style="color: #EA580C;"></i> Jenis: <strong><?= Surat::getTypeLabel($surat['type']) ?></strong></div>
                                <div><i class="bi bi-person-fill" style="color: #64748B;"></i> Pembuat: <strong><?= htmlspecialchars($surat['pembuat_nama'] ?? 'Admin') ?></strong></div>
                                <div><i class="bi bi-calendar-event" style="color: #64748B;"></i> Tanggal: <strong><?= date('d F Y', strtotime($surat['tanggal_surat'])) ?></strong></div>
                            </div>
                        </div>

                        <div style="margin-top: 16px; padding-top: 12px; border-top: 1px dashed #E2E8F0; display: flex; align-items: center; justify-content: space-between; font-size: 13px; font-weight: 700; color: #EA580C;">
                            <span>Klik untuk Revisi Surat</span>
                            <i class="bi bi-arrow-right-circle-fill" style="font-size: 16px;"></i>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
