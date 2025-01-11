<?php

namespace App\Http\Controllers;

use App\Models\SentenceTemplate;
use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SentenceTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = SentenceTemplate::where('user_id', auth()->user()->id);

        // Filter berdasarkan favorit
        if ($request->has('is_favorite')) {
            $query->where('is_favorite', $request->is_favorite === 'true');
        }

        $sentenceTemplates = $query->get();
        return response()->json($sentenceTemplates);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'is_favorite' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = auth()->user();

        $sentenceTemplate = SentenceTemplate::create([
            'name' => $request->input('name', 'Template ' . now()->format('Y-m-d H:i:s')),
            'user_id' => $user->id,
            'is_favorite' => $request->input('is_favorite', false),
        ]);

        return response()->json($sentenceTemplate, 201);
    }

    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sentence_template_id' => 'required|integer|exists:sentence_templates,id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $sentenceTemplateId = $request->input('sentence_template_id');
        $sentenceTemplate = SentenceTemplate::find($sentenceTemplateId);

        if (!$sentenceTemplate) {
            return response()->json(['error' => 'Sentence template not found'], 404);
        }

        if ($sentenceTemplate->user_id != auth()->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($sentenceTemplate);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sentence_template_id' => 'required|integer|exists:sentence_templates,id',
            'name' => 'sometimes|string|max:255',
            'is_favorite' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $sentenceTemplateId = $request->input('sentence_template_id');
        $sentenceTemplate = SentenceTemplate::find($sentenceTemplateId);

        if (!$sentenceTemplate) {
            return response()->json(['error' => 'Sentence template not found'], 404);
        }

        if ($sentenceTemplate->user_id != auth()->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($request->has('name')) {
            $sentenceTemplate->name = $request->name;
        }

        if ($request->has('is_favorite')) {
            $sentenceTemplate->is_favorite = $request->is_favorite;
        }

        $sentenceTemplate->save();

        return response()->json($sentenceTemplate);
    }

    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sentence_template_id' => 'required|integer|exists:sentence_templates,id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $sentenceTemplateId = $request->input('sentence_template_id');
        $sentenceTemplate = SentenceTemplate::find($sentenceTemplateId);

        if (!$sentenceTemplate) {
            return response()->json(['error' => 'Sentence template not found'], 404);
        }

        if ($sentenceTemplate->user_id != auth()->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $sentenceTemplate->cards()->detach();
        $sentenceTemplate->delete();

        return response()->json(null, 204);
    }

    public function addCard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sentence_template_id' => 'required|integer|exists:sentence_templates,id',
            'card_ids' => 'required|array',
            'card_ids.*' => 'integer|exists:cards,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $sentenceTemplateId = $request->input('sentence_template_id');
        $sentenceTemplate = SentenceTemplate::find($sentenceTemplateId);

        if (!$sentenceTemplate) {
            return response()->json(['error' => 'Sentence template not found'], 404);
        }

        if ($sentenceTemplate->user_id != auth()->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $cardIds = $request->card_ids;

        $invalidCardIds = [];
        foreach ($cardIds as $cardId) {
            $card = Card::find($cardId);
            if (!$card || $card->album->user_id != auth()->user()->id) {
                $invalidCardIds[] = $cardId;
            }
        }

        if (!empty($invalidCardIds)) {
            return response()->json(['error' => 'Unauthorized to add some cards', 'invalid_card_ids' => $invalidCardIds], 403);
        }

        $existingCardIds = $sentenceTemplate->cards()->whereIn('card_id', $cardIds)->pluck('card_id')->toArray();
        if (!empty($existingCardIds)) {
            return response()->json(['error' => 'Cards already exist in this template', 'existing_card_ids' => $existingCardIds], 400);
        }

        $sentenceTemplate->cards()->attach($cardIds);

        return response()->json(['message' => 'Cards added to sentence template successfully']);
    }

    public function getCards(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sentence_template_id' => 'required|integer|exists:sentence_templates,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $sentenceTemplateId = $request->input('sentence_template_id');
        $sentenceTemplate = SentenceTemplate::find($sentenceTemplateId);

        if (!$sentenceTemplate) {
            return response()->json(['error' => 'Sentence template not found'], 404);
        }

        if ($sentenceTemplate->user_id != auth()->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $cards = $sentenceTemplate->cards()->get();

        return response()->json($cards);
    }

    public function removeCard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sentence_template_id' => 'required|integer|exists:sentence_templates,id',
            'card_id' => 'required|integer|exists:cards,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $sentenceTemplateId = $request->input('sentence_template_id');
        $cardId = $request->input('card_id');
        $sentenceTemplate = SentenceTemplate::find($sentenceTemplateId);

        if (!$sentenceTemplate) {
            return response()->json(['error' => 'Sentence template not found'], 404);
        }

        if ($sentenceTemplate->user_id != auth()->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $sentenceTemplate->cards()->detach($cardId);

        return response()->json(null, 204);
    }
}
