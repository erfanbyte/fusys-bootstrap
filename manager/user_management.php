<?php
require_once '../includes/auth_check.php';
if ($_SESSION['user_role'] !== 'manager') {
    header("Location: ../auth/unauthorized.php");
    exit;
}

// Get only soft-deleted users
$users = $pdo->query("SELECT id, name, email, deleted_at FROM users WHERE deleted_at IS NOT NULL")->fetchAll();

// Handle deletion requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_logs'])) {
        $user_id = $_POST['user_id'];
        
        // Verify user exists and is soft-deleted
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND deleted_at IS NOT NULL");
        $stmt->execute([$user_id]);
        
        if ($stmt->fetch()) {
            // Delete audit logs
            $pdo->prepare("DELETE FROM audit_log WHERE user_id = ?")->execute([$user_id]);
            
            // Log this action
            $pdo->prepare("INSERT INTO audit_log (user_id, action, ip_address) 
                          VALUES (?, ?, ?)")
               ->execute([
                   $_SESSION['user_id'],
                   'audit_logs_deleted_for_deleted_user_' . $user_id,
                   $_SERVER['REMOTE_ADDR']
               ]);
            
            $success = "All audit logs for soft-deleted user ID $user_id have been permanently deleted.";
        } else {
            $error = "User not found or not soft-deleted!";
        }
    }
    
    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        
        // Verify user exists and is soft-deleted
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND deleted_at IS NOT NULL");
        $stmt->execute([$user_id]);
        
        if ($stmt->fetch()) {
            try {
                $pdo->beginTransaction();
                
                // Disable foreign key checks temporarily
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                
                // List of all tables that reference users
                $dependent_tables = [
                    'audit_log',
                    'deletion_requests',
                    'fuel_requests',    // Add your actual tables here
                    'car_assignments'   // Add your actual tables here
                ];
                
                // Delete from all dependent tables
                foreach ($dependent_tables as $table) {
                    try {
                        $pdo->prepare("DELETE FROM $table WHERE user_id = ?")->execute([$user_id]);
                    } catch (Exception $e) {
                        // Skip if table or column doesn't exist
                        continue;
                    }
                }
                
                // Now delete the user
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
                
                // Re-enable foreign key checks
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                
                $pdo->commit();
                
                // Log this action
                $pdo->prepare("INSERT INTO audit_log (user_id, action, ip_address) 
                              VALUES (?, ?, ?)")
                   ->execute([
                       $_SESSION['user_id'],
                       'permanently_deleted_soft_deleted_user_' . $user_id,
                       $_SERVER['REMOTE_ADDR']
                   ]);
                
                $success = "Soft-deleted user ID $user_id has been permanently deleted from the system.";
                header("Location: user_management.php?success=" . urlencode($success));
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1"); // Ensure re-enable if error occurs
                $error = "Deletion failed: " . $e->getMessage();
            }
        } else {
            $error = "User not found or not soft-deleted!";
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard">
    <aside class="sidebar">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="profiles.php">Manage Profiles</a></li>
            <li><a href="cars.php">Manage Cars</a></li>
            <li><a href="requests.php">Deletion Requests</a></li>
            <li><a href="/fusys/includes/myprofile.php">My Profile</a></li>
            <li class="active"><a href="user_management.php">User Management</a></li>
        </ul>
    </aside>
    
    <main class="content">
        <h1>Deleted User Management</h1>
        <p class="subtitle">Showing only soft-deleted users</p>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        
        <?php if (empty($users)): ?>
            <div class="alert alert-info">No soft-deleted users found</div>
        <?php else: ?>
            <table class="user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Deleted On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo date('M j, Y g:i a', strtotime($user['deleted_at'])); ?></td>
                        <td class="actions">
                            <form method="post" style="display: inline-block;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="delete_logs" class="btn btn-warning" 
                                        onclick="return confirm('Permanently delete ALL audit logs for this deleted user?')">
                                    Delete Logs
                                </button>
                            </form>
                            
                            <form method="post" style="display: inline-block;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="delete_user" class="btn btn-danger" 
                                        onclick="return confirm('PERMANENTLY delete this user record and ALL their data?\n\nTHIS ACTION CANNOT BE UNDONE!')">
                                    Delete Permanently
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>
</div>

<style>
.user-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.user-table th, .user-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.user-table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.actions {
    white-space: nowrap;
}

.btn-warning {
    background-color: #ffc107;
    color: #212529;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn {
    padding: 8px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin-right: 5px;
    transition: background-color 0.3s;
}

.btn:hover {
    opacity: 0.9;
}

.btn-warning:hover {
    background-color: #e0a800;
}

.btn-danger:hover {
    background-color: #c82333;
}

.subtitle {
    color: #6c757d;
    margin-bottom: 20px;
    font-style: italic;
}

.alert-info {
    background-color: #d1ecf1;
    color: #0c5460;
    padding: 15px;
    border-radius: 4px;
    margin-top: 20px;
}
</style>

<?php include '../includes/footer.php'; ?>