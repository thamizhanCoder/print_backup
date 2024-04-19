<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet" />
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

                    <section class="inner_cont" style="width: 80%; margin: auto; text-align: left;">


                        <div>

                            <h1
                                style="font-size: 35px; font-weight: 700;color: #18448F; font-family: 'Rubik', sans-serif; text-align: center; margin-bottom: 6px; margin-top: 40px;">
                                <span style="color: #000;">Reraise a Quote </span></h1>


                            <div style="text-align: center;">
                                <svg class="svg" width="364" height="14" viewBox="0 0 364 14" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M0.352295 8.77609C50.6819 3.6635 156.614 -3.1051 177.704 10.7213C154.271 16.8369 164.166 -13.8559 363.552 12.7596"
                                        stroke="#18448F" stroke-width="2" />
                                </svg>
                            </div>





                        </div>

                        <div style="margin-top: 20px; margin-bottom: 15px;">

                            <img style="height: 100%; width: 100%; object-fit: cover;"
                                src="{{ URL::to('/') }}/public/register_assets/img_1.png" alt="">

                            <div>
                                <p
                                    style="font-size: 20px; color: #000; font-weight: 600; font-family: 'Rubik', sans-serif;">
                                    Dear Admin <span style="color: #18448F;">,</span></p>
                                <p
                                    style="font-size: 18px;font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; line-height: 25px;">
                                    You have received a request for quote re-raised
                                    <?php echo $user['quote_code'] ?> raised by
                                    <?php echo $user['employee_name'] ?>
                                </p>
                                <p
                                    style="font-size: 18px;font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; line-height: 25px;">
                                    Please find the details for enquiry –</p>

                                <p
                                    style="font-size: 17px;font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; line-height: 25px;">
                                    Quote Id:
                                    <span style="color: blue; margin-left: 4px;">
                                        <?php echo $user['quote_code'] ?>
                                    </span>
                                </p>
                                <p
                                    style="font-size: 17px;font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; line-height: 25px;">
                                    Employee Name:
                                    <span style="color: blue; margin-left: 4px;">
                                        <?php echo $user['employee_name'] ?>
                                    </span>
                                </p>
                                <p
                                    style="font-size: 17px;font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; line-height: 25px;">
                                    Quote Description:
                                    <span style="color: blue; margin-left: 4px;">
                                        <?php echo $user['quote_description'] ?>
                                    </span>
                                </p>
                                <div style="text-align: center;">
                                    <button style="height: 43px; width: 226px; background-color: #18448F; border-radius: 10px;
                    
                    border: none; cursor: pointer;
                    
                    ">

                                        <a style="font-size: 20px; font-weight: 700; font-family: 'Rubik', sans-serif; color: #ffff; text-decoration: none;"
                                            href="{{ env('WEBSITEURL') }}">View More</a>


                                    </button>

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
              width: 20%; margin: auto;
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