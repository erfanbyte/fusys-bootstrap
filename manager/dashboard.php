<?php
require_once '../includes/auth_check.php';
if ($_SESSION['user_role'] !== 'manager') {
    header("Location: ../auth/unauthorized.php");
    exit;
}
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard">
    <aside class="sidebar">
        <ul>
            <li class="active"><a href="dashboard.php">Dashboard</a></li>
            <li><a href="profiles.php">Manage Profiles</a></li>
            <li><a href="cars.php">Manage Cars</a></li>
            <li><a href="requests.php">Deletion Requests</a></li>
            <li><a href="/fusys/includes/myprofile.php">My Profile</a></li>
            <li><a href="user_management.php">User Management</a></li>
        </ul>
    </aside>
    
    <main class="content">
        <h1>Manager Dashboard</h1>
        
        <div class="stats">
            <div class="stat-card">
                <h3>Active Users</h3>
                <?php
                $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL");
                echo "<p>" . $stmt->fetchColumn() . "</p>";
                ?>
            </div>
            
            <div class="stat-card">
                <h3>Active Cars</h3>
                <?php
                $stmt = $pdo->query("SELECT COUNT(*) FROM cars WHERE relieved_on IS NULL AND deleted_at IS NULL");
                echo "<p>" . $stmt->fetchColumn() . "</p>";
                ?>
            </div>
            
            <div class="stat-card">
                <h3>Pending Deletion Requests</h3>
                <?php
                $stmt = $pdo->query("SELECT COUNT(*) FROM deletion_requests WHERE status = 'pending'");
                echo "<p>" . $stmt->fetchColumn() . "</p>";
                ?>
            </div>
        </div>
        
        <div class="recent-activity">
            <h2>Recent Profile Changes</h2>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT a.action, a.created_at, u.name 
                                        FROM audit_log a 
                                        JOIN users u ON a.user_id = u.id 
                                        WHERE a.action LIKE 'profile%' OR a.action LIKE 'car%'
                                        ORDER BY a.created_at DESC LIMIT 10");
                    while ($row = $stmt->fetch()):
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $row['action'])); ?></td>
                        <td><?php echo date('M j, Y H:i', strtotime($row['created_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>