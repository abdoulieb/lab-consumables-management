<?php
require_once 'config.php';

if (!isLoggedIn()) {
    flash('danger', 'You must be logged in to perform this action.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['drug_id'])) {
    $drug_id = intval($_POST['drug_id']);
    $used = intval($_POST['used']);

    try {
        // Get the drug
        $sql = "SELECT * FROM drug WHERE id = ? AND organization_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $drug_id, $_SESSION['organization_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $drug = $result->fetch_assoc();

        if (!$drug) {
            throw new Exception("Drug not found or doesn't belong to your organization.");
        }

        if ($used > $drug['remaining']) {
            throw new Exception("Used quantity cannot exceed remaining stock!");
        }

        // Calculate new values
        $new_used = $drug['used'] + $used;
        $new_remaining = $drug['total_stock'] - $new_used;

        // Check if it's the first time the drug is being used
        $first_used_date = $drug['used'] == 0 && $used > 0 ? date('Y-m-d') : $drug['first_used_date'];
        
        // Check if the drug has finished
        $date_finished = $new_remaining == 0 ? date('Y-m-d') : $drug['date_finished'];

        // Update the drug
        $update_sql = "UPDATE drug SET used = ?, remaining = ?, first_used_date = ?, date_finished = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("iissi", $new_used, $new_remaining, $first_used_date, $date_finished, $drug_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update drug.");
        }

        // Record the update
        $log_sql = "INSERT INTO drug_update (drug_id, user_id, updated_amount) VALUES (?, ?, ?)";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->bind_param("iii", $drug_id, $_SESSION['user_id'], $used);
        
        if (!$log_stmt->execute()) {
            throw new Exception("Failed to log update.");
        }

        flash('success', 'Used quantity updated successfully!');
    } catch (Exception $e) {
        flash('danger', 'Error updating used quantity: ' . $e->getMessage());
    }
}

redirect('index.php');
?>