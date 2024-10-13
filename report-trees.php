<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
require_once 'db.php';

function getSalesData($user_id)
{
    global $conn;
    try {
        $sql = "SELECT s.*, t.TreeName FROM Sales s 
                JOIN Trees t ON s.TreeID = t.TreeID 
                WHERE s.UserID = :user_id 
                ORDER BY s.SaleDate DESC LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

function getDeathsData($user_id)
{
    global $conn;
    try {
        $sql = "SELECT t.TreeName, t.TotalDeaths, t.DateReceived as DeathDate 
                FROM Trees t 
                WHERE t.UserID = :user_id AND t.TotalDeaths > 0
                ORDER BY t.DateReceived DESC LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

function getSummaryData($user_id)
{
    global $conn;
    try {
        $summary = [];
        $sql = "SELECT 
                    COALESCE(SUM(Price * Quantity), 0) as total_sales,
                    COALESCE(SUM(Quantity), 0) as total_trees_sold
                FROM Sales 
                WHERE UserID = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $summary['total_sales'] = $result['total_sales'];
        $summary['total_trees_sold'] = $result['total_trees_sold'];

        $sql = "SELECT COALESCE(SUM(TotalDeaths), 0) as total_deaths 
                FROM Trees 
                WHERE UserID = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $summary['total_deaths'] = $stmt->fetchColumn();

        return $summary;
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return ['total_sales' => 0, 'total_trees_sold' => 0, 'total_deaths' => 0];
    }
}

function getMonthlyComparisonData($user_id)
{
    global $conn;
    try {
        $sql = "SELECT 
                    months.month,
                    COALESCE(SUM(s.Quantity), 0) as trees_sold,
                    COALESCE(SUM(t.TotalDeaths), 0) as trees_died
                FROM 
                    (SELECT DATE_FORMAT(CURDATE() - INTERVAL n MONTH, '%Y-%m') as month
                     FROM (SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11) numbers) as months
                LEFT JOIN Sales s ON DATE_FORMAT(s.SaleDate, '%Y-%m') = months.month AND s.UserID = :user_id
                LEFT JOIN Trees t ON DATE_FORMAT(t.DateReceived, '%Y-%m') = months.month AND t.UserID = :user_id
                GROUP BY months.month
                ORDER BY months.month";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

$salesData = getSalesData($user_id);
$deathsData = getDeathsData($user_id);
$summaryData = getSummaryData($user_id);
$monthlyComparisonData = getMonthlyComparisonData($user_id);

try {
    $sql_user = "SELECT Username FROM Users WHERE UserID = :user_id";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bindParam(':user_id', $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->fetch(PDO::FETCH_ASSOC);
    $username = $result_user ? $result_user['Username'] : "ไม่พบชื่อผู้ใช้";
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $username = "ไม่พบชื่อผู้ใช้";
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานการขายและต้นไม้ตาย</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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
                            <a class="nav-link active" href="report-trees.php">
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

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">รายงานการขายและต้นไม้ตาย</h1>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-header">ยอดขายรวม</div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo number_format($summaryData['total_sales'], 2); ?> บาท
                                </h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-header">จำนวนต้นไม้ที่ขายได้</div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo number_format($summaryData['total_trees_sold']); ?>
                                    ต้น</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-danger mb-3">
                            <div class="card-header">จำนวนต้นไม้ที่ตาย</div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo number_format($summaryData['total_deaths']); ?> ต้น
                                </h5>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">เปรียบเทียบต้นไม้ที่ขายได้กับต้นไม้ที่ตาย (12 เดือนล่าสุด)</h5>
                        <canvas id="treeComparisonChart"></canvas>
                    </div>
                </div>


                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                รายงานการขายล่าสุด
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th>ชื่อต้นไม้</th>
                                                <th>จำนวน</th>
                                                <th>ราคา</th>
                                                <th>วันที่ขาย</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($salesData as $sale): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($sale['TreeName']); ?></td>
                                                    <td><?php echo htmlspecialchars($sale['Quantity']); ?></td>
                                                    <td><?php echo number_format($sale['Price'], 2); ?> บาท</td>
                                                    <td><?php echo htmlspecialchars($sale['SaleDate']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                รายงานต้นไม้ตายล่าสุด
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th>ชื่อต้นไม้</th>
                                                <th>จำนวนที่ตาย</th>
                                                <th>วันที่บันทึก</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($deathsData as $death): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($death['TreeName']); ?></td>
                                                    <td><?php echo htmlspecialchars($death['TotalDeaths']); ?></td>
                                                    <td><?php echo htmlspecialchars($death['DeathDate']); ?></td>
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

    <script src="JS/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('treeComparisonChart').getContext('2d');
            const comparisonData = <?php echo json_encode($monthlyComparisonData); ?>;

            if (comparisonData && comparisonData.length > 0) {
                const months = comparisonData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleString('th-TH', { month: 'short' });
                });
                const treesSold = comparisonData.map(item => parseInt(item.trees_sold) || 0);
                const treesDied = comparisonData.map(item => parseInt(item.trees_died) || 0);

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: months,
                        datasets: [
                            {
                                label: 'ต้นไม้ที่ขายได้',
                                data: treesSold,
                                borderColor: 'rgb(75, 192, 192)',
                                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                tension: 0.1,
                                fill: true
                            },
                            {
                                label: 'ต้นไม้ที่ตาย',
                                data: treesDied,
                                borderColor: 'rgb(255, 99, 132)',
                                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                                tension: 0.1,
                                fill: true
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            x: {
                                title: {
                                    display: true,
                                    text: 'เดือน'
                                }
                            },
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
                                text: 'เปรียบเทียบต้นไม้ที่ขายได้กับต้นไม้ที่ตาย (12 เดือนล่าสุด)'
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false
                            }
                        }
                    }
                });
            } else {
                document.getElementById('treeComparisonChart').innerHTML = '<p class="text-center">ไม่มีข้อมูลสำหรับการเปรียบเทียบ</p>';
            }
        });
    </script>
</body>

</html>