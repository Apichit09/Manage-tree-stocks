<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

require_once 'db.php';

function getUserInfo($user_id)
{
    global $conn;
    try {
        $sql = "SELECT UserID, Username, Phone, Password, Plan FROM Users WHERE UserID = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return null;
    }
}

function updateUserInfo($user_id, $phone, $password = null)
{
    global $conn;
    try {
        $sql = "UPDATE Users SET Phone = :phone";
        $params = [':phone' => $phone, ':user_id' => $user_id];

        if ($password !== null) {
            $sql .= ", Password = :password";
            $params[':password'] = $password;
        }

        $sql .= " WHERE UserID = :user_id";

        $stmt = $conn->prepare($sql);
        $result = $stmt->execute($params);

        if (!$result) {
            error_log("Error updating user info: " . print_r($stmt->errorInfo(), true));
        }

        return $result;
    } catch (PDOException $e) {
        error_log("Exception in updateUserInfo: " . $e->getMessage());
        return false;
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

        $sql_count = "SELECT COUNT(TreeID) FROM Trees WHERE UserID = :user_id";
        $stmt_count = $conn->prepare($sql_count);
        $stmt_count->bindParam(':user_id', $user_id);
        $stmt_count->execute();
        $tree_count = $stmt_count->fetchColumn();

        error_log("User ID: {$user_id}, Plan: {$user_plan}, Tree Count: {$tree_count}");

        if ($user_plan === 'Free' && $tree_count >= 15) {
            error_log("User cannot add more data");
            return false;
        }
        error_log("User can add more data");
        return true;
    } catch (PDOException $e) {
        error_log("Error in canAddMoreData: " . $e->getMessage());
        return false;
    }
}

$user_info = getUserInfo($user_id);


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = $_POST['phone'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $plan = $_POST['plan'];

    $error_occurred = false;

    if ($current_password && $current_password !== $user_info['Password']) {
        $error_message = "รหัสผ่านปัจจุบันไม่ถูกต้อง";
        $error_occurred = true;
    } elseif ($new_password && $new_password !== $confirm_password) {
        $error_message = "รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน";
        $error_occurred = true;
    } else {
        $password_to_update = $new_password ?: null;
        $update_result = updateUserInfo($user_id, $phone, $password_to_update);
        if (!$update_result) {
            $error_message = "เกิดข้อผิดพลาดในการอัปเดตข้อมูลผู้ใช้";
            $error_occurred = true;
        }
    }

    if (!$error_occurred) {
        $success_message = "ข้อมูลถูกอัปเดตเรียบร้อยแล้ว";
        $user_info = getUserInfo($user_id);

        if ($plan === 'Monthly' || $plan === 'Yearly') {
            $contact_message = "กรุณาติดต่อเราทาง Line: @yourtreeline เพื่อเปิดใช้งานแผน " . ($plan === 'Monthly' ? "รายเดือน" : "รายปี");
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ผู้ใช้</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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
                            <a class="nav-link active" href="profile.php">
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
                    <h1 class="h2">โปรไฟล์ผู้ใช้</h1>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row mb-3">
                                <label for="username" class="col-sm-3 col-form-label">ชื่อผู้ใช้</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control-plaintext" id="username"
                                        value="<?php echo $user_info['Username']; ?>" readonly>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="phone" class="col-sm-3 col-form-label">เบอร์โทรศัพท์</label>
                                <div class="col-sm-9">
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                        value="<?php echo $user_info['Phone']; ?>" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="current_password" class="col-sm-3 col-form-label">รหัสผ่านปัจจุบัน</label>
                                <div class="col-sm-9 password-container">
                                    <input type="password" class="form-control" id="current_password"
                                        name="current_password">
                                    <i class="fas fa-eye password-toggle"
                                        onclick="togglePassword('current_password')"></i>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="new_password" class="col-sm-3 col-form-label">รหัสผ่านใหม่</label>
                                <div class="col-sm-9 password-container">
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                    <i class="fas fa-eye password-toggle" onclick="togglePassword('new_password')"></i>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="confirm_password" class="col-sm-3 col-form-label">ยืนยันรหัสผ่านใหม่</label>
                                <div class="col-sm-9 password-container">
                                    <input type="password" class="form-control" id="confirm_password"
                                        name="confirm_password">
                                    <i class="fas fa-eye password-toggle"
                                        onclick="togglePassword('confirm_password')"></i>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label">แผนการใช้งานปัจจุบัน</label>
                                <div class="col-sm-9">
                                    <p class="form-control-plaintext">
                                        <?php
                                        switch ($user_info['Plan']) {
                                            case 'Free':
                                                echo "ฟรี (จำกัดการใช้งาน 15 ข้อมูล)";
                                                break;
                                            case 'Monthly':
                                                echo "รายเดือน ราคา 129 บาท (ไม่จำกัดการใช้งาน)";
                                                break;
                                            case 'Yearly':
                                                echo "รายปี ราคา 1190 บาท (ไม่จำกัดการใช้งาน)";
                                                break;
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-9 offset-sm-3">
                                    <button type="button" class="btn btn-primary"
                                        onclick="showUpgradePlanMessage()">อัพเกรดแผนการใช้งาน</button>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-sm-9 offset-sm-3">
                                    <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                                </div>
                            </div>
                        </form>
                        </form>
                        <?php if (isset($contact_message)): ?>
                            <div class="alert alert-info mt-3">
                                <?php echo $contact_message; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
            <script>
                function togglePassword(inputId) {
                    var passwordInput = document.getElementById(inputId);
                    var icon = document.querySelector(`[onclick="togglePassword('${inputId}')"]`);

                    if (passwordInput.type === "password") {
                        passwordInput.type = "text";
                        icon.classList.remove("fa-eye");
                        icon.classList.add("fa-eye-slash");
                    } else {
                        passwordInput.type = "password";
                        icon.classList.remove("fa-eye-slash");
                        icon.classList.add("fa-eye");
                    }
                }

                document.addEventListener('DOMContentLoaded', function () {
                    var toggleButtons = document.querySelectorAll('.password-toggle');
                    toggleButtons.forEach(function (button) {
                        button.addEventListener('click', function () {
                            var inputId = this.getAttribute('data-input');
                            togglePassword(inputId);
                        });
                    });
                });
            </script>
            <script>
                function showUpgradePlanMessage() {
                    alert("หากต้องการอัพเกรดแผนการใช้งาน กรุณาติดต่อเราทาง Line: @yourtreeline");
                }
            </script>
            <script src="JS/script.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>