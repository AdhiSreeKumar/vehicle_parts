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

// Fetch pending role applications
$applications = [];
try {
    $stmt = $pdo->prepare("
        SELECT id, name, email, role_request, role_reason, created_at 
        FROM users 
        WHERE role_status = 'pending' AND role_request IS NOT NULL
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Failed to fetch role requests: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Role Requests - Admin Panel</title>

  <!-- ✅ Correct Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body class="bg-gray-50 text-gray-900">

  <?php include 'includes/admin_header.php'; ?>

  <!-- Page Header -->
  <div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">Role Requests</h1>
    <p class="text-gray-600 mt-1">Review and approve pending role applications.</p>
  </div>

  <!-- Success/Error Messages -->
  <?php if (isset($_GET['message']) && $_GET['message'] === 'role_approved'): ?>
    <div class="mb-6 p-4 bg-green-100 text-green-800 rounded-lg text-sm">
      <i class="fas fa-check-circle mr-2"></i> Role application approved successfully.
    </div>
  <?php elseif (isset($_GET['message']) && $_GET['message'] === 'role_rejected'): ?>
    <div class="mb-6 p-4 bg-blue-100 text-blue-800 rounded-lg text-sm">
      <i class="fas fa-times-circle mr-2"></i> Role application rejected.
    </div>
  <?php elseif (isset($_GET['error']) && $_GET['error'] === 'action_failed'): ?>
    <div class="mb-6 p-4 bg-red-100 text-red-800 rounded-lg text-sm">
      <i class="fas fa-exclamation-triangle mr-2"></i> Failed to process request. Please try again.
    </div>
  <?php endif; ?>

  <!-- Requests Table -->
  <div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-700 uppercase text-xs">
          <tr>
            <th class="px-6 py-3 text-left">User</th>
            <th class="px-6 py-3 text-left">Requested Role</th>
            <th class="px-6 py-3 text-left">Applied On</th>
            <th class="px-6 py-3 text-left">Reason</th>
            <th class="px-6 py-3 text-left">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php if (empty($applications)): ?>
            <tr>
              <td colspan="5" class="px-6 py-8 text-center text-gray-500">No pending role requests.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($applications as $app): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
                  <div>
                    <div class="font-medium"><?= htmlspecialchars($app['name']) ?></div>
                    <div class="text-gray-600 text-xs"><?= htmlspecialchars($app['email']) ?></div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span class="capitalize px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">
                    <?= htmlspecialchars($app['role_request']) ?>
                  </span>
                </td>
                <td class="px-6 py-4 text-gray-600"><?= date('M j, Y', strtotime($app['created_at'])) ?></td>
                <td class="px-6 py-4 text-gray-600 max-w-xs truncate" title="<?= htmlspecialchars($app['role_reason']) ?>">
                  <?= htmlspecialchars($app['role_reason']) ?>
                </td>
                <td class="px-6 py-4 space-x-2">
                  <form method="POST" action="approve_role.php" class="inline" onsubmit="return confirm('Approve this role request?')">
                    <input type="hidden" name="user_id" value="<?= $app['id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">
                      Approve
                    </button>
                  </form>
                  <form method="POST" action="approve_role.php" class="inline" onsubmit="return confirm('Reject this role request?')">
                    <input type="hidden" name="user_id" value="<?= $app['id'] ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">
                      Reject
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php include 'includes/admin_footer.php'; ?>
</body>
</html>