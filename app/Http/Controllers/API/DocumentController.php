<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator,Redirect,Response,File;
use Illuminate\Support\Facades\Storage;
use App\Models\Documents;

class DocumentController extends Controller
{
    public function store(Request $request)
    {
       $validator = Validator::make($request->all(), 
              [ 
              'user_id' => 'required',
              'file' => 'required|mimes:mp4,doc,docx,pdf,txt|max:1000000',
             ]);   
 
    if ($validator->fails()) {          
            return response()->json(['error'=>$validator->errors()], 401);                        
         }  
 
  
        if ($files = $request->file('file')) {
             
            //store file into document folder
            $file = $request->file->store('public/documents');
 
            //store your file into database
            $document = $request->file('file');
            $uploadFolder = 'documents';
            $doc_uploaded_path = $document->store($uploadFolder, 'public');

            $uploadedDocResponse = array(
                "title" => $doc_uploaded_path,
                "user_id" => $request->user_id,
                "document_url" => Storage::disk('public')->url($doc_uploaded_path)
            );

            $doc = Documents::create($uploadedDocResponse);
            return response()->json($uploadedDocResponse, 200);
              
            // return response()->json([
            //     "success" => true,
            //     "message" => "File successfully uploaded",
            //     "file" => $file
            // ]);
  
        }
 
  
    }
}
