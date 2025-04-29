<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lab Consumables Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .navbar {
            background: #6a11cb;
            color: white;
        }

        .navbar a {
            color: white;
        }

        .navbar a:hover {
            color: #2575fc;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .progress {
            height: 20px;
            border-radius: 10px;
        }

        .progress-bar {
            border-radius: 10px;
        }

        .table thead th {
            background: #6a11cb;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            border: none;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        footer {
            text-align: center;
            padding: 10px;
            background-color: #8b7c9b;
            color: white;
            margin-top: auto;
        }

        footer a {
            color: rgb(231, 8, 8);
            text-decoration: none;
        }

        footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Lab Consumables</a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isLoggedIn() && isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_users.php">Manage Users</a>
                        </li>
                    <?php endif; ?>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="log_report.php">Log Report</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>