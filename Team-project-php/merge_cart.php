
<?php
session_start();
require_once "./connect.php"; 
$db = new Database();
$conn = $db->conn;

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (isset($_SESSION['user_id']) && isset($data['cart'])) {
    $user_id = $_SESSION['user_id'];
    
    $guest_cart = json_decode($data['cart'], true);

    $res = $conn->query("SELECT cart_id FROM carts WHERE user_id = '$user_id'");
    if ($res->num_rows > 0) {
        $cart_id = $res->fetch_assoc()['cart_id'];
    } else {
        $conn->query("INSERT INTO carts (user_id) VALUES ('$user_id')");
        $cart_id = $conn->insert_id;
    }

    foreach ($guest_cart as $item) {
        $p_id = $item['id'];
        $qty = (int)$item['quantity'];

        $check = $conn->query("SELECT * FROM cart_items WHERE cart_id = '$cart_id' AND product_id = '$p_id'");
        if ($check->num_rows > 0) {
            $conn->query("UPDATE cart_items SET quantity = quantity + $qty WHERE cart_id = '$cart_id' AND product_id = '$p_id'");
        } else {
            $conn->query("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES ('$cart_id', '$p_id', '$qty')");
        }
    }
    echo json_encode(['status' => 'success']);
}
?>
