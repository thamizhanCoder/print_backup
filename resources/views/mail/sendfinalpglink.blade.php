<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8" />
      <meta http-equiv="X-UA-Compatible" content="IE=edge" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700;800;900&display=swap"
         rel="stylesheet" />
      <title>Welcome</title>

      <style>
      .im {
         color: #000 !important;
      }
   </style>
   </head>
   <body style="background: #f5f5f5;">
      <table class="container_width" style="background: #ffff;max-width: 600px;margin: auto;" width="100%" border="0" cellspacing="0" cellpadding="0">
         <tr>
            <td>
               <section>
                  <div style="height: 60px; width: 100%;background-color: #18448f;">
                     <table style="width: 84px; margin: auto;">
                        <tr>
                           <td>
                              <div style="height: 84px; width: 84px; margin-top: 15px">
                              <a style="cursor: none; pointer-events: none;" href="#">
                                 <img style="height: 100%; width: 100%"
                                    src="https://api.theprintapp.com/public/register_assets/logo.png" alt="" />
                              </a>
                              </div>
                           </td>
                        </tr>
                     </table>
                  </div>
                  <section class="inner_cont" style="padding: 20px;">
                     <p
                        style="font-size: 20px; color: #000; font-weight: 600; font-family: 'Rubik', sans-serif;margin-bottom: 10px;">
                        Dear <span style="color: #18448F !important;">
                        <?php echo $user['contact_person_name'] ?>,
                        </span>
                     </p>
                     <p style="font-size: 14px;font-family: 'Rubik', sans-serif;margin-top:0px;">Greetings from Print App!</p>
                     <div style="margin-top: 20px; margin-bottom: 15px;">
                        <div style="text-align: center;">
                <a style="cursor: none; pointer-events: none;" href="#">
                           <img style="width: 100px;"
                              src="https://api.theprintapp.com/public/ordersuccess_assets/success.png"  alt="">
                </a>
                           <h3 style="font-size: 18px;font-weight: 600;color: green;font-family: 'Rubik', sans-serif;">Payment Link for Your Order Payment</h3>
                        </div>
                        <div>
                           <p style="font-size: 14px;font-family: 'Rubik', sans-serif;margin-top:0px;line-height: 20px;
                              text-align: justify;">
                              Your order <b>(<?php echo $user['order_id'] ?>)</b> is ready to be delivered.  To confirm the delivery of your order, please make the remaining amount <b>(₹<?php echo $user['initial_paid_amount'] ?>)</b> of the order amount.
                          </p>
                           <p
                              style="font-size: 20px; color: #000; font-weight: 600; font-family: 'Rubik', sans-serif;">
                              Order Details <span style="color: #18448F;"></span>
                           </p>
                           <div style="background-color: #18448f;padding: 10px;">
                              <table style="width: 100%; border-collapse: collapse;">
                                 <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.75);">
                                    <td style="padding: 10px 0px;"><span style="font-size: 20px; color: #ffff; font-weight: 700; font-family: 'Rubik', sans-serif;">Order ID:</span> </td>
                                    <td style="text-align: end; padding: 10px 0px;"><span style="font-size: 18px; color: #ffff; font-weight: 700; font-family: 'Rubik', sans-serif;"><?php echo $user['order_id'] ?></span> </td>
                                 </tr>
                              </table>
                              <table style="width: 100%; border-collapse: collapse;" class="order-summary">
                                 <tr>
                                    <th style="padding: 10px 10px 10px 0px;text-align: left;"><span style="font-size: 15px; color: #ffff; font-weight: 500; font-family: 'Rubik', sans-serif; padding-bottom: 20px;">Product Name</span> </th>
                                    <th style="padding: 10px 10px 10px 0px;text-align: center;"><span style="font-size: 15px;text-align: center; color: #ffff; font-weight: 500; font-family: 'Rubik', sans-serif; padding-bottom: 20px;">Qty</span> </th>
                                    <th style="padding: 10px 10px 10px 10px;text-align: right;white-space: nowrap;"><span style="font-size: 15px; color: #ffff; font-weight: 500; font-family: 'Rubik', sans-serif; padding-bottom: 20px;">Rate per Qty(₹)</span> </th>
                                    <th style="padding: 10px 10px 10px 10px;text-align: right;white-space: nowrap;"><span style="font-size: 15px; color: #ffff; font-weight: 500; font-family: 'Rubik', sans-serif; padding-bottom: 20px;">Discount(₹)</span> </th>
                                    <th style="padding: 10px 0px 10px 0px;text-align: right;"><span style="font-size: 15px; color: #ffff; font-weight: 500; font-family: 'Rubik', sans-serif; padding-bottom: 20px;">Amount(₹)</span> </th>
                                 </tr>
                                 @if(isset($user['product_details']) && is_array($user['product_details']))
                                 @foreach ($user['product_details'] as $details)
                                 <tr>
                     
                                    <td style="padding-bottom: 10px;padding-right:10px;"><span style="font-size: 14px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;"> {{ $details['product_name'] }}</span> </td>
                                    
                                    <td style="padding-bottom: 10px;padding-right:10px;text-align: center;"><span style="font-size: 14px;text-align: center; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;"> {{ $details['quantity'] }}</span> </td>
                                    <td style="padding-bottom: 10px;padding-right:10px;text-align: right;"><span style="font-size: 14px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;"> {{ $details['unit_price'] }}</span> </td>
                                    <td style="padding-bottom: 10px;padding-right:10px;text-align: right;"><span style="font-size: 14px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;"> {{ $details['discount'] }}</span> </td>
                                    <td style="padding-bottom: 10px;text-align: right;"><span style="font-size: 14px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;"> {{ $details['quote_amount'] }}</span> </td>
                                 </tr>
                                 @endforeach
                                  @endif
                                 <tr style="border-top: 1px solid rgba(255, 255, 255, 0.75);">
                                    <td style="padding: 5px 0px; width: 80%;" colspan="4"><span style="font-size: 14px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;"> Sub Total</span> </td>
                                    <td style="padding: 5px 0px; width: 80%;text-align: right;"><span style="font-size: 14px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;"> <?php echo $user['sub_total'] ?></span> </td>
                                 </tr>
                                 <tr>
                                    <td style="padding: 5px 0px; width: 80%;" colspan="4"><span style="font-size: 14px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;"> Delivery Charge</span> </td>
                                    <td style="padding: 5px 0px; width: 80%;text-align: right;"><span style="font-size: 14px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;">                          <?php echo $user['shipping_cost'] ?></span> </td>
                                 </tr>
                                 <tr>
                                    <td style="padding: 5px 0px; width: 80%;" colspan="4"><span style="font-size: 14px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;"> Round Off</span> </td>
                                    <td style="padding: 5px 0px; width: 80%;text-align: right;"><span style="font-size: 14px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;">                          <?php echo $user['remaining_value'] ?></span> </td>
                                 </tr>
                                 <tr>
                                    <td style="padding: 5px 0px; width: 80%;" colspan="4"><span style="font-size: 16px; color: #ffff; font-weight: 700; font-family: 'Rubik', sans-serif;"> Order Amount</span> </td>
                                    <td style="padding: 5px 0px; width: 80%;text-align: right;"><span style="font-size: 16px; color: #ffff; font-weight: 700; font-family: 'Rubik', sans-serif;"> ₹<?php echo $user['order_amount'] ?></span> </td>
                                 </tr>
                              </table>
                           </div>
                           <p
                           style="font-size: 20px; color: #000; font-weight: 600; font-family: 'Rubik', sans-serif;">
                           Previous Bill History <span style="color: #18448F;"></span>
                        </p>
                        <div style="background-color: #18448f;padding: 10px;">
                              <table style="width: 100%; border-collapse: collapse;">
                                 <tr>
                                    <th style="padding: 10px 10px 10px 0px;text-align: left;"><span style="font-size: 15px; color: #ffff; font-weight: 500; font-family: 'Rubik', sans-serif; padding-bottom: 20px;">Date</span> </th>
                                    <th style="padding: 10px 10px 10px 0px;text-align: left;"><span style="font-size: 15px; color: #ffff; font-weight: 500; font-family: 'Rubik', sans-serif; padding-bottom: 20px;">Transaction ID</span> </th>
                                    <th style="padding: 10px 0px 10px 0px;text-align: right;"><span style="font-size: 15px; color: #ffff; font-weight: 500; font-family: 'Rubik', sans-serif; padding-bottom: 20px;">Amount(₹)</span> </th>
                                 </tr>
                                 <tr style="border-top: 1px solid rgba(255, 255, 255, 0.75);">
                                    <td style="padding: 5px 0px;"><span style="font-size: 14px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;"><?php echo $user['initial_transaction_date'] ?></span> </td>
                                    <td style="padding: 5px 0px;"><span style="font-size: 14px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;"><?php echo $user['initial_transaction_id'] ?></span> </td>
                                    <td style="padding: 5px 0px;text-align: right;"><span style="font-size: 14px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;"><?php echo $user['initial_paid_amount'] ?></span> </td>
                                 </tr>
                                
                              </table>
                           </div>
                           <table style="width: 75%;margin: auto;background: #18448f1f;padding: 10px;margin-top: 20px;margin-bottom: 20px;line-height: 28px;">
                             
                              <tr>
                                 <td style="font-size: 18px;font-family:'Rubik', sans-serif;color: grey;"><b>Expiry Date</b></td>
                                 <td>:</td>
                                 <td style="font-size: 18px;font-family:'Rubik', sans-serif;color: black;white-space: nowrap;"><b><?php echo $user['due_by'] ?></b></td>
                              </tr>
                           </table>
                           <p style="font-family:'Rubik', sans-serif;    font-size: 14px;
                              line-height: 20px;">
                              Find the link below to complete your payment,
                           </p>
                           <div style="text-align: center;">
                              <button style="height: 45px; width: 226px; background-color: #18448F; border-radius: 0px;
                                 border: none; cursor: pointer;
                                 ">
                              <a style="font-size: 20px; font-weight: 700; font-family: 'Rubik', sans-serif; color: #ffff; text-decoration: none;"
                                 href="<?php echo $user['short_url'] ?>">Paynow</a>
                              </button>
                           </div>
                           <p style="font-family:'Rubik', sans-serif;font-size: 14px;
                              line-height: 20px;">
                              The payment link will expire on the mentioned due date. Once your payment is successful, your order will be confirmed, and we will proceed with the next steps to fulfill your order. For any questions or assistance, please contact us through email at <a style="text-decoration: none; color: #18448F;"
                                    href="mailto:printapp2021@gmail.com ">
                                 printapp2021@gmail.com
                                 </a> or call us at <a href="tel:9003923500">9003923500 </a>.
                           </p>
                           <p style="font-family:'Rubik', sans-serif;font-size: 14px;
                              line-height: 20px;">We appreciate your trust in our services and look forward to fulfilling your requirements promptly.</p>
                           
                           
                        </div>
                     </div>
                  </section>
                  <div style="
                     height: 60px;
                     width: 100%;
                     background-color: #18448f;
                     padding-top: 13px;
                     ">
                     <table style="margin:auto;">
                        <tr>
                           <td>
                              <div style="height: 35px; width: 35px;margin: 0px 3px;">
                                 <a href="https://www.facebook.com/printapp1"><img style="width: 100%; height: 100%;"
                                    src="https://api.theprintapp.com/public/register_assets/facebook.png" alt=""></a>
                              </div>
                           </td>
                           <td>
                              <div style="height: 35px; width: 35px;margin: 0px 3px;">
                                 <a href="https://www.youtube.com/channel/UCgP2ilffMitNsB3REOvDNwA"> <img
                                    style="width: 100%; height: 100%;"
                                    src="https://api.theprintapp.com/public/register_assets/youtube.png" alt=""></a>
                              </div>
                           </td>
                           <td>
                              <div style="height: 35px; width: 35px;margin: 0px 3px;">
                                 <a href="https://www.instagram.com/theprintapp2021/"> <img
                                    style="width: 100%; height: 100%;"
                                    src="https://api.theprintapp.com/public/register_assets/insta.png" alt=""></a>
                              </div>
                           </td>
                        </tr>
                     </table>
                    
                     
                    
                  </div>
               </section>
            </td>
         </tr>
      </table>
   </body>
</html>