<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Validator;
use Illuminate\Support\Facades\Auth;
// use Kreait\Firebase;
use Firebase\Auth\Token\Exception\InvalidToken;
use Laravel\Passport\Client as OClient; 
use Illuminate\Support\Facades\Route;
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
        //  if(Auth::attempt(['email' => $request->email, 'password' => $request->password])) { 
        //               $oClient = OClient::where('password_client', 1)->first();
        //               $user = Auth::user();
        //               $success['token'] = $user->createToken('MyApp')->accessToken;
        //               $success['name']  = $user->name;
        //               $success['refresh_token'] = getTokenAndRefreshToken($oClient, $request->email, $request->password);
        //      if ($user->status == 'true') {
        //         return $this->sendResponse($success, 'User login successfully.');
        //      }
        //      else {
        //         return $this->sendError(['error'=>'Your account has been blocked.']);
        //      }
        //  }
        //  else {
        //     return $this->sendError(['error'=>'Invalid email or password.']);
        //  }
        if (Auth::attempt(['email' => request('email'), 'password' => request('password')])) { 
         // forward the request to the oauth token request endpoint
        $res = Route::dispatch(request()->create('oauth/token', 'POST', $this->credentials($request)));
        // Set api response for successful login
        // dd("checkRes". $res->getContent());
          return $this->sendResponse(json_decode($res->getContent()), 'true');
      } 
      else { 
          return response()->json(['error'=>'Unauthorised'], 401); 
      } 
     }

     protected function credentials(Request $request)
    {
        return $request->only('email', 'password', 'grant_type', 'client_id', 'client_secret', 'scope');
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

  public function __construct(Request $request)
  {
    $oClient = OClient::where('password_client', 1)->first();
    $request->request->add([
      'grant_type' => 'password',
      'client_id' => $oClient->id,
      'client_secret' => $oClient->secret,
      'username' => $request->email,
      'password' => $request->password,
      'scope' => '*',
    ]);
  }
     
}
