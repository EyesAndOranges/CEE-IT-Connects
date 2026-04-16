<?php
$room_id = $_GET['room_id'];
// ROOM INFO
$stmt = $pdo->prepare("
    SELECT r.*, a.full_name, a.role
    FROM rooms r
    LEFT JOIN advisers a ON r.adviser_id = a.id
    WHERE r.id = ?
");
$stmt->execute([$room_id]);
$room = $stmt->fetch();

// ROOM POSTS (UPDATES)
$stmt = $pdo->prepare("
    SELECT * FROM room_posts
    WHERE room_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$room_id]);
$posts = $stmt->fetchAll();
?>

<?php
$tab = $_GET['tab'] ?? 'updates';
?>

<head>
    <style>
        .tab-link {
            text-decoration: none;
            padding-bottom: 5px;
            transition: 0.2s;
        }

        .tab-link:hover {
            color: #dc3545;
        }

        .active-tab {
            border-bottom: 2px solid #dc3545;
        }
    </style>
</head>
<div class="d-flex justify-content-end mb-2">
    <a href="message.php" class="text-danger fw-semibold" style="text-decoration:none;">
        <i class="fa-solid fa-arrow-left"></i> Back to rooms
    </a>
</div>
<!-- HEADER -->
<div class="p-3 text-white rounded" style="background:#d63ba5;">
    <h5><?= $room['room_name'] ?></h5>
    <small><?= $room['full_name'] ?> | <?= $room['role'] ?></small>
</div>

<!-- TABS -->
<div class="mt-3 border-bottom pb-2">

    <!-- UPDATES -->
    <a href="?room_id=<?= $room_id ?>&tab=updates"
        class="me-3 fw-bold tab-link <?= $tab === 'updates' ? 'text-danger active-tab' : 'text-dark' ?>">
        Updates
    </a>

    <!-- MEMBERS -->
    <a href="?room_id=<?= $room_id ?>&tab=members"
        class="fw-bold tab-link <?= $tab === 'members' ? 'text-danger active-tab' : 'text-dark' ?>">
        Members
    </a>

</div>

<!-- CONTENT -->
<div class="mt-3">

    <?php
    $tab = $_GET['tab'] ?? 'updates';

    if ($tab === 'members'):

        // MEMBERS QUERY
        $stmt = $pdo->prepare("
        SELECT s.full_name
        FROM room_members rm
        JOIN students s ON rm.user_id = s.id
        WHERE rm.room_id = ?
    ");
        $stmt->execute([$room_id]);
        $members = $stmt->fetchAll();
        ?>

        <?php foreach ($members as $m): ?>
            <div class="card mb-2 p-2">
                <?= $m['full_name'] ?>
            </div>
        <?php endforeach; ?>

    <?php else: ?>

        <!-- POSTS -->
        <?php foreach ($posts as $post): ?>
            <div class="card mb-3 shadow-sm">
                <div class="card-body">

                    <div class="d-flex align-items-center mb-2">
                        <div style="width:35px;height:35px;border-radius:50%;background:#ccc;margin-right:10px;"></div>

                        <div>
                            <strong><?= $post['sender_name'] ?></strong><br>
                            <small class="text-muted">
                                <?= $post['sender_role'] ?> •
                                <?= date("M d, Y", strtotime($post['created_at'])) ?>
                            </small>
                        </div>
                    </div>

                    <p><?= $post['content'] ?></p>

                </div>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>

</div>