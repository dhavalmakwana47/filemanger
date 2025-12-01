<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\DataTables;

class BookmarkController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $bookmarks = Bookmark::with(['bookmarkable' => function($query) {
                    $query->whereNull('deleted_at');
                }])
                ->where('user_id', Auth::id())
                ->where('company_id', get_active_company())
                ->where('bookmarkable_type', File::class)
                ->get()
                ->filter(function ($bookmark) {
                    return $bookmark->bookmarkable !== null;
                })
                ->map(function ($bookmark) {
                    return [
                        'id' => $bookmark->bookmarkable->id,
                        'name' => $bookmark->bookmarkable->file_name,
                        'size' => number_format(($bookmark->bookmarkable->size_kb/1024)/1024, 2) . ' MB',
                        'created_at' => $bookmark->bookmarkable->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            return DataTables::of($bookmarks)
                ->addColumn('action', function ($item) {
                    $file = File::find($item['id']);
                    $canView = $file && $file->checkAccess('Folder', 'file_view');
                    $canDownload = $file && $file->checkAccess('Folder', 'download');
                    
                    $viewBtn = $canView ? 
                        '<form action="' . route('file.getview') . '" method="POST" style="display:inline;" target="_blank">
                            ' . csrf_field() . '
                            <input type="hidden" name="id" value="' . $item['id'] . '">
                            <button type="submit" class="btn btn-sm btn-info">View</button>
                        </form> ' : '';
                    
                    $downloadBtn = $canDownload ? 
                        '<form action="' . route('file.download') . '" method="POST" style="display:inline;">
                            ' . csrf_field() . '
                            <input type="hidden" name="id" value="' . $item['id'] . '">
                            <button type="submit" class="btn btn-sm btn-primary">Download</button>
                        </form> ' : 
                        '<button class="btn btn-sm btn-secondary" disabled>No Access</button> ';
                    
                    return $viewBtn . $downloadBtn . '
                        <form action="' . route('bookmarks.remove') . '" method="POST" style="display:inline;" onsubmit="return confirm(\'Remove from bookmarks?\')">
                            ' . csrf_field() . '
                            <input type="hidden" name="file_id" value="' . $item['id'] . '">
                            <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                        </form>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('app.bookmarks.index', [
            'title' => 'My Bookmarks',
        ]);
    }

    public function toggleBookmark(Request $request)
    {
        $bookmark = Bookmark::where([
            'user_id' => Auth::id(),
            'bookmarkable_type' => $request->type === 'file' ? File::class : null,
            'bookmarkable_id' => $request->id
        ])->first();

        if ($bookmark) {
            $bookmark->delete();
            $action = 'removed';
        } else {
            Bookmark::create([
                'user_id' => Auth::id(),
                'company_id' => get_active_company(),
                'bookmarkable_type' => $request->type === 'file' ? File::class : null,
                'bookmarkable_id' => $request->id
            ]);
            $action = 'added';
        }

        return response()->json(['action' => $action]);
    }

    public function remove(Request $request)
    {
        Bookmark::where([
            'user_id' => Auth::id(),
            'company_id' => get_active_company(),
            'bookmarkable_type' => File::class,
            'bookmarkable_id' => $request->file_id
        ])->delete();

        return redirect()->back()->with('success', 'Bookmark removed successfully.');
    }
}