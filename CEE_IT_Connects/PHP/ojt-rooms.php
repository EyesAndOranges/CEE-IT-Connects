<?php
session_start();
require 'db.php';
require 'auth.php';
$current_room_id = $_GET['room_id'] ?? null;

$isAdviser = isset($_SESSION['role']) && $_SESSION['role'] === 'internship_adviser';
$stmt = $pdo->prepare("
    SELECT DISTINCT r.*, a.full_name, a.title, a.role
    FROM rooms r
    LEFT JOIN advisers a ON r.adviser_id = a.id
    LEFT JOIN room_members rm ON r.id = rm.room_id
    WHERE (
        r.adviser_id = ?
        OR rm.user_id = ?
    )
    " . (!$isAdviser ? "AND r.is_archived = FALSE" : "") . "
    ORDER BY r.id DESC
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);

$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>OJT Adviser | CEE IT Connects</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            background-color: #f0f2f7;
            margin: 0;
            padding-top: 70px;
            min-height: 100vh;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: 240px;
            background: #fff;
            position: fixed;
            padding: 20px 0 20px 20px;
            border-radius: 20px;
            overflow-y: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .sidebar::-webkit-scrollbar {
            display: none;
        }

        .sidebar a {
            display: block;
            align-items: center;
            padding: 10px 12px;
            color: #333;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 6px;
            font-size: 16px;
            font-weight: 500;
        }

        .sidebar a:hover {
            background: #f0f0f0;
        }

        .sidebar a.active {
            background: #ffdac8;
            color: #ff6b2c;
        }

        /* ── ROOMS LIST ── */
        .rooms-list {
            font-size: 12px;
            color: #585858;
            margin-top: 20px;
        }

        .room-item {
            padding: 8px 10px;
            border-radius: 10px;
            font-size: 13px;
        }

        .room-link {
            text-decoration: none;
            display: block;
            margin: 4px;
            width: calc(100% + 24px);
            margin-left: -12px;
        }

        .room-link .room-item:hover {
            cursor: pointer;
        }

        /* Active room highlight */
        .active-room {
            background: #ffdac8;
            color: #ff6b2c;
            font-weight: bold;
            cursor: default;
        }

        /* ── MAIN ── */
        .main {
            background-color: #f0f2f7;
            margin-left: 260px;
            padding: 20px;
            min-height: calc(100vh - 70px);
        }

        /* ── ROOM CARDS ── */
        .room-card {
            border-radius: 12px 12px 0 0;
            color: white;
            padding: 15px;
        }

        .room-footer {
            background: #fff;
            padding: 10px;
            border-radius: 0 0 12px 12px;
            text-align: center;
        }

        .enter-btn {
            background: #f4a62a;
            border: none;
            color: #fff;
            font-weight: 600;
            padding: 5px 15px;
            border-radius: 8px;
        }

        /* ── BUTTONS ── */
        .btn-create {
            background: #33448f;
            color: #fff;
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 8px;
            border: none;
        }

        .btn-create:hover {
            background: #272f54;
            color: #fff;
        }

        /* ── SECTION PANELS ── */
        .section-panel {
            display: none;
        }

        .section-panel.active {
            display: block;
        }

        /* ── BADGES ── */
        .badge-on-track {
            background: #d4edda;
            color: #155724;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .78rem;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .78rem;
        }

        .badge-submitted {
            background: #cce5ff;
            color: #004085;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .78rem;
        }

        .badge-missing {
            background: #f8d7da;
            color: #721c24;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .78rem;
        }

        /* ── STATUS TABLE ── */
        .page-section h2 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .page-section p {
            font-size: .85rem;
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

        /* ── REQUIREMENT ITEMS ── */
        .req-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .req-item:last-child {
            border-bottom: none;
        }

        .req-title {
            font-size: .9rem;
            font-weight: 600;
            color: #333;
        }

        .req-sub {
            font-size: .78rem;
            color: #888;
            margin-top: 2px;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 992px) {
            .main {
                margin-left: 0;
            }

            .sidebar {
                position: relative;
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3"
            style="z-index:9999; min-width:350px;" role="alert" id="flashAlert">
            <i class="fa fa-circle-check me-1"></i>
            <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['warning'])): ?>
        <div class="alert alert-warning alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3"
            style="z-index:9999; min-width:400px;" role="alert" id="flashAlert">
            <i class="fa fa-triangle-exclamation me-1"></i>
            <?= $_SESSION['warning'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['warning']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3"
            style="z-index:9999; min-width:350px;" role="alert" id="flashAlert">
            <i class="fa fa-circle-xmark me-1"></i>
            <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div style="display:flex; flex-direction:column; width:100%;">
            <a href="#" onclick="event.preventDefault(); showSection('home', event)" class="active" id="nav-home">
                <i class="fa-solid fa-house me-1"></i> Virtual Rooms
            </a>
            <a href="#" onclick="event.preventDefault(); showSection('status', event)" id="nav-status">
                <i class="fa-solid fa-calendar-check me-2"></i> Status
            </a>
            <a href="#" onclick="event.preventDefault(); showSection('requirements', event)" id="nav-requirements">
                <i class="fa-solid fa-list me-2"></i> Requirements
            </a>

            <div class="rooms-list" style="overflow-y:auto; max-height:400px; scrollbar-width:none;">
                <hr><br>
                <h6>ROOMS</h6>

                <?php foreach ($rooms as $room): ?>
                    <?php if (!$isAdviser && $room['is_archived'])
                        continue; ?>

                    <?php if ($current_room_id == $room['id']): ?>
                        <a href="#" onclick="return false;" class="room-link">
                            <div class="room-item active-room">
                                <?= htmlspecialchars($room['room_name']) ?>
                                <?php if ($room['is_archived']): ?>
                                    <span style="font-size:10px;"> (Archived)</span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php else: ?>
                        <a href="?room_id=<?= $room['id'] ?>" class="room-link">
                            <div class="room-item <?= $room['is_archived'] ? 'text-muted' : '' ?>">
                                <?= htmlspecialchars($room['room_name']) ?>
                                <?php if ($room['is_archived']): ?>
                                    <span style="font-size:10px;"> (Archived)</span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        <?//php var_dump($_SESSION) ?>
        <?php if ($current_room_id): ?>

            <?php include 'chat-room-content.php'; ?>

        <?php else: ?>

            <!-- HOME SECTION -->
            <div class="section-panel active" id="section-home">
                <div class="d-flex justify-content-between align-items-center">
                    <h3><strong>Virtual Rooms</strong></h3>
                    <div class="d-flex gap-2">
                        <button class="btn-create" data-bs-toggle="modal" data-bs-target="#joinRoomModal">
                            + Create a Room
                        </button>
                        <button class="btn-create" data-bs-toggle="modal" data-bs-target="#csvUploadModal">
                            <i class="fa fa-file-csv me-1"></i> Import CSV
                        </button>
                    </div>
                </div>

                <div class="row mt-1 g-4">
                    <?php foreach ($rooms as $room): ?>
                        <div class="col-12 col-sm-6 col-lg-4">
                            <div class="card shadow-sm <?= $room['is_archived'] ? 'opacity-50' : '' ?>"
                                style="border-radius:12px;">

                                <div class="room-card" style="background: <?= $color ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5><?= htmlspecialchars($room['room_name']) ?></h5>
                                            <small>
                                                <?= htmlspecialchars($room['full_name']) ?>
                                                (<?= htmlspecialchars($room['role']) ?>)
                                            </small>
                                        </div>
                                        <?php if ($isAdviser && !$room['is_archived']): ?>
                                            <form method="POST" action="archive-room.php"
                                                onsubmit="return confirm('Archive this room? Students will no longer see it.')">
                                                <input type="hidden" name="delete">
                                                <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-light" title="Archive room"
                                                    style="font-size:11px; color:#616161; border:1px solid #ccc;">
                                                    <i class="fa-solid fa-box-archive"></i>
                                                </button>
                                            </form>
                                        <?php elseif ($isAdviser && $room['is_archived']): ?>
                                            <span class="badge bg-secondary" style="font-size:10px;">Archived</span>
                                            <form method="POST" action="archive-room.php"
                                                onsubmit="return confirm('Restore this room?')">
                                                <input type="hidden" name="restore">
                                                <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-light" title="Archive room"
                                                    style="font-size:11px; color:#616161; border:1px solid #1a1a1a;">
                                                    <i class="bi bi-backpack4-fill"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="room-footer">
                                    <form method="GET">
                                        <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                        <button class="enter-btn" <?= $room['is_archived'] ? 'disabled' : '' ?>>
                                            Enter Room
                                        </button>
                                    </form>
                                </div>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- STATUS SECTION -->
            <div class="section-panel" id="section-status">
                <h4><strong>Monitor OJT progress and HTE remarks</strong></h4><br>

                <div style="display:flex; gap:10px; margin-bottom:16px; align-items:center;">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="searchInput" placeholder="Search student" oninput="filterTable()">
                    </div>
                    <select id="roomFilter" onchange="filterTable()"
                        style="padding:7px 14px; border:1px solid; border-radius:24px; font-size:12px;">
                        <option value="">All Rooms</option>
                        <?php foreach ($rooms as $r): ?>
                            <option value="<?= htmlspecialchars($r['room_name']) ?>">
                                <?= htmlspecialchars($r['room_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="background:white; border:1px solid #00000060; border-radius:8px; overflow:hidden;">
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
                                        <div class="avatar" style="background:#ff2c8f;"><strong>R</strong></div>
                                        <h6>Riva Mae Boongaling</h6>
                                    </div>
                                </td>
                                <td>Room 4A</td>
                                <td>TechCorp PH</td>
                                <td><strong>364</strong> / 486</td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
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
                                        <div class="avatar" style="background:#2c6fff;"><strong>M</strong></div>
                                        <h6>Mark Anthony Dela Cruz</h6>
                                    </div>
                                </td>
                                <td>Room 2C</td>
                                <td>TechCorp PH</td>
                                <td><strong>268</strong> / 486</td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
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

            <!-- REQUIREMENTS SECTION -->
            <div class="section-panel" id="section-requirements">
                <!-- Requirements content goes here -->
            </div>

        <?php endif; ?>
    </div>

    <!-- CREATE ROOM MODAL -->
    <div class="modal fade" id="joinRoomModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title"><strong>Create a Room</strong></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="ojt-rooms-db.php">
                        <div class="mb-3">
                            <label class="form-label">Room Name</label>
                            <input type="text" name="room_name" class="form-control" placeholder="Enter room name"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Section</label>
                            <input type="text" name="section" class="form-control" placeholder="e.g. 3-4"
                                pattern="^[0-9]+-[0-9]+$" title="Format must be like 3-4" required>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-warning px-4">Create</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- CSV UPLOAD MODAL -->
    <div class="modal fade" id="csvUploadModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title"><strong><i class="fa fa-file-csv me-2"></i>Import Students via CSV</strong>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <!-- File pick -->
                    <div id="csv-step-1">
                        <p style="font-size:13px;color:#888;">
                            Upload a <code>.csv</code> or <code>.tsv</code> file with these columns:<br>
                            <strong>student_id, full_name, email, program, year, section, contact no.,
                                company</strong><br>
                            Students will be added to a room matching their <strong>company</strong> column.
                            The room is created automatically if it doesn't exist yet.
                        </p>
                        <a href="download-csv-template.php" class="btn btn-sm btn-outline-secondary mb-3">
                            <i class="fa fa-download me-1"></i> Download Template
                        </a>
                        <input type="file" id="csvFileInput" accept=".csv,.tsv,.txt" class="form-control mb-3"
                            onchange="previewCSV(this)">
                        <div id="csv-error" class="alert alert-danger d-none"></div>
                    </div>

                    <!--Preview -->
                    <div id="csv-step-2" class="d-none">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span style="font-size:13px;color:#555;" id="csv-preview-count"></span>
                            <button class="btn btn-sm btn-outline-secondary" onclick="resetCSV()">
                                <i class="fa fa-rotate-left me-1"></i> Choose different file
                            </button>
                        </div>
                        <div style="overflow-x:auto;max-height:380px;border:1px solid #eee;border-radius:8px;">
                            <table class="table table-sm table-bordered mb-0" id="csv-preview-table"
                                style="font-size:12px;min-width:700px;">
                            </table>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-success" id="csv-confirm-btn" onclick="submitCSV()" disabled>
                        <i class="fa fa-upload me-1"></i> Confirm & Import
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showSection(name, e) {
            if (e) e.preventDefault();
            const target = document.getElementById('section-' + name);
            if (!target) {
                window.location = 'ojt-rooms.php?section=' + name;
                return;
            }
            document.querySelectorAll('.section-panel').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.sidebar a').forEach(a => a.classList.remove('active'));
            document.getElementById('section-' + name).classList.add('active');
            document.getElementById('nav-' + name).classList.add('active');
        }

        function filterTable() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const room = document.getElementById('roomFilter').value.toLowerCase();

            document.querySelectorAll('#all-students-tbody tr').forEach(row => {
                const name = row.querySelector('.student-cell h6')?.textContent.toLowerCase() ?? '';
                const rowRoom = (row.dataset.room ?? '').toLowerCase();

                const matchSearch = name.includes(search);
                const matchRoom = room === '' || rowRoom === room;

                row.style.display = (matchSearch && matchRoom) ? '' : 'none';
            });
        }

        function previewCSV(input) {
            const file = input.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function (e) {
                const text = e.target.result.trim();
                const lines = text.split(/\r?\n/).filter(l => l.trim() !== '');

                if (lines.length < 2) {
                    showCSVError('File must have at least a header row and one data row.');
                    return;
                }

                function parseCSVLine(line) {
                    const result = [];
                    let cur = '', inQuotes = false;
                    for (let i = 0; i < line.length; i++) {
                        const ch = line[i];
                        if (ch === '"') {
                            if (inQuotes && line[i + 1] === '"') { cur += '"'; i++; }
                            else inQuotes = !inQuotes;
                        } else if (ch === ',' && !inQuotes) {
                            result.push(cur.trim()); cur = '';
                        } else {
                            cur += ch;
                        }
                    }
                    result.push(cur.trim());
                    return result;
                }

                parsedHeaders = parseCSVLine(lines[0]);
                parsedRows = lines.slice(1).map(l => parseCSVLine(l));

                const required = ['student_id', 'full_name', 'email', 'company'];
                const normalized = parsedHeaders.map(h => h.toLowerCase().trim());
                const missing = required.filter(r => {
                    if (r === 'student_id') return !normalized.includes('student id') && !normalized.includes('student_id');
                    if (r === 'full_name') return !normalized.includes('full name') && !normalized.includes('full_name');
                    if (r === 'company') return !normalized.includes('company') && !normalized.includes('hte') && !normalized.includes('institution');
                    return !normalized.includes(r);
                });

                if (missing.length > 0) {
                    showCSVError('Missing required column(s): ' + missing.join(', '));
                    return;
                }

                hideCSVError();
                renderPreview();
            };
            reader.readAsText(file);
        }
        let parsedHeaders = [];
        let parsedRows = [];

        function renderPreview() {
            const table = document.getElementById('csv-preview-table');
            let html = '<thead style="position:sticky;top:0;background:#f8f9fa;"><tr>';

            const companyColIdx = parsedHeaders.findIndex(h =>
                ['company', 'hte', 'institution'].includes(h.toLowerCase().trim())
            );

            parsedHeaders.forEach((h, i) => {
                const isCompany = i === companyColIdx;
                html += `<th style="${isCompany ? 'background:#d4edda;color:#155724;' : ''}">${h}</th>`;
            });
            html += '</tr></thead><tbody>';

            parsedRows.forEach(row => {
                html += '<tr>';
                row.forEach((cell, i) => {
                    const isCompany = i === companyColIdx;
                    html += `<td style="${isCompany ? 'background:#f0fff4;font-weight:600;color:#1a7a3c;' : ''}">${cell}</td>`;
                });
                html += '</tr>';
            });

            html += '</tbody>';
            table.innerHTML = html;

            // Summary badge
            const companies = companyColIdx >= 0
                ? [...new Set(parsedRows.map(r => r[companyColIdx]).filter(Boolean))]
                : [];

            document.getElementById('csv-preview-count').innerHTML =
                `<strong>${parsedRows.length}</strong> student(s) across
         <strong>${companies.length}</strong> company room(s): `
                + companies.map(c =>
                    `<span class="rounded p-1" style="background:#1abc9c;font-size:11px;">${c}</span>`
                ).join(' ');

            document.getElementById('csv-step-1').classList.add('d-none');
            document.getElementById('csv-step-2').classList.remove('d-none');
            document.getElementById('csv-confirm-btn').disabled = false;
        }

        function resetCSV() {
            parsedHeaders = [];
            parsedRows = [];
            document.getElementById('csvFileInput').value = '';
            document.getElementById('csv-step-1').classList.remove('d-none');
            document.getElementById('csv-step-2').classList.add('d-none');
            document.getElementById('csv-confirm-btn').disabled = true;
            hideCSVError();
        }

        function showCSVError(msg) {
            const el = document.getElementById('csv-error');
            el.textContent = msg;
            el.classList.remove('d-none');
            document.getElementById('csv-confirm-btn').disabled = true;
        }

        function hideCSVError() {
            document.getElementById('csv-error').classList.add('d-none');
        }

        function submitCSV() {
            if (!parsedHeaders.length || !parsedRows.length) {
                showCSVError('No data to submit. Please upload a file first.');
                return;
            }

            const btn = document.getElementById('csv-confirm-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Importing...';

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'auto-register-save-csv.php';

            const sourceInput = document.createElement('input');
            sourceInput.type = 'hidden';
            sourceInput.name = 'source';
            sourceInput.value = 'ojt-rooms';
            form.appendChild(sourceInput);
            // Send headers
            parsedHeaders.forEach((h, i) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `headers[${i}]`;
                input.value = h;
                form.appendChild(input);
            });

            // Send rows
            parsedRows.forEach((row, rowIdx) => {
                row.forEach((cell, colIdx) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `csv[${rowIdx}][${colIdx}]`;
                    input.value = cell;
                    form.appendChild(input);
                });
            });

            document.body.appendChild(form);
            form.submit();
        }

        setTimeout(() => {
            const alert = document.getElementById('flashAlert');
            if (alert) {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 300);
            }
        }, 4000);
    </script>
</body>

</html>