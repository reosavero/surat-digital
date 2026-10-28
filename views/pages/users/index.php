<!-- Centered Animated Executive Welcome Hero Card (Full Width) -->
<div class="executive-welcome-card-centered hero-theme-purple">
    <div class="welcome-badge-strip-centered" style="color: #E9D5FF;">
        <i class="bi bi-people-fill"></i> MANAJEMEN PENGGUNA & HAK AKSES SISTEM
    </div>
    <h1 class="executive-title-centered" style="font-size: 26px; margin-top: 4px;">
        Kelola Otentikasi &amp; Persetujuan Akun User
    </h1>
    <p class="executive-subtitle-centered" style="font-size: 14.5px; color: #E9D5FF; font-weight: 500; margin-bottom: 18px; max-width: 650px;">
        Panel Superadmin untuk mengonfirmasi pendaftaran akun baru, mengatur struktur pengguna, hak akses, dan divisi kerja.
    </p>

    <div class="centered-actions-bar">
        <button onclick="openCreateUserModal();" class="btn-btn" style="background: #FFFFFF; color: #7E22CE; border: none; font-weight: 800; padding: 10px 24px; border-radius: 50px; box-shadow: 0 4px 16px rgba(0,0,0,0.18); display: inline-flex; align-items: center; gap: 8px; cursor: pointer; transition: transform 0.2s ease;">
            <i class="bi bi-person-plus-fill" style="font-size: 16px;"></i> Tambah Pengguna Baru
        </button>
    </div>
</div>


<?php if (!empty($pendingUsers)): ?>
<!-- ========================================================================= -->
<!-- SECTION 1: PERMINTAAN AKUN BARU MENUNGGU KONFIRMASI (PENDING APPROVALS) -->
<!-- ========================================================================= -->
<div class="card-panel-variant" style="padding: 24px; margin-bottom: 28px; border: 2px solid #FCD34D; background: #FFFFFF; border-radius: 18px; box-shadow: 0 4px 16px rgba(217, 119, 6, 0.08);">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1.5px dashed #FDE68A; flex-wrap: wrap; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 38px; height: 38px; border-radius: 10px; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center; font-size: 18px; border: 1px solid #FCD34D;">
                <i class="bi bi-person-exclamation"></i>
            </div>
            <div>
                <h3 style="font-size: 16.5px; font-weight: 800; color: #92400E; margin: 0;">
                    Pendaftaran Akun Baru Menunggu Konfirmasi
                </h3>
                <span style="font-size: 12.5px; color: #B45309;">Tinjau data pendaftaran petugas di bawah ini lalu tentukan persetujuan (Terima / Tolak).</span>
            </div>
        </div>
        <span style="background: #FEF3C7; color: #92400E; font-size: 12px; font-weight: 800; padding: 4px 12px; border-radius: 20px; border: 1px solid #FCD34D;">
            <?= count($pendingUsers) ?> Permintaan Pendataan
        </span>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr style="background: #FEF3C7; color: #78350F;">
                    <th>Nama &amp; Gelar</th>
                    <th>NIP</th>
                    <th>Bidang</th>
                    <th>Email</th>
                    <th>Tanggal Daftar</th>
                    <th>Status</th>
                    <th style="text-align: center;">Konfirmasi Superadmin</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingUsers as $pu): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 800; color: #1E293B; font-size: 13.5px;"><?= htmlspecialchars($pu['name']) ?></div>
                        </td>
                        <td style="font-weight: 700; color: #005BAC; font-size: 13.5px;">
                            <?= htmlspecialchars($pu['nip'] ?? '-') ?>
                        </td>
                        <td style="font-weight: 600; color: #334155;">
                            <?= htmlspecialchars(!empty($pu['divisi']) ? html_entity_decode($pu['divisi'], ENT_QUOTES, 'UTF-8') : '-') ?>
                        </td>
                        <td style="color: #475569; font-size: 12.5px;">
                            <?= htmlspecialchars($pu['email']) ?>
                        </td>
                        <td style="font-size: 12px; color: #64748B;">
                            <?= date('d/m/Y H:i', strtotime($pu['created_at'])) ?>
                        </td>
                        <td>
                            <span style="background: #FEF3C7; color: #B45309; border: 1px solid #FCD34D; padding: 3px 10px; border-radius: 12px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                <i class="bi bi-clock-history"></i> Menunggu Konfirmasi
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 8px; justify-content: center;">
                                <button type="button" onclick="openApproveModal(<?= $pu['id'] ?>, '<?= htmlspecialchars(addslashes($pu['name'])) ?>', '<?= htmlspecialchars(addslashes($pu['email'])) ?>')" class="btn-btn btn-btn-sm" style="background: #16A34A; color: #FFFFFF; border: none; font-weight: 700; padding: 6px 14px; cursor: pointer;">
                                    <i class="bi bi-check-circle-fill"></i> Terima
                                </button>
                                <button type="button" onclick="openRejectModal(<?= $pu['id'] ?>, '<?= htmlspecialchars(addslashes($pu['name'])) ?>', '<?= htmlspecialchars(addslashes($pu['email'])) ?>')" class="btn-btn btn-btn-sm" style="background: #FEF2F2; color: #DC2626; border: 1px solid #FCA5A5; font-weight: 700; padding: 6px 14px; cursor: pointer;">
                                    <i class="bi bi-x-circle-fill"></i> Tolak
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- SECTION 2: DAFTAR PENGGUNA AKTIF SISTEM -->
<!-- ========================================================================= -->


<div class="card-panel-variant" style="padding: 0; overflow: hidden; border-radius: 18px; border: 1px solid #E2E8F0; box-shadow: 0 4px 20px rgba(0,0,0,0.04); margin-bottom: 28px;">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr style="background: #F8FAFC;">
                    <th style="padding: 14px 20px; font-weight: 700; color: #64748B;">Pengguna &amp; Email</th>
                    <th style="padding: 14px 20px; font-weight: 700; color: #64748B;">NIP</th>
                    <th style="padding: 14px 20px; font-weight: 700; color: #64748B;">Role System</th>
                    <th style="padding: 14px 20px; font-weight: 700; color: #64748B;">Bidang</th>
                    <th style="padding: 14px 20px; text-align: right; font-weight: 700; color: #64748B;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr style="border-bottom: 1px solid #F1F5F9;">
                        <td style="padding: 14px 20px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 38px; height: 38px; border-radius: 10px; background: #FAF5FF; color: #7E22CE; border: 1px solid #E9D5FF; font-weight: 800; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0;">
                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: #0F172A; font-size: 14px;"><?= htmlspecialchars($u['name']) ?></div>
                                    <div style="font-size: 12.5px; color: #64748B; margin-top: 1px;"><?= htmlspecialchars($u['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 14px 20px;">
                            <span style="font-weight: 500; color: #334155; font-size: 13.5px;">
                                <?= htmlspecialchars(!empty($u['nip']) ? $u['nip'] : '-') ?>
                            </span>
                        </td>
                        <td style="padding: 14px 20px;">
                            <?php
                            $roleConfig = match($u['role']) {
                                'admin' => ['bg' => '#EFF6FF', 'color' => '#1D4ED8', 'border' => '#BFDBFE', 'icon' => 'bi-person-badge-fill'],
                                'kpp_bidang', 'kpp' => ['bg' => '#F0F9FF', 'color' => '#0284C7', 'border' => '#BAE6FD', 'icon' => 'bi-pen-fill'],
                                'kapel_bidang', 'kapel' => ['bg' => '#F0FDF4', 'color' => '#15803D', 'border' => '#BBF7D0', 'icon' => 'bi-person-workspace'],
                                'tata_usaha' => ['bg' => '#ECFDF5', 'color' => '#047857', 'border' => '#A7F3D0', 'icon' => 'bi-building-fill-gear'],
                                'sekretaris' => ['bg' => '#FEF3C7', 'color' => '#B45309', 'border' => '#FDE68A', 'icon' => 'bi-journal-check'],
                                'kepala_stasiun' => ['bg' => '#FAF5FF', 'color' => '#7E22CE', 'border' => '#E9D5FF', 'icon' => 'bi-award-fill'],
                                'superadmin' => ['bg' => '#FDF2F8', 'color' => '#BE185D', 'border' => '#FBCFE8', 'icon' => 'bi-shield-lock-fill'],
                                default => ['bg' => '#F1F5F9', 'color' => '#475569', 'border' => '#CBD5E1', 'icon' => 'bi-shield-check']
                            };
                            ?>
                            <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 50px; font-size: 12px; font-weight: 700; background: <?= $roleConfig['bg'] ?>; color: <?= $roleConfig['color'] ?>; border: 1px solid <?= $roleConfig['border'] ?>;">
                                <i class="bi <?= $roleConfig['icon'] ?>"></i> <?= User::getRoleLabel($u['role']) ?>
                            </span>
                        </td>
                        <td style="padding: 14px 20px;">
                            <span style="font-weight: 500; color: #334155; font-size: 13.5px;">
                                <?= htmlspecialchars(!empty($u['divisi']) ? html_entity_decode($u['divisi'], ENT_QUOTES, 'UTF-8') : '-') ?>
                            </span>
                        </td>
                        <td style="padding: 14px 20px; text-align: right;">
                            <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                <button type="button" onclick='openEditUserModal(<?= json_encode($u, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' class="btn-btn btn-btn-sm" style="background: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE; font-weight: 700; padding: 6px 14px; border-radius: 8px; cursor: pointer;">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>
                                <?php if ($u['id'] !== (int)Session::get('user_id')): ?>
                                    <button type="button" onclick="openDeleteModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['name'])) ?>', '<?= htmlspecialchars(addslashes($u['email'])) ?>')" class="btn-btn btn-btn-sm" style="background: #FEF2F2; color: #DC2626; border: 1px solid #FCA5A5; font-weight: 700; padding: 6px 14px; border-radius: 8px; cursor: pointer;">
                                        <i class="bi bi-trash-fill"></i> Hapus
                                    </button>
                                <?php else: ?>
                                    <span style="font-size: 11.5px; color: #64748B; background: #F1F5F9; border: 1px solid #CBD5E1; padding: 4px 10px; border-radius: 20px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="bi bi-person-check-fill" style="color: #2563EB;"></i> (Anda)
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.custom-select-container {
    position: relative;
}
.custom-select-trigger {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 42px;
    padding: 10px 14px;
    background: #F8FAFC;
    border: 1.5px solid #E2E8F0;
    border-radius: 10px;
    font-size: 13.5px;
    color: #1E293B;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    outline: none;
}
.custom-select-trigger:hover {
    border-color: #CBD5E1;
    background: #FFFFFF;
}
.custom-dropdown-menu {
    display: none;
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    width: 100%;
    z-index: 99999;
    background: #FFFFFF;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15), 0 4px 12px rgba(0,0,0,0.06);
    border: 1.5px solid #7E22CE;
    padding: 6px;
}
.custom-dropdown-options-list {
    max-height: 200px;
    overflow-y: auto;
    padding-right: 2px;
}
.custom-option-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 9px 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.15s ease;
    margin-bottom: 2px;
}
.custom-option-item:hover {
    background: #F3F4F6;
}
.custom-option-item.selected {
    background: #FAF5FF;
}
.custom-option-item.selected .option-label {
    color: #7E22CE;
    font-weight: 500;
}
#editUserModal .custom-option-item.selected {
    background: #EFF6FF;
}
#editUserModal .custom-option-item.selected .option-label {
    color: #2563EB;
    font-weight: 500;
}
</style>

<!-- ========================================================================= -->
<!-- MODAL POPUP: CREATE USER (SUPERADMIN) -->
<!-- ========================================================================= -->
<div id="createUserModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.65); backdrop-filter: blur(4px); z-index: 2000; overflow-y: auto; padding: 32px 16px; justify-content: center; align-items: flex-start;">
    <div class="card-panel-variant" style="width: 100%; max-width: 560px; margin: auto; padding: 28px; background: #FFFFFF; border-radius: 18px; box-shadow: 0 20px 50px rgba(0,0,0,0.25); position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #F3F4F6; padding-bottom: 12px;">
            <h3 style="font-size: 17.5px; font-weight: 800; color: #1E293B; margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-person-plus-fill" style="color: #7E22CE;"></i> Tambah Pengguna Baru
            </h3>
            <button onclick="closeCreateUserModal();" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #9CA3AF;">&times;</button>
        </div>

        <form id="createUserForm" action="<?= BASE_URL ?>/users/store" method="POST" autocomplete="off">
            <?= Session::csrfField() ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label class="form-label-custom" style="font-weight: 700; color: #374151;">Nama Lengkap (disertai Gelar)</label>
                    <input type="text" name="name" id="createNameInput" required placeholder="Nama lengkap" class="form-input-custom">
                </div>
                <div>
                    <label class="form-label-custom" style="font-weight: 700; color: #374151;">NIP</label>
                    <input type="text" name="nip" id="createNipInput" required placeholder="1234567890" class="form-input-custom">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label class="form-label-custom" style="font-weight: 700; color: #374151;">Email</label>
                    <input type="email" name="email" id="createEmailInput" required placeholder="email@gmail.com" class="form-input-custom">
                </div>
                <div>
                    <label class="form-label-custom" style="font-weight: 700; color: #374151;">Role Pengguna</label>
                    <?php
                    $singleRoleCounts = $singleRoleCounts ?? ['tata_usaha' => 0, 'kepala_stasiun' => 0];
                    $tuExists = ($singleRoleCounts['tata_usaha'] ?? 0) >= 1;
                    $kepstaExists = ($singleRoleCounts['kepala_stasiun'] ?? 0) >= 1;
                    $kppDivisiList = $kppDivisiList ?? [];
                    $kppAllExist = $kppAllExist ?? false;
                    $kapelDivisiList = $kapelDivisiList ?? [];
                    $kapelAllExist = $kapelAllExist ?? false;
                    ?>
                    <div class="custom-select-container" id="createRoleContainer">
                        <input type="hidden" name="role" id="createRoleHidden" value="">
                        <button type="button" class="custom-select-trigger" id="createRoleTrigger" onclick="toggleCustomDropdown(event, 'createRoleMenu', 'createRoleChevron', 'createRoleTrigger')">
                            <span id="createRoleSelectedText" style="color: #9CA3AF; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">-- Pilih Role Pengguna --</span>
                            <i class="bi bi-chevron-down" id="createRoleChevron" style="font-size: 13px; color: #64748B; transition: transform 0.2s ease;"></i>
                        </button>
                        <div id="createRoleMenu" class="custom-dropdown-menu" style="border-color: #7E22CE;">
                            <div class="custom-dropdown-options-list" id="createRoleOptionsList">
                                <div class="custom-option-item" data-val="admin" onclick="selectCreateRole('admin', 'Admin')">
                                    <span class="option-label" style="font-size: 13px; font-weight: 600; color: #1F2937;">Admin</span>
                                    <i class="bi bi-check2 check-icon" style="display: none; color: #7E22CE; font-size: 16px; font-weight: 800;"></i>
                                </div>
                                <?php if (!$kppAllExist): ?>
                                <div class="custom-option-item" data-val="kpp_bidang" onclick="selectCreateRole('kpp_bidang', 'KPP Bidang')">
                                    <span class="option-label" style="font-size: 13px; font-weight: 600; color: #1F2937;">KPP Bidang</span>
                                    <i class="bi bi-check2 check-icon" style="display: none; color: #7E22CE; font-size: 16px; font-weight: 800;"></i>
                                </div>
                                <?php endif; ?>
                                <?php if (!$kapelAllExist): ?>
                                <div class="custom-option-item" data-val="kapel_bidang" onclick="selectCreateRole('kapel_bidang', 'Kapel Bidang')">
                                    <span class="option-label" style="font-size: 13px; font-weight: 600; color: #1F2937;">Kapel Bidang</span>
                                    <i class="bi bi-check2 check-icon" style="display: none; color: #7E22CE; font-size: 16px; font-weight: 800;"></i>
                                </div>
                                <?php endif; ?>
                                <?php if (!$tuExists): ?>
                                <div class="custom-option-item" data-val="tata_usaha" onclick="selectCreateRole('tata_usaha', 'Tata Usaha')">
                                    <span class="option-label" style="font-size: 13px; font-weight: 600; color: #1F2937;">Tata Usaha</span>
                                    <i class="bi bi-check2 check-icon" style="display: none; color: #7E22CE; font-size: 16px; font-weight: 800;"></i>
                                </div>
                                <?php endif; ?>
                                <div class="custom-option-item" data-val="sekretaris" onclick="selectCreateRole('sekretaris', 'Sekretaris')">
                                    <span class="option-label" style="font-size: 13px; font-weight: 600; color: #1F2937;">Sekretaris</span>
                                    <i class="bi bi-check2 check-icon" style="display: none; color: #7E22CE; font-size: 16px; font-weight: 800;"></i>
                                </div>
                                <?php if (!$kepstaExists): ?>
                                <div class="custom-option-item" data-val="kepala_stasiun" onclick="selectCreateRole('kepala_stasiun', 'Kepala Stasiun')">
                                    <span class="option-label" style="font-size: 13px; font-weight: 600; color: #1F2937;">Kepala Stasiun</span>
                                    <i class="bi bi-check2 check-icon" style="display: none; color: #7E22CE; font-size: 16px; font-weight: 800;"></i>
                                </div>
                                <?php endif; ?>
                                <div class="custom-option-item" data-val="superadmin" onclick="selectCreateRole('superadmin', 'Superadmin')">
                                    <span class="option-label" style="font-size: 13px; font-weight: 600; color: #1F2937;">Superadmin</span>
                                    <i class="bi bi-check2 check-icon" style="display: none; color: #7E22CE; font-size: 16px; font-weight: 800;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="bidangInputRow" style="display: none; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label class="form-label-custom" style="font-weight: 700; color: #374151;">Bidang</label>
                    <div class="custom-select-container" id="createBidangContainer">
                        <input type="hidden" name="divisi" id="createBidangHidden" value="">
                        <button type="button" class="custom-select-trigger" id="createBidangTrigger" onclick="toggleCustomDropdown(event, 'createBidangMenu', 'createBidangChevron', 'createBidangTrigger')">
                            <span id="createBidangSelectedText" style="color: #9CA3AF; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">-- Pilih Bidang --</span>
                            <i class="bi bi-chevron-down" id="createBidangChevron" style="font-size: 13px; color: #64748B; transition: transform 0.2s ease;"></i>
                        </button>
                        <div id="createBidangMenu" class="custom-dropdown-menu" style="border-color: #7E22CE;">
                            <div class="custom-dropdown-options-list" id="createBidangOptionsList">
                                <!-- Populated dynamically by JS -->
                            </div>
                        </div>
                    </div>
                </div>
                <div></div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 24px;">
                <div>
                    <label class="form-label-custom" style="font-weight: 700; color: #374151;">Password Akun</label>
                    <input type="password" name="password" required placeholder="Minimal 6 karakter" class="form-input-custom">
                </div>
                <div>
                    <label class="form-label-custom" style="font-weight: 700; color: #374151;">Konfirmasi Password</label>
                    <input type="password" name="confirm_password" required placeholder="Ulangi password" class="form-input-custom">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeCreateUserModal();" class="btn-btn btn-btn-secondary">Batal</button>
                <button type="submit" class="btn-btn" style="background: #7E22CE; color: #FFFFFF; border: none; font-weight: 700; padding: 10px 20px;">
                    <i class="bi bi-person-check-fill"></i> Simpan User Baru
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL POPUP: EDIT USER (SUPERADMIN) -->
<!-- ========================================================================= -->
<div id="editUserModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.65); backdrop-filter: blur(4px); z-index: 2000; overflow-y: auto; padding: 32px 16px; justify-content: center; align-items: flex-start;">
    <div class="card-panel-variant" style="width: 100%; max-width: 540px; margin: auto; padding: 28px; background: #FFFFFF; border-radius: 18px; box-shadow: 0 20px 50px rgba(0,0,0,0.25); position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #F3F4F6; padding-bottom: 12px;">
            <h3 style="font-size: 17.5px; font-weight: 800; color: #1E293B; margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-pencil-square" style="color: #2563EB;"></i> Edit Data Pengguna
            </h3>
            <button onclick="closeEditUserModal();" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #9CA3AF;">&times;</button>
        </div>

        <form id="editUserForm" action="" method="POST" autocomplete="off">
            <?= Session::csrfField() ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label class="form-label-custom" style="font-weight: 700; color: #374151;">Nama Lengkap (disertai Gelar)</label>
                    <input type="text" name="name" id="editNameInput" required placeholder="Nama lengkap" class="form-input-custom">
                </div>
                <div>
                    <label class="form-label-custom" style="font-weight: 700; color: #374151;">NIP</label>
                    <input type="text" name="nip" id="editNipInput" required placeholder="1234567890" class="form-input-custom">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label class="form-label-custom" style="font-weight: 700; color: #374151;">Email</label>
                    <input type="email" name="email" id="editEmailInput" required placeholder="email@gmail.com" class="form-input-custom">
                </div>
                <div>
                    <label class="form-label-custom" style="font-weight: 700; color: #374151;">Role Pengguna</label>
                    <div class="custom-select-container" id="editRoleContainer">
                        <input type="hidden" name="role" id="editRoleHidden" value="admin">
                        <button type="button" class="custom-select-trigger" id="editRoleTrigger" onclick="toggleCustomDropdown(event, 'editRoleMenu', 'editRoleChevron', 'editRoleTrigger')">
                            <span id="editRoleSelectedText" style="color: #1F2937; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 400;">Admin</span>
                            <i class="bi bi-chevron-down" id="editRoleChevron" style="font-size: 13px; color: #64748B; transition: transform 0.2s ease;"></i>
                        </button>
                        <div id="editRoleMenu" class="custom-dropdown-menu" style="border-color: #2563EB;">
                            <div class="custom-dropdown-options-list" id="editRoleOptionsList">
                                <div class="custom-option-item" data-val="admin" onclick="selectEditRole('admin', 'Admin')">
                                    <span class="option-label" style="font-size: 13px; font-weight: 600; color: #1F2937;">Admin</span>
                                    <i class="bi bi-check2 check-icon" style="display: none; color: #2563EB; font-size: 16px; font-weight: 800;"></i>
                                </div>
                                <div class="custom-option-item" data-val="kpp_bidang" onclick="selectEditRole('kpp_bidang', 'KPP Bidang')">
                                    <span class="option-label" style="font-size: 13px; font-weight: 600; color: #1F2937;">KPP Bidang</span>
                                    <i class="bi bi-check2 check-icon" style="display: none; color: #2563EB; font-size: 16px; font-weight: 800;"></i>
                                </div>
                                <div class="custom-option-item" data-val="kapel_bidang" onclick="selectEditRole('kapel_bidang', 'Kapel Bidang')">
                                    <span class="option-label" style="font-size: 13px; font-weight: 600; color: #1F2937;">Kapel Bidang</span>
                                    <i class="bi bi-check2 check-icon" style="display: none; color: #2563EB; font-size: 16px; font-weight: 800;"></i>
                                </div>
                                <div class="custom-option-item" data-val="tata_usaha" onclick="selectEditRole('tata_usaha', 'Tata Usaha')">
                                    <span class="option-label" style="font-size: 13px; font-weight: 600; color: #1F2937;">Tata Usaha</span>
                                    <i class="bi bi-check2 check-icon" style="display: none; color: #2563EB; font-size: 16px; font-weight: 800;"></i>
                                </div>
                                <div class="custom-option-item" data-val="sekretaris" onclick="selectEditRole('sekretaris', 'Sekretaris')">
                                    <span class="option-label" style="font-size: 13px; font-weight: 600; color: #1F2937;">Sekretaris</span>
                                    <i class="bi bi-check2 check-icon" style="display: none; color: #2563EB; font-size: 16px; font-weight: 800;"></i>
                                </div>
                                <div class="custom-option-item" data-val="kepala_stasiun" onclick="selectEditRole('kepala_stasiun', 'Kepala Stasiun')">
                                    <span class="option-label" style="font-size: 13px; font-weight: 600; color: #1F2937;">Kepala Stasiun</span>
                                    <i class="bi bi-check2 check-icon" style="display: none; color: #2563EB; font-size: 16px; font-weight: 800;"></i>
                                </div>
                                <div class="custom-option-item" data-val="superadmin" onclick="selectEditRole('superadmin', 'Superadmin')">
                                    <span class="option-label" style="font-size: 13px; font-weight: 600; color: #1F2937;">Superadmin</span>
                                    <i class="bi bi-check2 check-icon" style="display: none; color: #2563EB; font-size: 16px; font-weight: 800;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="editBidangInputRow" style="display: none; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label class="form-label-custom" style="font-weight: 700; color: #374151;">Bidang</label>
                    <div class="custom-select-container" id="editBidangContainer">
                        <input type="hidden" name="divisi" id="editBidangHidden" value="">
                        <button type="button" class="custom-select-trigger" id="editBidangTrigger" onclick="toggleCustomDropdown(event, 'editBidangMenu', 'editBidangChevron', 'editBidangTrigger')">
                            <span id="editBidangSelectedText" style="color: #9CA3AF; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">-- Pilih Bidang --</span>
                            <i class="bi bi-chevron-down" id="editBidangChevron" style="font-size: 13px; color: #64748B; transition: transform 0.2s ease;"></i>
                        </button>
                        <div id="editBidangMenu" class="custom-dropdown-menu" style="border-color: #2563EB;">
                            <div class="custom-dropdown-options-list" id="editBidangOptionsList">
                                <!-- Populated dynamically by JS -->
                            </div>
                        </div>
                    </div>
                </div>
                <div></div>
            </div>

            <div style="margin-bottom: 24px;">
                <label class="form-label-custom" style="font-weight: 700; color: #374151;">Password Baru <span style="font-weight: 400; color: #6B7280; font-size: 12px;">(Kosongkan jika tidak diubah)</span></label>
                <input type="password" name="password" placeholder="Minimal 6 karakter" class="form-input-custom">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeEditUserModal();" class="btn-btn btn-btn-secondary">Batal</button>
                <button type="submit" class="btn-btn" style="background: #2563EB; color: #FFFFFF; border: none; font-weight: 700; padding: 10px 20px;">
                    <i class="bi bi-check-circle-fill"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL POPUP: CONFIRM APPROVAL (TERIMA AKUN PENGGUNA) -->
<!-- ========================================================================= -->
<div id="approveUserModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.65); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center; padding: 20px;">
    <div class="card-panel-variant" style="width: 100%; max-width: 480px; margin: 0; padding: 28px; background: #FFFFFF; border-radius: 18px; box-shadow: 0 20px 50px rgba(0,0,0,0.25);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid #F3F4F6; padding-bottom: 12px;">
            <h3 style="font-size: 17px; font-weight: 800; color: #15803D; margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-check-circle-fill" style="color: #16A34A;"></i> Disetujui / Terima Akun
            </h3>
            <button onclick="closeApproveModal()" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #9CA3AF;">&times;</button>
        </div>

        <div style="margin-bottom: 16px; background: #F0FDF4; padding: 14px; border-radius: 10px; border: 1px solid #BBF7D0;">
            <div style="font-size: 13.5px; color: #166534; font-weight: 700;">Pengguna: <span id="approveUserName" style="color: #1E293B;">-</span></div>
            <div style="font-size: 12.5px; color: #15803D;" id="approveUserEmail">-</div>
        </div>

        <p style="font-size: 13.5px; color: #475569; line-height: 1.5; margin: 0 0 20px 0;">
            Apakah Anda yakin ingin <strong>menyetujui / menerima</strong> pendaftaran akun ini? Email persetujuan beserta link akses masuk ke sistem akan otomatis dikirimkan ke Gmail pengguna.
        </p>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" onclick="closeApproveModal()" class="btn-btn btn-btn-secondary">Batal</button>
            <a id="btnConfirmApprove" href="" class="btn-btn" style="background: #16A34A; color: #FFFFFF; border: none; font-weight: 700; padding: 10px 22px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <i class="bi bi-check-lg"></i> Terima
            </a>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL POPUP: REASON FOR REJECTION (BERI ALASAN PENOLAKAN) -->
<!-- ========================================================================= -->
<div id="rejectUserModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.65); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center; padding: 20px;">
    <div class="card-panel-variant" style="width: 100%; max-width: 500px; margin: 0; padding: 28px; background: #FFFFFF; border-radius: 18px; box-shadow: 0 20px 50px rgba(0,0,0,0.25);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid #F3F4F6; padding-bottom: 12px;">
            <h3 style="font-size: 17px; font-weight: 800; color: #991B1B; margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-exclamation-triangle-fill" style="color: #DC2626;"></i> Alasan Penolakan Akun
            </h3>
            <button onclick="closeRejectModal()" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #9CA3AF;">&times;</button>
        </div>

        <form id="rejectUserForm" action="" method="POST" autocomplete="off">
            <?= Session::csrfField() ?>
            
            <div style="margin-bottom: 16px; background: #FEF2F2; padding: 12px 14px; border-radius: 10px; border: 1px solid #FCA5A5;">
                <div style="font-size: 13px; color: #7F1D1D; font-weight: 700;">Pengguna: <span id="rejectUserName" style="color: #1E293B;">-</span></div>
                <div style="font-size: 12px; color: #991B1B;" id="rejectUserEmail">-</div>
            </div>

            <div class="form-group-block" style="margin-bottom: 20px;">
                <label class="form-label-custom" style="font-weight: 700; color: #374151;">Alasan Penolakan <span style="color: #DC2626;">*</span></label>
                <textarea name="rejection_reason" id="rejectionReasonInput" required placeholder="Tuliskan alasan penolakan pendaftaran akun di sini..." class="form-input-custom" style="min-height: 100px; padding: 10px 12px; resize: vertical; border-radius: 10px;"></textarea>
                <span style="font-size: 11.5px; color: #6B7280; display: block; margin-top: 5px;">Alasan ini akan dikirimkan secara otomatis ke alamat Gmail pengguna.</span>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeRejectModal()" class="btn-btn btn-btn-secondary">Batal</button>
                <button type="submit" class="btn-btn" style="background: #DC2626; color: #FFFFFF; border: none; font-weight: 700; padding: 10px 20px;">
                    <i class="bi bi-send-fill"></i> Kirim Penolakan &amp; Email
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL POPUP: CONFIRM DELETE (HAPUS AKUN PENGGUNA) -->
<!-- ========================================================================= -->
<div id="deleteUserModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.65); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center; padding: 20px;">
    <div class="card-panel-variant" style="width: 100%; max-width: 480px; margin: 0; padding: 28px; background: #FFFFFF; border-radius: 18px; box-shadow: 0 20px 50px rgba(0,0,0,0.25);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid #F3F4F6; padding-bottom: 12px;">
            <h3 style="font-size: 17px; font-weight: 800; color: #991B1B; margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-exclamation-triangle-fill" style="color: #DC2626;"></i> Konfirmasi Hapus Akun
            </h3>
            <button onclick="closeDeleteModal()" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #9CA3AF;">&times;</button>
        </div>

        <div style="margin-bottom: 16px; background: #FEF2F2; padding: 14px; border-radius: 10px; border: 1px solid #FCA5A5;">
            <div style="font-size: 13.5px; color: #7F1D1D; font-weight: 700;">Pengguna: <span id="deleteUserName" style="color: #1E293B;">-</span></div>
            <div style="font-size: 12.5px; color: #991B1B;" id="deleteUserEmail">-</div>
        </div>

        <p style="font-size: 13.5px; color: #475569; line-height: 1.5; margin: 0 0 20px 0;">
            Apakah Anda yakin ingin <strong>menghapus</strong> akun pengguna ini secara permanen dari sistem? Tindakan ini tidak dapat dibatalkan.
        </p>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" onclick="closeDeleteModal()" class="btn-btn btn-btn-secondary">Batal</button>
            <a id="btnConfirmDelete" href="" class="btn-btn" style="background: #DC2626; color: #FFFFFF; border: none; font-weight: 700; padding: 10px 22px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <i class="bi bi-trash-fill"></i> Ya, Hapus Akun
            </a>
        </div>
    </div>
</div>

<script>
function openDeleteModal(id, name, email) {
    document.getElementById('btnConfirmDelete').href = '<?= BASE_URL ?>/users/delete/' + id;
    document.getElementById('deleteUserName').textContent = name;
    document.getElementById('deleteUserEmail').textContent = email;
    document.getElementById('deleteUserModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteUserModal').style.display = 'none';
}
function openApproveModal(id, name, email) {
    document.getElementById('btnConfirmApprove').href = '<?= BASE_URL ?>/users/approve/' + id;
    document.getElementById('approveUserName').textContent = name;
    document.getElementById('approveUserEmail').textContent = email;
    document.getElementById('approveUserModal').style.display = 'flex';
}

function closeApproveModal() {
    document.getElementById('approveUserModal').style.display = 'none';
}

function openRejectModal(id, name, email) {
    document.getElementById('rejectUserForm').action = '<?= BASE_URL ?>/users/reject/' + id;
    document.getElementById('rejectUserName').textContent = name;
    document.getElementById('rejectUserEmail').textContent = email;
    document.getElementById('rejectionReasonInput').value = '';
    document.getElementById('rejectUserModal').style.display = 'flex';
}

function closeRejectModal() {
    document.getElementById('rejectUserModal').style.display = 'none';
}

function decodeHtmlEntities(str) {
    if (!str) return '';
    var txt = document.createElement('textarea');
    txt.innerHTML = str;
    return txt.value.trim();
}

const kppOccupied = (<?= json_encode(array_values($kppDivisiList ?? [])) ?>).map(decodeHtmlEntities);
const kapelOccupied = (<?= json_encode(array_values($kapelDivisiList ?? [])) ?>).map(decodeHtmlEntities);
const kppAllOptions = ['Program', 'Berita', 'Konten Media Baru (KMB)', 'Teknik', 'Pengembangan dan Usaha (PU)', 'Umum', 'Keuangan'].map(decodeHtmlEntities);
const kapelAllOptions = (<?= json_encode(array_values($allKapelBidangs ?? [])) ?>).map(decodeHtmlEntities);

function toggleCustomDropdown(e, menuId, chevronId, triggerId) {
    if (e) e.stopPropagation();
    var menu = document.getElementById(menuId);
    var chevron = document.getElementById(chevronId);
    var trigger = document.getElementById(triggerId);
    if (!menu || !trigger) return;

    var isOpen = menu.style.display === 'block';
    closeAllCustomDropdowns();

    if (!isOpen) {
        menu.style.display = 'block';
        if (chevron) chevron.style.transform = 'rotate(180deg)';
        if (trigger) {
            trigger.style.borderColor = triggerId.includes('edit') ? '#2563EB' : '#7E22CE';
            trigger.style.boxShadow = triggerId.includes('edit') 
                ? '0 0 0 3px rgba(37, 99, 235, 0.12)' 
                : '0 0 0 3px rgba(126, 34, 206, 0.12)';
        }
    }
}

function closeAllCustomDropdowns() {
    var menus = document.querySelectorAll('.custom-dropdown-menu');
    menus.forEach(function(m) { m.style.display = 'none'; });
    var chevrons = document.querySelectorAll('.custom-select-trigger .bi-chevron-down');
    chevrons.forEach(function(c) { c.style.transform = 'rotate(0deg)'; });
    var triggers = document.querySelectorAll('.custom-select-trigger');
    triggers.forEach(function(t) {
        t.style.borderColor = '#E2E8F0';
        t.style.boxShadow = 'none';
    });
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.custom-select-container')) {
        closeAllCustomDropdowns();
    }
});

function selectCreateRole(val, label) {
    var hidden = document.getElementById('createRoleHidden');
    var textEl = document.getElementById('createRoleSelectedText');
    if (hidden) hidden.value = val;
    if (textEl) {
        textEl.textContent = label || '-- Pilih Role Pengguna --';
        textEl.style.color = val ? '#1F2937' : '#9CA3AF';
        textEl.style.fontWeight = '400';
    }

    var items = document.querySelectorAll('#createRoleMenu .custom-option-item');
    items.forEach(function(item) {
        var isSelected = item.getAttribute('data-val') === val;
        item.classList.toggle('selected', isSelected);
        var check = item.querySelector('.check-icon');
        if (check) check.style.display = isSelected ? 'inline-block' : 'none';
    });

    closeAllCustomDropdowns();
    onRoleChange(val);
}

function selectCreateBidang(val) {
    var hidden = document.getElementById('createBidangHidden');
    var textEl = document.getElementById('createBidangSelectedText');
    if (hidden) hidden.value = val;
    if (textEl) {
        textEl.textContent = val || '-- Pilih Bidang --';
        textEl.style.color = val ? '#1F2937' : '#9CA3AF';
        textEl.style.fontWeight = '400';
    }

    var items = document.querySelectorAll('#createBidangMenu .custom-option-item');
    items.forEach(function(item) {
        var isSelected = item.getAttribute('data-val') === val;
        item.classList.toggle('selected', isSelected);
        var check = item.querySelector('.check-icon');
        if (check) check.style.display = isSelected ? 'inline-block' : 'none';
    });

    closeAllCustomDropdowns();
    updateCreateUsernamePreview();
}

function populateCreateBidangOptions(roleVal) {
    var list = document.getElementById('createBidangOptionsList');
    if (!list) return;
    list.innerHTML = '';

    var options = [];
    if (roleVal === 'kpp_bidang' || roleVal === 'kpp') {
        options = kppAllOptions.filter(function(opt) {
            return !kppOccupied.includes(decodeHtmlEntities(opt));
        });
    } else if (roleVal === 'kapel_bidang' || roleVal === 'kapel') {
        options = kapelAllOptions.filter(function(opt) {
            return !kapelOccupied.includes(decodeHtmlEntities(opt));
        });
    }

    if (options.length === 0) {
        list.innerHTML = '<div style="padding: 10px; font-size: 13px; color: #94A3B8; text-align: center;">Tidak ada bidang yang tersedia</div>';
        return;
    }

    options.forEach(function(opt) {
        var cleanOpt = decodeHtmlEntities(opt);
        var escapedOpt = cleanOpt.replace(/'/g, "\\'");
        var itemHtml = `
            <div class="custom-option-item" data-val="${cleanOpt}" onclick="selectCreateBidang('${escapedOpt}')">
                <span class="option-label" style="font-size: 13px; font-weight: 500; color: #1F2937;">${cleanOpt}</span>
                <i class="bi bi-check2 check-icon" style="display: none; color: #7E22CE; font-size: 16px; font-weight: 800;"></i>
            </div>
        `;
        list.insertAdjacentHTML('beforeend', itemHtml);
    });
}

function selectEditRole(val, label) {
    var hidden = document.getElementById('editRoleHidden');
    var textEl = document.getElementById('editRoleSelectedText');
    if (hidden) hidden.value = val;
    if (textEl) {
        textEl.textContent = label || '-- Pilih Role Pengguna --';
        textEl.style.color = val ? '#1F2937' : '#9CA3AF';
        textEl.style.fontWeight = '400';
    }

    var items = document.querySelectorAll('#editRoleMenu .custom-option-item');
    items.forEach(function(item) {
        var isSelected = item.getAttribute('data-val') === val;
        item.classList.toggle('selected', isSelected);
        var check = item.querySelector('.check-icon');
        if (check) check.style.display = isSelected ? 'inline-block' : 'none';
    });

    closeAllCustomDropdowns();
    toggleEditBidangInput(val);
}

function selectEditBidang(val) {
    var hidden = document.getElementById('editBidangHidden');
    var textEl = document.getElementById('editBidangSelectedText');
    if (hidden) hidden.value = val;
    if (textEl) {
        textEl.textContent = val || '-- Pilih Bidang --';
        textEl.style.color = val ? '#1F2937' : '#9CA3AF';
        textEl.style.fontWeight = '400';
    }

    var items = document.querySelectorAll('#editBidangMenu .custom-option-item');
    items.forEach(function(item) {
        var isSelected = item.getAttribute('data-val') === val;
        item.classList.toggle('selected', isSelected);
        var check = item.querySelector('.check-icon');
        if (check) check.style.display = isSelected ? 'inline-block' : 'none';
    });

    closeAllCustomDropdowns();
    updateEditUsernamePreview();
}

function populateEditBidangOptions(roleVal, currentDivisi) {
    var list = document.getElementById('editBidangOptionsList');
    if (!list) return;
    list.innerHTML = '';

    currentDivisi = decodeHtmlEntities((currentDivisi || '').trim());

    var options = [];
    if (roleVal === 'kpp_bidang' || roleVal === 'kpp') {
        options = kppAllOptions.filter(function(opt) {
            var decodedOpt = decodeHtmlEntities(opt);
            return decodedOpt === currentDivisi || !kppOccupied.includes(decodedOpt);
        });
    } else if (roleVal === 'kapel_bidang' || roleVal === 'kapel') {
        options = kapelAllOptions.filter(function(opt) {
            var decodedOpt = decodeHtmlEntities(opt);
            return decodedOpt === currentDivisi || !kapelOccupied.includes(decodedOpt);
        });
    }

    if (options.length === 0) {
        list.innerHTML = '<div style="padding: 10px; font-size: 13px; color: #94A3B8; text-align: center;">Tidak ada bidang yang tersedia</div>';
        return;
    }

    options.forEach(function(opt) {
        var cleanOpt = decodeHtmlEntities(opt);
        var isSel = cleanOpt === currentDivisi;
        var escapedOpt = cleanOpt.replace(/'/g, "\\'");
        var itemHtml = `
            <div class="custom-option-item ${isSel ? 'selected' : ''}" data-val="${cleanOpt}" onclick="selectEditBidang('${escapedOpt}')">
                <span class="option-label" style="font-size: 13px; font-weight: 600; color: #1F2937;">${cleanOpt}</span>
                <i class="bi bi-check2 check-icon" style="display: ${isSel ? 'inline-block' : 'none'}; color: #2563EB; font-size: 16px; font-weight: 800;"></i>
            </div>
        `;
        list.insertAdjacentHTML('beforeend', itemHtml);
    });
}

function openCreateUserModal() {
    var form = document.getElementById('createUserForm');
    if (form) form.reset();
    selectCreateRole('', '');
    selectCreateBidang('');
    var modal = document.getElementById('createUserModal');
    if (modal) {
        modal.scrollTop = 0;
        modal.style.display = 'flex';
    }
}

function closeCreateUserModal() {
    document.getElementById('createUserModal').style.display = 'none';
}

function onRoleChange(roleVal) {
    var row = document.getElementById('bidangInputRow');
    if (!row) return;

    selectCreateBidang('');

    if (roleVal === 'kpp_bidang' || roleVal === 'kpp' || roleVal === 'kapel_bidang' || roleVal === 'kapel') {
        row.style.display = 'grid';
        populateCreateBidangOptions(roleVal);
    } else {
        row.style.display = 'none';
    }

    updateCreateUsernamePreview();
}

function computeAutoUsername(role, divisi, nip) {
    if (!role) return nip || '-';
    role = role.toLowerCase();
    divisi = decodeHtmlEntities((divisi || '').trim());

    if (role === 'superadmin') return 'superadmin';
    if (role === 'admin') return 'admin';
    if (role === 'tata_usaha' || role === 'tu' || role === 'ktu') return 'tu';
    if (role === 'sekretaris' || role === 'sekre') return 'sekre';
    if (role === 'kepala_stasiun' || role === 'kepsta') return 'kepsta';

    if (role === 'kpp_bidang' || role === 'kpp') {
        var kppMap = {
            'Program': 'kpp_program',
            'Berita': 'kpp_berita',
            'Konten Media Baru (KMB)': 'kpp_kmb',
            'Teknik': 'kpp_teknik',
            'Pengembangan dan Usaha (PU)': 'kpp_pu',
            'Umum': 'kpp_umum',
            'Keuangan': 'kpp_keuangan'
        };
        if (kppMap[divisi]) return kppMap[divisi];
        var slug = divisi.replace(/&/g, ' ').replace(/[^a-zA-Z0-9\s_]/g, '').trim().replace(/\s+/g, '_').toLowerCase();
        return 'kpp_' + (slug || 'bidang');
    }

    if (role === 'kapel_bidang' || role === 'kapel') {
        var kapelMap = {
            'Kapel Monev Program': 'kapel_monev_program',
            'Kapel Pengelola SDM & RB': 'kapel_sdm',
            'Kapel Dokumentasi & Distribusi Audio Video': 'kapel_dokumentasi',
            'Kapel Perlengkapan & Sarpras': 'kapel_sarpras',
            'Kapel CA & OR': 'kapel_caor',
            'Kapel Pengelola BMN': 'kapel_bmn',
            'Kapel Berita': 'kapel_berita',
            'Kapel Humas & Protokol': 'kapel_humas',
            'Kapel Monev Berita': 'kapel_monev_berita',
            'Kapel Konten, Kreatif Digital': 'kapel_kreatif',
            'Kapel Penjualan & Pemasaran': 'kapel_pemasaran',
            'Kapel Analitik & Distribusi Konten': 'kapel_analitik',
            'Kapel IT': 'kapel_it',
            'PPK I': 'kapel_ppk1',
            'Kapel Produksi & Penyiaran Teknik': 'kapel_produksi_teknik',
            'PPK II': 'kapel_ppk2',
            'Kapel Produksi Program': 'kapel_produksi_program',
            'Kapel Transmisi': 'kapel_transmisi',
            'Kapel Promo': 'kapel_promo',
            'Kapel Evaluasi & Pelaporan Keuangan': 'kapel_evaluasi_keuangan'
        };
        if (kapelMap[divisi]) return kapelMap[divisi];
        var clean = divisi.replace(/^Kapel\s+/i, '');
        var slugK = clean.replace(/&/g, ' ').replace(/[^a-zA-Z0-9\s_]/g, '').trim().replace(/\s+/g, '_').toLowerCase();
        return 'kapel_' + (slugK || 'bidang');
    }

    return nip || '-';
}

function updateCreateUsernamePreview() {
    var role = (document.getElementById('createRoleHidden') || {}).value || '';
    var divisi = (document.getElementById('createBidangHidden') || {}).value || '';
    var nipInput = document.querySelector('#createUserForm input[name="nip"]');
    var nip = nipInput ? nipInput.value : '';
    var box = document.getElementById('createAutoUsernameBox');
    var text = document.getElementById('createAutoUsernameText');
    if (!box || !text) return;
    if (role) {
        box.style.display = 'flex';
        text.textContent = computeAutoUsername(role, divisi, nip);
    } else {
        box.style.display = 'none';
    }
}

function updateEditUsernamePreview() {
    var role = (document.getElementById('editRoleHidden') || {}).value || '';
    var divisi = (document.getElementById('editBidangHidden') || {}).value || '';
    var nip = (document.getElementById('editNipInput') || {}).value || '';
    var box = document.getElementById('editAutoUsernameBox');
    var text = document.getElementById('editAutoUsernameText');
    if (!box || !text) return;
    if (role) {
        box.style.display = 'flex';
        text.textContent = computeAutoUsername(role, divisi, nip);
    } else {
        box.style.display = 'none';
    }
}

function openEditUserModal(user) {
    if (!user) return;
    document.getElementById('editUserForm').action = '<?= BASE_URL ?>/users/update/' + user.id;
    document.getElementById('editNameInput').value = user.name || '';
    document.getElementById('editNipInput').value = user.nip || '';
    document.getElementById('editEmailInput').value = user.email || '';

    var roleLabelMap = {
        'admin': 'Admin',
        'kpp_bidang': 'KPP Bidang',
        'kpp': 'KPP Bidang',
        'kapel_bidang': 'Kapel Bidang',
        'kapel': 'Kapel Bidang',
        'tata_usaha': 'Tata Usaha',
        'sekretaris': 'Sekretaris',
        'kepala_stasiun': 'Kepala Stasiun',
        'superadmin': 'Superadmin'
    };

    var userRole = user.role || 'admin';
    selectEditRole(userRole, roleLabelMap[userRole] || userRole);

    if (user.role === 'kpp_bidang' || user.role === 'kpp' || user.role === 'kapel_bidang' || user.role === 'kapel') {
        var cleanDivisi = decodeHtmlEntities(user.divisi || '');
        selectEditBidang(cleanDivisi);
        populateEditBidangOptions(user.role, cleanDivisi);
    } else {
        selectEditBidang('');
    }

    updateEditUsernamePreview();
    var modal = document.getElementById('editUserModal');
    if (modal) {
        modal.scrollTop = 0;
        modal.style.display = 'flex';
    }
}

function closeEditUserModal() {
    document.getElementById('editUserModal').style.display = 'none';
}

function toggleEditBidangInput(roleVal) {
    var row = document.getElementById('editBidangInputRow');
    if (!row) return;

    if (roleVal === 'kpp_bidang' || roleVal === 'kpp' || roleVal === 'kapel_bidang' || roleVal === 'kapel') {
        row.style.display = 'grid';
        populateEditBidangOptions(roleVal, document.getElementById('editBidangHidden').value);
    } else {
        row.style.display = 'none';
        selectEditBidang('');
    }
    updateEditUsernamePreview();
}

document.addEventListener('DOMContentLoaded', function() {
    var createNipInput = document.querySelector('#createUserForm input[name="nip"]');
    if (createNipInput) {
        createNipInput.addEventListener('input', updateCreateUsernamePreview);
    }
    var editNipInput = document.getElementById('editNipInput');
    if (editNipInput) {
        editNipInput.addEventListener('input', updateEditUsernamePreview);
    }

    var createForm = document.getElementById('createUserForm');
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            var role = document.getElementById('createRoleHidden').value;
            var divisi = document.getElementById('createBidangHidden').value;
            if (!role) {
                e.preventDefault();
                alert('Harap pilih Role Pengguna terlebih dahulu.');
                return false;
            }
            if ((role === 'kpp_bidang' || role === 'kapel_bidang') && !divisi) {
                e.preventDefault();
                alert('Harap pilih Bidang terlebih dahulu.');
                return false;
            }
        });
    }

    var editForm = document.getElementById('editUserForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            var role = document.getElementById('editRoleHidden').value;
            var divisi = document.getElementById('editBidangHidden').value;
            if (!role) {
                e.preventDefault();
                alert('Harap pilih Role Pengguna terlebih dahulu.');
                return false;
            }
            if ((role === 'kpp_bidang' || role === 'kapel_bidang') && !divisi) {
                e.preventDefault();
                alert('Harap pilih Bidang terlebih dahulu.');
                return false;
            }
        });
    }
});
</script>
