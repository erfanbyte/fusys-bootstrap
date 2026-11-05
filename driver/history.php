<?php
require_once '../includes/auth_check.php';
if ($_SESSION['user_role'] !== 'driver') {
    header("Location: ../auth/unauthorized.php");
    exit;
}

// Get all fuel requests for this user
$stmt = $pdo->prepare("SELECT fr.*, c.vehicle_number 
                      FROM fuel_requests fr
                      JOIN cars c ON fr.car_id = c.id
                      WHERE fr.user_id = ?
                      ORDER BY fr.requested_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$requests = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard">
    <aside class="sidebar">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="/fusys/includes/myprofile.php">My Profile</a></li>
            <li><a href="fuel_request.php">Request Fuel</a></li>
            <li class="active"><a href="history.php">Request History</a></li>
        </ul>
    </aside>
    
    <main class="content">
        <h1>My Fuel Request History</h1>
        
        <?php if (empty($requests)): ?>
            <p>No fuel requests found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Car</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Requested At</th>
                        <th>Processed At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['vehicle_number']); ?></td>
                        <td><?php echo ucfirst($request['request_type']); ?></td>
                        <td><?php echo $request['amount'] . ($request['request_type'] === 'litres' ? 'L' : '₹'); ?></td>
                        <td class="status-<?php echo $request['status']; ?>">
                            <?php echo ucfirst($request['status']); ?>
                        </td>
                        <td><?php echo date('M j, Y H:i', strtotime($request['requested_at'])); ?></td>
                        <td>
                            <?php echo $request['processed_at'] ? date('M j, Y H:i', strtotime($request['processed_at'])) : '--'; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>
</div>

<?php include '../includes/footer.php'; ?>