<?php
require_once '../includes/auth_check.php';
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/unauthorized.php");
    exit;
}

// Get all users
$stmt = $pdo->query("SELECT * FROM users WHERE deleted_at IS NULL ORDER BY name");
$users = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard">
    <aside class="sidebar">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li class="active"><a href="profiles.php">View All Profiles</a></li>
            <li><a href="cars.php">View All Cars</a></li>
            <li><a href="fuel_requests.php">Fuel Requests</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="/fusys/includes/myprofile.php">My Profile</a></li>
        </ul>
    </aside>
    
    <main class="content">
        <h1>All User Profiles</h1>
        
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                    <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                    <td><?php echo ucfirst($user['role']); ?></td>
                    <td>
                        <a href="view_profile.php?id=<?php echo $user['id']; ?>" class="btn btn-view">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</div>

<?php include '../includes/footer.php'; ?>