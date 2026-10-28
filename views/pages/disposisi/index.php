<style>
    .disposisi-header-card {
        background: linear-gradient(135deg, #0284C7 0%, #0369A1 100%);
        padding: 32px 24px;
        border-radius: 20px;
        color: #FFFFFF;
        text-align: center;
        margin-bottom: 28px;
        box-shadow: 0 10px 30px rgba(2, 132, 199, 0.2);
    }
    .disposisi-badge-strip {
        color: #E0F2FE;
        font-weight: 700;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }
    .disposisi-title {
        font-size: 26px;
        font-weight: 800;
        margin: 4px 0 8px 0;
        color: #FFFFFF;
    }
    .disposisi-subtitle {
        font-size: 14.5px;
        color: #E0F2FE;
        font-weight: 500;
        margin: 0 auto;
        max-width: 650px;
    }
    .disposisi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 22px;
        margin-bottom: 28px;
        width: 100%;
        box-sizing: border-box;
    }
    .disposisi-card {
        padding: 24px;
        background: #FFFFFF;
        border-radius: 18px;
        border: 1.5px solid #E2E8F0;
        box-shadow: 0 4px 16px rgba(0,0,0,0.03);
        transition: all 0.25s ease;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
        box-sizing: border-box;
        cursor: pointer;
    }
    .disposisi-card:hover {
        transform: translateY(-4px);
        border-color: #0284C7;
        box-shadow: 0 12px 24px -8px rgba(2, 132, 199, 0.2);
    }
    .disposisi-card-title {
        font-size: 16.5px;
        font-weight: 800;
        color: #0F172A;
        margin: 0 0 12px 0;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .disposisi-meta-list {
        font-size: 12.5px;
        color: #475569;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .disposisi-card-footer {
        margin-top: 18px;
        padding-top: 12px;
        border-top: 1px dashed #E2E8F0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12.5px;
        font-weight: 700;
        color: #0284C7;
    }

    @media screen and (max-width: 768px) {
        .disposisi-header-card {
            padding: 22px 18px;
            border-radius: 16px;
            margin-bottom: 20px;
        }
        .disposisi-title {
            font-size: 22px;
        }
        .disposisi-subtitle {
            font-size: 13.5px;
        }
        .disposisi-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }
        .disposisi-card {
            padding: 18px 16px;
            border-radius: 16px;
        }
    }

    @media screen and (max-width: 576px) {
        .disposisi-header-card {
            padding: 18px 14px;
            border-radius: 14px;
            margin-bottom: 16px;
        }
        .disposisi-badge-strip {
            font-size: 11px;
            letter-spacing: 0.3px;
        }
        .disposisi-title {
            font-size: 19px;
            margin: 2px 0 6px 0;
        }
        .disposisi-subtitle {
            font-size: 12.5px;
            line-height: 1.45;
        }
        .disposisi-grid {
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }
        .disposisi-card {
            padding: 16px 14px;
            border-radius: 14px;
        }
        .disposisi-card-title {
            font-size: 15px;
            margin-bottom: 10px;
            line-height: 1.35;
            -webkit-line-clamp: 2;
        }
        .disposisi-meta-list {
            font-size: 12px;
            gap: 5px;
        }
        .disposisi-card-footer {
            margin-top: 14px;
            padding-top: 10px;
            font-size: 12px;
        }
    }
</style>

<!-- Centered Executive Header Card for Disposisi Page -->
<div class="disposisi-header-card executive-welcome-card-centered hero-theme-purple">
    <div class="disposisi-badge-strip welcome-badge-strip-centered">
        <i class="bi bi-file-earmark-arrow-down-fill"></i> MODUL DISPOSISI SURAT &amp; INSTRUKSI KERJA
    </div>
    <h1 class="disposisi-title executive-title-centered">
        Halaman Disposisi
    </h1>
    <p class="disposisi-subtitle executive-subtitle-centered">
        Selamat Datang, <strong><?= htmlspecialchars($user['name'] ?? 'User') ?></strong> (<?= htmlspecialchars($user['jabatan'] ?? 'Unit Kerja') ?>)
    </p>
</div>

<?php if (empty($disposisiList)): ?>
    <!-- Main Clean Empty State Work Container -->
    <div class="card-panel-variant" style="padding: 50px 20px; text-align: center; background: #FFFFFF; border-radius: 20px; border: 1.5px dashed #CBD5E1; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); margin-bottom: 28px;">
        <div style="width: 72px; height: 72px; margin: 0 auto 18px auto; border-radius: 50%; background: #F0F9FF; border: 2px solid #BAE6FD; color: #0284C7; display: flex; align-items: center; justify-content: center; font-size: 32px; box-shadow: 0 8px 24px rgba(2, 132, 199, 0.12);">
            <i class="bi bi-inbox-fill"></i>
        </div>
        <h3 style="font-size: 19px; font-weight: 800; color: #0F172A; margin: 0 0 8px 0;">
            Belum Ada Lembar Disposisi Aktif
        </h3>
        <p style="font-size: 13.5px; color: #64748B; max-width: 480px; margin: 0 auto; line-height: 1.6;">
            Halaman disposisi untuk unit <strong><?= htmlspecialchars($user['jabatan'] ?? 'Unit Kerja') ?></strong> saat ini masih dalam keadaan kosong. Lembar disposisi yang diteruskan dari Kepala Stasiun akan otomatis tampil di halaman ini.
        </p>
    </div>
<?php else: ?>
    <!-- Disposisi Letter Cards Grid -->
    <div class="disposisi-grid">
        <?php foreach ($disposisiList as $d): ?>
            <?php
            $sMeta = !empty($d['meta']) && is_array($d['meta']) ? $d['meta'] : [];
            $jenisLabel = Surat::getTypeLabel($d['type'] ?? '', $sMeta);
            $targetUrl = BASE_URL . '/surat/disposisi-process/' . $d['id'];
            ?>
            <a href="<?= $targetUrl ?>" style="text-decoration: none; color: inherit; display: block;">
                <div class="disposisi-card card-panel-variant">
                    <div>
                        <!-- Perihal Title -->
                        <h3 class="disposisi-card-title">
                            <?= htmlspecialchars($d['perihal'] ?? 'Surat Disposisi') ?>
                        </h3>

                        <!-- Metadata Rows -->
                        <div class="disposisi-meta-list">
                            <div><i class="bi bi-tag-fill" style="color: #3B82F6;"></i> Jenis: <strong><?= htmlspecialchars($jenisLabel) ?></strong></div>
                            <div><i class="bi bi-calendar-event" style="color: #0284C7;"></i> Tanggal Surat: <strong><?= date('d F Y', strtotime($d['tanggal_surat'])) ?></strong></div>
                            <?php if (!empty($d['kepsta_signed_at'])): ?>
                                <div><i class="bi bi-clock-history" style="color: #16A34A;"></i> Disposisi Kepsta: <strong><?= date('d/m/Y H:i', strtotime($d['kepsta_signed_at'])) ?></strong></div>
                            <?php endif; ?>
                            <div><i class="bi bi-person-badge" style="color: #7E22CE;"></i> Dari Kepsta: <strong><?= htmlspecialchars($d['kepsta_nama'] ?? 'Akbar Sahidi, S.Sos., M.Si') ?></strong></div>
                        </div>
                    </div>

                    <!-- Card Action Footer -->
                    <div class="disposisi-card-footer">
                        <span><i class="bi bi-eye-fill"></i> Tinjau &amp; Cetak Disposisi</span>
                        <i class="bi bi-chevron-right"></i>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
