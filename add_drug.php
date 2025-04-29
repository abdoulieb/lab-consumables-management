<?php
require_once 'config.php';

if (!isLoggedIn()) {
    flash('danger', 'You must be logged in to perform this action.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Extract form data
        $name = trim($_POST['name']);
        $received_from = trim($_POST['received_from']);
        $box_count = intval($_POST['box_count']);
        $pack_per_box = intval($_POST['pack_per_box']);
        $lot_batch_number = trim($_POST['lot_batch_number']);
        $company_name = trim($_POST['company_name']);
        $expiry_date = trim($_POST['expiry_date']);
        $reference_number = trim($_POST['reference_number']);
        $reorder_limit = intval($_POST['reorder_limit']);
        $losses_adjustment = intval($_POST['losses_adjustment']);

        // Calculate total stock and remaining stock
        $total_stock = $box_count * $pack_per_box;
        $remaining = $total_stock - $losses_adjustment;

        // Prepare SQL statement
        $sql = "INSERT INTO drug (name, received_from, box_count, pack_per_box, lot_batch_number, 
                company_name, reference_number, expiry_date, total_stock, remaining, losses_adjustment, 
                reorder_limit, organization_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssiissssiisii",
            $name,
            $received_from,
            $box_count,
            $pack_per_box,
            $lot_batch_number,
            $company_name,
            $reference_number,
            $expiry_date,
            $total_stock,
            $remaining,
            $losses_adjustment,
            $reorder_limit,
            $_SESSION['organization_id']
        );

        if ($stmt->execute()) {
            flash('success', 'Item added successfully!');
        } else {
            throw new Exception("Database error: " . $stmt->error);
        }
    } catch (Exception $e) {
        flash('danger', 'Error adding item: ' . $e->getMessage());
    }
}

redirect('index.php');
