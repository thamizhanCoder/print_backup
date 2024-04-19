<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Helpers\Firebase;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contest;
use Illuminate\Support\Facades\Log;
use App\Helpers\GlobalHelper;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Requests\ContestRequest;
use App\Models\ContestForm;
use App\Models\ContestParticipant;
use App\Models\ContestparticipantForm;
use App\Models\Customer;

class ContestController extends Controller
{
    public function contest_create(ContestRequest $request)
    {
        try {

            $contestjsontring = json_decode($request->contest_fields, true);

            $exist = Contest::where([['contest_name', $request->contest_name], ['status', '!=', 2]])->first();
            if (empty($exist)) {
                $contest = new Contest();
                $contest->contest_name = $request->contest_name;
                $contest->validity_from = $request->validity_from;
                $contest->validity_to = $request->validity_to;
                $contest->contest_image = $request->contest_image;
                $contest->contest_image = $request->contest_image;
                if (!empty($request->contest_image)) {
                    $Extension =  pathinfo($request->contest_image, PATHINFO_EXTENSION);
                    $extension_ary = ['jpeg', 'png', 'jpg'];
                    if (in_array($Extension, $extension_ary)) {
                        $request->artist_image;
                    } else {
                        return response()->json([
                            'keyword'      => 'failed',
                            'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
                            'data'        => []
                        ]);
                    }
                }
                $contest->created_on = Server::getDateTime();
                $contest->created_by = JwtHelper::getSesUserId();

                // log start *********
                Log::channel("contest")->info("******* Contest Insert Method Start *******");
                Log::channel("contest")->info("Contest Controller start:: Request values :: $contest");
                // log start ********* 

                if ($contest->save()) {

                    $contests = Contest::where('contest_id', $contest->contest_id)->first();

                    // log activity
                    $desc =  'Contest '  . $contest->contest_name  . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Contest');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    if (!empty($contestjsontring)) {

                        for ($i = 0; $i < count($contestjsontring); $i++) {

                            $contestslabel = new ContestForm();
                            $contestslabel->contest_id = $contest->contest_id;
                            $contestslabel->label_name = $contestjsontring[$i]['label_name'];
                            $contestslabel->contest_field_type_id = $contestjsontring[$i]['contest_field_type_id'];
                            $contestslabel->value = json_encode($contestjsontring[$i]['value'], true);
                            $contestslabel->is_multiple = $contestjsontring[$i]['is_multiple'];
                            $contestslabel->is_required = $contestjsontring[$i]['is_required'];
                            $contestslabel->created_on = Server::getDateTime();
                            $contestslabel->created_by = JwtHelper::getSesUserId();
                            $contestslabel->save();
                        }
                    }
                    // log end ***********
                    Log::channel("contest")->info("ContestController end:: save values :: $contest::::end");
                    Log::channel("contest")->info("******* ContestInsert Method End *******");
                    Log::channel("contest")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                    // log end ***********

                    //Send Notifiation for Customer

                    $title = "New Contest Added";
                    $body = "New contest " . $contest->contest_name . " is added by admin. Ready to participate!";

                    $module = 'contest_post';

                    $currentDateTime =  date("Y-m-d");
                    if (($currentDateTime >= $contest->validity_from) && ($currentDateTime <= $contest->validity_to)) {
                        $status = "ongoing";
                    } elseif ($currentDateTime > $contest->validity_to) {
                        $status = "expired";
                    } else {
                        $status = "upcoming";
                    }

                    $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);

                    $url = "contest/join-contest?";
                    $page = 'new_contest_post';
                    $data = [
                        'contest_id' => $contest->contest_id,
                        'contest_name' => $contest->contest_name,
                        'status' => $status,
                        'random_id' => $random_id,
                        'page' => $page
                    ];

                    $data2 = [
                        'contest_id' => $contest->contest_id,
                        'contest_name' => $contest->contest_name,
                        'status' => $status,
                        'random_id' => $random_id,
                        'url' => $url,
                        'page' => $page
                    ];


                    $portal1 = 'mobile';
                    $portal2 = 'website';


                    $token = Customer::where('token', '!=', NULL)->orwhere('mbl_token', '!=', NULL)->select('token', 'mbl_token', 'customer_id')->get();

                    if (!empty($token)) {
                        $tokens = [];
                        foreach ($token as $tk) {
                            $tokens[] = $tk['token'];
                        }

                        $mbl_tokens = [];
                        foreach ($token as $tks) {
                            $mbl_tokens[] = $tks['mbl_token'];
                        }

                        $customerId = [];
                        foreach ($token as $tk) {
                            $customerId[] = $tk['customer_id'];
                        }
                    }

                    if (!empty($tokens)) {
                        foreach (array_chunk($tokens, 500) as $tok) {
                    
                            $key = $tok;
                           
                            if (!empty($key)) {

                                $message = [
                                    'title' => $title,
                                    'body' => $body,
                                    'page' => $page,
                                    'data' => $data2,
                                    'portal' => $portal2,
                                    'module' => $module
                                ];


                                if ($contests->status == 1) {
                                    $push = Firebase::sendMultiple($key, $message);
                                }
                            }
                        
                        }

                        if ($contests->status == 1) {
                            if (!empty($customerId)) {
                                $prod = array_chunk($customerId, 500);
                                // print_r($prod);exit;
                                if (!empty($prod)) {
                                    for ($i = 0; $i < count($prod); $i++) {
                                        $sizeOfArrayChunk = sizeof($prod[$i]);
                                        for ($j = 0; $j < $sizeOfArrayChunk; $j++) {
                                            // $mail_data = [];

                                            $getdata = GlobalHelper::notification_create($title, $body, 1, 1, $prod[$i][$j], $module, $page, "website", $data2, $random_id);
                                        }
                                    }
                                }
                            }
                        }
                    }

                    //mobile app push
                    if (!empty($mbl_tokens)) {
                        foreach (array_chunk($mbl_tokens, 500) as $mbl_tok) {
                            // for ($i = 0; $i < count($mbl_tok); $i++) {
                            $key_mbl = $mbl_tok;
                            // print_r($key_mbl);die;
                            // $key = ($tok->token) ? $tok->token : " ";
                            // $key_mbl = ($tok->mbl_token) ? $tok->mbl_token : " ";
                            if (!empty($key_mbl)) {
                                $message = [
                                    'title' => $title,
                                    'body' => $body,
                                    'page' => $page,
                                    'data' => $data,
                                    'portal' => $portal1,
                                    'module' => $module
                                ];


                                if ($contests->status == 1) {
                                    $push2 = Firebase::sendMultipleMbl($key_mbl, $message);
                                    // print_r($push2);
                                    // die;
                                }
                            }
                            // }
                        }

                        if ($contests->status == 1) {
                            if (!empty($customerId)) {
                                $prod = array_chunk($customerId, 500);
                                // print_r($prod);exit;
                                if (!empty($prod)) {
                                    for ($i = 0; $i < count($prod); $i++) {
                                        $sizeOfArrayChunk = sizeof($prod[$i]);
                                        for ($j = 0; $j < $sizeOfArrayChunk; $j++) {
                                            // $mail_data = [];

                                            $getdata = GlobalHelper::notification_create($title, $body, 1, 1, $prod[$i][$j], $module, $page, "mobile", $data, $random_id);
                                        }
                                    }
                                }
                            }
                        }
                    }


                    return response()->json([
                        'keyword' => 'success',
                        'message' => 'Contest created successfully',
                        'data' => [$contest],
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('Contest creation failed'),
                        'data' => [],
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('Contest name already exist'),
                    'data' => [],
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("contest")->error("******* Contest Insert Method Error Start *******");
            Log::channel("contest")->error($exception);
            Log::channel("contest")->error("******* Contest Insert Method Error End   *******");

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function contest_update(Request $request)
    {
        try {

            $contestjsontring = json_decode($request->contest_fields, true);

            $exist = Contest::where([['contest_name', $request->contest_name], ['status', '!=', 2], ['contest_id', '!=', $request->contest_id]])->first();
            if (empty($exist)) {
                $ids = $request->contest_id;
                $contest = Contest::find($ids);
                $contest->contest_name = $request->contest_name;
                $contest->validity_from = $request->validity_from;
                $contest->validity_to = $request->validity_to;
                $contest->contest_image = $request->contest_image;
                if (!empty($request->contest_image)) {
                    $Extension =  pathinfo($request->contest_image, PATHINFO_EXTENSION);
                    $extension_ary = ['jpeg', 'png', 'jpg'];
                    if (in_array($Extension, $extension_ary)) {
                        $request->artist_image;
                    } else {
                        return response()->json([
                            'keyword'      => 'failed',
                            'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
                            'data'        => []
                        ]);
                    }
                }
                $contest->updated_on = Server::getDateTime();
                $contest->updated_by = JwtHelper::getSesUserId();

                // log start *********
                Log::channel("contest")->info("******* Contest Update Method Start *******");
                Log::channel("contest")->info("Contest Controller start:: Request values :: $contest");
                // log start ********* 

                if ($contest->save()) {

                    // log activity
                    $desc =  'Contest '  . $contest->contest_name  . ' is updated by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Contest');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    if (!empty($contestjsontring)) {
                        $delete = ContestForm::where('contest_id', $contest->contest_id)->delete();
                        for ($i = 0; $i < count($contestjsontring); $i++) {

                            $contestslabel = new ContestForm();
                            $contestslabel->contest_id = $contest->contest_id;
                            $contestslabel->label_name = $contestjsontring[$i]['label_name'];
                            $contestslabel->contest_field_type_id = $contestjsontring[$i]['contest_field_type_id'];
                            $contestslabel->value = json_encode($contestjsontring[$i]['value'], true);
                            $contestslabel->is_multiple = $contestjsontring[$i]['is_multiple'];
                            $contestslabel->is_required = $contestjsontring[$i]['is_required'];
                            $contestslabel->save();
                        }
                    }
                    // log end ***********
                    Log::channel("contest")->info("ContestController end:: save values :: $contest::::end");
                    Log::channel("contest")->info("******* Contest Update Method End *******");
                    Log::channel("contest")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                    // log end ***********
                    return response()->json([
                        'keyword' => 'success',
                        'message' => 'Contest updated successfully',
                        'data' => [$contest],
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('Contest update failed'),
                        'data' => [],
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('Contest name already exist'),
                    'data' => [],
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("contest")->error("******* Contest Update Method Error Start *******");
            Log::channel("contest")->error($exception);
            Log::channel("contest")->error("******* Contest Update Method Error End   *******");

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function contest_list(Request $request)
    {
        try {
            Log::channel("contest")->info('** started the contest list method **');

            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'contest_name' => 'contest.contest_name',
                'validity_from' => 'contest.validity_from',
                'validity_to' => 'contest.validity_to',
                'created_on' => 'contest.created_on',
            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "contest_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array('contest.contest_name', 'contest.validity_from', 'contest.validity_to', 'contest.created_on');

            $contest =  Contest::where('status', '!=', 2)->select('contest.*');

            $contest->where(function ($query) use ($searchval, $column_search, $contest) {
                $i = 0;
                if ($searchval) {
                    foreach ($column_search as $item) {
                        if ($i === 0) {
                            $query->where(($item), 'LIKE', "%{$searchval}%");
                        } else {
                            $query->orWhere(($item), 'LIKE', "%{$searchval}%");
                        }
                        $i++;
                    }
                }
            });
            if (array_key_exists($sortByKey, $order_by_key) && in_array($sortType, $sort_dir)) {
                $contest->orderBy($order_by_key[$sortByKey], $sortType);
            }
            $count = $contest->count();
            if ($offset) {
                $offset = $offset * $limit;
                $contest->offset($offset);
            }
            if ($limit) {
                $contest->limit($limit);
            }
            Log::channel("contest")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType");
            $contest->orderBy('contest_id', 'desc');
            $contest = $contest->get();
            if ($count > 0) {
                $final = [];
                foreach ($contest as $value) {
                    $ary = [];
                    $ary['contest_id'] = $value['contest_id'];
                    $ary['contest_name'] = $value['contest_name'];
                    $ary['contest_image'] = $value['contest_image'];
                    $ary['contest_image_url'] = ($value['contest_image'] != '') ?
                        env('APP_URL') . env('CONTEST_URL') . $value['contest_image'] :
                        env('APP_URL') . "avatar.jpg";
                    $ary['valid_from'] = $value['validity_from'];
                    $ary['valid_to'] = $value['validity_to'];
                    $currentDateTime =  date("Y-m-d");
                    if (($currentDateTime >= $value['validity_from']) && ($currentDateTime <= $value['validity_to'])) {
                        $ary['validity_status'] = "ongoing";
                    } elseif ($currentDateTime > $value['validity_to']) {
                        $ary['validity_status'] = "expired";
                    } else {
                        $ary['validity_status'] = "upcoming";
                    }
                    $apply_checking = ContestParticipant::where('contest_id', $value['contest_id'])->first();
                    $ary['is_participant_apply'] = !empty($apply_checking) ? 1 : 0;
                    $ary['created_on'] = $value['created_on'];
                    $ary['created_by'] = $value['created_by'];
                    $ary['updated_on'] = $value['updated_on'];
                    $ary['updated_by'] = $value['updated_by'];
                    $ary['status'] = $value['status'];
                    $final[] = $ary;
                }
            }


            if (!empty($final)) {
                $impl = json_encode($final, true);
                Log::channel("contest")->info("Contest Controller end:: save values :: $impl ::::end");
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Contest listed successfully'),
                    'data' => $final,
                    'count' => $count
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                    'count' => $count
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("contest")->error($exception);
            Log::channel("contest")->error('** end the contest list method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function contest_view($id)
    {
        try {
            Log::channel("contest")->info('** started the contest view method **');
            if ($id != '' && $id > 0) {
                $get_contest = Contest::where('contest_id', $id)->get();
                Log::channel("contest")->info("request value contest_id:: $id");
                $count = $get_contest->count();
                if ($count > 0) {
                    $final = [];
                    foreach ($get_contest as $value) {
                        $ary = [];
                        $ary['contest_id'] = $value['contest_id'];
                        $ary['contest_name'] = $value['contest_name'];
                        $ary['contest_image'] = $value['contest_image'];
                        $ary['contest_image_url'] = ($value['contest_image'] != '') ?
                            env('APP_URL') . env('CONTEST_URL') . $value['contest_image'] :
                            env('APP_URL') . "avatar.jpg";
                        $ary['valid_from'] = $value['validity_from'];
                        $ary['valid_to'] = $value['validity_to'];
                        $currentDateTime =  date("Y-m-d");
                        if (($currentDateTime >= $value['validity_from']) && ($currentDateTime <= $value['validity_to'])) {
                            $ary['validity_status'] = "ongoing";
                        } elseif ($currentDateTime > $value['validity_to']) {
                            $ary['validity_status'] = "expired";
                        } else {
                            $ary['validity_status'] = "upcoming";
                        }
                        $apply_checking = ContestParticipant::where('contest_id', $value['contest_id'])->first();
                        $ary['is_participant_apply'] = !empty($apply_checking) ? 1 : 0;
                        $ary['contest_details'] = $this->contestDetailsGetall($value['contest_id']);
                        $ary['created_on'] = $value['created_on'];
                        $ary['created_by'] = $value['created_by'];
                        $ary['updated_on'] = $value['updated_on'];
                        $ary['updated_by'] = $value['updated_by'];
                        $ary['status'] = $value['status'];
                        $final[] = $ary;
                    }
                }
                if (!empty($final)) {
                    $log = json_encode($final, true);
                    Log::channel("contest")->info("view value :: $log");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Contest viewed successfully'),
                        'data' => $final
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('No data found'),
                        'data' => []
                    ]);
                }
            }
        } catch (\Exception $exception) {
            Log::channel("contest")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function contest_participant_details_view($c_id, $id)
    {
        try {
            // log start *********
            Log::channel("contest")->info("******* Participant View Method Start *******");
            Log::channel("contest")->info("Contest Controller start:: find ID : $id");
            // log start *********

            if ($id != '' && $id > 0) {
                $get_contest = ContestParticipant::where('contest_participant.customer_id', $id)->where('contest_participant.contest_id', $c_id)
                    ->leftjoin('customer', 'customer.customer_id', '=', 'contest_participant.customer_id')
                    ->leftjoin('contest', 'contest.contest_id', '=', 'contest_participant.contest_id')
                    ->select('contest_participant.contest_participant_id', 'contest_participant.contest_id', 'contest_participant.customer_id', 'customer.customer_first_name', 'customer.customer_last_name', 'contest_participant.created_on', 'contest.contest_name', 'customer.mobile_no', 'customer.profile_image')->get();

                // log end ***********
                Log::channel("contest")->info("Contest Controller end:: save values :: $id ::::end");
                Log::channel("contest")->info("******* Participant View Method End *******");
                Log::channel("contest")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                // log end ***********
                $count = $get_contest->count();
                if ($count > 0) {
                    $final = [];
                    foreach ($get_contest as $value) {
                        $ary = [];
                        $ary['contest_participant_id'] = $value['contest_participant_id'];
                        $ary['customer_id'] = $value['customer_id'];
                        $ary['contest_id'] = $value['contest_id'];
                        $ary['contest_name'] = $value['contest_name'];
                        $ary['date'] =  date('d-m-Y', strtotime($value['created_on'])) ?? "-";
                        $ary['view_participants_details'] = $this->getFormData($value['contest_participant_id']);
                        $final[] = $ary;
                    }
                }
                if (!empty($final)) {
                    $log = json_encode($final, true);

                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Contest participant viewed successfully'),
                        'data' => $final
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('No data found'),
                        'data' => []
                    ]);
                }
            }
        } catch (\Exception $exception) {
            // log for error start
            Log::channel("contest")->error("******* Participant View Method Error Start *******");
            Log::channel("contest")->error($exception);
            Log::channel("contest")->error("******* Participant View Method Error End *******");
            Log::channel("contest")->error("********************************END !!!!!!!!!!!!!!******************************************** ");
            // log for error end

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function getFormData($formId)
    {
        $formlabelDetails = ContestparticipantForm::where('contest_participant_form.contest_participant_id', $formId)
            ->leftjoin('contest_form', 'contest_form.contest_form_id', '=', 'contest_participant_form.contest_form_id')
            ->leftjoin('contest_field_type', 'contest_field_type.contest_field_type_id', '=', 'contest_form.contest_field_type_id')
            ->select('contest_form.label_name', 'contest_participant_form.value', 'contest_form.is_multiple', 'contest_field_type.field_type')
            ->orderby('contest_participant_form.contest_participant_form_id')
            ->get();
        $relatedDetails = [];
        if (!empty($formlabelDetails)) {
            foreach ($formlabelDetails as $contestform) {
                $ary = [];
                $ary['label_name'] = $contestform['label_name'];
                $ary['field_type'] = $contestform['field_type'];
                if ($contestform['field_type'] == 'Text field' || $contestform['field_type'] == 'Textarea') {
                    $ary['value'] = $contestform['value'];
                }

                if ($contestform['field_type'] == 'Radio button' || $contestform['field_type'] == 'Checkbox' || $contestform['field_type'] == 'Selectbox') {
                    $ary['value'] = json_decode($contestform['value'], true);
                }
                if ($contestform['field_type'] == 'Upload') {
                    $mulImages = json_decode($contestform['value'], true);
                    $ary['value'] = $this->getdefaultImages_allImages($mulImages);
                }
                $relatedDetails[] = $ary;
            }
        }
        return $relatedDetails;
    }

    public function getdefaultImages_allImages($gTImage)
    {
        $imG = [];
        if (!empty($gTImage)) {
            foreach ($gTImage as $im) {
                $ary = [];
                $ary['photo_url'] = ($im['photo'] != '') ? env('APP_URL') . env('CONTEST_URL') . $im['photo'] : env('APP_URL') . "avatar.jpg";
                $ary['photo'] = $im['photo'];
                $imG[] = $ary;
            }
        }
        return $imG;
    }

    function contest_participant_view(Request $request, $contest_id)
    {
        try {
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $order_by_key = [
                'created_on' => 'contest_participant.created_on',
                'mobile_no' => 'customer.mobile_no',
                'customer_name' => 'customer.customer_first_name',
            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "customer_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array('contest_participant.created_on', 'customer.mobile_no', 'customer.customer_first_name', 'customer.customer_last_name',);

            // log start *********
            Log::channel("contest")->info("******* Contest Participant View Method Start *******");
            Log::channel("contest")->info("Contest Controller start ::: limit = $limit, ::: offset == $offset:::: , searchval == $searchval::: , sortByKey === $sortByKey ,:::  sortType == $sortType :::");
            // log start *********

            $participant_list = ContestParticipant::where('contest_participant.contest_id', $contest_id)
                ->leftJoin('customer', 'contest_participant.customer_id', '=', 'customer.customer_id')
                ->select(
                    'contest_participant.customer_id',
                    'contest_participant.created_on',
                    'customer.customer_first_name',
                    'customer.customer_last_name',
                    'customer.mobile_no',
                    'customer.profile_image'
                );

            $participant_list->where(function ($query) use ($searchval, $column_search, $participant_list) {
                $i = 0;
                if ($searchval) {
                    foreach ($column_search as $item) {
                        if ($i === 0) {
                            $query->where(($item), 'LIKE', "%{$searchval}%");
                        } else {
                            $query->orWhere(($item), 'LIKE', "%{$searchval}%");
                        }
                        $i++;
                    }
                }
            });
            if (array_key_exists($sortByKey, $order_by_key) && in_array($sortType, $sort_dir)) {
                $participant_list->orderBy($order_by_key[$sortByKey], $sortType);
            }

            $count = count($participant_list->get());
            if ($offset) {
                $offset = $offset * $limit;
                $participant_list->offset($offset);
            }
            if ($limit) {
                $participant_list->limit($limit);
            }
            Log::channel("contest")->info("request event_view :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType");
            $participant_list->orderBy('contest_participant.contest_participant_id', 'desc');
            $participant_list = $participant_list->get();
            if ($count > 0) {
                $final = [];
                foreach ($participant_list as $value) {
                    $ary = [];
                    $ary['created_on'] = date('d-m-Y', strtotime($value['created_on']));
                    $ary['customer_id'] = $value['customer_id'];
                    $ary['customer_name'] = !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'];
                    $ary['profile_image'] = $value['profile_image'] ?? '-';
                    $ary['prfile_image_url'] = ($value['profile_image'] != '') ?
                        env('APP_URL') . env('PROFILE_URL') . $value['profile_image'] :
                        env('APP_URL') . "avatar.jpg";
                    $ary['mobile_no'] = $value['mobile_no'];
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                // log end ***********
                $impl = json_encode($final, true);
                Log::channel("contest")->info("Contest Controller end:: save values :: $impl ::::end");
                Log::channel("contest")->info("******* Contest Participant View Method End *******");
                Log::channel("contest")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                // log end ***********
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Contest participant viewed successfully'),
                    'data' => $final,
                    'count' => $count,
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                    'count' => $count,
                ]);
            }
        } catch (\Exception $exception) {
            // log for error start
            Log::channel("contest")->error("******* Contest Participant View Method Error Start *******");
            Log::channel("contest")->error($exception);
            Log::channel("contest")->error("******* Contest Participant View Method Error End *******");
            Log::channel("contest")->error("********************************END !!!!!!!!!!!!!!******************************************** ");
            // log for error end
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function contestDetailsGetall($contest_id)
    {
        $event_ticketDetails = ContestForm::where('contest_form.contest_id', $contest_id)
            ->leftjoin('contest', 'contest.contest_id', '=', 'contest_form.contest_id')
            ->leftjoin('contest_field_type', 'contest_field_type.contest_field_type_id', '=', 'contest_form.contest_field_type_id')
            ->select('contest_form.*', 'contest.contest_name', 'contest_form.label_name', 'contest_form.value', 'contest_form.is_required', 'contest_form.is_multiple', 'contest_field_type.field_type')->orderby('contest_form.contest_form_id', 'asc')->get();
        $ticDetails = [];
        if (!empty($event_ticketDetails)) {
            foreach ($event_ticketDetails as $eventticket) {
                $ary = [];
                $ary['contest_form_id'] = $eventticket['contest_form_id'];
                $ary['contest_id'] = $eventticket['contest_id'];
                $ary['contest_name'] = $eventticket['contest_name'];
                $ary['label_name'] = $eventticket['label_name'];
                $ary['contest_field_type_id'] = $eventticket['contest_field_type_id'];
                $ary['field_type'] = $eventticket['field_type'];
                if ($eventticket['field_type'] == 'Selectbox' ||  $eventticket['field_type'] == 'Radio button' || $eventticket['field_type'] == 'Checkbox') {
                    $ary['value']  =  json_decode($eventticket['value'], true);
                }
                $ary['is_multiple'] = $eventticket['is_multiple'];
                $ary['is_required'] =  $eventticket['is_required'];
                $ticDetails[] = $ary;
            }
        }
        return $ticDetails;
    }

    public function contest_update_status(Request $request)
    {
        try {

            if (!empty($request)) {
                $ids = $request->id;
                $ids = json_decode($ids, true);
                if (!empty($ids)) {

                    // log start *********
                    Log::channel("contest")->info("******* Contest Status Method Start *******");
                    Log::channel("contest")->info("Contest Controller start ::: Request IDS == $ids :::: Request status === $request->status");
                    // log start *********

                    $contest = Contest::where('contest_id', $ids)->first();
                    $update = Contest::where('contest_id', $ids)->update(array(
                        'status' => $request->status,
                        'updated_on' => Server::getDateTime(),
                        'updated_by' => JwtHelper::getSesUserId()
                    ));

                    //   log activity
                    $activity_status = ($request->status) ? 'activated' : 'inactivated';
                    $desc =  'Contest '  . $contest->contest_name  . ' is ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Contest');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    if ($request->status == 0) {

                        // log end ***********
                        // $impl = implode(",", $ids);
                        Log::channel("contest")->info("Contest Controller end:: save values :: $ids :: Status == Inactive ::::end");
                        Log::channel("contest")->info("******* Contest Status Method End *******");
                        Log::channel("contest")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                        // log end ***********

                        return response()->json([
                            'keyword' => 'success',
                            'message' => 'Contest inactivated successfully',
                            'data' => []
                        ]);
                    } else if ($request->status == 1) {

                        // log end ***********
                        // $impl = implode(",", $ids);
                        Log::channel("contest")->info("Contest Controller end:: save values :: $ids :: Status == Active ::::end");
                        Log::channel("contest")->info("******* Contest Status Method End *******");
                        Log::channel("contest")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                        // log end ***********

                        return response()->json([
                            'keyword' => 'success',
                            'message' =>  'Contest activated successfully',
                            'data' => []
                        ]);
                    }
                } else {
                    return response()
                        ->json([
                            'keyword' => 'failed',
                            'message' => __('Contest failed'),
                            'data' => []
                        ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('Contest failed'), 'data' => []
                ]);
            }
        } catch (\Exception $exception) {

            // log for error start
            Log::channel("contest")->error("******* Contest Stauts Method Error Start *******");
            Log::channel("contest")->error($exception);
            Log::channel("contest")->error("******* Contest Stauts Method Error End *******");
            Log::channel("contest")->error("********************************END !!!!!!!!!!!!!!******************************************** ");
            // log for error end

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function contest_delete(Request $request)
    {
        try {
            if (!empty($request)) {
                $ids = $request->id;
                $ids = json_decode($ids, true);

                if (!empty($ids)) {

                    // log start *********
                    Log::channel("contest")->info("******* Contest Delete Method Start *******");
                    Log::channel("contest")->info("Contest Controller start ::: Request IDS == $ids ::::");
                    // log start *********

                    $contest = Contest::where('contest_id', $ids)->first();
                    $update = Contest::where('contest_id', $ids)->update(array(
                        'status' => 2,
                        'updated_on' => Server::getDateTime(),
                        'updated_by' => JwtHelper::getSesUserId()
                    ));

                    // log activity
                    // $implode = implode(",", $ids);
                    $desc =  ' Contest '  . $contest->contest_name  . ' is' . ' deleted by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Contest');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    // log end ***********
                    Log::channel("contest")->info("Contest Controller end:: save values :: $ids :: Status == Deleted ::::end");
                    Log::channel("contest")->info("******* Contest Delete Method End *******");
                    Log::channel("contest")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                    // log end ***********

                    return response()->json([
                        'keyword' => 'success',
                        'message' =>  'Contest deleted successfully',
                        'data' => []
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('message.failed'),
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('message.failed'),
                    'data' => []
                ]);
            }
        } catch (\Exception $exception) {

            // log for error start
            Log::channel("contest")->error("******* Contest Delete Method Error Start *******");
            Log::channel("contest")->error($exception);
            Log::channel("contest")->error("******* Contest Delete Method Error End *******");
            Log::channel("contest")->error("********************************END !!!!!!!!!!!!!!******************************************** ");
            // log for error end

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}
