<?php
require_once '../includes/auth_check.php';
if ($_SESSION['user_role'] !== 'manager') {
    header("Location: ../auth/unauthorized.php");
    exit;
}

// Handle profile deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    $pdo->prepare("UPDATE users SET deleted_at = NOW() WHERE id = ?")->execute([$user_id]);
    
    // Log action
    $pdo->prepare("INSERT INTO audit_log (user_id, action, ip_address) VALUES (?, ?, ?)")
        ->execute([$_SESSION['user_id'], "profile_deleted", $_SERVER['REMOTE_ADDR']]);
    
    header("Location: profiles.php");
    exit;
}

// Get all users
$stmt = $pdo->query("SELECT * FROM users WHERE deleted_at IS NULL ORDER BY name");
$users = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard">
    <aside class="sidebar">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li class="active"><a href="profiles.php">Manage Profiles</a></li>
            <li><a href="cars.php">Manage Cars</a></li>
            <li><a href="requests.php">Deletion Requests</a></li>
            <li><a href="/fusys/includes/myprofile.php">My Profile</a></li>
            <li><a href="user_management.php">User Management</a></li>
        </ul>
    </aside>
    
    <main class="content">
        <h1>Manage User Profiles</h1>
        
        <div class="actions">
            <a href="add_profile.php" class="btn btn-add">Add New Profile</a>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                    <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                    <td><?php echo ucfirst($user['role']); ?></td>
                    <td class="actions">
                        <a href="edit_profile.php?id=<?php echo $user['id']; ?>" class="btn btn-edit">Edit</a>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" name="delete_user" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this profile?')">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</div>

<?php include '../includes/footer.php'; ?>