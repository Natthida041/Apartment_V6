<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าหลัก</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --background-color: #ecf0f1;
            --text-color: #34495e;
            --hover-color: #e8f4f8;
            --sidebar-width: 250px;
        }
        
        body {
            font-family: 'Kanit', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
            color: var(--text-color);
        }
        
        .navbar {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar-brand {
            font-size: 1.5em;
            font-weight: 600;
        }
        
        .navbar-menu a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            transition: background-color 0.3s, transform 0.2s;
        }
        
        .navbar-menu a:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .container {
            display: flex;
            margin-top: 60px;
            min-height: calc(100vh - 60px);
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background-color: #fff;
            padding: 20px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease-in-out;
        }
        
        .sidebar h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .sidebar-menu a {
            display: block;
            color: var(--text-color);
            padding: 12px 15px;
            text-decoration: none;
            transition: all 0.3s;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .sidebar-menu a:hover {
            background-color: var(--hover-color);
            color: var(--primary-color);
            transform: translateX(5px);
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .content {
            flex-grow: 1;
            padding: 30px;
            overflow-y: auto;
        }
        
        .content h1 {
            color: var(--primary-color);
            margin-bottom: 30px;
            font-size: 2em;
            border-bottom: 3px solid var(--primary-color);
            padding-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                transform: translateX(-100%);
                position: fixed;
                height: 100%;
                z-index: 999;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <span class="navbar-brand">เจ้าสัว Apartment</span>
    </nav>
    
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-menu">
                <a href="Admin/crudd.php"><i class="fas fa-users"></i>จัดการข้อมูลผู้ใช้</a>
                <a href="Admin/TT.php"><i class="fas fa-tint"></i>การคำนวนค่าน้ำ-ค่าไฟ</a>
                <a href="Admin/bill.php"><i class="fas fa-file-invoice"></i>พิมพ์เอกสาร</a>
                <a href="history.php"><i class="fas fa-history"></i>รายการย้อนหลัง</a>
                <a href="summary.php?period=monthly"><i class="fas fa-chart-bar"></i>สรุปยอด</a>
            </div>
        </aside>
    </div>

    <script>
        // สคริปต์สำหรับการทำงานของ sidebar บนอุปกรณ์มือถือ
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>