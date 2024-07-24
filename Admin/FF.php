<?php
require_once 'config.php'; // Include the database configuration file

$selected_room = $selected_month = $selected_year = "";
$rooms = [];
$months = [];
$years = [];

// Fetch distinct rooms, months, and years from the database for dropdown
$roomQuery = "SELECT DISTINCT Room_number FROM users WHERE Room_number IN ('201', '202', '302', '303', '304', '305', '306', '203', '204', '205', '206', '301', 'S1', 'S2') ORDER BY Room_number";
$monthQuery = "SELECT DISTINCT month FROM bill ORDER BY month";
$yearQuery = "SELECT DISTINCT year FROM bill ORDER BY year";

if ($roomResult = $conn->query($roomQuery)) {
    while ($row = $roomResult->fetch_assoc()) {
        $rooms[] = $row['Room_number'];
    }
}
if ($monthResult = $conn->query($monthQuery)) {
    while ($row = $monthResult->fetch_assoc()) {
        $months[] = $row['month'];
    }
}
if ($yearResult = $conn->query($yearQuery)) {
    while ($row = $yearResult->fetch_assoc()) {
        $years[] = $row['year'];
    }
}

// Handle the form submission
$bill_details = null;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['view_bill'])) {
    $selected_room = $_POST['selected_room'];
    $selected_month = $_POST['selected_month'];
    $selected_year = $_POST['selected_year'];

    // Prepare and execute the SQL query to fetch the latest bill for the selected room
    $sql = "SELECT b.*, u.Room_number, u.First_name, u.Last_name, 
                   CASE 
                       WHEN u.Room_number IN ('201', '202', '302', '303', '304', '305', '306', '203', '204', '205', '206', '301') THEN u.water_was 
                       WHEN u.Room_number = 'S1' THEN b.water_cost 
                       ELSE b.water_cost 
                   END as water_cost_display
            FROM bill b
            LEFT JOIN users u ON b.user_id = u.id
            WHERE u.Room_number = ? AND b.month = ? AND b.year = ?
            ORDER BY b.id DESC LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $selected_room, $selected_month, $selected_year);
    $stmt->execute();
    $result = $stmt->get_result();
    $bill_details = $result->fetch_assoc();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ใบแจ้งหนี้</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        form {
            margin-bottom: 20px;
            text-align: center;
        }
        label {
            margin-right: 10px;
            font-weight: bold;
        }
        select, button {
            padding: 8px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-right: 10px;
        }
        button {
            background-color: #0071C3;
            color: #fff;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #005194;
        }
        .invoice {
            margin-top: 20px;
        }
        .invoice-header, .invoice-details, .invoice-items, .total-amount, .print-button {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
        }
        .invoice-header h2 {
            text-align: center;
            color: #333;
        }
        .invoice-items table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .invoice-items table th, .invoice-items table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .invoice-items table th {
            background-color: #f2f2f2;
        }
        .total-amount {
            text-align: right;
            font-size: 20px;
            color: red;
            font-weight: bold;
        }
        .print-button {
            text-align: center;
            margin-top: 20px;
        }
        .print-button button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #0071C3;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .print-button button:hover {
            background-color: #005194;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>เลือกข้อมูลสำหรับการออกใบแจ้งหนี้</h1>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <label for="selected_room">หมายเลขห้อง:</label>
        <select id="selected_room" name="selected_room" required>
            <?php foreach ($rooms as $room): ?>
                <option value="<?php echo $room; ?>" <?php echo $room == $selected_room ? 'selected' : ''; ?>><?php echo $room; ?></option>
            <?php endforeach; ?>
        </select>
        <label for="selected_month">เดือน:</label>
        <select id="selected_month" name="selected_month" required>
            <?php foreach ($months as $month): ?>
                <option value="<?php echo $month; ?>" <?php echo $month == $selected_month ? 'selected' : ''; ?>><?php echo $month; ?></option>
            <?php endforeach; ?>
        </select>
        <label for="selected_year">ปี:</label>
        <select id="selected_year" name="selected_year" required>
            <?php foreach ($years as $year): ?>
                <option value="<?php echo $year; ?>" <?php echo $year == $selected_year ? 'selected' : ''; ?>><?php echo $year; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="view_bill">ดูใบแจ้งหนี้</button>
    </form>

    <?php if (!empty($bill_details)): ?>
        <div class="invoice">
            <div class="invoice-header">
                <h2>ใบแจ้งหนี้</h2>
            </div>
            <div class="invoice-details">
                <p>หมายเลขห้อง: <?php echo htmlspecialchars($bill_details['Room_number']); ?></p>
                <p>ชื่อ: <?php echo htmlspecialchars($bill_details['First_name']); ?> <?php echo htmlspecialchars($bill_details['Last_name']); ?></p>
                <p>เดือน: <?php echo htmlspecialchars($bill_details['month']); ?></p>
                <p>ปี: <?php echo htmlspecialchars($bill_details['year']); ?></p>
            </div>
            <div class="invoice-items">
                <table>
                    <thead>
                        <tr>
                            <th>รายการ</th>
                            <th>จำนวนหน่วย</th>
                            <th>ราคาต่อหน่วย</th>
                            <th>รวม</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>ค่าเช่าห้องรายเดือน/Rent</td>
                            <td>1</td>
                            <td><?php echo number_format($bill_details['room_cost'], 2); ?></td>
                            <td><?php echo number_format($bill_details['room_cost'], 2); ?></td>
                        </tr>
                        <tr>
                            <td>ค่าไฟ/Electricity</td>
                            <td><?php echo htmlspecialchars($bill_details['electric_cost'] / 7); ?></td>
                            <td>7.00</td>
                            <td><?php echo number_format($bill_details['electric_cost'], 2); ?></td>
                        </tr>
                        <tr>
                            <td>ค่าน้ำ/Water</td>
                            <td><?php echo htmlspecialchars($bill_details['water_cost_display'] / 20); ?></td>
                            <td><?php echo number_format($bill_details['water_cost_display'], 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="total-amount">
                <p>รวมทั้งสิ้น: <?php echo number_format($bill_details['total_cost'], 2); ?> บาท</p>
            </div>
            <div class="print-button">
                <button onclick="window.print()">พิมพ์ใบแจ้งหนี้</button>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
