<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8" />
      <meta http-equiv="X-UA-Compatible" content="IE=edge" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
      <title>Welcome</title>
   </head>
   <body style="background: #f5f5f5;">
      <style>
         .container_width {
         width: 40%;
         margin: auto;
         }
         @media screen and (max-width:768px) {
         .container_width {
         width: 100%;
         margin: auto;
         }
         .svg {
         width: 70%;
         }
         .img_cont {
         height: 80px !important;
         width: 80px !important;
         }
         .inner_cont {
         width: 90% !important;
         }
         }
      </style>
      <table class="container_width" style="background: #ffff;max-width: 600px;margin: auto;" width="100%" border="0" cellspacing="0" cellpadding="0">
         <tr>
            <td>
               <section >
                  <div style="
                     height: 60px;
                     width: 100%;
                     background-color: #18448f;
                     ">
                     <table style="width: 100px; margin: auto;">
                        <tr>
                           <td>
                              <div style="height: 84px; width: 84px; margin-top: 15px">
                              <a style="cursor: none; pointer-events: none;" href="#">
                                 <img style="height: 100%; width: 100%"
                                    src="{{ URL::to('/') }}/public/register_assets/logo.png" alt="" />
                              </a>
                              </div>
                           </td>
                        </tr>
                     </table>
                  </div>
                  <section class="inner_cont" style="padding: 20px;">
                     <div>
                     </div>
                     <div style="margin-top: 20px; margin-bottom: 15px; text-align: left;">
                        <p style="font-size: 20px; color: #000; font-weight: 600; font-family: 'Rubik', sans-serif;">Dear <span style="color: #18448F;"><?php echo $user['contact_person_name'] ?>,</span></p>
                        <p style="font-size: 14px;font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; line-height: 12px;">Thankyou for business with our PrintApp!</p>
                <a style="cursor: none; pointer-events: none;" href="#">
                        <img style="height: 100%; width: 24%; display: block; margin: 34px auto; object-fit: cover;" src="https://api.theprintapp.com/public/ordersuccess_assets/success.png" alt="">
                </a>
                        <div style="text-align: left;" >
                           <h1 style="font-size: 25px; font-weight: 700;color: green; font-family: 'Rubik', sans-serif; text-align: center; margin-bottom: 6px; margin-top: 20px;">Order Created</h1>
                           <p style="font-size: 18px; text-align: center !important; font-family: 'Rubik', sans-serif; font-weight: 500;color: #008405; line-height: 0px;">
                              <?php echo $user['order_item_count'] ?> items have been created successfully
                           </p>
                           <p style="font-size: 14px; margin-top: 30px; font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; line-height: 20px;">
                              We're excited to confirm that your order <b><?php echo $user['order_code'] ?></b> has been successfully created and is now being processed.</p>
                            <p style="font-size: 14px;font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; line-height: 20px;">  We will keep you updated on the progress of your order and notify you once it has been shipped.
                           </p>
                           
                           <p style="font-size: 14px;font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; line-height: 20px;">
                              We'll let you know when it's on the way.
                           </p>
                        </div>
                        <div style="background-color: #18448F; padding: 10px 25px ;">
                           <table style="width: 100%; border-collapse: collapse;">
                              <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.75);">
                                 <td style="padding: 10px 0px;"><span style="font-size: 20px; color: #ffff; font-weight: 700; font-family: 'Rubik', sans-serif;">Order ID:</span> </td>
                                 <td style="text-align: end; padding: 10px 0px;"><span style="font-size: 18px; color: #ffff; font-weight: 700; font-family: 'Rubik', sans-serif;"><b><?php echo $user['order_code'] ?></b></span> </td>
                              </tr>
                           </table>
                           <table style="width: 100%; border-collapse: collapse;">
                              <tr>
                                 <td style="padding: 10px 0px; width: 80%;"><span style="font-size: 20px; color: #ffff; font-weight: 700; font-family: 'Rubik', sans-serif; padding-bottom: 20px;">Ordered Items</span> </td>
                                 <td style="padding: 10px 0px;text-align: right;"><span style="font-size: 20px; color: #ffff; font-weight: 700; font-family: 'Rubik', sans-serif; padding-bottom: 20px; ">Amount</span> </td>
                              </tr>
                           </table>
                           <table style="width: 100%; border-collapse: collapse;">
                              <tr>
                              </tr>
                              @if(isset($user['product_details']) && is_array($user['product_details']))
                              @foreach ($user['product_details'] as $details)
                              <tr>
                                 <td style="padding: 10px 0px; width: 80%;"><span style="font-size: 18px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;">{{ $details['product_name'] }}</span> </td>
                                 <td style="text-align: end; padding: 10px 0px; font-size: 18px; color: #ffff; font-weight: 700; font-family: 'Rubik', sans-serif;">
                                    <span style="color: #00FF0A;">
                                    ₹
                                    </span>
                                    {{ $details['amount'] }}
                                 </td>
                              </tr>
                              @endforeach
                              @endif
                              <tr style="border-top: 1px solid rgba(255, 255, 255, 0.75);">
                                 <td style="padding: 10px 0px; width: 80%;">
                                    <span style="font-size: 18px; color: #ffff; font-weight:400; font-family: 'Rubik', sans-serif;">Total</span>
                                 </td>
                                 <td style="text-align: end; padding: 10px 0px; font-size: 18px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;">
                                    <span style="color: #00FF0A;">
                                    ₹
                                    </span>
                                    <?php echo $user['total_amount'] ?>
                                 </td>
                              </tr>
                              <tr>
                                <td style="padding: 10px 0px; width: 80%;">
                                   <span style="font-size: 18px; color: #ffff; font-weight:400; font-family: 'Rubik', sans-serif;">Delivery Charge</span>
                                </td>
                                <td style="text-align: end; padding: 10px 0px; font-size: 18px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;">
                                   <span style="color: #00FF0A;">
                                   ₹
                                   </span>
                                   <?php echo $user['shipping_cost'] ?>
                                </td>
                             </tr>

                             <tr>
                                <td style="padding: 10px 0px; width: 80%;">
                                   <span style="font-size: 18px; color: #ffff; font-weight:400; font-family: 'Rubik', sans-serif;">Round Off</span>
                                </td>
                                <td style="text-align: end; padding: 10px 0px; font-size: 18px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;">
                                   <span style="color: #00FF0A;">
                                   <?php echo $user['roundOffValueSymbol'] ?>
                                   </span>
                                   <?php echo $user['remaining_value'] ?>
                                </td>
                             </tr>

                             <tr>
                                <td style="padding: 10px 0px; width: 80%;">
                                   <span style="font-size: 20px; color: #ffff; font-weight:700; font-family: 'Rubik', sans-serif;">Grand Total</span>
                                </td>
                                <td style="text-align: end; padding: 10px 0px; font-size: 18px; color: #ffff; font-weight: 700; font-family: 'Rubik', sans-serif;">
                                   <span style="color: #00FF0A;">
                                   ₹
                                   </span>
                                   <?php echo $user['grand_total'] ?>
                                </td>
                             </tr>
                           </table>
                        </div>
                        <p style="font-size: 18px; text-align: center !important; margin: 30px 0; font-family: 'Rubik', sans-serif; font-weight: 500;color: #008405; line-height: 0px;">
                           Your Order Should be delivered in 7 days
                        </p>
                     </div>
                     </div>
                  </section>
                  <table style="height: 60px;
                     width: 100%;
                     background-color: #18448f;">
                     <tr>
                        <td style="text-align: center;">
                           <table style="
                              width: 10%; margin: auto;
                              ">
                              <tr>
                                 <td>
                                    <div style="height: 35px; width: 35px;     margin: 0px 3px;">
                                       <a href="https://www.facebook.com/printapp1"><img style="width: 100%; height: 100%;"
                                          src="{{ URL::to('/') }}/public/register_assets/facebook.png" alt=""></a>
                                    </div>
                                 </td>
                                 <td>
                                    <div style="height: 35px; width: 35px;     margin: 0px 3px;">
                                       <a href="https://www.youtube.com/channel/UCgP2ilffMitNsB3REOvDNwA"> <img
                                          style="width: 100%; height: 100%;"
                                          src="{{ URL::to('/') }}/public/register_assets/youtube.png" alt=""></a>
                                    </div>
                                 </td>
                                 <td>
                                    <div style="height: 35px; width: 35px;     margin: 0px 3px;">
                                       <a href="https://www.instagram.com/theprintapp2021/"> <img
                                          style="width: 100%; height: 100%;"
                                          src="{{ URL::to('/') }}/public/register_assets/insta.png" alt=""></a>
                                    </div>
                                 </td>
                              </tr>
                           </table>
                        </td>
                     </tr>
                  </table>
               </section>
            </td>
         </tr>
      </table>
   </body>
</html>