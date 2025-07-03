<?php

namespace App\Http\Controllers\V2\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InstructorController extends Controller
{
    /**
     * list all instructors
     * cache in redis
     */
    public function getAllInstructors () {
        try {
            //code...
        } catch (\Throwable $e) {
            //throw $th;
        }
    }

    /**
     * get instructors details by id
     */
    public function getInstructorById () {
        try {
            //code...
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * assign a general user to become an instructor
     * FLUSH redis
     */
    public function assignInstructor () {
        try {
            //code...
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * assign an instructor to a group
     */
    public function assignInstructorToGroup () {
        try {
            //code...
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * remove an instructor from a group
     */
    public function removeInstructorFromGroup () {
        try {
            //code...
        } catch (\Throwable $th) {
            //throw $th;
        }
    }


}
