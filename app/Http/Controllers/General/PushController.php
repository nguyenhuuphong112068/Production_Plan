<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class PushController extends Controller
{
    /**
     * Store the user's subscription.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        \Log::info('Push Subscription Request:', $request->all());

        try {
            $request->validate([
                'endpoint'    => 'required',
                'keys.auth'   => 'required',
                'keys.p256dh' => 'required'
            ]);

            $endpoint = $request->endpoint;
            $key = $request->keys['p256dh'];
            $token = $request->keys['auth'];
            
            // Lấy user hiện tại từ session
            $sessionUser = session('user');
            if (!$sessionUser || !isset($sessionUser['userId'])) {
                \Log::warning('Push Subscription: Session user not found');
                return response()->json(['success' => false, 'message' => 'Session expired'], 401);
            }

            $userId = $sessionUser['userId'];
            $user = User::find($userId);

            if ($user) {
                \Log::info('Push Subscription: Saving for user ID ' . $userId);
                $user->updatePushSubscription($endpoint, $key, $token);
                return response()->json(['success' => true]);
            }

            \Log::error('Push Subscription: User ID ' . $userId . ' not found in DB');
            return response()->json(['success' => false, 'message' => 'User not found'], 404);

        } catch (\Exception $e) {
            \Log::error('Push Subscription Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete the user's subscription.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'endpoint' => 'required'
        ]);

        $userId = session('user')['userId'];
        $user = User::find($userId);

        if ($user) {
            $user->deletePushSubscription($request->endpoint);
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'User not found'], 404);
    }
}
