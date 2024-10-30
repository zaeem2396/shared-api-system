<?php

namespace App\Models;

use App\Utils\Response;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use App\Models\Blog;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'isEmailVerified'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Returns a unique identifier for the user (e.g., user ID)
    public function getJWTIdentifier()
    {
        return $this->getKey(); // Usually this is the user's ID
    }

    // Returns an array of custom claims (you can leave it empty if not using any custom claims)
    public function getJWTCustomClaims()
    {
        return [];
    }

    public static function createUser(array $inputData)
    {
        try {
            $isUserExist = self::where('email', $inputData['email'])->first();
            if ($isUserExist) {
                return Response::duplicate(['message' => 'User already exist']);
            }
            $isUserCreated = self::create($inputData);
            if ($isUserCreated) {
                return Response::success(['message' => 'User created successfully']);
            } else {
                return Response::error(['message' => 'Something went wrong']);
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public static function getUserProfile($id)
    {
        try {
            $user = self::where('id', $id)->first();

            if (!$user) {
                return Response::error(['message' => 'User not found']);
            }
            $userArray = $user->toArray();
            $userArray['totalBlogs'] = Blog::where('authorId', $id)->count();

            return Response::success(['response' => $userArray]);
        } catch (Exception $e) {
            return Response::error(['message' => $e->getMessage()]);
        }
    }

    public static function updateAuthor($id, array $inputData)
    {
        try {
            $isUserExist = self::where('id', $id)->first();
            if (!$isUserExist) {
                return Response::error(['message' => 'User not found']);
            }
            $isUserUpdated = self::where('id', $id)->update($inputData);
            if ($isUserUpdated) {
                $getUpdatedInfo = self::where('id', $id)->first()->toArray();
                $response = [
                    'message' => 'User updated successfully',
                    'userInfo' => $getUpdatedInfo
                ];
                return Response::success($response);
            } else {
                return Response::error(['message' => 'Something went wrong']);
            }
        } catch (Exception $e) {
            return Response::error(['message' => $e->getMessage()]);
        }
    }

    public static function updatePassword($id, array $inputData)
    {
        try {
            $isUserExist = self::where('id', $id)->first();
            if (!$isUserExist) {
                return Response::error(['message' => 'User not found']);
            }
            $isUserUpdated = self::where('id', $id)->update($inputData);
            if ($isUserUpdated) {
                return Response::success(['message' => 'Password updated successfully']);
            } else {
                return Response::error(['message' => 'Something went wrong']);
            }
        } catch (Exception $e) {
            return Response::error(['message' => $e->getMessage()]);
        }
    }
}
