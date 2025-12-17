<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - CEE IT Connects</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #F3F4F6;
            margin: 0;
            padding: 0;
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

        .content-header-card {
            background: linear-gradient(135deg, #D8BFD8 0%, #E6E6FA 100%);
            padding: 30px;
            border-radius: 12px;
        }

        .content-header-card h2 {
            font-size: 32px;
            font-weight: 700;
            color: #3F0046;
            margin-bottom: 8px;
        }

        .content-header-card p {
            font-size: 14px;
            color: #3F0046;
            font-style: italic;
        }

        .content-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            margin-top: 25px;
            gap: 20px;
        }

        .search-section {
            display: flex;
            gap: 15px;
            flex: 1;
        }

        .search-input {
            flex: 1;
            padding: 12px 20px;
            border: 1px solid #D1D5DB;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }

        .add-content-btn {
            background: #29335C;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .add-content-btn:hover {
            background: #0F172A;
        }

        .content-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .table-header {
            display: grid;
            grid-template-columns: 2fr 2fr 2fr 1fr;
            gap: 20px;
            padding: 20px 25px;
            background: #F9FAFB;
            font-size: 12px;
            font-weight: 600;
            color: #6B7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-row {
            display: grid;
            grid-template-columns: 2fr 2fr 2fr 1fr;
            gap: 20px;
            padding: 20px 25px;
            border-top: 1px solid #F3F4F6;
            align-items: center;
        }

        .td-title {
            font-size: 14px;
            font-weight: 500;
        }

        .td-publisher {
            font-size: 13px;
        }

        .td-type {
            font-size: 13px;
        }

        .td-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-start;
        }

        .action-btn {
            background: none;
            border: none;
            color: #91159F;
            cursor: pointer;
            padding: 6px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .action-btn:hover {
            background: #F3E8FF;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 70px;
            }

            .table-header,
            .table-row {
                grid-template-columns: 1fr;
                gap: 10px;
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

        <div class="content-header-card">
            <h2>Scholarships</h2>
            <p>Manage the CEE IT Connects contents</p>
        </div>

        <div class="content-controls">
            <div class="search-section">
                <input type="text" placeholder="Search Scholarships" class="search-input">
            </div>
        </div>

        <div class="content-table">
            <div class="table-header">
                <div>TITLE</div>
                <div>PUBLISHER</div>
                <div>TYPE</div>
                <div>ACTIONS</div>
            </div>
            <div class="table-row">
                <div class="td-title">FACT CHECK: Online application link for CHED’s SIKAP scholarship is fake</div>
                <div class="td-publisher">Rappler</div>
                <div class="td-type">Fact Checking</div>
                <div class="td-actions">
                    <button class="action-btn">
                        <img width="16" height="16" src="edit.png">
                    </button>
                    <button class="action-btn">
                        <img width="16" height="16" src="disable.png">
                    </button>
                    <button class="action-btn">
                        <img width="16" height="16" src="delete.png">
                    </button>
                </div>
            </div>
            <div class="table-row">
                <div class="td-title">Title</div>
                <div class="td-publisher">Publisher</div>
                <div class="td-type">Type</div>
                <div class="td-actions">
                    <button class="action-btn">
                        <img width="16" height="16" src="edit.png">
                    </button>
                    <button class="action-btn">
                        <img width="16" height="16" src="disable.png">
                    </button>
                    <button class="action-btn">
                        <img width="16" height="16" src="delete.png">
                    </button>
                </div>
            </div>
            <div class="table-row">
                <div class="td-title">Title</div>
                <div class="td-publisher">Publisher</div>
                <div class="td-type">Type</div>
                <div class="td-actions">
                    <button class="action-btn">
                        <img width="16" height="16" src="edit.png">
                    </button>
                    <button class="action-btn">
                        <img width="16" height="16" src="disable.png">
                    </button>
                    <button class="action-btn">
                        <img width="16" height="16" src="delete.png">
                    </button>
                </div>
            </div>
            <div class="table-row">
                <div class="td-title">Title</div>
                <div class="td-publisher">Publisher</div>
                <div class="td-type">Type</div>
                <div class="td-actions">
                    <button class="action-btn">
                        <img width="16" height="16" src="edit.png">
                    </button>
                    <button class="action-btn">
                        <img width="16" height="16" src="disable.png">
                    </button>
                    <button class="action-btn">
                        <img width="16" height="16" src="delete.png">
                    </button>
                </div>
            </div>
            <div class="table-row">
                <div class="td-title">Title</div>
                <div class="td-publisher">Publisher</div>
                <div class="td-type">Type</div>
                <div class="td-actions">
                    <button class="action-btn">
                        <img width="16" height="16" src="edit.png">
                    </button>
                    <button class="action-btn">
                        <img width="16" height="16" src="disable.png">
                    </button>
                    <button class="action-btn">
                        <img width="16" height="16" src="delete.png">
                    </button>
                </div>
            </div>
            <div class="table-row">
                <div class="td-title">Title</div>
                <div class="td-publisher">Publisher</div>
                <div class="td-type">Type</div>
                <div class="td-actions">
                    <button class="action-btn">
                        <img width="16" height="16" src="edit.png">
                    </button>
                    <button class="action-btn">
                        <img width="16" height="16" src="disable.png">
                    </button>
                    <button class="action-btn">
                        <img width="16" height="16" src="delete.png">
                    </button>
                </div>
            </div>
            <div class="table-row">
                <div class="td-title">Title</div>
                <div class="td-publisher">Publisher</div>
                <div class="td-type">Type</div>
                <div class="td-actions">
                    <button class="action-btn">
                        <img width="16" height="16" src="edit.png">
                    </button>
                    <button class="action-btn">
                        <img width="16" height="16" src="disable.png">
                    </button>
                    <button class="action-btn">
                        <img width="16" height="16" src="delete.png">
                    </button>
                </div>
            </div>
            <div class="table-row">
                <div class="td-title">Title</div>
                <div class="td-publisher">Publisher</div>
                <div class="td-type">Type</div>
                <div class="td-actions">
                    <button class="action-btn">
                        <img width="16" height="16" src="edit.png">
                    </button>
                    <button class="action-btn">
                        <img width="16" height="16" src="disable.png">
                    </button>
                    <button class="action-btn">
                        <img width="16" height="16" src="delete.png">
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>