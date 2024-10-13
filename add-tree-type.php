<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["addTreeType"])) {
    $treeTypeName = $_POST["treeTypeName"];
    $description = $_POST["description"];

    try {
        $sql = "INSERT INTO TreeTypes (TreeTypeName, Description, UserID) VALUES (:treeTypeName, :description, :user_id)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':treeTypeName', $treeTypeName);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        $success_message = "เพิ่มประเภทต้นไม้สำเร็จแล้ว";
    } catch (PDOException $e) {
        $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

try {
    $sql_tree_types = "SELECT * FROM TreeTypes WHERE UserID = :user_id";
    $stmt_tree_types = $conn->prepare($sql_tree_types);
    $stmt_tree_types->bindParam(':user_id', $user_id);
    $stmt_tree_types->execute();
    $tree_types = $stmt_tree_types->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}

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


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มประเภทต้นไม้</title>
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
                            <a class="nav-link active" href="add-tree-type.php">
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
                    <h1 class="h2">เพิ่มประเภทต้นไม้</h1>
                </div>

                <div class="container mt-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">เพิ่มประเภทต้นไม้</h5>
                            <?php if (isset($success_message)): ?>
                                <div class="alert alert-success" role="alert">
                                    <?php echo $success_message; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($error_message)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>
                            <form action="add-tree-type.php" method="POST">
                                <div class="mb-3">
                                    <label for="treeTypeName" class="form-label">ชื่อประเภทต้นไม้</label>
                                    <input type="text" class="form-control" id="treeTypeName" name="treeTypeName"
                                        required>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">คำอธิบาย</label>
                                    <textarea class="form-control" id="description" name="description"></textarea>
                                </div>
                                <button type="submit" name="addTreeType" class="btn btn-primary">เพิ่ม</button>
                            </form>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="card-title">ประเภทต้นไม้</h5>
                            <?php if (!empty($tree_types)): ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>รหัสประเภท</th>
                                                <th>ชื่อประเภท</th>
                                                <th>คำอธิบาย</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($tree_types as $type): ?>
                                                <tr>
                                                    <td><?php echo $type['TreeTypeID']; ?></td>
                                                    <td><?php echo $type['TreeTypeName']; ?></td>
                                                    <td><?php echo $type['Description']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p>ยังไม่มีข้อมูลประเภทต้นไม้</p>
                            <?php endif; ?>
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