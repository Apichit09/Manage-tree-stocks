<?php
session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_input = $_POST["login"];
    $password = $_POST["password"];
    $rememberMe = isset($_POST["rememberMe"]) ? true : false;

    try {
        $sql = "SELECT UserID, Username, Password, Phone FROM Users WHERE Username = :login OR Phone = :login";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':login', $login_input);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && $password == $result['Password']) {
            $_SESSION['user_id'] = $result['UserID'];

            if ($rememberMe) {
                $cookie_name = "remember_user";
                $cookie_value = $result['UserID'];
                $cookie_expire = time() + (86400 * 30); // 30 วัน
                setcookie($cookie_name, $cookie_value, $cookie_expire, "/");
            }

            header("Location: index.php");
            exit();
        } else {
            $error_message = "ชื่อผู้ใช้ เบอร์โทร หรือรหัสผ่านไม่ถูกต้อง";
        }
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ</title>
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
                                <i class="fas fa-home me-2"></i>หน้าแรก
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="add-tree.php">
                                <i class="fas fa-plus-circle me-2"></i>เพิ่มต้นไม้
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage-trees.php">
                                <i class="fas fa-seedling me-2"></i>จัดการต้นไม้
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="report-trees.php">
                                <i class="fas fa-chart-bar me-2"></i>รายงาน
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
                                <i class="fas fa-user me-2"></i>โปรไฟล์
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="record.php">
                                <i class="fa-solid fa-book me-2"></i>ประวัติการทำรายการ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-success" href="register.php">
                                <i class="fas fa-sign-in-alt me-2"></i>สมัครสมาชิค
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <button class="btn menu-btn d-md-none" type="button" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="container mt-5">
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h2 class="text-center mb-4">เข้าสู่ระบบ</h2>
                                    <?php if (isset($error_message)): ?>
                                        <div class="alert alert-danger" role="alert">
                                            <?php echo $error_message; ?>
                                        </div>
                                    <?php endif; ?>
                                    <form action="login.php" method="POST">
                                        <div class="mb-3">
                                            <label for="login" class="form-label">ชื่อผู้ใช้หรือเบอร์โทรศัพท์</label>
                                            <input type="text" class="form-control" id="login" name="login" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="password" class="form-label">รหัสผ่าน</label>
                                            <input type="password" class="form-control" id="password" name="password"
                                                required>
                                        </div>
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="rememberMe"
                                                name="rememberMe">
                                            <label class="form-check-label" for="rememberMe">จดจำฉัน</label>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100">เข้าสู่ระบบ</button>
                                    </form>
                                    <p class="mt-3 text-center">ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a>
                                    </p>
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