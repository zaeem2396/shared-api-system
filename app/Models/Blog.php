<?php

namespace App\Models;

use App\Models\User;
use App\Utils\Response;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use HasFactory;

    public static function createBlog(array $inputData)
    {
        $allowedExt = ['jpg', 'png', 'jpeg'];
        if (!in_array($inputData['image']->getClientOriginalExtension(), $allowedExt)) {
            return app(Response::class)->error(['response' => 'Invalid image type, please upload only ' . implode(',', $allowedExt)]);
        }
        $users = explode(' ', $inputData['authorId']);
        // where in eloquent not working 
        $ifUserExist = User::whereIn('id', $users)->get();
        var_dump($ifUserExist);
    }
}
