<?php

namespace App\Http\Controllers;

use App\Models\{Card, Album};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Validator, Log};
use App\Services\GoogleCloudStorage;

class CardController extends Controller
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
                'spelling' => 'nullable|string|max:12',
                'picture' => 'nullable|image|max:2048',
                'voice' => 'nullable|file|mimes:mp3,wav,ogg,m4a|max:2048',
                'album_id' => 'nullable|exists:albums,id'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            if ($request->album_id) {
                $album = Album::findOrFail($request->album_id);
                if ($album->user_id !== auth()->id()) {
                    return response()->json(['error' => 'Unauthorized'], 403);
                }
            }

            $pictureUrl = $request->hasFile('picture')
                ? $this->gcs->uploadFile($request->file('picture'), 'card_pictures')
                : null;

            $voiceUrl = $request->hasFile('voice')
                ? $this->gcs->uploadFile($request->file('voice'), 'card_voices')
                : null;

           $card = Card::create([
               'name' => $request->name,
               'spelling' => $request->spelling,
               'picture' => $pictureUrl,
               'voice' => $voiceUrl,
               'album_id' => $request->album_id
           ]);

           return response()->json($card, 201);
       } catch (\Exception $e) {
           Log::error('Card creation failed:', ['error' => $e->getMessage()]);
           return response()->json(['error' => $e->getMessage()], 500);
       }
   }

   public function attachToAlbum(Request $request)
   {
       try {
           $validator = Validator::make($request->all(), [
               'card_id' => 'required|exists:cards,id',
               'album_id' => 'required|exists:albums,id'
           ]);

           if ($validator->fails()) {
               return response()->json($validator->errors(), 400);
           }

           $card = Card::findOrFail($request->card_id);
           $album = Album::findOrFail($request->album_id);

           if ($album->user_id !== auth()->id()) {
               return response()->json(['error' => 'Unauthorized'], 403);
           }

           $card->album_id = $request->album_id;
           $card->save();

           return response()->json($card);
       } catch (\Exception $e) {
           Log::error('Card attach failed:', ['error' => $e->getMessage()]);
           return response()->json(['error' => $e->getMessage()], 500);
       }
   }

    public function getAllCards()
    {
        try {
            $cards = Card::where(function ($query) {
                $query->whereHas('album', function ($q) {
                    $q->where('user_id', auth()->id());
                })->orWhereNull('album_id');
            })->get();

            return response()->json($cards);
        } catch (\Exception $e) {
            Log::error('Cards fetch failed:', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'card_id' => 'required|exists:cards,id'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $card = Card::findOrFail($request->card_id);

            if ($card->album && $card->album->user_id !== auth()->id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            unset($card->album);
            return response()->json($card);
        } catch (\Exception $e) {
            Log::error('Card fetch failed:', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

   public function index(Request $request)
   {
       try {
           $validator = Validator::make($request->all(), [
               'album_id' => 'required|exists:albums,id'
           ]);

           if ($validator->fails()) {
               return response()->json($validator->errors(), 400);
           }

           $album = Album::with('cards')->findOrFail($request->album_id);
           if ($album->user_id !== auth()->id()) {
               return response()->json(['error' => 'Unauthorized'], 403);
           }

           return response()->json($album->cards);
       } catch (\Exception $e) {
           Log::error('Card fetch failed:', ['error' => $e->getMessage()]);
           return response()->json(['error' => $e->getMessage()], 500);
       }
   }

   public function update(Request $request)
   {
       try {
           $validator = Validator::make($request->all(), [
               'card_id' => 'required|exists:cards,id',
               'name' => 'nullable|string|max:12',
               'spelling' => 'nullable|string|max:12',
               'picture' => 'nullable|image|max:2048',
               'voice' => 'nullable|file|mimes:mp3,wav,ogg,m4a|max:2048'
           ]);

           if ($validator->fails()) {
               return response()->json($validator->errors(), 400);
           }

           $card = Card::findOrFail($request->card_id);
           if ($card->album && $card->album->user_id !== auth()->id()) {
               return response()->json(['error' => 'Unauthorized'], 403);
           }

           if ($request->has('name') && $request->name) {
               $card->name = $request->name;
           }

            if ($request->has('spelling') && $request->spelling) {
                $card->spelling = $request->spelling;
            }

           if ($request->hasFile('picture')) {
               if ($card->picture) {
                   $this->gcs->deleteFile($card->picture);
               }
               $card->picture = $this->gcs->uploadFile($request->file('picture'), 'card_pictures');
           }

           if ($request->hasFile('voice')) {
               if ($card->voice) {
                   $this->gcs->deleteFile($card->voice);
               }
               $card->voice = $this->gcs->uploadFile($request->file('voice'), 'card_voices');
           }

           $card->save();
           return response()->json($card);
       } catch (\Exception $e) {
           Log::error('Card update failed:', ['error' => $e->getMessage()]);
           return response()->json(['error' => $e->getMessage()], 500);
       }
   }

   public function destroy(Request $request)
   {
       try {
           $validator = Validator::make($request->all(), [
               'card_id' => 'required|exists:cards,id'
           ]);

           if ($validator->fails()) {
               return response()->json($validator->errors(), 400);
           }

           $card = Card::findOrFail($request->card_id);
           if ($card->album && $card->album->user_id !== auth()->id()) {
               return response()->json(['error' => 'Unauthorized'], 403);
           }

           if ($card->picture) {
               $this->gcs->deleteFile($card->picture);
           }
           if ($card->voice) {
               $this->gcs->deleteFile($card->voice);
           }

           $card->delete();
           return response()->json(['message' => 'Card deleted successfully']);
       } catch (\Exception $e) {
           Log::error('Card deletion failed:', ['error' => $e->getMessage()]);
           return response()->json(['error' => $e->getMessage()], 500);
       }
   }
}
