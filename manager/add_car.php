<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SESSION['user_role'] !== 'manager') {
    header("Location: ../auth/unauthorized.php");
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_number = trim($_POST['vehicle_number']);
    $type = $_POST['type'];
    $owner = trim($_POST['owner_name']);
    $in_use_from = $_POST['in_use_from'];
    
    // Validation
    if (empty($vehicle_number)) $errors[] = "Vehicle number is required";
    if (empty($owner)) $errors[] = "Owner name is required";
    if (empty($in_use_from)) $errors[] = "In use from date is required";
    
    // Check if vehicle number exists
    $stmt = $pdo->prepare("SELECT id FROM cars WHERE vehicle_number = ?");
    $stmt->execute([$vehicle_number]);
    if ($stmt->fetch()) $errors[] = "Vehicle number already exists";
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO cars (vehicle_number, type, owner_name, in_use_from) 
                              VALUES (?, ?, ?, ?)");
        $stmt->execute([$vehicle_number, $type, $owner, $in_use_from]);
        
        // Log action
        $pdo->prepare("INSERT INTO audit_log (user_id, action, ip_address) VALUES (?, ?, ?)")
            ->execute([$_SESSION['user_id'], "car_added", $_SERVER['REMOTE_ADDR']]);
            
        $success = "Car added successfully!";
        header("Location: cars.php?success=" . urlencode($success));
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard">
    <?php include __DIR__ . '/../includes/manager_sidebar.php'; ?>
    
    <main class="content">
        <h1>Add New Vehicle</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="vehicle_number">Vehicle Number*</label>
                <input type="text" id="vehicle_number" name="vehicle_number" required
                        pattern="[A-Z]{2}[0-9]{1,2}[A-Z]{1,2}[0-9]{4}"
                        placeholder="Enter valid Indian vehicle number (e.g. MH01AB1234)">
                
            </div>
            
            <div class="form-group">
                <label for="type">Vehicle Type*</label>
                <select id="type" name="type" required>
                    <option value="4_seater">4 Seater</option>
                    <option value="6_seater">6 Seater</option>
                    <option value="mini_van">Mini Van</option>
                    <option value="bus">Bus</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="owner_name">Owner Name*</label>
                <input type="text" id="owner_name" name="owner_name" required>
            </div>
            
            <div class="form-group">
                <label for="in_use_from">In Use From*</label>
                <input type="date" id="in_use_from" name="in_use_from" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Add Vehicle</button>
            <a href="cars.php" class="btn btn-secondary">Cancel</a>
        </form>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>