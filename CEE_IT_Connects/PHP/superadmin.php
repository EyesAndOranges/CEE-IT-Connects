<?php
require 'db.php';
require 'auth.php';

/* var_dump($_SESSION);
exit(); */
?>

<?php
$stmt = $pdo->query("SELECT id, name, email, role FROM admins ORDER BY id ASC");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {

    $admin_ids = $_POST['admin_id'];
    $roles = $_POST['role'];

    if (!is_array($roles)) {
        $roles = [$roles];
    }

    if (!is_array($admin_ids)) {
        $admin_ids = [$admin_ids];
    }
    $superadmincount = 0;
    foreach ($roles as $role) {
        if ($role === 'superadmin') {
            $superadmincount++;
        }
    }

    if ($superadmincount > 3) {
        die("Only three superadmins are allowed buckooo.");
    }

    $stmt = $pdo->prepare("UPDATE admins SET role = ? WHERE id = ?");

    for ($i = 0; $i < count($admin_ids); $i++) {
        $stmt->execute([$roles[$i], $admin_ids[$i]]);
    }
    header("Location: superadmin.php?updated=1");
    exit;
}

$stmt = $pdo->query("
    SELECT id, full_name AS name, email, 'student' AS role, 'students' AS source FROM students

    UNION ALL

    SELECT id, name, email, role, 'admins' AS source FROM admins WHERE role != 'superadmin'

    UNION ALL

    SELECT id, full_name AS name, email, 'adviser' AS role, 'advisers' AS source FROM advisers

    ORDER BY name ASC
");

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Superadmin Dashboard</title>

    <style>
        :root {
            --gradient-start: #FFB62F;
            --gradient-end: #E4572E;
            --primary-dark-blue: #272f54;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(135deg, #f5f7ff, #eef1ff);
            min-height: 100vh;
            padding-top: 70px;
        }

        .dashboard-container {
            width: 100%;
            max-width: 600px;
            background: #fff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            animation: fadeIn 0.8s ease-in-out;
        }

        .main-heading {
            text-align: center;
            font-size: 28px;
            font-weight: 800;
            color: var(--primary-dark-blue);
            margin-bottom: 25px;
        }

        .sub-text {
            text-align: center;
            color: #777;
            font-size: 14px;
            margin-bottom: 25px;
        }

        .success-box {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;

            opacity: 1;
            transition: opacity 0.5s ease;
        }

        .admin-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .admin-form input,
        .admin-form select {
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid #ddd;
            font-size: 14px;
            outline: none;
            transition: 0.3s;
        }

        .admin-form input:focus,
        .admin-form select:focus {
            border-color: var(--gradient-end);
            box-shadow: 0 0 8px rgba(228, 87, 46, 0.3);
        }

        .btn-find {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-find:hover {

            box-shadow: 0 8px 20px rgba(228, 87, 46, 0.3);
        }

        .top-bar {
            text-align: center;
            margin-bottom: 20px;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            background: var(--primary-dark-blue);
            color: white;
            font-size: 12px;
            border-radius: 20px;
        }

        .layout {
            display: flex;
            height: 100vh;
            width: 100vw;
        }

        /* SIDEBAR */
        .sidebar {
            width: 220px;
            background: #272f54;
            color: white;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .sidebar h3 {
            margin-bottom: 20px;
        }

        .sidebar a {
            text-decoration: none;
            color: white;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 10px;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar a.active {
            background: #FFB62F;
            color: #272f54;
        }

        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            padding: 40px;
            background: #f5f7ff;
            width: 50vw;
        }

        /* SECTIONS */
        .section {
            display: none;
            width: 50vw;

        }

        .section.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<script>
    function showSection(event, sectionId) {

        // hide all sections
        document.querySelectorAll('.section').forEach(sec => {
            sec.classList.remove('active');
        });

        // show selected
        document.getElementById(sectionId).classList.add('active');

        // update sidebar active 
        document.querySelectorAll('.sidebar a').forEach(link => {
            link.classList.remove('active');
        });

        event.target.classList.add('active');
    }
    function confirmSuperadminGlobal() {
        const roles = document.querySelectorAll("select[name='role[]']");

        let superadminCount = 0;
        let hasChangeToSuperadmin = false;
        let hasRemovalFromSuperadmin = false;

        roles.forEach(select => {
            const original = select.dataset.original;
            const current = select.value;

            if (current === 'superadmin') {
                superadminCount++;
            }

            // ANY upgrade to superadmin
            if (current === 'superadmin' && original !== 'superadmin') {
                hasChangeToSuperadmin = true;
            }

            // ANY downgrade from superadmin
            if (original === 'superadmin' && current !== 'superadmin') {
                hasRemovalFromSuperadmin = true;
            }
        });

        if (superadminCount > 3) {
            alert("Only 3 superadmins are allowed.");
            return false;
        }

        if (hasRemovalFromSuperadmin) {
            return confirm("You are removing a Superadmin. Continue?");
        }

        if (hasChangeToSuperadmin) {
            return confirm("You are assigning a Superadmin. Are you sure?");
        }

        return true;
    }
</script>

<body>

    <?php include 'navbar.php'; ?>

    <div class="layout">

        <!-- SIDEBAR -->
        <div class="sidebar">
            <h3>Superadmin</h3>

            <a href="#" onclick="showSection(event, 'add-admin')" class="active">Add Admin Accounts</a>
            <a href="#" onclick="showSection(event, 'add-adviser')">Add Adviser Accounts</a>
            <a href="#" onclick="showSection(event, 'delete')">Delete Account</a>
            <a href="#" onclick="showSection(event, 'roles')">Change Roles</a>
            <a href="#" onclick="showSection(event, 'monitor')">Monitor</a>
        </div>

        <!-- MAIN CONTENT -->
        <div class="main-content">

            <!-- ADD ADMIN ACCOUNT SECTION -->
            <div id="add-admin" class="section active">
                <h2>Create New Admin Account</h2>

                <form method="POST" action="superadmin-db.php" class="admin-form">
                    <input type="text" name="name" placeholder="Full Name" required>
                    <input type="email" name="email" placeholder="Email Address" required>
                    <input type="password" name="password" placeholder="Password" required>

                    <select name="role" required>
                        <option value="" disabled selected>Select Role</option>
                        <option value="internship_admin">Internship Admin</option>
                        <!-- <option value="cma">Content Management Admin</option> -->
                    </select>

                    <button type="submit" name="create-admin" class="btn-find">Create Admin</button>
                </form>
            </div>
            <!-- Create Adviser Account Section-->
            <div id="add-adviser" class="section">
                <h2>Create New Adviser Account</h2>

                <form method="POST" action="superadmin-db.php" class="admin-form">
                    <input type="text" name="name" placeholder="Full Name" required>
                    <input type="email" name="email" placeholder="Email Address" required>
                    <input type="password" name="password" placeholder="Password" required>

                    <select name="title" required>
                        <option value="" disabled selected>Select Title</option>
                        <option value="Adviser">Adviser</option>
                        <option value="Professor">Professor</option>
                        <option value="Engineer">Engineer</option>
                        <option value="Doctor">Doctor</option>
                        <option value="Instructor">Instructor</option>
                        <!-- <option value="cma">Content Management Admin</option> -->
                    </select>

                    <select name="role" required>
                        <option value="" disabled selected>Select Role</option>
                        <option value="HTE_adviser">HTE Adviser</option>
                        <option value="internship_adviser">Internship Adviser</option>
                        <!-- <option value="cma">Content Management Admin</option> -->
                    </select>

                    <button type="submit" name="create-adviser" class="btn-find">Create Adviser</button>
                </form>
            </div>
            <!-- DELETE USER SECTION -->
            <div id="delete" class="section">
                <h2>Delete User</h2>

                <div class="form-card">

                    <table style="width:100%; border-collapse: collapse;">
                        <thead>
                            <!-- Table Header -->
                            <tr style="text-align:left; border-bottom:1px solid #ddd;">
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr style="border-bottom:1px solid #eee;">
                                    <!-- Out puts the data per row -->
                                    <td>
                                        <?= htmlspecialchars($u['name']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($u['email']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($u['role']) ?>
                                    </td>
                                    <!-- This is the Action-->
                                    <td>
                                        <form method="POST" action="superadmin-db.php"
                                            onsubmit="return confirm('Are you sure?')">
                                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="source" value="<?= $u['source'] ?>">
                                            <button type="submit" name="delete"
                                                style="background:red;color:white;border:none;padding:6px 10px;border-radius:6px;">
                                                Delete
                                            </button>
                                        </form>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>

                    </table>

                </div>
            </div>
            <!-- CHANGE ROLES SECTION -->
            <div id="roles" class="section">
                <h2>Admin Management</h2>

                <?php if (isset($_GET['updated'])): ?>
                    <p class="success-box" id="successMsg">Role updated successfully!</p>
                <?php endif; ?>
                <form method="POST" onsubmit="return confirmSuperadminGlobal()">
                    <table style="width:100%; border-collapse: collapse; margin-top:20px; background:white;">
                        <thead>
                            <tr style="background:#272f54; color:white;">
                                <!-- <th style="padding:10px;">ID</th> -->
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                                <tr style="text-align:center; border-bottom:1px solid #ddd;">

                                    <!-- <td><?//= $admin['id'] ?></td> -->
                                    <td><?= htmlspecialchars($admin['name']) ?></td>
                                    <td><?= htmlspecialchars($admin['email']) ?></td>
                                    <td style="padding: 0px, 40px;">
                                        <input type="hidden" name="admin_id[]" value="<?= $admin['id'] ?>">

                                        <select name="role[]" data-original="<?= $admin['role'] ?>" required>
                                            <option value="null" disabled <?= $admin['role'] !== 'superadmin' && $admin['role'] !== 'internship_admin' ? 'selected' : '' ?>>None</option>
                                            <option value="superadmin" <?= $admin['role'] == 'superadmin' ? 'selected' : '' ?>>
                                                Superadmin
                                            </option>

                                            <option value="internship_admin" <?= $admin['role'] == 'internship_admin' ? 'selected' : '' ?>>
                                                Internship Admin
                                            </option>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <br>
                    <button type="submit" name="update_role" class="btn-find p-10">
                        Update
                    </button>
                </form>
            </div>

            <!-- MONITOR SECTION -->
            <div id="monitor" class="section">
                <h2>System Monitoring</h2>
                <p>For the student, adviser and admn activity logs</p>
            </div>

        </div>

    </div>
    <script>
        setTimeout(() => {
            const msg = document.getElementById("successMsg");
            if (msg) {
                msg.style.transition = "0.5s";
                msg.style.opacity = "0";
                setTimeout(() => msg.remove(), 500);
            }
        }, 2000); // disappears after 3 seconds
    </script>
</body>

</html>