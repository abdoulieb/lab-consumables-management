<?php
require_once 'config.php';

if (!isLoggedIn()) {
    flash('danger', 'You must be logged in to perform this action.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['drug_id'])) {
    $drug_id = intval($_POST['drug_id']);

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

        if ($drug['is_maintained']) {
            throw new Exception("Only expired items can be removed.");
        }

        // Create a new ExpiredDrug entry
        $insert_sql = "INSERT INTO expired_drug (
            name, reception_date, expiry_date, total_stock, used, remaining, 
            received_from, box_count, pack_per_box, lot_batch_number, 
            company_name, reference_number, reorder_limit, date_finished, 
            first_used_date, organization_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param(
            "sssiiisiisssissi",
            $drug['name'],
            $drug['reception_date'],
            $drug['expiry_date'],
            $drug['total_stock'],
            $drug['used'],
            $drug['remaining'],
            $drug['received_from'],
            $drug['box_count'],
            $drug['pack_per_box'],
            $drug['lot_batch_number'],
            $drug['company_name'],
            $drug['reference_number'],
            $drug['reorder_limit'],
            date('Y-m-d'),  // date_finished
            $drug['first_used_date'],
            $_SESSION['organization_id']
        );

        if (!$insert_stmt->execute()) {
            throw new Exception("Failed to create expired drug record.");
        }

        // Delete the original drug
        $delete_sql = "DELETE FROM drug WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $drug_id);

        if (!$delete_stmt->execute()) {
            throw new Exception("Failed to delete drug.");
        }

        flash('success', 'Expired item removed and moved to expired items!');
    } catch (Exception $e) {
        flash('danger', 'Error removing expired item: ' . $e->getMessage());
    }
}

redirect('index.php');
