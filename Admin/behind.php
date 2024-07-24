<?php
require_once 'config.php'; // Include the database configuration file

$start_month = $start_year = $end_month = $end_year = $selected_room = "";
$records = [];
$months = [];
$years = [];
$rooms = [];

// Fetch distinct months, years, and room numbers from the database for dropdown
$monthQuery = "SELECT DISTINCT month FROM bill ORDER BY month";
$yearQuery = "SELECT DISTINCT year FROM bill ORDER BY year";
$roomQuery = "SELECT DISTINCT Room_number FROM users WHERE Room_number IN ('201', '202', '302', '303', '304', '305', '306', '203', '204', '205', '206', '301', 'S1', 'S2') ORDER BY Room_number";

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
if ($roomResult = $conn->query($roomQuery)) {
    while ($row = $roomResult->fetch_assoc()) {
        $rooms[] = $row['Room_number'];
    }
}

// Handle the form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['view_records'])) {
    $start_month = $_POST['start_month'];
    $start_year = $_POST['start_year'];
    $end_month = $_POST['end_month'];
    $end_year = $_POST['end_year'];
    $selected_room = $_POST['selected_room'];

    // Prepare and execute the SQL query
    $sql = "SELECT b.*, u.Room_number, 
                   CASE 
                       WHEN u.Room_number IN ('201', '202', '302', '303', '304', '305', '306', '203', '204', '205', '206', '301') THEN u.water_was 
                       WHEN u.Room_number = 'S1' THEN b.water_cost 
                       ELSE b.water_cost 
                   END as water_cost_display
            FROM bill b
            LEFT JOIN users u ON b.user_id = u.id
            WHERE (b.year > ? OR (b.year = ? AND b.month >= ?)) AND 
                  (b.year < ? OR (b.year = ? AND b.month <= ?))";
    
    if ($selected_room != "ทั้งหมด") {
        $sql .= " AND u.Room_number = ?";
    }
    
    $sql .= " ORDER BY b.id DESC";

    $stmt = $conn->prepare($sql);

    if ($selected_room != "ทั้งหมด") {
        $stmt->bind_param("iiiiiis", $start_year, $start_year, $start_month, $end_year, $end_year, $end_month, $selected_room);
    } else {
        $stmt->bind_param("iiiiii", $start_year, $start_year, $start_month, $end_year, $end_year, $end_month);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $records = $result->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ดูยอดคำนวณค่าไฟฟ้าและค่าน้ำย้อนหลัง</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { width: 80%; margin: auto; padding: 20px; background: #f4f4f4; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        form { margin-bottom: 20px; }
        label { margin-right: 10px; }
        select, button { padding: 5px; }
    </style>
</head>
<body>
<div class="container">
    <h1>ดูยอดคำนวณค่าไฟฟ้าและค่าน้ำย้อนหลัง</h1>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <label for="selected_room">หมายเลขห้อง:</label>
        <select id="selected_room" name="selected_room" required>
            <option value="ทั้งหมด" <?php echo $selected_room == "ทั้งหมด" ? 'selected' : ''; ?>>ทั้งหมด</option>
            <?php foreach ($rooms as $room): ?>
                <option value="<?php echo $room; ?>" <?php echo $room == $selected_room ? 'selected' : ''; ?>><?php echo $room; ?></option>
            <?php endforeach; ?>
        </select>
        <label for="start_month">เดือนเริ่มต้น:</label>
        <select id="start_month" name="start_month" required>
            <?php foreach ($months as $month): ?>
                <option value="<?php echo $month; ?>" <?php echo $month == $start_month ? 'selected' : ''; ?>><?php echo $month; ?></option>
            <?php endforeach; ?>
        </select>
        <label for="start_year">ปีเริ่มต้น:</label>
        <select id="start_year" name="start_year" required>
            <?php foreach ($years as $year): ?>
                <option value="<?php echo $year; ?>" <?php echo $year == $start_year ? 'selected' : ''; ?>><?php echo $year; ?></option>
            <?php endforeach; ?>
        </select>
        <label for="end_month">เดือนสิ้นสุด:</label>
        <select id="end_month" name="end_month" required>
            <?php foreach ($months as $month): ?>
                <option value="<?php echo $month; ?>" <?php echo $month == $end_month ? 'selected' : ''; ?>><?php echo $month; ?></option>
            <?php endforeach; ?>
        </select>
        <label for="end_year">ปีสิ้นสุด:</label>
        <select id="end_year" name="end_year" required>
            <?php foreach ($years as $year): ?>
                <option value="<?php echo $year; ?>" <?php echo $year == $end_year ? 'selected' : ''; ?>><?php echo $year; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="view_records">ดูรายการ</button>
    </form>
    <?php if (!empty($records)): ?>
        <h2>ผลลัพธ์:</h2>
        <table>
            <tr>
                <th>หมายเลขห้อง</th>
                <th>เดือน</th>
                <th>ปี</th>
                <th>ค่าไฟฟ้า</th>
                <th>ค่าน้ำ</th>
                <th>ค่าห้อง</th>
                <th>ค่าใช้จ่ายทั้งหมด</th>
            </tr>
            <?php foreach ($records as $record): ?>
                <tr>
                    <td><?php echo htmlspecialchars($record['Room_number']); ?></td>
                    <td><?php echo htmlspecialchars($record['month']); ?></td>
                    <td><?php echo htmlspecialchars($record['year']); ?></td>
                    <td><?php echo htmlspecialchars($record['electric_cost']); ?> บาท</td>
                    <td><?php echo htmlspecialchars($record['water_cost_display']); ?> บาท</td>
                    <td><?php echo htmlspecialchars($record['room_cost']); ?> บาท</td>
                    <td><?php echo htmlspecialchars($record['total_cost']); ?> บาท</td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
</body>
</html>