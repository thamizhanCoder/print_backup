<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOperationTaskManagerView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = "CREATE VIEW operation_task_manager AS
                SELECT
    `order_items`.`order_items_id` AS `order_items_id`,
    `order_items`.`order_id` AS `order_id`,
    `order_items`.`product_id` AS `product_id`,
    `order_items`.`product_variant_id` AS `product_variant_id`,
    `order_items`.`service_id` AS `service_id`,
    `order_items`.`product_name` AS `product_name`,
    `order_items`.`product_code` AS `product_code`,
    `orders`.`order_code` AS `order_code`,
    `orders`.`order_date` AS `order_date`,
    NULL AS `task_manager_id`,
    NULL AS `task_code`,
    NULL AS `task_type`,
    NULL AS `task_name`,
    NULL AS `description`,
    NULL AS `attachment_image`,
    NULL AS `current_task_stage`,
    NULL AS `status`,
    NULL AS `created_on`,
    NULL AS `folder`
FROM
    (
        `order_items`
    LEFT JOIN `orders` ON
        (
            `orders`.`order_id` = `order_items`.`order_id`
        )
    )
WHERE
    `order_items`.`order_status` = 10 AND `order_items`.`production_status` = 0
UNION
SELECT
    `task_manager`.`order_items_id` AS `order_items_id`,
    NULL AS `order_id`,
    NULL AS `product_id`,
    NULL AS `product_variant_id`,
    NULL AS `service_id`,
    NULL AS `product_name`,
    NULL AS `product_code`,
    NULL AS `order_code`,
    NULL AS `order_date`,
    `task_manager`.`task_manager_id` AS `task_manager_id`,
    `task_manager`.`task_code` AS `task_code`,
    `task_manager`.`task_type` AS `task_type`,
    `task_manager`.`task_name` AS `task_name`,
    `task_manager`.`description` AS `description`,
    `task_manager`.`attachment_image` AS `attachment_image`,
    `task_manager`.`current_task_stage` AS `current_task_stage`,
    `task_manager`.`status` AS `status`,
    `task_manager`.`created_on` AS `created_on`,
    `task_manager`.`folder` AS `folder`
FROM
    `task_manager`
WHERE
    `task_manager`.`current_task_stage` = 1";

        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $sql = "DROP VIEW IF EXISTS operation_task_manager";
        DB::statement($sql);
    }
}
