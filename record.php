<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

require_once 'db.php';

function getAdditionHistory($user_id, $limit = 10)
{
    global $conn;
    try {
        $sql = "SELECT t.TreeID, t.TreeName, t.DateReceived, tt.TreeTypeName, t.QuantityInStock, t.SellingPrice 
                FROM Trees t
                JOIN TreeTypes tt ON t.TreeType = tt.TreeTypeID
                WHERE t.UserID = :user_id 
                ORDER BY t.DateReceived DESC 
                LIMIT :limit";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getAdditionHistory: " . $e->getMessage());
        return array();
    }
}

function getSalesHistory($user_id, $limit = 10)
{
    global $conn;
    try {
        $sql = "SELECT s.SaleID, t.TreeName, s.Quantity, s.Price, s.SaleDate, s.CustomerName
                FROM Sales s
                JOIN Trees t ON s.TreeID = t.TreeID
                WHERE s.UserID = :user_id 
                ORDER BY s.SaleDate DESC 
                LIMIT :limit";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getSalesHistory: " . $e->getMessage());
        return array();
    }
}



$additionHistory = getAdditionHistory($user_id);
$salesHistory = getSalesHistory($user_id);


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการทำรายการ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/style.css">
</head>

<body>
    <divฬ class="container-fluid">
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
                            <a class="nav-link" href="add-tree.php">
                                <i class="fas fa-plus-circle me-2"></i> เพิ่มต้นไม้
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
                            <a class="nav-link active" href="record.php">
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

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">ประวัติการทำรายการ</h1>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="card-title mb-0"><i class="fas fa-plus-circle me-2"></i>ประวัติการเพิ่มข้อมูล
                                </h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($additionHistory as $addition): ?>
                                        <li class="list-group-item">
                                            <strong><?php echo htmlspecialchars($addition['TreeName']); ?></strong><br>
                                            <small class="text-muted">เพิ่มเมื่อ:
                                                <?php echo htmlspecialchars($addition['DateReceived']); ?></small><br>
                                            <small>ประเภท:
                                                <?php echo htmlspecialchars($addition['TreeTypeName']); ?></small><br>
                                            <small>จำนวน:
                                                <?php echo htmlspecialchars($addition['QuantityInStock']); ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0"><i class="fas fa-shopping-cart me-2"></i>ประวัติการขาย</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($salesHistory as $sale): ?>
                                        <li class="list-group-item">
                                            <strong><?php echo htmlspecialchars($sale['TreeName']); ?></strong><br>
                                            <small class="text-muted">ขายเมื่อ:
                                                <?php echo htmlspecialchars($sale['SaleDate']); ?></small><br>
                                            <small>จำนวน: <?php echo htmlspecialchars($sale['Quantity']); ?></small><br>
                                            <small>ราคา: <?php echo htmlspecialchars($sale['Price']); ?> บาท</small><br>
                                            <small>ลูกค้า: <?php echo htmlspecialchars($sale['CustomerName']); ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        </div>
        <script src="JS/script.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>