<?php

namespace App\Helpers;

class Firebase
{

    //public $firebase_api_key;

    public $title;
    public $message;
    public $image;
    // push message payload
    public $data;
    // flag indicating whether to show the push notification or not
    // this flag will be useful when perform some opertation in background when push is recevied
    public $is_background;

    public function api_key($key)
    {
        $this->firebase_api_key = $key;
    }

    // sending push message to single user by firebase reg id
    public function send($to)
    {
        if (is_string($to)) {
            $to = array($to);
        }
        $fields = array(
            'registration_ids' => $to,
            'notification' => array(
                'body' => $this->body,
                'sound' => 'default'
            ),
            'data' => $this->data,
        );

        if ($this->title != "") {
            $fields['notification']['title'] = $this->title;
        }

        return $this->sendPushNotification($fields);
    }

    // Sending message to a topic by topic name
    public function sendToTopic($to, $message)
    {
        $fields = array(
            'to' => '/topics/' . $to,
            'data' => $message,
        );
        return $this->sendPushNotification($fields);
    }


    // sending push message to multiple users by firebase registration ids
    public static function sendMultiple($registration_ids, $message)
    {
        $change_message = [];
        $change_message['click_action'] = '';
        $change_message['title'] = $message['title'];
        $change_message['body'] = $message['body'];
        $change_message['page'] = $message['page'];
        $change_message['portal'] = $message['portal'];
        $change_message['data'] = $message['data'];

        $fields = array(
            'registration_ids' => $registration_ids,
            'data' => $change_message,
            'priority' => 'high',
            //'notification' => $change_message,
        );

        return Firebase::sendPushNotification($fields);
    }

    public static function sendMultipleGreeting($registration_ids, $message)
    {
        $change_message = [];
        $change_message['click_action'] = '';
        $change_message['title'] = $message['title'];
        $change_message['body'] = $message['body'];
        $change_message['page'] = $message['page'];
        $change_message['portal'] = $message['portal'];
        $change_message['data'] = $message['data'];

        $fields = array(
            'to' => $registration_ids,
            'data' => $change_message
            //'notification' => $change_message,
        );

        return Firebase::sendPushNotification($fields);
    }


    public static function sendMultipleMbl($registration_ids, $message)
    {
        $change_message = [];
        $change_message['click_action'] = '';
        $change_message['title'] = $message['title'];
        $change_message['body'] = $message['body'];
        $change_message['page'] = $message['page'];
        $change_message['portal'] = $message['portal'];
        $change_message['data'] = $message['data'];
        $change_message['content_available'] = true;
        $fields = array(
            'registration_ids' => $registration_ids,
            'data' => $change_message,
            'priority' => 'high',
            'notification' => $change_message,
        );
        return Firebase::sendPushNotification($fields);
    }


    public static function sendSingle($registration_ids, $message)
    {
        $change_message = [];
        $change_message['click_action'] = '';
        $change_message['title'] = $message['title'];
        $change_message['body'] = $message['body'];
        $change_message['page'] = $message['page'];
        $change_message['portal'] = $message['portal'];
        $change_message['data'] = $message['data'];

        $fields = array(
            'to' => $registration_ids,
            'data' => $change_message,
            'priority' => 'high'
            //'notification' => $change_message,
        );

        return Firebase::sendPushNotification($fields);
    }

    public static function sendSingleMbl($registration_ids, $message)
    {
        $change_message = [];
        $change_message['click_action'] = '';
        $change_message['title'] = $message['title'];
        $change_message['body'] = $message['body'];
        $change_message['page'] = $message['page'];
        $change_message['portal'] = $message['portal'];
        $change_message['data'] = $message['data'];
        $change_message['content_available'] = true;

        $fields = array(
            'to' => $registration_ids,
            'data' => $change_message,
            'priority' => 'high',
            'notification' => $change_message
        );

        return Firebase::sendPushNotification($fields);
    }
    // function makes curl request to firebase servers
    private static function sendPushNotification($fields)
    {

        // Set POST variables
        $url = 'https://fcm.googleapis.com/fcm/send';

        $headers = array(
            'Authorization: key=' . env('SERVER_KEY'),
            'Content-Type: application/json'
        );
        // Open connection
        $ch = curl_init();

        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSLVERSION, 'CURL_SSLVERSION_SSLv3');

        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        // Execute post
        $result = curl_exec($ch);
        if ($result === FALSE) {
            die('Curl failed: ' . curl_error($ch));
        }

        // Close connection
        curl_close($ch);

        return $result;
    }
}
