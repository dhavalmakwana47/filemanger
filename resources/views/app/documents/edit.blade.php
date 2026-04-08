@extends('app.layouts.layout')

@push('styles')
    @include('app.documents._editor_styles')
@endpush

@push('scripts')
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
    const Font = Quill.import('formats/font');
    Font.whitelist = ['arial','times','courier','georgia','verdana'];
    Quill.register(Font, true);

    const Size = Quill.import('attributors/style/size');
    Size.whitelist = ['8pt','9pt','10pt','11pt','12pt','14pt','16pt','18pt','24pt','36pt','48pt','72pt'];
    Quill.register(Size, true);

    const quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: '#toolbar',
            history: { delay: 1000, maxStack: 100 },
        },
        placeholder: 'Start typing your document…',
    });

    quill.root.innerHTML = {!! json_encode(old('content', $document->content)) !!};

    document.getElementById('doc-form').addEventListener('submit', function () {
        document.getElementById('content').value = quill.root.innerHTML;
    });
</script>
@endpush

@section('content')
<div class="gdocs-wrap">

    <form id="delete-form" action="{{ route('documents.destroy', $document) }}" method="POST" style="display:none">
        @csrf @method('DELETE')
    </form>

    <form action="{{ route('documents.update', $document) }}" method="POST" id="doc-form">
        @csrf @method('PUT')

        <div class="gdocs-topbar">
            <a href="{{ route('documents.index') }}" class="gdocs-back" title="All Documents">
                <i class="bi bi-arrow-left"></i>
            </a>
            <i class="bi bi-file-earmark-text-fill gdocs-icon"></i>
            <input type="text" name="title" id="doc-title"
                   class="gdocs-title-input @error('title') is-invalid @enderror"
                   value="{{ old('title', $document->title) }}"
                   placeholder="Untitled document">
            @error('title')<span class="error-msg">{{ $message }}</span>@enderror
            @error('content')<span class="error-msg">Content is required.</span>@enderror

            <span class="gdocs-meta">Last edited {{ $document->updated_at->diffForHumans() }}</span>

            <button type="button" class="gdocs-delete-btn"
                    onclick="if(confirm('Delete \'{{ addslashes($document->title) }}\'?')) document.getElementById('delete-form').submit()">
                <i class="bi bi-trash"></i> Delete
            </button>

            <button type="submit" class="gdocs-save-btn">
                <i class="bi bi-cloud-arrow-up"></i> Save
            </button>
        </div>

        @include('app.documents._toolbar')

        <div class="gdocs-editor-area">
            <div class="gdocs-page">
                <div id="editor"></div>
            </div>
        </div>

        <textarea name="content" id="content" style="display:none"></textarea>
    </form>
</div>
@endsection
