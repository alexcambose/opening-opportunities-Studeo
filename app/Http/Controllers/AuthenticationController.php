<?php

namespace App\Http\Controllers;

use App\Logins;
use App\Media;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Http\Request;
use App\User;

class AuthenticationController extends Controller
{
    function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => User::$rules['email'], //username-ul
            'password' => User::$rules['password'],
        ]);

        if ($validator->fails())
            return response()->json([
                'success' => false,
                'message' => $validator->errors()
            ]);

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $ipInfo = json_decode(file_get_contents('http://ip-api.com/json/' . $_SERVER['REMOTE_ADDR']));
            if ($ipInfo->status === 'success') {
                $login = new Logins();
                $login->user_agent = $_SERVER['HTTP_USER_AGENT'];
                $login->ip = $_SERVER['REMOTE_ADDR'];

                $login->city = $ipInfo->city;
                $login->country = $ipInfo->country;
                $login->country_code = $ipInfo->countryCode;
                $login->zip = $ipInfo->zip;
                $login->timezone = $ipInfo->timezone;
                $login->lat = $ipInfo->lat;
                $login->lon = $ipInfo->lon;
                $login->as = $ipInfo->as;
                $login->isp = $ipInfo->isp;
                $login->org = $ipInfo->org;
                Auth::user()->logins()->save($login);
            }

            return response()->json([
                'success' => true,
                'user' => Auth::user(),
                'notifications' => Auth::user()->notifications,
            ]);
        }
        return response()->json([
            'success' => false,
            'message' => trans('auth.failed')
        ]);
    }

    function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'first_name' => User::$rules['first_name'],
            'last_name' => User::$rules['last_name'],
            'username' => User::$rules['username'],
            'email' => User::$rules['email'],
            'password' => User::$rules['password'],
        ]);
        if ($validator->fails())
            return response()->json([
                'success' => false,
                'message' => $validator->errors()
            ]);
        $user = new User();
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->username = $request->username;
        $user->password = bcrypt($request->password);
        $user->email = $request->email;
        $user->image_id = Media::add('images/defaultProfile.png');
        $user->save();

        $this->login($request);
        return response()->json([
            'success' => true,
            'user' => User::find($user->id),
        ]);
    }

    function logout(Request $request) {
        Auth::logout();
        return response()->json(['success' => true]);
    }

}