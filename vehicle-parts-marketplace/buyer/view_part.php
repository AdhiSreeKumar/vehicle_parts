<?php
session_start();
include '../includes/config.php';

// ✅ Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch user role
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

// ✅ Check if user has buyer role (even if multi-role)
$roles = explode(',', $user['role']);
$has_buyer_role = in_array('buyer', $roles);

// Get part ID
$part_id = $_GET['id'] ?? null;
if (!$part_id) {
    header("Location: ../browse_parts.php");
    exit();
}

// Fetch part data with category name
$part = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, c.name as category_name, p.price, p.stock_quantity as stock, 
               p.description, p.image_url, p.created_at, u.name as seller_name, u.email as seller_email
        FROM parts p
        LEFT JOIN users u ON p.seller_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ? AND p.status = 'active'
    ");
    $stmt->execute([$part_id]);
    $part = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$part) {
        header("Location: ../browse_parts.php");
        exit();
    }
} catch (Exception $e) {
    error_log("Failed to fetch part: " . $e->getMessage());
    header("Location: ../browse_parts.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($part['name']) ?> - AutoParts Hub</title>

  <!-- ✅ Correct Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body class="bg-gray-50 text-gray-900">

  <?php include '../includes/header.php'; ?>

  <!-- Page Header -->
  <div class="py-12 bg-gradient-to-r from-blue-600 to-blue-800 text-white">
    <div class="container mx-auto px-6 text-center">
      <h1 class="text-3xl md:text-4xl font-bold mb-4"><?= htmlspecialchars($part['name']) ?></h1>
      <p class="text-blue-100 max-w-2xl mx-auto">Detailed information about this vehicle part.</p>
    </div>
  </div>

  <!-- Role Error Modal -->
  <div id="roleErrorModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 max-w-sm mx-4 text-center shadow-lg">
      <i class="fas fa-exclamation-triangle text-yellow-500 text-4xl mb-4"></i>
      <h3 class="text-lg font-bold text-gray-800 mb-2">Permission Denied</h3>
      <p class="text-gray-600 mb-4">You need a buyer role to use this function.</p>
      <button onclick="hideRoleError()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        OK
      </button>
    </div>
  </div>

  <!-- Main Content -->
  <div class="container mx-auto px-6 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
      <!-- Product Image -->
      <div class="lg:w-1/2">
        <div class="relative h-80 lg:h-[500px] bg-gray-100 rounded-xl overflow-hidden">
          <?php if ($part['image_url']): ?>
            <img src="<?= htmlspecialchars($part['image_url']) ?>" alt="<?= htmlspecialchars($part['name']) ?>"
                 class="w-full h-full object-cover transition-transform duration-300 hover:scale-105">
          <?php else: ?>
            <div class="w-full h-full flex items-center justify-center">
              <i class="fas fa-cog text-gray-400 text-6xl"></i>
            </div>
          <?php endif; ?>
          
          <!-- Price Tag -->
          <div class="absolute top-4 left-4 bg-blue-600 text-white px-4 py-2 rounded-lg font-bold text-lg">
            $<?= number_format($part['price'], 2) ?>
          </div>
          
          <!-- Stock Status -->
          <?php if ($part['stock'] <= 5 && $part['stock'] > 0): ?>
            <div class="absolute top-4 right-4 bg-amber-500 text-white px-3 py-1 rounded-full text-sm">
              Low Stock
            </div>
          <?php elseif ($part['stock'] == 0): ?>
            <div class="absolute top-4 right-4 bg-red-500 text-white px-3 py-1 rounded-full text-sm">
              Out of Stock
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Product Info -->
      <div class="lg:w-1/2">
        <div class="bg-white rounded-xl shadow-md p-6">
          <div class="flex items-center mb-4">
            <span class="inline-block capitalize text-sm font-medium text-blue-600 bg-blue-100 px-2 py-1 rounded-full">
              <?= htmlspecialchars($part['category_name'] ?? 'Uncategorized') ?>
            </span>
            <span class="ml-2 text-sm text-gray-500">
              Added on <?= date('M j, Y', strtotime($part['created_at'])) ?>
            </span>
          </div>

          <h2 class="text-2xl font-bold mb-2"><?= htmlspecialchars($part['name']) ?></h2>
          <p class="text-gray-600 mb-6"><?= htmlspecialchars($part['description']) ?></p>

          <div class="space-y-4 mb-6">
            <div class="flex items-center">
              <i class="fas fa-store mr-2 text-blue-600"></i>
              <span class="text-gray-700"><strong>Seller:</strong> <?= htmlspecialchars($part['seller_name'] ?? 'Unknown') ?></span>
            </div>
            <div class="flex items-center">
              <i class="fas fa-envelope mr-2 text-blue-600"></i>
              <span class="text-gray-700"><strong>Email:</strong> <?= htmlspecialchars($part['seller_email'] ?? 'N/A') ?></span>
            </div>
            <div class="flex items-center">
              <i class="fas fa-boxes mr-2 text-blue-600"></i>
              <span class="text-gray-700"><strong>Stock:</strong> <?= $part['stock'] ?> in stock</span>
            </div>
          </div>

          <div class="flex space-x-3">
            <!-- Add to Cart Form -->
            <form method="POST" action="cart/add_to_cart.php" class="flex-1">
              <input type="hidden" name="part_id" value="<?= $part['id'] ?>">
              <input type="hidden" name="quantity" value="1">

              <?php if ($has_buyer_role): ?>
                <button type="submit" 
                        class="w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition flex items-center justify-center disabled:opacity-50"
                        <?= $part['stock'] <= 0 ? 'disabled' : '' ?>>
                  <i class="fas fa-shopping-cart mr-2"></i>
                  <?= $part['stock'] > 0 ? 'Add to Cart' : 'Out of Stock' ?>
                </button>
              <?php else: ?>
                <button type="button" 
                        class="w-full px-6 py-3 bg-gray-400 text-white rounded-lg cursor-not-allowed flex items-center justify-center"
                        onclick="showRoleError()">
                  <i class="fas fa-ban mr-2"></i> Buyer Only
                </button>
              <?php endif; ?>
            </form>
            
            <!-- Back to Parts -->
            <a href="browse_parts.php" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition flex items-center justify-center">
              <i class="fas fa-arrow-left mr-2"></i> Back to Parts
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include '../includes/footer.php'; ?>

  <script>
    function showRoleError() {
      document.getElementById('roleErrorModal').classList.remove('hidden');
    }

    function hideRoleError() {
      document.getElementById('roleErrorModal').classList.add('hidden');
    }
  </script>
</body>
</html>