


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link
      href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700;800;900&display=swap"
      rel="stylesheet"
    />
    <title>reset password</title>
  </head>
  <body style="background: #f5f5f5;" >

    <table class="container_width" style="background: #ffff;" width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center">
    <section style="width:80%; margin: auto; background: #ffff;">
      <div
        style="
          height: 60px;
          width: 100%;
          background-color: #18448f;
          display: flex;
          align-items: baseline;
          justify-content: center;
        "
      >
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
                <p style="font-size:20px; font-weight: 600; font-family: 'Rubik', sans-serif; color: #000;" >Dear <span style="color: #18448f;"><?php echo $user['name'] ?>,</span></p>
               
            </div>

            <div style="text-align: center;">
                <div style="width: 166px; height: 86px; margin: auto; margin-top: 25px;">
                    <img style="height: 100%; width: 100%;" src="{{ URL::to('/') }}/public/reset_assets/resetpass.png" alt="">
                </div>

             
                <div>
                    <h1 style="font-size: 35px; font-weight: 700; font-family: 'Rubik', sans-serif; color: #18448F; 
                    margin-top: 10px; margin-bottom: 13px;
                    
                    ">
                   Reset Password
                    </h1>
                </div>
             

                



            </div>

            <div style="box-shadow: 0px 4px 20px rgba(205, 205, 205, 0.1), -4px -4px 20px rgba(177, 177, 177, 0.1); border-radius: 10px;
            padding: 12px 30px;
          margin-top: 25px;
            ">

                <div style="text-align: center;">

                    <p style="font-size: 20px; font-weight: 500; font-family: 'Rubik', sans-serif; color: rgba(0, 0, 0, 0.75);line-height: 25px;">
                        Are you forgot your password?
                    </p>



                    <p style="font-size: 18px;   margin-top: 25px; font-weight: 400; font-family: 'Rubik', sans-serif; color: #000;  line-height: 25px;">
                        We received a request to reset the password for your account.
                    </p>
    
                    
                    <p style="font-size: 18px;   margin-top: 25px; font-weight: 400; font-family: 'Rubik', sans-serif; color: #000;  line-height: 25px;">
                        Please click this button to change your password
                    </p>
    
    
                      
    
                    </p>
                    <div style="text-align: center;     margin-top: 25px;">
                        <button style="height: 39px; width: 247px; background-color: #18448F; border-radius: 10px;
                        
                        border: none; cursor: pointer;
                        
                        ">
    
                        <a style="font-size: 20px; font-weight: 500; font-family: 'Rubik', sans-serif; color: #ffff; text-decoration: none;" href="{{$user['link']}}">
                            
                            Reset Your Password
                        </a>
                            
                          
                        </button>
    
                    </div>

                    <p style="font-size: 18px; margin-top: 25px; font-weight: 400; font-family: 'Rubik', sans-serif; color: #000;  line-height: 25px;">
                        If you did not make this request then please ignore this email.
                    </p>
    

    
                </div>



            </div>


           

          

        

            <div style="margin-top: 20px;">

            <!-- <div style="display: flex; align-items: center; justify-content: space-between;">
                    <table style="width: 38%; margin: auto;">
                        <tr>
                            <td>
                                <p  style="text-decoration: none; font-size: 20px; color:  rgba(0, 0, 0, 0.75);;
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
                   
                    
                </div> -->

            


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
