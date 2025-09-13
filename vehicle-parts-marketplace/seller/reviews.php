<?php
session_start();
include 'includes/config.php';

// ✅ Check if user is logged in and has approved seller role
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit();
}

$roles = explode(',', $_SESSION['role']);
if (!in_array('seller', $roles) || $_SESSION['role_status'] !== 'approved') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['name']);

// Fetch reviews for this seller's parts
$reviews = [];
try {
    $stmt = $pdo->prepare("
        SELECT r.id, r.rating, r.comment, r.created_at, u.name as buyer_name, p.name as part_name
        FROM reviews r
        JOIN parts p ON r.product_id = p.id
        JOIN users u ON r.buyer_id = u.id
        WHERE p.seller_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Failed to fetch reviews: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reviews - Seller Dashboard</title>

  <!-- ✅ Correct Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body class="bg-gray-50 text-gray-900">

  <?php include 'includes/seller_header.php'; ?>

  <!-- Page Header -->
  <div class="py-12 bg-gradient-to-r from-blue-600 to-blue-800 text-white">
    <div class="container mx-auto px-6 text-center">
      <h1 class="text-4xl md:text-5xl font-bold mb-4">Reviews</h1>
      <p class="text-blue-100 max-w-2xl mx-auto text-lg">View feedback from customers who bought your parts.</p>
    </div>
  </div>

  <!-- Main Content -->
  <div class="container mx-auto px-6 py-8">
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
      <div class="p-6 border-b">
        <h2 class="text-xl font-bold text-gray-800">Customer Reviews</h2>
        <p class="text-gray-600 mt-1">See what buyers think about your products.</p>
      </div>
      
      <div class="p-6">
        <?php if (empty($reviews)): ?>
          <div class="text-center py-12">
            <i class="fas fa-star text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-medium text-gray-500">No reviews yet</h3>
            <p class="text-gray-400 mt-2">Wait for customers to leave reviews after purchasing your parts.</p>
          </div>
        <?php else: ?>
          <div class="space-y-6">
            <?php foreach ($reviews as $review): ?>
              <div class="border border-gray-200 rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                  <div class="flex items-center">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-semibold">
                      <?= substr($review['buyer_name'], 0, 1) ?>
                    </div>
                    <div class="ml-3">
                      <div class="font-medium text-gray-800"><?= htmlspecialchars($review['buyer_name']) ?></div>
                      <div class="text-sm text-gray-500">Reviewed <?= htmlspecialchars($review['part_name']) ?></div>
                    </div>
                  </div>
                  <div class="flex items-center">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <i class="fas fa-star <?= $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300' ?>"></i>
                    <?php endfor; ?>
                  </div>
                </div>
                
                <p class="text-gray-700 mb-4"><?= htmlspecialchars($review['comment']) ?></p>
                
                <div class="text-sm text-gray-500">
                  <i class="fas fa-calendar-alt mr-1"></i> <?= date('M j, Y', strtotime($review['created_at'])) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php include 'includes/seller_footer.php'; ?>
</body>
</html>