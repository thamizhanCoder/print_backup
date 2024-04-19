<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email</title>
</head>
<body>

    <style>

@media screen and (max-width:991px){

    .details_table{
width: 100% !important;

}


}



    </style>

    <table style="width: 100%; border: none; border-collapse: collapse;">

        <tr>
            <td style="padding: 5px;" >

                <p style="margin-bottom: 0;">Dear <b>Customer</b></p>

            </td>
        </tr>

        <tr>
            <td style="padding: 5px;">

                <p style="margin-bottom: 0;">Good News from Printapp!
                    The day is finally here. Yes, your favourite product
                    now available on our site. Don’t be delay, place your order today.</p>
            </td>
        </tr>
 </table>

    <table class="details_table" style=" width: 55%; border: none; border-collapse: collapse;">
    
        <tr>
            <td style="padding: 5px; width: 20%;" >

                <p style="margin-bottom: 0;">Product Name &nbsp;:</p>

            </td>
            <td style="padding: 5px;" >

                <p style="margin-bottom: 0;"><?php echo $user['name']; ?></p>

            </td>
        </tr>
        <tr>
            <td style="padding: 5px; width: 20%; " >

                <p style="margin-bottom: 0;">Product Description &nbsp;:</p>

            </td>
            <td style="padding: 5px;" >

                <p style="margin-bottom: 0;"><?php echo $user['pdes']; ?></p>

            </td>
        </tr>

        <tr>
            <td style="padding: 5px; width: 20%;" >

                <p style="margin-bottom: 0;">image &nbsp;:</p>

            </td>
            <td style="padding: 5px;" >

                 <img src="<?php echo $user['image']; ?>" alt="" style=" border: 1px solid #ddd;
  border-radius: 4px;
  padding: 5px;
  width: 150px;">

            </td>
        </tr>

        <tr>
            <td style="padding: 5px; width: 20%;" >

                <p style="margin-bottom: 0;">Quantity &nbsp;:</p>

            </td>
            <td style="padding: 5px;" >

                <p style="margin-bottom: 0;"><?php echo $user['quantity']; ?></p>

            </td>
        </tr>
        <tr>
            <td style="padding: 5px; width: 20%;" >

                <p style="margin-bottom: 0;">Price &nbsp;:</p>

            </td>
            <td style="padding: 5px;" >

                <p style="margin-bottom: 0;">₹ <?php echo $user['selling_price']; ?></p>

            </td>
        </tr>

        <tr>
            <td style="padding: 5px; width: 20%;" >

                <p style="margin-bottom: 0;">Shop Now &nbsp;:</p>

            </td>
            <td style="padding: 5px;" >

                <p style="margin-bottom: 0;"><a href="{{ env('WEBSITEURL') }}" target="_blank" rel="noopener noreferrer">https://stage.theprintapp.com/</a></p>

            </td>
        </tr>
    
    </table>

    <div style="margin-top: 5%;">
        <p style="margin-bottom: 10px;"><b>Thanks & Regards</b></p>
        <p style="margin-bottom: 10px;"><b>Team Printapp</b></p>



    </div>





    
</body>
</html>