<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadPhotoRequest;
use App\Http\Resources\PhotoResource;
use App\Http\Resources\TagResource;
use App\Models\PhotoTag;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TagController extends Controller
{
    public function index()
    {
        $q = \request('q');
        $tags = Tag::with(['photos'])->when($q, function ($query) use ($q) {
            return $query->where('name', $q);
        })->get();

        return TagResource::collection($tags);
    }

    public function store(UploadPhotoRequest $request)
    {
        try {
            $file = $request->file('photo');
            $fileName = md5($file->hashName() . time());
            $file->storeAs('', $fileName, 'photos');

            foreach ($request->tags as $tag) {
                $tag = Tag::query()->create(['name' => $tag]);
                $tag->photos()->create(['file_name' => $fileName]);
            }

            return response()->json(['عملیات با موفقیت انجام شد.'], Response::HTTP_CREATED);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return response()->json(['message' => 'خطا در ایجاد برچسب'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'tags' => 'required|array',
            'tags.*' => 'string',
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()]);
        }

        try {
            if ($photoTag = PhotoTag::query()->where('file_name', $request->file_name)->exists()) {

                foreach ($request->tags as $tag) {
                    $tag = Tag::query()->firstOrCreate(['name' => $tag]);
                    $tag->photos()->create(['file_name' => $request->file_name]);
                }
            } else {
                return response()->json(['message' => 'فایلی با این نام وجود ندارد'], Response::HTTP_BAD_REQUEST);
            }

            return response()->json(['عملیات با موفقیت انجام شد.'], Response::HTTP_CREATED);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return response()->json(['message' => 'خطا در ایجاد برچسب'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(Request $request)
    {
        try {
            Tag::query()->findOrFail($request->tag_id)->delete();

            return response()->json(['برچسب با موفقیت حذف شد.'], Response::HTTP_OK);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return response()->json(['message' => 'خطا در حذف برچسب'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Request $request)
    {
        try {
            PhotoTag::query()->where('file_name', $request->file_name)->delete();

            Storage::disk('photos')->delete($request->file_name);

            return response()->json(['عکس با موفقیت حذف شد.'], Response::HTTP_OK);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return response()->json(['message' => 'خطا در حذف عکس'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function photos(Request $request)
    {
        $tag = $request->tag_name;
        $photos = PhotoTag::query()->when($tag, function ($query) use ($tag) {
            return $query->leftJoin('tags', 'tags.id', '=', 'photo_tags.tag_id')->where('tags.name', 'LIKE', '%' . $tag . '%');
        })->groupBy('file_name')->get();

        return PhotoResource::collection($photos);
    }
}
