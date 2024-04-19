<html>

<head>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,400;0,500;1,400&display=swap" rel="stylesheet" />
    <script type="text/javascript" src="https://canvasjs.com/assets/script/jquery-1.11.1.min.js"></script>
    <style>
        body {
            font-family: Roboto, "Helvetica Neue", sans-serif;
        }

        .employee-table th {
            color: black !important;
            font-weight: 600 !important;
        }

        .employee-table th {
            background: red;
        }

        .employee-table {
            border-spacing: 0;
            border-collapse: collapse;
            width: 30%;
            border-color: #d9d9d9;
            margin-top: 20px;
        }

        .employee-table td {
            line-height: 30px;
            white-space: nowrap;
        }

        .employee-table th {
            font-size: 13px;
            padding: 4px 4px;
            white-space: nowrap;
        }

        .employee-table td {
            font-size: 13px !important;
            padding: 4px 4px;
        }

        .text-center {
            text-align: center;
        }

        .canvasjs-chart-credit {
            display: none;
        }

        .canvasjs-chart-canvas {
            /* width: 73% !important; */

        }

        .table-form {
            width: 100%;
            border-spacing: 0px;
            padding: 0px;
            margin-top: 20px;
        }

        .table-form td {
            text-transform: capitalize;
            padding: 8px;
        }

        .table-form th {
            background-color: #dfdfdf;
            font-weight: 500;
            padding: 10px 8px;
        }

        .searchdata td {
            font-size: 14px;
        }
    </style>
</head>

<body>
    <table style="width:100%;">
        <tr>
            <td style="width: 100%; text-align: center;">
                <!-- <img src="https://sparkapiv2.petalyellow.com/public/logo.png" style="width:14%;" /> -->
                <h2>Task Report</h2>
            </td>

        </tr>
    </table>


    <div style="border-top:1px solid #ccc;"></div>
    <table border="1" class="table-form">
        <thead>
            <tr>
            <?php if ($req['filterByType'] == 2) { ?>
                <th style="text-align:center;width:15%;font-weight: bold;">Date</th>
                <th style="text-align:center;width: 15%;font-weight: bold;">Type of task</th>
                <th style="text-align:center;width: 15%;font-weight: bold;">Order ID</th>
                <th style="text-align:center;width: 15%;font-weight: bold;">Product ID</th>
                <th style="text-align:center;width: 15%;font-weight: bold;">Assigned to</th>
                <th style="text-align:center;width: 15%;font-weight: bold;">Status</th>
                <?php } ?>
                <?php if ($req['filterByType'] == 1) { ?>
                <th style="text-align:center;width:15%;font-weight: bold;">Date</th>
                <th style="text-align:center;width: 15%;font-weight: bold;">Type of task</th>
                <th style="text-align:center;width: 15%;font-weight: bold;">Task code</th>
                <th style="text-align:center;width: 15%;font-weight: bold;">Task name</th>
                <th style="text-align:center;width: 15%;font-weight: bold;">Assigned to</th>
                <th style="text-align:center;width: 15%;font-weight: bold;">Status</th>
                <?php } ?>
            </tr>
        </thead>
        <?php
        foreach ($final as $fs) {
        ?>
            <tbody>
                <tr>
                <?php if ($req['filterByType'] == 2) { ?>
                    <td style="text-align:center;width:10%;"><?php echo date('d-m-Y', strtotime($fs['date'])); ?></td>
                    <td style="text-align:center;width:10%;text-transform:uppercase;"><?php echo $fs['task_type']; ?></td>
                    <td style="text-align:center;width:20%;text-transform:uppercase;"><?php echo $fs['order_code']; ?></td>
                    <td style="text-align:center;width:20%;text-transform:uppercase;"><?php echo $fs['product_code']; ?></td>
                    <td style="text-align:center;width:10%;"><?php echo "stage "; echo $fs['stage']; ?><?php echo " : "; echo $fs['employee_name']; ?></td>
                    <td style="text-align:center;width:10%;"><?php if ($fs['work_stage'] == 1) {
                                                        echo "<b><font color=orange>TO DO</font></b>";
                                                    } elseif ($fs['work_stage'] == 2) {
                                                        echo "<b><font color=red>IN PROGRESS</font></b>";
                                                    } elseif ($fs['work_stage'] == 3) {
                                                      echo "<b><font color=gray>PREVIEW</font></b>";
                                                  } elseif ($fs['work_stage'] == 4) {
                                                    echo "<b><font color=green>COMPLETED</font></b>";
                                                } elseif ($fs['work_stage'] == 5) {
                                                  echo "<b><font color=brown>OVER DUE</font></b>";
                                              } ?></td>
                <?php } ?>

                <?php if ($req['filterByType'] == 1) { ?>
                    <td style="text-align:center;width:10%;"><?php echo date('d-m-Y', strtotime($fs['date'])); ?></td>
                    <td style="text-align:center;width:10%;text-transform:uppercase;"><?php echo $fs['task_type']; ?></td>
                    <td style="text-align:center;width:20%;text-transform:uppercase;"><?php echo $fs['task_code']; ?></td>
                    <td style="text-align:center;width:20%;text-transform:uppercase;"><?php echo $fs['task_name']; ?></td>
                    <td style="text-align:center;width:10%;"><?php echo $fs['employee_name']; ?></td>
                    <td style="text-align:center;width:10%;"><?php if ($fs['work_stage'] == 1) {
                                                        echo "<b><font color=orange>TO DO</font></b>";
                                                    } elseif ($fs['work_stage'] == 2) {
                                                        echo "<b><font color=red>IN PROGRESS</font></b>";
                                                    } elseif ($fs['work_stage'] == 3) {
                                                      echo "<b><font color=gray>PREVIEW</font></b>";
                                                  } elseif ($fs['work_stage'] == 4) {
                                                    echo "<b><font color=green>COMPLETED</font></b>";
                                                } elseif ($fs['work_stage'] == 5) {
                                                  echo "<b><font color=brown>OVER DUE</font></b>";
                                              } ?></td>
                <?php } ?>


                </tr>
            </tbody>
        <?php } ?>

    </table>
    </div>
</body>

</html>