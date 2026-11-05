<?php
require_once '../includes/auth_check.php';
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/unauthorized.php");
    exit;
}

// Check if car ID is provided
if (!isset($_GET['id'])) {
    header("Location: cars.php");
    exit;
}

$car_id = $_GET['id'];

// Fetch car details
$stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$car_id]);
$car = $stmt->fetch();

if (!$car) {
    header("Location: cars.php?error=Car not found");
    exit;
}

// Format dates for display
$in_use_from = date('F j, Y', strtotime($car['in_use_from']));
$relieved_on = $car['relieved_on'] ? date('F j, Y', strtotime($car['relieved_on'])) : 'Still Active';
$created_at = date('F j, Y, g:i a', strtotime($car['created_at']));
$updated_at = date('F j, Y, g:i a', strtotime($car['updated_at']));
?>


<?php include '../includes/header.php'; ?>

<div class="dashboard">
    <aside class="sidebar">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="profiles.php">View All Profiles</a></li>
            <li><a href="cars.php">View All Cars</a></li>
            <li><a href="fuel_requests.php">Fuel Requests</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="/fusys/includes/myprofile.php">My Profile</a></li>
        </ul>
    </aside>
    
    <main class="content">
    <link rel="stylesheet" href="/fusys/assets/css/admin_view_car.css">
        <div class="car-header">
            <h1>Vehicle Details</h1>
            <a href="cars.php" class="btn btn-back">Back to All Cars</a>
        </div>
        
        <div class="car-details">
            <div class="detail-group">
                <span class="detail-label">Vehicle Number:</span>
                <span class="detail-value"><?php echo htmlspecialchars($car['vehicle_number']); ?></span>
            </div>
            
            <div class="detail-group">
                <span class="detail-label">Vehicle Type:</span>
                <span class="detail-value"><?php echo str_replace('_', ' ', ucfirst($car['type'])); ?></span>
            </div>
            
            <div class="detail-group">
                <span class="detail-label">Owner Name:</span>
                <span class="detail-value"><?php echo htmlspecialchars($car['owner_name']); ?></span>
            </div>
            
            <div class="detail-group">
                <span class="detail-label">In Use From:</span>
                <span class="detail-value"><?php echo $in_use_from; ?></span>
            </div>
            
            <div class="detail-group">
                <span class="detail-label">Current Status:</span>
                <span class="detail-value status-<?php echo $car['relieved_on'] ? 'relieved' : 'active'; ?>">
                    <?php echo $car['relieved_on'] ? 'Relieved' : 'Active'; ?>
                </span>
            </div>
            
            <?php if ($car['relieved_on']): ?>
            <div class="detail-group">
                <span class="detail-label">Relieved On:</span>
                <span class="detail-value"><?php echo $relieved_on; ?></span>
            </div>
            <?php endif; ?>
            
            <div class="detail-group">
                <span class="detail-label">Record Created:</span>
                <span class="detail-value"><?php echo $created_at; ?></span>
            </div>
            
            <div class="detail-group">
                <span class="detail-label">Last Updated:</span>
                <span class="detail-value"><?php echo $updated_at; ?></span>
            </div>
        </div>
        
    </main>
</div>

<?php include '../includes/footer.php'; ?>