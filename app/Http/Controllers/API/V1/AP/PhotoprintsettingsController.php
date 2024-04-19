<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use Illuminate\Support\Facades\Log;
use App\Models\Photoprintsetting;
use App\Helpers\GlobalHelper;
use App\Models\ProductCatalogue;

class PhotoprintsettingsController extends Controller
{
    public function photoprintsettings_create(Request $request)
    {
        try {
            Log::channel("photoprintsettings")->info('** started the photoprintsettings create method **');
            $photo = new Photoprintsetting();
            $exist = Photoprintsetting::where([
                ['width', $request->width], ['height', $request->height], ['min_resolution_width', $request->min_resolution_width],
                ['min_resolution_height', $request->min_resolution_height], ['max_resolution_width', $request->max_resolution_width], ['max_resolution_height', $request->max_resolution_height],
                ['status', '!=', 2]
            ])->first();

            if (empty($exist)) {
                $photo->width = $request->width;
                $photo->height = $request->height;
                $photo->min_resolution_width = $request->min_resolution_width;
                $photo->min_resolution_height = $request->min_resolution_height;
                $photo->max_resolution_width = $request->max_resolution_width;
                $photo->max_resolution_height = $request->max_resolution_height;
                $photo->created_on = Server::getDateTime();
                $photo->created_by = JwtHelper::getSesUserId();

                if ($photo->save()) {
                    $photos = Photoprintsetting::where('photo_print_settings_id', $photo->photo_print_settings_id)->first();
                    // log activity
                    $desc =  'Photo print settings ' . '(' . $photos->width . '*' . $photos->height . ')' . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Photo Print Setting');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    Log::channel("photoprintsettings")->info("save value :: $photos");
                    return response()->json([
                        'keyword'      => 'success',
                        'message'      => __('Photo print settings created successfully'),
                        'data'        => [$photos]
                    ]);
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => __('Photo print settings creation failed'),
                        'data'        => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Photo print settings already exists'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("photoprintsettings")->error($exception);
            Log::channel("photoprintsettings")->error('** error occured in photoprintsettings create method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
    public function photoprintsettings_list(Request $request)
    {
        try {
            Log::channel("photoprintsettings")->info('** started the photoprintsettings list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'width' => 'width',
                'height' => 'height',
                'min_resolution_width' => 'min_resolution_width',
                'min_resolution_height' => 'min_resolution_height',
                'max_resolution_width' => 'max_resolution_width',
                'max_resolution_height' => 'max_resolution_height',
            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "photo_print_settings_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array('width', 'height', 'min_resolution_width', 'min_resolution_height', 'max_resolution_width', 'max_resolution_height');
            $photo = Photoprintsetting::where([
                ['status', '!=', '2']
            ]);

            $photo->where(function ($query) use ($searchval, $column_search, $photo) {
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
                $photo->orderBy($order_by_key[$sortByKey], $sortType);
            }

            $count = $photo->count();

            if ($offset) {
                $offset = $offset * $limit;
                $photo->offset($offset);
            }
            if ($limit) {
                $photo->limit($limit);
            }
            Log::channel("photoprintsettings")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType");
            $photo->orderBy('photo_print_settings_id', 'desc');
            $photo = $photo->get();
            if ($count > 0) {
                $final = [];
                foreach ($photo as $value) {
                    $ary = [];
                    $ary['photo_print_settings_id'] = $value['photo_print_settings_id'];
                    $ary['width'] = $value['width'];
                    $ary['height'] = $value['height'];
                    $ary['min_resolution_width'] = $value['min_resolution_width'];
                    $ary['min_resolution_height'] = $value['min_resolution_height'];
                    $ary['max_resolution_width'] = $value['max_resolution_width'];
                    $ary['max_resolution_height'] = $value['max_resolution_height'];
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
                Log::channel("photoprintsettings")->info("list value :: $log");
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Photo print settings listed successfully'),
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
            Log::channel("photoprintsettings")->error($exception);
            Log::channel("photoprintsettings")->error('** error occured in photoprintsettings list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
    public function photoprintsettings_update(Request $request)
    {
        try {
            Log::channel("photoprintsettings")->info('** started the photoprintsettings update method **');

            $exist = Photoprintsetting::where([
                ['width', $request->width], ['height', $request->height], ['min_resolution_width', $request->min_resolution_width],
                ['min_resolution_height', $request->min_resolution_height], ['max_resolution_width', $request->max_resolution_width], ['max_resolution_height', $request->max_resolution_height],
                ['status', '!=', 2],['photo_print_settings_id','!=',$request->photo_print_settings_id]
            ])->first();

            if (empty($exist)) {
                $photosOldDetails = Photoprintsetting::where('photo_print_settings_id', $request->photo_print_settings_id)->first();

                $ids = $request->photo_print_settings_id;
                $photo = Photoprintsetting::find($ids);
                $photo->width = $request->width;
                $photo->height = $request->height;
                $photo->min_resolution_width = $request->min_resolution_width;
                $photo->min_resolution_height = $request->min_resolution_height;
                $photo->max_resolution_width = $request->max_resolution_width;
                $photo->max_resolution_height = $request->max_resolution_height;
                $photo->updated_on = Server::getDateTime();
                $photo->updated_by = JwtHelper::getSesUserId();

                if ($photo->save()) {
                    $photos = Photoprintsetting::where('photo_print_settings_id', $photo->photo_print_settings_id)->first();
                    // log activity
                    $desc =  'Photo print settings ' . '(' . $photosOldDetails->width . '*' . $photosOldDetails->height . ')' . ' is updated as ' . '(' . $photos->width . '*' . $photos->height . ')' . ' by ' .JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Photo Print Setting');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    Log::channel("photoprintsettings")->info("save value :: $photos");
                    Log::channel("photoprintsettings")->info('** end the photoprintsettings update method **');

                    return response()->json([
                        'keyword'      => 'success',
                        'data'        => [$photos],
                        'message'      => __('Photo print settings updated successfully')
                    ]);
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'data'        => [],
                        'message'      => __('Photo print settings update failed')
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('photo print settings already exist'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("photoprintsettings")->error($exception);
            Log::channel("photoprintsettings")->error('** error occured in photoprintsettings update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
    public function photoprintsettings_view($id)
    {
        try {
            Log::channel("photoprintsettings")->info('** started the photoprintsettings view method **');
            if ($id != '' && $id > 0) {
                $get_photo = Photoprintsetting::where('photo_print_settings_id', $id)->get();
                Log::channel("photoprintsettings")->info("request value photo_print_settings_id:: $id");
                $count = $get_photo->count();
                if ($count > 0) {
                    $final = [];
                    foreach ($get_photo as $value) {
                        $ary = [];
                        $ary['photo_print_settings_id'] = $value['photo_print_settings_id'];
                        $ary['width'] = $value['width'];
                        $ary['height'] = $value['height'];
                        $ary['min_resolution_width'] = $value['min_resolution_width'];
                        $ary['min_resolution_height'] = $value['min_resolution_height'];
                        $ary['max_resolution_width'] = $value['max_resolution_width'];
                        $ary['max_resolution_height'] = $value['max_resolution_height'];
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
                    Log::channel("photoprintsettings")->info("view value :: $log");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Photo print setting viewed successfully'),
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
            Log::channel("photoprintsettings")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
    public function photoprintsettings_delete(Request $request)
    {
        try {
            if (!empty($request)) {

                $ids = $request->id;

                if (!empty($ids)) {
                    Log::channel("photoprintsettings")->info("request value photo_print_settings_id:: $ids :: status :: $request->status");

                    $photo = Photoprintsetting::where('photo_print_settings_id', $ids)->first();
                    $update = Photoprintsetting::where('photo_print_settings_id', $ids)->update(array(
                        'status' => $request->status,
                        'updated_on' => Server::getDateTime(),
                        'updated_by' => JwtHelper::getSesUserId()
                    ));

                    // log activity
                    if ($request->status == 0) {
                        $activity_status = 'inactivated';
                    } else if ($request->status == 1) {
                        $activity_status = 'activated';
                    } else if ($request->status == 2) {
                        $activity_status = 'deleted';
                    }
                    $desc =  'Photo print settings ' . '(' . $photo->width . '*' . $photo->height . ')' . ' is ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Photo Print Setting');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    if ($request->status == 0) {
                        Log::channel("photoprintsettings")->info("save value :: photo_print_settings_id :: $ids :: photoprintsettings inactive successfully");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Photo print settings inactivated successfully'),
                            'data' => []
                        ]);
                    } else if ($request->status == 1) {
                        Log::channel("Photoprintsettings")->info("save value :: photo_print_settings_id :: $ids :: photoprintsettings active successfully");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Photo print settings activated successfully'),
                            'data' => []
                        ]);
                    } else if ($request->status == 2) {
                    $exist = ProductCatalogue::where('print_size', $ids)->where('status', '!=', 2)->first();
                    if (empty($exist)) {
                        Log::channel("Photoprintsettings")->info("save value :: photo_print_settings_id :: $ids :: photoprintsettings active successfully");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Photo print settings deleted successfully'),
                            'data' => []
                        ]);
                        } else {
                        return response()->json([
                          'keyword' => 'failed',
                          'message' =>  __('The photo print setting is Already used in product catalogue.'),
                          'data' => []
                        ]);
                      }
                    }
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('No data found'),
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('message.no_data'),
                    'data' => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("photoprintsettings")->error($exception);
            Log::channel("photoprintsettings")->info('** end the photoprintsettings status method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

}
