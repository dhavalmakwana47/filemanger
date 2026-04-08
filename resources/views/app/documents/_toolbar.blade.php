<div id="toolbar">
    <span class="ql-formats">
        <select class="ql-font">
            <option value="arial" selected>Arial</option>
            <option value="times">Times New Roman</option>
            <option value="courier">Courier New</option>
            <option value="georgia">Georgia</option>
            <option value="verdana">Verdana</option>
        </select>
    </span>
    <span class="ql-formats">
        <select class="ql-size">
            <option value="8pt">8</option>
            <option value="9pt">9</option>
            <option value="10pt">10</option>
            <option value="11pt" selected>11</option>
            <option value="12pt">12</option>
            <option value="14pt">14</option>
            <option value="16pt">16</option>
            <option value="18pt">18</option>
            <option value="24pt">24</option>
            <option value="36pt">36</option>
            <option value="48pt">48</option>
            <option value="72pt">72</option>
        </select>
    </span>
    <span class="ql-formats">
        <select class="ql-header">
            <option value="1">Heading 1</option>
            <option value="2">Heading 2</option>
            <option value="3">Heading 3</option>
            <option selected>Normal</option>
        </select>
    </span>
    <span class="ql-formats">
        <button class="ql-bold" title="Bold (Ctrl+B)"></button>
        <button class="ql-italic" title="Italic (Ctrl+I)"></button>
        <button class="ql-underline" title="Underline (Ctrl+U)"></button>
        <button class="ql-strike" title="Strikethrough"></button>
    </span>
    <span class="ql-formats">
        <select class="ql-color" title="Text color"></select>
        <select class="ql-background" title="Highlight color"></select>
    </span>
    <span class="ql-formats">
        <select class="ql-align" title="Alignment">
            <option selected></option>
            <option value="center"></option>
            <option value="right"></option>
            <option value="justify"></option>
        </select>
    </span>
    <span class="ql-formats">
        <button class="ql-list" value="ordered" title="Numbered list"></button>
        <button class="ql-list" value="bullet" title="Bullet list"></button>
        <button class="ql-indent" value="-1" title="Decrease indent"></button>
        <button class="ql-indent" value="+1" title="Increase indent"></button>
    </span>
    <span class="ql-formats">
        <button class="ql-link" title="Insert link"></button>
        <button class="ql-image" title="Insert image"></button>
        <button class="ql-blockquote" title="Blockquote"></button>
        <button class="ql-code-block" title="Code block"></button>
    </span>
    <span class="ql-formats">
        <button class="ql-script" value="sub" title="Subscript"></button>
        <button class="ql-script" value="super" title="Superscript"></button>
    </span>
    <span class="ql-formats">
        <button class="ql-clean" title="Clear formatting"></button>
    </span>
    <span class="ql-formats">
        <button title="Undo" onclick="quill.history.undo(); return false;">
            <i class="bi bi-arrow-counterclockwise" style="font-size:14px"></i>
        </button>
        <button title="Redo" onclick="quill.history.redo(); return false;">
            <i class="bi bi-arrow-clockwise" style="font-size:14px"></i>
        </button>
    </span>
</div>
