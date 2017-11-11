<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    function notifications(){
        return response()->json([
            'success' => true,
            'notifications' => Auth::user()->notifications,
        ]);
    }
    function toggle(Request $request){
        $notification = Auth::user()->notifications()->find($request->id);
        if($request->read){
            $notification->markAsRead();
        } else {
            $notification->read_at = null;
        }
        $notification->save();

        return response()->json($notification);
    }
    function toggleReadAll(){
        $notifications = Auth::user()->notifications;

        $shouldMarkAsRead = Auth::user()->notifications()->where('read_at', null)->count();

        foreach ($notifications as $notification) {
            if($shouldMarkAsRead) {
                $notification->markAsRead();
            } else {
                $notification->read_at = null;
            }
            $notification->save();
        }
        return response()->json([
            'success' => true,
            'readall' => !!$shouldMarkAsRead,
        ]);
    }
}
