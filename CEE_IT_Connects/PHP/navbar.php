
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CEE IT CONNECTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* NAVBAR BACKGROUND */

.navbar-custom{
    background:#2c3e67;
    padding:12px 0;
}

/* LOGO */
.nav-logo{
    width:38px;
}

/* BRAND TEXT */
.brand-text{
    color:#ff6b2c;
    font-weight:700;
    letter-spacing:1px;
    font-size:18px;
}

/* MENU LINKS */
.navbar-nav .nav-link{
    color:white;
    font-weight:500;
    font-size:15px;
    transition:0.2s;
}

/* HOVER */
.navbar-nav .nav-link:hover{
    color:#00cfff;
}

/* ACTIVE LINK */
.navbar-nav .active{
    color:white;
    font-weight:600;
}

/* RIGHT ICONS */
.navbar-icons i{
    color:white;
    font-size:20px;
}

.navbar-icons i:hover{
    color:#00cfff;
}
    </style>
</head>

<nav class="navbar navbar-expand-lg navbar-custom fixed-top">
    <div class="container-fluid px-5">

        <!-- Logo + Brand -->
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="../Sources/CEE IT Connects Logo.png" class="nav-logo">
            <span class="brand-text ms-2">CEE IT CONNECTS</span>
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Center Menu -->
        <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
            <ul class="navbar-nav gap-4">

                <li class="nav-item">
                    <a class="nav-link <?= ($page=='home')?'active':'' ?>" href="index.php">Home</a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= ($page == 'opportunity') ? 'active-link' : '' ?>"
                       href="#" role="button" data-bs-toggle="dropdown">
                        Opportunity
                    </a>

                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="applied-scholarship-programs.php">Scholarship</a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="applied-internship-programs.php">Internship</a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= ($page=='announcements')?'active':'' ?>" href="announcement.php">
                        Announcements
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= ($page=='about')?'active':'' ?>" href="about.php">
                        About
                    </a>
                </li>

            </ul>
        </div>

        <!-- Right Icons -->
        <div class="navbar-icons d-flex align-items-center gap-3">
            <a href="#"><i class="fa-regular fa-bell"></i></a>
            <a href="personal-information.php"><i class="fa-regular fa-user"></i></a>
        </div>

    </div>
</nav>
