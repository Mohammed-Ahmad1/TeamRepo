<?php
session_start();
require_once "./connect.php";
$db = new Database();
$conn = $db->conn;

/*add to cart*/
if (isset($_POST['add_now'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    if (!isset($_SESSION['user_id'])) {
        echo "<script>alert('Please Login First'); window.location.href='login.php';</script>";
        exit();
    }

    $user_id = $_SESSION['user_id'];

    // Validate user exists
    $check_user = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $check_user->bind_param("i", $user_id);
    $check_user->execute();
    if ($check_user->get_result()->num_rows == 0) {
        session_destroy();
        echo "<script>alert('Session expired. Please login again.'); window.location.href='login.php';</script>";
        exit();
    }

    // Get or create cart
    $sql_cart = "SELECT cart_id FROM carts WHERE user_id = ?";
    $stmt_cart = $conn->prepare($sql_cart);
    $stmt_cart->bind_param("i", $user_id);
    $stmt_cart->execute();
    $result_cart = $stmt_cart->get_result();

    if ($result_cart->num_rows > 0) {
        $row_cart = $result_cart->fetch_assoc();
        $cart_id = $row_cart['cart_id'];
    } else {
        $sql_create_cart = "INSERT INTO carts (user_id) VALUES (?)";
        $stmt_new_cart = $conn->prepare($sql_create_cart);
        $stmt_new_cart->bind_param("i", $user_id);
        if ($stmt_new_cart->execute()) {
            $cart_id = $stmt_new_cart->insert_id;
        } else {
            die("Database Error: " . $conn->error);
        }
    }

    // Add or update cart item
    $sql_check_item = "SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ?";
    $stmt_check = $conn->prepare($sql_check_item);
    $stmt_check->bind_param("ii", $cart_id, $product_id);
    $stmt_check->execute();
    $result_item = $stmt_check->get_result();

    if ($result_item->num_rows > 0) {
        $sql_action = "UPDATE cart_items SET quantity = quantity + ? WHERE cart_id = ? AND product_id = ?";
        $stmt_update = $conn->prepare($sql_action);
        $stmt_update->bind_param("iii", $quantity, $cart_id, $product_id);
        $stmt_update->execute();
    } else {
        $sql_action = "INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_action);
        $stmt_insert->bind_param("iii", $cart_id, $product_id, $quantity);
        $stmt_insert->execute();
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $product_id . "&added=success");
    exit();
}
/*add to cart*/

/*search, filter & pagination*/
$search_input = $_GET['search'] ?? '';
$category_id  = $_GET['category_id'] ?? '';
$has_discount = $_GET['has_discount'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$items_per_page = 5;
$offset = ($page - 1) * $items_per_page;

// ✅ FIXED: Use only discount_percent from your actual DB structure
$base_sql = "SELECT 
    p.product_id, 
    p.name, 
    p.price AS original_price,
    p.image,
    p.category_id,
    p.status,
    d.discount_id,
    d.discount_percent,
    d.start_date,
    d.end_date,
    d.is_active,
    CASE 
        WHEN d.discount_id IS NOT NULL 
             AND d.is_active = 1 
             AND CURDATE() BETWEEN d.start_date AND d.end_date
        THEN p.price * (1 - d.discount_percent / 100)
        ELSE p.price 
    END AS final_price
FROM products p
LEFT JOIN discounts d ON p.product_id = d.product_id 
WHERE p.status = 'active'";

$params = [];
$types = "";

// Filters
if (!empty($search_input)) {
    $base_sql .= " AND p.name LIKE ?";
    $params[] = "%$search_input%";
    $types .= "s";
}

if (!empty($category_id)) {
    $base_sql .= " AND p.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

if ($has_discount === 'yes') {
    $base_sql .= " AND d.discount_id IS NOT NULL 
                  AND d.is_active = 1 
                  AND CURDATE() BETWEEN d.start_date AND d.end_date";
} elseif ($has_discount === 'no') {
    $base_sql .= " AND (d.discount_id IS NULL 
                  OR d.is_active = 0 
                  OR CURDATE() NOT BETWEEN d.start_date AND d.end_date)";
}

// Count total
$count_sql = "SELECT COUNT(*) as total FROM ($base_sql) AS filtered";
$stmt_count = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_items = $stmt_count->get_result()->fetch_assoc()['total'] ?? 0;
$total_pages = ceil($total_items / $items_per_page);

// Fetch products with pagination
$sql = $base_sql . " ORDER BY p.product_id DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $items_per_page;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $u_id = $_SESSION['user_id'];
    $sql_count = "SELECT SUM(ci.quantity) as total_items 
                  FROM cart_items ci 
                  JOIN carts c ON ci.cart_id = c.cart_id 
                  WHERE c.user_id = ?";
    $stmt_cnt = $conn->prepare($sql_count);
    $stmt_cnt->bind_param("i", $u_id);
    $stmt_cnt->execute();
    $res = $stmt_cnt->get_result();
    if ($res && $row = $res->fetch_assoc()) {
        $cart_count = $row['total_items'] ? (int)$row['total_items'] : 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
  <title>Anon - eCommerce Website</title>

  <!-- favicon -->
  <link rel="shortcut icon" href="./assets/images/logo/favicon.ico" type="image/x-icon">

  <!-- custom css -->
  <link rel="stylesheet" href="./assets/css/style-prefix.css">

  <!-- google font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<style>
    .auth-link {
        font-size: 14px;
        font-weight: 500;
        color: var(--sonic-silver);
        transition: color 0.3s ease;
        text-transform: uppercase;
    }
    .auth-link:hover { color: #ff8f9c; }
    @media (max-width: 768px) {
        .logo-auth-group { flex-direction: column; gap: 5px; }
    }

    .filter-container {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-start;
        gap: 10px;
        padding: 12px 15px;
        margin-bottom: 20px;
        background-color: #f8f9fa;
        border-radius: 8px;
    }
    .filter-input.search-name { flex: 1 1 300px; min-width: 200px; }
    .filter-select { flex: 0 0 150px; }
    .filter-btn {
        padding: 8px 18px;
        background-color: #ff8f9c;
        color: #fff;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: 0.3s;
    }
    .filter-btn:hover { background-color: #ff6f84; }
    @media (max-width: 768px) {
        .filter-container { flex-direction: column; align-items: stretch; }
        .filter-input.search-name, .filter-select, .filter-btn { width: 100%; flex: none; }
    }

    /* PAGINATION */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 30px;
        gap: 6px;
        flex-wrap: wrap;
    }
    .pagination a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 38px;
        height: 38px;
        padding: 0 10px;
        border-radius: 20px;
        text-decoration: none;
        font-weight: 500;
        font-size: 15px;
        color: #333;
        background: #fff;
        border: 1px solid #e0e0e0;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        transition: all 0.25s ease;
        cursor: pointer;
    }
    .pagination a:hover:not(.active) {
        background-color: #f8f9fa;
        border-color: #ccc;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.08);
    }
    .pagination .active {
        background-color: #ff8f9c;
        color: white;
        border-color: #ff8f9c;
        box-shadow: 0 3px 10px rgba(255, 143, 156, 0.4);
        transform: translateY(-1px);
        font-weight: 600;
    }
    .pagination .arrow {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }
    .pagination .arrow:hover {
        background-color: #f5f5f5;
        border-color: #ccc;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.08);
    }
    @media (max-width: 500px) {
        .pagination { gap: 4px; }
        .pagination a { min-width: 34px; height: 34px; font-size: 14px; padding: 0 8px; }
    }

    /* SALE BADGE */
    .sale-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #ff4757;
        color: white;
        font-size: 12px;
        font-weight: bold;
        padding: 4px 8px;
        border-radius: 4px;
        z-index: 2;
    }
</style>

<body>
  <div class="overlay" data-overlay></div>

  <?php if (isset($_GET['added']) && $_GET['added'] == 'success'): ?>
    <script>
        swal({
            title: "Add Success",
            text: "The product has been successfully added to your cart.",
            icon: "success",
            button: "OK",
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    </script>
  <?php endif; ?>

  <header>
    <div class="header-main">
      <div class="container">
        <a href="index.php" class="header-logo">
          <img src="./assets/images/logo/logo.svg" alt="Anon's logo" width="120" height="36">
        </a>

        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
          <div class="logo-auth-group" style="display: flex; align-items: center; gap: 20px;">
            <div class="auth-buttons" style="display: flex; gap: 10px;">
              <a href="Login.php" class="auth-link">Login</a>
              <span style="color: #ccc;">|</span>
              <a href="SignUp.php" class="auth-link">Register</a>
            </div>
          </div>

          <div class="filter-container">
            <form method="get" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; width: 100%;">
              <input type="search" name="search" class="filter-input search-name"
                     placeholder="Search by product name..." value="<?= htmlspecialchars($search_input); ?>">

              <select name="category_id" class="filter-select">
                <option value="">All Categories</option>
                <?php
                $cat_result = $conn->query("SELECT category_id, name FROM categories ORDER BY name ASC");
                if ($cat_result && $cat_result->num_rows > 0) {
                    while ($cat_row = $cat_result->fetch_assoc()) {
                        $selected = ($category_id == $cat_row['category_id']) ? 'selected' : '';
                        echo "<option value='{$cat_row['category_id']}' $selected>{$cat_row['name']}</option>";
                    }
                }
                ?>
              </select>

              <select name="has_discount" class="filter-select">
                <option value="">Any Discount</option>
                <option value="yes" <?= ($has_discount === 'yes') ? 'selected' : ''; ?>>With Discount</option>
                <option value="no" <?= ($has_discount === 'no') ? 'selected' : ''; ?>>Without Discount</option>
              </select>

              <button type="submit" class="filter-btn">
                <ion-icon name="filter-outline"></ion-icon> Apply Filters
              </button>
            </form>
          </div>

          <div class="header-user-actions">
            <a href="./edit_profile.php" class="action-btn" title="Personal information">
              <ion-icon name="person-outline"></ion-icon>
            </a>
            <a href="./cart.php" class="action-btn" title="Shopping Cart">
              <ion-icon name="bag-handle-outline"></ion-icon>
              <span class="count"><?= $cart_count ?></span>
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
              <a href="./LandingPage.php" class="action-btn" title="Logout">
                <ion-icon name="log-out-outline"></ion-icon>
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="mobile-bottom-navigation">
      <button class="action-btn"><ion-icon name="bag-handle-outline"></ion-icon></button>
      <button class="action-btn"><a href="./edit_profile.php"><ion-icon name="person-outline"></ion-icon></a></button>
      <button class="action-btn"><ion-icon name="heart-outline"></ion-icon></button>
      <a href="./LandingPage.php" class="action-btn" title="Logout"><ion-icon name="log-out-outline"></ion-icon></a>
    </div>
  </header>

  <main>
    <div class="banner">
      <div class="container">
        <div class="slider-container has-scrollbar">
          <div class="slider-item">
            <img src="./assets/images/banner-1.jpg" alt="women's latest fashion sale" class="banner-img">
            <div class="banner-content">
              <p class="banner-subtitle">Trending item</p>
              <h2 class="banner-title">Women's latest fashion sale</h2>
              <p class="banner-text">starting at &dollar; <b>20</b>.00</p>
              <a href="#" class="banner-btn">Shop now</a>
            </div>
          </div>
          <div class="slider-item">
            <img src="./assets/images/banner-2.jpg" alt="modern sunglasses" class="banner-img">
            <div class="banner-content">
              <p class="banner-subtitle">Trending accessories</p>
              <h2 class="banner-title">Modern sunglasses</h2>
              <p class="banner-text">starting at &dollar; <b>15</b>.00</p>
              <a href="#" class="banner-btn">Shop now</a>
            </div>
          </div>
          <div class="slider-item">
            <img src="./assets/images/banner-3.jpg" alt="new fashion summer sale" class="banner-img">
            <div class="banner-content">
              <p class="banner-subtitle">Sale Offer</p>
              <h2 class="banner-title">New fashion summer sale</h2>
              <p class="banner-text">starting at &dollar; <b>29</b>.99</p>
              <a href="#" class="banner-btn">Shop now</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="product-container">
      <div class="container">
        <div class="product-main">
          <h2 class="title">New Products</h2>

          <div class="product-grid">
            <?php
            if ($result && $result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
                    $id = $row['product_id'];
                    $name = htmlspecialchars($row['name']);
                    $original_price = (float)$row['original_price'];
                    $final_price = (float)$row['final_price'];
                    $imagePath = htmlspecialchars($row['image']);
                    $has_discount = (
                        $row['discount_id'] &&
                        $row['is_active'] == 1 &&
                        $row['start_date'] <= date('Y-m-d') &&
                        $row['end_date'] >= date('Y-m-d') &&
                        $original_price > $final_price
                    );
            ?>
            <div class="showcase">
              <div class="showcase-banner" 
                   onclick="window.location.href='./productDetails.php?id=<?= $id ?>';"
                   style="cursor: pointer; position: relative;">
                <img src="./<?= $imagePath ?>" alt="<?= $name ?>" width="300" class="product-img default">
                <img src="./<?= $imagePath ?>" alt="<?= $name ?>" width="300" class="product-img hover">
                
                <?php if ($has_discount): ?>
                  <div class="sale-badge">Sale</div>
                <?php endif; ?>
              </div>

              <div class="showcase-content">
                <a href="./productDetails.php?id=<?= $id ?>">
                  <h3 class="showcase-title"><?= $name ?></h3>
                </a>

                <div class="price-box" style="margin: 10px 0;">
                  <?php if ($has_discount): ?>
                    <p style="text-decoration: line-through; color: #999; margin: 0; font-size: 0.9rem;">
                      $<?= number_format($original_price, 2) ?>
                    </p>
                    <p class="price" style="color: #ff8f9c; font-weight: 700; margin: 4px 0 0; font-size: 1.1rem;">
                      $<?= number_format($final_price, 2) ?>
                    </p>
                  <?php else: ?>
                    <p class="price" style="color: #ff8f9c; font-weight: 700; margin: 0; font-size: 1.1rem;">
                      $<?= number_format($original_price, 2) ?>
                    </p>
                  <?php endif; ?>
                </div>

                <form method="POST">
                  <div class="showcase-controls" style="display: flex; gap: 8px; margin-top: 10px; align-items: center; justify-content: center;">
                    <input type="number" name="quantity" value="1" min="1"
                           style="width: 45px; border: 1px solid #eee; text-align: center; border-radius: 5px; height: 35px;">
                    <input type="hidden" name="product_id" value="<?= $id ?>">
                    <button type="submit" name="add_now" class="add-cart-btn custom-add-btn"
                            style="background: #ff8f9c; color: white; border: none; padding: 0 15px; border-radius: 5px; height: 35px; cursor: pointer; font-size: 12px; font-weight: 600; flex-grow: 1;"
                            data-id="<?= $id ?>"
                            data-name="<?= $name ?>"
                            data-price="<?= $final_price ?>"
                            data-image="<?= $imagePath ?>">
                      ADD TO CART
                    </button>
                  </div>
                </form>
              </div>
            </div>
            <?php
                endwhile;
            else:
                echo "<p>No products found matching your criteria.</p>";
            endif;
            ?>
          </div>

          <!-- Pagination -->
          <?php if ($total_pages > 1): ?>
            <div class="pagination">
              <?php
              $params_arr = [];
              if ($search_input !== '') $params_arr[] = "search=" . urlencode($search_input);
              if ($category_id !== '') $params_arr[] = "category_id=" . urlencode($category_id);
              if ($has_discount !== '') $params_arr[] = "has_discount=" . urlencode($has_discount);
              $base_query = implode('&', $params_arr);

              $makeUrl = function($page_num) use ($base_query) {
                  $url = '?';
                  if ($base_query) $url .= $base_query;
                  if ($page_num > 1) {
                      $url .= ($base_query ? '&' : '') . "page=$page_num";
                  }
                  return $url;
              };

              $visible_pages = 5;
              $half = floor($visible_pages / 2);
              $start = max(1, $page - $half);
              $end = min($total_pages, $start + $visible_pages - 1);
              if ($end - $start + 1 < $visible_pages) {
                  $start = max(1, $end - $visible_pages + 1);
              }

              // First + ellipsis
              if ($start > 1) {
                  echo '<a href="' . htmlspecialchars($makeUrl(1)) . '" class="arrow">&laquo;</a>';
                  if ($start > 2) echo '<span style="color:#aaa; padding:0 4px;">&hellip;</span>';
              }

              // Pages
              for ($i = $start; $i <= $end; $i++) {
                  $url = $makeUrl($i);
                  $active = ($i == $page) ? ' class="active"' : '';
                  echo '<a href="' . htmlspecialchars($url) . '"' . $active . '>' . $i . '</a>';
              }

              // Last + ellipsis
              if ($end < $total_pages) {
                  if ($end < $total_pages - 1) echo '<span style="color:#aaa; padding:0 4px;">&hellip;</span>';
                  echo '<a href="' . htmlspecialchars($makeUrl($total_pages)) . '" class="arrow">&raquo;</a>';
              }
              ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <footer>
    <div class="footer-category">
      <div class="container">
        <h2 class="footer-category-title">Brand directory</h2>
        <div class="footer-category-box">
          <h3 class="category-box-title">Fashion :</h3>
          <a href="#" class="footer-category-link">T-shirt</a>
          <a href="#" class="footer-category-link">Shirts</a>
          <a href="#" class="footer-category-link">shorts & jeans</a>
          <a href="#" class="footer-category-link">jacket</a>
          <a href="#" class="footer-category-link">dress & frock</a>
          <a href="#" class="footer-category-link">innerwear</a>
          <a href="#" class="footer-category-link">hosiery</a>
        </div>
        <div class="footer-category-box">
          <h3 class="category-box-title">footwear :</h3>
          <a href="#" class="footer-category-link">sport</a>
          <a href="#" class="footer-category-link">formal</a>
          <a href="#" class="footer-category-link">Boots</a>
          <a href="#" class="footer-category-link">casual</a>
          <a href="#" class="footer-category-link">cowboy shoes</a>
          <a href="#" class="footer-category-link">safety shoes</a>
          <a href="#" class="footer-category-link">Party wear shoes</a>
          <a href="#" class="footer-category-link">Branded</a>
          <a href="#" class="footer-category-link">Firstcopy</a>
          <a href="#" class="footer-category-link">Long shoes</a>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <div class="container">
        <img src="./assets/images/payment.png" alt="payment method" class="payment-img">
        <p class="copyright">
          Copyright &copy; <a href="#">Anon</a> all rights reserved.
        </p>
      </div>
    </div>
  </footer>

  <script src="./assets/js/script.js"></script>
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

  <script>
    function updateCartIconCount() {
      const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
      const countElement = document.querySelector('.header-user-actions .action-btn .count');
      if (!isLoggedIn && countElement) {
        let cart = JSON.parse(localStorage.getItem('guest_cart')) || [];
        let total = cart.reduce((sum, item) => sum + parseInt(item.quantity, 10), 0);
        countElement.innerText = total || 0;
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      updateCartIconCount();

      // Guest cart handling
      document.querySelectorAll('.custom-add-btn').forEach(button => {
        button.addEventListener('click', function(e) {
          const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
          if (!isLoggedIn) {
            e.preventDefault();
            const qtyInput = this.closest('.showcase-controls').querySelector('input[name="quantity"]');
            const qtyValue = parseInt(qtyInput.value) || 1;
            const product = {
              id: this.dataset.id,
              name: this.dataset.name,
              price: parseFloat(this.dataset.price),
              image: this.dataset.image,
              quantity: qtyValue
            };
            let cart = JSON.parse(localStorage.getItem('guest_cart')) || [];
            const existing = cart.find(item => item.id == product.id);
            if (existing) {
              existing.quantity += product.quantity;
            } else {
              cart.push(product);
            }
            localStorage.setItem('guest_cart', JSON.stringify(cart));
            swal({
              title: "Added!",
              text: `${qtyValue} × "${product.name}" added to cart.`,
              icon: "success",
              button: "OK"
            });
            updateCartIconCount();
          }
        });
      });
    });
  </script>
</body>
</html>