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

$user_name = htmlspecialchars($_SESSION['name']);
$ticket_id = $_GET['id'] ?? null;

if (!$ticket_id) {
    $_SESSION['error'] = "Ticket not found.";
    header("Location: tickets.php");
    exit();
}

// Fetch ticket and user
$ticket = null;
$replies = [];
try {
    $stmt = $pdo->prepare("
        SELECT t.*, u.name as user_name, u.email as user_email, u.role as user_role
        FROM tickets t
        JOIN users u ON t.user_id = u.id
        WHERE t.id = ?
    ");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        $_SESSION['error'] = "Ticket not found.";
        header("Location: tickets.php");
        exit();
    }

    // Fetch all replies
    $reply_stmt = $pdo->prepare("
        SELECT tr.*, u.name as sender_name, u.role as sender_role_name
        FROM ticket_replies tr
        JOIN users u ON tr.sender_id = u.id
        WHERE tr.ticket_id = ?
        ORDER BY tr.created_at ASC
    ");
    $reply_stmt->execute([$ticket_id]);
    $replies = $reply_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Failed to fetch ticket: " . $e->getMessage());
    $_SESSION['error'] = "Failed to load ticket.";
    header("Location: tickets.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Ticket #<?= htmlspecialchars($ticket['id']) ?> - Admin Panel</title>

  <!-- ✅ Correct Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

  <style>
    .status-open {
      @apply px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium;
    }
    .status-in_progress {
      @apply px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-medium;
    }
    .status-resolved {
      @apply px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium;
    }
    .status-closed {
      @apply px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-medium;
    }
    .priority-urgent {
      @apply px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-bold;
    }
    .priority-high {
      @apply px-2 py-1 bg-orange-100 text-orange-800 rounded-full text-xs font-bold;
    }
    .priority-medium {
      @apply px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-bold;
    }
  </style>
</head>
<body class="bg-gray-50 text-gray-900">

  <?php include 'includes/admin_header.php'; ?>

  <!-- Page Header -->
  <div class="py-12 bg-gradient-to-r from-blue-600 to-blue-800 text-white">
    <div class="container mx-auto px-6 text-center">
      <h1 class="text-4xl md:text-5xl font-bold mb-4">Ticket #<?= htmlspecialchars($ticket['id']) ?></h1>
      <p class="text-blue-100 max-w-2xl mx-auto text-lg">View customer support request.</p>
    </div>
  </div>

  <!-- Main Content -->
  <div class="container mx-auto px-6 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Ticket Info -->
      <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
          <div class="p-6 border-b">
            <h2 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($ticket['subject']) ?></h2>
            <p class="text-gray-600 mt-1">Submitted on <?= date('M j, Y \a\t g:i A', strtotime($ticket['created_at'])) ?></p>
          </div>
          
          <div class="p-6 space-y-6">
            <!-- User Message -->
            <div class="p-4 bg-blue-50 rounded-lg">
              <div class="flex justify-between items-center mb-2">
                <strong><?= htmlspecialchars($ticket['user_name']) ?> 
                  <?php
                  // Show sender_role (buyer/seller), not full role list
                  $sender_role = $ticket['sender_role'];
                  echo '<span class="px-2 py-1 rounded-full text-xs font-medium ' . 
                       ($sender_role === 'buyer' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') . 
                       '">' . ucfirst($sender_role) . '</span>';
                  
                  // If user has multiple roles, show that too
                  $user_roles = explode(',', $ticket['user_role']);
                  if (count($user_roles) > 1) {
                      echo ' <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-medium">(' . implode('/', $user_roles) . ')</span>';
                  }
                  ?>
                </strong>
                <span class="text-gray-500 text-sm"><?= date('M j, Y \a\t g:i A', strtotime($ticket['created_at'])) ?></span>
              </div>
              <p class="text-gray-800 leading-relaxed"><?= nl2br(htmlspecialchars($ticket['message'])) ?></p>
            </div>

            <!-- Support Replies -->
            <?php foreach ($replies as $reply): ?>
              <div class="p-4 <?= $reply['sender_role'] === 'support' ? 'bg-green-50' : 'bg-blue-50' ?> rounded-lg">
                <div class="flex justify-between items-center mb-2">
                  <strong><?= htmlspecialchars($reply['sender_name']) ?> 
                    <?php
                    $reply_role = $reply['sender_role'];
                    echo '<span class="px-2 py-1 rounded-full text-xs font-medium ' . 
                         ($reply_role === 'buyer' ? 'bg-blue-100 text-blue-800' : ($reply_role === 'seller' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800')) . 
                         '">' . ucfirst($reply_role) . '</span>';
                    
                    $user_roles = explode(',', $reply['sender_role_name']);
                    if (count($user_roles) > 1) {
                        echo ' <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-medium">(' . implode('/', $user_roles) . ')</span>';
                    }
                    ?>
                  </strong>
                  <span class="text-gray-500 text-sm"><?= date('M j, Y \a\t g:i A', strtotime($reply['created_at'])) ?></span>
                </div>
                <p class="text-gray-800 leading-relaxed"><?= nl2br(htmlspecialchars($reply['message'])) ?></p>
              </div>
            <?php endforeach; ?>

            <?php if (empty($replies)): ?>
              <div class="text-center py-6 bg-gray-50 rounded-lg">
                <i class="fas fa-comments text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-600">No replies yet.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-md overflow-hidden sticky top-24">
          <div class="p-6 border-b">
            <h2 class="text-xl font-bold text-gray-800">Ticket Info</h2>
          </div>
          
          <div class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">User</label>
              <p class="text-gray-800"><?= htmlspecialchars($ticket['user_name']) ?></p>
              <p class="text-gray-600 text-sm"><?= htmlspecialchars($ticket['user_email']) ?></p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Priority</label>
              <span class="priority-<?= htmlspecialchars($ticket['priority']) ?>">
                <?= ucfirst($ticket['priority']) ?>
              </span>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Status</label>
              <span class="status-<?= htmlspecialchars($ticket['status']) ?>">
                <?= str_replace('_', ' ', $ticket['status']) ?>
              </span>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Submitted</label>
              <p class="text-gray-800"><?= date('M j, Y \a\t g:i A', strtotime($ticket['created_at'])) ?></p>
            </div>

            <div class="pt-4 border-t">
              <a href="tickets.php" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition text-center block">
                <i class="fas fa-arrow-left mr-1"></i> Back to Tickets
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/admin_footer.php'; ?>
</body>
</html>