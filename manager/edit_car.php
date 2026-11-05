<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SESSION['user_role'] !== 'manager') {
    header("Location: ../auth/unauthorized.php");
    exit;
}

// Check if car ID is provided
if (!isset($_GET['id'])) {
    header("Location: cars.php");
    exit;
}

$car_id = $_GET['id'];
$errors = [];
$success = '';

// Fetch car details
$stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$car_id]);
$car = $stmt->fetch();

if (!$car) {
    header("Location: cars.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_number = trim($_POST['vehicle_number']);
    $type = $_POST['type'];
    $owner = trim($_POST['owner_name']);
    $in_use_from = $_POST['in_use_from'];
    $relieved_on = !empty($_POST['relieved_on']) ? $_POST['relieved_on'] : null;
    
    // Validation
    if (empty($vehicle_number)) $errors[] = "Vehicle number is required";
    if (empty($owner)) $errors[] = "Owner name is required";
    if (empty($in_use_from)) $errors[] = "In use from date is required";
    
    // Check if vehicle number exists for another car
    $stmt = $pdo->prepare("SELECT id FROM cars WHERE vehicle_number = ? AND id != ?");
    $stmt->execute([$vehicle_number, $car_id]);
    if ($stmt->fetch()) $errors[] = "Vehicle number already exists";
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE cars SET 
                              vehicle_number = ?, 
                              type = ?, 
                              owner_name = ?, 
                              in_use_from = ?, 
                              relieved_on = ?
                              WHERE id = ?");
        $stmt->execute([$vehicle_number, $type, $owner, $in_use_from, $relieved_on, $car_id]);
        
        // Log action
        $pdo->prepare("INSERT INTO audit_log (user_id, action, ip_address) VALUES (?, ?, ?)")
            ->execute([$_SESSION['user_id'], "car_updated", $_SERVER['REMOTE_ADDR']]);
            
        $success = "Car updated successfully!";
        header("Location: cars.php?success=" . urlencode($success));
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard">
    <?php include __DIR__ . '/../includes/manager_sidebar.php'; ?>
    
    <main class="content">
        <h1>Edit Vehicle</h1>
        
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
                        value="<?= htmlspecialchars($car['vehicle_number']) ?>"
                        placeholder="Enter valid Indian vehicle number (e.g. MH01AB1234)">
            </div>
            
            <div class="form-group">
                <label for="type">Vehicle Type*</label>
                <select id="type" name="type" required>
                    <option value="4_seater" <?= $car['type'] === '4_seater' ? 'selected' : '' ?>>4 Seater</option>
                    <option value="6_seater" <?= $car['type'] === '6_seater' ? 'selected' : '' ?>>6 Seater</option>
                    <option value="mini_van" <?= $car['type'] === 'mini_van' ? 'selected' : '' ?>>Mini Van</option>
                    <option value="bus" <?= $car['type'] === 'bus' ? 'selected' : '' ?>>Bus</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="owner_name">Owner Name*</label>
                <input type="text" id="owner_name" name="owner_name" required
                       value="<?= htmlspecialchars($car['owner_name']) ?>">
            </div>
            
            <div class="form-group">
                <label for="in_use_from">In Use From*</label>
                <input type="date" id="in_use_from" name="in_use_from" required
                       value="<?= htmlspecialchars($car['in_use_from']) ?>">
            </div>
            
            <div class="form-group">
                <label for="relieved_on">Relieved On (leave empty if still active)</label>
                <input type="date" id="relieved_on" name="relieved_on"
                       value="<?= htmlspecialchars($car['relieved_on']) ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">Update Vehicle</button>
            <a href="cars.php" class="btn btn-secondary">Cancel</a>
        </form>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>