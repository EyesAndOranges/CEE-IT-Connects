<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CEE IT Connects</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #F3F4F6;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }

        .top-bar {
            background: linear-gradient(90deg, #6B0F8C 0%, #3F0046 100%);
            padding: 20px 30px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .user-info {
            color: white;
            font-size: 16px;
            font-weight: 500;
        }

        .dashboard-header {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
            margin-bottom: 30px;
        }

        .header-card {
            background: linear-gradient(135deg, #D8BFD8 0%, #E6E6FA 100%);
            padding: 30px;
            border-radius: 12px;
        }

        .header-card h2 {
            font-size: 32px;
            font-weight: 700;
            color: #2D1B4E;
            margin-bottom: 8px;
        }

        .header-card p {
            font-size: 14px;
            color: #5A4A7A;
            font-style: italic;
        }

        .website-stats {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            min-width: 200px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #2D1B4E;
            margin-bottom: 5px;
        }
        
        .stat-change {
            color: #10B981;
            font-size: 12px;
            font-weight: 500;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #2D1B4E;
            margin-bottom: 5px;
        }

        .stat-label-main {
            font-size: 13px;
            color: #6B7280;
            margin-bottom: 8px;
        }

        .stat-change-info {
            color: #10B981;
            font-size: 12px;
            font-weight: 500;
        }

        .dashboard-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 20px;
        }

        .chart-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            position: relative;
            min-height: 400px;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .bar-chart {
            width: 100%;
            height: 100%;
        }

        .chart-tooltip {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #91159F;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
        }

        .tooltip-value {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .tooltip-labels {
            display: flex;
            gap: 15px;
            font-size: 12px;
        }

        .tooltip-label-item.green {
            color: #86EFAC;
        }

        .export-btn {
            position: absolute;
            bottom: 30px;
            right: 30px;
            background: #91159F;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .export-btn:hover {
            background: #3F0046;
        }

        .activities-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .activities-section h3 {
            font-size: 20px;
            font-weight: 600;
            color: #2D1B4E;
            margin-bottom: 25px;
        }

        .activity-item {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }

        .activity-dot {
            width: 8px;
            height: 8px;
            background: #91159F;
            border-radius: 50%;
            margin-top: 6px;
        }

        .activity-content {
            flex: 1;
        }

        .activity-text {
            font-size: 14px;
            color: #2D1B4E;
            margin-bottom: 5px;
        }

        .activity-time {
            font-size: 12px;
            color: #9CA3AF;
            font-style: italic;
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .dashboard-content {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 70px;
            }

            .dashboard-header {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'navigation.html'; ?>

    <div class="main-content">
        <div class="top-bar">
            <div class="user-info">[Admin Name]</div>
        </div>

        <div class="dashboard-header">
            <div class="header-card">
                <h2>Dashboard</h2>
                <p>Here's what's happening with CEE IT Connects today</p>
            </div>
            <div class="website-stats">
                <div class="stat-number">1,956</div>
                <div class="stat-label">Total website visits</div>
                <div class="stat-change">↑ 4% from last month</div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="students.png" style="width:20px; height:20px; filter: invert(100)">
                </div>
                <div class="stat-value">1,023</div>
                <div class="stat-label-main">Total students</div>
                <div class="stat-change-info">↑ 4% from last month</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <img src="administrators.png" style="width:20px; height:20px; filter: invert(100)">
                </div>
                <div class="stat-value">12</div>
                <div class="stat-label-main">Total admins</div>
                <div class="stat-change-info">↑ 2 added this week</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <img src="internships.png" style="width:20px; height:20px; filter: invert(100)">
                </div>
                <div class="stat-value">68%</div>
                <div class="stat-label-main">Scholarship bookmarks</div>
                <div class="stat-change-info">↑ 36 added this week</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <img src="internships.png" style="width:20px; height:20px; filter: invert(100)">
                </div>
                <div class="stat-value">68%</div>
                <div class="stat-label-main">Internship bookmarks</div>
                <div class="stat-change-info">↑ 36 added this week</div>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="chart-section">
                <div class="chart-container">
                    <svg viewBox="0 0 400 250" class="bar-chart">
                        <rect x="40" y="140" width="40" height="60" fill="#91159F" rx="4"/>
                        <rect x="100" y="135" width="40" height="65" fill="#91159F" rx="4"/>
                        <rect x="160" y="105" width="40" height="95" fill="#91159F" rx="4"/>
                        <rect x="220" y="75" width="40" height="125" fill="#91159F" rx="4"/>
                        <rect x="280" y="60" width="40" height="140" fill="#91159F" rx="4"/>
                        <rect x="340" y="20" width="40" height="180" fill="#91159F" rx="4"/>
                        <line x1="30" y1="200" x2="390" y2="200" stroke="#E5E7EB" stroke-width="2"/>
                    </svg>
                    <div class="chart-tooltip">
                        <div class="tooltip-value">124</div>
                        <div class="tooltip-labels">
                            <span class="tooltip-label-item">Data</span>
                            <span class="tooltip-label-item green">124%</span>
                        </div>
                    </div>
                    <button class="export-btn">Export as PDF</button>
                </div>
            </div>

            <div class="activities-section">
                <h3>Recent Activities</h3>
                <div class="activity-item">
                    <div class="activity-dot"></div>
                    <div class="activity-content">
                        <div class="activity-text">4 new student registrations</div>
                        <div class="activity-time">11 mins ago</div>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-dot"></div>
                    <div class="activity-content">
                        <div class="activity-text">New pending application approval</div>
                        <div class="activity-time">1 hour ago</div>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-dot"></div>
                    <div class="activity-content">
                        <div class="activity-text">Upload new listing from drafts? View now</div>
                        <div class="activity-time">4 hours ago</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>