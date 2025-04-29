<?php
require_once 'config.php';

// Check if system owner exists, if not create one (without organization)
$system_owner = $conn->query("SELECT * FROM user WHERE role = 'system_owner'")->fetch_assoc();
if (!$system_owner) {
    $hashed_password = passwordHash('system_owner_password');
    // System owner initially has no organization
    $conn->query("INSERT INTO user (username, password, email, is_admin, role) 
                 VALUES ('system_owner', '$hashed_password', 'system@owner.com', 1, 'system_owner')");
}

if (isLoggedIn() && $_SESSION['role'] != 'system_owner') {
    flash('danger', 'You do not have permission to access this page.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $org_name = trim($_POST['org_name']);
    $admin_username = trim($_POST['admin_username']);
    $admin_password = trim($_POST['admin_password']);

    try {
        // Start transaction
        $conn->begin_transaction();

        // 1. First create the organization
        $insert_org = $conn->prepare("INSERT INTO organization (name) VALUES (?)");
        $insert_org->bind_param("s", $org_name);

        if (!$insert_org->execute()) {
            throw new Exception("Failed to create organization.");
        }

        // Get the new organization ID
        $org_id = $conn->insert_id;

        // 2. Then create the admin user with the new organization_id
        $hashed_password = passwordHash($admin_password);
        $admin_email = $admin_username . "@organization.com";

        $insert_admin = $conn->prepare("INSERT INTO user (username, password, email, is_admin, organization_id, role) 
                                      VALUES (?, ?, ?, 1, ?, 'admin')");
        $insert_admin->bind_param("sssi", $admin_username, $hashed_password, $admin_email, $org_id);

        if (!$insert_admin->execute()) {
            throw new Exception("Failed to create admin user.");
        }

        // Commit the transaction
        $conn->commit();

        flash('success', 'Organization and admin user created successfully!');
        redirect('login.php');
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        flash('danger', 'Error: ' . $e->getMessage());
    }
}

include 'header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg p-4 bg-white">
                <div class="card-body">
                    <h3 class="text-center text-primary fw-bold">Register Organization</h3>
                    <p class="text-center text-muted">Fill in the details to register Organization</p>
                    <form action="register_organization.php" method="POST">
                        <div class="mb-3">
                            <label for="org_name" class="form-label fw-semibold">Organization Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-building"></i></span>
                                <input type="text" class="form-control form-control-lg" name="org_name"
                                    placeholder="Enter organization name" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="admin_username" class="form-label fw-semibold">Admin Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control form-control-lg" name="admin_username"
                                    placeholder="Enter admin username" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="admin_password" class="form-label fw-semibold">Admin Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control form-control-lg" name="admin_password"
                                    placeholder="Enter admin password" required>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-semibold">Register</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>