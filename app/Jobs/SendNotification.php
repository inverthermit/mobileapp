<?php
/**
 * Created by PhpStorm.
 * User: GooD-YeaR
 * Date: 2016/10/11
 * Time: 15:31
 */

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use Log;
use App\Models\User;
use App\Models\Event;
use App\Models\Calendar;
use App\Models\Message;

use App\Models\Invitee;

abstract class MessageType
{
    const CreateGroupEvent = 0;
    const UpdateGroupEvent = 1;
    const ConfirmGroupEvent = 2;
    const DeleteGroupEvent = 3;
    const AcceptGroupEvent = 4;
    const QuitGroupEvent = 5;


    // etc.
}


class SendNotification extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * @var [array]
     */
    public $data;
    public $message_type;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data, $type = MessageType::CreateGroupEvent)
    {
        $this->data = $data;

        $this->message_type = $type;


    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // there event doesn't have photo, invitee, timeslot field
        // Log::info('SendNotification:'.$this->event['summary']);

        $event = $this->data['event'];

        switch ($this->message_type) {

            case MessageType::CreateGroupEvent:

                $invitee_user_uid = [];
                foreach ($event['invitee'] as $invitee) {

                    if ($event['hostUserUid'] != $invitee['userUid']) {

                        $invitee_user_uid[] = $invitee['userUid']."";

                        $message = new Message();
                        $message->userUid = $invitee['userUid'];
                        $message->eventUid = $event["eventUid"];

                        $message->title = $event["summary"];
                        $message->subtitle1 = $event["hostUserUid"]." invites you to this event";
                        $message->subtitle2 = "Waiting for your approval";

                        $message->hasBadge = 1;

                        $message->save();

                        info("ABCDEFG");

                    }

                }

                $data = [
                    "message_content" => "You received a new event invitation",
                    "eventUid" => $event["eventUid"],
                    "calendarUid" => $event["calendarUid"],
                    "action" => "create"
                ];


                $this->sendMessage(true, $invitee_user_uid,$data);
                $this->sendMessage(false, $invitee_user_uid,$data);

                break;
            case MessageType::UpdateGroupEvent:



                $updatedFields = $this->data['updatedFields'];

                foreach ($event['invitee'] as $invitee) {

                    if ($event['hostUserUid'] != $invitee['userUid']) {

                        $invitee_user_uid[] = $invitee['userUid']."";


                        $message = new Message();
                        $message->userUid = $invitee['userUid'];
                        $message->eventUid = $event["eventUid"];

                        $message->title = $event["summary"];
                        $message->subtitle1 = $event["hostUserUid"]." changed ".implode(" ",$updatedFields);
                        $message->subtitle2 = "Waiting for your approval";

                        $message->hasBadge = 1;

                        $message->save();


                    }

                }


                $data = [
                    "message_content" => "Your group event updated",
                    "eventUid" => $event["eventUid"],
                    "calendarUid" => $event["calendarUid"],
                    "action" => "update"
                ];

                $this->sendMessage(true, $invitee_user_uid, $data);
                $this->sendMessage(false, $invitee_user_uid, $data);


                $in_invitee_user_uid = Invitee::whereIn('invitee_uid', $this->data['inInvitees'])
                    ->where('delete_level', 0)
                    ->get()->map(function ($iv) {
                        return $iv->userUid."";
                    });


                foreach ($in_invitee_user_uid as $userUid){

                    $message = new Message();
                    $message->userUid = $userUid;
                    $message->eventUid = $event["eventUid"];

                    $message->title = $event["summary"];
                    $message->subtitle1 = $event["hostUserUid"]." invites you to this event";
                    $message->subtitle2 = "Waiting for your approval";

                    $message->hasBadge = 1;

                    $message->save();


                }




                $data = [
                    "message_content" => "You received a new event invitation",
                    "eventUid" => $event["eventUid"],
                    "calendarUid" => $event["calendarUid"],
                    "action" => "create"
                ];



                $this->sendMessage(true, $in_invitee_user_uid, $data);
                $this->sendMessage(false, $in_invitee_user_uid, $data);


                $out_invitee_user_uid = Invitee::whereIn('invitee_uid', $this->data['outInvitees'])
                    ->where('delete_level', 1)
                    ->get()->map(function ($iv) {
                        return $iv->userUid."";
                    });


                info('-----');
                info($this->data['outInvitees']);


                foreach ($out_invitee_user_uid as $userUid){

                    $message = new Message();
                    $message->userUid = $userUid;
                    $message->eventUid = $event["eventUid"];

                    $message->title = $event["summary"];
                    $message->subtitle1 = $event["hostUserUid"]." removed you from this event";


                    $message->save();


                }


                $data = [
                    "message_content" => "You was removed from a group event",
                    "eventUid" => $event["eventUid"],
                    "calendarUid" => $event["calendarUid"],
                    "action" => "delete"
                ];


                $this->sendMessage(true, $out_invitee_user_uid, $data);
                $this->sendMessage(false, $out_invitee_user_uid, $data);

                break;
            case MessageType::DeleteGroupEvent:

                foreach ($event['invitee'] as $invitee) {

                    if ($event['hostUserUid'] != $invitee['userUid']) {

                        $invitee_user_uid[] = $invitee['userUid']."";



                        $message = new Message();
                        $message->userUid = $invitee['userUid'];
                        $message->eventUid = $event["eventUid"];

                        $message->title = $event["summary"];
                        $message->subtitle1 = $event["hostUserUid"]." deleted this group event";


                        $message->save();
                    }

                }

                $data = [
                    "message_content" => "A group event has been deleted by host",
                    "eventUid" => $event["eventUid"],
                    "calendarUid" => $event["calendarUid"],
                    "action" => "delete"
                ];



                $this->sendMessage(true, $invitee_user_uid, $data);
                $this->sendMessage(false, $invitee_user_uid, $data);

                break;
            case MessageType::ConfirmGroupEvent:

                foreach ($event['invitee'] as $invitee) {

                    if ($event['hostUserUid'] != $invitee['userUid']) {

                        $invitee_user_uid[] = $invitee['userUid']."";

                        $message = new Message();
                        $message->userUid = $invitee['userUid'];
                        $message->eventUid = $event["eventUid"];

                        $message->title = $event["summary"];
                        $message->subtitle1 = $event["hostUserUid"]." confirmed this group event";


                        $message->save();


                    }

                }

                $data = [
                    "message_content" => "Host confirmed a group event",
                    "eventUid" => $event["eventUid"],
                    "calendarUid" => $event["calendarUid"],
                    "action" => "host_confirmed"
                ];

                $this->sendMessage(true, $invitee_user_uid, $data);
                $this->sendMessage(false, $invitee_user_uid, $data);

                break;
            case MessageType::AcceptGroupEvent:


                $data = [
                    "message_content" => "Some invitee accepted your group event",
                    "eventUid" => $event["eventUid"],
                    "calendarUid" => $event["calendarUid"],
                    "action" => "invitee_accepted"
                ];


                $message = new Message();
                $message->userUid = $event['hostUserUid'];
                $message->eventUid = $event["eventUid"];

                $message->title = $event["summary"];
                $message->subtitle1 = $event["userUid"]." accepted some timeslot(s)";
                $message->subtitle2 = "Waiting for ".$event["hostUserUid"]." to confirm";


                $message->save();


                $this->sendMessage(true, [$event["hostUserUid"].""], $data);
                $this->sendMessage(false, [$event["hostUserUid"].""], $data);



                break;
            case MessageType::QuitGroupEvent:

                $data = [
                    "message_content" => "Some invitee quited your group event",
                    "eventUid" => $event["eventUid"],
                    "calendarUid" => $event["calendarUid"],
                    "action" => "invitee_quited"
                ];



                $message = new Message();
                $message->userUid = $event['userUid'];
                $message->eventUid = $event["eventUid"];

                $message->title = $event["summary"];
                $message->subtitle1 = $event["hostUserUid"]." quit this event";


                $message->save();

                $this->sendMessage(true, [$event["hostUserUid"].""], $data);
                $this->sendMessage(false, [$event["hostUserUid"].""], $data);


                break;

            default:


                break;
        }


        Log::info('---message sended-------------');
    }

    function sendMessage($is_dev = true, $to_user_uids = ["1", "2", "3"], $data)
    {

        $message_content = $data["message_content"];



        $base_uri = 'https://leancloud.cn/1.1/push';
        $headers = [
            'X-LC-Id:Sk9FQYePVwHdXtXQKQuNfdpr-gzGzoHsz',
            'X-LC-Key:1PsfeF7pA1S5xI7EmEoQviwT',
            'Content-Type:application/json'
        ];
        $body = [

            'where' => [

                "user_uid" => [

                    '$in' => $to_user_uids

                ]

            ],


            'data' => [

                'alert' => $message_content,
                'eventUid' => $data['eventUid'],
                'calendarUid' => $data['calendarUid'],
                'action' => $data['action']

            ]
        ];

        if ($is_dev) {

            $body['prod'] = 'dev';

        }

        info(json_encode($body));


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $base_uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        // Edit: prior variable $postFields should be $postfields;

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1); // On dev server only!

        $result = curl_exec($ch);
        curl_close($ch);

//        info($result);

    }


}