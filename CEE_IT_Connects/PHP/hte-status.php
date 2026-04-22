<?php
session_start();
require 'db.php';
require 'auth.php';
$current_room_id = $_GET['room_id'] ?? null;

$stmt = $pdo->prepare("
    SELECT r.*, a.full_name, a.title, a.role
    FROM rooms r
    LEFT JOIN advisers a ON r.adviser_id = a.id
    JOIN room_members rm ON r.id = rm.room_id
    WHERE rm.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);

$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
$colors = ['#d63ba5', '#1abc9c', '#3498db', '#9b59b6'];
$color = $colors[array_rand($colors)];
$page = 'messages'
    ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Virtual Rooms</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            background: #f5f6fa;
            margin: 0;
            padding-top: 70px;
        }

        .sidebar {
            width: 240px;
            background: #fff;
            position: fixed;
            padding: 20px;
            border-right: 1px solid #ddd;
        }

        .sidebar a {
            display: block;
            padding: 10px;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 5px;
        }

        .sidebar a.active {
            background: #ffe5d9;
            color: #ff6b2c;
        }

        .rooms-list {
            margin-top: 20px;
        }

        .room-item {
            padding: 8px;
            border-radius: 6px;
            background: #f1f1f1;
            margin-bottom: 5px;
        }

        .room-link {
            text-decoration: none;
        }

        .room-link .room-item:hover {
            background: #e0e0e0;
            cursor: pointer;
        }

        .active-room {
            background: #ffe5d9;
            color: #ff6b2c;
            font-weight: bold;
            cursor: default;
        }

        .main {
            margin-left: 260px;
            padding: 20px;
        }

        .room-card {
            border-radius: 12px;
            color: white;
            padding: 15px;
        }

        .room-footer {
            background: #eee;
            padding: 10px;
            border-radius: 0 0 12px 12px;
            text-align: center;
        }

        .enter-btn {
            background: #f4a62a;
            border: none;
            padding: 5px 15px;
            border-radius: 8px;
        }

        .page-section h2 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .page-section p {
            font-size: 0.85rem;
            color: #888;
            margin-bottom: 16px;
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 8px;
            background: white;
            border: 1px solid;
            border-radius: 24px;
            padding: 7px 14px;
            flex: 1;
            max-width: 300px;
        }

        .search-box input {
            border: none;
            outline: none;
            font-size: 13px;
            width: 100%;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        table th {
            padding: 10px 14px;
            text-align: left;
            font-size: 14px;
            font-weight: 600;
        }

        table td {
            padding: 12px 14px;
            vertical-align: middle;
        }

        .avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
        }

        .student-cell {
            display: flex;
            align-items: center;
        }

        .progress-bar-bg {
            width: 80px;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 4px;
            background: #ff6b2c;
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>
    <div class="sidebar">
        <a href="hte-rooms.php"><i class="fa-solid fa-house"></i> Home</a>
        <a href="hte-status.php" class="active"><i class="fa-solid fa-calendar-check"></i> Status</a>
        <a href="hte-remarks.php"><i class="fa-solid fa-star"></i> Remarks</a>

        <div class="rooms-list">
            <h6>ROOMS</h6>

            <?php foreach ($rooms as $room): ?>

                <?php if ($current_room_id == $room['id']): ?>

                    <div class="room-item active-room">
                        <?= $room['room_name'] ?>
                    </div>

                <?php else: ?>

                    <a href="?room_id=<?= $room['id'] ?>" class="room-link">
                        <div class="room-item">
                            <?= $room['room_name'] ?>
                        </div>
                    </a>

                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <main class="main">
  <div>
    <h4><strong>Monitor OJT progress and HTE remarks</strong></h4><br>

    <div style="display:flex;gap:10px;margin-bottom:16px;align-items:center;">
      <div class="search-box">
          <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="searchInput" placeholder="Search student" oninput="filterTable()">
      </div>
      <select id="roomFilter" onchange="filterTable()" style="padding:7px 14px;border:1px solid;border-radius:24px;font-size:12px;">
        <option value="">All Rooms</option>
        <?php foreach ($rooms as $r): ?>
          <option value="<?= htmlspecialchars($r['room_name']) ?>"><?= htmlspecialchars($r['room_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="background:white;border:1px solid #00000060;border-radius:8px;overflow:hidden;">
      <table>
        <thead>
          <tr>
            <th>STUDENT</th>
            <th>ROOM</th>
            <th>COMPANY</th>
            <th>HOURS</th>
            <th>PROGRESS</th>
            <th>HTE REMARKS</th>
          </tr>
        </thead>
        <tbody id="all-students-tbody">
          <tr data-room="Room 4A">
            <td>
              <div class="student-cell">
                <div class="avatar" style="background: #ff2c8f;"><strong>R</strong></div>
                <h6>Riva Mae Boongaling</h6>
              </div>
            </td>
            <td>Room 4A</td>
            <td>TechCorp PH</td>
            <td><strong>364</strong> / 486</td>
            <td>
              <div style="display:flex;align-items:center;gap:8px;">
                <div class="progress-bar-bg">
                  <div class="progress-bar-fill" style="width:75%"></div>
                </div>
                <span>75%</span>
              </div>
            </td>
            <td>Very Satisfactory</td>
          </tr>
          <tr data-room="Room 2C">
            <td>
              <div class="student-cell">
                <div class="avatar" style="background: #2c6fff;"><strong>M</strong></div>
                <h6>Mark Anthony Dela Cruz</h6>
              </div>
            </td>
            <td>Room 2C</td>
            <td>TechCorp PH</td>
            <td><strong>268</strong> / 486</td>
            <td>
              <div style="display:flex;align-items:center;gap:8px;">
                <div class="progress-bar-bg">
                  <div class="progress-bar-fill" style="width:55%"></div>
                </div>
                <span>55%</span>
              </div>
            </td>
            <td>Satisfactory</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</main>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function filterTable() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const room   = document.getElementById('roomFilter').value.toLowerCase();

    document.querySelectorAll('#all-students-tbody tr').forEach(row => {
        const name    = row.querySelector('.student-cell h6')?.textContent.toLowerCase() ?? '';
        const rowRoom = (row.dataset.room ?? '').toLowerCase();

        const matchSearch = name.includes(search);
        const matchRoom   = room === '' || rowRoom === room;

        row.style.display = (matchSearch && matchRoom) ? '' : 'none';
    });
}
</script>

</body>
</html>