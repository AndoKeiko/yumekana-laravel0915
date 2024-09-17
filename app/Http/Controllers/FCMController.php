<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class FCMController extends Controller
{
    public function storeToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        $user = $request->user();
        $user->fcm_token = $request->token;
        $user->save();

        return response()->json(['message' => 'Token stored successfully']);
    }

    public function sendNotification($userId, $title, $body)
    {
        $user = User::find($userId);
        $fcmToken = $user->fcm_token;

        $serverKey = env('FIREBASE_SERVER_KEY');
        $data = [
            "to" => $fcmToken,
            "notification" => [
                "title" => $title,
                "body" => $body,
            ]
        ];
        $encodedData = json_encode($data);

        $headers = [
            'Authorization:key=' . $serverKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);

        $result = curl_exec($ch);
        if ($result === FALSE) {
            die('Curl failed: ' . curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }
}
