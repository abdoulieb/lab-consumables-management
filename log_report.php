<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Get query parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;
$search_query = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';

// Base query
$sql = "SELECT 
            du.id, 
            d.name AS drug_name, 
            u.username AS user_name, 
            du.updated_amount, 
            du.update_date 
        FROM drug_update du
        JOIN drug d ON du.drug_id = d.id
        JOIN user u ON du.user_id = u.id
        WHERE d.organization_id = ?";

$params = [$_SESSION['organization_id']];
$types = "i";

// Add date filters if provided
if ($start_date && $end_date) {
    $sql .= " AND du.update_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
}

// Add search filter if provided
if ($search_query) {
    $sql .= " AND (d.name LIKE ? OR u.username LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

// Prepare and execute the query
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$updates = $result->fetch_all(MYSQLI_ASSOC);

include 'header.php';
?>

<div class="container mt-4">
    <h2 class="text-center">Log Report</h2>

    <!-- Date Filter Form -->
    <form action="log_report.php" method="GET" class="mb-3">
        <!-- Form fields same as in original HTML -->
        <!-- ... -->
    </form>

    <!-- Log Report Table -->
    <div class="card">
        <div class="card-header">Update Logs</div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <!-- Table headers same as in original HTML -->
                <tbody>
                    <?php if (count($updates) > 0): ?>
                        <?php foreach ($updates as $update): ?>
                            <tr>
                                <!-- Table rows same as in original HTML -->
                                <!-- ... -->
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No updates found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>