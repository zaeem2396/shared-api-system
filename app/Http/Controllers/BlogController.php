<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\User;
use App\Utils\Cloudinary;
use App\Utils\Response;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File;

class BlogController extends Controller
{

    protected $blog;
    protected $blogCat;
    protected $response;
    protected $cloud;
    protected $user;

    public function __construct(Blog $blog, BlogCategory $blogCat, Response $response, Cloudinary $cloud, User $user)
    {
        $this->blog = $blog;
        $this->blogCat = $blogCat;
        $this->response = $response;
        $this->cloud = $cloud;
        $this->user = $user;
    }

    public function create(Request $request)
    {
        try {
            $inputData = $request->only('name');
            $validator = Validator::make($inputData, [
                'name' => 'required|max:255'
            ]);

            if ($validator->fails()) {
                return $this->response->error(['errors' => $validator->errors()->all()]);
            }

            $isCategoryCreated = $this->blogCat->createCategory($inputData);
            return $isCategoryCreated;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getCategory()
    {
        try {
            return $this->blogCat->all();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function update(Request $request)
    {
        try {
            $inputData = $request->only('id', 'name');
            $validator = Validator::make($inputData, [
                'id' => 'required',
                'name' => 'required|max:255'
            ]);

            if ($validator->fails()) {
                return $this->response->error(['errors' => $validator->errors()->all()]);
            }

            $isCategoryUpdated = $this->blogCat->updateCategory($inputData);
            return $isCategoryUpdated;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function createBlog(Request $request)
    {
        try {
            $inputData = $request->only('authorId', 'categoryId', 'title', 'summary', 'image', 'region');

            $validator = Validator::make($inputData, [
                'authorId' => 'required',
                'categoryId' => 'required',
                'title' => 'required|string',
                'summary' => 'required|string',
                'image' => ['required', File::types(['jpg', 'jpeg', 'png'])->image()->max(3072)],
                'region' => 'required|string'
            ]);

            if ($validator->fails()) {
                return $this->response->error(['errors' => $validator->errors()->all()]);
            }

            $isBlogCreated = $this->blog->createBlog($inputData);
            return $isBlogCreated;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function updateBlog(Request $request)
    {
        try {
            $inputData = $request->only('id', 'authorId', 'categoryId', 'title', 'summary', 'image', 'region');

            $validator = Validator::make($inputData, [
                'id' => 'required',
                'authorId' => 'required',
                'categoryId' => 'required',
                'title' => 'required|string',
                'summary' => 'required|string',
                'image' => ['required', File::types(['jpg', 'jpeg', 'png'])->image()->max(3072)],
                'region' => 'required|string'
            ]);

            if ($validator->fails()) {
                return $this->response->error(['errors' => $validator->errors()->all()]);
            }

            $isBlogUpdated = $this->blog->updateBlog($inputData);
            return $isBlogUpdated;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getBlog(Request $request)
    {
        try {
            $inputData = $request->only('region', 'categoryId', 'date', 'perPage');

            $validator = Validator::make($inputData, [
                'region' => 'nullable|string',
                'categoryId' => 'nullable|string',
                'date' => 'nullable|date',
                'perPage' => 'nullable|integer'
            ]);

            if ($validator->fails()) {
                return $this->response->error(['errors' => $validator->errors()->all()]);
            }
            $blogs = $this->blog->fetchBlogs($inputData);
            return $blogs;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getTodaysBlogsByCategory(Request $request)
    {
        try {
            $inputData = $request->only('categoryId', 'limit');
            $validator = Validator::make($inputData, [
                'categoryId' => 'required|string',
                'limit' => 'required|integer'
            ]);
            if ($validator->fails()) {
                return $this->response->error(['errors' => $validator->errors()->all()]);
            }
            $blogs = $this->blog->fetchTodaysBlogsByCategory($inputData);
            return $blogs;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
