<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

require_once 'db.php';

function getTrees($user_id, $limit = 10)
{
    global $conn;
    try {
        $sql = "SELECT t.*, tt.TreeTypeName 
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
        return array("error" => "Error: " . $e->getMessage());
    }
}

function getUserInfo($user_id)
{
    global $conn;
    try {
        $sql = "SELECT Username FROM Users WHERE UserID = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['Username'] : "ไม่พบชื่อผู้ใช้";
    } catch (PDOException $e) {
        return "Error: " . $e->getMessage();
    }
}

function getStatCardData($user_id)
{
    global $conn;
    try {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM Trees WHERE UserID = :user_id AND MONTH(DateReceived) = MONTH(CURDATE())) as new_trees_this_month,
                    (SELECT COUNT(*) FROM Trees WHERE UserID = :user_id) as total_trees,
                    (SELECT SUM(TotalDeaths) FROM Trees WHERE UserID = :user_id) as total_deaths,
                    (SELECT SUM(QuantityInStock * SellingPrice) FROM Trees WHERE UserID = :user_id) as total_stock_value";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return ["error" => "Error: " . $e->getMessage()];
    }
}

function getNotifications($user_id)
{
    global $conn;
    try {
        $sql = "SELECT 
                    (SELECT MAX(DateReceived) FROM Trees WHERE UserID = :user_id) as last_added_date,
                    (SELECT MAX(SaleDate) FROM Sales WHERE UserID = :user_id) as last_sale_date,
                    (SELECT COUNT(*) FROM Trees WHERE UserID = :user_id AND QuantityInStock < 5) as low_stock_count";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return ["error" => "Error: " . $e->getMessage()];
    }
}

function getChartData($user_id)
{
    global $conn;
    try {
        $sql = "SELECT 
                    MONTH(DateReceived) as month, 
                    COUNT(*) as tree_count
                FROM Trees 
                WHERE UserID = :user_id AND YEAR(DateReceived) = YEAR(CURDATE())
                GROUP BY MONTH(DateReceived)
                ORDER BY MONTH(DateReceived)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $chartData = array_fill(1, 12, 0);
        foreach ($result as $row) {
            $chartData[$row['month']] = $row['tree_count'];
        }
        return $chartData;
    } catch (PDOException $e) {
        return ["error" => "Error: " . $e->getMessage()];
    }
}

$username = getUserInfo($user_id);
$trees = getTrees($user_id);
$statCardData = getStatCardData($user_id);
$notifications = getNotifications($user_id);
$chartData = getChartData($user_id);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสต๊อกต้นไม้</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse" id="sidebarMenu">
                <div class="position-sticky">
                    <h5 class="mb-3 ps-3"><i class="fa-solid fa-tree me-2"></i>จัดการสต๊อกต้นไม้</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php">
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                    <h1 class="h2 text-white">สวัสดี คุณ <?php echo $username; ?></h1>
                </div>
                <div class="row">
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">ต้นไม้ที่เพิ่มเดือนนี้</h6>
                                <h2 class="card-title"><?php echo $statCardData['new_trees_this_month']; ?></h2>
                                <i class="fas fa-plus-circle stat-icon text-primary"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">ต้นไม้ทั้งหมด</h6>
                                <h2 class="card-title"><?php echo $statCardData['total_trees']; ?></h2>
                                <i class="fas fa-tree stat-icon text-success"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">ต้นไม้ที่ตาย</h6>
                                <h2 class="card-title text-danger"><?php echo $statCardData['total_deaths']; ?></h2>
                                <i class="fas fa-exclamation-triangle stat-icon text-danger"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">มูลค่าสต็อกรวม</h6>
                                <h2 class="card-title">
                                    <?php echo number_format($statCardData['total_stock_value'], 2); ?> บาท
                                </h2>
                                <i class="fas fa-dollar-sign stat-icon text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">จำนวนต้นไม้ที่เพิ่มรายเดือน</h5>
                                <canvas id="treeChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">แจ้งเตือน</h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">เพิ่มต้นไม้ล่าสุด:
                                        <?php echo $notifications['last_added_date']; ?>
                                    </li>
                                    <li class="list-group-item">ขายล่าสุด:
                                        <?php echo $notifications['last_sale_date']; ?>
                                    </li>
                                    <li class="list-group-item">ต้นไม้ใกล้หมดสต็อก:
                                        <?php echo $notifications['low_stock_count']; ?> รายการ
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">ข้อมูลต้นไม้ล่าสุด</h5>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>ชื่อต้นไม้</th>
                                                <th>ประเภทต้นไม้</th>
                                                <th>ราคาขาย</th>
                                                <th>จำนวนคงเหลือ</th>
                                                <th>วันที่รับเข้า</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($trees as $tree): ?>
                                                <tr>
                                                    <td><?php echo $tree['TreeName']; ?></td>
                                                    <td><?php echo $tree['TreeTypeName']; ?></td>
                                                    <td><?php echo number_format($tree['SellingPrice'], 2); ?> บาท</td>
                                                    <td><?php echo $tree['QuantityInStock']; ?></td>
                                                    <td><?php echo $tree['DateReceived']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
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

    <script>
        const ctx = document.getElementById('treeChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'],
                datasets: [{
                    label: 'จำนวนต้นไม้ที่เพิ่ม',
                    data: <?php echo json_encode(array_values($chartData)); ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgb(75, 192, 192)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'จำนวนต้นไม้'
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'จำนวนต้นไม้ที่เพิ่มรายเดือนในปีนี้'
                    }
                }
            }
        });
    </script>
</body>

</html>