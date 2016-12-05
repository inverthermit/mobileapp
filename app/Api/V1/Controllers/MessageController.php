<?php
/**
 * Created by PhpStorm.
 * User: GooD-YeaR
 * Date: 2016/10/11
 * Time: 18:29
 */
namespace App\Api\V1\Controllers;

use App\Models\Message;

use App\Lib\Output;

use JWTAuth;
use Validator;
use Config;
use Uuid;
use DB;

use Exception;
use PDOException;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\Password;
use Tymon\JWTAuth\Exceptions\JWTException;
use Dingo\Api\Exception\ValidationHttpException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MessageController extends Controller
{

    public function insert(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        return 'not implemented error';
    }

    /**
     * list the newest message of all event
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function listGroups(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $output = new Output();
        try{
            // todo: for better performance: 1. add index; 2. add limit
            $sub = Message::orderBy('updated_at', 'desc');
            $builder = DB::table(DB::raw("({$sub->toSql()}) as sub"));
            $builder = $builder->where('user_uid', $user->userUid);

            $builder = $builder->groupBy('event_uid')->orderBy('updated_at', 'desc');
            $msgList = $builder->get();
            $msgList = snakeToCamelList(Message::class, $msgList);

            $output->data = $msgList;
            $output->info = trans('message.data_success');
            $output->status = 1;
        } catch (Exception $e){
            $output->info = $e->getMessage();
            $output->status = -1;
        }
        return response()->json($output);
    }

    /**
     * [lists description]
     * 1. if nothing provided, it will return the newest 20 records
     * 2. if just timeMin or/and timeMax provided, it will return the record in range(timeMin, timeMax]
     * 3. if just syncToken provided, it will return the record in range (syncToken, infinite)
     * 4. if all of this provides, if will return the record in range(syncToken, timeMax]
     * method: get
     * input: (optional)
     *     timeMin: urlencode(update_at), (e.g. 2016-11-01+11%3A23%3A06)
     *     timeMax: urlencode(update_at), (e.g. 2016-11-01+11%3A23%3A06)
     *     syncToken: urlencode(update_at), (e.g. 2016-11-01+11%3A23%3A06)
     * @param  Request $request 
     * @param  String $eventUid  [-1 all events, otherwise, just list given eventUid's message]
     * @return Output<List<Message>>
     */
    public function lists(Request $request, $eventUid){
        $user = JWTAuth::parseToken()->authenticate();
        $output = new Output();
        try{
            $builder= Message::where('user_uid', $user->userUid);
            if($eventUid != '-1'){
                $builder = $builder->where('event_uid', $eventUid);
            }

            $timeMin = urldecode($request->input('timeMin'));
            $timeMax = urldecode($request->input('timeMax'));
            $syncToken = urldecode($request->input('syncToken'));
            if($timeMin != '' && $syncToken == ''){
                $builder = $builder->where('updated_at', '>', $timeMin);
            }
            if($timeMax != ''){
                $builder = $builder->where('updated_at', '<=', $timeMax);
            }
            if($syncToken != ''){
                $builder = $builder->where('updated_at', '>', $syncToken);
            }
            $builder = $builder->orderby('updated_at', 'desc');
            // maximum 20 message per request
            $builder = $builder->skip(0)->take(20);
            $msgList = $builder->get();
            if(count($msgList) > 0){
                $syncToken = urlencode($msgList[0]->updatedAt->toDateTimeString());
            }
            $output->data = $msgList;
            $output->syncToken = $syncToken;
            $output->info = trans('message.data_success');
            $output->status = 1;
        } catch (Exception $e){
            $output->info = $e->getMessage();
            $output->status = -1;
        }
        return response()->json($output);
    }

    /**
     * [get description]
     * @param  Request $request    [description]
     * @param  [type]  $messageUid [description]
     * @return [type]              [description]
     */
    public function get(Request $request, $messageUid){
        $user = JWTAuth::parseToken()->authenticate();
        $output = new Output();
        try{
            $msg = Message::where('message_uid', $messageUid)
                    ->where('user_uid', $user->userUid)
                    ->first();
            if(!empty($msg)){
                $output->data = $msg;
                $output->info = trans('message.data_success');
                $output->status = 1;
            }else{
                $output->info = trans('message.data_failed');
                $output->status = -1;
            }
        } catch (Exception $e){
            $output->info = trans('message.data_failed');
            $output->status = -2;
        }
        return response()->json($output);
    }

    /**
     * [read description]
     * method: post
     * input: 
     *     messageUids: array, the uid of selected message
     *     isRead: int, 1 for marking as read; 0 for marking as unread
     * @param  Request $request    [description]
     * @return [type]              [description]
     */
    public function read(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $output = new Output();
        try{
            $msgUids = json_decode($request->input('messageUids'));
            if(count($msgUids) < 1){
                throw new Exception('msg uids cannot be null', -1);
            }
            $isRead = $request->input('isRead') == '1' ? 1 : 0;
            Message::whereIn('message_uid', $msgUids)
                    ->where('user_uid', $user->userUid)
                    ->update(['is_read' => $isRead]);

            $output->info = trans('message.data_success');
            $output->status = 1;
        }catch (PDOException $e){
            $output->info = trans('message.data_failed');
            $output->status = -1000;
        }catch (Exception $e){
            $output->info = $e->getMessage();
            $output->status = $e->getCode();
        }
        return response()->json($output);
    }

    /**
     * [delete description]
     * method: post
     * input: 
     *     messageUids: array, the uid of selected message
     * @param  Request $request    [description]
     * @return [type]              [description]
     */
    public function delete(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $output = new Output();
        try{
            $msgUids = $request->input('messageUids');
            if(count($msgUids) < 1){
                throw new Exception('msg uids cannot be null', -1);
            }
            Message::whereIn('message_uid', $msgUids)
                    ->where('user_uid', $user->userUid)
                    ->update(['delete_level' => 1]);

            $output->info = trans('message.data_success');
            $output->status = 1;
        }catch (PDOException $e){
            $output->info = trans('message.data_failed');
            $output->status = -1000;
        }catch (Exception $e){
            $output->info = $e->getMessage();
            $output->status = $e->getCode();
        }
        return response()->json($output);
    
    }

    /**
     * [clear description]
     * method: post
     * 
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function clear(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $output = new Output();
        try{
            Message::where('user_uid', $user->userUid)->update(['delete_level' => 1]);
            $output->info = trans('message.data_success');
            $output->status = 1;
        }catch (PDOException $e){
            $output->info = trans('message.data_failed');
            $output->status = -1000;
        }catch (Exception $e){
            $output->info = $e->getMessage();
            $output->status = $e->getCode();
        }
        return response()->json($output);
    }



}