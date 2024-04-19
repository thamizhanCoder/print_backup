<!---------------   Customer preview approved  ----------------->
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
      <table class="container_width" style="background: #ffff;max-width: 600px;margin: auto;" width="100%" border="0" cellspacing="0" cellpadding="0">
         <tr>
            <td>
               <section>
                  <div style="height: 60px;width: 100%;background-color: #18448f;">
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
                     <p style="font-size: 20px; color: #000; font-weight: 600; font-family: 'Rubik', sans-serif;margin-bottom: 10px;margin-top:40px;">Dear <span style="color: #18448F;">{{$customer_name}},</span></p>
                     <p style="font-size: 14px;font-family: 'Rubik', sans-serif;margin-top:0px;">Greetings from Print App!</p>     
                     <div style="margin-top: 20px; margin-bottom: 15px;">
                        <div style="text-align: center;">
                <a style="cursor: none; pointer-events: none;" href="#">
                           <img style="width: 100px;" src="{{ URL::to('/') }}/public/ordersuccess_assets/success.png" alt="">
                </a>
                           <h3 style="font-size: 18px;font-weight: 600;color: green;font-family: 'Rubik', sans-serif;">Approved Image Acknowledgement</h3>
                        </div>
                        <div>
                           <p style="font-size: 14px;font-family: 'Rubik', sans-serif;line-height: 20px;text-align: justify;">
                              We hope this message finds you well. The purpose of the mail is to accept your previous email and approve the image for the next stage.</p>
                           <p style="font-size: 14px;font-family: 'Rubik', sans-serif;line-height: 20px;text-align: justify;">If you have any questions or need further assistance, please do not hesitate to contact us <a style="text-decoration: none; color: #18448F;" href="mailto:printapp2021@gmail.com ">
                                printapp2021@gmail.com
                                </a>/
                                <a style="text-decoration: none; color: #18448F;" href="tel:9003923500">
                                   +91-9003923500
                                 </a>.
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