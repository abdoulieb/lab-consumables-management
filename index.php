<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Get current organization's drugs
$user_id = $_SESSION['user_id'];
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
    <?php if (isLoggedIn()): ?>
        <button class="btn btn-success" onclick="toggleForm()">Add New Item</button>
    <?php endif; ?>

    <!-- Add Drug Form -->
    <div id="addDrugForm" class="card mt-4" style="display:none;">
        <div class="card-header">Add New Items</div>
        <div class="card-body">
            <form action="add_drug.php" method="POST">
                <!-- Form fields same as in original HTML -->
                <!-- ... -->
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
        <!-- Date range inputs same as in original HTML -->
        <!-- ... -->
    </form>

    <!-- Drug Stock Table -->
    <div class="card mt-4">
        <div class="card-header">Consumables Item Overview</div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <!-- Table headers same as in original HTML -->
                <tbody>
                    <?php foreach ($drugs as $drug): ?>
                        <tr>
                            <!-- Table rows same as in original HTML -->
                            <!-- ... -->
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