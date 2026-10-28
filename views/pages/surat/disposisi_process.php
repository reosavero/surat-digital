<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

<style>
    img { max-width: 100% !important; display: inline-block !important; visibility: visible !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    @media print {
        @page { size: A4 portrait; margin: 0; }
        :root {
            color-scheme: light !important;
        }
        html, body {
            width: 210mm !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #ffffff !important;
            color: #000000 !important;
            color-scheme: light !important;
            overflow: visible !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .no-print, button, .btn-btn, header, aside, .app-topbar, .app-sidebar, .toast-container, .page-header-banner, .disposisi-bottom-footer {
            display: none !important;
        }
        .app-main, .app-page, .printable-wrapper, div[style*="max-width: 1360px"], .admin-review-grid-container, .admin-review-col {
            padding: 0 !important;
            margin: 0 !important;
            background: #ffffff !important;
            border: none !important;
            box-shadow: none !important;
            width: 210mm !important;
            max-width: 210mm !important;
            height: auto !important;
            min-height: 0 !important;
        }
        .a4-paper-preview {
            background: #ffffff !important;
            border: none !important;
            box-shadow: none !important;
        }
        img { display: inline-block !important; visibility: visible !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
    @media screen and (max-width: 768px) {
        .printable-wrapper {
            padding: 14px 8px !important;
            border-radius: 14px !important;
        }
        .disposisi-bottom-footer {
            flex-direction: column-reverse !important;
            gap: 10px !important;
            padding: 14px 16px !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        .disposisi-bottom-footer > * {
            width: 100% !important;
            justify-content: center !important;
            text-align: center !important;
        }
    }
</style>

<div style="max-width: 1360px; margin: 0 auto; padding-bottom: 40px;">
    <!-- Top Action & Navigation Header -->
    <div class="page-header-banner no-print" style="margin-bottom: 24px;">
        <h1 style="font-size: 24px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 10px;">
            <i class="bi bi-file-earmark-check-fill" style="color: #0284C7;"></i> Review &amp; Cetak Dokumen Disposisi
        </h1>
        <p style="font-size: 14px; color: #64748b; margin-top: 4px; margin-bottom: 0;">
            Review seluruh lembar disposisi Kepala Stasiun, surat permohonan/dinas, dan lampiran dokumen terkait.
        </p>
    </div>

    <!-- Document Review Template (Disposisi Sheet + Surat + Lampiran PDF) -->
    <div class="printable-wrapper" style="background: #ffffff; padding: 32px; border-radius: 20px; border: 1.5px solid #E2E8F0; box-shadow: 0 10px 30px rgba(0,0,0,0.04); margin-bottom: 24px;">
        <?php include __DIR__ . '/admin_review_template.php'; ?>
    </div>

    <!-- Bottom Action Footer -->
    <div class="disposisi-bottom-footer no-print" style="display: flex; justify-content: flex-end; align-items: center; gap: 12px; padding: 18px 24px; background: #FFFFFF; border: 1.5px solid #E2E8F0; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
        <a href="<?= BASE_URL ?>/disposisi" class="btn-btn btn-btn-secondary" style="padding: 10px 20px; border-radius: 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>

        <button onclick="printDisposisiDoc();" class="btn-btn btn-btn-primary" style="background: #0284C7; border-color: #0284C7; font-weight: 700; padding: 10px 24px; border-radius: 10px; font-size: 14.5px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer;">
            <i class="bi bi-printer-fill"></i> Cetak / Print PDF Surat
        </button>
    </div>
</div>

<script>
async function printDisposisiDoc() {
    try {
        if (typeof renderServerPdfCanvases === 'function') {
            await renderServerPdfCanvases();
        }
    } catch (e) {
        console.error('Error pre-rendering PDF canvases:', e);
    }

    // Ensure all canvas elements are properly converted to image items
    document.querySelectorAll('.pdf-canvas-item').forEach(canvas => {
        if (canvas.width > 0) {
            try {
                const img = canvas.nextElementSibling;
                if (img && img.classList.contains('pdf-img-item')) {
                    img.src = canvas.toDataURL('image/png');
                    img.style.display = 'block';
                    canvas.style.display = 'none';
                }
            } catch (e) {
                console.error('Error converting canvas for print:', e);
            }
        }
    });

    const wrapper = document.querySelector('.printable-wrapper') || document;
    const papers = wrapper.querySelectorAll('.a4-paper-preview, .pdf-lampiran-card-preview');

    if (!papers || papers.length === 0) {
        window.print();
        return;
    }

    let papersHtml = '';

    papers.forEach(paper => {
        const isLampiran = paper.classList.contains('pdf-lampiran-card-preview');
        if (isLampiran) {
            const origImg = paper.querySelector('.pdf-img-item');
            const origCanvas = paper.querySelector('canvas');
            let lampiranImgSrc = null;

            if (origImg && origImg.src && origImg.src.length > 10 && !origImg.src.includes('about:blank')) {
                lampiranImgSrc = origImg.src;
            }
            if (!lampiranImgSrc && origCanvas && origCanvas.width > 0) {
                try {
                    lampiranImgSrc = origCanvas.toDataURL('image/png');
                } catch (e) {
                    console.error('Error converting canvas for lampiran:', e);
                }
            }
            if (!lampiranImgSrc) {
                const anyImg = paper.querySelector('img');
                if (anyImg && anyImg.src && anyImg.src.length > 10 && !anyImg.src.includes('about:blank')) {
                    lampiranImgSrc = anyImg.src;
                }
            }

            papersHtml += `
            <div class="printable-a4-page printable-lampiran-page">
                <img src="${lampiranImgSrc || ''}" class="pdf-img-item">
            </div>`;
        } else {
            const cloned = paper.cloneNode(true);
            cloned.querySelectorAll('.no-print').forEach(el => el.remove());

            const clonedCanvases = cloned.querySelectorAll('canvas');
            const origCanvases = paper.querySelectorAll('canvas');
            origCanvases.forEach((c, i) => {
                if (clonedCanvases[i]) {
                    if (c && c.width > 0 && c.style.display !== 'none') {
                        try {
                            const dataUrl = c.toDataURL('image/png');
                            const img = document.createElement('img');
                            img.src = dataUrl;
                            img.style.maxWidth = '100%';
                            img.style.height = 'auto';
                            img.style.display = 'block';
                            img.style.margin = '0 auto';
                            clonedCanvases[i].parentNode.replaceChild(img, clonedCanvases[i]);
                        } catch (e) {
                            clonedCanvases[i].remove();
                        }
                    } else {
                        clonedCanvases[i].remove();
                    }
                }
            });

            cloned.querySelectorAll('.pdf-img-item').forEach(img => {
                if (img.src && img.src.length > 10 && !img.src.includes('about:blank')) {
                    img.style.display = 'block';
                    img.style.width = '100%';
                    img.style.height = 'auto';
                    img.style.visibility = 'visible';
                }
            });

            const isDispo = paper.classList.contains('dispo-paper-preview');
            if (isDispo) {
                papersHtml += `<div class="printable-a4-page printable-dispo-page">${cloned.innerHTML}</div>`;
            } else {
                papersHtml += `<div class="printable-a4-page printable-surat-page">${cloned.innerHTML}</div>`;
            }
        }
    });

    const printWin = window.open('', '_blank', 'width=950,height=800');
    if (!printWin) {
        window.print();
        return;
    }

    printWin.document.write(`<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Cetak Dokumen Disposisi - TVRI Jawa Timur</title>
    <base href="<?= (defined('BASE_URL') ? BASE_URL : '') ?>/">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            color-scheme: light !important;
        }
        @page {
            size: A4 portrait;
            margin: 0;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        html, body {
            font-family: Arial, sans-serif;
            background: #ffffff !important;
            color: #000000 !important;
            color-scheme: light !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 210mm !important;
            height: auto !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        img {
            max-width: 100% !important;
            display: inline-block !important;
            visibility: visible !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        img.kpp-paraf-img {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            width: auto !important;
            max-width: 70px !important;
            max-height: 48px !important;
            object-fit: contain !important;
        }
        .printable-a4-page {
            background: #FFFFFF !important;
            color: #000000 !important;
            border: none !important;
            box-shadow: none !important;
            padding: 2.5cm 2cm !important;
            margin: 0 auto !important;
            width: 210mm !important;
            height: 297mm !important;
            max-height: 297mm !important;
            box-sizing: border-box !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            page-break-after: always !important;
            break-after: page !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            overflow: hidden !important;
            position: relative !important;
        }
        .printable-a4-page.printable-dispo-page {
            padding: 2.2cm !important;
            display: block !important;
            background: #FFFFFF !important;
        }
        .printable-a4-page.printable-dispo-page > div {
            border: 1.5px solid #000000 !important;
            padding: 12px 16px !important;
            box-sizing: border-box !important;
            background: #FFFFFF !important;
            height: 100% !important;
            min-height: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
        }
        .printable-a4-page.printable-surat-page {
            padding: 2.5cm !important;
        }
        .printable-a4-page.printable-lampiran-page {
            padding: 0 !important;
            border: none !important;
            box-shadow: none !important;
            overflow: hidden !important;
            width: 210mm !important;
            height: 297mm !important;
            background: #ffffff !important;
            display: block !important;
        }
        .printable-a4-page.printable-lampiran-page img,
        .printable-a4-page.printable-lampiran-page .pdf-img-item {
            width: 100% !important;
            height: 100% !important;
            max-width: 100% !important;
            max-height: 297mm !important;
            object-fit: contain !important;
            display: block !important;
            margin: 0 auto !important;
            border: none !important;
            box-shadow: none !important;
            transform: none !important;
        }
        .printable-a4-page:last-child {
            page-break-after: avoid !important;
            break-after: avoid !important;
        }
        .a4-paper-preview {
            background: #FFFFFF !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
            max-width: 100% !important;
            height: auto !important;
            min-height: 0 !important;
            box-sizing: border-box !important;
        }
        #pvNomorBox, #pvTtdBox, #pvTtdBoxSingle, #pvParafBox, #pvKepstaTtdBox {
            border: none !important;
            background: transparent !important;
            box-shadow: none !important;
            padding: 0 !important;
        }
        #pvNomorPlaceholder, #pvTtdPlaceholder, #pvTtdPlaceholderSingle, #pvParafPlaceholder, #revKepstaTtdPlaceholder, .tu-ttd-placeholder-text {
            display: none !important;
        }
        .no-print {
            display: none !important;
        }
        .pdf-canvas-item {
            display: none !important;
        }
        .pdf-img-item {
            display: block !important;
            visibility: visible !important;
            width: 100% !important;
            height: auto !important;
        }
        @media print {
            :root {
                color-scheme: light !important;
            }
            html, body {
                background: #ffffff !important;
                color: #000000 !important;
                color-scheme: light !important;
            }
            img {
                display: inline-block !important;
                visibility: visible !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .printable-a4-page {
                border: none !important;
                box-shadow: none !important;
                background: #ffffff !important;
                color: #000000 !important;
            }
            .printable-a4-page.printable-dispo-page {
                padding: 2.2cm !important;
            }
            .printable-a4-page.printable-dispo-page > div {
                border: 1.5px solid #000000 !important;
                padding: 12px 16px !important;
                background: #ffffff !important;
            }
            .a4-paper-preview {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                background: #ffffff !important;
            }
            #pvNomorBox, #pvTtdBox, #pvTtdBoxSingle, #pvParafBox, #pvKepstaTtdBox {
                border: none !important;
                background: transparent !important;
                box-shadow: none !important;
            }
            #pvNomorPlaceholder, #pvTtdPlaceholder, #pvTtdPlaceholderSingle, #pvParafPlaceholder, #revKepstaTtdPlaceholder {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    ${papersHtml}
    <script>
        function doPrint() {
            const imgs = Array.from(document.images);
            let loaded = 0;
            const total = imgs.length;
            function run() {
                setTimeout(function() {
                    window.focus();
                    window.print();
                }, 250);
            }
            if (total === 0) {
                run();
            } else {
                let done = false;
                function check() {
                    if (!done) {
                        done = true;
                        run();
                    }
                }
                imgs.forEach(function(img) {
                    if (img.complete && img.naturalWidth !== 0) {
                        loaded++;
                    } else {
                        img.onload = img.onerror = function() {
                            loaded++;
                            if (loaded >= total) check();
                        };
                    }
                });
                if (loaded >= total) check();
                else setTimeout(check, 1500);
            }
        }
        window.onload = doPrint;
        window.onafterprint = function() { window.close(); };
    <\/script>
</body>
</html>`);
    printWin.document.close();
}
</script>
