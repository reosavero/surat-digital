<?php
$daysIndo = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
$monthsIndo = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
?>

<div style="max-width: 1360px; margin: 0 auto; padding-bottom: 40px;">
    
    <!-- Top Action & Navigation Header -->
    <div class="page-header-banner" style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div>
            <?php if ($level === 'months'): ?>
                <a href="<?= BASE_URL ?>/surat/history" class="btn-btn btn-btn-secondary" style="margin-bottom: 12px; display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; font-size: 13.5px; font-weight: 700;">
                    <i class="bi bi-arrow-left"></i> Kembali ke Pilih Tahun
                </a>
            <?php elseif ($level === 'letters'): ?>
                <a href="<?= BASE_URL ?>/surat/history?year=<?= $selectedYear ?>" class="btn-btn btn-btn-secondary" style="margin-bottom: 12px; display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; font-size: 13.5px; font-weight: 700;">
                    <i class="bi bi-arrow-left"></i> Kembali ke Pilih Bulan (Tahun <?= $selectedYear ?>)
                </a>
            <?php endif; ?>

            <h1 style="font-size: 24px; font-weight: 800; color: #0F172A; margin: 0; display: flex; align-items: center; gap: 10px;">
                <i class="bi bi-clock-history" style="color: #6366F1;"></i> 
                <?php if ($level === 'years'): ?>
                    History Disposisi Surat Digital
                <?php elseif ($level === 'months'): ?>
                    History Disposisi - Tahun <?= $selectedYear ?>
                <?php else: ?>
                    History Disposisi - <?= htmlspecialchars($monthName) ?> <?= $selectedYear ?>
                <?php endif; ?>
            </h1>

            <p style="font-size: 14px; color: #64748B; margin-top: 4px; margin-bottom: 0;">
                <?php if ($level === 'years'): ?>
                    Pilih tahun untuk membuka arsip riwayat disposisi surat yang telah disetujui Kepala Stasiun.
                <?php elseif ($level === 'months'): ?>
                    Pilih bulan pada tahun <strong><?= $selectedYear ?></strong> untuk melihat daftar surat disposisi yang telah diselesaikan.
                <?php else: ?>
                    Menampilkan riwayat surat disposisi selesai pada bulan <strong><?= htmlspecialchars($monthName) ?> <?= $selectedYear ?></strong>, dikelompokkan berdasarkan tanggal.
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- LEVEL 1: YEARS LIST -->
    <?php if ($level === 'years'): ?>
        <?php if (empty($yearsList)): ?>
            <div style="background: #FFFFFF; border-radius: 16px; padding: 48px; text-align: center; border: 1px solid #E2E8F0; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                <i class="bi bi-inbox-fill" style="font-size: 42px; color: #CBD5E1; display: block; margin-bottom: 12px;"></i>
                <h3 style="font-size: 16px; font-weight: 700; color: #334155; margin-bottom: 4px;">Belum Ada Riwayat History Surat</h3>
                <p style="font-size: 13.5px; color: #64748B; margin: 0;">Surat yang telah diselesaikan dan disimpan disposisinya oleh Kepala Stasiun akan tampil di sini.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: flex-start;">
                <?php foreach ($yearsList as $y): ?>
                    <a href="<?= BASE_URL ?>/surat/history?year=<?= $y['year'] ?>" style="width: 360px; max-width: 100%; text-decoration: none; color: inherit; display: block;">
                        <div class="card-panel-variant" style="padding: 24px; border-radius: 18px; background: #FFFFFF; border: 1.5px solid #E2E8F0; transition: all 0.25s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.03); display: flex; flex-direction: column; justify-content: space-between; height: 100%; cursor: pointer;"
                             onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='#6366F1'; this.style.boxShadow='0 12px 24px -8px rgba(99, 102, 241, 0.25)';"
                             onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='#E2E8F0'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.03)';">
                            <div>
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                                    <div style="width: 52px; height: 52px; border-radius: 14px; background: #EEF2FF; color: #4338CA; display: flex; align-items: center; justify-content: center; font-size: 26px;">
                                        <i class="bi bi-calendar-range-fill"></i>
                                    </div>
                                    <span style="font-size: 12px; font-weight: 800; background: #EEF2FF; color: #4338CA; padding: 4px 12px; border-radius: 20px; border: 1px solid #C7D2FE;">
                                        <?= $y['total'] ?> Surat Disposisi
                                    </span>
                                </div>

                                <h3 style="font-size: 22px; font-weight: 800; color: #0F172A; margin: 0 0 6px 0;">
                                    Tahun <?= $y['year'] ?>
                                </h3>
                                <p style="font-size: 13.5px; color: #64748B; margin: 0; line-height: 1.4;">
                                    Arsip riwayat disposisi surat selesai untuk tahun <?= $y['year'] ?>.
                                </p>
                            </div>

                            <div style="margin-top: 20px; padding-top: 14px; border-top: 1px dashed #E2E8F0; display: flex; align-items: center; justify-content: space-between;">
                                <span style="font-size: 13.5px; font-weight: 700; color: #4338CA; display: inline-flex; align-items: center; gap: 6px;">
                                    Pilih Bulan <i class="bi bi-arrow-right"></i>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <!-- LEVEL 2: MONTHS LIST -->
    <?php elseif ($level === 'months'): ?>
        <?php if (empty($monthsList)): ?>
            <div style="background: #FFFFFF; border-radius: 16px; padding: 48px; text-align: center; border: 1px solid #E2E8F0; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                <i class="bi bi-inbox-fill" style="font-size: 42px; color: #CBD5E1; display: block; margin-bottom: 12px;"></i>
                <h3 style="font-size: 16px; font-weight: 700; color: #334155; margin-bottom: 4px;">Tidak Ada Riwayat Surat di Tahun <?= $selectedYear ?></h3>
                <p style="font-size: 13.5px; color: #64748B; margin: 0;">Silakan pilih tahun lain pada halaman sebelumnya.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: flex-start;">
                <?php foreach ($monthsList as $m): ?>
                    <?php $mName = $monthsIndo[(int)$m['month_num']] ?? 'Bulan ' . $m['month_num']; ?>
                    <a href="<?= BASE_URL ?>/surat/history?year=<?= $selectedYear ?>&month=<?= $m['month_num'] ?>" style="width: 360px; max-width: 100%; text-decoration: none; color: inherit; display: block;">
                        <div class="card-panel-variant" style="padding: 24px; border-radius: 18px; background: #FFFFFF; border: 1.5px solid #E2E8F0; transition: all 0.25s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.03); display: flex; flex-direction: column; justify-content: space-between; height: 100%; cursor: pointer;"
                             onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='#059669'; this.style.boxShadow='0 12px 24px -8px rgba(5, 150, 105, 0.2)';"
                             onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='#E2E8F0'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.03)';">
                            <div>
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                                    <div style="width: 52px; height: 52px; border-radius: 14px; background: #ECFDF5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 26px;">
                                        <i class="bi bi-calendar3-event-fill"></i>
                                    </div>
                                    <span style="font-size: 12px; font-weight: 800; background: #ECFDF5; color: #047857; padding: 4px 12px; border-radius: 20px; border: 1px solid #A7F3D0;">
                                        <?= $m['total'] ?> Surat Selesai
                                    </span>
                                </div>

                                <h3 style="font-size: 20px; font-weight: 800; color: #0F172A; margin: 0 0 6px 0;">
                                    <?= $mName ?> <?= $selectedYear ?>
                                </h3>
                                <p style="font-size: 13.5px; color: #64748B; margin: 0; line-height: 1.4;">
                                    Daftar disposisi surat selesai untuk bulan <?= $mName ?>.
                                </p>
                            </div>

                            <div style="margin-top: 20px; padding-top: 14px; border-top: 1px dashed #E2E8F0; display: flex; align-items: center; justify-content: space-between;">
                                <span style="font-size: 13.5px; font-weight: 700; color: #059669; display: inline-flex; align-items: center; gap: 6px;">
                                    Lihat List Surat <i class="bi bi-arrow-right"></i>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <!-- LEVEL 3: LETTERS LIST GROUPED BY DATE CONTAINER (EXACT MATCH TO ATTACHED IMAGE) -->
    <?php elseif ($level === 'letters'): ?>
        <?php if (empty($suratList)): ?>
            <div style="background: #FFFFFF; border-radius: 16px; padding: 48px; text-align: center; border: 1px solid #E2E8F0; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                <i class="bi bi-inbox-fill" style="font-size: 42px; color: #CBD5E1; display: block; margin-bottom: 12px;"></i>
                <h3 style="font-size: 16px; font-weight: 700; color: #334155; margin-bottom: 4px;">Tidak Ada Surat pada Bulan <?= htmlspecialchars($monthName) ?> <?= $selectedYear ?></h3>
                <p style="font-size: 13.5px; color: #64748B; margin: 0;">Silakan pilih bulan lain pada halaman sebelumnya.</p>
            </div>
        <?php else: ?>
            <?php 
            // Group letters by date string Y-m-d
            $groupedByDate = [];
            foreach ($suratList as $s) {
                $dateKey = !empty($s['tanggal_surat']) ? date('Y-m-d', strtotime($s['tanggal_surat'])) : date('Y-m-d');
                $groupedByDate[$dateKey][] = $s;
            }
            ?>

            <?php foreach ($groupedByDate as $dateKey => $lettersOnDate): ?>
                <?php 
                $dateTimestamp = strtotime($dateKey);
                $dayNum = date('d', $dateTimestamp);
                $dayName = $daysIndo[date('l', $dateTimestamp)] ?? date('l', $dateTimestamp);
                $fullDateStr = date('d/m/Y', $dateTimestamp);
                ?>
                <!-- CONTAINER WRAPPER PER TANGGAL (1:1 UI MATCHING PHOTO) -->
                <div class="card-panel-variant" style="background: #FFFFFF; border: 1.5px solid #E2E8F0; border-radius: 18px; padding: 24px; margin-bottom: 28px; box-shadow: 0 4px 16px rgba(0,0,0,0.03);">
                    
                    <!-- Header Bar Tanggal per Kelompok -->
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; border-bottom: 1.5px solid #F1F5F9; padding-bottom: 16px; margin-bottom: 20px;">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <div style="width: 44px; height: 44px; background: #2563EB; color: #FFFFFF; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 19px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);">
                                <?= $dayNum ?>
                            </div>
                            <div>
                                <h2 style="margin: 0; font-size: 18px; font-weight: 800; color: #0F172A;">
                                    <?= $dayName ?> (<?= $fullDateStr ?>)
                                </h2>
                            </div>
                        </div>

                        <div style="font-size: 13px; font-weight: 700; color: #64748B; background: #F8FAFC; padding: 6px 14px; border-radius: 20px; border: 1px solid #E2E8F0;">
                            Surat Disposisi: <span style="color: #2563EB; font-weight: 800; font-size: 14px;"><?= count($lettersOnDate) ?></span>
                        </div>
                    </div>

                    <!-- Inner Grid Card Surat -->
                    <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: flex-start;">
                        <?php foreach ($lettersOnDate as $s): ?>
                            <a href="<?= BASE_URL ?>/surat/detail/<?= $s['id'] ?>" style="width: 360px; max-width: 100%; text-decoration: none; color: inherit; display: block;">
                                <div class="card-panel-variant" style="padding: 22px; border-radius: 16px; background: #FFFFFF; border: 1.5px solid #E2E8F0; transition: all 0.25s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.03); display: flex; flex-direction: column; justify-content: space-between; height: 100%; cursor: pointer;"
                                     onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='#2563EB'; this.style.boxShadow='0 12px 24px -8px rgba(37, 99, 235, 0.2)';"
                                     onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='#E2E8F0'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.03)';">
                                    <div>
                                        <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin-bottom: 10px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                            <?= htmlspecialchars($s['perihal']) ?>
                                        </h3>

                                        <div style="font-size: 13px; color: #475569; margin-bottom: 6px; display: flex; flex-direction: column; gap: 5px;">
                                            <div><i class="bi bi-tag-fill" style="color: #3B82F6;"></i> Jenis: <strong><?= Surat::getTypeLabel($s['type']) ?></strong></div>
                                            <div><i class="bi bi-person-fill" style="color: #64748B;"></i> Pembuat: <strong><?= htmlspecialchars($s['pembuat_nama'] ?? 'Admin') ?></strong></div>
                                            <div><i class="bi bi-calendar-event" style="color: #64748B;"></i> Tanggal: <strong><?= date('d F Y', strtotime($s['tanggal_surat'])) ?></strong></div>
                                            <?php if (!empty($s['gdrive_web_link'])): ?>
                                                <div><i class="bi bi-cloud-check-fill" style="color: #059669;"></i> GDrive: <span style="color: #059669; font-weight: 700;">Tersimpan</span></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid #F1F5F9; display: flex; align-items: center; justify-content: space-between;">
                                        <?= Surat::getStatusBadgeHtml($s['status']) ?>
                                        <span style="font-size: 12px; font-weight: 700; color: #2563EB; display: inline-flex; align-items: center; gap: 4px;">
                                            Lihat Detail <i class="bi bi-arrow-right"></i>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>

</div>
