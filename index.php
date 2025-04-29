<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Get current organization's drugs
<<<<<<< HEAD
$user_id = $_SESSION['user_id'];
=======
>>>>>>> f71fa435217b073ae6e412306806f430fca0e6f7
$org_id = $_SESSION['organization_id'];

$sql = "SELECT * FROM drug WHERE organization_id = ? AND remaining != 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $org_id);
$stmt->execute();
$result = $stmt->get_result();
$drugs = $result->fetch_all(MYSQLI_ASSOC);

// Check for low stock and expired items
$today = date('Y-m-d');
foreach ($drugs as &$drug) {
    if ($drug['remaining'] <= $drug['reorder_limit']) {
        flash('warning', 'Reorder ' . $drug['name'] . ' - This item is below the reorder limit! Remaining stock: ' . $drug['remaining']);
    }

    if ($drug['expiry_date'] < $today) {
        // Update is_maintained status
        $update_sql = "UPDATE drug SET is_maintained = FALSE WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $drug['id']);
        $update_stmt->execute();
        $drug['is_maintained'] = false;
    } else {
        $drug['is_maintained'] = true;
    }
}

include 'header.php';
?>

<div class="container mt-4">
    <h2 class="text-center">Lab Consumables Management System</h2>

    <!-- Flash Messages -->
    <?php flash('success'); ?>
    <?php flash('danger'); ?>
    <?php flash('warning'); ?>

    <!-- Button to Toggle Add Drug Form -->
<<<<<<< HEAD
    <?php if (isLoggedIn()): ?>
        <button class="btn btn-success" onclick="toggleForm()">Add New Item</button>
    <?php endif; ?>
=======
    <button class="btn btn-success" onclick="toggleForm()">Add New Item</button>
>>>>>>> f71fa435217b073ae6e412306806f430fca0e6f7

    <!-- Add Drug Form -->
    <div id="addDrugForm" class="card mt-4" style="display:none;">
        <div class="card-header">Add New Items</div>
        <div class="card-body">
            <form action="add_drug.php" method="POST">
<<<<<<< HEAD
                <!-- Form fields same as in original HTML -->
                <!-- ... -->
=======
                <div class="mb-3">
                    <label for="name" class="form-label">Item Name</label>
                    <input type="text" class="form-control" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="received_from" class="form-label">Received From</label>
                    <input type="text" class="form-control" name="received_from" required>
                </div>
                <div class="mb-3">
                    <label for="expiry_date" class="form-label">Expiry Date</label>
                    <input type="date" class="form-control" name="expiry_date" required>
                </div>
                <div class="mb-3">
                    <label for="lot_batch_number" class="form-label">Batch Number/Lot Number</label>
                    <input type="text" class="form-control" name="lot_batch_number" required>
                </div>
                <div class="mb-3">
                    <label for="reference_number" class="form-label">Reference Number</label>
                    <input type="text" class="form-control" name="reference_number" required>
                </div>
                <div class="mb-3">
                    <label for="company_name" class="form-label">Company Name</label>
                    <input type="text" class="form-control" name="company_name" required>
                </div>
                <div class="mb-3">
                    <label for="box_count" class="form-label">Box Count</label>
                    <input type="number" class="form-control" name="box_count" min="1" required>
                </div>
                <div class="mb-3">
                    <label for="pack_per_box" class="form-label">Packs per Box</label>
                    <input type="number" class="form-control" name="pack_per_box" min="1" required>
                </div>
                <div class="mb-3">
                    <label for="losses_adjustment" class="form-label">Losses/Adjustment</label>
                    <input type="number" class="form-control" name="losses_adjustment" min="0" value="0" required>
                </div>
                <div class="mb-3">
                    <label for="reorder_limit" class="form-label">Reorder Limit</label>
                    <input type="number" class="form-control" name="reorder_limit" min="1" required>
                </div>
                <button type="submit" class="btn btn-primary">Add Item</button>
>>>>>>> f71fa435217b073ae6e412306806f430fca0e6f7
            </form>
        </div>
    </div>

    <!-- Search Form -->
    <form action="search_drug.php" method="GET" class="d-flex mb-3 mt-4">
        <input type="text" name="search_query" class="form-control me-2" placeholder="Enter item name" required>
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <!-- Filter by Date Range Form -->
    <form action="summary.php" method="GET" class="mb-3 no-print">
<<<<<<< HEAD
        <!-- Date range inputs same as in original HTML -->
        <!-- ... -->
=======
        <div class="row">
            <div class="col-md-6">
                <label for="start_date" class="form-label">Start Date:</label>
                <input type="date" id="start_date" name="start_date" class="form-control mb-2">
            </div>
            <div class="col-md-6">
                <label for="end_date" class="form-label">End Date:</label>
                <input type="date" id="end_date" name="end_date" class="form-control mb-2">
            </div>
        </div>
        <button type="submit" class="btn btn-info">Generate Summary Report</button>
>>>>>>> f71fa435217b073ae6e412306806f430fca0e6f7
    </form>

    <!-- Drug Stock Table -->
    <div class="card mt-4">
        <div class="card-header">Consumables Item Overview</div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
<<<<<<< HEAD
                <!-- Table headers same as in original HTML -->
                <tbody>
                    <?php foreach ($drugs as $drug): ?>
                        <tr>
                            <!-- Table rows same as in original HTML -->
                            <!-- ... -->
=======
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Reception Date</th>
                        <th>Expiry Date</th>
                        <th>Total Stock</th>
                        <th>Box Count</th>
                        <th>Used</th>
                        <th>Losses/Adjustment</th>
                        <th>Remaining</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($drugs as $drug): ?>
                        <tr>
                            <td><?php echo $drug['id']; ?></td>
                            <td><?php echo htmlspecialchars($drug['name']); ?></td>
                            <td><?php echo $drug['reception_date']; ?></td>
                            <td><?php echo $drug['expiry_date']; ?></td>
                            <td><?php echo $drug['total_stock']; ?></td>
                            <td><?php echo $drug['box_count']; ?></td>
                            <td>
                                <form action="update_used.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="drug_id" value="<?php echo $drug['id']; ?>">
                                    <input type="number" name="used" class="form-control form-control-sm"
                                        value="<?php echo $drug['used']; ?>"
                                        min="0" max="<?php echo $drug['remaining']; ?>" required>
                                    <button type="submit" class="btn btn-primary btn-sm mt-1">Update</button>
                                </form>
                            </td>
                            <td><?php echo $drug['losses_adjustment']; ?></td>
                            <td><?php echo $drug['remaining']; ?></td>
                            <td>
                                <?php if ($drug['is_maintained']): ?>
                                    <span class="badge bg-success">Valid</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Expired</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$drug['is_maintained']): ?>
                                    <form action="remove_expired.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="drug_id" value="<?php echo $drug['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                                    </form>
                                <?php endif; ?>
                            </td>
>>>>>>> f71fa435217b073ae6e412306806f430fca0e6f7
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
    function toggleForm() {
        var form = document.getElementById("addDrugForm");
        if (form.style.display === "none" || form.style.display === "") {
            form.style.display = "block";
        } else {
            form.style.display = "none";
        }
    }
</script>