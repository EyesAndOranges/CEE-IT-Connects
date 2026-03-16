<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uploaded Documents - CEE IT Connects</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../CSS/index-style.css">
    <style>
        .page-title {
            margin-top: 30px;
            margin-bottom: 20px;
        }
        .document-table th, .document-table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid px-5">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="Sources/CEE IT Connects Logo.png" alt="Logo" class="nav-logo">
                <span class="ms-2 brand-text">CEE IT CONNECTS</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                <ul class="navbar-nav gap-4 align-items-center">
                    <li class="nav-item"><a class="nav-link" href="index.html">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Announcements</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="oppDropdown" role="button" data-bs-toggle="dropdown">Opportunity</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Scholarship</a></li>
                            <li><a class="dropdown-item" href="#">Internship</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="#">About Us</a></li>
                </ul>
            </div>

            <div class="navbar-icons d-flex gap-3 align-items-center">
                <a href="#"><i class="fa-regular fa-bell fa-lg"></i></a>
                <a href="#"><i class="fa-regular fa-user fa-lg"></i></a>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="container-fluid px-5">
        <h2 class="page-title">Uploaded Documents</h2>
        
        <!-- Search & Filters -->
        <div class="row mb-3">
            <div class="col-md-3">
                <input type="text" class="form-control" id="searchInput" placeholder="Search documents...">
            </div>
            <div class="col-md-3">
                <select class="form-select" id="typeFilter">
                    <option value="">All Types</option>
                    <option value="Image">Image</option>
                    <option value="PDF">PDF</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="modifiedFilter">
                    <option value="">All Dates</option>
                    <option value="June 3, 2024">June 3, 2024</option>
                    <option value="July 10, 2024">July 10, 2024</option>
                    <option value="August 16, 2024">August 16, 2024</option>
                    <option value="August 17, 2024">August 17, 2024</option>
                    <option value="September 3, 2024">September 3, 2024</option>
                    <option value="November 5, 2024">November 5, 2024</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary" onclick="filterTable()"><i class="fa fa-search"></i> Search</button>
            </div>
        </div>

        <!-- Documents Table -->
        <div class="table-responsive">
            <table class="table table-bordered document-table" id="documentsTable">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Date Modified</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Student ID (front/back)</td>
                        <td>Image</td>
                        <td>June 3, 2024</td>
                    </tr>
                    <tr>
                        <td>COR (Certificate of Registration)</td>
                        <td>Image</td>
                        <td>June 3, 2024</td>
                    </tr>
                    <tr>
                        <td>Resume</td>
                        <td>PDF</td>
                        <td>July 10, 2024</td>
                    </tr>
                    <tr>
                        <td>Barangay Clearance</td>
                        <td>PDF</td>
                        <td>August 16, 2024</td>
                    </tr>
                    <tr>
                        <td>Certificate of Indigency</td>
                        <td>PDF</td>
                        <td>August 16, 2024</td>
                    </tr>
                    <tr>
                        <td>Birth Certificate</td>
                        <td>PDF</td>
                        <td>August 17, 2024</td>
                    </tr>
                    <tr>
                        <td>Portfolio</td>
                        <td>PDF</td>
                        <td>September 3, 2024</td>
                    </tr>
                    <tr>
                        <td>Endorsement Letter</td>
                        <td>PDF</td>
                        <td>November 5, 2024</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function filterTable() {
            const typeFilter = document.getElementById("typeFilter").value.toLowerCase();
            const modifiedFilter = document.getElementById("modifiedFilter").value.toLowerCase();
            const searchInput = document.getElementById("searchInput").value.toLowerCase();
            const table = document.getElementById("documentsTable");
            const rows = table.getElementsByTagName("tr");

            for (let i = 1; i < rows.length; i++) {
                const name = rows[i].cells[0].textContent.toLowerCase();
                const type = rows[i].cells[1].textContent.toLowerCase();
                const modified = rows[i].cells[2].textContent.toLowerCase();

                const matchesSearch = name.includes(searchInput);
                const matchesType = typeFilter === "" || type === typeFilter;
                const matchesModified = modifiedFilter === "" || modified === modifiedFilter;

                rows[i].style.display = (matchesSearch && matchesType && matchesModified) ? "" : "none";
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
