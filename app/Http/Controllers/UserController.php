<?php
namespace App\Http\Controllers;
use App\Course;
use App\Media;
use App\Notifications\PasswordChanged;
use App\Notifications\BecameMentor;
use App\User;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    function becomeMentor(){
        $user = Auth::user();
        $user->role = 2;
        $user->save();
        Auth::user()->notify(new BecameMentor());
        return response()->json([
            'success' => true,
        ]);
    }
    function user($id = null) {
        $user = Auth::user();
        if($id) $user = User::find($id);
        $user->notifications;
        return response()->json([
            'success' => !!$user,
            'user' => $user,
        ]);
    }

    public function updateProfileImage(Request $request) {
        $user = Auth::user();
        $validation = Validator::make($request->all(), [
            'image' => User::$rules['image'],
        ]);
        if ($validation->fails()) return response()->json(['success' => false]);

        if ($request->hasFile('image') && $request->file('image')->isValid()) {

            //image manipulation stuff
            $image = $request->file('image');
            $filename  = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('userdata/images/'), $filename);

            //add new media row
            $media = Media::add('userdata/images/' . $filename);

            //update user with the media id
            $user->image_id = $media->id;
            $user->save();

            //enjoy
            return response()->json(['success' => true, 'user'=>$user]);
        }
        return response()->json(['success' => false]); // :c
    }

    public function updateProfile(Request $request) {
        $user = Auth::user();
        $validation = Validator::make($request->all(), [
            'nickname' => User::$rules['nickname'],
            'sex' => User::$rules['sex'],
            'description' => User::$rules['description'],
            'school' => User::$rules['school'],
            'school_level' => User::$rules['school_level'],
            'phone' => User::$rules['phone'],
            'city' => User::$rules['city'],
        ]);
        if ($validation->fails()) return response()->json(['success' => false]);
        $user->nickname = $request->nickname;
        $user->sex = $request->sex;
        $user->description = $request->description;
        $user->birthday = $request->birthday;
        $user->school = $request->school;
        $user->school_level = $request->school_level;
        $user->phone = $request->phone;
        $user->cover_color = $request->cover_color;
        $user->city = $request->city;
        $user->save();
        return response()->json([
            'success' => true,
            'user' => $user,
        ]);
    }
    public function updateData(Request $request) {
        $user = Auth::user();
        $validation = Validator::make($request->all(), [
            'first_name' => User::$rules['first_name'],
            'last_name' => User::$rules['last_name'],
            'username' => User::$rules['username'],
            'email' => User::$rules['email'],
        ]);
        if($validation->fails()) return response()->json(['success' => false]);
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->save();
        return response()->json([
            'success' => true,
            'user' => $user,
        ]);
    }
    public function updatePassword(Request $request) {
        $user = Auth::user();
        if(Hash::check($request->current_password, $user->password)) {
            $validation = Validator::make($request->all(), [
                'password' => User::$rules['password'],
            ]);
            if($validation->fails()) return response()->json(['success' => false]);
            $user->password = Hash::make($request->password);
            $user->save();
            Auth::user()->notify(new PasswordChanged());
            return response()->json(['success' => true]);
        }
        return response()->json([
            'success' => false,
            'message' => trans('auth.failed'),
        ]);
    }

    public function shareAll(User $user) {
        return response()->json([
            'success' => true,
            'courses' => $user->sharedCourses()->orderBy('created_at', 'DESC')->get(),
        ]);
    }
    public function shareAdd(Course $course) {
        Auth::user()->sharedCourses()->attach($course->id);
        return response()->json([ 'success' => true ]);
    }

    public function shareRemove($pivotId) {
        Auth::user()->sharedCourses()->wherePivot('id', $pivotId)->detach();
        return response()->json([
            'success' => true,
            'courses' => Auth::user()->sharedCourses()->orderBy('created_at', 'DESC')->get(),
        ]);
    }
}