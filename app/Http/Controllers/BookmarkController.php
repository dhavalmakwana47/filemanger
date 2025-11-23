<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\File;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookmarkController extends Controller
{
    public function toggleBookmark(Request $request)
    {
        $request->validate([
            'type' => 'required|in:folder,file',
            'id' => 'required|integer',
        ]);

        $user = Auth::user();
        $companyId = get_active_company(); // Assuming you have this helper

        $model = $request->type === 'folder' ? Folder::class : File::class;
        $item = $model::findOrFail($request->id);

        $bookmark = Bookmark::where([
            'user_id' => $user->id,
            'company_id' => $companyId,
            'bookmarkable_id' => $item->id,
            'bookmarkable_type' => $model,
        ])->first();

        if ($bookmark) {
            $bookmark->delete();
            $action = 'removed';
        } else {
            Bookmark::create([
                'user_id' => $user->id,
                'company_id' => $companyId,
                'bookmarkable_id' => $item->id,
                'bookmarkable_type' => $model,
            ]);
            $action = 'added';
        }

        return response()->json([
            'status' => 'success',
            'action' => $action,
            'message' => 'Bookmark ' . $action . ' successfully'
        ]);
    }

    public function getBookmarks()
    {
        $user = Auth::user();
        $companyId = get_active_company();

        $bookmarks = Bookmark::with('bookmarkable')
            ->where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->latest()
            ->get()
            ->map(function ($bookmark) {
                $item = $bookmark->bookmarkable;
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'type' => $bookmark->bookmarkable_type === Folder::class ? 'folder' : 'file',
                    'created_at' => $bookmark->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json($bookmarks);
    }
}