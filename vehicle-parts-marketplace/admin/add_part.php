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

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = (float)$_POST['price'] ?? 0;
    $stock = (int)$_POST['stock'] ?? 0;
    $image_url = $_POST['image_url'] ?? '';
    $description = $_POST['description'] ?? '';

    // Validate input
    if (empty($name) || empty($category) || $price <= 0 || $stock < 0) {
        $error = "Please fill in all fields correctly.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO parts (name, category, description, price, stock_quantity, image_url, seller_id, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $name,
                $category,
                $description,
                $price,
                $stock,
                $image_url,
                null, // seller_id is NULL for now
                'active' // default status
            ]);

            $success = "Part added successfully!";
        } catch (Exception $e) {
            $error = "Failed to add part: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Add Part - Admin Panel</title>

  <!-- ✅ Correct Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body class="bg-gray-50 text-gray-900">

  <?php include 'includes/admin_header.php'; ?>

  <!-- Page Header -->
  <div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">Add New Part</h1>
    <p class="text-gray-600 mt-1">Add a new vehicle part to the marketplace.</p>
  </div>

  <!-- Form -->
  <div class="bg-white rounded-xl shadow-md p-6 max-w-2xl mx-auto">
    <?php if ($error): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
        <i class="fas fa-exclamation-triangle mr-2"></i> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
        <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Part Name</label>
        <input type="text" name="name" required
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
               placeholder="Enter part name">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
        <input type="text" name="category" required
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
               placeholder="e.g., brakes, engine, ignition">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <textarea name="description" rows="4"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  placeholder="Describe the part..."></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Price</label>
        <input type="number" step="0.01" name="price" required
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
               placeholder="0.00">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Stock Quantity</label>
        <input type="number" name="stock" required
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
               placeholder="0">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Image URL</label>
        <input type="url" name="image_url"
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
               placeholder="https://example.com/image.jpg">
      </div>

      <div class="flex justify-end space-x-3">
        <a href="manage_parts.php" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition">
          Cancel
        </a>
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
          Add Part
        </button>
      </div>
    </form>
  </div>

  <?php include 'includes/admin_footer.php'; ?>
</body>
</html>