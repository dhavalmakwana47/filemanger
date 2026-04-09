<!-- PDF Viewer Container -->
<div class="pdf-viewer-container position-relative p-3 bg-white rounded-3 shadow-sm">

    <!-- Password Modal -->
    <div id="password-modal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.85);z-index:9999;align-items:center;justify-content:center;">
        <div class="bg-white p-4 rounded-3 shadow" style="max-width:400px;width:90%;">
            <h5 class="mb-3">Password Required</h5>
            <p class="mb-3">This PDF is password protected. Please enter the password:</p>
            <div id="password-error" class="alert alert-danger" style="display:none;">Incorrect password. Please try again.</div>
            <input type="password" id="pdf-password" class="form-control mb-3" placeholder="Enter password">
            <div class="d-flex gap-2">
                <button id="submit-password" class="btn btn-primary">Submit</button>
                <button id="cancel-password" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Print Warning Overlay -->
    <div id="print-warning" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.85);z-index:9999;color:#fff;align-items:center;justify-content:center;font-size:2rem;text-align:center;">
        Printing is disabled for this document.<br>Please contact the administrator for access.
    </div>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2 flex-wrap gap-2">
        <div class="d-flex align-items-center">
            <img src="{{ $systemSettings['horizontal_logo'] ?? asset('repository.png') }}" alt="Logo" class="me-2" style="height:40px;">
            <span class="fs-4 fw-bold text-primary">SmartView</span>
        </div>

        <!-- Search Bar -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="input-group" style="width:300px;">
                <input type="text" id="search-input" class="form-control form-control-sm" placeholder="Search in PDF…">
                <button id="search-prev" class="btn btn-outline-secondary btn-sm" title="Previous (Shift+Enter)" disabled>
                    <i class="bi bi-chevron-up"></i>
                </button>
                <button id="search-next" class="btn btn-outline-secondary btn-sm" title="Next (Enter)" disabled>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <button id="search-clear" class="btn btn-outline-danger btn-sm" title="Clear" style="display:none;">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <span id="search-status" class="text-muted small"></span>
            <!-- OCR progress shown only for image PDFs -->
            <span id="ocr-status" class="text-info small" style="display:none;">
                <span class="spinner-border spinner-border-sm me-1"></span>
                <span id="ocr-status-text">Running OCR…</span>
            </span>
        </div>

        <span id="page-info" class="badge bg-secondary fs-6"></span>
    </div>

    <!-- PDF Canvas + Highlight Overlay -->
    <div class="position-relative" id="pdf-wrapper">
        <canvas id="pdf-canvas" class="w-100 border rounded-3 shadow-sm"></canvas>
        <div id="text-layer" style="position:absolute;top:0;left:0;pointer-events:none;overflow:hidden;"></div>

        <div id="pdf-loader" style="display:none;position:absolute;inset:0;z-index:10;background:rgba(255,255,255,0.6);align-items:center;justify-content:center;">
            <div class="spinner-border text-primary" role="status"></div>
        </div>

        <div class="pdf-controls d-flex justify-content-center gap-3 mt-3">
            <button id="prev-page" class="btn btn-outline-primary rounded-pill px-4" disabled>
                <i class="bi bi-chevron-left"></i> Prev
            </button>
            <button id="next-page" class="btn btn-outline-primary rounded-pill px-4">
                Next <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.min.js"></script>

<script>
const pdfUrl        = "{{ route($routeName, ['id' => $id]) }}";
const scale         = 1.4;
const loader        = document.getElementById('pdf-loader');
const textLayerDiv  = document.getElementById('text-layer');
const searchInput   = document.getElementById('search-input');
const searchStatus  = document.getElementById('search-status');
const ocrStatus     = document.getElementById('ocr-status');
const ocrStatusText = document.getElementById('ocr-status-text');
const btnPrev       = document.getElementById('search-prev');
const btnNext       = document.getElementById('search-next');
const btnClear      = document.getElementById('search-clear');

let pdfDoc          = null;
let currentPage     = 1;
let navigationSetup = false;

// ── Search state ──────────────────────────────────────────────
// Two match formats:
//   text-PDF  : { page, charOffset, length, charMap, items }
//   image-PDF : { page, type:'ocr', boxes:[{x,y,w,h,isActive}] }
let allMatches        = [];
let currentMatchIndex = -1;

// ── Cache ─────────────────────────────────────────────────────
// pageTextCache[p] = { type:'text', items } | { type:'ocr', words:[{text,x,y,w,h}] }
let pageTextCache = {};
let isImagePdf    = false;   // set after first page check
let tesseractWorker = null;

/* ═══════════════════════════════════════════════════════════════
   LOADER
═══════════════════════════════════════════════════════════════ */
const showLoader = () => loader.style.display = 'flex';
const hideLoader = () => loader.style.display = 'none';
showLoader();

/* ═══════════════════════════════════════════════════════════════
   LOAD PDF
═══════════════════════════════════════════════════════════════ */
function loadPDF(src) {
    pdfjsLib.getDocument({ url: src, password: window.pdfPassword || '' })
        .promise.then(async pdf => {
            pdfDoc = pdf;
            updatePageInfo();
            await renderPage(currentPage);
            if (!navigationSetup) { setupNavigation(); navigationSetup = true; }
            hideLoader();
            // Detect image-PDF on first page
            await detectAndInitOcr();
        }).catch(err => {
            if (err.name === 'PasswordException' || err.message.includes('password')) {
                if (window.pdfPassword) document.getElementById('password-error').style.display = 'block';
                showPasswordModal();
            } else {
                document.body.innerHTML += `<div class="alert alert-danger mt-3">Error: ${err.message}</div>`;
            }
            hideLoader();
        });
}

/* ═══════════════════════════════════════════════════════════════
   DETECT IMAGE PDF → init Tesseract if needed
═══════════════════════════════════════════════════════════════ */
async function detectAndInitOcr() {
    const page    = await pdfDoc.getPage(1);
    const content = await page.getTextContent();
    const hasText = content.items.some(i => i.str.trim().length > 0);

    if (!hasText) {
        isImagePdf = true;
        await initTesseract();
        await buildOcrCacheAllPages();
    }
}

/* ═══════════════════════════════════════════════════════════════
   TESSERACT INIT (lazy, loaded only for image PDFs)
═══════════════════════════════════════════════════════════════ */
async function initTesseract() {
    if (tesseractWorker) return;

    // Load Tesseract.js from CDN
    await loadScript('https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js');

    tesseractWorker = await Tesseract.createWorker('eng', 1, {
        logger: m => {
            if (m.status === 'recognizing text') {
                ocrStatusText.textContent = `OCR: ${Math.round(m.progress * 100)}%`;
            }
        }
    });
}

function loadScript(src) {
    return new Promise((resolve, reject) => {
        if (document.querySelector(`script[src="${src}"]`)) { resolve(); return; }
        const s = document.createElement('script');
        s.src = src; s.onload = resolve; s.onerror = reject;
        document.head.appendChild(s);
    });
}

/* ═══════════════════════════════════════════════════════════════
   BUILD OCR CACHE FOR ALL PAGES
═══════════════════════════════════════════════════════════════ */
async function buildOcrCacheAllPages() {
    ocrStatus.style.display = 'inline-flex';
    searchInput.disabled    = true;

    for (let p = 1; p <= pdfDoc.numPages; p++) {
        ocrStatusText.textContent = `OCR page ${p} / ${pdfDoc.numPages}…`;
        await ocrPage(p);
    }

    ocrStatus.style.display = 'none';
    searchInput.disabled    = false;
    searchInput.placeholder = 'Search in PDF…';
}

/* ═══════════════════════════════════════════════════════════════
   OCR A SINGLE PAGE → returns word array cached in pageTextCache
═══════════════════════════════════════════════════════════════ */
async function ocrPage(pageNum) {
    if (pageTextCache[pageNum]) return pageTextCache[pageNum];

    // Render page to an offscreen canvas at OCR-friendly scale (2×)
    const ocrScale  = 2;
    const page      = await pdfDoc.getPage(pageNum);
    const viewport  = page.getViewport({ scale: ocrScale });
    const offscreen = document.createElement('canvas');
    offscreen.width  = viewport.width;
    offscreen.height = viewport.height;
    await page.render({ canvasContext: offscreen.getContext('2d'), viewport }).promise;

    // Run Tesseract on the offscreen canvas
    const { data } = await tesseractWorker.recognize(offscreen);

    // Build word list with normalised coordinates (0–1 relative to page)
    const words = data.words.map(w => ({
        text : w.text.toLowerCase(),
        // Store as fractions of the OCR canvas so we can scale to any render size
        x    : w.bbox.x0 / offscreen.width,
        y    : w.bbox.y0 / offscreen.height,
        w    : (w.bbox.x1 - w.bbox.x0) / offscreen.width,
        h    : (w.bbox.y1 - w.bbox.y0) / offscreen.height,
    })).filter(w => w.text.trim().length > 0);

    pageTextCache[pageNum] = { type: 'ocr', words };
    return pageTextCache[pageNum];
}

/* ═══════════════════════════════════════════════════════════════
   RENDER PAGE → resolves with viewport
═══════════════════════════════════════════════════════════════ */
function renderPage(pageNum) {
    return new Promise(resolve => {
        showLoader();
        document.getElementById('prev-page').disabled =
        document.getElementById('next-page').disabled = true;
        textLayerDiv.innerHTML = '';

        pdfDoc.getPage(pageNum).then(page => {
            const canvas   = document.getElementById('pdf-canvas');
            const ctx      = canvas.getContext('2d');
            const viewport = page.getViewport({ scale });
            canvas.height  = viewport.height;
            canvas.width   = viewport.width;
            textLayerDiv.style.width  = viewport.width  + 'px';
            textLayerDiv.style.height = viewport.height + 'px';

            page.render({ canvasContext: ctx, viewport }).promise.then(() => {
                document.getElementById('prev-page').disabled = pageNum === 1;
                document.getElementById('next-page').disabled = pageNum === pdfDoc.numPages;
                hideLoader();
                resolve(viewport);
            });
        });
    });
}

/* ═══════════════════════════════════════════════════════════════
   NAVIGATION
═══════════════════════════════════════════════════════════════ */
function setupNavigation() {
    document.getElementById('next-page').onclick = async () => {
        if (currentPage < pdfDoc.numPages) {
            currentPage++;
            updatePageInfo();
            const vp = await renderPage(currentPage);
            if (allMatches.length) await drawHighlightsForPage(currentPage, vp);
        }
    };
    document.getElementById('prev-page').onclick = async () => {
        if (currentPage > 1) {
            currentPage--;
            updatePageInfo();
            const vp = await renderPage(currentPage);
            if (allMatches.length) await drawHighlightsForPage(currentPage, vp);
        }
    };
}

function updatePageInfo() {
    document.getElementById('page-info').textContent = `Page ${currentPage} of ${pdfDoc.numPages}`;
}

/* ═══════════════════════════════════════════════════════════════
   GET PAGE TEXT (text PDF path, cached)
═══════════════════════════════════════════════════════════════ */
async function getPageTextItems(pageNum) {
    if (pageTextCache[pageNum]) return pageTextCache[pageNum];
    const page    = await pdfDoc.getPage(pageNum);
    const content = await page.getTextContent();
    pageTextCache[pageNum] = { type: 'text', items: content.items };
    return pageTextCache[pageNum];
}

/* ═══════════════════════════════════════════════════════════════
   SEARCH ACROSS ALL PAGES
═══════════════════════════════════════════════════════════════ */
async function runSearch(query) {
    allMatches        = [];
    currentMatchIndex = -1;
    textLayerDiv.innerHTML   = '';
    searchStatus.textContent = '';

    if (!query || !pdfDoc) { updateSearchUI(); return; }

    const q = query.toLowerCase();

    for (let p = 1; p <= pdfDoc.numPages; p++) {
        if (isImagePdf) {
            await searchOcrPage(p, q);
        } else {
            await searchTextPage(p, q);
        }
    }

    if (allMatches.length) {
        await goToMatch(0);
    } else {
        searchStatus.textContent = 'No results found';
    }
    updateSearchUI();
}

/* ── Text-PDF search ── */
async function searchTextPage(pageNum, q) {
    const { items } = await getPageTextItems(pageNum);
    let pageStr = '', charMap = [];
    items.forEach((item, idx) => {
        // Add a separator between text items so exact-word matching works across item boundaries.
        if (pageStr.length) {
            pageStr += ' ';
            charMap.push(null);
        }
        for (let c = 0; c < item.str.length; c++) charMap.push({ itemIndex: idx, charIndex: c });
        pageStr += item.str;
    });
    const lower = pageStr.toLowerCase();
    const matcher = buildExactWordRegex(q);
    let m;
    while ((m = matcher.exec(lower)) !== null) {
        const start = m.index + (m[1] ? m[1].length : 0);
        allMatches.push({ page: pageNum, type: 'text', charOffset: start, length: q.length, charMap, items });
        // Safety for zero-length progress (should not happen, but avoids infinite loops).
        if (matcher.lastIndex <= m.index) matcher.lastIndex = m.index + 1;
    }
}

/* ── OCR-PDF search ── */
async function searchOcrPage(pageNum, q) {
    const cached = pageTextCache[pageNum];
    if (!cached || cached.type !== 'ocr') return;

    // Find consecutive words whose joined text contains the query
    const words    = cached.words;
    const fullText = words.map(w => w.text).join(' ');
    const matcher = buildExactWordRegex(q);
    let m;
    while ((m = matcher.exec(fullText)) !== null) {
        const pos = m.index + (m[1] ? m[1].length : 0);
        // Map character position back to word bounding boxes
        const boxes = getWordBoxesForMatch(words, fullText, pos, q.length);
        if (boxes.length) {
            allMatches.push({ page: pageNum, type: 'ocr', boxes });
        }
        if (matcher.lastIndex <= m.index) matcher.lastIndex = m.index + 1;
    }
}

function buildExactWordRegex(query) {
    const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    // Word boundary using alphanumeric chars so matches stay exact even with punctuation.
    return new RegExp(`(^|[^a-z0-9])(${escaped})(?=[^a-z0-9]|$)`, 'g');
}

/* Map a char range in the joined word string back to word bbox list */
function getWordBoxesForMatch(words, fullText, start, length) {
    const end   = start + length;
    const boxes = [];
    let cursor  = 0;
    for (const w of words) {
        const wStart = cursor;
        const wEnd   = cursor + w.text.length;
        if (wEnd > start && wStart < end) boxes.push(w);
        cursor += w.text.length + 1; // +1 for the space separator
        if (cursor > end) break;
    }
    return boxes;
}

/* ═══════════════════════════════════════════════════════════════
   NAVIGATE TO MATCH
═══════════════════════════════════════════════════════════════ */
async function goToMatch(index) {
    if (!allMatches.length) return;
    currentMatchIndex = (index + allMatches.length) % allMatches.length;
    const match = allMatches[currentMatchIndex];

    searchStatus.textContent = `${currentMatchIndex + 1} / ${allMatches.length}`;
    updateSearchUI();

    if (currentPage !== match.page) {
        currentPage = match.page;
        updatePageInfo();
    }
    const vp = await renderPage(currentPage);
    await drawHighlightsForPage(currentPage, vp);
}

/* ═══════════════════════════════════════════════════════════════
   DRAW HIGHLIGHTS
═══════════════════════════════════════════════════════════════ */
async function drawHighlightsForPage(pageNum, viewport) {
    textLayerDiv.innerHTML = '';
    if (!viewport) {
        const page = await pdfDoc.getPage(pageNum);
        viewport   = page.getViewport({ scale });
    }
    textLayerDiv.style.width  = viewport.width  + 'px';
    textLayerDiv.style.height = viewport.height + 'px';

    const pageMatches = allMatches.filter(m => m.page === pageNum);
    if (!pageMatches.length) return;

    pageMatches.forEach((match, mi) => {
        const isActive = allMatches.indexOf(match) === currentMatchIndex;
        const color    = isActive ? 'rgba(255,165,0,0.6)' : 'rgba(255,255,0,0.4)';

        if (match.type === 'text') {
            drawTextHighlights(match, viewport, color);
        } else {
            drawOcrHighlights(match, viewport, color);
        }
    });
}

function drawTextHighlights(match, viewport, color) {
    const { charOffset, length, charMap, items } = match;
    const coveredRanges = new Map();
    for (let c = charOffset; c < charOffset + length; c++) {
        const cm = charMap[c];
        if (!cm) continue;

        const prev = coveredRanges.get(cm.itemIndex);
        if (!prev) {
            coveredRanges.set(cm.itemIndex, { start: cm.charIndex, end: cm.charIndex + 1 });
        } else {
            prev.start = Math.min(prev.start, cm.charIndex);
            prev.end   = Math.max(prev.end, cm.charIndex + 1);
        }
    }
    coveredRanges.forEach((range, idx) => {
        const item  = items[idx];
        if (!item || !item.str || !item.str.length) return;

        const tx    = pdfjsLib.Util.transform(viewport.transform, item.transform);
        const fontH = Math.sqrt(tx[2] * tx[2] + tx[3] * tx[3]);
        const itemWidth = item.width * scale;
        const charW     = itemWidth / item.str.length;
        const x         = tx[4] + (range.start * charW);
        const w         = Math.max((range.end - range.start) * charW, 2);
        appendHighlight(x, tx[5] - fontH, w, fontH * 1.2, color);
    });
}

function drawOcrHighlights(match, viewport, color) {
    match.boxes.forEach(box => {
        // box coords are 0-1 fractions → multiply by viewport pixel size
        const x = box.x * viewport.width;
        const y = box.y * viewport.height;
        const w = box.w * viewport.width;
        const h = box.h * viewport.height;
        appendHighlight(x, y, w, h, color);
    });
}

function appendHighlight(x, y, w, h, color) {
    const el = document.createElement('div');
    el.style.cssText = `position:absolute;left:${x}px;top:${y}px;width:${w}px;height:${h}px;background:${color};border-radius:2px;pointer-events:none;`;
    textLayerDiv.appendChild(el);
}

/* ═══════════════════════════════════════════════════════════════
   SEARCH UI
═══════════════════════════════════════════════════════════════ */
function updateSearchUI() {
    const has = allMatches.length > 0;
    btnPrev.disabled = btnNext.disabled = !has;
    btnClear.style.display = searchInput.value ? 'inline-flex' : 'none';
}

let debounce;
searchInput.addEventListener('input', () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => runSearch(searchInput.value.trim()), 400);
    updateSearchUI();
});
searchInput.addEventListener('keydown', e => {
    if (e.key === 'Enter') e.shiftKey ? goToMatch(currentMatchIndex - 1) : goToMatch(currentMatchIndex + 1);
    if (e.key === 'Escape') clearSearch();
});
btnNext.addEventListener('click',  () => goToMatch(currentMatchIndex + 1));
btnPrev.addEventListener('click',  () => goToMatch(currentMatchIndex - 1));
btnClear.addEventListener('click', clearSearch);

function clearSearch() {
    searchInput.value = ''; allMatches = []; currentMatchIndex = -1;
    searchStatus.textContent = ''; textLayerDiv.innerHTML = '';
    updateSearchUI();
}

/* ═══════════════════════════════════════════════════════════════
   PASSWORD MODAL
═══════════════════════════════════════════════════════════════ */
function showPasswordModal() { document.getElementById('password-modal').style.display = 'flex'; document.getElementById('pdf-password').focus(); }
function hidePasswordModal() { document.getElementById('password-modal').style.display = 'none'; document.getElementById('password-error').style.display = 'none'; document.getElementById('pdf-password').value = ''; }
document.getElementById('submit-password').onclick = () => {
    const pw = document.getElementById('pdf-password').value;
    if (pw) { window.pdfPassword = pw; hidePasswordModal(); showLoader(); loadPDF(pdfUrl); }
};
document.getElementById('cancel-password').onclick = () => {
    hidePasswordModal();
    document.body.innerHTML += '<div class="alert alert-warning mt-3">PDF viewing cancelled.</div>';
};
document.getElementById('pdf-password').onkeypress = e => { if (e.key === 'Enter') document.getElementById('submit-password').click(); };

/* ═══════════════════════════════════════════════════════════════
   SECURITY
═══════════════════════════════════════════════════════════════ */
document.addEventListener('contextmenu', e => { if (e.target.tagName === 'CANVAS') e.preventDefault(); });
document.addEventListener('keydown', e => {
    if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && 'Ii'.includes(e.key)) ||
        (e.ctrlKey && 'Uu'.includes(e.key)) || (e.ctrlKey && 'Pp'.includes(e.key))) {
        e.preventDefault();
        if ('Pp'.includes(e.key)) showPrintWarning();
    }
});
function showPrintWarning() {
    const w = document.getElementById('print-warning');
    if (w) { w.style.display = 'flex'; setTimeout(() => w.style.display = 'none', 4000); }
}
window.onbeforeprint = () => { showPrintWarning(); setTimeout(() => window.stop(), 100); return false; };

/* ── Boot ── */
loadPDF(pdfUrl);
</script>

<style>
    .pdf-viewer-container { max-width: 900px; margin: auto; }
    .pdf-controls button  { transition: all 0.2s ease; }
    .pdf-controls button:hover { background-color: #0d6efd; color: white; }
    #text-layer { position: absolute; top: 0; left: 0; pointer-events: none; }
    #search-input:focus { box-shadow: 0 0 0 .2rem rgba(13,110,253,.25); }
    @media (max-width: 576px) {
        .pdf-controls { flex-direction: column; }
        .pdf-controls button { width: 100%; }
        #search-input { width: 160px !important; }
    }
    @media print { body { display: none !important; } }
</style>
