<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    flash('danger', 'You do not have permission to access this page.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $email = trim($_POST['email']);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    // Check if username exists
    $check_sql = "SELECT id FROM user WHERE username = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        flash('danger', 'Username already exists. Please choose a different username.');
    } else {
        $hashed_password = passwordHash($password);
        $insert_sql = "INSERT INTO user (username, password, email, is_admin, organization_id) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("sssii", $username, $hashed_password, $email, $is_admin, $_SESSION['organization_id']);

        if ($insert_stmt->execute()) {
            flash('success', 'User added successfully!');
        } else {
            flash('danger', 'Error adding user.');
        }
    }
}

// Get all users in the organization
$sql = "SELECT id, username, is_admin FROM user WHERE organization_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['organization_id']);
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);

include 'header.php';
?>

<div class="container mt-4">
    <h2 class="text-center">Manage Users</h2>

    <?php flash('success'); ?>
    <?php flash('danger'); ?>

    <!-- Add User Form -->
    <div class="card mt-4">
        <div class="card-header">Add New User</div>
        <div class="card-body">
            <form action="manage_users.php" method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" name="is_admin" id="is_admin">
                    <label class="form-check-label" for="is_admin">Is Admin</label>
                </div>
                <button type="submit" class="btn btn-primary">Add User</button>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card mt-4">
        <div class="card-header">User List</div>
        <div class="card-body">
            <?php if (count($users) > 0): ?>
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Is Admin</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo $user['is_admin'] ? 'Yes' : 'No'; ?></td>
                                <td>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <form action="delete_user.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No users found in your organization.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>