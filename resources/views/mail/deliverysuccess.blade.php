<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
  <title>Delivery Success</title>
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


  <table class="container_width" style="background: #ffff; max-width: 600px; margin: auto;" width="100%" border="0" cellspacing="0" cellpadding="0">
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
                    <img style="height: 100%; width: 100%" src="{{ URL::to('/') }}/public/register_assets/logo.png" alt="" />
                </a>
                  </div>
                </td>
              </tr>
            </table>
          </div>

          <section style="width: 80%; margin: auto; margin-top: 55px; margin-bottom: 15px;">
            <div>

              <div style="text-align: left;">
                <p style="font-size:20px; font-weight: 600; font-family: 'Rubik', sans-serif; color: #000;">Dear <span style="color: #18448f;"><?php echo $user['customer_name'] ?>,</span></p>
                <p style="font-size: 18px; font-weight: 400; font-family: 'Rubik', sans-serif; color: #000;">
                  Thanks for shopping with Print App!</p>
              </div>

              <div style="text-align: center;">
                <div style="width: 217px; height: 230px; margin: auto; margin-top: 25px;">
                <a style="cursor: none; pointer-events: none;" href="#">
                  <img style="height: 100%; width: 100%;" src="{{ URL::to('/') }}/public/delivery_assets/deliverysuccess.png" alt="">
                </a>
                </div>









              </div>

              <div>
                <h1 style="font-size: 35px; font-weight: 700; font-family: 'Rubik', sans-serif; color: #18448F; 
                margin-top: 20px; margin-bottom: 13px;
                
                ">
                  Your order has been delivered successfully
                </h1>
              </div>

              <div>

                <p style="font-size: 18px; font-weight: 400; font-family: 'Rubik', sans-serif; color: #000; margin-top: 25px; line-height: 25px; text-align: justify;">
                  We’re happy an update that your order “<?php foreach ($user['order_items']  as $resource) { ?> <?php echo $resource['product_name'] ?> <?php } ?>” has been delivered successfully. Enjoy with your order.
                </p>





                </p>


              </div>


              <div style="background-color: #18448f; padding: 10px 25px ; width: 75%; margin: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                  <tr>
                    <td style="padding: 10px 0px;"><span style="font-size: 20px; color: #ffff; font-weight: 700; font-family: 'Rubik', sans-serif;">Delivered Items :</span> </td>


                  </tr>

                  <?php foreach ($user['order_items']  as $resource) { ?>
                    <tr>
                      <td>

                        <div style="display: flex; align-items: center; padding-left: 12px;">
                          <div style="height: 28px; width: 28px; ">
                <a style="cursor: none; pointer-events: none;" href="#">
                            <img style="width: 100%; height: 100%;" src="{{ URL::to('/') }}/public/delivery_assets/successtick_email.png" alt="">
                </a>


                          </div>

                          <div>

                            <p style="font-size: 18px; font-weight: 400; font-family: 'Rubik', sans-serif; color: #ffff;  line-height: 25px; margin-top:  10px; margin-bottom: 10px; margin-left: 10px;">
                              <?php echo $resource['product_name'] ?></p>

                          </div>

                        </div>






                      </td>




                    </tr>

                  <?php } ?>

                </table>







              </div>
              <div>
                <p style="font-size: 18px; font-weight: 400; font-family: 'Rubik', sans-serif; color: #000; margin-top: 25px; line-height: 25px;">
                  We hope you have a great day.
                </p>
              </div>




              <div style="margin-top: 20px;">

                <div style="display: flex; align-items: center; justify-content: space-between;">
                  <table style="width: 100%;">
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
                width: 10%; margin: auto;
                ">
                  <tr>
                    <td>
                      <div style="height: 35px; width: 35px;     margin: 0px 3px;">
                        <a href="https://www.facebook.com/printapp1"><img style="width: 100%; height: 100%;" src="{{ URL::to('/') }}/public/register_assets/facebook.png" alt=""></a>



                      </div>
                    </td>
                    <td>
                      <div style="height: 35px; width: 35px;     margin: 0px 3px;">
                        <a href="https://www.youtube.com/channel/UCgP2ilffMitNsB3REOvDNwA"> <img style="width: 100%; height: 100%;" src="{{ URL::to('/') }}/public/register_assets/youtube.png" alt=""></a>

                      </div>
                    </td>
                    <td>
                      <div style="height: 35px; width: 35px;     margin: 0px 3px;">
                        <a href="https://www.instagram.com/theprintapp2021/"> <img style="width: 100%; height: 100%;" src="{{ URL::to('/') }}/public/register_assets/insta.png" alt=""></a>

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