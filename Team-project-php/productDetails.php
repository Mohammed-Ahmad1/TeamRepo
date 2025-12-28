<?php
session_start();
require_once "./connect.php"; 
$db = new Database();
$conn = $db->conn;

/*add*/
if (isset($_POST['add_now'])) {
    $product_id = $_POST['product_id'];
    $quantity = (int)$_POST['quantity']; 

    if (!isset($_SESSION['user_id'])) {
        echo "<script>alert('Please Login First'); window.location.href='login.php';</script>";
        exit();
    }

    $user_id = $_SESSION['user_id'];


    $check_user = $conn->query("SELECT user_id FROM users WHERE user_id = '$user_id'");
    if ($check_user->num_rows == 0) {
        session_destroy();
        echo "<script>alert('Session expired. Please login again.'); window.location.href='login.php';</script>";
        exit();
    }


    $sql_cart = "SELECT cart_id FROM carts WHERE user_id = '$user_id'";
    $result_cart = $conn->query($sql_cart); 

    if ($result_cart && $result_cart->num_rows > 0) {
        $row_cart = $result_cart->fetch_assoc();
        $cart_id = $row_cart['cart_id'];
    } else {
        $sql_create_cart = "INSERT INTO carts (user_id) VALUES ('$user_id')";
        if ($conn->query($sql_create_cart)) {
            $cart_id = $conn->insert_id;
        } else {
            die("Database Error: " . $conn->error);
        }
    }

    $sql_check_item = "SELECT * FROM cart_items WHERE cart_id = '$cart_id' AND product_id = '$product_id'";
    $result_item = $conn->query($sql_check_item);

    if ($result_item && $result_item->num_rows > 0) {
        $sql_action = "UPDATE cart_items SET quantity = quantity + $quantity 
                       WHERE cart_id = '$cart_id' AND product_id = '$product_id'";
    } else {
        $sql_action = "INSERT INTO cart_items (cart_id, product_id, quantity) 
                       VALUES ('$cart_id', '$product_id', '$quantity')";
    }

    if ($conn->query($sql_action)) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $product_id . "&added=success");
        exit();
    } else {
        die("Error: " . $conn->error);
    }
}
/*add*/



if (isset($_GET['id'])) {
    $p_id = $_GET['id']; 
    $sql = "SELECT * FROM products WHERE product_id = ?";
    $stmt_p = $conn->prepare($sql);
        if (!$stmt_p) {
        die(" error : " . $conn->error);
    }
    $stmt_p->bind_param("i", $p_id);
    $stmt_p->execute();
    $result = $stmt_p->get_result();

    if ($result && $result->num_rows > 0) {
        $product = $result->fetch_assoc();
    } else {
        die("Product Not Found");
    }
} else {
    header("Location: index.php");
    exit;
}

$can_review = false;
if (isset($_SESSION['user_id'])) {
    $u_id = $_SESSION['user_id'];
    $check_query = "SELECT ci.product_id 
                    FROM order_items ci 
                    JOIN orders o ON ci.order_id = o.order_id 
                    WHERE o.user_id = ? 
                    AND ci.product_id = ? 
                    AND o.order_status = 'completed'"; 
    $stmt_check = $conn->prepare($check_query);
    $stmt_check->bind_param("ii", $u_id, $p_id);
    $stmt_check->execute();
    $check_result = $stmt_check->get_result();

    if ($check_result->num_rows > 0) {
        $can_review = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_comment'])) {
    if (isset($_SESSION['user_id']) && !empty($_POST['comment_text'])) {
        $user_id = $_SESSION['user_id'];
        $comment_input = $_POST['comment_text'];

        $comment_sql = "INSERT INTO comments (product_id, user_id, comment) VALUES (?, ?, ?)";
        $stmt_comm = $conn->prepare($comment_sql);
        $stmt_comm->bind_param("iis", $p_id, $user_id, $comment_input);
        
        if ($stmt_comm->execute()) {
            header("Location: productDetails.php?id=" . $p_id);
            exit;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product['name']; ?> | Details</title>
    <link rel="stylesheet" href="./anon-ecommerce-website/assets/css/style-prefix.css">
      <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --salmon-pink: hsl(353, 100%, 78%);
            --onyx: hsl(0, 0%, 25%);
            --cultured: hsl(0, 0%, 93%);
            --white: hsl(0, 0%, 100%);
        }
        body { font-family: 'Poppins', sans-serif; background: var(--cultured); margin: 0; padding: 20px; }
        .details-container { max-width: 1100px; margin: 40px auto; display: flex; gap: 40px; background: var(--white); padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .product-image-box { flex: 1; text-align: center; }
        .product-image-box img { width: 100%; max-width: 450px; border-radius: 15px; }
        .product-info-box { flex: 1.2; }
        .product-title { font-size: 2rem; color: var(--onyx); margin-bottom: 10px; }
        .product-price { font-size: 1.5rem; color: var(--salmon-pink); font-weight: 700; margin-bottom: 20px; }
        .description-text { color: #666; line-height: 1.7; margin-bottom: 30px; }
        .comment-section { max-width: 1100px; margin: 20px auto; background: var(--white); padding: 30px; border-radius: 20px; }
        .comment-item { border-bottom: 1px solid #eee; padding: 15px 0; }
        .comment-user { font-weight: 600; color: var(--onyx); font-size: 0.9rem; }
        .comment-date { font-size: 0.75rem; color: #999; }
        textarea { width: 100%; border: 1.5px solid #eee; border-radius: 10px; padding: 15px; font-family: inherit; margin-bottom: 15px; }
        .btn-action { background: var(--salmon-pink); color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-action:hover { background: var(--onyx); }
        .back-link { text-decoration: none; color: var(--salmon-pink); font-size: 0.9rem; display: inline-block; margin-bottom: 20px; }
    </style>
</head>
<body>
<?php if (isset($_GET['added']) && $_GET['added'] == 'success'): ?>
    <script>
        
        swal({
            title: "Add success",
Text: "The product has been successfully added to the shopping cart",
            icon: "success",
            button: "Ok",
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    </script>
<?php endif; ?>

<div class="details-container">

<div class="product-image-box">
    <img src="./<?php echo $product['image']; ?>" 
         alt="<?php echo htmlspecialchars($product['name']); ?>" 
         class="product-img default">
</div>

<div class="product-info-box">
    <a href="index.php" class="back-link">← Back to Shop</a>
    <h1 class="product-title"><?php echo $product['name']; ?></h1>
    
    <!-- ✅ Add id & data-price for JS -->
    <div id="unit-price" class="product-price" data-price="<?php echo (float)$product['price']; ?>">
        <?php echo $product['price']; ?> JD
    </div>

    <p class="description-text">
        <?php echo !empty($product['description']) ? $product['description'] : "Luxury and elegance combined in this high-quality timepiece, designed to fit your unique style."; ?>
    </p>

    <form method="POST">
        <input type="hidden" name="product_id" value="<?php echo $p_id; ?>">

        <div style="display: flex; gap: 15px; align-items: center; margin-bottom: 15px;">
            <!-- ✅ Add id="qty-selector" -->
            <input type="number" id="qty-selector" name="quantity" value="1" min="1" 
                   style="width: 60px; padding: 10px; border: 1.5px solid #eee; border-radius: 8px; text-align: center;">

            <!-- ✅ Add total display (optional but nice) -->
            <span id="total-display" style="font-weight: 600; color: #ff8f9c; font-size: 1.1rem;">
                Total: <?php echo $product['price']; ?> JD
            </span>
        </div>

        <button type="submit" name="add_now" class="add-cart-btn" 
                style="background: #ff8f9c; color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%;">
            ADD TO CART
        </button>
    </form>
</div>
   </div>

<div class="comment-section">
    <h3 style="margin-top: 0;">Customer Reviews</h3>

    <?php if ($can_review): ?>
        <form method="POST">
            <textarea name="comment_text" placeholder="Share your experience with this product..." required></textarea>
            <button type="submit" name="submit_comment" class="btn-action">Post Review</button>
        </form>
    <?php elseif (isset($_SESSION['user_id'])): ?>
        <div style="background: #fff5f6; border-left: 4px solid var(--salmon-pink); padding: 15px; font-size: 0.85rem;">
            ⚠️ Reviews are restricted to verified purchasers of this item.
        </div>
    <?php endif; ?>

    <div class="old-comments" style="margin-top: 30px;">
        <?php
        $get_comm_sql = "SELECT c.comment, c.created_at, u.first_name FROM comments c 
                         JOIN users u ON c.user_id = u.user_id 
                         WHERE c.product_id = ? ORDER BY c.created_at DESC";
        $stmt_get = $conn->prepare($get_comm_sql);
        $stmt_get->bind_param("i", $p_id);
        $stmt_get->execute();
        $comments_result = $stmt_get->get_result();

        if ($comments_result->num_rows > 0):
            while ($comm = $comments_result->fetch_assoc()): ?>
                <div class="comment-item">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span class="comment-user"><?php echo htmlspecialchars($comm['first_name']); ?></span>
                        <span class="comment-date"><?php echo date('M d, Y', strtotime($comm['created_at'])); ?></span>
                    </div>
                    <p style="margin: 8px 0 0; color: #444; font-size: 0.9rem;"><?php echo htmlspecialchars($comm['comment']); ?></p>
                </div>
            <?php endwhile;
        else: ?>
            <p style="color: #999; font-size: 0.9rem;">No reviews yet. Be the first to share your thoughts!</p>
        <?php endif; ?>
    </div>
</div>

<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
</body>
</html>



<script>
// 1. حساب السعر الفوري عند تغيير الكمية
const qtyInput = document.getElementById('qty-selector');
const unitPrice = parseFloat(document.getElementById('unit-price').getAttribute('data-price'));
const totalDisplay = document.getElementById('total-display');

qtyInput.addEventListener('input', function() {
    let qty = parseInt(this.value);
    if (isNaN(qty) || qty < 1) qty = 1; 
    
    const totalPrice = (unitPrice * qty).toFixed(2);
    totalDisplay.innerText = "Total: " + totalPrice + " JD";
});

// 2. تحديث عداد السلة في الهيدر
function updateCartIconCount() {
    const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    const countElement = document.querySelector('.header-user-actions .action-btn .count');
    
    if (!isLoggedIn && countElement) {
        let cart = JSON.parse(localStorage.getItem('guest_cart')) || [];
        let total = cart.reduce((sum, item) => sum + parseInt(item.quantity), 0);
        countElement.innerText = total;
    }
}

document.addEventListener('DOMContentLoaded', updateCartIconCount);

// 3. التحكم في زر الإضافة (زوار vs مسجلين)
document.querySelector('.custom-add-btn').addEventListener('click', function(e) {
    const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    
    if (!isLoggedIn) {
        e.preventDefault(); // منع الفورم من الإرسال للـ PHP

        const qtyValue = document.getElementById('qty-selector').value;

        const product = {
            id: this.dataset.id,
            name: this.dataset.name,
            price: this.dataset.price,
            image: this.dataset.image,
            quantity: qtyValue
        };

        let cart = JSON.parse(localStorage.getItem('guest_cart')) || [];
        let found = cart.find(item => item.id === product.id);
        
        if (found) {
            found.quantity = parseInt(found.quantity) + parseInt(product.quantity);
        } else {
            cart.push(product);
        }
        
        localStorage.setItem('guest_cart', JSON.stringify(cart));
        alert("Success: Added to guest cart!");
        updateCartIconCount();
    }
});
</script>