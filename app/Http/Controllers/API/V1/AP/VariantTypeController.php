<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Helpers\GlobalHelper;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Controllers\Controller;
use App\Http\Requests\VariantTypeRequest;
use App\Models\ProductVariant;
use App\Models\VariantType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VariantTypeController extends Controller
{
    public function variant_type_create(VariantTypeRequest $request)
    {
        try {
            Log::channel("varianttype")->info('** started the variant type create method **');

            $varianttype = new VariantType();
            $exist = VariantType::where([['variant_type', $request->input('variant_type')], ['status', '!=', 2]])->first();

            if (empty($exist)) {
                $varianttype = new VariantType();
                $varianttype->variant_type = $request->input('variant_type');
                $varianttype->created_on = Server::getDateTime();
                $varianttype->created_by = JwtHelper::getSesUserId();
                Log::channel("varianttype")->info("request value :: $varianttype->variant_type");

                if ($varianttype->save()) {
                    $varianttypes = VariantType::where('variant_type_id', $varianttype->variant_type_id)->first();

                    //log activity
                    $desc = 'Variant Type ' . $varianttype->variant_type . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Variant Type');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    Log::channel("varianttype")->info("save value :: $varianttypes");
                    Log::channel("varianttype")->info('** end the variant type create method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Variant type created successfully'),
                        'data' => [$varianttypes],
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('Variant type creation failed'),
                        'data' => [],
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('Variant type already exist'),
                    'data' => [],
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("varianttype")->error($exception);
            Log::channel("varianttype")->error('** end the variant type create method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function variant_type_update(VariantTypeRequest $request)
    {
        try {
            Log::channel("varianttype")->info('** started the variant type update method **');

            $exist = VariantType::where([['variant_type_id', '!=', $request->variant_type_id], ['variant_type', $request->variant_type], ['status', '!=', 2]])->first();

            if (empty($exist)) {
                $varianttypesOldDetails = VariantType::where('variant_type_id', $request->variant_type_id)->first();

                $ids = $request->variant_type_id;
                $varianttype = VariantType::find($ids);
                $varianttype->variant_type = $request->variant_type;
                $varianttype->updated_on = Server::getDateTime();
                $varianttype->updated_by = JwtHelper::getSesUserId();

                if ($varianttype->save()) {
                    $varianttypes = VariantType::where('variant_type_id', $varianttype->variant_type_id)->first();

                    // log activity
                    $desc =  'Variant Type ' . '(' . $varianttypesOldDetails->variant_type . ')' . ' is updated as ' . '(' . $varianttype->variant_type . ')' . ' by ' .JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Variant Type');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    Log::channel("varianttype")->info("save value :: $varianttypes");
                    Log::channel("varianttype")->info('** end the variant type update method **');

                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Variant type updated successfully'),
                        'data' => [$varianttypes],
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('Variant type update failed'),
                        'data' => [],
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('Variant type already exist'),
                    'data' => [],
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("varianttype")->error($exception);
            Log::channel("varianttype")->error('** end the variant type update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function variant_type_list(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";
        $order_by_key = [
            // 'mention the api side' => 'mention the mysql side column'
            'variant_type' => 'variant_type',
        ];
        $sort_dir = ['ASC', 'DESC'];
        $sortByKey = ($request->sortByKey) ? $request->sortByKey : "variant_type_id";
        $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
        $column_search = array('variant_type');
        $varianttypes = VariantType::where([
            ['status', '!=', '2'],
        ]);

        $varianttypes->where(function ($query) use ($searchval, $column_search, $varianttypes) {
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
            $varianttypes->orderBy($order_by_key[$sortByKey], $sortType);
        }
        $count = $varianttypes->count();
        if ($offset) {
            $offset = $offset * $limit;
            $varianttypes->offset($offset);
        }
        if ($limit) {
            $varianttypes->limit($limit);
        }
        $varianttypes->orderBy('variant_type_id', 'desc');
        $varianttypes = $varianttypes->get();
        if ($count > 0) {
            $final = [];
            foreach ($varianttypes as $value) {
                $ary = [];
                $ary['variant_type_id'] = $value['variant_type_id'];
                $ary['variant_type'] = $value['variant_type'];
                $ary['created_on'] = $value['created_on'];
                $ary['created_by'] = $value['created_by'];
                $ary['updated_on'] = $value['updated_on'];
                $ary['updated_by'] = $value['updated_by'];
                $ary['status'] = $value['status'];
                $ary['is_default'] = $value['is_default'];
                $final[] = $ary;
            }
        }
        if (!empty($final)) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Variant type listed successfully'),
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
    }

    public function variant_type_view($id)
    {
        if ($id != '' && $id > 0) {
            $varianttype = new VariantType();
            $get_variant_type = VariantType::where('variant_type_id', $id)->get();
            $count = $get_variant_type->count();
            if ($count > 0) {
                $final = [];
                foreach ($get_variant_type as $value) {
                    $ary = [];
                    $ary['variant_type_id'] = $value['variant_type_id'];
                    $ary['variant_type'] = $value['variant_type'];
                    $ary['status'] = $value['status'];
                    $ary['created_on'] = $value['created_on'];
                    $ary['created_by'] = $value['created_by'];
                    $ary['updated_on'] = $value['updated_on'];
                    $ary['updated_by'] = $value['updated_by'];
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Variant type viewed successfully'),
                    'data' => $final,
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                ]);
            }
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('No data found'),
                'data' => [],
            ]);
        }
    }

    public function variant_type_delete(Request $request)
    {
        try {

            if (!empty($request)) {
                $ids = $request->id;

                // $qry = "select * from `product_variant`
                // WHERE JSON_CONTAINS(JSON_EXTRACT(variant_attributes, '$**.variant_type_id'),'" . '"' . $ids . '"' . "')";

                // $checkUsage = DB::select(DB::raw($qry));

            
                $checkUsage = ProductVariant::whereRaw("JSON_CONTAINS(JSON_EXTRACT(variant_attributes, '$[*].variant_type_id'), ?)", $ids);

                $count = count($checkUsage->get());

                $getVarientDet = $checkUsage->get();
             

                if ($count == 0) {
                    Log::channel("varianttype")->info('** started the variant type delete method **');
                    Log::channel("varianttype")->info("request value variant_type_id:: $ids :: ");
                    $varianttype = VariantType::where('variant_type_id', $ids)->first();
                    $update = VariantType::where('variant_type_id', $ids)->update(array(
                        'status' => 2,
                        'updated_on' => Server::getDateTime(),
                        'updated_by' => JwtHelper::getSesUserId(),
                    ));

                    // log activity
                    $desc = 'Variant Type ' . $varianttype->variant_type . ' is' . ' deleted by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Variant Type');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
                    Log::channel("varianttype")->info("save value :: variant_type_id :: $ids :: variant type deleted successfully");
                    Log::channel("varianttype")->info('** end the variant type delete method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Variant type deleted successfully'),
                        'data' => [],
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('Variant type is used in product catalogue u cannot delete'),
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
            Log::channel("varianttype")->error($exception);
            Log::channel("varianttype")->info('** end the variant type delete method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
}
