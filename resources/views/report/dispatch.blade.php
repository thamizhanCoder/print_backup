<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400&display=swap" rel="stylesheet">
    <title>Document</title>

</head>

<body>
    <style>
        body{
            font-family: 'Roboto', sans-serif;
        }
        table,
        tr,
        td,
        th {
            border: 1px solid #000;
            border-collapse: collapse;
            white-space: normal;
        }
.billing-address,.billing-address tr,.billing-address td{
    border: 0px solid #000;
            border-collapse: collapse;
            white-space: normal;
}
        table td {
            font-size: 16px;
            font-weight: 400;
            padding: 8px;
        }

        h6 {
            font-size: 16px !important;
            margin-bottom: 0px !important;
            margin-top: 0px !important;
        }

        table th {
            font-size: 16px;
            padding: 8px;
        }

        .logo {
            height: 90px;
            width: 90px;
        }

        .logo p {
            margin: 0;
            text-align: center;
            font-size: 18px;
            font-weight: 600;
        }

        .logo img {
            height: 100%;
            width: 100%;
            object-fit: cover;
        }

        ul {
            margin: 2px 20px;
            padding: 5px 10px;
        }

        ul li {
            text-decoration: none;
            list-style: none;
            font-size: 18px;
            padding: 5px;
            font-weight: 400;
        }

        .name {
            font-weight: 600;
        }

        .overwrite {
            text-align: end;
        }

        .table-division {
            margin-top: 20px;
        }

        .header_table table td {
            border: none;
        }
    </style>
    <div class="print_view">
        <table style="width: 100%; border: none !important;">
            <tr style="border: none !important;">
                <td style="border: none !important;"> <b>Order ID : <span>
                            <?php echo $order_code ?>
                        </span> </b> </td>
                <td style="width: 60%; border: none !important;"></td>
                <td style="text-align: end; border: none !important;"> <b> Order Date: <span>
                                <?php echo $order_date ?>
                            </span> </b> </td>
            </tr>
        </table>
        <div class="header_table">
            <table style="width: 100%;">
                <tr>
                    <td colspan="30" style="vertical-align: top; width: 9%;padding-top: 20px">
                        <div class="logo">
                            <p style="padding-left: 10px;"> <b>Sold by</b> </p>
                            <br>
                <a style="cursor: none; pointer-events: none;" href="#">
                            <img src="https://api.theprintapp.com/public/register_assets/logo.png" alt="" style="width: 90px;height: 90px;">
                </a>
                        </div>
                    </td>
                    <td style="border-right: 0.8px solid #000;width: 41%;">
                        <table style="width: 100%; border: 0px;" class="billing-address">
                            <tr>
                                <td>
                                    <h6 style=" list-style: none; padding: 5px; font-weight: 600;font-size: 24px !important;">
                                        <?php echo $company_name ?>
                                    </h6>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h6 style=" list-style: none;  padding: 5px; font-weight: 400;">
                                        <?php echo $company_mobile_no ?>
                                    </h6>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h6 style=" list-style: none; padding: 5px; font-weight: 400;">
                                        <?php echo $company_address ?>.
            
                                    </h6> 
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td style="vertical-align: baseline; border: none;width: 50%;">
                        <table class="billing-address">
                            <tr>
                                <td>
                                    <h5 style="font-size: 20px;margin-top: 0px;"><b>To</b></h5>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h6 class="name" style=" list-style: none !important;  padding: 5px;font-size:24px !important;font-weight: 600;">
                                        <b><?php echo !empty($customer_last_name) ? $customer_first_name . ' ' . $customer_last_name : $customer_first_name ?? "-"?></b>
                                    </h6>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h6 style=" list-style: none;  padding: 5px; font-weight: 400;">
                                        <span><?php echo $customer_mobile ?? "-" ?></span>,
                                        <?php if (!empty($customer_alt_mobile_number)) { ?>
                                            <span><?php echo $customer_alt_mobile_number ?? "-" ?>,</span>
                                        <?php } ?>
                                    </h6>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h6 style=" list-style: none; padding: 5px; font-weight: 400;">
                                        <?php echo $customer_email.',' ?? "-"?>
                                    </h6>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                <h6 style=" list-style: none; padding: 5px; font-weight: 400;">
                                    <span><?php echo $customer_address ?? "-" ?>,</span>
                                    <?php if (!empty($customer_address_2)) { ?>
                                    <span><?php echo $customer_address_2 ?? "-"?>,</span>
                                    <?php } ?>
                                    <?php if (!empty($customer_landmark)) { ?>
                                    <span><?php echo $customer_landmark ?? "-" ?>,</span>
                                    <?php } ?>
                                    <span><?php echo $customer_district . ', ' . $customer_state.' - ' . $customer_pincode ?? "-"?>.</span>
                                     
                                 </h6>
                                    </td>
                            </tr>
                            <tr>
                                <td>
                                    <?php if (!empty($customer_gst_no)) { ?>
                                        <h6 style=" list-style: none; padding: 5px; font-weight: 400;">
                                            GST: <?php echo $customer_gst_no ?? "-" ?>
                                           <?php } ?>
                                        </h6>
                                </td>
                            </tr>
                        </table>
                       
                    </td>
                </tr>
            </table>
        </div>
        <table style="width: 100%; border: none !important;margin-top: 20px;">
            <tr style="border: none !important;">
                <td style="border: none !important;"> <b>Invoice Date : <span>
                            <?php echo $final_invoice_date ?>
                        </span></b> </td>
                <td style="width: 55%; border: none !important;"></td>
                <td style="text-align: right; border: none !important;"> <b> No.of.Items: <span>
                            <?php echo $count ?>
                        </span> </b></td>
            </tr>
        </table>
        <div class="table-division">
            <table style="width:100%; border:1px solid #000;">
                <tr>
                    <th rowspan="2">Product ID</th>
                    <th rowspan="2">Product Name</th>
                    <th rowspan="2">Gross Amount (₹)</th>
                    <th rowspan="2">Qty</th>
                    <th rowspan="2">Discount (₹)</th>
                    <th rowspan="2">Taxable Amount (₹)</th>
                    <th colspan="2">CGST</th>
                    <th colspan="2">SGST</th>
                    <th colspan="2">IGST</th>
                    <th rowspan="2">Net Amount (₹)</th>
                </tr>
                <tr>
                    <th class="read">%</th>
                    <th>Amount (₹)</th>
                    <th class="read">%</th>
                    <th>Amount (₹)</th>
                    <th class="read">%</th>
                    <th>Amount (₹)</th>
                </tr>
                <?php
                  foreach ($final as $dispatch) {
                  ?>
                <tr>
                    <td style="text-align:start ;">
                        <?php echo $dispatch['product_id']; ?>
                    </td>
                    <td style="text-align:start ;">
                        <?php echo $dispatch['product_name']; ?>
                    </td>
                    <td class="overwrite" style="text-align: right;">
                        <?php echo $dispatch['gross_amount']; ?>
                    </td>
                    <td style="text-align: center;">
                        <?php echo $dispatch['quantity']; ?>
                    </td>
                    <td class="overwrite" style="text-align: right;">
                        <?php echo $dispatch['discount']; ?>
                    </td>
                    <td class="overwrite" style="text-align: right;">
                        <?php echo $dispatch['taxable_amount']; ?>
                    </td>
                    <td style="text-align: center;">
                        <?php echo $dispatch['cgst_percent']; ?>
                    </td>
                    <td class="overwrite" style="text-align: right;">
                        <?php echo $dispatch['cgst_amount']; ?>
                    </td>
                    <td style="text-align: center;">
                        <?php echo $dispatch['sgst_percent']; ?>
                    </td>
                    <td class="overwrite" style="text-align: right;">
                        <?php echo $dispatch['sgst_amount']; ?>
                    </td>
                    <td style="text-align: center;">
                        <?php echo $dispatch['igst_percent']; ?>
                    </td>
                    <td class="overwrite" style="text-align: right;">
                        <?php echo $dispatch['igst_amount']; ?>
                    </td>
                    <td class="overwrite" style="text-align: right;">
                        <?php echo $dispatch['net_amount']; ?>
                    </td>
                </tr>
                <?php } ?>
                <tr>
                    <td colspan="12" class="overwrite" style="text-align: right;"> <b>Sub Total (₹)</b> </td>
                    <td class="overwrite" style="text-align: right;">
                        <?php echo number_format((float)$sum, 2, '.', '') ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="12" class="overwrite" style="text-align: right;"> <b>Delivery (₹)</b> </td>
                    <td class="overwrite" style="text-align: right;">
                        <?php echo number_format((float)$deliveryChargeAmount, 2, '.', '') ?? "0.00" ?>
                    </td>
                </tr>
                <?php if (!empty($coupon_amount)) { ?>
                    <tr>
                    <td colspan="12" class="overwrite" style="text-align: right;"> <b>Coupon Amount (₹)</b> </td>
                    <td class="overwrite" style="text-align: right;">
                        <?php echo number_format((float)$coupon_amount, 2, '.', '') ?? "0.00" ?>
                    </td>
                </tr>
                <?php } ?>

                <?php if (!empty($remaining_value)) { ?>
                    <tr>
                    <td colspan="12" class="overwrite" style="text-align: right;"> <b>Round Off (<?php echo $roundOffValueSymbol?>)</b> </td>
                    <td class="overwrite" style="text-align: right;">
                        <?php echo number_format((float)$remaining_value, 2, '.', '') ?? "0.00" ?>
                    </td>
                </tr>
                <?php } ?>

                <tr>
                    <td colspan="12" class="overwrite" style="font-size: 20px;text-align: right;"> <b> Total Amount (₹)</b></td>
                    <td class="overwrite" style="font-size: 20px;text-align: right;">
                        <b><?php echo number_format((float)$total_amount, 2, '.', '') ?></b>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>