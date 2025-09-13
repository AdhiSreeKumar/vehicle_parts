<?php
session_start();
include 'includes/config.php';

// ✅ Check if user is logged in and has admin role
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit();
}

$roles = explode(',', $_SESSION['role']);
if (!in_array('admin', $roles)) {
    header("Location: ../login.php");
    exit();
}

// Get part ID
$part_id = $_GET['id'] ?? null;
if (!$part_id) {
    header("Location: manage_parts.php");
    exit();
}

// Fetch part data
$part = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM parts WHERE id = ?");
    $stmt->execute([$part_id]);
    $part = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$part) {
        header("Location: manage_parts.php");
        exit();
    }
} catch (Exception $e) {
    error_log("Failed to fetch part: " . $e->getMessage());
    header("Location: manage_parts.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = (float)$_POST['price'] ?? 0;
    $stock = (int)$_POST['stock'] ?? 0;
    $image_url = $_POST['image_url'] ?? '';

    // Validate input
    if (empty($name) || empty($category) || $price <= 0 || $stock < 0) {
        $error = "Please fill in all fields correctly.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE parts SET name = ?, category = ?, price = ?, stock_quantity = ?, image_url = ? WHERE id = ?");
            $stmt->execute([$name, $category, $price, $stock, $image_url, $part_id]);

            header("Location: manage_parts.php?message=part_updated");
            exit();
        } catch (Exception $e) {
            $error = "Failed to update part: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Part - Admin Panel</title>

  <!-- ✅ Correct Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body class="bg-gray-50 text-gray-900">

  <?php include 'includes/admin_header.php'; ?>

  <!-- Page Header -->
  <div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">Edit Part</h1>
    <p class="text-gray-600 mt-1">Update the details of this vehicle part.</p>
  </div>

  <!-- Form -->
  <div class="bg-white rounded-xl shadow-md p-6 max-w-2xl mx-auto">
    <?php if (isset($error)): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
        <i class="fas fa-exclamation-triangle mr-2"></i> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Part Name</label>
        <input type="text" name="name" required
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
               value="<?= htmlspecialchars($part['name']) ?>">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
        <input type="text" name="category" required
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
               value="<?= htmlspecialchars($part['category']) ?>">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Price</label>
        <input type="number" step="0.01" name="price" required
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
               value="<?= $part['price'] ?>">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Stock Quantity</label>
        <input type="number" name="stock" required
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
               value="<?= $part['stock_quantity'] ?>">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Image URL</label>
        <input type="url" name="image_url"
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
               value="<?= htmlspecialchars($part['image_url']) ?>">
      </div>

      <div class="flex justify-end space-x-3">
        <a href="manage_parts.php" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition">
          Cancel
        </a>
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
          Update Part
        </button>
      </div>
    </form>
  </div>

  <?php include 'includes/admin_footer.php'; ?>
</body>
</html>