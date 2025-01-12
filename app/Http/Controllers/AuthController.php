<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use App\Utils\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{

    protected $user;
    protected $response;
    protected $mailService;

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
                'password' => ['required', 'string', Password::min(8)->max(10)->mixedCase()->symbols()],
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

    public function verifyEmail(Request $request)
    {
        try {
            $inputData = $request->only('token');

            $validator = Validator::make($inputData, [
                'token' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->response->error(['errors' => $validator->errors()->all()]);
            }

            $isEmailVerified = $this->user->verify($inputData);
            return $isEmailVerified;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function login(Request $request)
    {
        try {
            $inputData = $request->only('email', 'password');
            $email = $inputData['email'];
            $cacheKey = 'login_attempts_' . $email;

            // Rate limiting logic: Check if the user has exceeded login attempts
            if (RateLimiter::tooManyAttempts($cacheKey, 3)) {
                $retryAfterSeconds = RateLimiter::availableIn($cacheKey);
                return $this->response->error(['message' => "Too many attempts. Please try again in {$retryAfterSeconds} seconds."]);
            }

            // Check if user exist
            $isUserExist = $this->user->where('email', $email)->first();
            if (!$isUserExist) {
                // Log a failed attempt
                RateLimiter::hit($cacheKey, 300);

                return $this->response->error(['message' => 'User not found']);
            }

            // Check if the email is verified
            $isEmailVerified = json_decode($this->user->isEmailVerified($inputData));
            if (!$isEmailVerified->response->verified) {
                return $this->response->error(['message' => 'Email not verified']);
            }

            $token = JWTAuth::attempt($inputData);
            if (!$token) {
                // Log a failed attempt
                RateLimiter::hit($cacheKey, 300);

                return $this->response->error(['error' => 'Invalid credentials']);
            }

            // Clear login attempts cache if successful
            RateLimiter::clear($cacheKey);

            // Successful login response
            $response = [
                'data' => $this->user->where('email', $email)->first(),
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
                'password' => ['required', 'string', Password::min(8)->max(10)->mixedCase()->symbols()],
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

    public function verificationLink(Request $request)
    {
        try {
            $inputData = $request->only('email');
            $validator = Validator::make($inputData, [
                'email' => 'required|email',
            ]);
            if ($validator->fails()) {
                return $this->response->error(['errors' => $validator->errors()->all()]);
            }
            $isVerificationLinkSent = $this->user->sendVerificationLink($inputData);
            return $isVerificationLinkSent;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function authors(Request $request)
    {
        try {
            $inputData = $request->only('id');
            $validator = Validator::make($inputData, [
                'id' => 'nullable|integer',
            ]);
            if ($validator->fails()) {
                return $this->response->error(['errors' => $validator->errors()->all()]);
            }
            $authors = $this->user->getAuthors($inputData);
            return $authors;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
