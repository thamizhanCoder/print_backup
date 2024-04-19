<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>View Quote</title>
   <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400&display=swap" rel="stylesheet">
   <style>
      @media print {
         .terms-conditions-sec {
            page-break-before: always;
         }

         body {
            font-family: 'Roboto', sans-serif;
         }
      }

      body {
         font-family: 'Roboto', sans-serif;
      }

      .text-left {
         text-align: left !important;
      }

      .text-center {
         text-align: center !important;
      }

      .text-right {
         text-align: right !important;
      }

      .varients {
         display: flex;
         align-items: center;
         margin-top: 8px;
      }

      .varients p {
         font-size: 14px;
         color: #494545e0;
         margin-bottom: 0px;
         margin-top: 6px;
      }

      .varients .dot {
         width: 5px;
         height: 5px;
         background-color: #D9D9D9;
         border-radius: 30px;
         margin: 0 10px;
         position: relative;
         top: 3px;
      }

      .table-wrapper b {
         font-weight: 500 !important;
      }

      .bb-0 {
         border-bottom: unset !important;
      }

      .bt-0 {
         border-top: unset !important;
      }

      .total-row td {
         padding: 16px 10px;
      }

      .total-row td b {
         font-size: 18px;
         font-weight: 500;
      }

      .table-wrapper {
         width: 100%;
         border-collapse: collapse;
      }

      .table-wrapper th {
         text-align: center;
         background-color: #F8F8F8;
         font-size: 15px;
         font-weight: 500;
         border: 1px solid #ddd;
         padding: 8px;
         white-space: nowrap;
      }

      .table-wrapper th span {
         font-size: 13px;
         color: #666;
         font-weight: 500;
      }

      .table-wrapper td {
         background-color: #fff;
         vertical-align: middle;
         border: 1px solid #ddd;
         padding: 8px;
         white-space: nowrap;
      }

      .link-clr {
         color: #0000a6;
      }

      .to-address .value {
         margin-top: 5px;
         margin-bottom: 5px;
         font-size: 15px;
      }

      .terms-conditions-sec {
         margin-top: 30px;
      }

      .terms-conditions-sec h3 {
         color: #666;
         font-size: 18px;
         font-weight: 500;
      }

      .terms-conditions-sec h4 {
         color: #494545;
         font-size: 17px;
         font-weight: 600;
         margin-bottom: 5px;
      }

      .terms-conditions-sec p {
         font-size: 16px;
         color: #666;
         padding-bottom: 10px;
      }
   </style>
</head>

<body>
   <div style="border-bottom: 1px solid #D9D9D9;">
      <table border="0" style="width: 100%;">
         <tr>
            <td>
               <h2 style="font-size: 18px;"><?php echo $company_details->name ?? "-" ?></h2>
               <p style="margin-top: 5px;"><?php echo $company_details->address ?? "-" ?></p>
               <p style="margin-top: 5px;"><?php echo $company_details->mobile_no ?? "-" ?></p>
            </td>
            <td style="text-align:right;"><img src="{{ env('WEBSITEURL') }}assets/images/logo1.png" alt="" style="width: 72px;height: 72px;" />
            </td>
         </tr>
      </table>
   </div>
   <div style="margin-top: 10px;margin-bottom:10px;">
      <table border="0" style="width: 100%;">
         <tr>
            <td>
               <p style="font-size: 16px; font-weight: 600;">Date: <span style="font-weight: 500;"><b><?php echo $quoteDate ?? "-" ?></b></span></p>
            </td>
            <td style="text-align:right;">
               <p style="font-size: 16px; font-weight: 600;">Quote : <span style="font-weight: 500;"><b>#<?php echo $quote_code ?? "-" ?></b></span></p>
            </td>
         </tr>
      </table>
   </div>
   <div style="border-top: 1px solid #D9D9D9;">
      <h2 style="font-size: 18px;text-decoration: underline;margin-bottom: 10px;">To</h2>
      <table class="to-address">
         <tr>
            <td>
               <p class="value" style="font-size: 18px;font-weight: 600;"><b><?php echo $billing_customer_first_name ?? "-" ?></b>,</p>
            </td>
         </tr>


         <tr>
            <td>
               <p class="value">
                  <span><?php echo $billing_mobile_number.', ' ?></span>
                  <?php if (!empty($billing_alt_mobile_number)) { ?>
                     <span> 
                     <?php echo $billing_alt_mobile_number.',' ?? "-" ?></span>
                  <?php } ?>

               </p>
            </td>
         </tr>

         <tr>
            <td>
               <p class="value">
                  <span><?php echo $billing_email.',' ?? "-" ?></span>
               </p>
            </td>
         </tr>

         <tr>
            <td>
               <p class="value">
                  <span><?php echo $billing_address_1 . ', ' . $billing_address_2.',' ?? "-" ?></span> 
                  <?php if (!empty($billing_landmark)) { ?>
                     <span><?php echo $billing_landmark.',' ?? "-" ?></span>
                  <?php } ?>
                  <span><?php echo $billing_city_name.',' ?? "-" ?></span>
                  <span><?php echo $billing_state_name ?? "-" ?></span>
                  <span> - <?php echo $billing_pincode.'.' ?? "-" ?></span>
               </p>
            </td>
         </tr>

         <tr>
            <td>
               <p class="value"><span><b>GST</b> :</span> <span><?php echo $billing_gst_no ?? "-" ?></span></p>
            </td>
         </tr>
      </table>
   </div>
   <div style="margin-top: 30px;">
      <table class="table-wrapper">
         <thead>
            <tr>
               <th rowspan="2">S.No</th>
               <th rowspan="2">Product</th>
               <th rowspan="2">Rate (₹)<br> <span>Per Qty</span></th>
               <th rowspan="2">Qty</th>
               <th colspan="2">Discount <span>Per Qty</span></th>
               <th rowspan="2">Taxable Amt (₹)<br></th>
               <th colspan="2">CGST</th>
               <th colspan="2">SGST</th>
               <th colspan="2">IGST</th>
               <th rowspan="2">Total Payable <br> Amt (₹)</th>
            </tr>
            <tr>
               <th>%</th>
               <th>₹</th>
               <th>%</th>
               <th>₹</th>
               <th>%</th>
               <th>₹</th>
               <th>%</th>
               <th>₹</th>
            </tr>
         </thead>
         <tbody>
            <?php
            foreach ($quoteOrderDetails as $detail) {
            ?>
               <tr>
                  <td class="text-center"><?php echo $detail['serial_number']; ?></td>
                  <td class="text-left">
                     <b><?php echo $detail['product_name']; ?></b>
                     <div class="varients mt-0">
                        <p><?php echo $detail['variant_attributes']; ?></p>
                        <div class="dot"></div>
                     </div>
                  </td>
                  <td class="text-right"><?php echo $detail['rate']; ?></td>
                  <td class="text-center"><?php echo $detail['quantity']; ?></td>
                  <td class="text-center"><?php echo $detail['discount_percentage']; ?></td>
                  <td class="text-right"><?php echo $detail['discount_amount']; ?></td>
                  <td class="text-right"><?php echo $detail['taxable_amount']; ?></td>
                  <td class="text-center"><?php echo $detail['cgst_percent']; ?></td>
                  <td class="text-right"><?php echo $detail['cgst_amount']; ?></td>
                  <td class="text-center"><?php echo $detail['sgst_percent']; ?></td>
                  <td class="text-right"><?php echo $detail['sgst_amount']; ?></td>
                  <td class="text-center"><?php echo $detail['igst_percent']; ?></td>
                  <td class="text-right"><?php echo $detail['igst_amount']; ?></td>
                  <td class="text-right"><?php echo $detail['amount']; ?></td>
               </tr>
            <?php } ?>
            <tr class="total-row">
               <td class="text-right bb-0" colspan="13"><b>Sub Total (₹)</b></td>
               <td class="text-right bb-0"><b><?php echo $sub_total; ?></b></td>
            </tr>
            <tr class="total-row">
               <td class="text-right bb-0 bt-0" colspan="13"><b>Delivery (₹)</b></td>
               <td class="text-right bb-0 bt-0"><b><?php echo $delivery_charge; ?></b></td>
            </tr>
            <tr class="total-row">
               <td class="text-right bb-0 bt-0" colspan="13"><b>Round Off (<?php echo $roundOffValueSymbol; ?>)</b></td>
               <td class="text-right bb-0 bt-0"><b><?php echo $remaining_value; ?></b></td>
            </tr>
            <tr class="total-row">
               <td class="text-right bt-0 link-clr" colspan="13"><b>Grand Total (₹)</b></td>
               <td class="text-right bt-0 link-clr"><b><?php echo $grand_total; ?></b></td>
            </tr>
         </tbody>
      </table>
   </div>
   <div class="terms-conditions-sec">
      <h3>TERMS & CONDITIONS</h3>
      <?php foreach ($termsAndCOnditionDetails as $termsDetail) {
               ?>
      <table style="border: 0px;line-height: 28px;margin-bottom: 20px;">
            <tr>
               <td style="color:#494545d1; font-size:18px"><b><?php echo $termsDetail['service_name']; ?></b> : </td>
            </tr>
            <tr>
              <td><?php echo $termsDetail['terms_and_conditions']; ?></td>
            </tr>
         </table>
         <?php } ?>
   </div>
</body>

</html>