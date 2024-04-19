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
                <h2>Sales Report</h2>
            </td>

        </tr>
    </table>


    <div style="border-top:1px solid #ccc;"></div>
    <table border="1" class="table-form">
        <thead>
            <tr>
                <th style="text-align:center;width:15%;font-weight: bold;">Order Date</th>
                <th style="text-align:center;width: 15%;font-weight: bold;">Order ID</th>
                <th style="text-align:center;width: 15%;font-weight: bold;">Customer Name</th>
                <?php if ($req['typeCus'] == 'all') { ?>
                    <th style="text-align:center;width: 15%;font-weight: bold;">Type of Customer</th>
                <?php } ?>
                <?php if ($req['typeCus'] == '') { ?>
                    <th style="text-align:center;width: 10%;font-weight: bold;">Type of Customer</th>
                <?php } ?>
                <?php if ($req['typeCus'] == 1 && $req['dealer'] != '[]') { ?>
                    <th style="text-align:center;width: 15%;font-weight: bold;">Dealer Name</th>
                <?php } ?>
                <th style="text-align:center;width: 15%;font-weight: bold;">Order Amount(₹)</th>
                <?php if ($req['status'] == '[]' || $req['status'] == '') { ?>
                <th style="text-align:center;width: 8%;font-weight: bold;">Status</th>
                <?php } ?>
            </tr>
        </thead>
        <?php
        foreach ($sales as $fs) {
        ?>
            <tbody>
                <tr>
                    <td style="text-align:center;"><?php echo date('d-m-Y', strtotime($fs['order_date'])); ?></td>
                    <td style="text-align:center;text-transform:uppercase;"><?php echo $fs['order_code']; ?></td>
                    <td style="text-align:center;"> <?php echo $fs['billing_customer_first_name'] . ' ' . $fs['billing_customer_last_name']; ?></td>
                    <?php if ($req['typeCus'] == 'all') { ?>
                    <?php if ($fs['customer_type'] == 2) { ?>
                        <td style="text-align:center;"> <?php echo $fs['customer_first_name'] . ' ' . $fs['customer_last_name']; ?></td>
                    <?php } else { ?>
                        <td style="text-align:center;"> <?php echo "non-dealer"; ?></td>
                    <?php } ?>
                    <?php } ?>
                    <?php if ($req['typeCus'] == '') { ?>
                    <?php if ($fs['customer_type'] == 2) { ?>
                        <td style="text-align:center;"> <?php echo $fs['customer_first_name'] . ' ' . $fs['customer_last_name']; ?></td>
                    <?php } else { ?>
                        <td style="text-align:center;"> <?php echo "non-dealer"; ?></td>
                    <?php } ?>
                    <?php } ?>
                    <?php if ($req['typeCus'] == 1 && $req['dealer'] != '[]') { ?>
                    <td style="text-align:center;"> <?php echo $fs['customer_first_name'] . ' ' . $fs['customer_last_name']; ?></td>
                    <?php } ?>
                    <td style="text-align:right;"><?php echo $fs['order_totalamount']; ?></td>
                    <?php if ($req['status'] == '[]' || $req['status'] == '') { ?>
                    <td style="text-align:center;">
                        <?php if ($fs['is_cod'] == 1) { ?>
                            <?php if ($fs['order_status'] ==  0) {
                                echo "<b><font color=olive>Cod Pending</font></b>";
                            } elseif ($fs['order_status'] == 9) {
                                echo "<b><font color=blue>Cod Approved</font></b>";
                            } elseif ($fs['order_status'] == 3) {
                                echo "<b><font color=gray>Dispatched</font></b>";
                            } elseif ($fs['order_status'] == 4) {
                                echo "<b><font color=red>Cancelled</font></b>";
                            } elseif ($fs['order_status'] == 5) {
                                echo "<b><font color=green>Delivered</font></b>";
                            } elseif ($fs['order_status'] == 6 || $fs['order_status'] == 8) {
                                echo "<b><font color=orange>Cod Disapproved</font></b>";
                            } elseif ($fs['order_status'] == 7) {
                                echo "<b><font color=green>Delivered</font></b>";
                            } ?>
                        <?php } elseif ($fs['is_cod'] == 2) { ?>
                            <?php if ($fs['order_status'] ==  1) {
                                echo "<b><font color=olive>Online Pending</font></b>";
                            } elseif ($fs['order_status'] == 2) {
                                echo "<b><font color=blue>Online Approved</font></b>";
                            } elseif ($fs['order_status'] == 3) {
                                echo "<b><font color=gray>Dispatched</font></b>";
                            } elseif ($fs['order_status'] == 4) {
                                echo "<b><font color=red>Cancelled</font></b>";
                            } elseif ($fs['order_status'] == 5) {
                                echo "<b><font color=green>Delivered</font></b>";
                            } elseif ($fs['order_status'] == 6) {
                                echo "<b><font color=orange>Online Disapproved</font></b>";
                            } elseif ($fs['order_status'] == 7) {
                                echo "<b><font color=green>Delivered</font></b>";
                            } ?>
                        <?php } ?>
                    </td>
                    <?php } ?>

                </tr>
            </tbody>
        <?php } ?>
        <tr>
            <td></td>
            <td></td>
            <?php if ($req['typeCus'] == 2) { ?>
            <td style="text-align:center;width:10%;font-weight: bold;">Total</td>
            <td style="text-align:right;width:10%;font-weight: bold;"><?php echo number_format((float)$totalAmount_count,2,'.','') ?></td>
            <?php } ?>
            <?php if ($req['typeCus'] == 'all' || $req['typeCus'] == '') { ?>
            <td></td>
            <td style="text-align:center;width:10%;font-weight: bold;">Total</td>
            <td style="text-align:right;width:10%;font-weight: bold;"><?php echo number_format((float)$totalAmount_count,2,'.','') ?></td>
            <?php if ($req['status'] == '[]') { ?><td></td><?php } ?>
            <?php } ?>
            <?php if ($req['typeCus'] == 1 && $req['dealer'] != '[]') { ?>
            <td></td>
                <td style="text-align:center;width:10%;font-weight: bold;">Total</td>
            <td style="text-align:right;width:10%;font-weight: bold;"><?php echo number_format((float)$totalAmount_count,2,'.','') ?></td>
            <?php if ($req['status'] == '[]') { ?><td></td><?php } ?>
            <?php } ?>
        </tr>
    </table>
    </div>
</body>

</html>