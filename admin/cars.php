<?php
require_once '../includes/auth_check.php';
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/unauthorized.php");
    exit;
}

// Get all cars
$stmt = $pdo->query("SELECT * FROM cars WHERE deleted_at IS NULL ORDER BY vehicle_number");
$cars = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard">
    <aside class="sidebar">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="profiles.php">View All Profiles</a></li>
            <li class="active"><a href="cars.php">View All Cars</a></li>
            <li><a href="fuel_requests.php">Fuel Requests</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="/fusys/includes/myprofile.php">My Profile</a></li>
        </ul>
    </aside>
    
    <main class="content">
        <h1>All Cars</h1>
        
        <table>
            <thead>
                <tr>
                    <th>Vehicle Number</th>
                    <th>Type</th>
                    <th>Owner</th>
                    <th>In Use From</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cars as $car): ?>
                <tr>
                    <td><?php echo htmlspecialchars($car['vehicle_number']); ?></td>
                    <td><?php echo str_replace('_', ' ', ucfirst($car['type'])); ?></td>
                    <td><?php echo htmlspecialchars($car['owner_name']); ?></td>
                    <td><?php echo date('M j, Y', strtotime($car['in_use_from'])); ?></td>
                    <td><?php echo $car['relieved_on'] ? 'Relieved' : 'Active'; ?></td>
                    <td>
                        <a href="view_car.php?id=<?php echo $car['id']; ?>" class="btn btn-view">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</div>

<?php include '../includes/footer.php'; ?>