<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Course extends Model
{
    public static $rules = [
        'title' => 'required|string|max:100|min:4',
        'slug' => 'required|string|unique:courses|max:100|min:4',
        'short_description' => 'required|string|max:240',
        'description' => 'required|string|max:4000',
        'difficulty' => 'required|numeric',
        'category' => 'required|numeric',
        'prerequisites' => 'array',
        'purpose' => 'required|string|max:2000',
        'purpose_what_will_learn' => 'array',
        'target_class_level' => 'required|numeric',
        'image' => 'image|mimes:jpeg,png,jpg|max:10000',
    ];
    protected $appends = [
        '_image',
        '_tags',
        '_joined',
        '_user',
        '_shares',
        '_lessons',
        '_xp',
    ];
    public function getImageAttribute(){
        return Media::find($this->image_id);
    }
    public function getTagsAttribute(){
        return $this->tags()->get();
    }
    public function getUserAttribute(){
        return User::find($this->user_id);
    }
    public function getSharesAttribute(){
        return $this->usersShared()->count();
    }
    public function getJoinedAttribute(){
        if($this->isUserJoined(Auth::user())){
            return [
                'users' => count($this->joinedUsersArray()),
                'notes' => $this->notes()->orderBy('created_at', 'DESC')->get(),
            ];
        }
        return false;
    }
    public function getPrerequisitesAttribute($value) {
        return json_decode($value);
    }
    public function getPurposeWhatWillLearnAttribute($value) {
        return json_decode($value);
    }
    public function getLessonsAttribute(){
        return $this->lessons()->get();
    }
    public function getXpAttribute(){
        return $this->xp();
    }
    //methods
    public function joinedUsersArray() {
        $lessons = $this->lessons()->get();
        $users = [];
        foreach ($lessons as $lesson){
            $joinedUsers = $lesson->joinedUsers;
            foreach ($joinedUsers as $joinedUser) $users[] = $joinedUser;
        }
        return $users;
    }
    public function isUserJoined(User $user) {
        return in_array($this->id, $user->joinedCourses()->pluck('id')->toArray());
    }

    public function hasTag(Tag $tag) {
        return $tag->inCourse($this);
    }
    public function xp(){
        return $this->difficulty * 10
            + 6 * $this->lessons()->count()
            + 2 * $this->lessons()->get()->reduce(function($carry, $lesson) { return $carry + $lesson->questions()->count(); });
    }
    // Relationships
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function lessons() {
        return $this->hasMany(Lesson::class);
    }
    public function notes() {
        return $this->hasMany(Note::class);
    }
    public function playlists() {
        return $this->belongsToMany(Playlist::class);
    }
    public function paths() {
        return $this->belongsToMany(Path::class);
    }
    public function tags() {
        return $this->belongsToMany(Tag::class);
    }
    public function usersShared() {
        return $this->belongsToMany(Course::class,'course_user_shares')->withTimestamps();
    }
}
