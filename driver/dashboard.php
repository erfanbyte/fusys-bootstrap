<?php
require_once '../includes/auth_check.php';
if ($_SESSION['user_role'] !== 'driver') {
    header("Location: ../auth/unauthorized.php");
    exit;
}

// Get driver's assigned cars
$stmt = $pdo->prepare("SELECT c.* FROM user_cars uc
                      JOIN cars c ON uc.car_id = c.id
                      WHERE uc.user_id = ? AND uc.unassigned_at IS NULL AND c.deleted_at IS NULL");
$stmt->execute([$_SESSION['user_id']]);
$assigned_cars = $stmt->fetchAll();

// Get driver's recent fuel requests
$stmt = $pdo->prepare("SELECT fr.*, c.vehicle_number 
                      FROM fuel_requests fr
                      JOIN cars c ON fr.car_id = c.id
                      WHERE fr.user_id = ?
                      ORDER BY fr.requested_at DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$recent_requests = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard">
    <aside class="sidebar">
        <ul>
            <li class="active"><a href="dashboard.php">Dashboard</a></li>
            <li><a href="/fusys/includes/myprofile.php">My Profile</a></li>
            <li><a href="fuel_request.php">Request Fuel</a></li>
            <li><a href="history.php">Request History</a></li>
        </ul>
    </aside>
    
    <main class="content">
        <h1>Driver Dashboard</h1>
        
        <div class="stats">
            <div class="stat-card">
                <h3>Assigned Cars</h3>
                <p><?php echo count($assigned_cars); ?></p>
            </div>
            
            <div class="stat-card">
                <h3>Pending Requests</h3>
                <?php
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM fuel_requests WHERE user_id = ? AND status = 'pending'");
                $stmt->execute([$_SESSION['user_id']]);
                echo "<p>" . $stmt->fetchColumn() . "</p>";
                ?>
            </div>
            
            <div class="stat-card">
                <h3>Approved Requests</h3>
                <?php
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM fuel_requests WHERE user_id = ? AND status = 'approved'");
                $stmt->execute([$_SESSION['user_id']]);
                echo "<p>" . $stmt->fetchColumn() . "</p>";
                ?>
            </div>
        </div>
        
        <div class="assigned-cars">
            <h2>My Assigned Cars</h2>
            <?php if (empty($assigned_cars)): ?>
                <p>No cars assigned to you.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($assigned_cars as $car): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($car['vehicle_number']); ?></strong> - 
                        <?php echo str_replace('_', ' ', ucfirst($car['type'])); ?> - 
                        Assigned since <?php echo date('M j, Y', strtotime($car['in_use_from'])); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <div class="recent-requests">
            <h2>Recent Fuel Requests</h2>
            <?php if (empty($recent_requests)): ?>
                <p>No recent fuel requests.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Car</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Requested At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['vehicle_number']); ?></td>
                            <td><?php echo ucfirst($request['request_type']); ?></td>
                            <td><?php echo $request['amount'] . ($request['request_type'] === 'litres' ? 'L' : '₹'); ?></td>
                            <td class="status-<?php echo $request['status']; ?>">
                                <?php echo ucfirst($request['status']); ?>
                            </td>
                            <td><?php echo date('M j, Y H:i', strtotime($request['requested_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><a href="history.php">View full history</a></p>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>