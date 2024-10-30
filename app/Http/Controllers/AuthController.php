<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use App\Utils\Response;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{

    protected $user;
    protected $response;

    public function __construct(User $user, Response $response)
    {
        $this->user = $user;
        $this->response = $response;
    }

    public function register(Request $request)
    {
        try {
            $inputData = $request->only('name', 'email', 'password');

            $validator = Validator::make($inputData, [
                'name' => 'required|string|max:255',
                'email' => 'required|email',
                'password' => ['required','string', Password::min(8)->max(10)->mixedCase()->symbols()],
            ]);

            if ($validator->fails()) {
                return $this->response->error(['errors' => $validator->errors()->all()]);
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
                $response = ['error' => 'Invalid credentials'];
                return $this->response->error($response);
            }
            $response = [
                'data' => $this->user->where('email', $inputData['email'])->first(),
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
            ];
            return $this->response->success($response);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function get(Request $request)
    {
        try {
            $token = $request->header('Authorization');
            if (!$token) {
                return $this->response->error(['error' => 'Unauthorized or token not provided']);
            }
            $id = JWTAuth::parseToken()->authenticate()->id;
            $getUserProfile = $this->user->getUserProfile($id);
            return $getUserProfile;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function update(Request $request)
    {
        try {
            $token = $request->header('Authorization');
            if (!$token) {
                return $this->response->error(['error' => 'Unauthorized or token not provided']);
            }
            $id = JWTAuth::parseToken()->authenticate()->id;
            
            $inputData = $request->only('name', 'email', 'specialist', 'role');
            $validator = Validator::make($inputData, [
                'name' => 'string|max:255',
                'email' => 'email',
                'specialist' => 'string',
                'role' => 'string',
            ]);
            if ($validator->fails()) {
                return $this->response->error(['errors' => $validator->errors()->all()]);
            }
            $isUserUpdated = $this->user->updateAuthor($id, $inputData);
            return $isUserUpdated;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function updatePassword(Request $request)
    {
        try {
            $token = $request->header('Authorization');
            if (!$token) {
                return $this->response->error(['error' => 'Unauthorized or token not provided']);
            }
            $id = JWTAuth::parseToken()->authenticate()->id;

            $inputData = $request->only('password');
            $validator = Validator::make($inputData, [
                'password' => ['required','string', Password::min(8)->max(10)->mixedCase()->symbols()],
            ]);
            if ($validator->fails()) {
                return $this->response->error(['errors' => $validator->errors()->all()]);
            }
            $isPasswordUpdated = $this->user->updatePassword($id, $inputData);
            return $isPasswordUpdated;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function verify(Request $request)
    {
        try {
            $token = $request->header('Authorization');

            if (!$token) {
                return $this->response->error(['error' => 'Unauthorized or token not provided']);
            }
            $isTokenValid = JWTAuth::parseToken()->authenticate();
            $response = ['response' => $isTokenValid];
            return $this->response->success($response);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
