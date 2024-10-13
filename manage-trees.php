<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

require_once 'db.php';

function getTrees($user_id)
{
    global $conn;
    try {
        $sql = "SELECT t.*, tt.TreeTypeName 
                FROM Trees t
                LEFT JOIN TreeTypes tt ON t.TreeType = tt.TreeTypeID
                WHERE t.UserID = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    } catch (PDOException $e) {
        return array("error" => "Error: " . $e->getMessage());
    }
}

$trees = getTrees($user_id);

$sql_user = "SELECT Username FROM Users WHERE UserID = :user_id";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bindParam(':user_id', $user_id);
$stmt_user->execute();
$result_user = $stmt_user->fetch(PDO::FETCH_ASSOC);

if ($result_user) {
    $username = $result_user['Username'];
} else {
    $username = "ไม่พบชื่อผู้ใช้";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["updateDeadTrees"])) {
    $treeId = $_POST["treeId"];
    $deadTrees = $_POST["deadTrees"];

    try {
        $conn->beginTransaction();

        $sql_get_tree = "SELECT QuantityInStock, TotalDeaths FROM Trees WHERE TreeID = :treeId AND UserID = :userId";
        $stmt_get_tree = $conn->prepare($sql_get_tree);
        $stmt_get_tree->bindParam(':treeId', $treeId);
        $stmt_get_tree->bindParam(':userId', $user_id);
        $stmt_get_tree->execute();
        $tree = $stmt_get_tree->fetch(PDO::FETCH_ASSOC);

        if ($tree) {
            $newQuantity = $tree['QuantityInStock'] - $deadTrees;
            $newTotalDeaths = $tree['TotalDeaths'] + $deadTrees;

            $sql_update = "UPDATE Trees 
                           SET TotalDeaths = :newTotalDeaths, 
                               QuantityInStock = :newQuantity 
                           WHERE TreeID = :treeId AND UserID = :userId";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bindParam(':newTotalDeaths', $newTotalDeaths);
            $stmt_update->bindParam(':newQuantity', $newQuantity);
            $stmt_update->bindParam(':treeId', $treeId);
            $stmt_update->bindParam(':userId', $user_id);
            $stmt_update->execute();

            $conn->commit();
            $_SESSION['success_message'] = "อัพเดตข้อมูลต้นไม้ที่ตายเรียบร้อยแล้ว";
        } else {
            throw new Exception("ไม่พบข้อมูลต้นไม้");
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

    header("Location: manage-trees.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["sellTree"])) {
    $treeId = $_POST["treeId"];
    $quantity = $_POST["quantity"];
    $price = $_POST["price"];
    $customerName = $_POST["customerName"];
    $phone = $_POST["phone"];

    try {
        $conn->beginTransaction();

        $sql_get_tree = "SELECT QuantityInStock FROM Trees WHERE TreeID = :treeId AND UserID = :userId";
        $stmt_get_tree = $conn->prepare($sql_get_tree);
        $stmt_get_tree->bindParam(':treeId', $treeId);
        $stmt_get_tree->bindParam(':userId', $user_id);
        $stmt_get_tree->execute();
        $tree = $stmt_get_tree->fetch(PDO::FETCH_ASSOC);

        if ($tree) {
            $newQuantity = $tree['QuantityInStock'] - $quantity;

            if ($newQuantity >= 0) {
                $sql_update_stock = "UPDATE Trees 
                                     SET QuantityInStock = :newQuantity
                                     WHERE TreeID = :treeId AND UserID = :userId";
                $stmt_update_stock = $conn->prepare($sql_update_stock);
                $stmt_update_stock->bindParam(':newQuantity', $newQuantity);
                $stmt_update_stock->bindParam(':treeId', $treeId);
                $stmt_update_stock->bindParam(':userId', $user_id);
                $stmt_update_stock->execute();

                $sql_insert_sale = "INSERT INTO Sales (TreeID, Quantity, Price, SaleDate, CustomerName, Phone, UserID) 
                                    VALUES (:treeId, :quantity, :price, NOW(), :customerName, :phone, :userId)";
                $stmt_insert_sale = $conn->prepare($sql_insert_sale);
                $stmt_insert_sale->bindParam(':treeId', $treeId);
                $stmt_insert_sale->bindParam(':quantity', $quantity);
                $stmt_insert_sale->bindParam(':price', $price);
                $stmt_insert_sale->bindParam(':customerName', $customerName);
                $stmt_insert_sale->bindParam(':phone', $phone);
                $stmt_insert_sale->bindParam(':userId', $user_id);
                $stmt_insert_sale->execute();

                $conn->commit();
                $_SESSION['success_message'] = "บันทึกการขายต้นไม้เรียบร้อยแล้ว";
            } else {
                throw new Exception("จำนวนต้นไม้ในสต็อกไม่เพียงพอ");
            }
        } else {
            throw new Exception("ไม่พบข้อมูลต้นไม้");
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

    header("Location: manage-trees.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["deleteTree"])) {
    $treeId = $_POST["treeId"];

    try {
        $conn->beginTransaction();

        $sql_delete_sales = "DELETE FROM sales WHERE TreeID = :treeId";
        $stmt_delete_sales = $conn->prepare($sql_delete_sales);
        $stmt_delete_sales->bindParam(':treeId', $treeId);
        $stmt_delete_sales->execute();

        $sql_delete_tree = "DELETE FROM trees WHERE TreeID = :treeId AND UserID = :userId";
        $stmt_delete_tree = $conn->prepare($sql_delete_tree);
        $stmt_delete_tree->bindParam(':treeId', $treeId);
        $stmt_delete_tree->bindParam(':userId', $user_id);
        $stmt_delete_tree->execute();

        if ($stmt_delete_tree->rowCount() > 0) {
            $conn->commit();
            $_SESSION['success_message'] = "ลบต้นไม้และข้อมูลที่เกี่ยวข้องเรียบร้อยแล้ว";
        } else {
            throw new Exception("ไม่พบต้นไม้ที่ต้องการลบ");
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

    header("Location: manage-trees.php");
    exit();
}

function getSoldQuantity($treeId)
{
    global $conn;
    $sql = "SELECT SUM(Quantity) as TotalSold FROM Sales WHERE TreeID = :treeId";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':treeId', $treeId);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['TotalSold'] ? $result['TotalSold'] : 0;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการต้นไม้</title>
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
                            <a class="nav-link" href="add-tree.php">
                                <i class="fas fa-plus-circle me-2"></i> เพิ่มต้นไม้
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="manage-trees.php">
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
                    <h1 class="h2">จัดการต้นไม้</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="input-group">
                            <input type="text" class="form-control search-input" placeholder="ค้นหา...">
                            <button class="btn search-btn" type="button"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">จัดการข้อมูลต้นไม้</h5>
                                <?php if (isset($_SESSION['success_message'])): ?>
                                    <div class="alert alert-success" role="alert">
                                        <?php
                                        echo $_SESSION['success_message'];
                                        unset($_SESSION['success_message']);
                                        ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($_SESSION['error_message'])): ?>
                                    <div class="alert alert-danger" role="alert">
                                        <?php
                                        echo $_SESSION['error_message'];
                                        unset($_SESSION['error_message']);
                                        ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($trees)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>ชื่อต้นไม้</th>
                                                    <th>รูปภาพ</th>
                                                    <th>ประเภท</th>
                                                    <th>ขนาด</th>
                                                    <th>ราคาทุน</th>
                                                    <th>ราคาขาย</th>
                                                    <th>จำนวนคงเหลือ</th>
                                                    <th>จำนวนที่ตาย</th>
                                                    <th>จำนวนที่ขาย</th>
                                                    <th>การดำเนินการ</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($trees as $tree): ?>
                                                    <tr>
                                                        <td><?php echo $tree['TreeName']; ?></td>
                                                        <td>
                                                            <?php if (!empty($tree['Image'])): ?>
                                                                <img src="<?php echo $tree['Image']; ?>"
                                                                    alt="<?php echo $tree['TreeName']; ?>" class="img-thumbnail"
                                                                    style="max-width: 100px;">
                                                            <?php else: ?>
                                                                <span class="text-muted">ไม่มีรูปภาพ</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo $tree['TreeTypeName']; ?></td>
                                                        <td><?php echo $tree['TreeSize']; ?></td>
                                                        <td><?php echo number_format($tree['CostPrice'], 2); ?> บาท</td>
                                                        <td><?php echo number_format($tree['SellingPrice'], 2); ?> บาท</td>
                                                        <td><?php echo $tree['QuantityInStock']; ?></td>
                                                        <td><?php echo $tree['TotalDeaths']; ?></td>
                                                        <td><?php echo getSoldQuantity($tree['TreeID']); ?></td>
                                                        <td>
                                                            <a href="tree-detail.php?id=<?php echo $tree['TreeID']; ?>"
                                                                class="btn btn-info btn-sm">รายละเอียด</a>
                                                            <button type="button" class="btn btn-primary btn-sm edit-btn"
                                                                data-bs-toggle="modal" data-bs-target="#editModal"
                                                                data-tree-id="<?php echo $tree['TreeID']; ?>">
                                                                แก้ไข
                                                            </button>
                                                            <button type="button" class="btn btn-danger btn-sm delete-btn"
                                                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                                data-tree-id="<?php echo $tree['TreeID']; ?>">
                                                                ลบ
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">ไม่พบข้อมูลต้นไม้</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">แก้ไขข้อมูลต้นไม้</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="dead-tab" data-bs-toggle="tab" data-bs-target="#dead"
                                type="button" role="tab" aria-controls="dead" aria-selected="true">ต้นไม้ที่ตาย</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="sell-tab" data-bs-toggle="tab" data-bs-target="#sell"
                                type="button" role="tab" aria-controls="sell" aria-selected="false">ขายต้นไม้</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="dead" role="tabpanel" aria-labelledby="dead-tab">
                            <form method="POST" action="manage-trees.php">
                                <input type="hidden" name="treeId" id="editTreeId">
                                <div class="mb-3">
                                    <label for="deadTrees" class="form-label">จำนวนต้นไม้ที่ตาย</label>
                                    <input type="number" class="form-control" id="deadTrees" name="deadTrees" required>
                                </div>
                                <button type="submit" name="updateDeadTrees" class="btn btn-primary">บันทึก</button>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="sell" role="tabpanel" aria-labelledby="sell-tab">
                            <form method="POST" action="manage-trees.php">
                                <input type="hidden" name="treeId" id="sellTreeId">
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">จำนวนที่ขาย (ต้น)</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" required>
                                </div>
                                <div class="mb-3">
                                    <label for="price" class="form-label">ราคาขาย (ต่อ 1 ต้น)</label>
                                    <input type="number" step="0.01" class="form-control" id="price" name="price"
                                        required>
                                </div>
                                <div class="mb-3">
                                    <label for="customerName" class="form-label">ชื่อลูกค้า</label>
                                    <input type="text" class="form-control" id="customerName" name="customerName"
                                        required>
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">เบอร์โทรศัพท์</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required>
                                </div>
                                <button type="submit" name="sellTree" class="btn btn-primary">บันทึกการขาย</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">ยืนยันการลบ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    คุณแน่ใจหรือไม่ว่าต้องการลบต้นไม้นี้?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <form method="POST" action="manage-trees.php">
                        <input type="hidden" name="treeId" id="deleteTreeId">
                        <button type="submit" name="deleteTree" class="btn btn-danger">ลบ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/script.js"></script>
</body>

</html>