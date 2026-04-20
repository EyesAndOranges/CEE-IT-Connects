<?php
//require 'db.php';
//require 'auth.php';
?>

<?php
/**$stmt = $pdo->query("SELECT id, name, email, role FROM admins");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {

    $admin_id = $_POST['admin_id'];
    $new_role = $_POST['role'];

    $stmt = $pdo->prepare("UPDATE admins SET role = ? WHERE id = ?");
    $stmt->execute([$new_role, $admin_id]);

    header("Location: superadmin.php?updated=1");
    exit;
}
**/

//dagdag ni susu (temporary lang to test)
$admins = [
    ['id' => 1, 'name' => 'Sample Admin', 'email' => 'admin@test.com', 'role' => 'superadmin'],
    ['id' => 2, 'name' => 'John Doe', 'email' => 'john@test.com', 'role' => 'cma']
];
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
            width: 50%;
            align-self: center;
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
            width: 50%;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            border: 2px solid transparent;
            padding: 14px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-update {
            transition: all 0.4s ease;
        }

        .btn-update:hover {
            /* HOVER STATE: Line/Outline Button */
            background: transparent; /* Inaalis ang solid color */
            border: 2px solid var(--gradient-end); /* Nilalabas ang outline */
            color: var(--gradient-end); /* Binabago ang kulay ng text para mabasa */
        }

        .btn-create {
            margin-top: 30px;
            align-self: center;
            opacity: 80%;
            transition: transform 0.3s ease-in-out, background 0.3s ease-in-out;
        }

        .btn-create:hover {
            opacity: 100%;
            box-shadow: 0 5px 5px rgba(228, 87, 46, 0.3);
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
            width: 300px;
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
            width: 100%;
        }

        /* SECTIONS */
        .section {
            display: none;
            width: 95%;
            
        }

        .section.active {
            display: block;
        }

        .section thead th {
            padding: 10px;
        }

        .section tbody td {
            padding: 10px;
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

        /*MONITORING TABLE*/
        .monitoring-table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        }

        .monitoring-table thead th {
            font-size: 15px;
            font-weight: 800;
            color: #2c3e67;
            border-bottom: 1px solid #f1f1f1;
            padding: 20px;
        }

        .monitoring-table tbody tr:hover {
            background-color: #fcfcfc;
            transition: 0.3s;
        }

        .monitoring-table td {
            padding: 10px;
            font-size: 14px;
            border-bottom: 1px solid #dee2e6;
        }

        /*ICONS*/
        .btn-action {
            background: transparent;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.4s ease;
            outline: none;
            padding: 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-action:hover {
            background-color: #f3f5f6;
        }

        .table-icon {
            width: 23px;
            height: 23px;
            object-fit: contain;
        }
    </style>
</head>
<script>
    function showSection(sectionId) {

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
</script>

<body>

    <?php include 'navbar.php'; ?>

    <div class="layout">

        <!-- SIDEBAR -->
        <div class="sidebar">
            <h3>Superadmin</h3>

            <a href="#" onclick="showSection('add')" class="active">Add Accounts</a>
            <a href="#" onclick="showSection('roles')">Change Roles</a>
            <a href="#" onclick="showSection('monitor')">Monitor</a>
        </div>

        <!-- MAIN CONTENT -->
        <div class="main-content">

            <!-- ADD ACCOUNT SECTION -->
            <div id="add" class="section active">
                <h2 style="margin-bottom: 70px;">Create New Admin Account</h2>

                <form method="POST" action="superadmin-db.php" class="admin-form">
                    <input type="text" name="name" placeholder="Full Name" required>
                    <input type="email" name="email" placeholder="Email Address" required>
                    <input type="password" name="password" placeholder="Password" required>

                    <select name="role" required>
                        <option value="" disabled selected>Select Role</option>
                        <option value="internship_admin">Internship Admin</option>
                        <option value="cma">Content Management Admin</option>
                    </select>

                    <button type="submit" class="btn-find btn-create">Create Admin</button>
                </form>
            </div>

            <!-- CHANGE ROLES SECTION -->
            <div id="roles" class="section">
                <h2>Admin Management</h2>

                <?php if (isset($_GET['updated'])): ?>
                    <p class="success-box" id="successMsg">Role updated successfully!</p>
                <?php endif; ?>

                <table style="width:100%; border-collapse: collapse; margin-top:20px; background:white;">
                    <thead>
                        <tr style="background:#272f54; color:white; text-align:center;">
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

                                <form method="POST">
                                    <td style="padding: 0px, 40px;">
                                        <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">

                                        <select name="role" required>
                                            <option value="superadmin" <?= $admin['role'] == 'superadmin' ? 'selected' : '' ?>>
                                                Superadmin
                                            </option>

                                            <option value="internship_admin" <?= $admin['role'] == 'internship_admin' ? 'selected' : '' ?>>
                                                Internship Admin
                                            </option>

                                            <option value="cma" <?= $admin['role'] == 'cma' ? 'selected' : '' ?>>
                                                CMA
                                            </option>
                                        </select>
                                    </td>

                                    <td>
                                        <button type="submit" name="update_role" class="btn-find btn-update" style="padding:5px;">
                                            Update
                                        </button>
                                    </td>
                                </form>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- MONITOR SECTION -->
            <div id="monitor" class="section">
                <div class="monitor-header">
                    <h2>System Monitoring</h2>
                    <p>For the student, adviser and admin activity logs</p>
                </div>

                <div class="log-container">
                    <table class="monitoring-table">
                        <thead>
                            <tr style="text-align: center;">
                                <th>Name</th>
                                <th>Email</th>
                                <th>Activity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="text-align: center;">
                                <td>Maria Carmela Alfonso</td>
                                <td>macarmelaalfonso@gmail.com</td>
                                <td>Reserved a slot</td>
                                <td>
                                    <button class="btn-action">
                                        <img src="../Sources/history.png" alt="History" class="table-icon">
                                    </button>
                                    <button class="btn-action">
                                        <img src="../Sources/delete.png" alt="Delete" class="table-icon">
                                    </button>
                                </td>
                            </tr>
                            <tr style="text-align: center;">
                                <td>Juan Leonardo Seleno</td>
                                <td>leoseleno@gmail.com</td>
                                <td>Updated listing detail</td>
                                <td>
                                    <button class="btn-action">
                                        <img src="../Sources/history.png" alt="History" class="table-icon">
                                    </button>
                                    <button class="btn-action">
                                        <img src="../Sources/delete.png" alt="Delete" class="table-icon">
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

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