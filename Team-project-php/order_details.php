<?php
session_start();
require_once "connect.php";
$db = new Database();
$conn = $db->conn;

if (!isset($_GET['order_id'])) {
    echo "Invalid request!";
    exit;
}

$order_id = intval($_GET['order_id']); 

$order_query = "SELECT o.order_id, o.user_id, o.order_status, o.total_amount, o.created_at,
                       CONCAT(u.first_name, ' ', u.last_name) AS customer_name, 
                       u.email AS customer_email
                FROM orders o
                JOIN users u ON o.user_id = u.user_id
                WHERE o.order_id = ?";

$stmt_order = $conn->prepare($order_query);
$stmt_order->bind_param("i", $order_id);
$stmt_order->execute();
$order_result = $stmt_order->get_result();

if ($order_result->num_rows == 0) {
    echo "Order not found!";
    exit;
}

$order = $order_result->fetch_assoc();

$items_query = "SELECT oi.quantity, 
                       p.product_id, 
                       p.name AS product_name, 
                       p.image, 
                       p.price AS product_price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.product_id
                WHERE oi.order_id = ?";

$stmt_items = $conn->prepare($items_query);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$items_result = $stmt_items->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order #<?php echo htmlspecialchars($order['order_id']); ?> Details</title>
    <link rel="stylesheet" href="./assets/css/style-prefix.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; padding: 20px; }
        .order-container { max-width: 900px; margin: auto; padding: 30px; background: #fff; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); }
        .order-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .order-table th, .order-table td { padding: 15px; border: 1px solid #eee; text-align: center; }
        .order-table th { background-color: #f8f9fa; }
        .product-img { width: 70px; height: 70px; object-fit: cover; border-radius: 8px; }
        h2 { text-align: center; color: #333; }
        .back-btn { display: inline-block; margin-top: 20px; padding: 12px 25px; background: #007bff; color: #fff; text-decoration: none; border-radius: 6px; }
        .back-btn:hover { background: #0056b3; }
    </style>
</head>
<body>

<div class="order-container">
    <h2>Order #<?php echo htmlspecialchars($order['order_id']); ?> Details</h2>
    
    

    <table class="order-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Image</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $calculated_total = 0;
            while($item = $items_result->fetch_assoc()):
                $subtotal = $item['product_price'] * $item['quantity'];
                $calculated_total += $subtotal;
            ?>
            <tr>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td>
                    <?php if (!empty($item['image'])): ?>
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" class="product-img" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                    <?php else: ?>
                        <img src="assets/images/no-image.jpg" class="product-img" alt="No image">
                    <?php endif; ?>
                </td>
                <td>$<?php echo number_format($item['product_price'], 2); ?></td>
                <td><?php echo $item['quantity']; ?></td>
                <td>$<?php echo number_format($subtotal, 2); ?></td>
            </tr>
            <?php endwhile; ?>
            
            <?php if ($items_result->num_rows == 0): ?>
            <tr>
                <td colspan="5">No items found in this order.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h3 style="text-align:right; margin-top: 25px; font-size: 1.5em;">
        Total: $<?php echo number_format($calculated_total, 2); ?>
        <?php if (isset($order['total_amount']) && $order['total_amount'] != $calculated_total): ?>
            <small style="color:#888; display:block; font-size:0.7em;">(Original: $<?php echo number_format($order['total_amount'], 2); ?>)</small>
        <?php endif; ?>
    </h3>

    <div style="text-align:center;">
        <a href="index.php" class="back-btn">← Back to Shop</a>
    </div>
</div>

</body>
</html>