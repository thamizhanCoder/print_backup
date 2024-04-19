<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCouponcodecountHistoryView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = "CREATE VIEW couponcodecount_history AS
                SELECT
    `coupon_code`.`coupon_code_id` AS `coupon_code_id`,
    `orders`.`coupon_code` AS `coupon_code`,
    COUNT(
        `orders`.`coupon_code`
    ) AS `count`
FROM
    (
        `orders`
    LEFT JOIN `coupon_code` ON
        (
            `coupon_code`.`coupon_code` = `orders`.`coupon_code`
        )
    )
WHERE
    `orders`.`coupon_code` IS NOT NULL
GROUP BY
    `orders`.`coupon_code`";

        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $sql = "DROP VIEW IF EXISTS couponcodecount_history";
        DB::statement($sql);
    }
}
