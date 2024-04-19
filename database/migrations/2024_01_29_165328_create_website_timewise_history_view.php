<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebsiteTimewiseHistoryView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = "CREATE VIEW website_timewise_history AS
                SELECT
    `visit_history`.`page_type` AS `page_type`,
    CAST(
        `visit_history`.`visited_on` AS DATE
    ) AS `visited_on`,
    `allhours`.`hr` AS `visited_time`,
    COUNT(
        `visit_history`.`visited_on`
    ) AS `visited_count`
FROM
    (
        (
        SELECT
            0 AS `hr`
        UNION ALL
    SELECT
        1 AS `1`
    UNION ALL
SELECT
    2 AS `2`
UNION ALL
SELECT
    3 AS `3`
UNION ALL
SELECT
    4 AS `4`
UNION ALL
SELECT
    5 AS `5`
UNION ALL
SELECT
    6 AS `6`
UNION ALL
SELECT
    7 AS `7`
UNION ALL
SELECT
    8 AS `8`
UNION ALL
SELECT
    9 AS `9`
UNION ALL
SELECT
    10 AS `10`
UNION ALL
SELECT
    11 AS `11`
UNION ALL
SELECT
    12 AS `12`
UNION ALL
SELECT
    13 AS `13`
UNION ALL
SELECT
    14 AS `14`
UNION ALL
SELECT
    15 AS `15`
UNION ALL
SELECT
    16 AS `16`
UNION ALL
SELECT
    17 AS `17`
UNION ALL
SELECT
    18 AS `18`
UNION ALL
SELECT
    19 AS `19`
UNION ALL
SELECT
    20 AS `20`
UNION ALL
SELECT
    21 AS `21`
UNION ALL
SELECT
    22 AS `22`
UNION ALL
SELECT
    23 AS `23`
    ) `allhours`
LEFT JOIN `visit_history` ON
    (
        HOUR(
            `visit_history`.`visited_on`
        ) = `allhours`.`hr`
    )
    )
WHERE
    `visit_history`.`visited_on` IS NOT NULL
GROUP BY
    `allhours`.`hr`,
    CAST(
        `visit_history`.`visited_on` AS DATE
    )
ORDER BY
    `allhours`.`hr`";

        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $sql = "DROP VIEW IF EXISTS website_timewise_history";
        DB::statement($sql);
    }
}
