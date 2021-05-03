<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\Auth;
// use Kreait\Firebase;
use Firebase\Auth\Token\Exception\InvalidToken;

class RegisterController extends BaseController
{

    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request) 
    {
        $validator = Validator::make($request->all(),[
            'name'         => 'required',
            'email'        => 'required|unique:users,email',
            'password'     => 'required',
            'c_password'   => 'required|same:password',
            'phone_number' => 'required|max:10',
            'age'          => 'required',
        ]);

        if ($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $input = $request->all();
        $input['status'] = 'true';
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['token'] = $user->createToken('MyApp')->accessToken;
        $success['name'] = $user->name;
        $success['status'] = $user->status;

        return $this->sendResponse($success, 'User registered successfully.');
    }

    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */

     public function login(Request $request)
     {
         if(Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                      $user = Auth::user();
                      $success['token'] = $user->createToken('MyApp')->accessToken;
                      $success['name']  = $user->name;
             if ($user->status == 'true') {
                return $this->sendResponse($success, 'User login successfully.');
             }
             else {
                return $this->sendError(['error'=>'Your account has been blocked.']);
             }
         }
         else {
            return $this->sendError(['error'=>'Invalid email or password.']);
         }
     }

     public function verifyFirToken(Request $request) {
        $auth = app('firebase.auth');
        $input = $request->all();
        $idTokenString = $input['firebase_token'];
        try { // Try to verify the Firebase credential token with Google
    
            $verifiedIdToken = $auth->verifyIdToken($idTokenString);
            
          } catch (\InvalidArgumentException $e) { // If the token has the wrong format
            
            return response()->json([
                'message' => 'Unauthorized - Can\'t parse the token: ' . $e->getMessage()
            ], 401);        
            
          } catch (InvalidToken $e) { // If the token is invalid (expired ...)
            
            return response()->json([
                'message' => 'Unauthorized - Token is invalide: ' . $e->getMessage()
            ], 401);
            
          }
          // Retrieve the UID (User ID) from the verified Firebase credential's token
        $uid = $verifiedIdToken->getClaim('sub');

        $user = $auth->getUser($uid);
        $email = $user->email;
        $uid = $user->uid;
        $displayName = $user->displayName;
        $phone = $user->phoneNumber;
        $avatar = $user->photoUrl;

      $findProfile = User::where('email', $email)->first();
      if (is_null($findProfile)) {
        $data = array(
          'name' => $displayName,
          'email' => $email,
          'phone_number' => $phone,
          'password' => '',
          'c_password' => '',
          'age'  => '',
          'avatar_url' => $avatar,
        );
        
        $data['password'] = bcrypt($data['password']);
        $user = User::create($data);
        $success['token'] = $user->createToken('MyApp')->accessToken;
        $success['name'] = $user->name;
      }
      else {
        $user = $findProfile;
        $success['token'] = $user->createToken('MyApp')->accessToken;
        $success['name']  = $user->name;
      }

      return $this->sendResponse($success, 'User logged in successfully.');
  
  // Return a JSON object containing the token datas
  // You may format this object to suit your needs
  // return response()->json([
  //   // 'uid'         => $uid,
  //   // 'email'       => $email,
  //   // 'displayName' => $displayName,
  //   // 'phone' => $phone,
  //   'phone' => $user,
  //   // 'token_type' => 'Bearer',
  //   // 'expires_at' => Carbon::parse(
  //   //   $tokenResult->token->expires_at
  //   // )->toDateTimeString()
  // ]);
     }
     
}
