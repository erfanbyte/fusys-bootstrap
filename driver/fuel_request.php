<?php
require_once '../includes/auth_check.php';
if ($_SESSION['user_role'] !== 'driver') {
    header("Location: ../auth/unauthorized.php");
    exit;
}

// Get ALL available cars (not deleted)
$stmt = $pdo->prepare("SELECT * FROM cars WHERE deleted_at IS NULL");
$stmt->execute();
$available_cars = $stmt->fetchAll();

// Debug: Check what cars were fetched
error_log("Available cars: " . print_r($available_cars, true));

// Current fuel rates
$fuel_rates = [
    'petrol' => 105.50,
    'diesel' => 95.30
];

// Handle fuel request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //$car_id = $_POST['car_id'];
    $car_id = !empty($_POST['car_id_actual']) ? $_POST['car_id_actual'] : $_POST['car_id'];
    $request_type = $_POST['request_type'];
    $amount = $_POST['amount'];
    $distance_km = $_POST['distance_km'] ?? null;
    $purpose = $_POST['purpose'] ?? '';
    $fuel_type = $_POST['fuel_type'] ?? null;
    
    // Validate inputs
    $errors = [];
    
    if (empty($car_id)) $errors[] = "Car selection is required";
    if (!in_array($request_type, ['litres', 'money'])) $errors[] = "Invalid request type";
    if (!is_numeric($amount) || $amount <= 0) $errors[] = "Amount must be a positive number";
    
    if ($request_type === 'litres') {
        if (empty($fuel_type)) $errors[] = "Fuel type is required for litre requests";
        if (!is_numeric($distance_km) || $distance_km <= 0) $errors[] = "Distance must be a positive number";
    }
    
    // Verify the car exists (no need to check assignment)
    $valid_car = false;
    foreach ($available_cars as $car) {
        if ($car['id'] == $car_id) {
            $valid_car = true;
            break;
        }
    }
    
    if (!$valid_car) $errors[] = "Invalid car selection";
    
    if (empty($errors)) {
        // Calculate fuel rate if requesting litres
        $fuel_rate = null;
        if ($request_type === 'litres' && isset($fuel_rates[$fuel_type])) {
            $fuel_rate = $fuel_rates[$fuel_type];
        }
        
        $stmt = $pdo->prepare("INSERT INTO fuel_requests 
                             (user_id, car_id, request_type, amount, fuel_type, distance_km, purpose, fuel_rate) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'], 
            $car_id, 
            $request_type, 
            $amount,
            $fuel_type,
            $distance_km,
            $purpose,
            $fuel_rate
        ]);
        
        // Log action
        $pdo->prepare("INSERT INTO audit_log (user_id, action, ip_address) VALUES (?, ?, ?)")
            ->execute([$_SESSION['user_id'], "fuel_request_submitted", $_SERVER['REMOTE_ADDR']]);
        
        $success = "Fuel request submitted successfully!";
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard">
    <aside class="sidebar">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="/fusys/includes/myprofile.php">My Profile</a></li>
            <li class="active"><a href="fuel_request.php">Request Fuel</a></li>
            <li><a href="history.php">Request History</a></li>
        </ul>
    </aside>
    
    <main class="content">
        <h1>Request Fuel</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" id="fuelRequestForm">
            <div class="form-group">
    <label for="car_id">Select Car</label>
    <input list="cars_list" id="car_id" name="car_id" class="form-control" placeholder="Type to search..." required>
    <datalist id="cars_list">
        <?php foreach ($available_cars as $car): ?>
            <option value="<?php echo htmlspecialchars($car['vehicle_number']); ?>" data-car-id="<?php echo htmlspecialchars($car['id']); ?>">
                <?php echo htmlspecialchars($car['vehicle_number']); ?> - 
                <?php echo isset($car['type']) ? str_replace('_', ' ', ucfirst($car['type'])) : 'N/A'; ?>
            </option>
        <?php endforeach; ?>
    </datalist>
    <input type="hidden" name="car_id_actual" id="car_id_actual">
</div>
            
            <!-- Rest of your form remains the same -->
            <div class="form-group">
                <label for="request_type">Request Type</label>
                <select id="request_type" name="request_type" required>
                    <option value="litres">Litres</option>
                    <option value="money">Money (₹)</option>
                </select>
            </div>
            
            <div class="form-group" id="fuelTypeGroup">
                    <label for="fuel_type">Fuel Type</label>
                    <select id="fuel_type" name="fuel_type">
                        <option value="">-- Select Fuel Type --</option>
                        <option value="petrol">Petrol</option>
                        <option value="diesel">Diesel</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="amount" id="amountLabel">Amount (Litres)</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0.01" required>
                </div>
                
                <div class="form-group" id="distanceGroup">
                    <label for="distance_km">Distance (km)</label>
                    <input type="number" id="distance_km" name="distance_km" min="1" value="">
                </div>
                
                <div class="form-group">
                    <label for="purpose">Purpose</label>
                    <textarea id="purpose" name="purpose" rows="3" placeholder="Enter purpose of the request"></textarea>
                </div>
                
                <button type="submit" class="btn">Submit Request</button>
         
        </form>
<script>
            // Show/hide fields based on request type
            document.getElementById('request_type').addEventListener('change', function() {
                const isLitres = this.value === 'litres';
                document.getElementById('fuelTypeGroup').style.display = isLitres ? 'block' : 'none';
                document.getElementById('amountLabel').textContent = isLitres ? 'Amount (Litres)' : 'Amount (₹)';
                
                // Make fields required/not required
                document.getElementById('fuel_type').required = isLitres;
                document.getElementById('distance_km').required = isLitres;
            });
            
            // Handle car selection from datalist
            document.getElementById('car_id').addEventListener('input', function() {
                const input = this.value.trim();
                const carIdActual = document.getElementById('car_id_actual');
                let foundCarId = null;
                
                // Try to find matching option
                const datalist = document.getElementById('cars_list');
                if (datalist && datalist.options) {
                    for (let i = 0; i < datalist.options.length; i++) {
                        if (datalist.options[i].value === input) {
                            foundCarId = datalist.options[i].getAttribute('data-car-id');
                            break;
                        }
                    }
                }
                
                carIdActual.value = foundCarId || '';
            });
            
            // Form submit validation
            document.getElementById('fuelRequestForm').addEventListener('submit', function(e) {
                const carId = document.getElementById('car_id').value.trim();
                const carIdActual = document.getElementById('car_id_actual').value.trim();
                
                if (!carId) {
                    e.preventDefault();
                    alert('Please select a car from the list');
                    return false;
                }
                
                if (!carIdActual) {
                    e.preventDefault();
                    alert('Invalid car selection. Please select a valid car from the dropdown.');
                    return false;
                }
            });
            
            // Trigger change event on page load
            document.getElementById('request_type').dispatchEvent(new Event('change'));
            </script>
    </main>
</div>

<?php include '../includes/footer.php'; ?>