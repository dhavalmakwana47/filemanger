<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DocumentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission_check:Documents,view',   only: ['index', 'show']),
            new Middleware('permission_check:Documents,create', only: ['create', 'store']),
            new Middleware('permission_check:Documents,update', only: ['edit', 'update']),
            new Middleware('permission_check:Documents,delete', only: ['destroy']),
        ];
    }
    public function index()
    {
        $documents = Document::where('company_id', get_active_company())->latest()->get();
        return view('app.documents.index', compact('documents'));
    }

    public function create()
    {
        return view('app.documents.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        Document::create([
            'company_id' => get_active_company(),
            'title'      => $request->title,
            'content'    => $request->content,
        ]);

        return redirect()->route('documents.index')->with('success', 'Document created successfully.');
    }

    public function edit(Document $document)
    {
        abort_if($document->company_id != get_active_company(), 403);
        return view('app.documents.edit', compact('document'));
    }

    public function update(Request $request, Document $document)
    {
        abort_if($document->company_id != get_active_company(), 403);

        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $document->update($request->only('title', 'content'));

        return redirect()->route('documents.index')->with('success', 'Document updated successfully.');
    }

    public function destroy(Document $document)
    {
        abort_if($document->company_id !== get_active_company(), 403);
        $document->delete();
        return redirect()->route('documents.index')->with('success', 'Document deleted.');
    }

    public function share(Document $document)
    {
        abort_if($document->company_id != get_active_company(), 403);
        $token = $document->share_token ?? $document->generateShareToken();
        return response()->json(['url' => route('doc.email', $token)]);
    }

    public function viewLogs(Request $request, Document $document)
    {
        abort_if($document->company_id != get_active_company(), 403);

        if ($request->ajax()) {
            $logs = $document->views()->latest()->get(['id', 'email', 'verified_at', 'viewed_at', 'ip_address', 'user_agent']);
            return \Yajra\DataTables\DataTables::of($logs)
                ->editColumn('verified_at', fn($r) => $r->verified_at?->format('Y-m-d H:i:s') ?? '—')
                ->editColumn('viewed_at',   fn($r) => $r->viewed_at?->format('Y-m-d H:i:s')   ?? '—')
                ->editColumn('ip_address',  fn($r) => $r->ip_address ?? '—')
                ->editColumn('user_agent',  fn($r) => $r->user_agent ?? '—')
                ->make(true);
        }

        return view('app.documents.view-logs', compact('document'));
    }
}
