<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <!--<title>Email Template</title>-->
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
</head>

<body>
    <style>
        .container_width {
            width: 600px;
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
                <section>
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

                    <section class="inner_cont" style="padding: 20px;text-align: left;">
                        <P
                            style="font-size: 20px; color: #000; font-weight: 600; font-family: 'Rubik', sans-serif; margin-bottom: 10px; margin-top: 50px;">
                            Dear <span style="color: #18448F;">{{$customer_name}},</span></P>
                        <p
                            style="font-size: 14px;font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; margin-top: 0;">
                            Greetings from Print App!</p>


                        <p
                            style="font-size: 14px;font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; line-height: 20px; text-align: justify;">
                            We are pleased to inform you that your enquiry <b>{{$enquiry_code}}</b> has been accepted
                            to proceed with further quote preparation. Our team will now begin working on preparing a
                            detailed quote based on your requirements.
                        </p>
                        <p style="font-size: 14px;font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; text-align: justify">
                            We appreciate your patience throughout this process and look forward to providing
                            you with the information you need to make an informed decision.
                        </p>
                        <p style="font-size: 14px;font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; text-align: justify">If
                            you have any questions or require any further clarification, please do not hesitate to
                            contact us
                            <a style="text-decoration: none; color: #18448F;" href="mailto:printapp2021@gmail.com ">
                                printapp2021@gmail.com
                            </a> / <a style="text-decoration: none; color: #18448F;">+91-9003923500</a>.
                        </p>
                        <p style="font-size: 14px;font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; text-align: justify">We
                            are committed to delivering the best possible solutions to meet your requirements.</p>
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