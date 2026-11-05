<?php
require_once '../includes/auth_check.php';
if ($_SESSION['user_role'] !== 'manager') {
    header("Location: ../auth/unauthorized.php");
    exit;
}

// Handle car deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_car'])) {
    $car_id = $_POST['car_id'];
    $pdo->prepare("UPDATE cars SET deleted_at = NOW() WHERE id = ?")->execute([$car_id]);
    
    // Log action
    $pdo->prepare("INSERT INTO audit_log (user_id, action, ip_address) VALUES (?, ?, ?)")
        ->execute([$_SESSION['user_id'], "car_deleted", $_SERVER['REMOTE_ADDR']]);
    
    header("Location: cars.php");
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
            <li><a href="profiles.php">Manage Profiles</a></li>
            <li class="active"><a href="cars.php">Manage Cars</a></li>
            <li><a href="requests.php">Deletion Requests</a></li>
            <li><a href="/fusys/includes/myprofile.php">My Profile</a></li>
            <li><a href="user_management.php">User Management</a></li>
        </ul>
    </aside>
    
    <main class="content">
        <h1>Manage Cars</h1>
        
        <div class="actions">
            <a href="add_car.php" class="btn btn-add">Add New Car</a>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Registration</th>
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
                    <td class="actions">
                        <a href="edit_car.php?id=<?php echo $car['id']; ?>" class="btn btn-edit">Edit</a>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                            <button type="submit" name="delete_car" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this car?')">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</div>

<?php include '../includes/footer.php'; ?>