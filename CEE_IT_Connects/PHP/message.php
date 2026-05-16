<?php
session_start();
require 'db.php';
require 'auth.php';
$current_room_id = $_GET['room_id'] ?? null;

$isAdviser = isset($_SESSION['role']) && $_SESSION['role'] === 'internship_adviser';
$stmt = $pdo->prepare("
    SELECT r.*, a.full_name, a.title, a.role
    FROM rooms r
    LEFT JOIN advisers a ON r.adviser_id = a.id
    JOIN room_members rm ON r.id = rm.room_id
    WHERE rm.user_id = ?
    " . (!$isAdviser ? "AND r.is_archived = FALSE" : "") . "
");
$stmt->execute([$_SESSION['user_id']]);

$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

$current_section = $_GET['section'] ?? 'home';
$current_chat_id = $_GET['chat_id'] ?? null;
$current_chat_type = $_GET['chat_type'] ?? null;
$current_user_type = getUserType($_SESSION['role']);

$stmt = $pdo->prepare("
    SELECT 
    CASE 
        WHEN sender_id = :uid THEN receiver_id
        ELSE sender_id
    END as chat_user_id,

    CASE 
        WHEN sender_id = :uid THEN receiver_type
        ELSE sender_type
    END as chat_user_type,

    CONCAT(
        LEAST(sender_id, receiver_id), '-',
        GREATEST(sender_id, receiver_id), '-',
        CASE 
            WHEN sender_id = :uid THEN receiver_type
            ELSE sender_type
        END
    ) AS chat_key

FROM messages
WHERE sender_id = :uid OR receiver_id = :uid

GROUP BY chat_key, chat_user_id, chat_user_type
ORDER BY MAX(created_at) DESC
");
$stmt->execute(['uid' => $_SESSION['user_id']]);
$chatUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$messages = [];

if ($current_chat_id) {
    $stmt = $pdo->prepare("
    SELECT * FROM messages
    WHERE 
        (sender_id = ? AND sender_type = ? AND receiver_id = ? AND receiver_type = ?)
        OR
        (sender_id = ? AND sender_type = ? AND receiver_id = ? AND receiver_type = ?)
    ORDER BY created_at ASC
");

    $stmt->execute([
        $_SESSION['user_id'],
        getUserType($_SESSION['role']),

        $current_chat_id,
        $current_chat_type,

        $current_chat_id,
        $current_chat_type,

        $_SESSION['user_id'],
        getUserType($_SESSION['role'])
    ]);

    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserName($pdo, $id, $type)
{
    switch (strtolower($type)) {

        case 'student':
            $stmt = $pdo->prepare("SELECT full_name FROM students WHERE id=?");
            break;

        case 'adviser':
            $stmt = $pdo->prepare("SELECT full_name FROM advisers WHERE id=?");
            break;

        case 'admin':
            $stmt = $pdo->prepare("SELECT name FROM admins WHERE id=?");
            break;

        default:
            return "Unknown ($type)";
    }

    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user['full_name'] ?? $user['name'] ?? "Unknown";
}
?>

<?php
$colors = ['#d63ba5', '#1abc9c', '#3498db', '#9b59b6'];
$color = $colors[array_rand($colors)];
$page = 'messages';
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
        * {
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
        }

        body {
            background: #f5f6fa;

            /* prevent content shift when navbar appears */
            margin: 0;
            padding-top: 70px;
        }

        /* SIDEBAR */
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

        /* ACTIVE ROOM */
        .active-room {
            background: #ffe5d9;
            color: #ff6b2c;
            font-weight: bold;
            cursor: default;
        }

        /* MAIN */
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

        /* SECTION  */
        .section {
            display: none;

        }

        .section.active {
            display: block;
        }

        /* CHAT DESIGN */

        .chat-container {
            margin: -20px;
            display: flex !important;
            height: calc(100vh - 70px);
            width: calc(100% + 40px);
            overflow: hidden;
        }

        .chat-list,
        .profile-sidebar {
            width: 280px;
            overflow-y: auto;
            flex-shrink: 0;
        }

        .chat-list {
            border-right: 1px solid #eee;
        }

        .message-area {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
        }

        .chat-inner-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 15px;
            background: #F9F9F9;
        }

        #text {
            font-size: 14px;
        }

        .fw-bold {
            font-size: 15px;
        }

        .message-content {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            background: #FCFCFC;
        }

        .chat-input-section {
            padding: 15px 25px;
            display: flex;
            align-items: center;
            gap: 25px;
            border-top: 1px solid #eee;
            background: #fff;
            margin-top: auto;
        }

        .message-input-box {
            flex-grow: 1;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 25px;
            padding: 8px 20px;
            outline: none;
        }

        /* COLUMN 3: PROFILE SIDEBAR (Fixed Width) */
        .profile-sidebar {
            width: 300px;
            flex-shrink: 0;
            overflow-y: auto;
        }

        /* TAB CONTAINER */
        .tab-item {
            flex: 1;
            padding-bottom: 10px;
            cursor: pointer;
            font-weight: 600;
            color: #888;
            text-align: center;
        }

        .tab-item.active-tab {
            color: #29335C;
        }

        .avatar-circle {
            width: 45px;
            height: 45px;
            background-color: #e0e0e0;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .big-avatar {
            width: 100px;
            height: 100px;
            background-color: #e0e0e0;
            border-radius: 50%;
            margin-bottom: 15px;
        }

        /* TAB SLIDER SYSTEM */
        .media-tabs {
            display: flex;
            width: 100%;
            position: relative;
            border-bottom: 2px solid #eee;
            margin-top: 20px;
        }

        .content-pane {
            display: none;
            width: 100%;
        }

        .active-pane {
            display: block;
        }

        /* THE SLIDING LINE */
        .tab-indicator {
            position: absolute;
            bottom: -2px;
            left: 0;
            height: 3px;
            width: 50%;
            background: #29335C;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .tab-content-area {
            position: relative;
            width: 100%;
            align-self: stretch;
        }

        .content-pane.active-pane {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ICONS & BUBBLES */
        .icon-btn {
            font-size: 1.3rem;
            color: #29335C;
            cursor: pointer;
        }

        .send-btn {
            color: #29335C;
            font-size: 1.3rem;
            transform: rotate(0deg);
        }

        .bubble {
            max-width: 70%;
            padding: 7px 15px;
            border-radius: 20px;
            margin-bottom: 8px;
        }

        .incoming {
            background: #ffcc80;
            color: #333;
            align-self: flex-start;
        }

        .outgoing {
            background: #f8d7da;
            color: #333;
            align-self: flex-end;
        }

        /*susu*/
        /* chat */
        .chat-slide-track { display: contents; }

        .chat-panel-screen { display: contents; }

        .mobile-chat-header, .mobile-profile-header { display: none; }

        /*MOBILE SIDEBAR & LAYOUT*/
        @media (max-width: 768px) {

            .sidebar {
                width: 60px;
                padding: 10px 0;
                display: flex;
                flex-direction: column;
                align-items: center;
                overflow: hidden;
            }

            /* Hide all text labels */
            .sidebar .sidebar-text,
            .rooms-list h6 {
                display: none !important;
            }

            /* Icon-only links */
            .sidebar a {
                width: 44px;
                height: 44px;
                display: flex !important;
                align-items: center;
                justify-content: center;
                border-radius: 12px;
                margin-bottom: 8px;
                font-size: 1.2rem;
                padding: 0;
            }

            /* Rooms list centering */
            .rooms-list {
                display: flex;
                flex-direction: column;
                align-items: center;
                margin-top: 10px;
                width: 100%;
            }

            .room-link {
                display: flex !important;
                justify-content: center;
                width: 100%;
                text-decoration: none;
            }

            /* Room initial bubble */
            .room-item {
                width: 44px !important;
                height: 44px !important;
                border-radius: 12px !important;
                background: #e8e8e8 !important;
                color: #555 !important;
                font-weight: bold !important;
                font-size: 1.1rem !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                margin-bottom: 8px !important;
                padding: 0 !important;
            }

            /* Hide the letter text inside .room-item directly (only show .room-initial) */
            .room-item .sidebar-text {
                display: none !important;
            }

            .room-initial {
                display: flex !important;
                align-items: center;
                justify-content: center;
            }

            /* Active room same style */
            .active-room {
                width: 44px !important;
                height: 44px !important;
                border-radius: 12px !important;
                background: #ffdac8 !important;
                color: #ff6b2c !important;
                font-weight: bold !important;
                font-size: 1.1rem !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                margin: 0 auto 8px auto !important;
            }

            .main {
                margin-left: 70px;
                padding: 15px;
            }

            .btn-join-top {
                display: none !important;
            }

            .fab-join {
                display: flex !important;
            }
            
            /*===MOBILE CHAT PANEL===*/
            .chat-container {
                position: fixed;
                top: 70px;
                left: 60px;
                right: 0;
                bottom: 0;
                width: auto;
                margin: 0;
                overflow: hidden;
                display: flex !important;
                flex-direction: row;
            }

            .chat-slide-track {
                display: flex !important;
                width: 300%;
                height: 100%;
                transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
                transform: translateX(0%); /* Panel 1 default */
            }

            /* slide to panel 2 */
            .chat-slide-track.show-chat {
                transform: translateX(calc(-100% / 3));
            }

            /* slide to panel 3 */
            .chat-slide-track.show-chat.show-profile {
                transform: translateX(calc(-200% / 3));
            }

            .chat-panel-screen {
                width: calc((100vw - 60px));
                flex-shrink: 0;
                height: 100%;
                overflow-y: auto;
                background: #fff;
                display: flex !important;
                flex-direction: column;
            }

            .chat-panel-screen:nth-child(2) {
                overflow: hidden;
            }

            .chat-panel-screen .chat-list {
                width: 100% !important;
                overflow-y: auto;
                height: 100%;
                border: none !important;
            }

            .chat-panel-screen .message-area {
                height: 100%;
                width: 100% !important;
                border: none;
                overflow: hidden;
            }

            .chat-panel-screen .profile-sidebar {
                width: 100% !important;
                padding: 20px;
                display: flex;
                flex-direction: column;
                align-items: center;
                border: none !important;
            }

            .message-area > .p-3.border-bottom {
                display: none;
            }

            .mobile-chat-header {
                display: flex !important;
                align-items: center;
                padding: 12px 16px;
                border-bottom: 1px solid #eee;
                background: #fff;
                gap: 12px;
                flex-shrink: 0;
            }

            .mobile-profile-header {
                display: flex !important;
                align-items: center;
                padding: 12px 16px;
                border-bottom: 1px solid #eee;
                gap: 12px;
                flex-shrink: 0;
                width: 100%;
            }

            .mobile-back-btn {
                background: none;
                border: none;
                font-size: 1.2rem;
                color: #333;
                cursor: pointer;
                padding: 4px 8px;
            }

            .mobile-chat-header .chat-name {
                flex-grow: 1;
                font-weight: 700;
                font-size: 1rem;
            }

            .mobile-info-btn {
                background: none;
                border: none;
                font-size: 1.2rem;
                color: #ff6b2c;
                cursor: pointer;
            }
        }

        /*room*/
        .room-initial {
        display: none;
        }

        /* FAB */
        .fab-join {
        display: none;
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 56px;
        height: 56px;
        background: #0B5ED7;
        color: white;
        border: none;
        border-radius: 50%;
        font-size: 4rem;
        padding-bottom: 16px;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 16px rgba(0,0,0,0.25);
        cursor: pointer;
        z-index: 999;
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <a href="?section=home<?php if ($current_room_id)
            echo "&room_id=$current_room_id"; ?>"
            class="sidebar-link <?= $current_section === 'home' ? 'active' : '' ?>">
            <i class="fa-solid fa-house"></i> <span class="sidebar-text">Home</span>
        </a>

        <a href="?section=chats<?php if ($current_room_id)
            echo "&room_id=$current_room_id"; ?>" class="sidebar-link 
            <?= $current_section === 'chats' ? 'active' : '' ?>">
            <i class="fa-solid fa-comments"></i> <span class="sidebar-text">Chats</span>
        </a>

        <a href="?section=connect<?php if ($current_room_id)
            echo "&room_id=$current_room_id"; ?>" class="sidebar-link
            <?= $current_section === 'connect' ? 'active' : '' ?>">
            <i class="fa-solid fa-user-group"></i> <span class="sidebar-text">Connect</span>
        </a>

        <div class="rooms-list">
            <h6>ROOMS</h6>

            <?php foreach ($rooms as $room): ?>

                <?php if ($current_room_id == $room['id']): ?>

                    <!-- CURRENT ROOM (NOT CLICKABLE) -->
                    <div class="room-item active-room">
                        <span class="room-initial"><?= strtoupper(substr(trim($room['room_name']),0 , 1)) ?></span>
                        <span class="sidebar-text"><?= $room['room_name'] ?></span>
                    </div>

                <?php else: ?>

                    <!-- CLICKABLE ROOM -->
                    <a href="?room_id=<?= $room['id'] ?>" class="room-link">
                        <div class="room-item">
                            <span class="room-initial"><?= strtoupper(substr(trim($room['room_name']),0 , 1)) ?></span>
                            <span class="sidebar-text"><?= $room['room_name'] ?></span>
                        </div>
                    </a>

                <?php endif; ?>

            <?php endforeach; ?>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div id="home" class="section <?= $current_section === 'home' ? 'active' : '' ?>">
        <div class="main">
            <?php if ($current_room_id): ?>

                <?php include 'chat-room-content.php'; ?>

            <?php else: ?>

                <!-- DEFAULT DASHBOARD VIEW -->
                <div class="d-flex justify-content-between align-items-center">
                    <h3><strong>Virtual Rooms</strong></h3>
                    <button class="btn btn-primary btn-join-top" data-bs-toggle="modal" data-bs-target="#joinRoomModal">
                        + Join a Room
                    </button>
                </div>

                <div class="row mt-4">
                    <?php foreach ($rooms as $room): ?>
                        <div class="col-md-4">
                            <div class="card shadow-sm">

                                <div class="room-card" style="background: <?= $color ?>">
                                    <div>
                                        <h5 style="text-color <? $color ?>">
                                            <?= $room['room_name'] ?>
                                        </h5>
                                        <small>
                                            <?= $room['full_name'] ?> (
                                            <?= $room['role'] ?>)
                                        </small>
                                    </div>

                                </div>

                                <div class="room-footer">
                                    <form method="GET">
                                        <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                        <button class="enter-btn">Enter Room</button>
                                    </form>
                                </div>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="modal fade" id="joinRoomModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content rounded-4">

                    <!-- HEADER -->
                    <div class="modal-header">
                        <h5 class="modal-title"><strong>Join a Room</strong></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <!-- BODY -->
                    <div class="modal-body">

                        <form method="POST" action="join-room.php">

                            <!-- ROOM CODE -->
                            <div class="mb-3">
                                <label class="form-label">Room Code</label>
                                <input type="text" name="room_code" class="form-control" placeholder="Enter room code"
                                    required>
                            </div>

                            <!-- ROOM NAME
                        <div class="mb-3">
                            <label class="form-label">Room Name</label>
                            <input type="text" name="room_name" class="form-control" placeholder="Enter room name">
                        </div>
                         -->
                            <!-- BUTTON -->
                            <div class="text-end">
                                <button type="submit" class="btn btn-warning px-4">
                                    Join
                                </button>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="chats" class="section <?= $current_section === 'chats' ? 'active' : '' ?>">
        <div class="main">
            <div class="chat-container">

                <div class="chat-slide-track" id="chatSlideTrack">
                    <div class="chat-panel-screen">
                        <div class="chat-list">
                            <div class="p-3">
                                <h3><strong>Chats</strong></h3>
                            </div>
                            <div>
                                <?php foreach ($chatUsers as $chat): ?>
                                    <?php
                                    $other_id = $chat['chat_user_id'];
                                    $other_type = $chat['chat_user_type'];
                                    $name = getUserName($pdo, $other_id, $other_type);
                                ?>
                                <a href="?chat_id=<?= $other_id ?>&chat_type=<?= $other_type ?>&section=chats"
                                    class="chat-user-link d-block text-decoration-none text-dark mb-2"
                                    data-id="<?= $other_id ?>"
                                    data-type="<?= $other_type ?>"
                                    data-name="<?= htmlspecialchars($name) ?>">
                                        <div class="chat-user-item d-flex align-items-center p-3 border-bottom
                                            <?= ($current_chat_id == $other_id) ? 'bg-light' : '' ?>">
                                            <div class="avatar-circle me-3"></div>
                                            <div>
                                                <div class="fw-bold"><?= htmlspecialchars($name) ?></div>
                                                <small class="text-muted">Click to open chat</small>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!--susu (message)-->
                    <div class="chat-panel-screen">
                    <!-- Mobile header with back + info -->
                    <div class="mobile-chat-header">
                        <button class="mobile-back-btn" onclick="mobileChatBack()">
                            <i class="fa-solid fa-arrow-left"></i>
                        </button>
                        <span class="chat-name" id="mobileChatName">
                            <?php if ($current_chat_id): ?>
                                <?= getUserName($pdo, $current_chat_id, $current_chat_type) ?>
                            <?php endif; ?>
                        </span>
                        <button class="mobile-info-btn" onclick="mobileChatInfo()">
                            <i class="fa-solid fa-circle-info"></i>
                        </button>
                    </div>

                    <div class="message-area">
                        <!-- Desktop header (hidden on mobile via CSS) -->
                        <div class="p-3 border-bottom">
                            <strong id="desktopChatName">
                                <?php if ($current_chat_id): ?>
                                    <?= getUserName($pdo, $current_chat_id, $current_chat_type) ?>
                                <?php endif; ?>
                            </strong>
                        </div>

                        <div class="message-content" id="messageContent">
                            <?php if ($current_chat_id): ?>
                                <?php foreach ($messages as $msg): ?>
                                    <?php $isMe = $msg['sender_id'] == $_SESSION['user_id']; ?>
                                    <div class="bubble <?= $isMe ? 'outgoing' : 'incoming' ?>">
                                        <small><?= getUserName($pdo, $msg['sender_id'], $msg['sender_type']) ?></small><br>
                                        <?= htmlspecialchars($msg['message']) ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-5 text-center text-muted">Select a chat to start messaging</div>
                            <?php endif; ?>
                        </div>

                        <!-- Send form -->
                        <form method="POST" action="message-db.php" class="p-3 border-top d-flex">
                            <input type="hidden" name="receiver_id" value="<?= $current_chat_id ?>">
                            <input type="hidden" name="receiver_type" value="<?= $current_chat_type ?>">
                            <input type="text" name="message" class="form-control me-2" placeholder="Type message..." required>
                            <button class="btn btn-primary">Send</button>
                        </form>
                    </div>
                </div>

                <!--susu(profile sidebar) -->
                <div class="chat-panel-screen">
                    <div class="mobile-profile-header">
                        <button class="mobile-back-btn" onclick="mobileProfileBack()">
                            <i class="fa-solid fa-arrow-left"></i>
                        </button>
                        <span class="fw-bold">Profile Info</span>
                    </div>

                    <div class="profile-sidebar">
                        <div class="big-avatar"></div>

                        <?php if ($current_chat_id): ?>
                            <?php $profile = getUserProfile($pdo, $current_chat_id, $current_chat_type); ?>
                            <h5 class="fw-bold"><?= htmlspecialchars($profile['full_name']) ?></h5>
                            <div class="w-100 text-start mt-3" style="font-size: 0.85rem;">
                                <div class="mb-3">
                                    <i class="fa-solid fa-envelope me-2 text-muted"></i>
                                    <?= htmlspecialchars($profile['email'] ?? 'No email') ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <h5 class="fw-bold">No chat selected</h5>
                        <?php endif; ?>

                        <div class="media-tabs" id="profileTabs">
                            <div class="tab-item active-tab" onclick="switchTab('media')">Media</div>
                            <div class="tab-item" onclick="switchTab('files')">Files</div>
                            <div class="tab-indicator" id="tabIndicator"></div>
                        </div>

                        <div class="tab-content-area">
                            <div id="mediaPane" class="content-pane active-pane">
                                <div class="media-grid">
                                    <div class="media-box"></div>
                                    <div class="media-box" style="background:#666;"></div>
                                    <div class="media-box" style="background:#999;"></div>
                                </div>
                            </div>
                            <div id="filesPane" class="content-pane">
                                <div class="mt-2" style="width:100%;">
                                    <div class="py-2 border-bottom small d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-file-pdf text-danger"></i> Internship_Form.pdf
                                    </div>
                                    <div class="py-2 border-bottom small d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-file-word text-primary"></i> Resume_Draft.docx
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- end .chat-slide-track -->
        </div><!-- end .chat-container -->
    </div>
</div>
    <div id="connect" class="section <?= $current_section === 'connect' ? 'active' : '' ?>">
        <div class="main">
            <h3><strong>Connect</strong></h3>
            <p>Connect content goes here...</p>
        </div>
    </div>

    <!-- susu (mobile FAB) -->
     <?php if ($current_section === 'home' && !$current_room_id): ?>
        <button class="fab-join" data-bs-toggle="modal" data-bs-target="#joinRoomModal">+</button>
    <?php endif; ?>
</body>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    //susu
    // ── MOBILE CHAT SLIDE NAVIGATION ──
    const track = document.getElementById('chatSlideTrack'); // declared ONCE

    function isMobile() {
        return window.innerWidth <= 768;
    }

    // On page load: if a chat is already open, jump to panel 2
    document.addEventListener('DOMContentLoaded', () => {
        <?php if ($current_chat_id && $current_section === 'chats'): ?>
            if (isMobile() && track) {
                track.classList.add('show-chat');
            }
        <?php endif; ?>

        // Intercept chat-user clicks on mobile
        document.querySelectorAll('.chat-user-link').forEach(link => {
            link.addEventListener('click', function(e) {
                if (isMobile()) {
                    e.preventDefault();
                    const href = this.href;
                    const name = this.dataset.name;
                    if (track) {
                        track.classList.add('show-chat');
                        track.classList.remove('show-profile');
                    }
                    const mobileNameEl = document.getElementById('mobileChatName');
                    if (mobileNameEl) mobileNameEl.textContent = name;
                    setTimeout(() => { window.location.href = href; }, 200);
                }
            });
        });
    });

    function mobileChatInfo() {
        if (track) {
         track.classList.add('show-chat'); track.classList.add('show-profile');
        }
    }

    function mobileChatBack() {
        if (track) track.classList.remove('show-chat', 'show-profile'); // only show-profile, NOT show-chat
    }

    function mobileProfileBack() {
        if (track) track.classList.remove('show-profile'); // go back to panel 2
    }

    function switchTab(type) {
        const indicator = document.getElementById('tabIndicator');
        const mediaPane = document.getElementById('mediaPane');
        const filesPane = document.getElementById('filesPane');
        const tabs = document.querySelectorAll('.tab-item');

        if (type === 'media') {
            // Move indicator to left
            indicator.style.transform = 'translateX(0%)';

            // Update active classes
            tabs[0].classList.add('active-tab');
            tabs[1].classList.remove('active-tab');

            // Show media, hide files
            mediaPane.classList.add('active-pane');
            filesPane.classList.remove('active-pane');
        } else {
            // Move indicator to right (50% because there are 2 tabs)
            indicator.style.transform = 'translateX(100%)';

            // Update active classes
            tabs[1].classList.add('active-tab');
            tabs[0].classList.remove('active-tab');

            // Show files, hide media
            filesPane.classList.add('active-pane');
            mediaPane.classList.remove('active-pane');
        }
    }

    function truncateRoomItems() {
        if (window.innerWidth <= 768) {
            document.querySelectorAll('.room-link .room-item, .active-room').forEach(el => {
                if (!el.dataset.original) el.dataset.original = el.textContent.trim();
                el.textContent = el.dataset.original.trim().charAt(0).toUpperCase();
            });
        } else {
            document.querySelectorAll('.room-link .room-item, .active-room').forEach(el => {
                if (el.dataset.original) el.textContent = el.dataset.original;
            });
        }
    }

    window.addEventListener('resize', truncateRoomItems);
    document.addEventListener('DOMContentLoaded', truncateRoomItems);
</script>

</html>