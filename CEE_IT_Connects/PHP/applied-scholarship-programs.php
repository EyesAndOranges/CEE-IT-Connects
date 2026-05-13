<?php $page = 'opportunity'; ?>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Scholarships | CEE IT Connects</title>


  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../CSS/index-style.css">


  <style>
    .listing-wrapper {
      background: #f4f4f4;
      padding: 40px 0;
    }

    .filter-box {
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 6px;
      padding: 16px;
    }

    .filter-box h6 {
      font-weight: 700;
      margin-bottom: 10px;
    }

    .listing-card {
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 6px;
      padding: 18px;
      margin-bottom: 15px;
    }

    .listing-card h6 {
      color: #ff3d00;
      font-weight: 800;
    }

    .badge-date {
      font-size: 12px;
      color: #777;
    }

    .btn-read {
      background: #ff6a00;
      color: #fff;
      font-weight: 600;
    }

    .btn-apply {
      background: #272f54;
      color: #fff;
      font-weight: 600;
    }

    .btn-search {
      background: #272f54;
      color: #fff;
      font-weight: 600;
    }
  </style>
</head>

<body>

  <?php include 'navbar.php'; ?>

  <section class="listing-wrapper">
    <div class="container-fluid px-5">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold">Scholarships</h4>
        <form class="d-flex" style="max-width: 400px; width: 100%;">
          <input class="form-control me-2" type="search" placeholder="Search scholarships..." aria-label="Search">
          <button class="btn btn-search" type="submit">Search</button>
        </form>
      </div>


      <div class="row">
        <div class="col-lg-3">
          <div class="filter-box">

            <h6>Filters</h6>
            <!-- Sort by -->
            <div class="mb-3">
              <strong>Sort by</strong>
              <div class="form-check"><input class="form-check-input" type="radio" name="sort"> All scholarship listings
              </div>
              <div class="form-check"><input class="form-check-input" type="radio" name="sort"> Civil Engineering</div>
              <div class="form-check"><input class="form-check-input" type="radio" name="sort"> Electrical Engineering
              </div>
              <div class="form-check"><input class="form-check-input" type="radio" name="sort"> Information Technology
              </div>
            </div>

            <!-- Deadline -->
            <div class="mb-3">
              <strong>Deadline</strong>
              <div class="form-check"><input class="form-check-input" type="radio" name="deadline"> All dates</div>
              <div class="form-check"><input class="form-check-input" type="radio" name="deadline"> Due this week</div>
              <div class="form-check"><input class="form-check-input" type="radio" name="deadline"> Due this month</div>
              <div class="form-check"><input class="form-check-input" type="radio" name="deadline"> Upcoming (future
                deadlines)</div>
            </div>

            <!-- Academic Requirement -->
            <div class="mb-3">
              <strong>Academic Requirement</strong>
              <div class="form-check"><input class="form-check-input" type="radio" name="gpa"> All GPAs</div>
              <div class="form-check"><input class="form-check-input" type="radio" name="gpa"> GPA 2.0 and above</div>
              <div class="form-check"><input class="form-check-input" type="radio" name="gpa"> GPA 1.50 and above</div>
              <div class="form-check"><input class="form-check-input" type="radio" name="gpa"> No GPA requirement</div>
            </div>

            <!-- Year Level -->
            <div class="mb-3">
              <strong>Year Level</strong>
              <div class="form-check"><input class="form-check-input" type="radio" name="year"> All year levels</div>
              <div class="form-check"><input class="form-check-input" type="radio" name="year"> First year</div>
              <div class="form-check"><input class="form-check-input" type="radio" name="year"> Second year</div>
              <div class="form-check"><input class="form-check-input" type="radio" name="year"> Third year</div>
              <div class="form-check"><input class="form-check-input" type="radio" name="year"> Fourth year</div>
            </div>
          </div>
        </div>


        <!-- The Listings -->
        <div class="col-lg-9">
          <small class="text-muted">Showing {Total Number} out of {Total Number of Listing} scholarship listings</small>


          <div class="listing-card mt-3">
            <h6>CHED Tertiary Education Subsidy</h6>
            <p class="badge-date">CHED Scholarship Program | Published October 6, 2025</p>
            <p class="mb-3">Financial assistance for eligible tertiary students enrolled in CHED-recognized
              institutions.</p>
            <div class="d-flex gap-2">
              <button class="btn btn-read">Read More</button>
              <button class="btn btn-apply">Apply</button>
            </div>
          </div>
          <div class="listing-card mt-3">
            <h6>CHED Tertiary Education Subsidy</h6>
            <p class="badge-date">CHED Scholarship Program | Published October 6, 2025</p>
            <p class="mb-3">Financial assistance for eligible tertiary students enrolled in CHED-recognized
              institutions.</p>
            <div class="d-flex gap-2">
              <button class="btn btn-read">Read More</button>
              <button class="btn btn-apply">Apply</button>
            </div>
          </div>
          <div class="listing-card mt-3">
            <h6>CHED Tertiary Education Subsidy</h6>
            <p class="badge-date">CHED Scholarship Program | Published October 6, 2025</p>
            <p class="mb-3">Financial assistance for eligible tertiary students enrolled in CHED-recognized
              institutions.</p>
            <div class="d-flex gap-2">
              <button class="btn btn-read">Read More</button>
              <button class="btn btn-apply">Apply</button>
            </div>
          </div>


          <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
          <script src="../JS/index-script.js"></script>

</html>