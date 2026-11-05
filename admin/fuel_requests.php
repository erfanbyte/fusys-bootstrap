<?php
require_once '../includes/auth_check.php';
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/unauthorized.php");
    exit;
}

// Handle request approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    
    if (in_array($action, ['approve', 'reject'])) {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $stmt = $pdo->prepare("UPDATE fuel_requests SET status = ?, processed_at = NOW(), processed_by = ? WHERE id = ?");
        $stmt->execute([$status, $_SESSION['user_id'], $request_id]);
        
        // Log action
        $pdo->prepare("INSERT INTO audit_log (user_id, action, ip_address) VALUES (?, ?, ?)")
            ->execute([$_SESSION['user_id'], "fuel_request_$status", $_SERVER['REMOTE_ADDR']]);
        
        header("Location: fuel_requests.php");
        exit;
    }
}

// Get all fuel requests
$stmt = $pdo->query("SELECT fr.*, u.name as user_name, c.vehicle_number 
                    FROM fuel_requests fr
                    JOIN users u ON fr.user_id = u.id
                    JOIN cars c ON fr.car_id = c.id
                    ORDER BY fr.requested_at DESC");
$requests = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard">
    <aside class="sidebar">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="profiles.php">View All Profiles</a></li>
            <li><a href="cars.php">View All Cars</a></li>
            <li class="active"><a href="fuel_requests.php">Fuel Requests</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="../includes/myprofile.php">My Profile</a></li>
        </ul>
    </aside>
    
    <main class="content">
        <h1>Fuel Requests</h1>
        
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Car</th>
                    <th>Request Type</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Requested At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                <tr>
                    <td><?php echo htmlspecialchars($request['user_name']); ?></td>
                    <td><?php echo htmlspecialchars($request['vehicle_number']); ?></td>
                    <td><?php echo ucfirst($request['request_type']); ?></td>
                    <td><?php echo $request['amount'] . ($request['request_type'] === 'litres' ? 'L' : '₹'); ?></td>
                    <td class="status-<?php echo $request['status']; ?>">
                        <?php echo ucfirst($request['status']); ?>
                    </td>
                    <td><?php echo date('M j, Y H:i', strtotime($request['requested_at'])); ?></td>
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