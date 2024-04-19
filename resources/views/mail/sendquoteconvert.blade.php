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
                     <p style="font-size: 20px; margin-top: 50px; color: #000; font-weight: 600; font-family: 'Rubik', sans-serif;margin-bottom: 10px;">Dear <span style="color: #18448F;">{{$contact_person_name}},</span></p>
                     <p style="font-size: 14px;font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; line-height: 12px;">Thank you for choosing our PrintApp!</p>
                     <div>
                     </div>
                     <div style="margin-top: 20px; margin-bottom: 15px;">
                     <a style="cursor: none; pointer-events: none;" href="#">
                        <img style="height: 100%; width: 24%; display: block; margin: 34px auto; object-fit: cover;" src="https://api.theprintapp.com/public/ordersuccess_assets/success.png" alt="">
                     </a>
                        <div style="text-align: left;">
                           <h1 style="font-size: 18px; font-weight: 700;color: #18448F; font-family: 'Rubik', sans-serif; text-align: center; margin-bottom: 6px; margin-top: 20px;">Quote Created Successfully</h1>
                           <p style="font-size: 14px;font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; line-height: 20px;text-align: justify;">
                              We are pleased to inform you that your recent enquiry has been successfully processed, and a quotation has been created for your consideration. We greatly appreciate your interest in our PrintApp.
                           </p>
                           <p style="font-size: 15px;font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; line-height: 25px;">Here are the details of the quotation :</p>
                           <div class="">
                              <p style="font-size: 14px;font-family: 'Rubik', sans-serif; font-weight: 600;color: #000; line-height: 25px;">Enquiry Id: <span style="font-weight: 600;color: #18448F;margin-left: 4px;">{{$enquiry_code}}</span></p>
                              <p style="font-size: 14px;font-family: 'Rubik', sans-serif; font-weight: 600;color: #000; line-height: 2px;">Quotation No: <span style="font-weight: 600;color: #18448F; margin-left: 4px;">{{$quote_code}}</span></p>
                              <p style="font-size: 14px;font-family: 'Rubik', sans-serif; font-weight: 600;color: #000; line-height: 25px;">Name:<span style="font-weight: 600;color: #18448F; margin-left: 4px;">{{$contact_person_name}}</span></p>
                           </div>
                           <div>
                            <p style="font-size: 14px;font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; line-height: 20px;">
                                If you have any questions or need further assistance, please feel free to contact our customer service team at <a href="mailto:printapp2021@gmail.com">printapp2021@gmail.com</a> / <a href="tel:9003923500">+91-9003923500</a>.
                             </p>
                              <p style="font-size: 14px;font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; line-height: 25px;">Find the attachment below.</p>
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