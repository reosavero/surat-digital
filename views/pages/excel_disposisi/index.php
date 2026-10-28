<style>
    .excel-header-banner {
        background: linear-gradient(135deg, #0F766E 0%, #047857 50%, #065F46 100%);
        padding: 32px 28px;
        border-radius: 20px;
        color: #FFFFFF;
        margin-bottom: 28px;
        box-shadow: 0 10px 30px rgba(15, 118, 110, 0.25);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
    }
    .excel-header-title {
        font-size: 26px;
        font-weight: 800;
        margin: 0 0 8px 0;
        color: #FFFFFF;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        text-align: center;
    }
    .excel-header-subtitle {
        font-size: 14.5px;
        color: #D1FAE5;
        font-weight: 400;
        margin: 0 auto;
        max-width: 680px;
        line-height: 1.6;
        text-align: center;
    }

    .excel-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 18px;
        margin-bottom: 28px;
    }
    .excel-stat-card {
        background: #FFFFFF;
        border-radius: 16px;
        padding: 20px;
        border: 1.5px solid #E2E8F0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .excel-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
    }

    .excel-filter-card {
        background: #FFFFFF;
        border-radius: 16px;
        padding: 18px 22px;
        border: 1.5px solid #E2E8F0;
        margin-bottom: 26px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 14px;
    }

    .excel-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
        gap: 22px;
        margin-bottom: 30px;
    }
    .excel-month-card {
        background: #FFFFFF;
        border-radius: 18px;
        border: 1.5px solid #E2E8F0;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
        padding: 22px;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }
    .excel-month-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #10B981, #059669);
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    .excel-month-card:hover {
        transform: translateY(-5px);
        border-color: #10B981;
        box-shadow: 0 16px 32px -10px rgba(16, 185, 129, 0.25);
    }
    .excel-month-card:hover::before {
        opacity: 1;
    }

    .excel-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 14px;
    }
    .excel-card-icon {
        width: 44px;
        height: 44px;
        background: #ECFDF5;
        border: 1px solid #A7F3D0;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #059669;
        font-size: 22px;
    }
    .excel-card-period {
        font-size: 19px;
        font-weight: 800;
        color: #0F172A;
        margin: 0;
        line-height: 1.25;
    }
    .excel-card-filename {
        font-size: 12px;
        font-family: 'Consolas', 'Courier New', monospace;
        color: #047857;
        background: #ECFDF5;
        padding: 4px 8px;
        border-radius: 6px;
        display: inline-block;
        margin-top: 6px;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .excel-card-details {
        font-size: 13px;
        color: #475569;
        margin: 14px 0 18px 0;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .excel-card-detail-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .excel-card-actions {
        display: flex;
        gap: 10px;
        padding-top: 14px;
        border-top: 1px dashed #E2E8F0;
    }
    .btn-enter-excel {
        flex: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 9px 14px;
        background: #047857;
        color: #FFFFFF;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        border: 1.5px solid #047857;
        transition: all 0.2s ease;
    }
    .btn-enter-excel:hover {
        background: #065F46;
        border-color: #065F46;
        color: #FFFFFF;
        box-shadow: 0 4px 12px rgba(4, 120, 87, 0.3);
    }
    .btn-download-excel {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 9px 14px;
        background: #ECFDF5;
        color: #065F46;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        border: 1.5px solid #A7F3D0;
        transition: all 0.2s ease;
    }
    .btn-download-excel:hover {
        background: #D1FAE5;
        border-color: #059669;
        color: #065F46;
    }
    .btn-delete-excel {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 9px 12px;
        background: #FEF2F2;
        color: #DC2626;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        border: 1.5px solid #FECACA;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .btn-delete-excel:hover {
        background: #FEE2E2;
        border-color: #EF4444;
        color: #B91C1C;
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.15);
    }
</style>

<div style="max-width: 1300px; margin: 0 auto; padding-bottom: 40px;">
    <!-- Header Banner -->
    <div class="excel-header-banner">
        <div style="text-align: center;">
            <div class="excel-header-title">
                <i class="bi bi-file-earmark-excel-fill" style="color: #6EE7B7;"></i>
                Kelola Rekap Disposisi Excel
            </div>
            <p class="excel-header-subtitle">
                Halaman pengelolaan arsip file Microsoft Excel (.xlsx) otomatis per bulan untuk surat disposisi. 
                Setiap kali Kepala Stasiun meneruskan disposisi, data surat akan terisi otomatis ke file Excel bulan berjalan.
            </p>
        </div>
    </div>

    <!-- Month Cards Grid -->
    <?php if (empty($periods)): ?>
        <div style="text-align: center; padding: 60px 20px; background: #FFFFFF; border-radius: 18px; border: 1.5px dashed #CBD5E1;">
            <i class="bi bi-calendar-x" style="font-size: 48px; color: #94A3B8; margin-bottom: 12px; display: block;"></i>
            <h3 style="font-size: 18px; font-weight: 700; color: #334155; margin-bottom: 6px;">Tidak ada periode ditemukan</h3>
            <p style="color: #64748B; font-size: 14px; margin: 0;">Silakan periksa kata kunci pencarian atau filter tahun Anda.</p>
        </div>
    <?php else: ?>
        <div class="excel-cards-grid">
            <?php foreach ($periods as $p): ?>
                <?php 
                $detailUrl = BASE_URL . "/excel-disposisi/view?year={$p['year']}&month={$p['month']}";
                $downloadUrl = BASE_URL . "/excel-disposisi/download?year={$p['year']}&month={$p['month']}";
                $currentYear = (int)date('Y');
                $currentMonth = (int)date('n');
                $isCurrentMonth = ($p['year'] === $currentYear && $p['month'] === $currentMonth);
                $isPassedMonth = ($p['year'] < $currentYear) || ($p['year'] === $currentYear && $p['month'] < $currentMonth);
                ?>
                <div class="excel-month-card" onclick="window.location.href='<?= $detailUrl ?>'">
                    <div>
                        <div class="excel-card-header">
                            <div>
                                <h3 class="excel-card-period"><?= htmlspecialchars($p['period_label']) ?></h3>
                                <?php if ($isCurrentMonth): ?>
                                    <span style="display: inline-block; margin-top: 4px; background: #ECFDF5; color: #047857; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 6px; border: 1px solid #A7F3D0;">
                                        <i class="bi bi-record-fill" style="font-size: 9px; vertical-align: middle;"></i> Bulan Berjalan
                                    </span>
                                <?php elseif ($isPassedMonth): ?>
                                    <span style="display: inline-block; margin-top: 4px; background: #F8FAFC; color: #64748B; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 6px; border: 1px solid #E2E8F0;">
                                        Periode Lalu
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="excel-card-icon">
                                <i class="bi bi-file-earmark-spreadsheet"></i>
                            </div>
                        </div>

                        <div class="excel-card-details">
                            <div class="excel-card-detail-item">
                                <span><i class="bi bi-file-text"></i> Total Disposisi:</span>
                                <span style="font-weight: 800; color: #0F172A; font-size: 14px;">
                                    <?= $p['total_surat'] ?> Surat
                                </span>
                            </div>
                            <div class="excel-card-detail-item">
                                <span><i class="bi bi-clock-history"></i> Terakhir Update:</span>
                                <span style="color: #64748B; font-size: 12px;"><?= htmlspecialchars($p['last_updated']) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="excel-card-actions" onclick="event.stopPropagation()">
                        <a href="<?= $detailUrl ?>" class="btn-enter-excel">
                            <i class="bi bi-table"></i> Buka File
                        </a>
                        <a href="<?= $downloadUrl ?>" class="btn-download-excel" title="Download Excel (.xlsx)">
                            <i class="bi bi-download"></i> .xlsx
                        </a>
                        <?php if ($isPassedMonth): ?>
                            <button type="button" 
                                    class="btn-delete-excel" 
                                    title="Hapus Card & File Excel Periode <?= htmlspecialchars($p['period_label']) ?>"
                                    onclick="event.stopPropagation(); openDeleteExcelModal(<?= (int)$p['year'] ?>, <?= (int)$p['month'] ?>, '<?= htmlspecialchars(addslashes($p['period_label'])) ?>', '<?= htmlspecialchars(addslashes($p['filename'])) ?>')">
                                <i class="bi bi-trash3"></i> Hapus
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Konfirmasi Hapus Excel Periode -->
<div id="deleteExcelModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.65); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center; padding: 20px;">
    <div class="card-panel-variant" style="width: 100%; max-width: 480px; margin: 0; padding: 28px; background: #FFFFFF; border-radius: 18px; box-shadow: 0 20px 50px rgba(0,0,0,0.25);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid #F3F4F6; padding-bottom: 12px;">
            <h3 style="font-size: 17px; font-weight: 800; color: #991B1B; margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-trash3-fill" style="color: #DC2626;"></i> Konfirmasi Hapus Rekap Excel
            </h3>
            <button type="button" onclick="closeDeleteExcelModal()" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #9CA3AF;">&times;</button>
        </div>

        <div style="margin-bottom: 16px; background: #FEF2F2; padding: 14px 16px; border-radius: 12px; border: 1px solid #FCA5A5;">
            <div style="font-size: 14px; color: #7F1D1D; font-weight: 700; margin-bottom: 4px;">
                Periode: <span id="delModalPeriod" style="color: #1E293B;">-</span>
            </div>
            <div style="font-size: 12px; color: #991B1B; font-family: 'Consolas', monospace; word-break: break-all;" id="delModalFilename">-</div>
        </div>

        <p style="font-size: 13.5px; color: #475569; line-height: 1.5; margin: 0 0 20px 0;">
            Apakah Anda yakin ingin <strong>menghapus card dan file Excel</strong> dari bulan ini? Card akan dihapus dari daftar dan file Excel di server akan dibersihkan.
        </p>

        <form id="deleteExcelForm" method="POST" action="<?= BASE_URL ?>/excel-disposisi/delete" style="margin: 0;">
            <input type="hidden" name="year" id="delModalYear" value="">
            <input type="hidden" name="month" id="delModalMonth" value="">

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeDeleteExcelModal()" class="btn-btn btn-btn-secondary" style="padding: 10px 18px; font-weight: 600; border-radius: 10px; cursor: pointer;">
                    Batal
                </button>
                <button type="submit" class="btn-btn" style="background: #DC2626; color: #FFFFFF; border: none; font-weight: 700; padding: 10px 22px; border-radius: 10px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="bi bi-trash-fill"></i> Ya, Hapus Card & File
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openDeleteExcelModal(year, month, periodLabel, filename) {
    document.getElementById('delModalYear').value = year;
    document.getElementById('delModalMonth').value = month;
    document.getElementById('delModalPeriod').textContent = periodLabel;
    document.getElementById('delModalFilename').textContent = filename;
    document.getElementById('deleteExcelModal').style.display = 'flex';
}

function closeDeleteExcelModal() {
    document.getElementById('deleteExcelModal').style.display = 'none';
}
</script>
