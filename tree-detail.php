<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: manage-trees.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$tree_id = $_GET['id'];

$sql_tree = "SELECT t.*, tt.TreeTypeName 
             FROM Trees t
             LEFT JOIN TreeTypes tt ON t.TreeType = tt.TreeTypeID
             WHERE t.TreeID = :tree_id AND t.UserID = :user_id";
$stmt_tree = $conn->prepare($sql_tree);
$stmt_tree->bindParam(':tree_id', $tree_id);
$stmt_tree->bindParam(':user_id', $user_id);
$stmt_tree->execute();
$tree = $stmt_tree->fetch(PDO::FETCH_ASSOC);

if (!$tree) {
    header("Location: manage-trees.php");
    exit();
}

$sql_sales = "SELECT * FROM Sales WHERE TreeID = :tree_id ORDER BY SaleDate DESC";
$stmt_sales = $conn->prepare($sql_sales);
$stmt_sales->bindParam(':tree_id', $tree_id);
$stmt_sales->execute();
$sales = $stmt_sales->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดต้นไม้</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="card-title text-center mb-4">รายละเอียดต้นไม้: <?php echo $tree['TreeName']; ?></h1>
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-info text-white">
                                <h2 class="h5 mb-0"><i class="bi bi-image"></i> รูปภาพ</h2>
                            </div>
                            <div class="card-body d-flex align-items-center justify-content-center">
                                <?php if (!empty($tree['Image'])): ?>
                                    <img src="<?php echo $tree['Image']; ?>" alt="<?php echo $tree['TreeName']; ?>" class="img-fluid rounded">
                                <?php else: ?>
                                    <p class="text-muted">ไม่มีรูปภาพ</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h2 class="h5 mb-0"><i class="bi bi-tree"></i> ข้อมูลต้นไม้</h2>
                            </div>
                            <div class="card-body">
                                <table class="table table-striped">
                                    <tr><th>ชื่อ</th><td><?php echo $tree['TreeName']; ?></td></tr>
                                    <tr><th>ขนาด</th><td><?php echo $tree['TreeSize']; ?></td></tr>
                                    <tr><th>ประเภท</th><td><?php echo $tree['TreeTypeName']; ?></td></tr>
                                    <tr><th>ราคาทุน</th><td><?php echo number_format($tree['CostPrice'], 2); ?> บาท</td></tr>
                                    <tr><th>ราคาขาย</th><td><?php echo number_format($tree['SellingPrice'], 2); ?> บาท</td></tr>
                                    <tr><th>จำนวนคงเหลือ</th><td><?php echo $tree['QuantityInStock']; ?></td></tr>
                                    <tr><th>วันที่รับเข้า</th><td><?php echo date('d/m/Y', strtotime($tree['DateReceived'])); ?></td></tr>
                                    <tr><th>แหล่งที่มา</th><td><?php echo $tree['Source']; ?></td></tr>
                                    <tr><th>หมายเหตุ</th><td><?php echo $tree['Notes']; ?></td></tr>
                                    <tr><th>จำนวนที่ตาย</th><td><?php echo $tree['TotalDeaths']; ?></td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white">
                                <h2 class="h5 mb-0"><i class="bi bi-cash-coin"></i> ประวัติการขาย</h2>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($sales)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>รหัสการขาย</th>
                                                    <th>จำนวน</th>
                                                    <th>ราคา</th>
                                                    <th>วันที่ขาย</th>
                                                    <th>ชื่อลูกค้า</th>
                                                    <th>เบอร์โทร</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($sales as $sale): ?>
                                                    <tr>
                                                        <td><?php echo $sale['SaleID']; ?></td>
                                                        <td><?php echo $sale['Quantity']; ?></td>
                                                        <td><?php echo number_format($sale['Price'], 2); ?> บาท</td>
                                                        <td><?php echo date('d/m/Y', strtotime($sale['SaleDate'])); ?></td>
                                                        <td><?php echo $sale['CustomerName']; ?></td>
                                                        <td><?php echo $sale['Phone']; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">ยังไม่มีประวัติการขาย</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <a href="manage-trees.php" class="btn btn-primary"><i class="bi bi-arrow-left"></i> กลับไปหน้าจัดการต้นไม้</a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>