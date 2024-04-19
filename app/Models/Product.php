<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    protected $table='product';
    protected $primaryKey = 'product_id';
    protected $fillable=['product_code','product_name','brand_id','category_id','subcategory_id','product_description','product_specification','other_specification','product_image','selling_price','mrp','quantity','dealer_commision_amount','is_cod','is_featured_product','is_notification','is_trending_item','is_hot_item','product_available'];
    public $timestamps = false;

    public static function getStock($TypeOfStock,$filterByCategory)
{
    $records=DB::table('product')->leftjoin('category','category.category_id','=','product.category_id')->select('product.product_name','product.product_code','category.category_name','product.quantity','product.status')->where('product.status','!=',2);
   
    if ($TypeOfStock != '[]') {
        $TypeOfStock = json_decode($TypeOfStock, true);
        if ($TypeOfStock == [1]) {
            $records->where('product.quantity','!=',0);
        }
        if ($TypeOfStock == [0]) {
            $records->where('product.quantity',0);
        }
        if ($TypeOfStock == [2]) {
            $records->where('product.status','!=',2);
        }
    }

    if ($filterByCategory != '[]') {
        $filterByCategory = json_decode($filterByCategory, true);
        if ($filterByCategory > [0]) {
            $records->whereIn('product.category_id',$filterByCategory);
        }
    }
    $records = $records->get();
    $product_image = [];
    foreach ($records as $key => $product) {
        $product_image[$key]['product_code'] = $product->product_code;
        $product_image[$key]['product_name'] = $product->product_name;
        $product_image[$key]['category_name'] = $product->category_name;
        $product_image[$key]['quantity'] = sprintf($product->quantity);
        if ($TypeOfStock == [2]) {
        $product_image[$key]['quantity_stock']  = ($product->quantity > 0) ? "In stock" : "Out of stock";
        }
        else{
            echo '';
        }
    }
    // print_r($product_image);exit;
    return $product_image;
}

}