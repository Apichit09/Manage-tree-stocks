<?php
session_start();

// ตรวจสอบว่าผู้ใช้ได้ล็อกอินหรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ดึง user_id จาก session
$user_id = $_SESSION['user_id'];

require_once 'db.php'; // Include the database connection file

// เพิ่มฟังก์ชันสำหรับ debug
function debug_to_console($data)
{
    echo "<script>console.log(" . json_encode($data) . ");</script>";
}

// ฟังก์ชันสำหรับเพิ่มข้อมูลต้นไม้
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $treeName = $_POST["treeName"];
    $treeSize = $_POST["treeSize"];
    $treeType = $_POST["treeType"];
    $costPrice = $_POST["costPrice"];
    $sellingPrice = $_POST["sellingPrice"];
    $quantityInStock = $_POST["quantityInStock"];
    $source = $_POST["source"];
    $notes = $_POST["notes"];

    $target_file = null;
    $uploadOk = 1;
    $error_message = "";

    // ตรวจสอบว่ามีการอัปโหลดรูปภาพหรือไม่
    if (!empty($_FILES["image"]["name"])) {
        $base_dir = "uploads/";
        $target_dir = $base_dir . $user_id . "/";

        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // ตรวจสอบว่าเป็นไฟล์รูปภาพหรือไม่
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check === false) {
            $error_message .= "ไฟล์ที่อัพโหลดไม่ใช่รูปภาพ<br>";
            $uploadOk = 0;
        }

        // ตรวจสอบขนาดไฟล์ (5MB)
        if ($_FILES["image"]["size"] > 5000000) {
            $error_message .= "ขออภัย ไฟล์ของคุณมีขนาดใหญ่เกินไป<br>";
            $uploadOk = 0;
        }

        // อนุญาตเฉพาะไฟล์ JPG, JPEG, PNG & GIF
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            $error_message .= "ขออภัย อนุญาตเฉพาะไฟล์ JPG, JPEG, PNG & GIF เท่านั้น<br>";
            $uploadOk = 0;
        }
    }

    if ($uploadOk == 1) {
        try {
            $conn->beginTransaction();

            // ถ้ามีการอัปโหลดรูปภาพ
            if ($target_file !== null) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    // ปรับขนาดรูปภาพ
                    list($width, $height) = getimagesize($target_file);
                    $new_width = 300; // ปรับขนาดความกว้างเป็น 300px
                    $new_height = $height * ($new_width / $width);

                    $image_p = imagecreatetruecolor($new_width, $new_height);

                    switch ($imageFileType) {
                        case 'jpeg':
                        case 'jpg':
                            $image = imagecreatefromjpeg($target_file);
                            break;
                        case 'png':
                            $image = imagecreatefrompng($target_file);
                            break;
                        case 'gif':
                            $image = imagecreatefromgif($target_file);
                            break;
                        default:
                            throw new Exception("ไม่รองรับประเภทไฟล์นี้");
                    }

                    imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

                    switch ($imageFileType) {
                        case 'jpeg':
                        case 'jpg':
                            imagejpeg($image_p, $target_file, 75);
                            break;
                        case 'png':
                            imagepng($image_p, $target_file, 7);
                            break;
                        case 'gif':
                            imagegif($image_p, $target_file);
                            break;
                    }
                } else {
                    throw new Exception("ไม่สามารถอัปโหลดไฟล์ได้");
                }
            }

            // บันทึกข้อมูลลงในฐานข้อมูล
            $sql = "INSERT INTO Trees (TreeName, TreeSize, TreeType, CostPrice, SellingPrice, QuantityInStock, DateReceived, Source, Notes, Image, UserID) 
                    VALUES (:treeName, :treeSize, :treeType, :costPrice, :sellingPrice, :quantityInStock, CURDATE(), :source, :notes, :image, :user_id)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':treeName', $treeName);
            $stmt->bindParam(':treeSize', $treeSize);
            $stmt->bindParam(':treeType', $treeType);
            $stmt->bindParam(':costPrice', $costPrice);
            $stmt->bindParam(':sellingPrice', $sellingPrice);
            $stmt->bindParam(':quantityInStock', $quantityInStock);
            $stmt->bindParam(':source', $source);
            $stmt->bindParam(':notes', $notes);
            $stmt->bindParam(':image', $target_file);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();

            $conn->commit();
            $_SESSION['success_message'] = "บันทึกข้อมูลสำเร็จแล้ว";
            header("Location: manage-trees.php");
            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            $error_message .= "Error: " . $e->getMessage();
            debug_to_console($error_message);
        }
    }
}

try {
    $sql_tree_types = "SELECT * FROM TreeTypes WHERE UserID = :user_id";
    $stmt_tree_types = $conn->prepare($sql_tree_types);
    $stmt_tree_types->bindParam(':user_id', $user_id);
    $stmt_tree_types->execute();
    $tree_types = $stmt_tree_types->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message .= "Error: " . $e->getMessage();
    debug_to_console($error_message);
}

try {
    $sql_user = "SELECT Username FROM Users WHERE UserID = :user_id";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bindParam(':user_id', $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    $username = $result_user ? $result_user['Username'] : "ไม่พบชื่อผู้ใช้";
} catch (PDOException $e) {
    $error_message .= "Error: " . $e->getMessage();
    debug_to_console($error_message);
}

function getLatestTrees($user_id)
{
    global $conn;
    try {
        $sql = "SELECT 
                    t.TreeName,
                    t.TreeSize,
                    tt.TreeTypeName,
                    t.CostPrice,
                    t.SellingPrice,
                    t.QuantityInStock
                FROM Trees t
                JOIN TreeTypes tt ON t.TreeType = tt.TreeTypeID
                WHERE t.UserID = :user_id 
                ORDER BY t.DateReceived DESC 
                LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        debug_to_console("Error: " . $e->getMessage());
        return array();
    }
}
function canAddMoreData($user_id)
{
    global $conn;
    try {
        $sql_plan = "SELECT Plan FROM Users WHERE UserID = :user_id";
        $stmt_plan = $conn->prepare($sql_plan);
        $stmt_plan->bindParam(':user_id', $user_id);
        $stmt_plan->execute();
        $user_plan = $stmt_plan->fetchColumn();

        if ($user_plan === 'Monthly' || $user_plan === 'Yearly') {
            return true;
        }

        $sql_count = "SELECT COUNT(TreeID) FROM Trees WHERE UserID = :user_id";
        $stmt_count = $conn->prepare($sql_count);
        $stmt_count->bindParam(':user_id', $user_id);
        $stmt_count->execute();
        $tree_count = $stmt_count->fetchColumn();

        if ($user_plan === 'Free' && $tree_count >= 15) {
            return false;
        }
        return true;
    } catch (PDOException $e) {
        error_log("Error in canAddMoreData: " . $e->getMessage());
        return false;
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("POST request received in add-tree.php");
    error_log("User ID: " . $user_id);

    if (!canAddMoreData($user_id)) {
        $error_message = "คุณไม่สามารถเพิ่มข้อมูลได้อีก เนื่องจากคุณใช้แผนฟรีและมีข้อมูลครบ 15 รายการแล้ว กรุณาติดต่อเราทาง Line: @yourtreeline เพื่ออัพเกรดแผนการใช้งาน";
    } else {
    }
}
function getUserPlan($user_id)
{
    global $conn;
    $stmt = $conn->prepare("SELECT Plan FROM Users WHERE UserID = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    return $stmt->fetchColumn();
}

function getTreeCount($user_id)
{
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Trees WHERE UserID = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    return $stmt->fetchColumn();
}

$latestTrees = getLatestTrees($user_id);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มต้นไม้</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/style.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse" id="sidebarMenu">
                <div class="position-sticky">
                    <h5 class="mb-3 ps-3"><i class="fa-solid fa-tree me-2"></i>จัดการสต๊อกต้นไม้</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-home me-2"></i> หน้าแรก
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="add-tree.php">
                                <i class="fas fa-plus-circle me-2"></i> เพิ่มต้นไม้
                            </a>
                            <a class="nav-link" href="add-tree-type.php">
                                <i class="fas fa-plus-square me-2"></i> เพิ่มประเภทต้นไม้
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage-trees.php">
                                <i class="fas fa-seedling me-2"></i> จัดการต้นไม้
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="report-trees.php">
                                <i class="fas fa-chart-bar me-2"></i> รายงาน
                            </a>
                        </li>
                    </ul>

                    <h6
                        class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Account pages</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user me-2"></i> โปรไฟล์
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="record.php">
                                <i class="fa-solid fa-book me-2"></i> ประวัติการทำรายการ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="fas fa-sign-in-alt me-2"></i> ออกจากระบบ
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <button class="btn menu-btn d-md-none" type="button" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">เพิ่มต้นไม้</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="input-group">
                            <input type="text" class="form-control search-input" placeholder="Search...">
                            <button class="btn search-btn" type="button"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </div>

                <?php
                $user_plan = getUserPlan($user_id);
                $tree_count = getTreeCount($user_id);
                $can_add_more = ($user_plan !== 'Free' || $tree_count < 15);
                ?>

                <div class="container mt-4">
                    <?php if ($user_plan === 'Free' && $tree_count >= 15): ?>
                        <div class="alert alert-warning" role="alert">
                            คุณไม่สามารถเพิ่มข้อมูลได้อีก เนื่องจากคุณใช้แผนฟรีและมีข้อมูลครบ 15 รายการแล้ว
                            กรุณาติดต่อเราทาง Line: @yourtreeline เพื่ออัพเกรดแผนการใช้งาน
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($can_add_more): ?>
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">เพิ่มข้อมูลต้นไม้</h5>
                                <form action="add-tree.php" method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="treeName" class="form-label">ชื่อต้นไม้</label>
                                        <input type="text" class="form-control" id="treeName" name="treeName" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="treeSize" class="form-label">ขนาดต้นไม้</label>
                                        <input type="text" class="form-control" id="treeSize" name="treeSize" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="treeType" class="form-label">ประเภทต้นไม้ (สามารถเพิ่มได้จาก
                                            เมนู)</label>
                                        <select class="form-select" id="treeType" name="treeType" required>
                                            <option value="">-- เลือกประเภทต้นไม้ --</option>
                                            <?php foreach ($tree_types as $type): ?>
                                                <option value="<?php echo $type['TreeTypeID']; ?>">
                                                    <?php echo $type['TreeTypeName']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="costPrice" class="form-label">ราคาต้นทุน</label>
                                        <input type="number" class="form-control" id="costPrice" name="costPrice" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="sellingPrice" class="form-label">ราคาขาย</label>
                                        <input type="number" class="form-control" id="sellingPrice" name="sellingPrice"
                                            required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="quantityInStock" class="form-label">จำนวนคงเหลือ</label>
                                        <input type="number" class="form-control" id="quantityInStock"
                                            name="quantityInStock" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="source" class="form-label">แหล่งที่มา</label>
                                        <input type="text" class="form-control" id="source" name="source">
                                    </div>
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">หมายเหตุ</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="image" class="form-label">รูปภาพ</label>
                                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                    </div>
                                    <button type="submit" class="btn btn-primary">เพิ่มต้นไม้</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">รายการล่าสุด</h5>
                                <?php if (!empty($latestTrees)): ?>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>ชื่อต้นไม้</th>
                                                    <th>ขนาดต้นไม้</th>
                                                    <th>ประเภทต้นไม้</th>
                                                    <th>ราคาต้นทุน</th>
                                                    <th>ราคาขาย</th>
                                                    <th>จำนวนคงเหลือ</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($latestTrees as $tree): ?>
                                                    <tr>
                                                        <td><?php echo $tree['TreeName']; ?></td>
                                                        <td><?php echo $tree['TreeSize']; ?></td>
                                                        <td><?php echo $tree['TreeTypeName']; ?></td>
                                                        <td><?php echo $tree['CostPrice']; ?></td>
                                                        <td><?php echo $tree['SellingPrice']; ?></td>
                                                        <td><?php echo $tree['QuantityInStock']; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p>ยังไม่มีข้อมูลต้นไม้</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
        </main>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/script.js"></script>
</body>

</html>