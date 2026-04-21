<nav class="navbar">
    <a href="rooms.php" class="brand">
        <img src="../Resources/logo.png" alt="Logo"/>
        <span>CEE IT CONNECTS</span>
    </a>

    <div class="nav-actions">
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