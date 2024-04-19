<?php

namespace App\Http\Controllers\API\V1\MP;

use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Controllers\Controller;
use App\Models\Contest;
use App\Models\ContestForm;
use App\Models\ContestParticipant;
use App\Models\ContestparticipantForm;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Events\ContestApply;

class MobileContestController extends Controller
{
    public function contest_apply_page(Request $request)
    {
        $id = JwtHelper::getSesUserId();
        $form_validation = $this->formValidation($request->contest_form);
        if ($form_validation) {
            return response()->json(['keyword' => 'failed', 'message' => $form_validation]);
        } else {
            $contestjsontring = json_decode($request->contest_form, true);
        }

        $exist = ContestParticipant::where([['contest_id', '=', $request->contest_id], ['customer_id', '=', $id]])->first();
        if (empty($exist)) {
            $contest = new ContestParticipant();
            $contest->contest_id = $request->contest_id;
            $contest->customer_id = $id;
            $contest->created_on = Server::getDateTime();
            $contest->created_by = JwtHelper::getSesUserId();
            if ($contest->save()) {

                if (!empty($contestjsontring)) {

                    for ($i = 0; $i < count($contestjsontring); $i++) {
                        $contestslabel = new ContestparticipantForm();
                        $contestslabel->contest_participant_id = $contest->contest_participant_id;
                        $contestslabel->contest_form_id = $contestjsontring[$i]['contest_form_id'];
                        // $contestslabel->value = $contestjsontring[$i]['value'];
                        if(array($contestjsontring[$i]['value']))
                        {
                        $contestslabel->value = json_encode($contestjsontring[$i]['value'],true);
                        }
                       if(is_string($contestjsontring[$i]['value']))
                       {
                        $contestslabel->value = $contestjsontring[$i]['value'];
                       }
                        $contestslabel->save();
                    }
                }

                $user = Customer::where('customer_id', $contest->created_by)->first();
                $contest_id = Contest::where('contest_id',  $contest->contest_id )->first();
             
                //mail send
                $mail_data = [];
                $mail_data['contest_name'] = $contest_id->contest_name;
                $mail_data['customer_name'] = !empty($user->customer_last_name) ? $user->customer_first_name . ' ' . $user->customer_last_name : $user->customer_first_name;
                $mail_data['email'] = $user->email;

                if ($user->email != '') {
                    event(new ContestApply($mail_data));
                }

                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Contest applied successfully',
                    'data' => [$contest],
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('Contest applied failed'),
                    'data' => [],
                ]);
            }
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('You are already applied for this contest'),
                'data' => [],
            ]);
        }
    }

    public function formValidation($formvalues)
    {

        $data = json_decode($formvalues, true);
        if (!empty($data)) {
            foreach ($data as $d) {
                $validator = Validator::make($d, [
                    'contest_form_id' => 'required',
                ]);
                if ($validator->fails()) {
                    $errors = $validator->errors();
                    return $errors->first();
                }
            }
        }
    }

    public function mycontest_list_page(Request $request)
    {
        try {
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $id = JwtHelper::getSesUserId();

            $get_contest = ContestParticipant::where('customer_id', $id)
                ->leftjoin('contest', 'contest_participant.contest_id', '=', 'contest.contest_id')
                ->select('contest_participant.customer_id', 'contest_participant.contest_id', 'contest.contest_name', 'contest.validity_to', 'contest_participant.created_on', 'contest.contest_image')
                ->orderBy('contest_participant.created_on', 'desc');

                $count = $get_contest->count();
                if ($offset) {
                    $offset = $offset * $limit;
                    $get_contest->offset($offset);
                }
                if ($limit) {
                    $get_contest->limit($limit);
                }

            $get_contest = $get_contest->get();

            // log start *********
            Log::channel("contest")->info("******* My Contest List Method Start *******");
            // log start *********

            $finalArray = [];

            foreach ($get_contest as $contest) {

                $contestArray = [];
                $contestArray['contest_id'] = $contest['contest_id'];
                $contestArray['customer_id'] = $contest['customer_id'];
                $contestArray['contest_name'] = $contest['contest_name'];
                $contestArray['participated_on'] = date('d-m-Y', strtotime($contest['created_on']));
                $contestArray['ending_on'] = date('d-m-Y', strtotime($contest['validity_to']));
                $contestArray['contest_image'] = $contest['contest_image'];
                $contestArray['contest_image_url'] = ($contest['contest_image'] != '') ? env('APP_URL') . env('CONTEST_URL') . $contest['contest_image'] : env('APP_URL') . "avatar.jpg";
                $contestArray['apply_status'] = "Submitted";
                $finalArray[] = $contestArray;
            }

            if (!empty($finalArray)) {
                // log end ***********
                $impl = json_encode($finalArray, true);
                Log::channel("contest")->info("Mobile Contest Controller end:: save values :: $impl ::::end");
                Log::channel("contest")->info("******* My Contest List Method End *******");
                Log::channel("contest")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                // log end ***********
                return response()->json(
                    [
                        'keyword' => 'success',
                        'message' => __('My contest listed successfully'),
                        'data' => $finalArray,
                    ]
                );
            } else {
                return response()->json(
                    [
                        'keyword' => 'failure',
                        'message' => __('No data found'),
                        'data' => [],
                    ]
                );
            }
        } catch (\Exception $exception) {

            // log for error start
            Log::channel("contest")->error("******* My contest List Method Error Start *******");
            Log::channel("contest")->error($exception);
            Log::channel("contest")->error("******* My Contest List Method Error End *******");
            Log::channel("contest")->error("********************************END !!!!!!!!!!!!!!******************************************** ");
            // log for error end

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function mycontest_view_page($id)
    {
        try {
            $userid = JwtHelper::getSesUserId();
            $get_contest = ContestParticipant::where('contest_participant.contest_id', $id)->where('contest_participant.customer_id', $userid)
                ->leftjoin('contest', 'contest_participant.contest_id', '=', 'contest.contest_id')
                ->select('contest_participant.contest_participant_id', 'contest_participant.customer_id', 'contest_participant.contest_id', 'contest.contest_name', 'contest.validity_to', 'contest_participant.created_on', 'contest.contest_image')->first();
            $final = [];
            if (!empty($get_contest)) {
                $ary = [];
                $ary['contest_participant_id'] = $get_contest['contest_participant_id'];
                $ary['customer_id'] = $get_contest['customer_id'];
                $ary['contest_id'] = $get_contest['contest_id'];
                $ary['contest_name'] = $get_contest['contest_name'];
                $ary['apply_status'] = "Submitted";
                $ary['participated_on'] = date('d-m-Y', strtotime($get_contest['created_on']));
                $ary['ending_on'] = date('d-m-Y', strtotime($get_contest['validity_to']));
                $ary['contest_image'] = $get_contest['contest_image'];
                $ary['contest_image_url'] = ($get_contest['contest_image'] != '') ? env('APP_URL') . env('CONTEST_URL') . $get_contest['contest_image'] : env('APP_URL') . "avatar.jpg";
                $ary['form_details_for_customer'] = $this->getFormData($get_contest['contest_participant_id']);
                $final[] = $ary;
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("adminevent")->info("view event view :: $log");
                Log::channel("adminevent")->info('** end the event view method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('My contest viewed successfully'),
                    'data' => $final,
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No Data Found'),
                    'data' => [],
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("adminevent")->error($exception);
            Log::channel("adminevent")->info('** end the event view method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function getFormData($formId)
    {
        $formlabelDetails = ContestparticipantForm::where('contest_participant_form.contest_participant_id', $formId)
            ->leftjoin('contest_form', 'contest_form.contest_form_id', '=', 'contest_participant_form.contest_form_id')
            ->leftjoin('contest_field_type', 'contest_field_type.contest_field_type_id', '=', 'contest_form.contest_field_type_id')
            ->select('contest_form.label_name', 'contest_participant_form.value','contest_form.is_multiple','contest_field_type.field_type')
            ->orderby('contest_participant_form.contest_participant_form_id')
            ->get();
        $relatedDetails = [];
        if (!empty($formlabelDetails)) {
            foreach ($formlabelDetails as $contestform)
            {
                $ary = [];
                $ary['label_name'] = $contestform['label_name'];
                $ary['field_type'] = $contestform['field_type'];
                if($contestform['field_type'] == 'Text field' || $contestform['field_type'] == 'Textarea')
                {
                $ary['value']= $contestform['value'];
                }
    
                if($contestform['field_type'] =='Radio button'||$contestform['field_type'] =='Checkbox' ||$contestform['field_type'] =='Selectbox' )
                {
                    $ary['value']= json_decode($contestform['value'],true);
                }
                if($contestform['field_type'] =='Upload')
                {
                    $mulImages= json_decode($contestform['value'],true);
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

    public function upcoming_contest_list_page(Request $request)
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
                    if ($value['validity_to'] >= date('Y-m-d')) {
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
                        $ary['validity_status'] = "Ongoing";
                    } elseif ($currentDateTime > $value['validity_to']) {
                        $ary['validity_status'] = "Expired";
                    } else {
                        $ary['validity_status'] = "Upcoming";
                    }
                    $ary['created_on'] = $value['created_on'];
                    $ary['created_by'] = $value['created_by'];
                    $ary['updated_on'] = $value['updated_on'];
                    $ary['updated_by'] = $value['updated_by'];
                    $ary['status'] = $value['status'];
                    $final[] = $ary;
                    }
                }
            }


            if (!empty($final)) {
                $impl = json_encode($final, true);
                Log::channel("contest")->info("Mobile Contest Controller end:: save values :: $impl ::::end");
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Upcoming contest listed successfully'),
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

    public function contest_view_page($id)
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


}
