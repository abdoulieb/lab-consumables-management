<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$query = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';

// Fetch drugs only for the current organization
$sql = "SELECT * FROM drug WHERE organization_id = ? AND name LIKE ?";
$stmt = $conn->prepare($sql);
$search_param = "%$query%";
$stmt->bind_param("is", $_SESSION['organization_id'], $search_param);
$stmt->execute();
$result = $stmt->get_result();
$drugs = $result->fetch_all(MYSQLI_ASSOC);

// Check for expired items
$today = date('Y-m-d');
foreach ($drugs as &$drug) {
    if ($drug['expiry_date'] < $today) {
        $drug['is_maintained'] = false;
    } else {
        $drug['is_maintained'] = true;
    }
}

include 'header.php';
?>

<div class="container mt-4">
    <h2 class="text-center">Search Results</h2>

    <!-- Drug Stock Table -->
    <div class="card mt-4">
        <div class="card-header">Consumables Item Overview</div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <!-- Table same as in index.php -->
                <!-- ... -->
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>