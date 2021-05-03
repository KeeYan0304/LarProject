<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;

class CheckBanned
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    protected $auth;

    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    public function handle(Request $request, Closure $next)
    {
        if (is_null($this->auth->user())) {
            return $next($request);
        }else {
            $status = $this->auth->user()->status;
            if ($status == 'false') {
                $response = [
                    'success' => false, 
                    'message' => ['error' => 'Your account has been blocked'],
                ];
                return response()->json($response, 403);
            }else {
                return $next($request);
            }
        }
    }  
}
