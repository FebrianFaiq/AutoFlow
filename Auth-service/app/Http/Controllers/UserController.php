<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Laravel\Lumen\Routing\Controller as BaseController;

class UserController extends BaseController
{
    public function profile(Request $request)
    {
        return response()->json(['user' => $request->auth]);
    }
}