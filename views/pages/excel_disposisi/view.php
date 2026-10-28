<style>
    /* Lock entire page from scrolling horizontally on desktop & mobile; 
       strictly restrict horizontal scrolling to the spreadsheet table card */
    html, body {
        overflow-x: hidden !important;
    }
    .app-main {
        min-width: 0 !important;
        max-width: calc(100vw - 260px) !important;
        overflow-x: hidden !important;
    }
    @media (max-width: 992px) {
        .app-main {
            max-width: 100vw !important;
        }
    }
    .app-page {
        min-width: 0 !important;
        max-width: 100% !important;
        overflow-x: hidden !important;
    }
    .excel-view-page-container {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        overflow-x: hidden;
    }

    .excel-view-header {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        background: #FFFFFF;
        border-radius: 18px;
        border: 1.5px solid #E2E8F0;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.03);
    }
    .excel-breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #64748B;
        margin-bottom: 12px;
    }
    .excel-breadcrumb a {
        color: #047857;
        text-decoration: none;
        font-weight: 600;
    }
    .excel-breadcrumb a:hover {
        text-decoration: underline;
    }

    /* Spreadsheet Container Card */
    .spreadsheet-container {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        background: #FFFFFF;
        border-radius: 16px;
        border: 1.5px solid #CBD5E1;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 30px;
    }
    .spreadsheet-toolbar {
        padding: 16px 20px;
        background: #F8FAFC;
        border-bottom: 1.5px solid #E2E8F0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    /* Spreadsheet Table Styling matching user photo */
    .spreadsheet-table-wrapper {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        background: #FFFFFF;
    }
    /* Clean custom horizontal scrollbar for desktop within the table card */
    .spreadsheet-table-wrapper::-webkit-scrollbar {
        height: 10px;
    }
    .spreadsheet-table-wrapper::-webkit-scrollbar-track {
        background: #F1F5F9;
        border-radius: 6px;
    }
    .spreadsheet-table-wrapper::-webkit-scrollbar-thumb {
        background: #94A3B8;
        border-radius: 6px;
        border: 2px solid #F1F5F9;
    }
    .spreadsheet-table-wrapper::-webkit-scrollbar-thumb:hover {
        background: #047857;
    }
    .spreadsheet-table {
        width: 100%;
        min-width: 1180px; /* Prevents columns from being crushed on mobile view */
        table-layout: fixed;
        border-collapse: collapse;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        font-size: 12px;
        color: #0F172A;
    }
    /* Header row styled with light mint green matching uploaded photo */
    .spreadsheet-table thead tr th {
        background-color: #BFE3D5 !important;
        color: #0F172A;
        font-weight: 800;
        font-size: 12px;
        padding: 11px 8px;
        border: 1px solid #7FA495;
        text-align: center;
        vertical-align: middle;
        white-space: normal;
        line-height: 1.35;
        letter-spacing: 0.1px;
    }
    .spreadsheet-table tbody tr {
        transition: background-color 0.15s ease;
    }
    .spreadsheet-table tbody tr:hover {
        background-color: #F0FDF4;
    }
    .spreadsheet-table tbody td {
        padding: 10px 8px;
        border: 1px solid #CBD5E1;
        vertical-align: top;
        line-height: 1.45;
        background-color: #FFFFFF;
        word-break: break-word;
        overflow-wrap: anywhere;
    }
    .spreadsheet-table tbody tr:hover td {
        background-color: #F0FDF4;
    }

    /* Column Specific Alignments & Widths */
    .col-agenda {
        width: 90px;
        font-weight: 700;
        font-family: 'Consolas', 'Courier New', monospace;
        color: #0F172A;
        font-size: 11.5px;
        text-align: center;
    }
    .col-tgl {
        width: 85px;
        text-align: center;
        color: #334155;
        font-weight: 600;
        font-size: 11.5px;
    }
    .col-nomor {
        width: 135px;
        font-family: 'Consolas', 'Courier New', monospace;
        color: #0F172A;
        font-size: 11.5px;
    }
    .col-dari {
        width: 120px;
        color: #334155;
        font-size: 12px;
    }
    .col-hal {
        width: 190px;
        color: #0F172A;
        font-weight: 500;
        font-size: 12px;
    }
    .col-sifat {
        width: 85px;
        text-align: center;
        color: #0F172A;
        font-size: 12px;
        font-weight: 500;
    }
    .col-penerima {
        width: 150px;
        color: #1E293B;
        font-size: 12px;
    }
    .col-disposisi {
        width: 135px;
        color: #1E293B;
        font-size: 12px;
    }
    .col-catatan {
        width: 105px;
        color: #475569;
        font-style: italic;
        font-size: 11.5px;
    }
    .col-link {
        width: 140px;
        text-align: left;
        word-break: break-all;
        overflow-wrap: anywhere;
    }
    .file-url-link {
        color: #0284C7;
        text-decoration: underline;
        word-break: break-all;
        overflow-wrap: anywhere;
        font-family: 'Consolas', 'Courier New', monospace;
        font-size: 10.5px;
        line-height: 1.35;
        transition: color 0.15s ease;
        display: inline-block;
        max-width: 100%;
    }
    .file-url-link:hover {
        color: #0369A1;
        text-decoration: underline;
    }

    /* Mobile Scroll Hint Banner */
    .mobile-scroll-hint {
        display: none;
        background: #ECFDF5;
        color: #047857;
        border-bottom: 1px solid #A7F3D0;
        padding: 8px 14px;
        font-size: 11.5px;
        font-weight: 600;
        align-items: center;
        justify-content: center;
        gap: 6px;
        text-align: center;
    }

    /* ==========================================================================
       Responsive Media Queries for Mobile
       ========================================================================== */
    @media (max-width: 768px) {
        .excel-view-header {
            padding: 16px 14px;
            border-radius: 14px;
            margin-bottom: 16px;
        }
        .excel-view-header-title {
            font-size: 18px !important;
            gap: 8px !important;
        }
        .excel-view-header-meta {
            font-size: 12px !important;
            gap: 8px !important;
        }
        .excel-view-header-actions {
            width: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 8px !important;
            margin-top: 8px !important;
        }
        .excel-view-header-actions .btn-btn {
            width: 100% !important;
            justify-content: center !important;
            padding: 10px 14px !important;
            font-size: 13px !important;
        }
        .spreadsheet-container {
            border-radius: 14px;
            margin-bottom: 20px;
        }
        .spreadsheet-toolbar {
            padding: 12px 14px;
        }
        .spreadsheet-search-box {
            width: 100% !important;
            max-width: 100% !important;
        }
        .mobile-scroll-hint {
            display: flex; /* Visible on mobile screens so users know to swipe horizontally */
        }
    }

    @media (max-width: 480px) {
        .excel-view-header {
            padding: 14px 12px;
        }
        .excel-view-header-title {
            font-size: 16.5px !important;
        }
    }
</style>

<div class="excel-view-page-container" style="width: 100%; max-width: 100%; min-width: 0; margin: 0 auto; padding-bottom: 40px;">
    <!-- View Header Card -->
    <div class="excel-view-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
            <div style="flex: 1; min-width: 250px;">
                <div class="excel-breadcrumb">
                    <a href="<?= BASE_URL ?>/excel-disposisi"><i class="bi bi-grid-3x3-gap-fill"></i> Kelola Excel Disposisi</a>
                    <span>/</span>
                    <span>Bulan <?= htmlspecialchars($periodLabel) ?></span>
                </div>

                <h1 class="excel-view-header-title" style="font-size: 22px; font-weight: 800; color: #0F172A; margin: 0 0 6px 0; display: flex; align-items: center; gap: 10px;">
                    <i class="bi bi-file-earmark-excel-fill" style="color: #059669;"></i>
                    Rekap Disposisi Bulan <?= htmlspecialchars($periodLabel) ?>
                </h1>

                <div class="excel-view-header-meta" style="font-size: 13px; color: #64748B; display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                    <span><i class="bi bi-file-code"></i> File: <strong><?= htmlspecialchars($filename) ?></strong> (<?= $fileSize ?>)</span>
                </div>
            </div>

            <!-- Action Buttons (Responsive on Mobile) -->
            <div class="excel-view-header-actions" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <a href="<?= BASE_URL ?>/excel-disposisi" class="btn-btn btn-btn-secondary" style="padding: 10px 18px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; border-radius: 10px;">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>

                <a href="<?= BASE_URL ?>/excel-disposisi/download?year=<?= $year ?>&month=<?= $month ?>" class="btn-btn" style="background: #047857; color: #FFFFFF; border-radius: 10px; padding: 10px 22px; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(4, 120, 87, 0.25);">
                    <i class="bi bi-download"></i> Unduh File Excel (.xlsx)
                </a>
            </div>
        </div>
    </div>

    <!-- Presentation Container -->
    <div class="spreadsheet-container">
        <!-- Toolbar inside Card -->
        <div class="spreadsheet-toolbar">
            <!-- Search Filter Input -->
            <div class="spreadsheet-search-box" style="display: flex; align-items: center; gap: 10px; flex: 1; max-width: 450px;">
                <i class="bi bi-search" style="color: #94A3B8;"></i>
                <input type="text" id="tableSearchInput" onkeyup="filterSpreadsheetTable()" placeholder="Ketik untuk mencari agenda, nomor, hal, atau penerima..."
                       style="width: 100%; border: 1.5px solid #CBD5E1; border-radius: 8px; padding: 8px 12px; font-size: 13px; outline: none; background: #FFFFFF;"
                       onfocus="this.style.borderColor='#10B981';" onblur="this.style.borderColor='#CBD5E1';">
            </div>
        </div>

        <!-- Mobile Scroll Hint (Shown on narrow screens) -->
        <div class="mobile-scroll-hint" id="mobileScrollHint">
            <i class="bi bi-arrow-left-right"></i> Geser tabel ke samping untuk melihat seluruh kolom
        </div>

        <!-- FULL SPREADSHEET TABLE VIEW (Matches uploaded photo 1:1) -->
        <div class="spreadsheet-table-wrapper" id="spreadsheetTableWrapper">
            <table class="spreadsheet-table" id="disposisiSpreadsheetTable">
                <thead>
                    <tr>
                        <th class="col-agenda">Nomor Agenda</th>
                        <th class="col-tgl">Tanggal</th>
                        <th class="col-nomor">Nomor Surat</th>
                        <th class="col-dari">Dari</th>
                        <th class="col-hal">Hal</th>
                        <th class="col-sifat">Sifat</th>
                        <th class="col-penerima">Unit Penerima</th>
                        <th class="col-disposisi">Disposisi</th>
                        <th class="col-catatan">Catatan</th>
                        <th class="col-link">Link File</th>
                    </tr>
                </thead>
                <tbody id="disposisiTableBody">
                    <?php if (empty($rows)): ?>
                        <tr class="empty-row">
                            <td colspan="10" style="text-align: center; padding: 50px 20px; color: #64748B;">
                                <i class="bi bi-inbox" style="font-size: 38px; color: #CBD5E1; display: block; margin-bottom: 8px;"></i>
                                <span style="font-size: 15px; font-weight: 700; color: #334155; display: block;">Belum ada surat disposisi pada bulan <?= htmlspecialchars($periodLabel) ?></span>
                                <span style="font-size: 13px;">Data akan terisi secara otomatis ketika Kepala Stasiun (Kepsta) menyetujui dan meneruskan disposisi.</span>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $item): ?>
                            <?php 
                            $sifatLower = strtolower($item['sifat'] ?? 'biasa');
                            ?>
                            <tr class="data-row" data-sifat="<?= $sifatLower ?>">
                                <!-- 1. Nomor Agenda -->
                                <td class="col-agenda">
                                    <?= htmlspecialchars($item['nomor_agenda']) ?>
                                </td>

                                <!-- 2. Tanggal (1-Oct-2026 format) -->
                                <td class="col-tgl">
                                    <?= htmlspecialchars($item['tanggal']) ?>
                                </td>

                                <!-- 3. Nomor Surat -->
                                <td class="col-nomor">
                                    <?= htmlspecialchars($item['nomor_surat']) ?>
                                </td>

                                <!-- 4. Dari -->
                                <td class="col-dari">
                                    <?= htmlspecialchars($item['dari']) ?>
                                </td>

                                <!-- 5. Hal -->
                                <td class="col-hal">
                                    <?= htmlspecialchars($item['hal']) ?>
                                </td>

                                <!-- 6. Sifat -->
                                <td class="col-sifat">
                                    <?= htmlspecialchars($item['sifat']) ?>
                                </td>

                                <!-- 7. Unit Penerima -->
                                <td class="col-penerima">
                                    <?= htmlspecialchars($item['unit_penerima']) ?>
                                </td>

                                <!-- 8. Disposisi -->
                                <td class="col-disposisi">
                                    <?= htmlspecialchars($item['disposisi']) ?>
                                </td>

                                <!-- 9. Catatan -->
                                <td class="col-catatan">
                                    <?= htmlspecialchars($item['catatan']) ?>
                                </td>

                                <!-- 10. Link File (Link teks URL ke PDF di Google Drive) -->
                                <td class="col-link">
                                    <?php if (!empty($item['link_file']) && $item['link_file'] !== '-'): ?>
                                        <a href="<?= htmlspecialchars($item['link_file']) ?>" target="_blank" rel="noopener noreferrer" class="file-url-link" title="Klik untuk membuka file PDF di Google Drive">
                                            <?= htmlspecialchars($item['link_file']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #94A3B8; font-size: 12px;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- No search results notification -->
        <div id="noSearchMatchAlert" style="display: none; padding: 40px 20px; text-align: center; color: #64748B;">
            <i class="bi bi-search" style="font-size: 32px; color: #CBD5E1; display: block; margin-bottom: 8px;"></i>
            <span style="font-size: 14px; font-weight: 700; color: #334155; display: block;">Tidak ada data yang cocok dengan pencarian</span>
            <span style="font-size: 12px;">Coba gunakan kata kunci lain (agenda, nomor surat, perihal, atau unit penerima).</span>
        </div>
    </div>
</div>

<script>
// Synchronous live filter for Spreadsheet Table Rows
function filterSpreadsheetTable() {
    const searchVal = document.getElementById('tableSearchInput').value.toLowerCase().trim();
    const table = document.getElementById('disposisiSpreadsheetTable');
    const rows = table ? table.querySelectorAll('tbody tr.data-row') : [];
    let matchCount = 0;

    rows.forEach(row => {
        const rowText = row.innerText.toLowerCase();
        const matches = searchVal === '' || rowText.includes(searchVal);
        row.style.display = matches ? '' : 'none';
        if (matches) matchCount++;
    });

    // Handle No Results Message
    const noResultsAlert = document.getElementById('noSearchMatchAlert');
    const totalDataCount = <?= count($rows) ?>;
    
    if (totalDataCount > 0) {
        if (matchCount === 0 && searchVal !== '') {
            if (noResultsAlert) noResultsAlert.style.display = 'block';
            if (table) table.style.display = 'none';
        } else {
            if (noResultsAlert) noResultsAlert.style.display = 'none';
            if (table) table.style.display = 'table';
        }
    }
}

// Enable smooth click-and-drag horizontal scrolling inside the table card on desktop
(function() {
    const wrapper = document.getElementById('spreadsheetTableWrapper');
    if (!wrapper) return;

    let isDown = false;
    let startX = 0;
    let scrollLeft = 0;

    wrapper.addEventListener('mousedown', function(e) {
        if (e.button !== 0 || e.target.closest('a') || e.target.closest('input') || e.target.closest('button')) return;
        isDown = true;
        wrapper.style.cursor = 'grabbing';
        wrapper.style.userSelect = 'none';
        startX = e.pageX - wrapper.offsetLeft;
        scrollLeft = wrapper.scrollLeft;
    });

    window.addEventListener('mouseup', function() {
        if (!isDown) return;
        isDown = false;
        if (wrapper) {
            wrapper.style.cursor = '';
            wrapper.style.removeProperty('user-select');
        }
    });

    wrapper.addEventListener('mousemove', function(e) {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - wrapper.offsetLeft;
        const walk = (x - startX) * 1.5;
        wrapper.scrollLeft = scrollLeft - walk;
    });
})();
</script>
