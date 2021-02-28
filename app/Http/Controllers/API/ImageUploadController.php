<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Image;
class ImageUploadController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
        'image' => 'required|image:jpeg,png,jpg,gif,svg,mp4|max:1000000']);

    if ($validator->fails()) {
        return response()->json($validator->messages()->first(), 500);
        // return sendCustomResponse($validator->messages()->first(),  'error', 500);
    }

    $uploadFolder = 'users';
    $image = $request->file('image');
    $image_uploaded_path = $image->store($uploadFolder, 'public');
    $uploadedImageResponse = array(
        "image_name" => basename($image_uploaded_path),
        "image_url" => Storage::disk('public')->url($image_uploaded_path),
        "mime" => $image->getClientMimeType()
    );
    $image = User::create($uploadedImageResponse);
    return response()->json($uploadedImageResponse, 200);
    // return sendCustomResponse('File Uploaded Successfully', 'success', 200, $uploadedImageResponse);
    }
}
