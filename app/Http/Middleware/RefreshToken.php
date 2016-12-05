<?php

/*
 * This file is part of jwt-auth.
 *
 * (c) Sean Tymon <tymon148@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Http\Middleware;

use Tymon\JWTAuth\Middleware\BaseMiddleware;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

use JWTAuth;

class RefreshToken extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        // try {

        //     if (! $user = JWTAuth::parseToken()->authenticate()) {
        //         return response()->json([
        //             'errcode' => 400004,
        //             'errmsg' => 'user not found'
        //         ], 404);
        //     }

        // } catch (TokenExpiredException $e) {
        //     return response()->json([
        //         'errcode' => 400001,
        //         'errmsg' => 'token expired'
        //     ], $e->getStatusCode());

        // } catch (TokenInvalidException $e) {

        //     return response()->json([
        //         'errcode' => 400003,
        //         'errmsg' => 'token invalid'
        //     ], $e->getStatusCode());

        // } catch (JWTException $e) {

        //     return response()->json([
        //         'errcode' => 400002,
        //         'errmsg' => 'token absent'
        //     ], $e->getStatusCode());

        // }

        $response = $next($request);
        try {
            $newToken = $this->auth->setRequest($request)->parseToken()->refresh();
        } catch (TokenExpiredException $e) {
            return $this->respond('tymon.jwt.expired', 'token_expired', $e->getStatusCode(), [$e]);
        } catch (JWTException $e) {
            return $this->respond('tymon.jwt.invalid', 'token_invalid', $e->getStatusCode(), [$e]);
        }
        // send the refreshed token back to the client
        $response->headers->set('Authorization', 'Bearer '.$newToken);

        return $response;
    }
}
