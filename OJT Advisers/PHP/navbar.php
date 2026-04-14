<?php
// navbar.php
// Include at the top of <body> on every page.
// Outputs: top nav, notification panel, profile dropdown.
// JS interactions are handled by script.js (loaded at bottom of each page).
?>

<nav class="navbar">
    <a href="rooms.php" class="brand">
        <img src="../Resources/logo.png" alt="Logo"/>
        <span>CEE IT CONNECTS</span>
    </a>

    <div class="nav-actions">
        <button class="icon-btn" id="notifBtn" title="Notifications">
            <i class="bi bi-bell-fill"></i>
            <span class="badge"></span>
        </button>

        <div class="profile-wrapper">
            <button class="icon-btn" id="profileBtn" title="Profile">
                <i class="bi bi-person-circle"></i>
            </button>

            <div class="profile-drop" id="profileDrop">
                <i class="bi bi-person-circle" style="font-size:3rem;color:#dde0ea;margin-bottom:8px;"></i>
                <div class="p-name">Hello, OJT Adviser</div>
                <div class="p-email">ojtadviser@gmail.com</div>
                <button class="btn-edit" onclick="window.location.href='edit-profile.php'">
                    <i class="bi bi-pencil-square"></i>
                    Edit Profile
                </button>
                <a href="../logout.php" class="logout">Log Out?</a>
            </div>
        </div>
    </div>
</nav>

<!-- Notification Panel -->
<div class="notif-overlay" id="notifOverlay">
    <div class="notif-backdrop" id="notifBackdrop"></div>
    <aside class="notif-panel">
        <h2>Notifications</h2>
        <p class="sub">You have 3 new notifications</p>

        <div class="notif-group-title">Today</div>
        <div class="notif-item">
            <span class="notif-dot"></span>
            <div>
                <div class="notif-title">Bookmarks: XYZ Internship Prog...</div>
                <div class="notif-meta">Interest acknowledged by Julius Rey...</div>
            </div>
        </div>
        <div class="notif-item">
            <span class="notif-dot"></span>
            <div>
                <div class="notif-title">Ma. Helena: Please do submit th...</div>
                <div class="notif-meta">38 m ago</div>
            </div>
        </div>
        <div class="notif-item">
            <span class="notif-dot"></span>
            <div>
                <div class="notif-title">You might wanna check out</div>
                <div class="notif-meta">OJT Interview Tips · 2 h ago</div>
            </div>
        </div>

        <div class="notif-group-title">This week</div>
        <div class="notif-item">
            <span class="notif-dot read"></span>
            <div>
                <div class="notif-title">Bookmarks: XYZ Internship Program</div>
                <div class="notif-meta">Interest acknowledged by Julius Reyes · 2 m</div>
            </div>
        </div>
        <div class="notif-item">
            <span class="notif-dot read"></span>
            <div>
                <div class="notif-title">Ma. Helena: Please do submit the req...</div>
                <div class="notif-meta">38 m ago</div>
            </div>
        </div>
        <div class="notif-item">
            <span class="notif-dot read"></span>
            <div>
                <div class="notif-title">You might wanna check out</div>
                <div class="notif-meta">OJT Interview Tips · 2 h ago</div>
            </div>
        </div>
    </aside>
</div>