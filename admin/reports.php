<?php
require_once '../includes/auth_check.php';
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/unauthorized.php");
    exit;
}

// Get fuel usage data
$stmt = $pdo->query("SELECT 
                    u.name as user_name,
                    c.vehicle_number,
                    fr.request_type,
                    SUM(fr.amount) as total_amount,
                    COUNT(fr.id) as request_count,
                    MAX(fr.requested_at) as last_request
                    FROM fuel_requests fr
                    JOIN users u ON fr.user_id = u.id
                    JOIN cars c ON fr.car_id = c.id
                    WHERE fr.status = 'approved'
                    GROUP BY u.name, c.vehicle_number, fr.request_type
                    ORDER BY total_amount DESC");
$fuel_data = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard">
    <aside class="sidebar">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="profiles.php">View All Profiles</a></li>
            <li><a href="cars.php">View All Cars</a></li>
            <li><a href="fuel_requests.php">Fuel Requests</a></li>
            <li class="active"><a href="reports.php">Reports</a></li>
            <li><a href="/fusys/includes/myprofile.php">My Profile</a></li>
        </ul>
    </aside>
    
    <main class="content">
        <h1>Fuel Usage Reports</h1>
        
        <div class="report-filters">
            <form method="get">
                <div class="form-group">
                    <label for="time_period">Time Period:</label>
                    <select id="time_period" name="time_period">
                        <option value="all">All Time</option>
                        <option value="year">This Year</option>
                        <option value="month">This Month</option>
                        <option value="week">This Week</option>
                    </select>
                </div>
                <button type="submit" class="btn">Apply Filter</button>
            </form>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Driver</th>
                    <th>Car</th>
                    <th>Request Type</th>
                    <th>Total Amount</th>
                    <th>Request Count</th>
                    <th>Last Request</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fuel_data as $data): ?>
                <tr>
                    <td><?php echo htmlspecialchars($data['user_name']); ?></td>
                    <td><?php echo htmlspecialchars($data['vehicle_number']); ?></td>
                    <td><?php echo ucfirst($data['request_type']); ?></td>
                    <td><?php echo $data['total_amount'] . ($data['request_type'] === 'litres' ? 'L' : '₹'); ?></td>
                    <td><?php echo $data['request_count']; ?></td>
                    <td><?php echo date('M j, Y', strtotime($data['last_request'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="chart-container">
            <canvas id="fuelUsageChart"></canvas>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Fuel usage chart
    const ctx = document.getElementById('fuelUsageChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_map(function($d) { return $d['user_name'] . ' - ' . $d['vehicle_number']; }, $fuel_data)); ?>,
            datasets: [{
                label: 'Fuel Usage',
                data: <?php echo json_encode(array_column($fuel_data, 'total_amount')); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>