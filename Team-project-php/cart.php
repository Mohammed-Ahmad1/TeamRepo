<?php
session_start();
require_once "connect.php";
$db = new Database();
$conn = $db->conn;

$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

if (isset($_GET['remove'])) {
    $item_id = $_GET['remove'];
    $delete_query = "DELETE FROM cart_items WHERE cart_item_id = '$item_id'";
    mysqli_query($conn, $delete_query);
    header("Location: cart.php");
    exit();
}

$grand_total = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Your Shopping Cart</title>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<link rel="stylesheet" href="./assets/css/style-prefix.css">
<style>
.cart-container { padding: 30px; max-width: 1000px; margin: auto; }
.cart-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
.cart-table th { background: #ff8f9c; color: white; padding: 15px; text-align: center; }
.cart-table td { padding: 15px; border-bottom: 1px solid #eee; text-align: center; vertical-align: middle; }
.product-img { width: 80px; border-radius: 5px; }
.btn-remove { color: #eb4d4b; text-decoration: none; font-weight: bold; font-size: 14px; }
.btn-remove:hover { text-decoration: underline; }
.total-section { margin-top: 20px; text-align: right; font-size: 20px; font-weight: 600; }
.checkout-btn { background: #333; color: white; padding: 10px 25px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 15px; }
</style>
</head>
<body>

<div class="cart-container">
<h2 class="title" style="margin-bottom: 20px;">Shopping Cart</h2>

<table class="cart-table">
<thead>
<tr>
<th>Product</th>
<th>Price</th>
<th>Quantity</th>
<th>Subtotal</th>
<th>Action</th>
</tr>
</thead>
<tbody id="cart-body">

<?php if ($is_logged_in): ?>
    <?php
    $query = "SELECT ci.cart_item_id, ci.quantity, p.name, p.price, p.image 
              FROM cart_items ci 
              JOIN products p ON ci.product_id = p.product_id 
              JOIN carts c ON ci.cart_id = c.cart_id 
              WHERE c.user_id = '$user_id'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $subtotal = $row['price'] * $row['quantity'];
            $grand_total += $subtotal;
            ?>
            <tr>
                <td><img src="<?php echo $row['image']; ?>" class="product-img"><br><strong><?php echo $row['name']; ?></strong></td>
                <td>$<?php echo number_format($row['price'], 2); ?></td>
                <td><?php echo $row['quantity']; ?></td>
                <td>$<?php echo number_format($subtotal, 2); ?></td>
                <td><a href="cart.php?remove=<?php echo $row['cart_item_id']; ?>" class="btn-remove" onclick="confirmDelete(event, this.href)">Remove</a></td>
            </tr>
        <?php }
    } else {
        echo "<tr><td colspan='5'>Your cart is empty!</td></tr>";
    }
    ?>
<?php endif; ?>

</tbody>
</table>

<!-- Total Section for Logged-in Users -->
<?php if ($is_logged_in): ?>
<div class="total-section">
    <p>Total: $<?php echo number_format($grand_total, 2); ?></p>
    <a href="index.php" class="checkout-btn">Back</a>
    <?php if ($grand_total > 0): ?>
        <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
    <?php else: ?>
        <a href="index.php" class="checkout-btn">Proceed to Checkout</a>
    <?php endif; ?>
</div>
<?php endif; ?>

</div>

<script>
// SweetAlert confirm remove
function confirmDelete(event, url) {
    event.preventDefault();
    swal({
        title: "Are you sure?",
        text: "Once deleted, you will need to add it again!",
        icon: "warning",
        buttons: ["Cancel", "Remove"],
        dangerMode: true,
    }).then((willDelete) => {
        if (willDelete) window.location.href = url;
    });
}

// Guest cart handling
const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
if (!isLoggedIn) {
    const cartBody = document.getElementById("cart-body");
    let cart = JSON.parse(localStorage.getItem("guest_cart")) || [];
    let total = 0;

    cartBody.innerHTML = cart.length ? "" : `<tr><td colspan="5">Your cart is empty! <a href="index.php">Go Shopping</a></td></tr>`;

    cart.forEach((item, index) => {
        let subtotal = item.price * item.quantity;
        total += subtotal;

        cartBody.innerHTML += `
        <tr>
            <td><img src="./${item.image}" class="product-img"><br><strong>${item.name}</strong></td>
            <td>$${Number(item.price).toFixed(2)}</td>
            <td>${item.quantity}</td>
            <td>$${subtotal.toFixed(2)}</td>
            <td><a href="#" class="btn-remove" onclick="removeGuestItem(${index})">Remove</a></td>
        </tr>`;
    });

    // Total section for Guest
    const totalDiv = document.createElement("div");
    totalDiv.className = "total-section";
    totalDiv.innerHTML = `
        <p>Total: $${total.toFixed(2)}</p>
        <a href="index.php" class="checkout-btn">Back</a>
        <a href="javascript:void(0);" onclick="alertLogin()" class="checkout-btn">Proceed to Checkout</a>
    `;
    document.querySelector(".cart-container").appendChild(totalDiv);
}

// Guest remove item
function removeGuestItem(index) {
    swal({
        title: "Are you sure?",
        text: "Once removed, you will need to add it again!",
        icon: "warning",
        buttons: ["Cancel", "Remove"],
        dangerMode: true,
    }).then((willDelete) => {
        if (willDelete) {
            let cart = JSON.parse(localStorage.getItem("guest_cart")) || [];
            cart.splice(index, 1);
            localStorage.setItem("guest_cart", JSON.stringify(cart));
            location.reload();
        }
    });
}

// Guest login alert
function alertLogin() {
    swal({
        title: "Login Required",
        text: "You must log in to complete your purchase.",
        icon: "info",
        buttons: {
            cancel: "Continue Browsing",
            login: { text: "Login Now", value: "login" }
        }
    }).then((value) => {
        if (value === "login") window.location.href = "login.php";
    });
}
</script>
</body>
</html>