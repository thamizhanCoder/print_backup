<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductDaywiseVisitHistoryView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = "CREATE VIEW product_daywise_visit_history AS
                SELECT
    CAST(`h`.`visited_on` AS DATE) AS `visited_on`,
    `p`.`service_id` AS `service_id`,
    `p`.`service_name` AS `service_name`,
    COUNT(0) AS `visited_count`
FROM
    (
        `product_visit_history` `h`
    LEFT JOIN `service` `p`
    ON
        (`p`.`service_id` = `h`.`service_id`)
    )
GROUP BY
    `p`.`service_id`,
    CAST(`h`.`visited_on` AS DATE)
ORDER BY
    CAST(`h`.`visited_on` AS DATE)";

        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $sql = "DROP VIEW IF EXISTS product_daywise_visit_history";
        DB::statement($sql);
    }
}
