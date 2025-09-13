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

$message = '';
$error = '';

// Handle form submission
if ($_POST) {
    $site_name = trim($_POST['site_name'] ?? '');
    $contact_email = filter_var($_POST['contact_email'] ?? '', FILTER_VALIDATE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $enable_notifications = isset($_POST['enable_notifications']);
    $auto_approve_sellers = isset($_POST['auto_approve_sellers']);

    if (!$site_name || !$contact_email || !$phone || !$address) {
        $error = "All fields are required.";
    } else {
        // In real app: save to settings table
        // For now: just show success
        $message = "Settings saved successfully.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Settings - Admin Panel</title>

  <!-- ✅ Correct Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body class="bg-gray-50 text-gray-900">

  <?php include 'includes/admin_header.php'; ?>

  <!-- Page Header -->
  <div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">Settings</h1>
    <p class="text-gray-600 mt-1">Configure site-wide settings and preferences.</p>
  </div>

  <!-- Success/Error Messages -->
  <?php if ($message): ?>
    <div class="mb-6 p-4 bg-green-100 text-green-800 rounded-lg text-sm">
      <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="mb-6 p-4 bg-red-100 text-red-800 rounded-lg text-sm">
      <i class="fas fa-exclamation-triangle mr-2"></i> <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <!-- Site Settings Form -->
  <div class="bg-white rounded-xl shadow-md p-6 mb-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-6">Site Settings</h2>
    <form method="POST">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Site Name</label>
          <input type="text" name="site_name" value="AutoParts Hub" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Contact Email</label>
          <input type="email" name="contact_email" value="admin@autopartshub.com" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
          <input type="text" name="phone" value="+1 (555) 123-4567" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
          <input type="text" name="address" value="123 Auto Lane, Tech City, TC 10101" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
        </div>
      </div>
      <div class="mt-6">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
          Save Settings
        </button>
      </div>
    </form>
  </div>

  <!-- Admin Settings -->
  <div class="bg-white rounded-xl shadow-md p-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-6">Admin Settings</h2>
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <strong>Enable Notifications</strong>
          <p class="text-gray-600 text-sm">Receive email alerts for new role requests</p>
        </div>
        <label class="inline-flex items-center cursor-pointer">
          <input type="checkbox" name="enable_notifications" class="sr-only peer" checked>
          <div class="relative w-11 h-6 bg-gray-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-0.5 after:start-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
        </label>
      </div>
      <div class="flex items-center justify-between">
        <div>
          <strong>Auto-Approve Sellers</strong>
          <p class="text-gray-600 text-sm">Automatically approve seller applications</p>
        </div>
        <label class="inline-flex items-center cursor-pointer">
          <input type="checkbox" name="auto_approve_sellers" class="sr-only peer">
          <div class="relative w-11 h-6 bg-gray-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-0.5 after:start-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
        </label>
      </div>
    </div>
  </div>

  <?php include 'includes/admin_footer.php'; ?>
</body>
</html>