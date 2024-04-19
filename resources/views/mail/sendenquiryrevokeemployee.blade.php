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
      <section class="container_width" style="background: #ffff;max-width: 600px;margin: auto;">
         <div style="
            height: 60px;
            width: 100%;
            background-color: #18448f;
            display: flex;
            align-items: baseline;
            justify-content: center;
            ">
            <div style="height: 84px; width: 84px; margin-top: 15px">
            <a style="cursor: none; pointer-events: none;" href="#">
               <img style="height: 100%; width: 100%" src="{{ URL::to('/') }}/public/register_assets/logo.png" alt="" />
            </a>
            </div>
         </div>
         <section class="inner_cont" style="padding: 20px;">
            <div style="margin-top: 20px; margin-bottom: 15px;">
               <div style="text-align: center;">
                  <p style="font-size: 18px; color: #18448F; font-weight: 600; font-family: 'Rubik', sans-serif;">Enquiry Revoked</p>
               </div>
               <div>
                  <p style="font-size: 20px; color: #000; font-weight: 600; font-family: 'Rubik', sans-serif;">Dear <span style="color: #18448F;"><?php echo $user['employee_name'] ?>,</span></p>
                  <p style="font-size: 14px;font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; line-height: 25px;">
                     We’re pleased to inform you that your enquiry <b><?php echo $user['enquiry_code'] ?></b> has been revoked by the admin.
                  </p>
                  <p style="font-size: 14px;font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; line-height: 20px;">
                     If you have any questions or need further assistance, please feel free to contact our customer service team at <a href="mailto:printapp2021@gmail.com">printapp2021@gmail.com</a> / <a href="tel:9003923500">+91-9003923500</a>.
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
   </body>
</html>