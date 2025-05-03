<?php

session_start();


require_once '../config/db_connect.php';
require_once '../config/functions.php';


redirect_if_not_logged_in();
redirect_if_not_role('admin');


if(isset($_POST['update_role']) && isset($_POST['user_id']) && isset($_POST['role'])) {
    $user_id = (int)$_POST['user_id'];
    $role = clean_data($_POST['role'], $conn);
 
    if($role == 'author' || $role == 'editor' || $role == 'admin') {
        $stmt = $conn->prepare("UPDATE user SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $role, $user_id);
        $stmt->execute();
        
       
        header("Location: /dashboard/admin.php");
        exit;
    }
}


if(isset($_POST['delete_user']) && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    
    
    if($user_id != $_SESSION['user_id']) {
       
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM news WHERE author_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if($row['count'] == 0) {
            
            $stmt = $conn->prepare("DELETE FROM user WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        } else {
            
            $_SESSION['admin_error'] = "لا يمكن حذف المستخدم لأن لديه أخبار منشورة.";
        }
        

        header("Location: /dashboard/admin.php");
        exit;
    }
}


$sql = "SELECT * FROM user ORDER BY role, name";
$result = $conn->query($sql);
$users = [];

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}


$news_stats = [
    'total' => 0,
    'approved' => 0,
    'draft' => 0,
    'denied' => 0
];

$sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
            SUM(CASE WHEN status = 'denied' THEN 1 ELSE 0 END) as denied
        FROM news";
$result = $conn->query($sql);

if($result->num_rows > 0) {
    $news_stats = $result->fetch_assoc();
}


include '../templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1>لوحة تحكم المدير</h1>
            
            <?php if(isset($_SESSION['admin_error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['admin_error'];
                unset($_SESSION['admin_error']);
                ?>
            </div>
            <?php endif; ?>
            

            <div class="row mt-4">
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">إجمالي الأخبار</h5>
                            <h2 class="card-text"><?php echo $news_stats['total']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">الأخبار المنشورة</h5>
                            <h2 class="card-text"><?php echo $news_stats['approved']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">قيد المراجعة</h5>
                            <h2 class="card-text"><?php echo $news_stats['draft']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-danger">
                        <div class="card-body">
                            <h5 class="card-title">الأخبار المرفوضة</h5>
                            <h2 class="card-text"><?php echo $news_stats['denied']; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            
         
            <div class="card mt-4">
                <div class="card-header">
                    <h3>إدارة المستخدمين</h3>
                </div>
                <div class="card-body">
                    <?php if(empty($users)): ?>
                    <div class="alert alert-info">لا يوجد مستخدمين.</div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>الاسم</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>الدور</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['name']; ?></td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td>
                                        <?php 
                                        switch($user['role']) {
                                            case 'admin':
                                                echo '<span class="badge bg-danger">مدير</span>';
                                                break;
                                            case 'editor':
                                                echo '<span class="badge bg-warning">محرر</span>';
                                                break;
                                            case 'author':
                                                echo '<span class="badge bg-info">كاتب</span>';
                                                break;
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if($user['id'] != $_SESSION['user_id']): ?>
                                        <form method="post" action="" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <select name="role" class="form-select form-select-sm d-inline w-auto">
                                                <option value="author" <?php echo ($user['role'] == 'author') ? 'selected' : ''; ?>>كاتب</option>
                                                <option value="editor" <?php echo ($user['role'] == 'editor') ? 'selected' : ''; ?>>محرر</option>
                                                <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>مدير</option>
                                            </select>
                                            <button type="submit" name="update_role" class="btn btn-sm btn-primary">تحديث الدور</button>
                                        </form>
                                        
                                        <form method="post" action="" class="d-inline ms-2">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')">حذف</button>
                                        </form>
                                        <?php else: ?>
                                        <span class="text-muted">أنت</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
           
            <div class="card mt-4">
                <div class="card-header">
                    <h3>إدارة الأخبار</h3>
                </div>
                <div class="card-body">
                    <a href="/news/manage.php" class="btn btn-primary">الذهاب إلى إدارة الأخبار</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>
