


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet" />
    <title>Delivery Success</title>
</head>

<body style="background: #f5f5f5;">

    <style>
        .container_width {
            width: 40%;
            margin: auto;
        }

        .coupon_codeStyle {
            color: #18448F;
            font-size: 18px;
            font-weight: 500;
            margin: 10px 0;
            font-family: 'Rubik', sans-serif !important;
        }

        .coupon_codeStyle span {
            color: #333 !important;
            font-weight: 500;
            margin-left: 10px;
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
                <a style="cursor: none; pointer-events: none;" href="#">
                                        <img style="height: 100%; width: 100%"
                                            src="{{ URL::to('/') }}/public/register_assets/logo.png" alt="" />
                </a>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <section
                        style="max-width:600px; padding: 0 40px; margin: auto; margin-top: 55px; margin-bottom: 15px;">
                        <div style="text-align: left;">

                            <div>
                                <p
                                    style="font-size:20px; font-weight: 600; font-family: 'Rubik', sans-serif; color: #000;">
                                    Dear Customer,</p>
                                <p
                                    style="font-size: 18px; font-weight: 400; font-family: 'Rubik', sans-serif; color: #000;">
                                    Thanks for shopping with Print App!</p>
                            </div>

                            <div style="text-align: center;">
                                <div style="width: 217px; height: 230px; margin: auto; margin-top: 25px;">
                <a style="cursor: none; pointer-events: none;" href="#">
                                    <!-- <img style="height: 100%; width: 100%;" src="{{ URL::to('/') }}/public/delivery_assets/deliverysuccess.png" alt=""> -->
                                    <img style="height: 100%; width: 100%;"
                                        src="{{ URL::to('/') }}/public/delivery_assets/deliverysuccess.png"
                                        alt="">
                </a>
                                </div>









                            </div>

                            <div>
                                <h1 style="font-size: 35px; font-weight: 700; font-family: 'Rubik', sans-serif; color: #18448F; 
                margin-top: 20px; margin-bottom: 13px;
                
                ">
                                    Special Coupon Just for You!
                                </h1>
                            </div>


                            <div>

                                



                                <p
                                    style="font-size: 18px; font-weight: 400; font-family: 'Rubik', sans-serif; color: #000; margin-top: 25px; line-height: 25px;">
                                    As a token of our appreciation for your continued support, we're delighted to offer
                                    you a special coupon code to use on your next purchase. Simply enter the code
                                    <b>“<?php echo $coupon_code['coupon_code']; ?>”</b> at checkout to enjoy <b>“<?php echo $coupon_code['percentage']; ?>%”</b>.
                                </p>

                                <p class="coupon_codeStyle">Coupon Code:<span><?php echo $coupon_code['coupon_code']; ?></span></p>
                               
                                <p class="coupon_codeStyle">Valid Until:<span><?php echo  date('d-m-Y', strtotime($coupon_code['set_end_date'])); ?></span></p>
                                <p
                                    style="font-size: 18px; font-weight: 400; font-family: 'Rubik', sans-serif; color: #000; margin-top: 25px; line-height: 25px;">
                                    We hope this discount enhances your shopping experience with us. Don't miss out on
                                    this exclusive offer!
                                </p>

                                <p
                                    style="font-size: 18px; font-weight: 400; font-family: 'Rubik', sans-serif; color: #000; margin-top: 25px; line-height: 25px;">
                                    Thank you for being a valued customer. We look forward to serving you again soon.
                                </p>

                            </div>



                            <div>







                                </p>


                            </div>


                         
                            <div>
                                
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
                                                <button
                                                    style=" border: 0.839836px solid #18448F; height: 39px; width: 121px; border-radius: 7.5px; background: #ffff;">
                                                    <a href="{{ env('WEBSITEURL') }}" style="text-decoration: none; font-size: 18px; color: #18448F;
                                    font-family: 'Rubik', sans-serif; font-weight: 500;
                                     "> Visit Here </a> </button>
                                            </td>
                                        </tr>
                                    </table>


                                </div>

             




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