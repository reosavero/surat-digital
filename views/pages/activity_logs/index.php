<?php
/**
 * Activity Logs Index Page - Superadmin
 * TVRI Stasiun Jawa Timur
 */
?>

<div style="max-width: 1360px; margin: 0 auto; padding-bottom: 40px;">
    <!-- Page Header Banner -->
    <div class="page-header-banner" style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 24px; font-weight: 800; color: #0F172A; margin: 0; display: flex; align-items: center; gap: 10px;">
                <i class="bi bi-journal-text" style="color: #059669;"></i> Log Aktivitas Sistem
            </h1>
            <p style="font-size: 14px; color: #64748B; margin-top: 4px; margin-bottom: 0;">
                Audit trail lengkap aktivitas seluruh pengguna sistem Surat Digital LPP TVRI Jawa Timur.
            </p>
        </div>

        <div style="display: flex; align-items: center; gap: 12px;">
            <?php if (!empty($logs)): ?>
                <button type="button" onclick="openClearLogsModal()" class="btn-btn" style="background: #FEF2F2; color: #DC2626; border: 1px solid #FCA5A5; font-size: 13px; padding: 8px 16px; border-radius: 8px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="bi bi-trash3-fill"></i> Bersihkan Log
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card-panel-variant" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.04); padding: 0;">
        <div style="padding: 16px 24px; background: #F8FAFC; border-bottom: 1px solid #E2E8F0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <div style="font-size: 14px; font-weight: 700; color: #1E293B; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-list-stars" style="color: #059669; font-size: 18px;"></i> Daftar Log Aktivitas
                <span style="font-size: 12px; color: #64748B; font-weight: 500;">(Menampilkan <?= count($logs) ?> dari <?= number_format($pagination['total_rows']) ?> log)</span>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                <thead>
                    <tr style="background: #F1F5F9; color: #334155; border-bottom: 1.5px solid #CBD5E1;">
                        <th style="padding: 12px 16px; width: 175px;">Waktu & Tanggal</th>
                        <th style="padding: 12px 16px; width: 220px;">Pengguna</th>
                        <th style="padding: 12px 16px; width: 140px;">Tipe Aktivitas</th>
                        <th style="padding: 12px 16px;">Deskripsi Aktivitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="4" style="padding: 32px; text-align: center; color: #64748B;">
                                <i class="bi bi-inbox" style="font-size: 32px; display: block; margin-bottom: 8px; color: #CBD5E1;"></i>
                                Tidak ada log aktivitas yang ditemukan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        foreach ($logs as $log): 
                            $userName = $log['user_nama'] ?? 'Sistem / Tamu';
                            $userRole = $log['user_role'] ?? 'guest';
                            $action = $log['action'] ?? 'ACTIVITY';
                            $timeFormatted = !empty($log['created_at']) ? date('d M Y, H:i:s', strtotime($log['created_at'])) : '-';

                            // Action Badge Styling
                            $badgeBg = '#F1F5F9';
                            $badgeColor = '#334155';
                            $badgeBorder = '#CBD5E1';
                            $badgeIcon = 'bi-activity';

                            if ($action === 'LOGIN') {
                                $badgeBg = '#ECFDF5'; $badgeColor = '#047857'; $badgeBorder = '#A7F3D0'; $badgeIcon = 'bi-box-arrow-in-right';
                            } elseif ($action === 'LOGOUT') {
                                $badgeBg = '#F3F4F6'; $badgeColor = '#4B5563'; $badgeBorder = '#E5E7EB'; $badgeIcon = 'bi-box-arrow-right';
                            } elseif (str_starts_with($action, 'CREATE_SURAT')) {
                                $badgeBg = '#EFF6FF'; $badgeColor = '#1D4ED8'; $badgeBorder = '#BFDBFE'; $badgeIcon = 'bi-file-earmark-plus-fill';
                            } elseif (str_starts_with($action, 'PROCESS_')) {
                                $badgeBg = '#F0F9FF'; $badgeColor = '#0284C7'; $badgeBorder = '#BAE6FD'; $badgeIcon = 'bi-gear-wide-connected';
                            } elseif (str_contains($action, 'USER')) {
                                $badgeBg = '#FDF2F8'; $badgeColor = '#BE185D'; $badgeBorder = '#FBCFE8'; $badgeIcon = 'bi-person-gear';
                            } elseif (str_contains($action, 'DELETE') || str_contains($action, 'REJECT')) {
                                $badgeBg = '#FEF2F2'; $badgeColor = '#DC2626'; $badgeBorder = '#FCA5A5'; $badgeIcon = 'bi-exclamation-octagon-fill';
                            }
                        ?>
                            <tr style="border-bottom: 1px solid #E2E8F0; transition: background 0.15s;" onmouseover="this.style.background='#F8FAFC';" onmouseout="this.style.background='#FFFFFF';">
                                <td style="padding: 12px 16px; white-space: nowrap; color: #334155; font-weight: 500;">
                                    <i class="bi bi-clock" style="color: #64748B; margin-right: 4px;"></i> <?= $timeFormatted ?>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 32px; height: 32px; border-radius: 50%; background: #003399; color: #FFFFFF; font-weight: 700; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0;">
                                            <?= strtoupper(substr($userName, 0, 1)) ?>
                                        </div>
                                        <div style="min-width: 0;">
                                            <div style="font-weight: 700; color: #0F172A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;"><?= htmlspecialchars($userName) ?></div>
                                            <span style="font-size: 10.5px; font-weight: 700; color: #475569; background: #F1F5F9; padding: 1px 6px; border-radius: 4px; display: inline-block; margin-top: 2px;">
                                                <?= User::getRoleLabel($userRole) ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <span style="font-size: 11.5px; font-weight: 800; background: <?= $badgeBg ?>; color: <?= $badgeColor ?>; border: 1px solid <?= $badgeBorder ?>; padding: 4px 10px; border-radius: 20px; display: inline-flex; align-items: center; gap: 5px;">
                                        <i class="bi <?= $badgeIcon ?>"></i> <?= htmlspecialchars($action) ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 16px; color: #1E293B; line-height: 1.4; font-weight: 500;">
                                    <?= htmlspecialchars($log['description']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div style="padding: 16px 24px; background: #F8FAFC; border-top: 1px solid #E2E8F0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                <div style="font-size: 13px; color: #64748B;">
                    Halaman <strong><?= $pagination['current_page'] ?></strong> dari <strong><?= $pagination['total_pages'] ?></strong>
                </div>
                <div style="display: flex; gap: 6px;">
                    <?php 
                    $queryParams = $filters;
                    $buildUrl = function($p) use ($queryParams) {
                        $queryParams['page'] = $p;
                        return BASE_URL . '/activity-logs?' . http_build_query($queryParams);
                    };
                    ?>
                    <?php if ($pagination['current_page'] > 1): ?>
                        <a href="<?= $buildUrl($pagination['current_page'] - 1) ?>" class="btn-btn btn-btn-secondary" style="padding: 6px 12px; font-size: 12.5px;">&laquo; Prev</a>
                    <?php endif; ?>

                    <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
                        <a href="<?= $buildUrl($p) ?>" class="btn-btn <?= $p === $pagination['current_page'] ? 'btn-btn-primary' : 'btn-btn-secondary' ?>" style="padding: 6px 12px; font-size: 12.5px; <?= $p === $pagination['current_page'] ? 'background:#059669; border-color:#059669;' : '' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                        <a href="<?= $buildUrl($pagination['current_page'] + 1) ?>" class="btn-btn btn-btn-secondary" style="padding: 6px 12px; font-size: 12.5px;">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Clear Logs Modal Confirmation -->
<div id="clearLogsModal" style="display: none; position: fixed; inset: 0; z-index: 99999; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #FFFFFF; width: 100%; max-width: 450px; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3); padding: 24px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
            <i class="bi bi-exclamation-triangle-fill" style="color: #DC2626; font-size: 24px;"></i>
            <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #991B1B;">Bersihkan Log Aktivitas?</h3>
        </div>

        <p style="font-size: 13.5px; color: #64748B; margin-bottom: 20px; line-height: 1.5;">
            Apakah Anda yakin ingin menghapus <strong>seluruh data Log Aktivitas Sistem</strong>? Tindakan ini bersifat permanen dan tidak dapat dibatalkan.
        </p>

        <form action="<?= BASE_URL ?>/activity-logs/clear" method="POST" style="display: flex; justify-content: flex-end; gap: 12px;">
            <?= Session::csrfField() ?>
            <button type="button" onclick="closeClearLogsModal()" class="btn-btn btn-btn-secondary">
                Batal
            </button>
            <button type="submit" class="btn-btn" style="background: #DC2626; color: #FFFFFF; font-weight: 700; padding: 10px 20px; border: none; display: inline-flex; align-items: center; gap: 6px;">
                <i class="bi bi-trash3-fill"></i> Ya, Bersihkan Log
            </button>
        </form>
    </div>
</div>

<script>
function openClearLogsModal() {
    document.body.style.overflow = 'hidden';
    document.getElementById('clearLogsModal').style.display = 'flex';
}

function closeClearLogsModal() {
    document.body.style.overflow = '';
    document.getElementById('clearLogsModal').style.display = 'none';
}
</script>
