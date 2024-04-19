<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8" />
      <meta http-equiv="X-UA-Compatible" content="IE=edge" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700;800;900&display=swap"
         rel="stylesheet" />
      <title>Order Success</title>
   </head>
   <body>
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
            <td align="center">
               <section>
                  <div style="
                     height: 60px;
                     width: 100%;
                     background-color: #18448f;
                     display: flex;
                     align-items: baseline;
                     justify-content: center;
                     ">
                     <table style="width: 100px; margin: auto;">
                        <tr>
                           <td>
                              <div style="height: 84px; width: 84px; margin-top: 15px">
                                 <a href="#" style="cursor: none; pointer-events: none;">
                                 <img style="height: 100%; width: 100%"
                                    src="{{ URL::to('/') }}/public/register_assets/logo.png" alt="" />
                                 </a>
                              </div>
                           </td>
                        </tr>
                     </table>
                  </div>
                  <section style="width: 80%; margin: auto; margin-top: 55px; margin-bottom: 15px;">
                     <div>
                        <div style="text-align: left;">
                           <p
                              style="font-size:20px; font-weight: 600; font-family: 'Rubik', sans-serif; color: #000;">
                              Dear <span style="color: #18448f;">
                              <?php echo $user['customer_name'] ?>,
                              </span>
                           </p>
                           <p
                              style="font-size: 18px; font-weight: 400; font-family: 'Rubik', sans-serif; color: #000;">
                              Thanks for shopping with Print App!
                           </p>
                        </div>
                        <div style="text-align: center;">
                           <div style="width: 178px; height: 178px; margin: auto; margin-top: 25px;">
                              <a href="#" style="cursor: none; pointer-events: none;">
                              <img style="height: 100%; width: 100%;"
                                 src="{{ URL::to('/') }}/public/ordersuccess_assets/success.png" alt="">
                              </a>
                           </div>
                           <h1 style="font-size: 35px; font-weight: 700; font-family: 'Rubik', sans-serif; color: #4CB050; 
                              margin-top: 20px; margin-bottom: 13px;
                              ">Order Success</h1>
                           <p style="font-size: 18px; font-family: 'Rubik', sans-serif; color: #008805; font-weight: 400;
                              margin-top: 2px;
                              ">
                              <?php echo $user['items_count'] ?> Items have been placed successfully.
                           </p>
                        </div>
                        <div style="text-align: left;">
                           <p
                              style="font-size: 18px; font-weight: 400; font-family: 'Rubik', sans-serif; color: #000; margin-top: 25px; line-height: 25px;">
                              We’re happy to let you know that we’ve received your order. Your order id is "<b><?php echo $user['order_code'] ?></b>", We are reviewing your order now.
                           </p>
                           <p style="font-size: 18px; font-weight: 400; font-family: 'Rubik', sans-serif; color: #000; margin-top: 25px;
                              margin-bottom: 25px; ">
                              We’ll let you know when it’s on the way.
                           </p>
                        </div>
                        <div style="background-color: #18448f; padding: 10px 25px ;">
                           <table style="width: 100%; border-collapse: collapse;">
                              <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.75);">
                                 <td style="padding: 10px 0px;"><span
                                    style="font-size: 20px; color: #ffff; font-weight: 700; font-family: 'Rubik', sans-serif;">Order
                                    ID:</span> 
                                 </td>
                                 <td style="text-align: end; padding: 10px 0px;"><span
                                    style="font-size: 18px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;">
                                    <?php echo $user['order_code'] ?>
                                    </span> 
                                 </td>
                              </tr>
                           </table>
                           <table style="width: 100%; border-collapse: collapse;">
                              <tr>
                                 <td style="padding: 10px 0px;"><span
                                    style="font-size: 20px; color: #ffff; font-weight: 700; font-family: 'Rubik', sans-serif; padding-bottom: 20px;">Ordered
                                    Items</span> 
                                 </td>
                                 <td style="padding: 10px 0px;text-align: right;"><span
                                    style="font-size: 20px; color: #ffff; font-weight: 700; font-family: 'Rubik', sans-serif; padding-bottom: 20px;">Amount</span>
                                 </td>
                              </tr>
                           </table>
                           <?php $total = 0; ?>
                         
                           <table style="width: 100%; border-collapse: collapse;">
                           <?php foreach ($user['order_items']  as $resource) { ?>
                              <tr style="vertical-align: baseline;">
                                 <td style="padding: 10px 0px;"><span
                                    style="font-size: 18px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;">
                                   <?php echo $resource['product_name'] ?>
                                    </span> 
                                 </td>
                                 <td
                                    style="text-align: end; padding: 10px 0px; font-size: 18px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif; white-space: nowrap;">
                                    <span style="color: #00FF0A;">
                                    ₹
                                    </span>
                               <?php echo $resource['sub_total'] ?>
                                 </td>
                                 <?php $total = $total + $resource['sub_total'] ?>
                              </tr>
                              <?php } ?>
                              <tr style="border-top: 1px solid rgba(255, 255, 255, 0.75);">
                                 <td style="padding: 10px 0px;">
                                    <span
                                       style="font-size: 18px; color: #ffff; font-weight:400; font-family: 'Rubik', sans-serif;">Total</span>
                                 </td>
                                 <td
                                    style="text-align: end; padding: 10px 0px; font-size: 18px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;">
                                    <span style="color: #00FF0A;">
                                    ₹
                                    </span>
                                    <?php echo sprintf("%.2f", $total) ?>
                                 </td>
                              </tr>
                              <tr>
                                 <td style="padding: 10px 0px;">
                                    <span
                                       style="font-size: 18px; color: #ffff; font-weight:400; font-family: 'Rubik', sans-serif;">Delivery
                                    Charge</span>
                                 </td>
                                 <td
                                    style="text-align: end; padding: 10px 0px; font-size: 18px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;">
                                    <span style="color: #00FF0A;">
                                    ₹
                                    </span>
                                    <?php echo $user['shipping_cost'] ?>
                                 </td>
                              </tr>

                              <?php if (!empty($user['coupon_code'])) { ?>
                                 <tr>
                                 <td style="padding: 10px 0px;">
                                    <span
                                       style="font-size: 18px; color: #ffff; font-weight:400; font-family: 'Rubik', sans-serif;">Coupon Amount</span>
                                 </td>
                                 <td
                                    style="text-align: end; padding: 10px 0px; font-size: 18px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;">
                                    <span style="color: #00FF0A;">
                                    ₹
                                    </span>
                                    <?php echo $user['coupon_amount'] ?>
                                 </td>
                              </tr>
                              <?php } ?>

                              <tr>
                                 <td style="padding: 10px 0px;">
                                    <span
                                       style="font-size: 18px; color: #ffff; font-weight:400; font-family: 'Rubik', sans-serif;">Round Off</span>
                                 </td>
                                 <td
                                    style="text-align: end; padding: 10px 0px; font-size: 18px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;">
                                    <span style="color: #00FF0A;">
                                    <?php echo $user['roundOffValueSymbol'] ?>
                                    </span>
                                    <?php echo $user['remaining_value'] ?>
                                 </td>
                              </tr>
                              


                              <tr style="border-top: 1px solid rgba(255, 255, 255, 0.75);">
                                 <td style="padding: 10px 0px;">
                                    <span
                                       style="font-size: 20px; color: #ffff; font-weight:700; font-family: 'Rubik', sans-serif;">Grand
                                    Total</span>
                                 </td>
                                 <td
                                    style="text-align: end; padding: 10px 0px; font-size: 18px; color: #ffff; font-weight: 600; font-family: 'Rubik', sans-serif;">
                                    <span style="color: #00FF0A;">
                                    ₹
                                    </span>
                                    <?php echo sprintf("%.2f", $user['payment_amount']) ?>
                                 </td>
                              </tr>
                           </table>
                        </div>
                        <div>
                           <p
                              style="font-size: 18px;  font-weight: 500; font-family: 'Rubik', sans-serif; color: #008405; text-align: center; margin-top: 25px;">
                              Your order should be delivered in "<?php echo $user['expected_delivery_days'] ?>" Days.
                           </p>
                        </div>
                        <div>
                           <div style="display: flex; align-items: center; justify-content: space-between;">
                              <table style="width: 100%; margin: auto;">
                                 <tr>
                                    <td>
                                       <p style="text-decoration: none; font-size: 20px; color:  rgba(0, 0, 0, 0.75);
                                          font-family: 'Rubik', sans-serif; font-weight: 500;
                                          ">Get more information</p>
                                    </td>
                                    <td style="text-align: right;">
                                       <button
                                          style=" border: 0.839836px solid #18448F; height: 39px; width: 121px; border-radius: 7.5px; background: #ffff;">
                                       <a href="https://stage.theprintapp.com/" style="text-decoration: none; font-size: 18px; color: #18448F;
                                          font-family: 'Rubik', sans-serif; font-weight: 500;
                                          "> Visit Here </a> </button>
                                    </td>
                                 </tr>
                              </table>
                           </div>
                           <!-- <p style="font-size: 18px; color: #000; font-family: 'Rubik', sans-serif; font-weight: 400;
                              line-height: 27px;
                              margin-top: 20px;
                              margin-bottom: 25px;
                              text-align: center;">Thanks & Regards
                              <br>
                              Team Printapp
                              
                              </p> -->
                        </div>
                     </div>
                  </section>
                  <table style="height: 60px;
                     width: 100%;
                     background-color: #18448f;">
                     <tr>
                        <td style="text-align: center;">
                           <table style="
                              width: 8%; margin: auto;
                              ">
                              <tr>
                                 <td>
                                    <div style="height: 35px; width: 35px;     margin: 0px 3px;">
                                       <a href="https://www.facebook.com/printapp1"><img
                                          style="width: 100%; height: 100%;"
                                          src="{{ URL::to('/') }}/public/register_assets/facebook.png"
                                          alt=""></a>
                                    </div>
                                 </td>
                                 <td>
                                    <div style="height: 35px; width: 35px;     margin: 0px 3px;">
                                       <a href="https://www.youtube.com/channel/UCgP2ilffMitNsB3REOvDNwA"> <img
                                          style="width: 100%; height: 100%;"
                                          src="{{ URL::to('/') }}/public/register_assets/youtube.png"
                                          alt=""></a>
                                    </div>
                                 </td>
                                 <td>
                                    <div style="height: 35px; width: 35px;     margin: 0px 3px;">
                                       <a href="https://www.instagram.com/theprintapp2021/"> <img
                                          style="width: 100%; height: 100%;"
                                          src="{{ URL::to('/') }}/public/register_assets/insta.png"
                                          alt=""></a>
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