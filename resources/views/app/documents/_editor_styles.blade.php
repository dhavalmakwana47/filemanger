<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
    .gdocs-wrap { background: #f8f9fa; min-height: 100vh; display: flex; flex-direction: column; }

    /* ── Top bar ── */
    .gdocs-topbar {
        background: #fff;
        border-bottom: 1px solid #e0e0e0;
        padding: 8px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: 0 1px 3px rgba(0,0,0,.08);
    }
    .gdocs-back {
        color: #5f6368; font-size: 20px; text-decoration: none;
        width: 36px; height: 36px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .gdocs-back:hover { background: #f1f3f4; color: #202124; }
    .gdocs-icon { color: #1a73e8; font-size: 30px; flex-shrink: 0; }
    .gdocs-title-input {
        border: none; outline: none;
        font-size: 18px; font-weight: 400; color: #202124;
        flex: 1; background: transparent;
        padding: 5px 8px; border-radius: 4px; min-width: 0;
    }
    .gdocs-title-input:hover { background: #f1f3f4; }
    .gdocs-title-input:focus { background: #fff; box-shadow: 0 0 0 2px #1a73e8; }
    .gdocs-title-input.is-invalid { box-shadow: 0 0 0 2px #d93025; }
    .gdocs-meta { font-size: 12px; color: #5f6368; white-space: nowrap; flex-shrink: 0; }
    .gdocs-save-btn {
        background: #1a73e8; color: #fff; border: none;
        padding: 8px 22px; border-radius: 4px;
        font-size: 14px; font-weight: 500; cursor: pointer;
        display: flex; align-items: center; gap: 6px; flex-shrink: 0;
        transition: background .2s;
    }
    .gdocs-save-btn:hover { background: #1557b0; }
    .gdocs-delete-btn {
        background: none; color: #d93025; border: 1px solid #d93025;
        padding: 7px 14px; border-radius: 4px;
        font-size: 14px; cursor: pointer; flex-shrink: 0;
        display: flex; align-items: center; gap: 6px;
        transition: background .2s;
    }
    .gdocs-delete-btn:hover { background: #fce8e6; }
    .error-msg { color: #d93025; font-size: 12px; }

    /* ── Quill toolbar (sticky below topbar) ── */
    #toolbar {
        position: sticky;
        top: 57px;
        z-index: 90;
        background: #fff;
        border: none !important;
        border-bottom: 1px solid #e0e0e0 !important;
        padding: 6px 40px !important;
        display: flex;
        flex-wrap: wrap;
        gap: 2px;
        box-shadow: 0 1px 2px rgba(0,0,0,.06);
    }
    #toolbar .ql-formats { margin-right: 8px !important; }
    #toolbar button, #toolbar .ql-picker { border-radius: 3px !important; }
    #toolbar button:hover, #toolbar .ql-picker-label:hover {
        background: #f1f3f4 !important;
        color: #1a73e8 !important;
    }
    #toolbar button.ql-active, #toolbar .ql-picker-label.ql-active { color: #1a73e8 !important; }
    .ql-picker-label { border: none !important; }
    .ql-snow .ql-picker.ql-font { width: 110px; }
    .ql-snow .ql-picker.ql-size { width: 70px; }
    .ql-snow .ql-picker.ql-header { width: 100px; }

    /* ── Editor page ── */
    .gdocs-editor-area {
        flex: 1;
        display: flex;
        justify-content: center;
        padding: 32px 16px 80px;
    }
    .gdocs-page {
        background: #fff;
        width: 816px;
        min-height: 1056px;
        box-shadow: 0 1px 4px rgba(0,0,0,.18), 0 4px 16px rgba(0,0,0,.06);
        border-radius: 2px;
    }
    #editor {
        min-height: 900px;
        font-family: 'Arial', sans-serif;
        font-size: 11pt;
        line-height: 1.6;
        color: #202124;
        padding: 72px 96px !important;
        border: none !important;
    }
    .ql-container.ql-snow { border: none !important; }
    .ql-editor { padding: 0 !important; }
    .ql-editor p { margin-bottom: 6px; }

    /* Font family options */
    .ql-font-arial   { font-family: Arial, sans-serif; }
    .ql-font-times   { font-family: 'Times New Roman', serif; }
    .ql-font-courier { font-family: 'Courier New', monospace; }
    .ql-font-georgia { font-family: Georgia, serif; }
    .ql-font-verdana { font-family: Verdana, sans-serif; }

    @media (max-width: 900px) {
        .gdocs-page { width: 100%; }
        #editor { padding: 32px 24px !important; }
        #toolbar { padding: 6px 12px !important; }
    }
</style>
