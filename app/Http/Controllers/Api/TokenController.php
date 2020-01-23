<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TokenController extends Controller{
    use  AuthenticatesUsers,ThrottlesLogins;

    function getLogin(Request $request){
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($this->hasTooManyLoginAttempts($request)){
            return response()->json([
                "metadata" => [
                    "code" => 429,
                    "message" => "Maaf! Terlalu Banyak Percobaan Login, Silahkan Coba Lagi Setelah 60 Menit"
                ]
            ], 429);
        }

        $credentials = request(['username', 'password']);
        if (!Auth::attempt($credentials)){
            $this->incrementLoginAttempts($request);
            return response()->json([
                "metadata" => [
                    "code" => 401,
                    "message" => "Whoops! Terjadi Kesalahaan Login, Periksa Kembali Username Dan Password Anda"
                ],
            ], 401);
        }
        $this->clearLoginAttempts($request);

        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        if ($request->remember_me)
            $token->expires_at = Carbon::now()->addWeeks(1);
        $token->save();

        return response()->json([
            "metadata" => [
                "code" => 200,
                "message" => "Ok"
            ],"response" =>[
                "token" => $tokenResult->accessToken,
            ]
        ]);
    }

    public function logout(Request $request){
        $request->user()->token()->revoke();
        return response()->json([
            "diagnostic" => $this->diagnostic("Successfully logged out")
        ]);
    }

    protected function hasTooManyLoginAttempts(Request $request){
        $maxLoginAttempts = 3;
        $lockoutTime = 1; // Dalam menit
        return $this->limiter()->tooManyAttempts(
            $this->throttleKey($request), $maxLoginAttempts, $lockoutTime
        );
    }
}
