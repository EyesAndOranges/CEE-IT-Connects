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

        .student-header-card {
            background: linear-gradient(135deg, #D8BFD8 0%, #E6E6FA 100%);
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            color: #3F0046;
        }

        .student-header-card h2 {
            font-size: 32px;
            font-weight: 700;
            color: #3F0046;
            margin-bottom: 8px;
        }

        .student-header-card p {
            font-size: 14px;
            color: #3F0046;
            font-style: italic;
        }

        .role-filters {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .role-btn {
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

        .role-btn:hover {
            background: #3F0046;
        }

        .student-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            gap: 20px;
        }

        .search-section {
            display: flex;
            gap: 15px;
            flex: 1;
        }

        .type-dropdown {
            padding: 12px 20px;
            border: 1px solid #D1D5DB;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            background: white;
            cursor: pointer;
            min-width: 120px;
        }

        .search-input {
            flex: 1;
            padding: 12px 20px;
            border: 1px solid #D1D5DB;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }

        .add-student-btn {
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

        .add-student-btn:hover {
            background: #0F172A;
        }

        .student-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .table-header {
            display: grid;
            grid-template-columns: 2fr 2fr 2fr 1fr 1fr;
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
            grid-template-columns: 2fr 2fr 2fr 1fr 1fr;
            gap: 20px;
            padding: 20px 25px;
            border-top: 1px solid #F3F4F6;
            align-items: center;
        }

        .td-name {
            font-size: 14px;
            font-weight: 500;
        }

        .td-email {
            font-size: 13px;
        }

        .td-activity {
            font-size: 13px;
        }

        .td-program {
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

        <div class="student-header-card">
            <h2>Students</h2>
            <p>Manage the CEE IT Connects students</p>
        </div>

        <div class="role-filters">
            <button class="role-btn">Information Officer</button>
            <button class="role-btn">Scholarship Administrators</button>
            <button class="role-btn">Internship Administrators</button>
        </div>

        <div class="student-controls">
            <div class="search-section">
                <select class="type-dropdown">
                    <option>Type</option>
                </select>
                <input type="text" placeholder="Search Information Officer" class="search-input">
            </div>
            <button class="add-student-btn">+ Add Student</button>
        </div>

        <div class="student-table">
            <div class="table-header">
                <div>NAME</div>
                <div>EMAIL</div>
                <div>ACTIVITY</div>
                <div>PROGRAM</div>
                <div>ACTIONS</div>
            </div>
            <div class="table-row">
                <div class="td-name">Maria Carmela Alfonso</div>
                <div class="td-email">macarmela@evc@gmail.com</div>
                <div class="td-activity">+1 Internship Interested</div>
                <div class="td-program">CE</div>
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
                <div class="td-name">Juan Leandro Solano</div>
                <div class="td-email">lesolano01@gmail.com</div>
                <div class="td-activity">Applied in Dr. Pio</div>
                <div class="td-program">CE</div>
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
                <div class="td-name">First Name Last Name</div>
                <div class="td-email">studentName@gmail.com</div>
                <div class="td-activity">+1 Internship Interested</div>
                <div class="td-program">CE</div>
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
                <div class="td-name">First Name Last Name</div>
                <div class="td-email">studentName@gmail.com</div>
                <div class="td-activity">Applied in Kuya Win</div>
                <div class="td-program">CE</div>
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
                <div class="td-name">First Name Last Name</div>
                <div class="td-email">studentName@gmail.com</div>
                <div class="td-activity">Registered</div>
                <div class="td-program">CE</div>
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
                <div class="td-name">First Name Last Name</div>
                <div class="td-email">studentName@gmail.com</div>
                <div class="td-activity">Applied in Kuya Win</div>
                <div class="td-program">CE</div>
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
                <div class="td-name">First Name Last Name</div>
                <div class="td-email">studentName@gmail.com</div>
                <div class="td-activity">Applied in CHED TES</div>
                <div class="td-program">CE</div>
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
                <div class="td-name">First Name Last Name</div>
                <div class="td-email">studentName@gmail.com</div>
                <div class="td-activity">Registered</div>
                <div class="td-program">CE</div>
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