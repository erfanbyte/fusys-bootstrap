<?php
require_once '../includes/auth_check.php';
if ($_SESSION['user_role'] !== 'manager') {
    header("Location: ../auth/unauthorized.php");
    exit;
}

// Handle deletion request approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    
    if (in_array($action, ['approve', 'reject'])) {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        
        // Update request status
        $pdo->prepare("UPDATE deletion_requests SET status = ?, processed_at = NOW(), processed_by = ? WHERE id = ?")
            ->execute([$status, $_SESSION['user_id'], $request_id]);
        
        // If approved, delete the user
        if ($action === 'approve') {
            $stmt = $pdo->prepare("SELECT user_id FROM deletion_requests WHERE id = ?");
            $stmt->execute([$request_id]);
            $user_id = $stmt->fetchColumn();
            
            $pdo->prepare("UPDATE users SET deleted_at = NOW() WHERE id = ?")->execute([$user_id]);
        }
        
        // Log action
        $pdo->prepare("INSERT INTO audit_log (user_id, action, ip_address) VALUES (?, ?, ?)")
            ->execute([$_SESSION['user_id'], "deletion_request_$action", $_SERVER['REMOTE_ADDR']]);
        
        header("Location: requests.php");
        exit;
    }
}

// Get all deletion requests
$stmt = $pdo->query("SELECT dr.*, u.name as user_name 
                    FROM deletion_requests dr
                    JOIN users u ON dr.user_id = u.id
                    ORDER BY dr.requested_at DESC");
$requests = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard">
    <aside class="sidebar">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="profiles.php">Manage Profiles</a></li>
            <li><a href="cars.php">Manage Cars</a></li>
            <li class="active"><a href="requests.php">Deletion Requests</a></li>
            <li><a href="/fusys/includes/myprofile.php">My Profile</a></li>
            <li><a href="user_management.php">User Management</a></li>
        </ul>
    </aside>
    
    <main class="content">
        <h1>Profile Deletion Requests</h1>
        
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Requested At</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                <tr>
                    <td><?php echo htmlspecialchars($request['user_name']); ?></td>
                    <td><?php echo date('M j, Y H:i', strtotime($request['requested_at'])); ?></td>
                    <td class="status-<?php echo $request['status']; ?>">
                        <?php echo ucfirst($request['status']); ?>
                    </td>
                    <td>
                        <?php if ($request['status'] === 'pending'): ?>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-approve">Approve</button>
                        </form>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn btn-reject">Reject</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</div>

<?php include '../includes/footer.php'; ?>