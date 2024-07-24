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
$rooms[] = "ทั้งหมด"; // Add the option for "ทั้งหมด"
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
$records = [];
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['view_bill'])) {
    $selected_room = $_POST['selected_room'];
    $selected_month = $_POST['selected_month'];
    $selected_year = $_POST['selected_year'];

    if ($selected_room == "ทั้งหมด") {
        // Fetch the latest bill for each room in the selected month and year
        $sql = "SELECT b.*, u.Room_number, u.First_name, u.Last_name, 
                       CASE 
                           WHEN u.Room_number IN ('201', '202', '302', '303', '304', '305', '306', '203', '204', '205', '206', '301') THEN u.water_was 
                           WHEN u.Room_number = 'S1' THEN b.water_cost 
                           ELSE b.water_cost 
                       END as water_cost_display
                FROM bill b
                LEFT JOIN users u ON b.user_id = u.id
                WHERE b.month = ? AND b.year = ? AND u.Room_number IN ('201', '202', '302', '303', '304', '305', '306', '203', '204', '205', '206', '301', 'S1', 'S2')
                AND (u.Room_number, b.id) IN (
                    SELECT u.Room_number, MAX(b.id)
                    FROM bill b
                    LEFT JOIN users u ON b.user_id = u.id
                    WHERE b.month = ? AND b.year = ?
                    GROUP BY u.Room_number
                )
                ORDER BY u.Room_number";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $selected_month, $selected_year, $selected_month, $selected_year);
    } else {
        // Fetch the latest bill for the selected room
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
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($selected_room == "ทั้งหมด") {
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
    } else {
        $bill_details = $result->fetch_assoc();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ใบเสร็จรับเงิน</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { width: 80%; margin: auto; padding: 20px; background: #f4f4f4; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        form { margin-bottom: 20px; }
        label { margin-right: 10px; }
        select, button { padding: 5px; }
        .total { margin-top: 20px; font-weight: bold; }
        .print-button { margin-top: 20px; }
        .print-button button { margin-right: 10px; }
    </style>
</head>
<body>
<div class="container">
    <h1>เลือกข้อมูลสำหรับการออกใบเสร็จรับเงิน</h1>
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
        <button type="submit" name="view_bill">ดูใบเสร็จรับเงิน</button>
    </form>

    <?php if ($selected_room == "ทั้งหมด" && !empty($records)): ?>
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
                <th>พิมพ์ใบเสร็จ</th>
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
                    <td>
                        <button onclick="printRoomBill('<?php echo $record['Room_number']; ?>', '<?php echo $record['month']; ?>', '<?php echo $record['year']; ?>')">พิมพ์ใบเสร็จ</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <div class="print-button">
            <button onclick="printAllBills()">พิมพ์ใบเสร็จทั้งหมด</button>
        </div>
    <?php elseif (!empty($bill_details)): ?>
        <div style="border: 1px solid #ddd; padding: 20px; margin-top: 20px;">
            <h2>ใบเสร็จรับเงิน</h2>
            <p>หมายเลขห้อง: <?php echo htmlspecialchars($bill_details['Room_number']); ?></p>
            <p>ชื่อ: <?php echo htmlspecialchars($bill_details['First_name']); ?> <?php echo htmlspecialchars($bill_details['Last_name']); ?></p>
            <p>เดือน: <?php echo htmlspecialchars($bill_details['month']); ?></p>
            <p>ปี: <?php echo htmlspecialchars($bill_details['year']); ?></p>
            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <thead>
                    <tr>
                        <th style="border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2;">รายการ</th>
                        <th style="border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2;">จำนวนหน่วย</th>
                        <th style="border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2;">ราคาต่อหน่วย</th>
                        <th style="border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2;">รวม</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;">ค่าไฟฟ้า</td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($bill_details['electric_cost']); ?></td>
                        <td style="border: 1px solid #ddd; padding: 8px;">บาท</td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($bill_details['electric_cost']); ?> บาท</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;">ค่าน้ำ</td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($bill_details['water_cost_display']); ?></td>
                        <td style="border: 1px solid #ddd; padding: 8px;">บาท</td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($bill_details['water_cost_display']); ?> บาท</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;">ค่าห้อง</td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($bill_details['room_cost']); ?></td>
                        <td style="border: 1px solid #ddd; padding: 8px;">บาท</td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($bill_details['room_cost']); ?> บาท</td>
                    </tr>
                </tbody>
            </table>
            <div class="total" style="margin-top: 20px;">
                <strong>ยอดรวม: <?php echo htmlspecialchars($bill_details['total_cost']); ?> บาท</strong>
            </div>
            <div class="print-button" style="margin-top: 20px;">
                <button onclick="window.print()">พิมพ์ใบเสร็จ</button>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    function printRoomBill(roomNumber, month, year) {
        // Open a new window to print the specific room bill
        var url = 'print_receipt.php?room=' + roomNumber + '&month=' + month + '&year=' + year;
        window.open(url, '_blank');
    }

    function printAllBills() {
        var url = 'print_receipt.php?room=ทั้งหมด&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>';
        window.open(url, '_blank');
    }
</script>

</body>
</html>