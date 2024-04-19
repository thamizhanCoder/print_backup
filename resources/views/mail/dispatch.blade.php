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

            <table rules="all" border="1" width="700" style="border-color: #666;" cellpadding="10">
                    <tr>
                                        <td colspan='4' align='center'>
                <a style="cursor: none; pointer-events: none;" href="#">
                                            <img style='width:100px;' src='http://nriapi.technogenesis.in/public/logo/logo.png'> 
                </a>   
                                        </td>
                                    </tr>

                    <tr style='background: #da1e13;'>
                                        <td colspan='4' align='center' style='color: white;'>
                                            <strong>Product Dispatched Details</strong> 
                                        </td>
                                    </tr>

                    <tr>
                                        <td>
                                            <strong>Courier Name: </strong>
                                        </td>
                                        <td colspan='3'>
                                            <?php echo $user['name']; ?> 
                                        </td>
                                    </tr>
                    <tr>
                                        <td>
                                            <strong>Courier Number:</strong> 
                                        </td>
                                        <td colspan='3'><?php echo $user['no']; ?> </td>
                                    </tr>
                    <tr>
                                        <td>
                                            <strong>Courier Tracking Url: </strong>
                                        </td>
                                        <td colspan='3'>
                                        <a href="<?php echo $user['url']; ?>"> <?php echo $user['url']; ?></a> 
                                        </td>
                                    </tr>
    </table>
</body>

</html>