<?php
session_start();
include '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['name']);

// Fetch user data
$stmt = $pdo->prepare("SELECT role, role_status, role_request, role_reason, additional_info, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: ../logout.php");
    exit();
}

// Get current role and status
$current_role = $user['role'];
$role_status = $user['role_status'];
$role_request = $user['role_request'];

// Determine if user already has a non-buyer role (approved)
$is_approved_non_buyer = $current_role !== 'buyer' && $role_status === 'approved';

// Determine if user has a pending application
$has_pending = !empty($role_request) && $role_status === 'pending';
$pending_role = $has_pending ? $role_request : '';

// Build applications array
$applications = [];
if ($role_request) {
    $applications[] = [
        'requested_role' => $role_request,
        'status' => $role_status,
        'created_at' => $user['created_at'] ?? null,
        'reason' => $user['role_reason'] ?? '',
        'additional_info' => $user['additional_info'] ?? ''
    ];
}

// Get flash message
$message = '';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'applied':
            $message = "Your application has been submitted successfully.";
            break;
        case 'request_cancelled':
            $message = "Your role request has been cancelled.";
            break;
        case 'request_deleted':
            $message = "Your role request has been deleted.";
            break;
        case 'pending_application_exists':
            $message = "You already have a pending application. Please wait for approval.";
            break;
        case 'already_approved':
            $message = "You are already approved for this role. You cannot apply again.";
            break;
        case 'seller_application_submitted':
            $message = "Your seller application has been submitted.";
            break;
        case 'support_application_submitted':
            $message = "Your support role application has been submitted.";
            break;
        case 'admin_application_submitted':
            $message = "Your admin role application has been submitted.";
            break;
        default:
            $message = htmlspecialchars($_GET['message']);
            break;
    }
}

// Error messages
$error = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'cancel_failed':
            $error = "Failed to cancel request. Please try again.";
            break;
        case 'not_pending':
            $error = "Only pending requests can be cancelled.";
            break;
        case 'invalid_request':
            $error = "Invalid request or permission denied.";
            break;
        case 'delete_failed':
            $error = "Failed to delete request. Please try again.";
            break;
        case 'not_deletable':
            $error = "Only rejected or cancelled requests can be deleted.";
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Role Applications - AutoParts Hub</title>

  <!-- ✅ Fixed: Removed extra spaces in CDN URLs -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f8fafc;
    }
    .card-hover:hover {
      transform: translateY(-4px);
      transition: all 0.3s ease;
    }
  </style>
</head>
<body class="bg-gray-50 text-gray-900">
  <?php include '../includes/header.php'; ?>

  <!-- Main Content -->
  <div class="container mx-auto px-6 py-12 max-w-5xl">

    <!-- Page Header -->
    <div class="mb-8 text-center">
      <h1 class="text-3xl font-bold text-gray-800">My Role Applications</h1>
      <p class="text-gray-600 mt-2">Welcome back, <?= $user_name ?>! Manage your role requests here.</p>
    </div>

    <!-- Success Message -->
    <?php if ($message): ?>
      <div class="mb-6 p-4 bg-green-100 border border-green-200 text-green-800 rounded-lg flex items-center space-x-2">
        <i class="fas fa-check-circle"></i>
        <span><?= htmlspecialchars($message) ?></span>
      </div>
    <?php endif; ?>

    <!-- Error Message -->
    <?php if ($error): ?>
      <div class="mb-6 p-4 bg-red-100 border border-red-200 text-red-800 rounded-lg flex items-center space-x-2">
        <i class="fas fa-exclamation-triangle"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>

    <!-- Current Role Info -->
    <div class="bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded-lg mb-6 flex items-start space-x-3">
      <i class="fas fa-user-shield mt-1"></i>
      <div>
        <strong>Your Role:</strong> 
        <span class="capitalize font-medium"><?= ucfirst($current_role) ?></span>
        <?php if ($is_approved_non_buyer): ?>
          <p class="text-sm mt-1">You are already a <?= ucfirst($current_role) ?>. You cannot apply for another role.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Pending Application Alert -->
    <?php if ($has_pending): ?>
      <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-lg mb-6 flex items-start space-x-3">
        <i class="fas fa-hourglass-half mt-1"></i>
        <div>
          <strong>Pending Application:</strong> You have a pending request to become a <span class="capitalize font-medium"><?= htmlspecialchars($pending_role) ?></span>.
          <p class="text-sm mt-1">You can cancel this request if needed.</p>
        </div>
      </div>
    <?php endif; ?>

    <!-- No Applications -->
    <?php if (empty($applications)): ?>
      <div class="bg-white rounded-xl shadow-md p-12 text-center">
        <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-medium text-gray-500">No applications found</h3>
        <p class="text-gray-400 mt-2">You haven't applied for any roles yet.</p>
        
        <!-- Apply Buttons (Only if eligible) -->
        <?php if (!$has_pending && !$is_approved_non_buyer): ?>
          <div class="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-3xl mx-auto">
            <a href="apply_seller.php" 
               class="bg-green-600 hover:bg-green-700 text-white p-4 rounded-lg flex flex-col items-center space-y-2 card-hover">
              Apply as Seller
            </a>
            <a href="apply_support.php" 
               class="bg-teal-600 hover:bg-teal-700 text-white p-4 rounded-lg flex flex-col items-center space-y-2 card-hover">
              Apply as Support
            </a>
            <a href="apply_admin.php" 
               class="bg-red-600 hover:bg-red-700 text-white p-4 rounded-lg flex flex-col items-center space-y-2 card-hover">
              Apply as Admin
            </a>
          </div>
        <?php else: ?>
          <p class="text-gray-500 mt-4">You cannot apply for a new role at this time.</p>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <!-- Applications Table -->
      <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-700 uppercase text-xs">
              <tr>
                <th class="px-6 py-3 text-left">Role Requested</th>
                <th class="px-6 py-3 text-left">Status</th>
                <th class="px-6 py-3 text-left">Applied On</th>
                <th class="px-6 py-3 text-left">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <?php foreach ($applications as $app): ?>
                <tr class="hover:bg-gray-50">
                  <td class="px-6 py-4">
                    <span class="capitalize font-medium text-blue-700"><?= htmlspecialchars($app['requested_role']) ?></span>
                  </td>
                  <td class="px-6 py-4">
                    <?php if ($app['status'] === 'approved'): ?>
                      <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">Approved</span>
                    <?php elseif ($app['status'] === 'rejected'): ?>
                      <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">Rejected</span>
                    <?php elseif ($app['status'] === 'cancelled'): ?>
                      <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-medium">Cancelled</span>
                    <?php else: ?>
                      <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">Pending</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-6 py-4 text-gray-600">
                    <?= $app['created_at'] ? date('M j, Y', strtotime($app['created_at'])) : 'Unknown' ?>
                  </td>
                  <td class="px-6 py-4 text-sm">
                    <?php if ($app['status'] === 'pending'): ?>
                      <!-- Cancel Button -->
                      <a href="#" 
                         onclick="confirmCancel()" 
                         class="text-red-600 hover:text-red-800 font-medium">
                        Cancel
                      </a>
                    <?php elseif ($app['status'] === 'rejected' || $app['status'] === 'cancelled'): ?>
                      <!-- Reapply Button -->
                      <a href="apply_<?= htmlspecialchars($app['requested_role']) ?>.php" 
                         class="text-blue-600 hover:text-blue-800 font-medium">
                        Reapply
                      </a>
                    <?php else: ?>
                      <span class="text-gray-500">—</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Apply Buttons (Only if eligible) -->
      <?php if (!$has_pending && !$is_approved_non_buyer): ?>
        <div class="text-center">
          <p class="text-gray-600 mb-4">Want to apply for another role?</p>
          <div class="flex justify-center space-x-4">
            <a href="apply_seller.php" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg text-sm">
              Apply as Seller
            </a>
            <a href="apply_support.php" class="bg-teal-600 hover:bg-teal-700 text-white px-5 py-2 rounded-lg text-sm">
              Apply as Support
            </a>
            <a href="apply_admin.php" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded-lg text-sm">
              Apply as Admin
            </a>
          </div>
        </div>
      <?php else: ?>
        <div class="text-center text-gray-500 text-sm">
          You cannot apply for another role at this time.
        </div>
      <?php endif; ?>
    <?php endif; ?>

  </div>

  <?php include '../includes/footer.php'; ?>

  <script>
    function confirmCancel() {
      const confirmed = confirm("Are you sure you want to cancel your role application? This action cannot be undone.");
      if (confirmed) {
        window.location.href = 'cancel_request.php';
      }
    }
  </script>
</body>
</html>