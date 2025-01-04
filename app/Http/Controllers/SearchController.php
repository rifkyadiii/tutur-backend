<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Album, Card};
use Illuminate\Support\Facades\Validator;

class SearchController extends Controller
{
   public function search(Request $request)
   {
       $validator = Validator::make($request->all(), [
           'query' => 'required|string'
       ]);

       if ($validator->fails()) {
           return response()->json($validator->errors(), 400);
       }

       $query = $request->get('query');

       $albums = Album::where('user_id', auth()->id())
           ->where('name', 'like', "%{$query}%")
           ->get();

       $cards = Card::whereHas('album', function($q) {
               $q->where('user_id', auth()->id());
           })
           ->where('name', 'like', "%{$query}%")
           ->get();

       return response()->json([
           'albums' => $albums,
           'cards' => $cards
       ]);
   }
}
