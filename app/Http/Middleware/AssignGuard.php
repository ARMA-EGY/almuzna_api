<?php

namespace App\Http\Middleware;

use App\Traits\GeneralTrait;
use JWTAuth;
use Closure;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class AssignGuard extends BaseMiddleware
{
    use GeneralTrait;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if($guard != null){
            auth()->shouldUse($guard); //shoud you user guard / table

           // dd($guard);
            $token = $request->header('auth-token');

            $request->headers->set('auth-token', (string) $token, true);

            $request->headers->set('Authorization', 'Bearer '.$token, true);

            try {
                $user = auth()->check();

                 if(!$user)
                 {

                    $error = $this -> returnError('401','Unauthenticated user');
                    return response()->json($error, 401); 
                 }

  //check authenticted user
            } catch (TokenExpiredException $e) {
               $error = $this -> returnError('401','Unauthenticated user');
               //return response()->json($error, 401); 
            } catch (JWTException $e) {

                $error = $this -> returnError('', $e->getMessage());
                return response()->json($error, 500); 
            }

        }
         return $next($request);
    }
}
