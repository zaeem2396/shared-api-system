<?php

namespace App\Models;

use App\Utils\Response;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use App\Models\{Blog, Vendors};
use App\Utils\ActivityLogger;
use App\Utils\MailService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = 'users';

    protected $primaryKey = 'id';

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
            app(ActivityLogger::class)->logSystemActivity('Starting user creation process', ['email' => $inputData['email']]);
            $isUserExist = self::where('email', $inputData['email'])->first();

            if ($isUserExist) {
                app(ActivityLogger::class)->logSystemActivity('Duplicate user found', ['email' => $inputData['email']], 409);
                app(ActivityLogger::class)->logUserActivity('Duplicate user creation', ['email' => $inputData['email']]);

                return app(Response::class)->duplicate(['message' => 'User already exist']);
            }
            $isUserCreated = self::create($inputData);
            if ($isUserCreated) {
                if (isset($inputData['role'])) {
                    app(Vendors::class)->create(['userId' => $isUserCreated->id]);
                }
                // Send register mail
                $getEmailData = app(EmailTemplates::class)->where('name', 'register_author')->first();
                if ($getEmailData === null) {
                    app(ActivityLogger::class)->logSystemActivity('Email template not found', ['name' => 'register_author'], 404);
                    app(ActivityLogger::class)->logUserActivity('Email template not found', ['name' => 'register_author'], 404);

                    return app(Response::class)->error(['message' => 'Processing failed due to technical fault']);
                }
                $subject = $getEmailData['subject'];
                $verificationLink = env('APP_URL') . 'api/author/verifyEmail?token=' . Crypt::encrypt($inputData['email']);
                $content = str_replace('<verification_link>', $verificationLink, $getEmailData['content']);

                app(MailService::class)->sendMail('no_reply@newzy.com', $inputData['email'], $subject, $content);
                app(ActivityLogger::class)->logSystemActivity('User created successfully', $isUserCreated, 200, 'json');
                app(ActivityLogger::class)->logUserActivity('User created successfully', $inputData['email'], ['email' => $inputData['email']]);

                return app(Response::class)->success(['message' => 'User created successfully']);
            } else {
                app(ActivityLogger::class)->logSystemActivity('User creation failed', $isUserCreated, 400);
                app(ActivityLogger::class)->logUserActivity('User creation failed', $inputData['email'], ['email' => $inputData['email']]);

                return app(Response::class)->error(['message' => 'Something went wrong']);
            }
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), $inputData, 500, 'JSON');

            return $e->getMessage();
        }
    }

    public static function verify(array $inputData)
    {
        try {
            app(ActivityLogger::class)->logSystemActivity('Starting user`s email verification process', ['email' => Crypt::decrypt($inputData['token'])]);
            $isUserExist = self::where('email', Crypt::decrypt($inputData['token']))->first();

            // Check if User exist
            if (!$isUserExist) {
                app(ActivityLogger::class)->logSystemActivity('User not found', ['email' => Crypt::decrypt($inputData['token'])], 404);
                app(ActivityLogger::class)->logUserActivity('User not found', ['email' => Crypt::decrypt($inputData['token'])], 404);

                return app(Response::class)->error(['message' => 'User not found']);
            }

            // Validate if email is already verified
            if (intval($isUserExist->isEmailVerified) === 1) {
                app(ActivityLogger::class)->logSystemActivity('User email already verified', ['email' => Crypt::decrypt($inputData['token'])], 400);
                app(ActivityLogger::class)->logUserActivity('User email already verified', ['email' => Crypt::decrypt($inputData['token'])], 400);

                return app(Response::class)->error(['message' => 'User email already verified']);
            }

            // Verify if token is valid or not
            if ($isUserExist['email'] === Crypt::decrypt($inputData['token'])) {
                $isUserVerified = self::where('email', Crypt::decrypt($inputData['token']))->update(['isEmailVerified' => 1]);
                if ($isUserVerified) {
                    app(ActivityLogger::class)->logSystemActivity('User email verified successfully', $isUserVerified, 200, 'json');
                    app(ActivityLogger::class)->logUserActivity('User email verified successfully', $isUserVerified);

                    return app(Response::class)->success(['message' => 'User email verified successfully']);
                } else {
                    app(ActivityLogger::class)->logSystemActivity('User email verification failed', $isUserVerified, 400);
                    app(ActivityLogger::class)->logUserActivity('User email verification failed', $isUserVerified);

                    return app(Response::class)->error(['message' => 'Something went wrong']);
                }
            }
        } catch (DecryptException $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), $inputData, 500, 'JSON');

            return $e->getMessage();
        }
    }

    public static function isEmailVerified(array $inputData)
    {
        $emailVerified = self::where('email', $inputData['email'])->first();
        if (intval($emailVerified->isEmailVerified) === 1) {
            return app(Response::class)->success(['verified' => true]);
        } else {
            return app(Response::class)->error(['verified' => false]);
        }
    }

    public static function getUserProfile($id)
    {
        try {
            app(ActivityLogger::class)->logSystemActivity('Starting get user profile process', ['id' => $id]);
            $user = self::where('id', $id)->first();

            if (!$user) {
                app(ActivityLogger::class)->logSystemActivity('User not found', ['id' => $id], 404);
                app(ActivityLogger::class)->logUserActivity('User not found', ['id' => $id]);

                return Response::error(['message' => 'User not found']);
            }
            $userArray = $user->toArray();
            $userArray['totalBlogs'] = Blog::where('authorId', $id)->count();

            app(ActivityLogger::class)->logSystemActivity('User profile fetched successfully', $userArray, 200, 'json');
            app(ActivityLogger::class)->logUserActivity('User profile fetched successfully', $userArray);

            return Response::success(['response' => $userArray]);
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), ['id' => $id], 500, 'JSON');

            return Response::error(['message' => $e->getMessage()]);
        }
    }

    public static function updateAuthor($id, array $inputData)
    {
        try {
            app(ActivityLogger::class)->logSystemActivity('Starting update user profile process', ['id' => $id]);
            $isUserExist = self::where('id', $id)->first();

            if (!$isUserExist) {
                app(ActivityLogger::class)->logSystemActivity('User not found', ['id' => $id, 'data' => $inputData], 404);
                app(ActivityLogger::class)->logUserActivity('User not found', ['id' => $id, 'data' => $inputData]);

                return Response::error(['message' => 'User not found']);
            }
            $isUserUpdated = self::where('id', $id)->update($inputData);
            if ($isUserUpdated) {
                $getUpdatedInfo = self::where('id', $id)->first()->toArray();
                $response = [
                    'message' => 'User updated successfully',
                    'userInfo' => $getUpdatedInfo
                ];

                app(ActivityLogger::class)->logSystemActivity('User profile updated successfully', $response, 200, 'json');
                app(ActivityLogger::class)->logUserActivity('User profile updated successfully', $response);

                return Response::success($response);
            } else {
                app(ActivityLogger::class)->logSystemActivity('User profile update failed', $isUserUpdated, 400);
                app(ActivityLogger::class)->logUserActivity('User profile update failed', $isUserUpdated);

                return Response::error(['message' => 'Something went wrong']);
            }
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), ['id' => $id, 'data' => $inputData], 500, 'json');
            return Response::error(['message' => $e->getMessage()]);
        }
    }

    public static function updatePassword($id, array $inputData)
    {
        try {
            app(ActivityLogger::class)->logSystemActivity('Starting update user password process', ['id' => $id]);
            $isUserExist = self::where('id', $id)->first();
            if (!$isUserExist) {
                app(ActivityLogger::class)->logSystemActivity('User not found', ['id' => $id, 'data' => $inputData], 404);
                app(ActivityLogger::class)->logUserActivity('User not found', ['id' => $id, 'data' => $inputData]);

                return Response::error(['message' => 'User not found']);
            }
            $inputData['password'] = Hash::make($inputData['password']);
            $isUserUpdated = self::where('id', $id)->update($inputData);
            if ($isUserUpdated) {
                app(ActivityLogger::class)->logSystemActivity('Password updated successfully', $isUserUpdated, 200, 'json');
                app(ActivityLogger::class)->logUserActivity('Password updated successfully', $isUserUpdated);

                return Response::success(['message' => 'Password updated successfully']);
            } else {
                app(ActivityLogger::class)->logSystemActivity('Password update failed', $isUserUpdated, 400);
                app(ActivityLogger::class)->logUserActivity('Password update failed', $isUserUpdated);

                return Response::error(['message' => 'Something went wrong']);
            }
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), ['id' => $id, 'data' => $inputData], 500, 'json');
            return Response::error(['message' => $e->getMessage()]);
        }
    }

    public static function sendVerificationLink(array $inputData)
    {
        try {
            app(ActivityLogger::class)->logSystemActivity('Starting send verification link process', ['email' => $inputData['email']]);
            $isUserExist = self::where('email', $inputData['email'])->first();
            // Check if user exist
            if (!$isUserExist) {
                app(ActivityLogger::class)->logSystemActivity('User not found', ['data' => $inputData], 404);
                app(ActivityLogger::class)->logUserActivity('User not found', ['data' => $inputData]);

                return Response::error(['message' => 'User not found']);
            } else {
                // CCheck if user is already verified
                if (intval($isUserExist->isEmailVerified) === 1) {
                    app(ActivityLogger::class)->logSystemActivity('User email already verified', ['data' => $inputData], 400);
                    app(ActivityLogger::class)->logUserActivity('User email already verified', ['data' => $inputData]);

                    return Response::error(['message' => 'User email already verified']);
                }
                // Send verification link
                $getEmailData = app(EmailTemplates::class)->where('name', 'resend_verification_link')->first();
                $subject = $getEmailData['subject'];
                $verificationLink = env('APP_URL') . 'api/author/verifyEmail?token=' . Crypt::encrypt($inputData['email']);
                $content = str_replace('<verification_link>', $verificationLink, $getEmailData['content']);

                app(MailService::class)->sendMail('no_reply@newzy.com', $inputData['email'], $subject, $content);
                app(ActivityLogger::class)->logSystemActivity('Verification link sent successfully', $inputData, 200, 'json');
                app(ActivityLogger::class)->logUserActivity('Verification link sent successfully', $inputData);

                return Response::success(['message' => 'Verification link sent successfully']);
            }
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), ['data' => $inputData], 500, 'json');
            return Response::error(['message' => $e->getMessage()]);
        }
    }

    public static function getAuthors($id = '')
    {
        try {
            // If not id parameter provided fetch all the author's
            if (!$id) {
                $authors = self::select('id', 'name', 'email', 'specialist', 'role')->where('isEmailVerified', 1)->get();
                if (count($authors) > 0) {
                    return app(Response::class)->success(['authors' => $authors]);
                } else {
                    return app(Response::class)->error(['message' => 'No author found']);
                }

                // If id is passed as a parameter return author with that specific Id
            } else {
                $author = self::select('id', 'name', 'email', 'isEmailVerified', 'specialist', 'role')->where('id', $id)->first();
                if (!$author) {
                    return app(Response::class)->error(['message' => 'Author not found']);
                }
                // Validate if author is verified or not
                if (intval($author->isEmailVerified) === 0) {
                    return app(Response::class)->error(['message' => 'Author not verified']);
                }
                return app(Response::class)->success(['author' => $author]);
            }
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), ['data' => $id], 500, 'json');
            return Response::error(['message' => $e->getMessage()]);
        }
    }
}
