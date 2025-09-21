<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$isAdmin = ($_SESSION['role'] === 'admin');

// fetch user info
$stmt = $mysqli->prepare("SELECT username, firstname, lastname, last_login FROM users WHERE id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($username, $firstname, $lastname, $last_login);
$stmt->fetch();
$stmt->close();

// if admin → see all files, else only own files
if ($isAdmin) {
    $stmt = $mysqli->prepare("SELECT f.id, f.filename, u.username, f.created_at 
                              FROM files f 
                              JOIN users u ON f.user_id=u.id 
                              ORDER BY f.created_at DESC");
} else {
    $stmt = $mysqli->prepare("SELECT id, filename, created_at FROM files WHERE user_id=? ORDER BY created_at DESC");
    $stmt->bind_param("i", $_SESSION['user_id']);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
<div class="max-w-3xl mx-auto bg-white p-6 rounded-2xl shadow">
  <h2 class="text-2xl font-bold mb-2">Welcome, <?= htmlspecialchars($firstname . " " . $lastname) ?> 👋</h2>
  <p class="text-gray-700 mb-1">Username: <span class="font-semibold"><?= htmlspecialchars($username) ?></span></p>
  <p class="text-gray-700 mb-1">Role: <span class="font-semibold"><?= htmlspecialchars($_SESSION['role']) ?></span></p>
  <p class="text-gray-700 mb-6">Last login: <span class="font-semibold"><?= $last_login ? htmlspecialchars($last_login) : "First time login" ?></span></p>

  <?php if ($isAdmin): ?>
    <!-- Admin link -->
    <a href="manage_users.php" class="inline-block mb-6 bg-purple-600 text-white px-4 py-2 rounded shadow">👑 Manage Users</a>
  <?php endif; ?>

  <?php if (!$isAdmin): ?>
  <!-- Upload form only for users -->
  <form action="upload.php" method="post" enctype="multipart/form-data" class="mb-6">
    <input type="file" name="file" required class="mb-2">
    <button class="bg-green-500 text-white px-4 py-2 rounded">Upload</button>
  </form>
  <?php endif; ?>

  <h3 class="font-semibold mb-2"><?= $isAdmin ? "All Uploaded Files" : "Your Files" ?></h3>
  <ul class="divide-y">
    <?php while ($row = $result->fetch_assoc()): ?>
      <li class="flex justify-between items-center py-2">
        <span>
          <?= htmlspecialchars($row['filename']) ?>
          <?php if ($isAdmin && isset($row['username'])): ?>
            <span class="text-sm text-gray-500">(by <?= htmlspecialchars($row['username']) ?>)</span>
          <?php endif; ?>
        </span>
        <a href="download.php?id=<?= $row['id'] ?>" class="text-blue-600">Download</a>
      </li>
    <?php endwhile; ?>
  </ul>

  <a href="logout.php" class="inline-block mt-6 bg-red-500 text-white px-4 py-2 rounded">Logout</a>
</div>
</body>
</html>
