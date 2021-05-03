<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Validator;

class UserProfileController extends BaseController
{
    public function show() {
        $user = Auth::user();
        $checkAvatar = $user->avatar_url;
        if (filter_var($checkAvatar, FILTER_VALIDATE_URL) === FALSE) { //not valid url
                // $image = Storage::disk('public')->url($user->avatar_url);
            if (is_null($checkAvatar)) {
                $checkAvatar = 'users/default.jpeg';
            }
            $expiresAt = new \DateTime('tomorrow');  
            $imageReference = app('firebase.storage')->getBucket()->object($checkAvatar);  
            if($imageReference->exists())
                $image = $imageReference->signedUrl($expiresAt); 
            else {
                $image = Storage::disk('public')->url($def_image);
            }
        }else {
            $image = $user->avatar_url;
        }
        $profile = ['name' => $user->name, 
                    'email' => $user->email,
                    'age' => $user->age,
                    'phone_number' => $user->phone_number,
                    'avatar' => $image];
        
        return $this->sendResponse($profile, 'Retrieve profile successfully');
    }

    public function update(Request $request, User $user) {
        $input = $request->all();

        $profile = Auth::user();
        $email   = $profile->email;

        $UpdateDetails = User::where('email', $email)->first();

        if (is_null($UpdateDetails)) {
            return false;
        }

        $validator = Validator::make($input, [
            'age' => 'required',
            'phone_number' => 'required|max:10'
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        
        $UpdateDetails->age = $input['age'];
        $UpdateDetails->phone_number = $input['phone_number'];
        $UpdateDetails->save();

        return $this->sendResponse($UpdateDetails, 'User profile updated successfully.');
    }

    public function upload(Request $request) {        
        $profile = Auth::user();
        $email = $profile->email;

        $findProfile = User::where('email', $email)->first();

        if (is_null($findProfile)) {
            return false;
        }

        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:jpeg,jpg,png,gif|max:100000']);
    
        if ($validator->fails()) {
            return response()->json($validator->messages()->first(), 500);
            // return sendCustomResponse($validator->messages()->first(),  'error', 500);
        }

        $storage = app('firebase.storage');
        $storageClient = $storage->getStorageClient();
        $defaultBucket = $storage->getBucket();

        $prevImage = $profile->avatar_url;
        if ($prevImage) {
            Storage::disk('public')->delete($prevImage);
            $imageReference = $storage->getBucket()->object($prevImage); 
            if ($imageReference->exists()) {
                $imageDeleted = $imageReference->delete(); 
            }
        }
        
        $image = $request->file('image');
        $firFolder = 'users/';
        $uploadFolder = 'users';
        //firebase cloud storage 
        $localfolder = public_path('users') .'/';  
        $name = time() .'-'.$image->getClientOriginalName();
        $file = $name;  
        if ($image->move($uploadFolder, $file)) {  
            $uploadedfile = fopen($localfolder.$file, 'r');  
            app('firebase.storage')->getBucket()->upload($uploadedfile, ['name' => $firFolder . $file]);  
            //will remove from local laravel folder  
            unlink($localfolder . $file);  
            $findProfile->avatar_url = $firFolder . $file;
            $findProfile->save();
        } 
        
        //local storage
        // $image_uploaded_path = $image->store($uploadFolder, 'public');
        // $uploadedImageResponse = array(
        //     "image_name" => basename($image_uploaded_path),
        //     "image_url" => Storage::disk('public')->url($image_uploaded_path),
        //     "mime" => $image->getClientMimeType()
        // );
        // $findProfile->avatar_url = $image_uploaded_path;
        // $findProfile->save();
        
        return $this->sendResponse($firFolder . $file, 'Avatar updated successfully');
    }

    public function block(Request $request, User $user) {
        $input = $request->all();

        $validator = Validator::make($input, [
            'status' => 'required',
            'email' => 'required'
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        $email = $input['email'];
        
        $UpdateDetails = User::where('email', $email)->first();
        if (is_null($UpdateDetails)) {
            // return false;
            return $this->sendResponse([], 'User does not exist');
        }

        $UpdateDetails->status = $input['status'];
        $UpdateDetails->save();

        // foreach($userTokens as $token) {
        //     $token->revoke();   
        // }        

        $newStatus = $input['status'];
        if ($newStatus == 'false') {
            return $this->sendResponse([], 'User has been blocked successfully');
        }else {
            return $this->sendResponse([], 'User has been unblocked successfully');
        }
    }
}
