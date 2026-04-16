<?php
require 'db.php';
require 'auth.php';
$stmt = $pdo->query("SELECT * FROM internships");
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php $page = 'home'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Map</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/index-style.css">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: #f4f4f9;
        }

        /* HEADER */

        .header {
            background: #FF5A1F;
            color: white;
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            padding: 12px;
        }

        /* LAYOUT */

        .main {
            display: flex;
            height: 100vh;
        }

        /* LEFT PANEL */

        .panel {
            width: 360px;
            background: #4B4596;
            color: white;
            padding: 15px;
            overflow-y: auto;
        }

        /* SEARCH */

        .search-box {
            margin-bottom: 15px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 14px;
            border-radius: 20px;
            border: none;
            outline: none;
            font-size: 14px;
        }

        /* LISTING */

        .listing {
            background: #5A5FA8;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 12px;
            position: relative;
        }

        .listing-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .listing h3 {
            margin: 0;
            font-size: 16px;
            color: #FFA500;
        }

        .status {
            color: #00FF00;
            font-weight: bold;
            font-size: 12px;
            margin-left: 6px;
        }

        .listing p {
            margin: 4px 0;
            font-size: 13px;
        }

        /* DISTANCE */

        .distance {
            color: #FFD700;
            font-size: 13px;
            display: flex;
            align-items: center;
        }

        .distance i {
            margin-right: 5px;
        }

        /* ICONS */

        .icons {
            display: flex;
            gap: 12px;
        }

        .icons i {
            cursor: pointer;
            font-size: 16px;
        }

        .icons i:hover {
            color: #FFA500;
        }

        /* PHONE DROPDOWN */

        .phone-dropdown {
            display: none;
            position: absolute;
            right: 10px;
            top: 40px;
            background: white;
            color: black;
            border-radius: 6px;
            padding: 6px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
            z-index: 5;
        }

        #map {
            flex: 1;
            height: 100%;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="main">
        <div class="panel">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search for an internship listing"
                    onkeyup="filterListings()">
            </div>

            <?php foreach ($locations as $loc): ?>
                <div class="listing">
                    <h3><?= htmlspecialchars($loc['title']) ?>
                        <span class="status" data-hours="9:00-21:00">OPEN</span>
                    </h3>
                    <p>Opens daily, 9 AM - 9 PM</p>
                    <p class="distance"><i class="fas fa-map-marker-alt"></i>4 km
                        (<?= htmlspecialchars($loc['location']) ?>)</p>
                    <p><?= htmlspecialchars($loc['address'] ?? $loc['location']) ?></p>

                    <div class="icons">
                        <i class="fas fa-phone"
                            onclick="toggleNumbers(this, '<?= htmlspecialchars($loc['phone_numbers']) ?>')"></i>
                        <i class="fas fa-location-arrow"
                            onclick="getDirections(<?= $loc['latitude'] ?>, <?= $loc['longitude'] ?>)"></i>
                    </div>
                    <!-- Dropdown container -->
                    <div class="phone-dropdown">>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div id="map"></div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let map;
        let userMarker;

        function initMap() {
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 10,
                center: { lat: 14.7011, lng: 120.9830 }
            });

            const locations = <?php echo json_encode($locations); ?>;

            locations.forEach(loc => {
                const marker = new google.maps.Marker({
                    position: { lat: parseFloat(loc.latitude), lng: parseFloat(loc.longitude) },
                    map: map,
                    title: loc.title
                });

                const info = new google.maps.InfoWindow({
                    content: `<b>${loc.title}</b><br>${loc.company}<br>${loc.location}`
                });

                marker.addListener('click', () => info.open(map, marker));
            });

            // Show user's location on button click
            const showLocationBtn = document.createElement('button');
            showLocationBtn.textContent = "Show My Location";
            showLocationBtn.style.position = "absolute";
            showLocationBtn.style.top = "10px";
            showLocationBtn.style.left = "50%";
            showLocationBtn.style.transform = "translateX(-50%)";
            showLocationBtn.style.padding = "8px 12px";
            showLocationBtn.style.zIndex = 5;
            showLocationBtn.style.background = "#FFA500";
            showLocationBtn.style.color = "#fff";
            showLocationBtn.style.border = "none";
            showLocationBtn.style.borderRadius = "4px";
            map.controls[google.maps.ControlPosition.TOP_CENTER].push(showLocationBtn);

            showLocationBtn.addEventListener('click', () => {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(pos => {
                        const coords = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                        if (!userMarker) {
                            userMarker = new google.maps.Marker({
                                position: coords,
                                map: map,
                                title: 'Your Location',
                                icon: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png'
                            });
                        } else {
                            userMarker.setPosition(coords);
                        }
                        map.setCenter(coords);
                    }, () => alert("Geolocation permission denied or unavailable."));
                } else {
                    alert("Geolocation not supported by your browser.");
                }
            });
        }

        // Show numbers dropdown (alert now)
        function showNumbers(numbers) {
            const nums = numbers.split(',');
            alert("Call numbers:\n" + nums.join('\n'));
        }

        // Directions
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

        // Filter listings
        function filterListings() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            document.querySelectorAll('.listing').forEach(listing => {
                const text = listing.textContent.toLowerCase();
                listing.style.display = text.includes(input) ? 'block' : 'none';
            });
        }
        function updateStatus() {
            const now = new Date();
            const hours = now.getHours();
            document.querySelectorAll('.status').forEach(statusEl => {
                const timeRange = statusEl.dataset.hours.split('-');
                const openHour = parseInt(timeRange[0]);
                const closeHour = parseInt(timeRange[1]);

                if (hours >= openHour && hours < closeHour) {
                    statusEl.textContent = 'OPEN';
                    statusEl.style.color = '#00FF00'; // green
                } else {
                    statusEl.textContent = 'CLOSED';
                    statusEl.style.color = '#FF4C4C'; // red
                }
            });
        }

        function toggleNumbers(iconEl, numbers) {
            const dropdown = iconEl.closest('.listing').querySelector('.phone-dropdown');

            if (dropdown.style.display === 'block') {
                dropdown.style.display = 'none';
                dropdown.innerHTML = '';
            } else {
                const nums = numbers.split(',');

                dropdown.innerHTML = nums.map(num => `
            <div 
                style="padding:4px 0; cursor:pointer;"
                onclick="copyNumber('${num.trim()}')"
            >
                ${num.trim()}
            </div>
        `).join('');

                dropdown.style.display = 'block';
            }
        }
        function copyNumber(number) {
            navigator.clipboard.writeText(number).then(() => {
                alert("Number copied: " + number);
            }).catch(() => {
                alert("Copy failed. Your browser may not support clipboard.");
            });
        }

        document.addEventListener('click', function (e) {
            document.querySelectorAll('.phone-dropdown').forEach(dd => {
                if (!dd.contains(e.target) && !dd.previousElementSibling.querySelector('i').contains(e.target)) {
                    dd.style.display = 'none';
                }
            });
        });
        // This means that the status will update every minute
        updateStatus();
        setInterval(updateStatus, 60000);
    </script>

    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDITrnTUmS0AwxqZCE8cfYI3d5kjtzg7RY&callback=initMap"
        async defer></script>
</body>

</html>