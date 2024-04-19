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
                <h2>Stock Report</h2>
            </td>

        </tr>
    </table>

    <div style="border-top:1px solid #ccc;"></div>
    <table border="1" class="table-form">
        <thead>
            <tr>
                
                <th style="text-align:center;width:8%;font-weight: bold;">Product ID</th>
                <th style="text-align:center;width: 10%;font-weight: bold;">Service Type</th>
                <th style="text-align:center;width: 10%;font-weight: bold;">Product Name</th>
                <th style="text-align:center;width: 10%;font-weight: bold;">Variant Details</th>
                <th style="text-align:center;width: 12%;font-weight: bold;">MRP(₹)</th>
                <th style="text-align:center;width: 12%;font-weight: bold;">Selling Price(₹)</th>
                <th style="text-align:center;width: 7%;font-weight: bold;">Quantity</th>
                <th style="text-align:center;width: 8%;font-weight: bold;">Type Of Stock</th>
            </tr>
        </thead>
        <?php
        foreach ($overll as $fs) {
        ?>
            <tbody>
                <tr>
                   
                    <td style="text-align:center;text-transform:uppercase;"><?php echo $fs['product_code']; ?></td>
                    <td style="text-align:center;"><?php echo $fs['service_name']; ?></td>
                    <td style="text-align:center;"> <?php echo $fs['product_name']; ?></td>
                    <td style="text-align:center;"> <?php echo $fs['variant_details']; ?></td>
                    <td style="text-align:center;"> <?php echo $fs['mrp']; ?></td>
                    <td style="text-align:center;"> <?php echo $fs['selling_price']; ?></td>
                    <td style="text-align:center;"><?php echo $fs['quantity']; ?></td>
                    <td style="text-align:center;"><?php if ($fs['quantity'] >  0) {
                                                        echo "<b><font color=green>In stock</font></b>";
                                                    } elseif ($fs['quantity'] == 0) {
                                                        echo "<b><font color=red>Out of stock</font></b>";
                                                    } ?></td>,
                </tr>
            </tbody>
        <?php } ?>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td style="text-align:center;width:10%;font-weight: bold;">Total</td>
            <td style="text-align:right;width:10%;font-weight: bold;"><?php echo number_format((float)$totalmrp,2,'.','') ?></td>
            <td style="text-align:right;width:10%;font-weight: bold;"><?php echo number_format((float)$totalsellingprice,2,'.','') ?></td>
            <?php if ($req['filterByStatus'] == '') { ?><td></td><?php } ?>
        </tr>
    </table>
    </div>
</body>
</html>