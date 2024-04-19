<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Helpers\GlobalHelper;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Models\ProductCatalogue;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    public function category_create(CategoryRequest $request)
    {

        try {
            Log::channel("category")->info("** started the category create method **");

            if ($request->input('category_image') != '') {

                $getExtension = pathinfo($request->input('category_image'), PATHINFO_EXTENSION);

                $extension_array = ['jpeg', 'png', 'jpg'];

                if (in_array($getExtension, $extension_array)) {

                    $exits = Category::where([
                        ['service_id', '=', $request->service_id],
                        ['category_name', $request->input('category_name')], ['status', '!=', 2],
                    ])->first();

                    if (empty($exits)) {

                        $category = new Category();
                        $category->category_name = $request->category_name;
                        $category->category_image = $request->category_image;
                        $category->service_id = $request->service_id;
                        $category->created_on = Server::getDateTime();
                        $category->created_by = JwtHelper::getSesUserId();

                        if ($category->save()) {

                            $categorys = Category::where('category_id', $category->category_id)
                                ->select('category.*')
                                ->first();
                            Log::channel("category")->info("** category save details : $categorys **");
                            // log activity
                            $desc = 'Category ' . '(' . $category->category_name . ')' . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                            $activitytype = Config('activitytype.Category');
                            GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
                            Log::channel("category")->info("** end the category create method **");

                            return response()->json([
                                'keyword' => 'success',
                                'message' => __('Category created successfully'),
                                'data' => [$categorys],

                            ]);
                        } else {
                            return response()->json([
                                'keyword' => 'failure',
                                'message' => __('Category creation failed'),
                                'data' => [],
                            ]);
                        }
                    } else {
                        return response()->json([
                            'keyword' => 'failed',
                            'message' => __('Category name already exist'),
                            'data' => [],
                        ]);
                    }
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => ('Only JPG,JPEG,PNG formats allowed for image'),
                        'data' => [],
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => 'Image field is required',
                    'data' => [],
                ]);
            }

        } catch (\Exception $exception) {
            Log::channel("category")->error($exception);
            Log::channel("category")->error('** end the category create method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
    public function category_list(Request $request)
    {
        try {
            Log::channel("category")->info('** started the category list method **');

            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'category_name' => 'category.category_name',
                'service_name' => 'service.service_name',
            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "category_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array('category.category_name', 'service.service_name');

            $category = Category::where([
                ['category.status', '!=', 2],
            ])->leftjoin('service', 'service.service_id', '=', 'category.service_id')
                ->select('category.*', 'service.service_name');

            $category->where(function ($query) use ($searchval, $column_search, $category) {
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
                $category->orderBy($order_by_key[$sortByKey], $sortType);
            }
            $count = $category->count();
            if ($offset) {
                $offset = $offset * $limit;
                $category->offset($offset);
            }
            if ($limit) {
                $category->limit($limit);
            }
            // Log::channel("category")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType");
            $category->orderBy('category_id', 'desc');
            $category = $category->get();
            if ($count > 0) {
                $final = [];
                foreach ($category as $value) {
                    $ary = [];
                    $ary['category_id'] = $value['category_id'];
                    $ary['category_name'] = $value['category_name'];
                    $ary['image'] = $value['category_image'];
                    $ary['service_id'] = $value['service_id'];
                    $ary['service_name'] = $value['service_name'];
                    $ary['category_image'] = ($ary['image'] != '') ? env('APP_URL') . env('CATEGORY_URL') . $ary['image'] : env('APP_URL') . "avatar.jpg";
                    $ary['created_on'] = $value['created_on'];
                    $ary['created_by'] = $value['created_by'];
                    $ary['updated_on'] = $value['updated_on'];
                    $ary['updated_by'] = $value['updated_by'];
                    $ary['status'] = $value['status'];
                    $final[] = $ary;
                }
            }

            if (!empty($final)) {
                // $impl = json_encode($final, true);
                // Log::channel("category")->info("Category Controller end:: save values :: $impl ::::end");
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Category listed successfully'),
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
            Log::channel("category")->error($exception);
            Log::channel("category")->error('** end the category list method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
    public function category_update(CategoryRequest $request)
    {
        try {
            Log::channel("category")->info('** started the category update method **');

            if ($request->input('category_image') != '') {

                $getExtension = pathinfo($request->input('category_image'), PATHINFO_EXTENSION);

                $extension_array = ['jpeg', 'png', 'jpg'];

                if (in_array($getExtension, $extension_array)) {

                    $exits = Category::where([
                        ['category_id', '!=', $request->input('category_id')],
                        ['service_id', '=', $request->service_id],
                        ['category_name', $request->input('category_name')], ['status', '!=', 2],
                    ])->first();

                    if (empty($exits)) {
                        $ids = $request->category_id;
                        $category = Category::find($ids);
                        $category->category_name = $request->category_name;
                        $category->service_id = $request->service_id;
                        $category->category_image = $request->category_image;
                        $category->updated_on = Server::getDateTime();
                        $category->updated_by = JwtHelper::getSesUserId();

                        if ($category->save()) {

                            $categorys = Category::where('category_id', $category->category_id)->first();

                            // log activity
                            $desc = 'Category' . '(' . $category->category_name . ')' . ' is updated by ' . JwtHelper::getSesUserNameWithType() . '';
                            $activitytype = Config('activitytype.Category');
                            GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
                            Log::channel("category")->info("save value :: $categorys");
                            Log::channel("category")->info('** end the category update method **');

                            return response()->json([
                                'keyword' => 'success',
                                'message' => __('Category updated successfully'),
                                'data' => [$categorys],

                            ]);
                        } else {
                            return response()->json([
                                'keyword' => 'failure',
                                'message' => __('Category update failed'),
                                'data' => [],
                            ]);
                        }
                    } else {
                        return response()->json([
                            'keyword' => 'failure',
                            'message' => __('Category name already exist'),
                            'data' => [],
                        ]);
                    }

                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => ('Only JPG,JPEG,PNG formats allowed for image'),
                        'data' => [],
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => 'Image field is required',
                    'data' => [],
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("category")->error($exception);
            Log::channel("category")->error('** end the category update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function category_view($id)
    {
        try {
            Log::channel("category")->info('** started the category view method **');
            if ($id != '' && $id > 0) {
                $category = Category::where('category_id', $id)->leftjoin('service', 'service.service_id', '=', 'category.service_id')->select('category.*', 'service.service_name')->get();
                Log::channel("category")->info("request value category_id:: $id");
                $count = $category->count();
                if ($count > 0) {
                    $final = [];
                    foreach ($category as $value) {
                        $ary = [];
                        $ary['category_id'] = $value['category_id'];
                        $ary['category_name'] = $value['category_name'];
                        $ary['image'] = $value['category_image'];
                        $ary['service_id'] = $value['service_id'];
                        $ary['service_name'] = $value['service_name'];
                        $ary['category_image'] = ($ary['image'] != '') ? env('APP_URL') . env('CATEGORY_URL') . $ary['image'] : env('APP_URL') . "avatar.jpg";
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
                    Log::channel("category")->info("view value :: $log");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Category viewed successfully'),
                        'data' => $final,
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('No data found'),
                        'data' => [],
                    ]);
                }
            }
        } catch (\Exception $exception) {
            Log::channel("category")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function category_delete(Request $request)
    {
        try {
            if (!empty($request)) {
                $ids = $request->id;

                Log::channel("category")->info("request value category_id :: $ids :: ");

                $exist = ProductCatalogue::where('category_id', $ids)->where('status', '!=', 2)->first();
                if (empty($exist)) {

                    $category = Category::where('category_id', $ids)->first();
                    $update = Category::where('category_id', $ids)->update(array(
                        'status' => 2,
                        'updated_on' => Server::getDateTime(),
                        'updated_by' => JwtHelper::getSesUserId(),
                    ));
                    // log activity
                    $desc = 'Category ' . '(' . $category->category_name . ')' . ' is' . ' deleted by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Category');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    Log::channel("category")->info("save value :: category_id  :: $ids :: category deleted successfully");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Category deleted successfully'),
                        'data' => [],
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('This category is already used in product catalogue u cannot delete.'),
                        'data' => [],
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('message.failed'),
                    'data' => [],
                ]);
            }

        } catch (\Exception $exception) {
            Log::channel("category")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
    public function servicename_getcall()
    {
        $get_service = Service::select('service.*')->get();

        if (!empty($get_service)) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Service listed successfully'),
                    'data' => $get_service,
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
    }
}
