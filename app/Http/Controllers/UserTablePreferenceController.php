<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserTablePreference;
use Illuminate\Support\Facades\Auth;

class UserTablePreferenceController extends Controller
{
    public function save(Request $request)
    {
        $request->validate([
            'table_name' => 'required|string',
            'preferences' => 'nullable|array',
        ]);

        $userId = Auth::id() ?? 1; // Fallback to 1 for dev if not logged in

        $pref = UserTablePreference::updateOrCreate(
            [
                'user_id' => $userId,
                'table_name' => $request->table_name,
            ],
            [
                'preferences' => $request->preferences,
            ]
        );

        return response()->json(['success' => true, 'data' => $pref]);
    }

    public function load(Request $request)
    {
        $request->validate([
            'table_name' => 'required|string',
        ]);

        $userId = Auth::id() ?? 1;

        $pref = UserTablePreference::where('user_id', $userId)
            ->where('table_name', $request->table_name)
            ->first();

        return response()->json([
            'success' => true,
            'preferences' => $pref ? $pref->preferences : null,
        ]);
    }
}
