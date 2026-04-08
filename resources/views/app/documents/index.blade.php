@extends('app.layouts.layout')

@push('styles')
<style>
    .docs-home { background: #f8f9fa; min-height: 100vh; }

    /* Top banner */
    .docs-banner {
        background: #fff;
        border-bottom: 1px solid #e0e0e0;
        padding: 20px 40px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .docs-banner h1 {
        font-size: 18px;
        font-weight: 500;
        color: #202124;
        margin: 0;
    }

    /* New doc button */
    .new-doc-area {
        background: #fff;
        border-bottom: 1px solid #e0e0e0;
        padding: 24px 40px;
    }
    .new-doc-area h6 {
        font-size: 12px;
        color: #5f6368;
        font-weight: 500;
        margin-bottom: 14px;
        text-transform: uppercase;
        letter-spacing: .5px;
    }
    .new-doc-card {
        width: 130px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    .new-doc-card:hover .new-doc-thumb { border-color: #1a73e8; }
    .new-doc-thumb {
        width: 130px;
        height: 168px;
        border: 2px solid #dadce0;
        border-radius: 4px;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: border-color .2s;
        position: relative;
        overflow: hidden;
    }
    .new-doc-thumb .plus-icon {
        font-size: 40px;
        color: #1a73e8;
    }
    .new-doc-thumb .doc-lines {
        position: absolute;
        bottom: 0; left: 0; right: 0;
        padding: 10px 12px;
    }
    .doc-line { height: 5px; background: #e8eaed; border-radius: 2px; margin-bottom: 5px; }
    .new-doc-label {
        font-size: 13px;
        color: #202124;
        margin-top: 8px;
        text-align: center;
    }

    /* Recent docs */
    .docs-recent { padding: 28px 40px; }
    .docs-recent h6 {
        font-size: 12px;
        color: #5f6368;
        font-weight: 500;
        margin-bottom: 16px;
        text-transform: uppercase;
        letter-spacing: .5px;
    }
    .doc-card {
        width: 160px;
        cursor: pointer;
        display: inline-block;
        margin-right: 16px;
        margin-bottom: 20px;
        vertical-align: top;
        text-decoration: none;
    }
    .doc-card-thumb {
        width: 160px;
        height: 207px;
        border: 1px solid #dadce0;
        border-radius: 4px;
        background: #fff;
        overflow: hidden;
        transition: box-shadow .2s;
        padding: 14px 16px;
        position: relative;
    }
    .doc-card:hover .doc-card-thumb {
        box-shadow: 0 2px 8px rgba(0,0,0,.15);
        border-color: #1a73e8;
    }
    .doc-card-thumb .doc-title-preview {
        font-size: 11px;
        font-weight: 600;
        color: #202124;
        margin-bottom: 8px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .doc-card-thumb .doc-content-preview {
        font-size: 9px;
        color: #5f6368;
        line-height: 1.5;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 10;
        -webkit-box-orient: vertical;
    }
    .doc-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 8px;
        padding: 0 2px;
    }
    .doc-card-info { flex: 1; overflow: hidden; }
    .doc-card-name {
        font-size: 13px;
        color: #202124;
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .doc-card-date { font-size: 11px; color: #5f6368; }
    .doc-card-menu { position: relative; }
    .doc-menu-btn {
        background: none;border: none; color: #5f6368;
        padding: 4px 6px; border-radius: 50%; cursor: pointer;
        font-size: 16px; line-height: 1;
    }
    .doc-menu-btn:hover { background: #f1f3f4; }
    .doc-dropdown {
        display: none;
        position: absolute;
        right: 0; top: 28px;
        background: #fff;
        border: 1px solid #dadce0;
        border-radius: 4px;
        box-shadow: 0 2px 10px rgba(0,0,0,.15);
        z-index: 100;
        min-width: 140px;
    }
    .doc-dropdown.show { display: block; }
    .doc-dropdown a, .doc-dropdown button {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 16px;
        font-size: 13px; color: #202124;
        text-decoration: none; background: none; border: none;
        width: 100%; text-align: left; cursor: pointer;
    }
    .doc-dropdown a:hover, .doc-dropdown button:hover { background: #f1f3f4; }
    .doc-dropdown .divider { border-top: 1px solid #e0e0e0; margin: 4px 0; }

    /* Empty state */
    .empty-state { text-align: center; padding: 60px 20px; color: #5f6368; }
    .empty-state i { font-size: 64px; color: #dadce0; display: block; margin-bottom: 16px; }
    .empty-state p { font-size: 15px; }
</style>
@endpush

@push('scripts')
<script>
    // Toggle dropdown menus
    document.addEventListener('click', function(e) {
        document.querySelectorAll('.doc-dropdown').forEach(d => d.classList.remove('show'));
        const btn = e.target.closest('.doc-menu-btn');
        if (btn) {
            e.stopPropagation();
            btn.nextElementSibling.classList.toggle('show');
        }
    });

    // Share button
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.share-btn');
        if (!btn) return;
        e.stopPropagation();

        const url  = btn.dataset.url;
        const input = document.getElementById('share-link-input');
        const modal = new bootstrap.Modal(document.getElementById('shareModal'));

        input.value = 'Generating...';
        modal.show();

        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => { input.value = data.url; });
    });

    function copyShareLink() {
        const input = document.getElementById('share-link-input');
        navigator.clipboard.writeText(input.value).then(() => {
            const btn = document.getElementById('copy-btn');
            btn.innerHTML = '<i class="bi bi-check2"></i> Copied!';
            setTimeout(() => btn.innerHTML = '<i class="bi bi-clipboard"></i> Copy', 2000);
        });
    }
</script>
@endpush

{{-- Share Modal --}}
@push('styles')
<style>
    #shareModal .modal-header { border-bottom: none; padding-bottom: 0; }
    #shareModal .share-url-wrap { display: flex; gap: 8px; }
    #shareModal .share-url-wrap input { font-size: 13px; color: #5f6368; background: #f8f9fa; }
</style>
@endpush

@section('content')
<div class="docs-home">

    {{-- Top banner --}}
    <div class="docs-banner">
        <h1><i class="bi bi-file-earmark-text-fill me-2" style="color:#1a73e8"></i> Docs</h1>
    </div>

    {{-- Start new document --}}
    <div class="new-doc-area">
        <h6>Start a new document</h6>
        <a href="{{ route('documents.create') }}" class="new-doc-card">
            <div class="new-doc-thumb">
                <span class="plus-icon">+</span>
                <div class="doc-lines">
                    <div class="doc-line" style="width:80%"></div>
                    <div class="doc-line" style="width:60%"></div>
                    <div class="doc-line" style="width:70%"></div>
                </div>
            </div>
            <div class="new-doc-label">Blank document</div>
        </a>
    </div>

    {{-- Recent documents --}}
    <div class="docs-recent">
        @if($documents->isEmpty())
            <div class="empty-state">
                <i class="bi bi-file-earmark-text"></i>
                <p>No documents yet. <a href="{{ route('documents.create') }}" style="color:#1a73e8">Create your first document</a>.</p>
            </div>
        @else
            <h6>Recent documents</h6>
            @foreach($documents as $doc)
            <div class="doc-card">
                <a href="{{ route('documents.edit', $doc) }}" class="doc-card-thumb d-block">
                    <div class="doc-title-preview">{{ $doc->title }}</div>
                    <div class="doc-content-preview">{!! strip_tags($doc->content) !!}</div>
                </a>
                <div class="doc-card-footer">
                    <div class="doc-card-info">
                        <div class="doc-card-name">{{ Str::limit($doc->title, 18) }}</div>
                        <div class="doc-card-date">{{ $doc->updated_at->format('M d, Y') }}</div>
                    </div>
                    <div class="doc-card-menu">
                        <button class="doc-menu-btn" title="More options">⋮</button>
                        <div class="doc-dropdown">
                            <a href="{{ route('documents.edit', $doc) }}">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <button type="button" class="share-btn" data-id="{{ $doc->id }}"
                                    data-url="{{ route('documents.share', $doc) }}">
                                <i class="bi bi-share"></i> Share
                            </button>
                            <a href="{{ route('documents.view-logs', $doc) }}">
                                <i class="bi bi-eye"></i> View Logs
                            </a>
                            <div class="divider"></div>
                            <form action="{{ route('documents.destroy', $doc) }}" method="POST"
                                  onsubmit="return confirm('Delete \'{{ addslashes($doc->title) }}\'?')">
                                @csrf @method('DELETE')
                                <button type="submit">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        @endif
    </div>

</div>
@endsection

{{-- Share Link Modal --}}
@section('modals')
<div class="modal fade" id="shareModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-share me-2 text-primary"></i>Share Document</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Anyone with this link can request access via email OTP verification.</p>
                <div class="share-url-wrap">
                    <input type="text" id="share-link-input" class="form-control" readonly>
                    <button id="copy-btn" class="btn btn-outline-primary btn-sm" onclick="copyShareLink()">
                        <i class="bi bi-clipboard"></i> Copy
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
