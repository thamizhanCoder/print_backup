<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <title>Order Cancel</title>
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

    <table class="container_width" style="background: #ffff;" width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center">
    <section >
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
                <a style="cursor: none; pointer-events: none;" href="#">
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

                <div>
                    <p style="font-size:20px; font-weight: 600; font-family: 'Rubik', sans-serif; color: #000;">Dear <span style="color: #18448f;"><?php echo $user['customer_name'] ?>,</span></p>
                    <p style="font-size: 18px; font-weight: 400; font-family: 'Rubik', sans-serif; color: #000;">Thanks for shopping with Print App!</p>
                </div>

                <div style="text-align: center;">
                    <div style="width: 217px; height: 230px; margin: auto; margin-top: 25px;">
                        <img style="height: 100%; width: 100%;" src="{{ URL::to('/') }}/public/cancel_assets/cancel_order.png" alt="">
                    </div>









                </div>

                <div>
                    <h1 style="font-size: 35px; font-weight: 700; font-family: 'Rubik', sans-serif; color: #18448F; 
                margin-top: 20px; margin-bottom: 13px;
                
                ">
                        Your order item has been cancelled
                    </h1>
                </div>

                <div>
                    <?php if ($user['is_cod'] == 2) { ?>
                        <p style="font-size: 18px; font-weight: 400; font-family: 'Rubik', sans-serif; color: #000; margin-top: 25px; line-height: 25px;">
                            We’re sorry that you’re unsatisfied with your order from Print App. Your order item <b>“<?php echo $user['product_name'] ?>”</b> has been cancelled successfully. We have started the refund process for your order, the details will be updated soon.
                        </p>
                    <?php } elseif ($user['is_cod'] == 1) { ?>
                        <p style="font-size: 18px; font-weight: 400; font-family: 'Rubik', sans-serif; color: #000; margin-top: 25px; line-height: 25px;">
                            We’re sorry that you’re unsatisfied with your order from Print App. Your order item <b>“<?php echo $user['product_name'] ?>”</b> has been cancelled successfully.
                        </p>

                    <?php } ?>

                    </p>


                </div>


                <div style="background-color: #18448f; padding: 10px 25px ;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.75);">
                            <td style="padding: 10px 0px;"><span style="font-size: 20px; color: #ffff; font-weight: 700; font-family: 'Rubik', sans-serif;">Order ID:</span> </td>
                            <td style="text-align: end; padding: 10px 0px;"><span style="font-size: 18px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;"><?php echo $user['order_code'] ?></span> </td>

                        </tr>
                    </table>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 10px 0px; width: 90%;"><span style="font-size: 20px; color: #ffff; font-weight: 700; font-family: 'Rubik', sans-serif; padding-bottom: 20px;">Ordered Items</span> </td>
                            <td style="padding: 10px 0px;"><span style="font-size: 20px; color: #ffff; font-weight: 700; font-family: 'Rubik', sans-serif; padding-bottom: 20px; text-align: end;">Amount</span> </td>

                        </tr>
                    </table>

                    <table style="width: 100%; border-collapse: collapse;">
                    <?php $total = 0; ?>
                        <tr>
                            <td style="padding: 10px 0px; width: 80%;"><span style="font-size: 18px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;"><?php echo $user['product_name'] ?></span> </td>
                            <td style="text-align: end; padding: 10px 0px; font-size: 18px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;">

                                <span style="color: #00FF0A;">
                                    ₹
                                </span>
                                <?php echo round($user['sub_total']) ?>.00
                            </td>
                            <?php $total += $total + $user['sub_total'] ?>

                        </tr>


                        <tr style="border-top: 1px solid rgba(255, 255, 255, 0.75);">
                            <td style="padding: 10px 0px; width: 80%;">
                                <span style="font-size: 20px; color: #ffff; font-weight:700; font-family: 'Rubik', sans-serif;">Total</span>
                            </td>
                            <td style="text-align: end; padding: 10px 0px; font-size: 18px; color: #ffff; font-weight: 400; font-family: 'Rubik', sans-serif;">

                                <span style="color: #00FF0A;">
                                    ₹
                                </span>
                                <?php echo round($total) ?>
                            </td>

                        </tr>
                    </table>






                </div>




                <div style="margin-top: 20px;">

                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <table style="width: 100%; margin: auto;">
                            <tr>
                                <td>
                                    <p style="text-decoration: none; font-size: 20px; color:  rgba(0, 0, 0, 0.75);;
                                    font-family: 'Rubik', sans-serif; font-weight: 500;
                                     ">Get more information</p>
                                </td>
                                <td style="text-align: right;">
                                    <button style=" border: 0.839836px solid #18448F; height: 39px; width: 121px; border-radius: 7.5px; background: #ffff;"> <a href="{{ env('WEBSITEURL') }}" style="text-decoration: none; font-size: 18px; color: #18448F;
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
                  width: 20%; margin: auto;
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