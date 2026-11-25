<?php
session_start();
include 'config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$sql = "SELECT * FROM users ORDER BY user_id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - DocuFlow</title><link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="sidebar">
        <div class="brand">
            <div class="brand-icon">D</div>
            <div><h2>DocuFlow</h2>
                <p>DOCUMENT MANAGEMENT</p>
            </div>
        </div>
        <p class="menu-title">MAIN MENU</p>
        <a href="dashboard.php"><span>▦</span> Dashboard</a>
        <a href="documents.html"><span>▤</span> Documents</a>
        <a href="upload.html"><span>＋</span> Upload Document</a>
        <a href="approvals.html"><span>✓</span> Approvals</a>
        <a href="versions.html"><span>↻</span> Version History</a>
        <a href="archive.html"><span>▣</span> Archive</a>
        <a class="active" href="users.php"><span>♙</span> Users</a>
        <div class="account">
            <div class="avatar">A</div>
            <div><strong>Administrator</strong>
                <p>System Account</p>
            </div>
        </div>
    </div>
    <div class="main-content">
        <div class="top-bar">
            <div><p class="breadcrumb">Pages / Users</p>
                <h1>User Management</h1>

                <?php if (isset($_GET['success'])): ?>
                    <p class="success-message">User added successfully!</p>
                <?php endif; ?>

                <?php if (isset($_GET['updated'])): ?>
                    <p class="success-message">User updated successfully!</p>
                <?php endif; ?>

                <?php if (isset($_GET['deleted'])): ?>
                    <p class="success-message">User deleted successfully!</p>
                <?php endif; ?>

                <?php if (isset($_GET['error']) && $_GET['error'] == 'self'): ?>
                    <p class="error-message">You cannot delete your own account!</p>
                <?php endif; ?>

                <?php if (isset($_GET['error']) && $_GET['error'] == 'delete'): ?>
                    <p class="error-message">Unable to delete this user. They may be connected to existing records.</p>
                <?php endif; ?>

            </div>
            <a class="upload-button" href="#add-user">＋ Add User</a>
        </div>
        <div class="content-box">
            <div class="box-heading">
                <div><h2>System Users</h2>
                    <p>View the people who can access DocuFlow</p>
                </div>
                <span class="record-count">4 sample users</span>
            </div>
            <div class="filter-row">
                <input type="text" placeholder="Search user name...">
                <select>
                    <option>All Roles</option>
                    <option>Administrator</option>
                    <option>Approver</option>
                    <option>Staff</option>
                </select>
                <button type="button" class="secondary-button">Search</button>
            </div>
            <div class="table-container"><table>
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Date Created</th>
                        <th>Actions</th>

                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                            <td> 
                                <a href="edit_user.php?id=<?php echo $user['user_id']; ?>">
                                    Edit
                                </a>
                                <a href="delete_user.php?id=<?php echo $user['user_id']; ?>"
                                    onclick="return confirm('Are you sure you want to delete this user?');">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table></div>
        </div>
        <div class="content-box form-box" id="add-user">
            <div class="box-heading">
                <div><h2>Add New User</h2>
                    <p>Enter the details of the new system user</p>
                </div>
            </div>
            <form action="add_user.php" method="POST">
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input
                        id="fullname"
                        type="text"
                        name="full_name"
                        placeholder="Enter full name"
                        required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        id="username" 
                        type="text" 
                        name="username" 
                        placeholder="Enter username" 
                        required>
                </div>
                <div class="form-group">
                    <label for="role">User Role</label>
                    <select id="role" name="role" required>
                        <option value="">Select role</option>
                        <option value="Admin">Administrator</option>
                        <option value="Approver">Approver</option>
                        <option value="User">Staff</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Enter password (minimum 8 characters)"
                        minlength="8"
                        required>
                </div>
            </div><div class="form-actions">
                <button type="submit" name="add_user" class="primary-button">
                    Save User
                </button>
            </div>
        </form>
        </div>
        <p class="footer">© 2026 DocuFlow | Document Management System</p>
    </div>
</body>
</html>