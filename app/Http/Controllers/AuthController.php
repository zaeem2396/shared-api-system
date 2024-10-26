<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    protected $jwtAuth;
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function register(Request $request)
    {
        try {
            $inputData = $request->only('name', 'email', 'password');

            $validator = Validator::make($inputData, [
                'name' => 'required|string|max:255',
                'email' => 'required|email',
                'password' => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $isUserCreated = $this->user->createUser($inputData);
            return $isUserCreated;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function login(Request $request)
    {
        try {
            $inputData = $request->only('email', 'password');

            $token = JWTAuth::attempt($inputData);

            if (!$token) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }
            $response = [
                'data' => $this->user->where('email', $inputData['email'])->first(),
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
            ];
            return response()->json(['message' => 'success', 'response' => $response], 201);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
