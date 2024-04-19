<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <!--<title>Email Template</title>-->
</head>

<body>
    <table>
        <tr>
           <td>
                <P> Dear Sir, 
                <br><br>This mail is inform to you, the order "<?php echo $user['order_code']; ?>" has been revoked by <?php echo $user['name']; ?>. The order details are,</p>
                Order ID : <?php echo $user['order_code']; ?><br>
                Order Date : <?php echo $user['order_date']; ?><br>
                Customer Name : <?php echo $user['billing_customer_first_name']; ?> <?php echo $user['billing_customer_last_name']; ?><br>
                Order Amount : <?php echo $user['order_totalamount']; ?><br>
                <br>
                With regards,<br>
                NRI Support
            </td>
        </tr>
        <!-- <tr>
                <td>
                    <p>My Sourcing Hub</p>
                </td>
            </tr> -->
    </table>
</body>

</html>