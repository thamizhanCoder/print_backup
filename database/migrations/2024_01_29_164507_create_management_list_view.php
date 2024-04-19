<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateManagementListView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = "CREATE VIEW management_list AS
                SELECT
    `communication`.`communication_id` AS `communication_id`,
    `communication`.`communication_no` AS `communication_no`,
    `communication`.`task_manager_id` AS `task_manager_id`,
    `communication`.`orderitem_stage_id` AS `orderitem_stage_id`,
    `communication`.`subject` AS `subject`,
    `communication`.`status` AS `status`,
    `communication`.`created_on` AS `created_on`,
    `communication`.`closed_on` AS `closed_on`,
    `communication`.`created_by` AS `created_by`,
    `communication`.`updated_on` AS `updated_on`,
    `communication`.`updated_by` AS `updated_by`,
    `communication_inbox`.`employee_id` AS `employee_id`,
    `employee`.`employee_name` AS `employee_name`,
    `order_items`.`product_id` AS `product_id`,
    `order_items`.`product_code` AS `product_code`,
    `order_items`.`product_name` AS `product_name`,
    `service`.`service_name` AS `service_name`,
    `orders`.`order_code` AS `order_code`,
    `order_items`.`order_id` AS `order_id`,
    `task_manager`.`task_code` AS `task_code`,
    `task_manager`.`task_name` AS `task_name`,
    `task_manager_history`.`work_stage` AS `work_stage`,
    `department`.`department_name` AS `department_name`
FROM
    (
        (
            (
                (
                    (
                        (
                            (
                                (
                                    `communication`
                                LEFT JOIN `task_manager` ON
                                    (
                                        `task_manager`.`task_manager_id` = `communication`.`task_manager_id`
                                    )
                                )
                            LEFT JOIN `order_items` ON
                                (
                                    `order_items`.`order_items_id` = `task_manager`.`order_items_id`
                                )
                            )
                        LEFT JOIN `orders` ON
                            (
                                `orders`.`order_id` = `order_items`.`order_id`
                            )
                        )
                    LEFT JOIN `employee` ON
                        (
                            `employee`.`employee_id` = `communication`.`created_by`
                        )
                    )
                LEFT JOIN `department` ON
                    (
                        `department`.`department_id` = `employee`.`department_id`
                    )
                )
            LEFT JOIN `communication_inbox` ON
                (
                    `communication_inbox`.`communication_id` = `communication`.`communication_id`
                )
            )
        LEFT JOIN `service` ON
            (
                `service`.`service_id` = `order_items`.`service_id`
            )
        )
    LEFT JOIN `task_manager_history` ON
        (
            `task_manager_history`.`orderitem_stage_id` = `communication`.`orderitem_stage_id`
        )
    )
WHERE
    `task_manager_history`.`production_status` = 1
GROUP BY
    `communication`.`communication_id`
UNION
SELECT
    `communication`.`communication_id` AS `communication_id`,
    `communication`.`communication_no` AS `communication_no`,
    `communication`.`task_manager_id` AS `task_manager_id`,
    `communication`.`orderitem_stage_id` AS `orderitem_stage_id`,
    `communication`.`subject` AS `subject`,
    `communication`.`status` AS `status`,
    `communication`.`created_on` AS `created_on`,
    `communication`.`closed_on` AS `closed_on`,
    `communication`.`created_by` AS `created_by`,
    `communication`.`updated_on` AS `updated_on`,
    `communication`.`updated_by` AS `updated_by`,
    `communication_inbox`.`employee_id` AS `employee_id`,
    `employee`.`employee_name` AS `employee_name`,
    `order_items`.`product_id` AS `product_id`,
    `order_items`.`product_code` AS `product_code`,
    `order_items`.`product_name` AS `product_name`,
    `service`.`service_name` AS `service_name`,
    `orders`.`order_code` AS `order_code`,
    `order_items`.`order_id` AS `order_id`,
    `task_manager`.`task_code` AS `task_code`,
    `task_manager`.`task_name` AS `task_name`,
    `task_manager_history`.`work_stage` AS `work_stage`,
    `department`.`department_name` AS `department_name`
FROM
    (
        (
            (
                (
                    (
                        (
                            (
                                (
                                    `communication`
                                LEFT JOIN `task_manager` ON
                                    (
                                        `task_manager`.`task_manager_id` = `communication`.`task_manager_id`
                                    )
                                )
                            LEFT JOIN `order_items` ON
                                (
                                    `order_items`.`order_items_id` = `task_manager`.`order_items_id`
                                )
                            )
                        LEFT JOIN `orders` ON
                            (
                                `orders`.`order_id` = `order_items`.`order_id`
                            )
                        )
                    LEFT JOIN `employee` ON
                        (
                            `employee`.`employee_id` = `communication`.`created_by`
                        )
                    )
                LEFT JOIN `department` ON
                    (
                        `department`.`department_id` = `employee`.`department_id`
                    )
                )
            LEFT JOIN `communication_inbox` ON
                (
                    `communication_inbox`.`communication_id` = `communication`.`communication_id`
                )
            )
        LEFT JOIN `service` ON
            (
                `service`.`service_id` = `order_items`.`service_id`
            )
        )
    LEFT JOIN `task_manager_history` ON
        (
            `task_manager_history`.`task_manager_id` = `communication`.`task_manager_id`
        )
    )
WHERE
    `communication`.`orderitem_stage_id` IS NULL AND `task_manager_history`.`production_status` = 1
GROUP BY
    `communication`.`communication_id`";

        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $sql = "DROP VIEW IF EXISTS management_list";
        DB::statement($sql);
    }
}
