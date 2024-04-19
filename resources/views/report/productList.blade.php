<html>

<head>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,400;0,500;1,400&display=swap" rel="stylesheet" />
    <script type="text/javascript" src="https://canvasjs.com/assets/script/jquery-1.11.1.min.js"></script>
    <script type="text/javascript" src="https://canvasjs.com/assets/script/jquery.canvasjs.min.js"></script>
    <style>
        body {
            font-family: Roboto, "Helvetica Neue", sans-serif;
        }
        
       .text-center {
            text-align: center;
        }
        
       .table-form {
            width: 100%;
            border-spacing: 0px;
            padding: 0px;
            margin-top:10px;
        }
        
        .table-form td {
            text-transform: capitalize;
            padding: 10px;
        }

        .table-form th {
            font-weight: 600;
            padding: 8px 10px;
            background: #d9d9d9;
        }

        th:nth-child(6) {
            border-right: 1px solid black;
        }
        .total td{
            font-weight: 700 !important;   
        }
    </style>
  
</head>

<body>
    <table style="width: 100%;">
        <tr>
            <td style="width: 100%; text-align: center;">
                <h2><strong>Product Catalogue</strong></h2>
            </td>
        </tr>
    </table>
    <div style="border-top:1px solid #ccc;"></div>


    <table style="width: 100%;margin-top:20px;">
    </table>
  
    <table class="table-form" border="1">
        <thead>
            <tr>
                <th style="text-align:center;"><strong>Created On </strong></th>
                <th style="text-align:center;"><strong>Product Code </strong></th>
                <th style="text-align:center;"><strong>Product Name </strong></th>
                <th style="text-align:center;"><strong>Brand </strong></th>
                <th style="text-align:center;"><strong>MRP (₹) </strong></th>
                <th style="text-align:center;"><strong>Price (₹) </strong></th>
                <th style="text-align:center;"><strong>Publish </strong></th>
                <th style="text-align:center;"><strong>Features Product </strong></th>
                <th style="text-align:center;"><strong>Availability </strong></th>
            </tr>
        </thead>
         <?php foreach ($result as $rs) {   ?>
                     <?php if(count($rs['list']) > 0) {  ?>
        <tbody>
            
                   
                      
                            <tr style="background: #ffebcdc4;">
                                <td colspan="6"><?php echo $rs['category_name']; ?></td>
                            </tr>
                            @forelse ($rs['list'] as $ls)
                            <tr>
                                <td style="text-align:center;text-transform:uppercase;"><?php echo $ls['product_date']; ?></td>
                                <td style="text-align:center;text-transform:uppercase;"><?php echo $ls['product_code']; ?></td>
                                <td style="text-align:center;"><?php echo $ls['product_name']; ?></td>
                                <td style="text-align:center;"><?php echo $ls['brand_name']; ?></td>
                                <td style="text-align:right;"><?php echo $ls['mrp']; ?></td>
                                <td style="text-align:right;"><?php echo $ls['selling_price']; ?></td>
                                <td style="text-align:center;"><?php if ($ls['publish'] == 1) {echo "Yes";} else { echo "No"; } ?></td>
                                <td style="text-align:center;"><?php if ($ls['is_featured_product'] == 1) {echo "Yes";} else { echo "No"; } ?></td>
                                <td style="text-align:center;"><?php if ($ls['quantity'] > 0) {echo "<b><font color=green>IN STOCK</font></b>";} else { echo "<b><font color=red>OUT OF STOCK</font></b>"; } ?></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6">No Data Found</td>
                            </tr>
                            @endforelse
                            </tbody>
                          
                       
                        <?php  } ?> <?php }  ?>

    </table>
    <table style="width: 100%;margin-top:10px;">
    </table>

</body>


</html>