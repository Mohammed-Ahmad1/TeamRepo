<?php

session_start();

require_once "connect.php";

$db = new Database();

$conn = $db->conn;



if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");

    exit();

}



$user_id = $_SESSION['user_id'];



$query = "SELECT SUM(p.price * ci.quantity) as total

          FROM cart_items ci

          JOIN products p ON ci.product_id = p.product_id

          JOIN carts c ON ci.cart_id = c.cart_id

          WHERE c.user_id = '$user_id'";

$result = mysqli_query($conn, $query);

$row = mysqli_fetch_assoc($result);

$cart_total = $row['total'] ? $row['total'] : 0;



if (isset($_POST['place_order']) && $cart_total > 0) {

    $payment_method = $_POST['payment_method'];

    $order_sql = "INSERT INTO orders (user_id, total_amount) VALUES ('$user_id', '$cart_total')";

   

    if (mysqli_query($conn, $order_sql)) {

        $order_id = mysqli_insert_id($conn);

        $payment_sql = "INSERT INTO payments (order_id, payment_method, payment_status, amount)



                        VALUES ('$order_id', '$payment_method', 'completed', '$cart_total')";

        mysqli_query($conn, $payment_sql);



        $move_items = "INSERT INTO order_items (order_id, product_id, quantity,price_at_purchase)

       

                       SELECT '$order_id', ci.product_id, ci.quantity, p.price

                       FROM cart_items ci

                       JOIN products p ON ci.product_id = p.product_id

                       JOIN carts c ON ci.cart_id = c.cart_id

                       WHERE c.user_id = '$user_id'";

       

        if (mysqli_query($conn, $move_items)) {

            $clear_cart = "DELETE ci FROM cart_items ci

                           JOIN carts c ON ci.cart_id = c.cart_id

                           WHERE c.user_id = '$user_id'";

            mysqli_query($conn, $clear_cart);

echo "
<script src='https://unpkg.com/sweetalert/dist/sweetalert.min.js'></script>
<script>
    setTimeout(function() {
        swal({
           title: 'Your order was successful!',
text: 'Your order has been registered. You can track the status from your account.',
icon: 'success',
button: 'OK',
}).then(function() {
        
             window.location.href ='order_details.php?order_id=$order_id';
        });
    }, 100);
</script>
";


            exit();

        }

    }

}

?>



<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Checkout - Anon eCommerce</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <style>

        :root {

            --primary-color: #ff8f9c;

            --secondary-color: #2b2d42;

            --bg-color: #f7f8fa;

            --text-color: #454545;

        }



        body {

            font-family: 'Poppins', sans-serif;

            background-color: var(--bg-color);

            color: var(--text-color);

            margin: 0;

            display: flex;

            justify-content: center;

            align-items: center;

            min-height: 100vh;

        }



        .checkout-card {

            background: #fff;

            width: 100%;

            max-width: 500px;

            padding: 40px;

            border-radius: 20px;

            box-shadow: 0 10px 25px rgba(0,0,0,0.05);

        }



        .checkout-header {

            text-align: center;

            margin-bottom: 30px;

        }



        .checkout-header h2 {

            margin: 0;

            font-size: 24px;

            color: var(--secondary-color);

        }



        .summary-box {

            background: var(--bg-color);

            padding: 20px;

            border-radius: 12px;

            margin-bottom: 30px;

            display: flex;

            justify-content: space-between;

            align-items: center;

        }



        .summary-box span {

            font-size: 14px;

            color: #888;

        }



        .summary-box strong {

            font-size: 22px;

            color: var(--primary-color);

        }



        .payment-methods h3 {

            font-size: 16px;

            margin-bottom: 15px;

            color: var(--secondary-color);

        }



        .method-option {

            display: flex;

            align-items: center;

            padding: 15px;

            border: 2px solid #eee;

            border-radius: 12px;

            margin-bottom: 12px;

            cursor: pointer;

            transition: 0.3s;

        }



        .method-option:hover {

            border-color: var(--primary-color);

        }



        .method-option input {

            margin-right: 15px;

            accent-color: var(--primary-color);

        }



        .method-option i {

            margin-right: 15px;

            font-size: 18px;

            color: #666;

        }



        .btn-confirm {

            background: var(--primary-color);

            color: white;

            border: none;

            width: 100%;

            padding: 15px;

            border-radius: 12px;

            font-size: 16px;

            font-weight: 600;

            cursor: pointer;

            transition: 0.3s;

            margin-top: 20px;

        }



        .btn-confirm:hover {

            background: #ff7685;

            transform: translateY(-2px);

            box-shadow: 0 5px 15px rgba(255, 143, 156, 0.3);

        }



        .back-link {

            display: block;

            text-align: center;

            margin-top: 20px;

            color: #888;

            text-decoration: none;

            font-size: 14px;

            transition: 0.3s;

        }



        .back-link:hover {

            color: var(--primary-color);

        }

    </style>

</head>

<body>



<div class="checkout-card">

    <div class="checkout-header">

        <i class="fas fa-shopping-bag" style="font-size: 40px; color: var(--primary-color); margin-bottom: 10px;"></i>

        <h2>Complete Your Order</h2>

    </div>



    <div class="summary-box">

        <span>Total Amount to Pay</span>

        <strong><?php echo number_format($cart_total, 2); ?> JD</strong>

    </div>

   

    <form method="POST">

        <div class="payment-methods">

            <h3>Payment Method</h3>

           

            <label class="method-option">

                <input type="radio" name="payment_method" value="Cash" checked>

                <i class="fas fa-money-bill-wave"></i>

                <span>Cash on Delivery</span>

            </label>



            <label class="method-option">

                <input type="radio" name="payment_method" value="Credit Card">

                <i class="fas fa-credit-card"></i>

                <span>Credit Card / Online</span>

            </label>

        </div>

       

        <button type="submit" name="place_order" class="btn-confirm" action="reviw_order.php">

            <i class="fas fa-check-circle"></i> Confirm Order

        </button>
 

        <a href="cart.php" class="back-link">

            <i class="fas fa-arrow-left"></i> Return to Shopping Cart

        </a>

    </form>

</div>    



</body>

</html> 


<form>
 
</form>