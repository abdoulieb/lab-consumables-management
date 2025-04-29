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

<<<<<<< HEAD
=======
$sql .= " ORDER BY du.update_date DESC";

>>>>>>> f71fa435217b073ae6e412306806f430fca0e6f7
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
<<<<<<< HEAD
        <!-- Form fields same as in original HTML -->
        <!-- ... -->
=======
        <div class="row">
            <div class="col-md-4">
                <label for="start_date" class="form-label">Start Date:</label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date:</label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-4">
                <label for="search_query" class="form-label">Search:</label>
                <input type="text" id="search_query" name="search_query" class="form-control"
                    placeholder="Search by item or user" value="<?php echo htmlspecialchars($search_query); ?>">
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-md-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Filter & Search</button>
            </div>
        </div>
>>>>>>> f71fa435217b073ae6e412306806f430fca0e6f7
    </form>

    <!-- Log Report Table -->
    <div class="card">
        <div class="card-header">Update Logs</div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
<<<<<<< HEAD
                <!-- Table headers same as in original HTML -->
=======
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Item Name</th>
                        <th>User Name</th>
                        <th>Updated Amount</th>
                        <th>Update Date</th>
                    </tr>
                </thead>
>>>>>>> f71fa435217b073ae6e412306806f430fca0e6f7
                <tbody>
                    <?php if (count($updates) > 0): ?>
                        <?php foreach ($updates as $update): ?>
                            <tr>
<<<<<<< HEAD
                                <!-- Table rows same as in original HTML -->
                                <!-- ... -->
=======
                                <td><?php echo $update['id']; ?></td>
                                <td><?php echo htmlspecialchars($update['drug_name']); ?></td>
                                <td><?php echo htmlspecialchars($update['user_name']); ?></td>
                                <td><?php echo $update['updated_amount']; ?></td>
                                <td><?php echo $update['update_date']; ?></td>
>>>>>>> f71fa435217b073ae6e412306806f430fca0e6f7
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
<<<<<<< HEAD
                            <td colspan="6" class="text-center">No updates found.</td>
=======
                            <td colspan="5" class="text-center">No updates found.</td>
>>>>>>> f71fa435217b073ae6e412306806f430fca0e6f7
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>