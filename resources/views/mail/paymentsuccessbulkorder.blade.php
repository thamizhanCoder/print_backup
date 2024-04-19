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








  <section class="container_width" style="background: #ffff;">
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

    <section class="inner_cont" style="width: 80%; margin: auto">
      <p style="text-align: end; font-family: 'Rubik', sans-serif;">
        <a style="text-decoration: none; font-size: 15px; color: #18448f" href="{{ env('WEBSITEURL') }}">View In Browser</a>
      </p>

      <div>

        <h1 style="font-size: 35px; font-weight: 700;color: #18448F; font-family: 'Rubik', sans-serif; text-align: center; margin-bottom: 6px; margin-top: 40px;"><span style="color: #000;">Reraise a Quote </span></h1>


        <div style="text-align: center;">
          <svg class="svg" width="364" height="14" viewBox="0 0 364 14" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0.352295 8.77609C50.6819 3.6635 156.614 -3.1051 177.704 10.7213C154.271 16.8369 164.166 -13.8559 363.552 12.7596" stroke="#18448F" stroke-width="2" />
          </svg>
        </div>





      </div>
      
      <div style="margin-top: 20px; margin-bottom: 15px;">

        <img style="height: 100%; width: 100%; object-fit: cover;" src="{{ URL::to('/') }}/public/register_assets/img_1.png" alt="">

        <div>
        <p style="font-size: 20px; color: #000; font-weight: 600; font-family: 'Rubik', sans-serif;">Dear Admin <span style="color: #18448F;">,</span></p>
          <p style="font-size: 18px;font-family: 'Rubik', sans-serif; font-weight: 400;color: #000; line-height: 25px;">
          You have successfully received the payment amount information Rs <?php echo $user['amount'] ?> for bulk order <?php echo $user['order_code'] ?>
          </p>

        
          <div>
            <p style="font-size: 18px; color: #000; font-family: 'Rubik', sans-serif; font-weight: 400;
                    line-height: 27px;
                    margin-top: 20px;
                    margin-bottom: 25px;
                    text-align: center;">Thanks & Regards
              <br>
            Team Printapp
        
            </p>

          </div>








        </div>




      </div>









    </section>

    <div style="
        height: 60px;
        width: 100%;
        background-color: #18448f;
        display: flex;
        align-items: center;
        justify-content: center;
      ">

      <div style="height: 35px; width: 35px;     margin: 0px 3px;">
        <a href="https://www.facebook.com/printapp1"><img style="width: 100%; height: 100%;" src="{{ URL::to('/') }}/public/register_assets/facebook.png" alt=""></a>
      </div>

      <div style="height: 35px; width: 35px;     margin: 0px 3px;">
        <a href="https://www.youtube.com/channel/UCgP2ilffMitNsB3REOvDNwA"> <img style="width: 100%; height: 100%;" src="{{ URL::to('/') }}/public/register_assets/youtube.png" alt=""></a>

      </div>

      <div style="height: 35px; width: 35px;     margin: 0px 3px;">
        <a href="https://www.instagram.com/theprintapp2021/"> <img style="width: 100%; height: 100%;" src="{{ URL::to('/') }}/public/register_assets/insta.png" alt=""></a>
      </div>



    </div>




  </section>
</body>

</html>