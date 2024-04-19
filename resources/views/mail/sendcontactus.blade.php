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
                <P> Dear NR Info Tech Computer, 
                <br><br>Your email received a new inquiry submission from <?php echo $user['contact_name']; ?>. For making inquiry about <?php echo $user['message']; ?>. Please find the details. </p>
                Name : <?php echo $user['contact_name']; ?><br>
                Email : <?php echo $user['contact_email']; ?><br>
                Contact No : <?php echo $user['contact_phone_no']; ?><br>
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