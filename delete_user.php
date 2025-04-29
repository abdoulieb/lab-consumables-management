<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    flash('danger', 'You do not have permission to perform this action.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);

    try {
        // Check if user exists and belongs to the same organization
        $sql = "SELECT id FROM user WHERE id = ? AND organization_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $_SESSION['organization_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            throw new Exception("User not found or doesn't belong to your organization.");
        }

        // Don't allow deleting yourself
        if ($user_id == $_SESSION['user_id']) {
            throw new Exception("You cannot delete your own account.");
        }

        // Delete the user
        $delete_sql = "DELETE FROM user WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $user_id);

        if ($delete_stmt->execute()) {
            flash('success', 'User deleted successfully!');
        } else {
            throw new Exception("Failed to delete user.");
        }
    } catch (Exception $e) {
        flash('danger', 'Error deleting user: ' . $e->getMessage());
    }
}

redirect('manage_users.php');
