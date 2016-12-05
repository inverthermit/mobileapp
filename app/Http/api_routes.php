<?php

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', function ($api) {

    /*----------------------------user------------------------------- */
    $api->post('user/signup', 'App\Api\V1\Controllers\UserController@signup');
    $api->post('user/signin', 'App\Api\V1\Controllers\UserController@signin');
    $api->post('user/signin_by_google', 'App\Api\V1\Controllers\UserController@signinByGoogle');
    $api->post('user/signin_by_facebook', 'App\Api\V1\Controllers\UserController@signinByFacebook');
    $api->get('user/refresh_token', 'App\Api\V1\Controllers\UserController@refreshToken');
    $api->get('user/test', 'App\Api\V1\Controllers\UserController@test');

    $api->group(['middleware' => ['jwt.auth']], function ($api) {
        $api->get('user/signout', 'App\Api\V1\Controllers\UserController@signout');
        $api->get('user/list', 'App\Api\V1\Controllers\UserController@lists');

        /*----------------------------calendar------------------------------- */
        $api->get('calendar/list', 'App\Api\V1\Controllers\CalendarController@lists');

        $api->get('calendar/get/{calendarUid}', 'App\Api\V1\Controllers\CalendarController@get');

        $api->post('calendar/insert', 'App\Api\V1\Controllers\CalendarController@insert');
        $api->post('calendar/update/{calendarUid}', 'App\Api\V1\Controllers\CalendarController@update');
        $api->post('calendar/delete/{calendarUid}', 'App\Api\V1\Controllers\CalendarController@delete');
        $api->post('calendar/clear/{calendarUid}', 'App\Api\V1\Controllers\CalendarController@clear');
        $api->post('calendar/share', 'App\Api\V1\Controllers\CalendarController@share');

        /*----------------------------events------------------------------- */
        $api->get('event/list/{calendarUid}', 'App\Api\V1\Controllers\EventController@lists');
        $api->get('event/get/{calendarUid}/{eventUid}', 'App\Api\V1\Controllers\EventController@get');
        $api->post('event/insert', 'App\Api\V1\Controllers\EventController@insert');
        $api->post('event/update/{calendarUid}/{eventUid}', 'App\Api\V1\Controllers\EventController@update');
        $api->post('event/delete/{calendarUid}/{eventUid}', 'App\Api\V1\Controllers\EventController@delete');
        $api->post('event/confirm/{calendarUid}/{eventUid}/{timeslotUid}', 'App\Api\V1\Controllers\EventController@confirm');
        $api->post('event/forward', 'App\Api\V1\Controllers\EventController@forward');

        /*----------------------------invitee------------------------------- */
        $api->post('event/invitee/accept/{calendarUid}/{eventUid}', 'App\Api\V1\Controllers\EventController@acceptEvent');
        $api->post('event/invitee/quit/{calendarUid}/{eventUid}', 'App\Api\V1\Controllers\EventController@quitEvent');

        /*----------------------------timslot------------------------------- */
        $api->post('event/timeslot/accept/{calendarUid}/{eventUid}', 'App\Api\V1\Controllers\EventController@acceptTimeslots');
        $api->post('event/timeslot/reject/{calendarUid}/{eventUid}', 'App\Api\V1\Controllers\EventController@rejectTimeslots');
        $api->post('event/timeslot/recommend', 'App\Api\V1\Controllers\EventController@recommendTimeslots');

        /*----------------------------photo------------------------------- */
        // $api->post('event/photo/upload', 'App\Api\V1\Controllers\EventController@uploadPhoto');
        $api->post('event/photo/update/{calendarUid}/{eventUid}/{photoUid}', 'App\Api\V1\Controllers\EventController@updatePhoto');
        

        /*----------------------------contact------------------------------- */
        $api->get('contact/list', 'App\Api\V1\Controllers\ContactController@lists');
        $api->get('contact/get/{contactUid}', 'App\Api\V1\Controllers\ContactController@get');
        $api->post('contact/insert', 'App\Api\V1\Controllers\ContactController@insert');
        $api->post('contact/update', 'App\Api\V1\Controllers\ContactController@update');
        $api->post('contact/delete', 'App\Api\V1\Controllers\ContactController@delete');

        /*----------------------------message------------------------------- */
        $api->get('message/list_group', 'App\Api\V1\Controllers\MessageController@listGroups');
        $api->get('message/list/{eventUid}', 'App\Api\V1\Controllers\MessageController@lists');
        $api->get('message/get/{messageUid}', 'App\Api\V1\Controllers\MessageController@get');
        $api->post('message/read', 'App\Api\V1\Controllers\MessageController@read');
        $api->post('message/delete', 'App\Api\V1\Controllers\MessageController@delete');
        $api->post('message/clear', 'App\Api\V1\Controllers\MessageController@clear');

        $api->get('message/count_unread', 'App\Api\V1\Controllers\MessageController@countUnread');


    });
    // for Yuhao testing, he owes me 10 dollar
    $api->post('event/photo/upload', 'App\Api\V1\Controllers\EventController@uploadPhoto');

});
