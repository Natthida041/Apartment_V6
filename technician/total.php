<?php
require_once 'config.php'; // Include the configuration file for database settings

// Initialize variables
$room_number = "";
$first_name = "";
$last_name = "";
$price = "";
$type_name = "";
$last_month_electric = 0;
$difference_electric = 0;
$Electricity_total = 0;
$total = 0;

$last_month_water = 0;
$difference_water = 0;
$Water_total = 0;

function calculateWaterCost($room_number) {
    // Define room groups with specific water costs
    $rooms_150 = ['201', '202', '302', '303', '304', '305', '306'];
    $rooms_200 = ['203', '204', '205', '206', '301'];

    if (in_array($room_number, $rooms_150)) {
        return 150;
    } elseif (in_array($room_number, $rooms_200)) {
        return 200;
    }
    return 0;
}

function getUserDetails($conn, $room_number) {
    try {
        $stmt = $conn->prepare("SELECT u.Room_number, u.First_name, u.Last_name, t.price, t.type_name FROM users u JOIN type t ON u.type_id = t.id WHERE u.Room_number = ?");
        $stmt->bind_param("s", $room_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            return null;
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        return null;
    }
}

function getLastMonthElectric($conn, $room_number) {
    try {
        $stmt = $conn->prepare("SELECT meter_electric FROM electric WHERE user_id = (SELECT id FROM users WHERE Room_number = ?) ORDER BY date_record DESC LIMIT 1");
        $stmt->bind_param("s", $room_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_assoc()['meter_electric'];
        } else {
            return 0;
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        return 0;
    }
}

function getLastMonthWater($conn, $room_number) {
    try {
        $stmt = $conn->prepare("SELECT meter_water FROM water WHERE user_id = (SELECT id FROM users WHERE Room_number = ?) ORDER BY date_record DESC LIMIT 1");
        $stmt->bind_param("s", $room_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_assoc()['meter_water'];
        } else {
            return 0;
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        return 0;
    }
}

function saveElectricMeter($conn, $room_number, $current_electric) {
    try {
        $stmt = $conn->prepare("INSERT INTO electric (user_id, meter_electric, date_record) VALUES ((SELECT id FROM users WHERE Room_number = ?), ?, CURDATE())");
        $stmt->bind_param("sd", $room_number, $current_electric);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

function saveWaterMeter($conn, $room_number, $current_water) {
    try {
        $stmt = $conn->prepare("INSERT INTO water (user_id, meter_water, date_record) VALUES ((SELECT id FROM users WHERE Room_number = ?), ?, CURDATE())");
        $stmt->bind_param("sd", $room_number, $current_water);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

function saveBill($conn, $room_number, $Electricity_total, $Water_total, $price, $total) {
    $water_cost = calculateWaterCost($room_number); // Calculate additional water cost based on the room number
    $total_with_water = $total + $water_cost; // Add water cost to the total bill

    try {
        $stmt = $conn->prepare("INSERT INTO bill (user_id, month, year, electric_cost, water_cost, room_cost, total_cost, Room_number) VALUES ((SELECT id FROM users WHERE Room_number = ?), MONTH(CURDATE()), YEAR(CURDATE()), ?, ?, ?, ?, ?)");
        // Ensure that each parameter is used appropriately
        $stmt->bind_param("sddddd", $room_number, $Electricity_total, $Water_total, $price, $total_with_water, $room_number);

        return $stmt->execute();
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

function fetchRoomNumbers($conn) {
    try {
        $result = $conn->query("SELECT Room_number FROM users");
        if ($result->num_rows > 0) {
            return $result;
        } else {
            return [];
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        return [];
    }
}

$roomNumbers = fetchRoomNumbers($conn);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['room_number'])) {
        $room_number = $_POST['room_number'];
        $userDetails = getUserDetails($conn, $room_number);

        if ($userDetails) {
            $room_number = $userDetails['Room_number'];
            $first_name = $userDetails['First_name'];
            $last_name = $userDetails['Last_name'];
            $price = $userDetails['price'];
            $type_name = $userDetails['type_name'];
        }

        $last_month_electric = getLastMonthElectric($conn, $room_number);
        $last_month_water = getLastMonthWater($conn, $room_number);
    }

    if (isset($_POST['calculate'])) {
        $current_electric = isset($_POST['current_electric']) ? $_POST['current_electric'] : 0;
        $last_month_electric = isset($_POST['last_month_electric']) ? $_POST['last_month_electric'] : 0;
        $price = isset($_POST['price']) ? $_POST['price'] : 0;
    
        $difference_electric = $current_electric - $last_month_electric;
        $Electricity_total = $difference_electric * 7; // Calculate cost based on electricity usage
        $total = $Electricity_total + $price; // Total cost including room price
    
        if (!empty($room_number)) {
            saveElectricMeter($conn, $room_number, $current_electric);
        }
    
        if ($room_number == 'S1') {
            $current_water = isset($_POST['current_water']) ? $_POST['current_water'] : 0;
            $last_month_water = isset($_POST['last_month_water']) ? $_POST['last_month_water'] : 0;
    
            $difference_water = $current_water - $last_month_water;
            $Water_total = $difference_water * 22; // Calculate cost based on water usage
    
            if (!empty($room_number)) {
                saveWaterMeter($conn, $room_number, $current_water);
            }
    
            $total += $Water_total;
        }
    }

    if (isset($_POST['save'])) {
        $room_number = $_POST['room_number'];
        $current_electric = $_POST['current_electric'];
        $last_month_electric = $_POST['last_month_electric'];
        $difference_electric = $current_electric - $last_month_electric;
        $Electricity_total = $difference_electric * 7;
        $price = $_POST['price'];
        $total = $Electricity_total + $price;

        if ($room_number == 'S1') {
            $current_water = $_POST['current_water'];
            $last_month_water = $_POST['last_month_water'];

            $difference_water = $current_water - $last_month_water;
            $Water_total = $difference_water * 22; // Calculate cost based on water usage

            $total += $Water_total;
        }

        if (saveBill($conn, $room_number, $Electricity_total, $Water_total, $price, $total)) {
            echo "<script>alert('บันทึกข้อมูลเรียบร้อย');</script>";
        } else {
            echo "Error: ไม่สามารถบันทึกข้อมูลได้";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบคำนวณค่าใช้จ่ายห้องพัก</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="tl.css">
</head>
<body>
    <div class="container">
        <h1>ระบบคำนวณค่าใช้จ่ายห้องพัก</h1>
        <?php if (empty($room_number)) { ?>
        <div class="card">
            <h2>เลือกห้องพัก</h2>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                <div class="form-group">
                    <label for="room_number">หมายเลขห้อง:</label>
                    <select id="room_number" name="room_number" required>
                        <option value="">เลือกหมายเลขห้อง</option>
                        <?php if (!empty($roomNumbers)) {
                            while($row = $roomNumbers->fetch_assoc()) {
                                echo "<option value='" . $row['Room_number'] . "'>" . $row['Room_number'] . "</option>";
                            }
                        } ?>
                    </select>
                </div>
                <button type="submit" name="fetch">ยืนยัน</button>
            </form>
        </div>
        <?php } else { ?>
        <div class="card">
            <h2>ข้อมูลผู้ใช้</h2>
            <div class="info-grid">
                <div class="info-item">
                    <strong>หมายเลขห้อง:</strong> <?php echo htmlspecialchars($room_number); ?>
                </div>
                <div class="info-item">
                    <strong>ชื่อ-นามสกุล:</strong> <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>
                </div>
                <div class="info-item">
                    <strong>ประเภทห้อง:</strong> <?php echo htmlspecialchars($type_name); ?>
                </div>
                <div class="info-item">
                    <strong>ราคาห้อง:</strong> <?php echo htmlspecialchars($price); ?> บาท
                </div>
                <div class="info-item">
                    <strong>ยอดค่าน้ำประจำห้อง:</strong> <?php echo calculateWaterCost($room_number); ?> บาท
                </div>
            </div>
        </div>

        <div class="card">
            <h2>คำนวณค่าไฟฟ้าและค่าน้ำ</h2>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                <div class="form-group">
                    <label for="current_electric">เลขมิเตอร์ไฟฟ้าปัจจุบัน:</label>
                    <input type="number" id="current_electric" name="current_electric" required>
                </div>
                <div class="form-group">
                    <label for="last_month_electric">เลขมิเตอร์ไฟฟ้าครั้งก่อน:</label>
                    <input type="number" id="last_month_electric" value="<?php echo htmlspecialchars($last_month_electric); ?>" readonly>
                </div>
                <div class="form-group">
            <label for="unit_price">ราคาต่อหน่วย:</label>
            <input type="number" id="unit_price" value="7" readonly>
        </div>
                <?php if ($room_number == 'S1') { ?>
                <div class="form-group">
                    <label for="current_water">เลขมิเตอร์น้ำปัจจุบัน:</label>
                    <input type="number" id="current_water" name="current_water" required>
                </div>
                <div class="form-group">
                    <label for="last_month_water">เลขมิเตอร์น้ำครั้งก่อน:</label>
                    <input type="number" id="last_month_water" value="<?php echo htmlspecialchars($last_month_water); ?>" readonly>
                </div>
                <div class="form-group">
            <label for="unit_price_water">ราคาต่อหน่วยน้ำ:</label>
            <input type="number" id="unit_price_water" value="7" readonly>
        </div>
                <?php } ?>
                <input type="hidden" name="room_number" value="<?php echo htmlspecialchars($room_number); ?>">
                <input type="hidden" name="price" value="<?php echo htmlspecialchars($price); ?>">
                <button type="submit" name="calculate">คำนวณ</button>
            </form>
        </div>

        <?php if (isset($_POST['calculate'])) { ?>
        <div class="card">
            <h2>ผลการคำนวณ</h2>
            <table>
                <tr>
                    <th>รายการ</th>
                    <th>จำนวน</th>
                </tr>
                <tr>
                    <td>ผลต่างของเลขมิเตอร์ไฟฟ้า</td>
                    <td><?php echo htmlspecialchars($difference_electric); ?> หน่วย</td>
                </tr>
                <tr>
                    <td>ยอดค่าไฟ</td>
                    <td><?php echo htmlspecialchars($Electricity_total); ?> บาท</td>
                </tr>
                <?php if ($room_number == 'S1') { ?>
                <tr>
                    <td>ผลต่างของเลขมิเตอร์น้ำ</td>
                    <td><?php echo htmlspecialchars($difference_water); ?> หน่วย</td>
                </tr>
                <tr>
                    <td>ยอดค่าน้ำ</td>
                    <td><?php echo htmlspecialchars($Water_total); ?> บาท</td>
                </tr>
                <?php } ?>
                <tr>
                    <td>ค่าห้องพัก</td>
                    <td><?php echo htmlspecialchars($price); ?> บาท</td>
                </tr>
                <tr>
                    <td><strong>ค่าใช้จ่ายทั้งหมด</strong></td>
                    <td><strong><?php echo htmlspecialchars($total); ?> บาท</strong></td>
                </tr>
            </table>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" style="margin-top: 1rem;">
                <input type="hidden" name="room_number" value="<?php echo htmlspecialchars($room_number); ?>">
                <input type="hidden" name="current_electric" value="<?php echo htmlspecialchars($current_electric); ?>">
                <input type="hidden" name="last_month_electric" value="<?php echo htmlspecialchars($last_month_electric); ?>">
                <input type="hidden" name="price" value="<?php echo htmlspecialchars($price); ?>">
                <input type="hidden" name="Electricity_total" value="<?php echo htmlspecialchars($Electricity_total); ?>">
                <input type="hidden" name="total" value="<?php echo htmlspecialchars($total); ?>">
                
                <?php if ($room_number == 'S1') { ?>
                <input type="hidden" name="current_water" value="<?php echo htmlspecialchars($current_water); ?>">
                <input type="hidden" name="last_month_water" value="<?php echo htmlspecialchars($last_month_water); ?>">
                <?php } ?>
                <button type="submit" name="save">บันทึกข้อมูล</button>
            </form>
        </div>
        <?php } ?>
        <?php } ?>
    </div>
</body>
</html>