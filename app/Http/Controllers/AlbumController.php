<?php

namespace App\Http\Controllers;


use App\Models\{Album, Card};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Storage, Validator, Auth, Log};
use App\Services\GoogleCloudStorage;

class AlbumController extends Controller
{
   protected $gcs;

   public function __construct(GoogleCloudStorage $gcs)
   {
       $this->gcs = $gcs;
   }

   public function store(Request $request)
   {
       try {
            $validator = Validator::make($request->all(), [
               'name' => 'required|string|max:12',
               'picture' => 'nullable|image|max:2048'
            ]);

            if ($validator->fails()) {
               return response()->json($validator->errors(), 400);
            }

            $url = null;
            if ($request->hasFile('picture')) {
                $url = $this->gcs->uploadFile($request->file('picture'), 'album_pictures');
            }

            $album = Album::create([
                'name' => $request->name,
                'picture' => $url,
                'user_id' => auth()->id()
            ]);

            return response()->json($album, 201);
        } catch (\Exception $e) {
            Log::error('Upload failed:', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
   }

    public function index(Request $request)
    {
        $query = Album::with('cards');
        $query->where('user_id', $request->user_id ?? auth()->id());
        return response()->json($query->get());
    }

    public function show(Request $request)
    {
    $validator = Validator::make($request->all(), [
        'album_id' => 'required|exists:albums,id'
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 400);
    }

    $album = Album::with('cards')
        ->where('user_id', auth()->id())
        ->find($request->album_id);

    if (!$album) {
        return response()->json(['error' => 'Album not found'], 404);
    }

    return response()->json($album);
    }

    public function update(Request $request)
    {
    try {
        $validator = Validator::make($request->all(), [
            'album_id' => 'required|exists:albums,id',
            'name' => 'nullable|string|max:12',
            'picture' => 'nullable|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $album = Album::where('user_id', auth()->id())
            ->find($request->album_id);

        if (!$album) {
            return response()->json(['error' => 'Album not found'], 404);
        }

        if ($request->has('name') && $request->name) {
            $album->name = $request->name;
        }

        if ($request->hasFile('picture')) {
            if ($album->picture) {
                $this->gcs->deleteFile($album->picture);
            }
            $album->picture = $this->gcs->uploadFile($request->file('picture'), 'album_pictures');
        }

        $album->save();
        return response()->json($album);

        } catch (\Exception $e) {
            Log::error('Update failed:', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
    try {
        $validator = Validator::make($request->all(), [
            'album_id' => 'required|exists:albums,id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $album = Album::where('user_id', auth()->id())
            ->find($request->album_id);

        if (!$album) {
            return response()->json(['error' => 'Album not found'], 404);
        }

        // Set album_id to null for all cards in album
        Card::where('album_id', $album->id)->update(['album_id' => null]);

        if ($album->picture) {
            $this->gcs->deleteFile($album->picture);
        }

        $album->delete();
        return response()->json(['message' => 'Album deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Album Delete failed:', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
