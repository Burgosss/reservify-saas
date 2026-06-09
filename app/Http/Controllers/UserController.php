<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function staffByTenant()
    {
        $staff = User::where('role', 'staff')
                    ->select('id', 'name', 'email')
                    ->get();

        return response()->json($staff);
    }
}
