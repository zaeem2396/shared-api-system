<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

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
                return response()->json(['message' => 'User already exist'], 400);
            }
            $isUserCreated = self::create($inputData);
            if ($isUserCreated) {
                return response()->json(['message' => 'User created successfully'], 200);
            } else {
                return response()->json(['message' => 'Something went wrong'], 400);
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
