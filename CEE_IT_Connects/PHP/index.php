<?php
require 'db.php';
require 'auth.php';
$stmt = $pdo->query("SELECT * FROM internships");
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page = 'home';
date_default_timezone_set('Asia/Manila'); // set timezone
$now = new DateTime(); // current time
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - CEE IT Connects</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../CSS/index-style.css">

    <style>
        body {
            margin: 0;
            background:
                linear-gradient(90deg,
                    rgba(255, 182, 47, 0.85) 10%,
                    rgba(228, 87, 46, 0.85) 70%),
                url("../Sources/CEIT Building facade.png");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <!-- HERO SECTION -->
    <div class="hero-wrapper">
        <div class="container-fluid px-5">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <div class="welcome-overlay animate-on-scroll animate-scale">
                        <h5 class="welcome-text animate-on-scroll animate-left">Welcome to</h5>
                        <h1 class="main-heading animate-on-scroll animate-right">CEE IT Connects</h1>
                        <div class="description-box animate-on-scroll animate-scale">
                            <p>
                                CEE IT Connects is a web-based internship coordination platform
                                developed for the College of Engineering and Information Technology
                                (CEIT) of Pamantasan ng Lungsod ng Valenzuela (PLV). Designed to
                                streamline and modernize the On-the-Job Training (OJT) process,
                                CEE IT Connects bridges the gap between students, internship
                                advisers, Human Training Establishment (HTE) advisers, and
                                partner institutions.
                            </p>
                        </div>
                        <a href="applied-internship-programs.php" class="btn-find animate-on-scroll animate-scale">
                            Browse for Internships
                        </a>
                    </div>
                </div>
                <div class="col-lg-6"></div>
            </div>
        </div>
        <img src="../Sources/suhay husay.png" class="statue-overlay animate-on-scroll animate-right">
    </div>

    <!-- FEATURES -->
    <section class="features-section">
        <div class="container text-center">
            <h2 class="features-title animate-on-scroll animate-left">CEE IT Connects Features</h2>
            <div class="row g-5 mt-2">
                <div class="col-md-4">
                    <div class="feature-item animate-on-scroll animate-scale">
                        <i class="fa-solid fa-filter feature-icon"></i>
                        <h5>Smart Internship Matching</h5>
                        <p>Find internship opportunities tailored to your program, qualifications,
                            and preferred location through powerful filtering and automated listings.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-item animate-on-scroll animate-scale">
                        <i class="fa-regular fa-clock feature-icon"></i>
                        <h5>Real-Time Application Tracking</h5>
                        <p>Track your application status from submission to approval with
                            instant updates and automated notifications.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-item animate-on-scroll animate-scale">
                        <i class="fa-regular fa-rectangle-list feature-icon"></i>
                        <h5>Virtual Rooms & OJT Monitoring</h5>
                        <p>Advisers and HTE supervisors can monitor progress, evaluations,
                            and internship logs through a structured virtual workspace.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- MAP SECTION -->
    <section class="map-section">
        <div class="map-section-wrapper">
            <div class="main">
                <div class="panel">

                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Search for an internship listing"
                            onkeyup="filterListings()">
                    </div>

                    <?php foreach ($locations as $loc):
                        $openTime = new DateTime($loc['time_open']);
                        $closeTime = new DateTime($loc['time_close']);
                        $status = ($now >= $openTime && $now <= $closeTime) ? 'OPEN' : 'CLOSED';
                        $statusClass = strtolower($status);
                        ?>
                        <div class="listing" data-title="<?= strtolower(htmlspecialchars($loc['title'])) ?>"
                            data-id="<?= $loc['id'] ?>">
                            <div class="listing-header">
                                <h3>
                                    <?= htmlspecialchars($loc['title']) ?>
                                    <span class="status <?= $statusClass ?>"
                                        data-hours="<?= $openTime->format('H:i') ?>-<?= $closeTime->format('H:i') ?>">
                                        <?= $status ?>
                                    </span>
                                </h3>
                            </div>
                            <p>Opens daily: <?= $openTime->format('h:i A') ?> - <?= $closeTime->format('h:i A') ?></p>
                            <p class="distance"><i class="fas fa-map-marker-alt"></i>
                                <?= htmlspecialchars($loc['location']) ?></p>
                            <p><?= htmlspecialchars($loc['address'] ?? $loc['location']) ?></p>
                            <div class="icons">
                                <i class="fas fa-phone"
                                    onclick="toggleNumbers(this,'<?= htmlspecialchars($loc['phone_numbers']) ?>')"></i>
                                <i class="fas fa-location-arrow"
                                    onclick="getDirections(<?= $loc['latitude'] ?>,<?= $loc['longitude'] ?>)"></i>
                            </div>
                            <div class="phone-dropdown"></div>
                        </div>
                    <?php endforeach; ?>

                </div>
                <div id="map"></div>
            </div>
        </div>
    </section>

    <!-- ABOUT -->
    <section class="about-section">
        <div class="container text-center">
            <h2 class="about-title animate-on-scroll animate-left">About CEE IT Connects</h2>
            <p class="about-text animate-on-scroll animate-right">
                CEE IT Connects is an internship coordination platform developed
                to support students from the College of Engineering and Information
                Technology of Pamantasan ng Lungsod ng Valenzuela. The system
                simplifies the internship process by connecting students,
                advisers, and partner companies through a centralized digital platform.
            </p>
        </div>
        <div class="about-info-bar">
            <div class="container">
                <div class="row text-center align-items-center">
                    <div class="col-md-4 info-item"><i class="fas fa-map-marker-alt"></i> Tongco St., Maysan, Valenzuela
                        City</div>
                    <div class="col-md-4 brand-center"><strong>CEE IT CONNECTS</strong></div>
                    <div class="col-md-4 info-item"><i class="fas fa-envelope"></i> ceeitconnects@gmail.com</div>
                    <div class="col-md-4 info-item"><i class="fab fa-facebook"></i> CEE IT Connects</div>
                    <div class="col-md-4 copyright">©2026 CEE IT Connects. All rights reserved</div>
                    <div class="col-md-4 info-item"><i class="fas fa-phone"></i> 09123456789</div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let map;
        let markers = {};

        function initMap() {
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 10,
                center: { lat: 14.70, lng: 120.98 }
            });

            const locations = <?php echo json_encode($locations); ?>;

            locations.forEach(loc => {
                let markerColor = loc.available ? 'green' : 'red';
                const marker = new google.maps.Marker({
                    position: { lat: parseFloat(loc.latitude), lng: parseFloat(loc.longitude) },
                    map: map,
                    title: loc.title,
                    icon: `http://maps.google.com/mapfiles/ms/icons/${markerColor}-dot.png`
                });
                markers[loc.id] = marker;

                const info = new google.maps.InfoWindow({
                    content: `<b>${loc.title}</b><br>${loc.company}<br>${loc.location}`
                });
                marker.addListener('click', () => info.open(map, marker));
            });
        }

        function filterListings() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            document.querySelectorAll('.listing').forEach(listing => {
                const title = listing.dataset.title;
                listing.style.display = title.includes(input) ? 'block' : 'none';
            });
        }

        function toggleNumbers(iconEl, numbers) {
            const dropdown = iconEl.closest('.listing').querySelector('.phone-dropdown');
            if (dropdown.style.display === 'block') {
                dropdown.style.display = 'none';
                dropdown.innerHTML = '';
            } else {
                const nums = numbers.split(',');
                dropdown.innerHTML = nums.map(num => `<div style="padding:4px 0;cursor:pointer;" 
                onclick="copyNumber('${num.trim()}'); closeDropdown();">${num.trim()}</div>`).join('');
                dropdown.style.display = 'block';
            }
        }
        function closeDropdown() {
            document.querySelectorAll('.phone-dropdown').forEach(dropdown => {
                dropdown.style.display = 'none';
                dropdown.innerHTML = '';
            });
        }
        function copyNumber(number) {
            navigator.clipboard.writeText(number)//.then(() => { alert("Number copied: " + number); });
        }

        function getDirections(lat, lng) {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(pos => {
                    const userLat = pos.coords.latitude;
                    const userLng = pos.coords.longitude;
                    window.open(`https://www.google.com/maps/dir/?api=1&origin=${userLat},${userLng}&destination=${lat},${lng}`, '_blank');
                }, () => alert("Cannot get your location for directions."));
            } else {
                alert("Geolocation not supported by your browser");
            }
        }

        // animations
        document.addEventListener('DOMContentLoaded', () => {
            const observerOptions = { threshold: 0.2 };
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) { entry.target.classList.add('visible'); }
                    else { entry.target.classList.remove('visible'); }
                });
            }, observerOptions);

            const elementsToAnimate = document.querySelectorAll('.animate-on-scroll');
            elementsToAnimate.forEach(el => observer.observe(el));
        });
    </script>

    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDITrnTUmS0AwxqZCE8cfYI3d5kjtzg7RY&callback=initMap"
        async defer></script>
</body>

</html>