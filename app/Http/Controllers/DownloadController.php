<?php

namespace App\Http\Controllers;

use App\Models\ZipDownload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\DataTables;

class DownloadController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $downloads = ZipDownload::where('user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->get();

            return DataTables::of($downloads)
                ->addIndexColumn()
                ->editColumn('status', function ($download) {
                    $badges = [
                        'pending' => 'warning',
                        'processing' => 'info', 
                        'completed' => 'success',
                        'failed' => 'danger'
                    ];
                    return '<span class="badge bg-' . $badges[$download->status] . '">' . ucfirst($download->status) . '</span>';
                })
                ->editColumn('created_at', function ($download) {
                    return $download->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('action', function ($download) {
                    $actions = '';
                    if ($download->status === 'completed') {
                        $actions .= '<a href="' . route('downloads.download', $download->id) . '" class="btn btn-sm btn-success">Download</a> ';
                    }
                    $actions .= '<button class="btn btn-sm btn-danger" onclick="deleteDownload(' . $download->id . ')">Delete</button>';
                    return $actions;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('app.downloads.index', ['title' => 'My Downloads']);
    }

    public function destroy($id)
    {
        $download = ZipDownload::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$download) {
            return response()->json(['error' => 'Download not found'], 404);
        }

        if ($download->zip_path && Storage::disk('s3')->exists($download->zip_path)) {
            Storage::disk('s3')->delete($download->zip_path);
        }

        $download->delete();

        return response()->json(['success' => true, 'message' => 'Download deleted successfully']);
    }

public function download($id)
{
    $download = ZipDownload::where('id', $id)
        ->where('user_id', auth()->id())
        ->where('status', 'completed')
        ->first();

    if (!$download || !$download->zip_path) {
        return response()->json(['error' => 'Zip file not found or not ready'], 404);
    }

    if (!Storage::disk('s3')->exists($download->zip_path)) {
        return response()->json(['error' => 'Zip file not found in storage'], 404);
    }

    addUserAction([
        'user_id' => auth()->id(),
        'action' => "Folder/File {$download->folder_name} Successfully Downloaded"
    ]);

    // ✅ Generate temporary S3 URL (BEST PRACTICE)
    $url = Storage::disk('s3')->temporaryUrl(
        $download->zip_path,
        now()->addMinutes(15),
        [
            'ResponseContentDisposition' =>
                'attachment; filename="'.$download->folder_name.'.zip"'
        ]
    );

    return redirect($url);
}

}
