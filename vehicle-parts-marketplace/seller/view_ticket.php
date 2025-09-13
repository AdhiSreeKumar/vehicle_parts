<?php
session_start();
include 'includes/config.php';

// ✅ Check if user is logged in and has seller role
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit();
}

$roles = explode(',', $_SESSION['role']);
if (!in_array('seller', $roles)) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$ticket_id = $_GET['id'] ?? null;

if (!$ticket_id) {
    $_SESSION['error'] = "Ticket not found.";
    header("Location: my_tickets.php");
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
        WHERE t.id = ? AND t.user_id = ? AND t.sender_role = 'seller'
    ");
    $stmt->execute([$ticket_id, $user_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        $_SESSION['error'] = "Ticket not found or access denied.";
        header("Location: my_tickets.php");
        exit();
    }

    // Fetch replies
    $reply_stmt = $pdo->prepare("
        SELECT tr.*, u.name as sender_name, u.role as sender_role_name
        FROM ticket_replies tr
        JOIN users u ON tr.sender_id = u.id
        WHERE tr.ticket_id = ?
        ORDER BY tr.created_at ASC
    ");
    $reply_stmt->execute([$ticket_id]);
    $replies = $reply_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark all replies from support as read
    $mark_read = $pdo->prepare("
        UPDATE ticket_replies 
        SET is_read = TRUE 
        WHERE ticket_id = ? AND sender_role = 'support' AND is_read = FALSE
    ");
    $mark_read->execute([$ticket_id]);

} catch (Exception $e) {
    error_log("Failed to fetch ticket: " . $e->getMessage());
    $_SESSION['error'] = "Failed to load ticket.";
    header("Location: my_tickets.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>View Ticket - AutoParts Hub</title>

  <!-- ✅ Correct Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body class="bg-gray-50 text-gray-900">

  <?php include 'includes/seller_header.php'; ?>

  <!-- Page Header -->
  <div class="py-12 bg-gradient-to-r from-blue-600 to-blue-800 text-white">
    <div class="container mx-auto px-6 text-center">
      <h1 class="text-4xl md:text-5xl font-bold mb-4">Ticket #<?= htmlspecialchars($ticket['id']) ?></h1>
      <p class="text-blue-100 max-w-2xl mx-auto text-lg">View your support ticket details.</p>
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
            <!-- Conversation Thread -->
            <div class="space-y-4">
              <!-- User Message -->
              <div class="p-4 bg-blue-50 rounded-lg">
                <div class="flex justify-between items-center mb-2">
                  <strong><?= htmlspecialchars($ticket['user_name']) ?> 
                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Seller</span>
                    <?php
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

              <!-- All Replies -->
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
            </div>
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
              <span class="px-2 py-1 rounded-full text-xs font-bold
                <?= $ticket['priority'] === 'urgent' ? 'bg-red-100 text-red-800' :
                   ($ticket['priority'] === 'high' ? 'bg-orange-100 text-orange-800' :
                   ($ticket['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')) ?>">
                <?= ucfirst($ticket['priority']) ?>
              </span>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Status</label>
              <span class="px-3 py-1 rounded-full text-xs font-medium
                <?= $ticket['status'] === 'open' ? 'bg-blue-100 text-blue-800' :
                   ($ticket['status'] === 'in_progress' ? 'bg-purple-100 text-purple-800' :
                   ($ticket['status'] === 'resolved' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800')) ?>">
                <?= str_replace('_', ' ', ucfirst($ticket['status'])) ?>
              </span>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Submitted</label>
              <p class="text-gray-800"><?= date('M j, Y \a\t g:i A', strtotime($ticket['created_at'])) ?></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/seller_footer.php'; ?>
</body>
</html>