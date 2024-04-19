<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Sample Requested</title>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />

    <style>
       .im {
         color: #000 !important;
      }
    </style>
</head>

<body>
    <table class="container_width" style="background: #ffff;max-width: 600px;margin: auto;" width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
           <td >
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
                 <section class="inner_cont" style="padding: 20px;">
                    <div>
                       <p style="font-size: 20px; color: #000; font-weight: 600; font-family: 'Rubik', sans-serif;margin-top:40px;margin-bottom: 10px;">Dear <span style="color: #18448F;">{{$customer_name}},</span></p>
                     <div style="margin-top: 20px; margin-bottom: 15px;">
                      <div>
                       
                          <p style="font-size: 14px;font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; line-height: 20px;text-align: justify;">
                            This mail is to inform you that your enquiry <b>{{$enquiry_code}}</b> is currently in the status of <b>Sample Requested</b>. It has been requested at <b>{{$date}}</b>.
                           </p>
                          <p style="font-size: 14px;font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; line-height: 20px;text-align: justify;">Rest assured, your enquiry is on track, and we are working diligently to address your needs. If you have any questions or need further assistance, please feel free to contact our customer service team at <a href="mailto:printapp2021@gmail.com">printapp2021@gmail.com</a> / <a href="tel:9003923500">+91-9003923500</a>.
                        </p>
                        <p style="font-size: 14px;font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; line-height: 20px;text-align: justify;">
                            Thank you for your patience and cooperation.
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