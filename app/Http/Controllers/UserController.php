<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Firebase\Auth\Token\Exception\InvalidToken;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Exception;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
   protected $auth;

   public function __construct(FirebaseAuth $auth)
   {
       $this->auth = $auth;
   }

   public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'firebase_uid' => 'required|string|unique:users,firebase_uid',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'firebase_uid' => $request->firebase_uid,
                'role' => 'user'
            ]);

            return response()->json([
                'message' => 'User created successfully',
                'user' => $user,
            ], 201);
        } catch (Exception $e) {
            Log::error('User registration failed:', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function show(Request $request) {
        try {
            $user = User::where('email', $request->email)
                        ->orWhere('firebase_uid', $request->user_id)
                        ->first();

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            $authUser = auth()->user();
            if ($authUser->role !== 'admin' && $authUser->id != $user->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            return response()->json($user);
        } catch (Exception $e) {
            Log::error('User fetch failed:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'User not found'], 404);
        }
    }

    public function index(Request $request)
    {
        $authUser = auth()->user();

        if (!$authUser) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        if ($authUser->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $users = User::all();
        return response()->json($users);
    }

    public function update(Request $request)
    {
       try {
           $validator = Validator::make($request->all(), [
               'user_id' => 'required|exists:users,id',
               'name' => 'nullable|string|max:255',
               'email' => [
                   'nullable',
                   'string',
                   'email',
                   'max:255',
                   Rule::unique('users')->ignore($request->user_id),
               ],
           ]);

           if ($validator->fails()) {
               return response()->json($validator->errors(), 400);
           }

           $authUser = auth()->user();
           $user = User::findOrFail($request->user_id);

           if ($authUser->role !== 'admin' && $authUser->id != $user->id) {
               return response()->json(['error' => 'Unauthorized'], 403);
           }

           if ($request->has('name')) {
               $user->name = $request->name;
           }

           if ($request->has('email') && $request->email && $user->email != $request->email) {
               if ($user->firebase_uid) {
                   $this->auth->updateUser($user->firebase_uid, [
                       'email' => $request->email,
                   ]);
               }
               $user->email = $request->email;
           }

           $user->save();
           return response()->json($user);

       } catch (Exception $e) {
           Log::error('User update failed:', ['error' => $e->getMessage()]);
           return response()->json(['error' => $e->getMessage()], 500);
       }
    }

    public function updatePhoto(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'photo' => 'required|image|max:2048',
                'user_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $authUser = auth()->user();
            $user = User::findOrFail($request->user_id);

            if ($authUser->role !== 'admin' && $authUser->id != $user->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $photoUrl = null;
            if ($request->hasFile('photo')) {
                $photoUrl = $this->gcs->uploadFile($request->file('photo'), 'photos');
            }

            $user->photo_url = $photoUrl;
            $user->save();

            return response()->json($user);
        } catch (Exception $e) {
            Log::error('Photo update failed:', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function updatePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'password' => 'required|string|min:6'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $authUser = auth()->user();
            $user = User::findOrFail($request->user_id);

            if ($authUser->role !== 'admin' && $authUser->id != $user->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $this->auth->updateUser($user->firebase_uid, [
                'password' => $request->password
            ]);

            return response()->json(['message' => 'Password updated successfully']);
        } catch (Exception $e) {
            Log::error('Password update failed:', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
       try {
           $validator = Validator::make($request->all(), [
               'user_id' => 'required|exists:users,id'
           ]);

           if ($validator->fails()) {
               return response()->json($validator->errors(), 400);
           }

           $authUser = auth()->user();
           $user = User::findOrFail($request->user_id);

           if ($authUser->role !== 'admin' && $authUser->id != $user->id) {
               return response()->json(['error' => 'Unauthorized'], 403);
           }

           if ($user->firebase_uid) {
               try {
                   $this->auth->deleteUser($user->firebase_uid);
               } catch (Exception $e) {
                   Log::error('Firebase user deletion failed:', ['error' => $e->getMessage()]);
               }
           }

           $user->delete();
           return response()->json(['message' => 'User deleted successfully']);

       } catch (Exception $e) {
           Log::error('User Delete Failed:', ['error' => $e->getMessage()]);
           return response()->json(['error' => $e->getMessage()], 500);
       }
    }
}
