<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $sql = "SELECT * FROM user WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && passwordVerify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['organization_id'] = $user['organization_id'];
        $_SESSION['role'] = $user['role'];

        flash('success', 'Login successful!');

        if ($user['role'] == 'system_owner') {
            redirect('register_organization.php');
        } else {
            redirect('index.php');
        }
    } else {
        flash('danger', 'Invalid username or password.');
    }
}

include 'header.php';
?>

<<<<<<< HEAD
<!-- Login Card -->
<div class="card">
    <div class="card-header">
        Login
    </div>
    <div class="card-body">
        <?php flash('danger'); ?>
        <?php flash('success'); ?>

        <form action="login.php" method="POST">
            <div class="mb-3">
                <input type="text" class="form-control" name="username" placeholder="Username" required>
            </div>
            <div class="mb-3">
                <input type="password" class="form-control" name="password" placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <div class="signup-link">
            Don't have an account? Contact your administrator to add you.
=======
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Login
                </div>
                <div class="card-body">
                    <?php flash('danger'); ?>
                    <?php flash('success'); ?>

                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <input type="text" class="form-control" name="username" placeholder="Username" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" class="form-control" name="password" placeholder="Password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Login</button>
                    </form>
                    <div class="signup-link mt-3">
                        Don't have an account? Contact your administrator to add you.
                    </div>
                </div>
            </div>
>>>>>>> f71fa435217b073ae6e412306806f430fca0e6f7
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>