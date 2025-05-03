<?php

session_start();


require_once '../config/db_connect.php';
require_once '../config/functions.php';


redirect_if_not_logged_in();
redirect_if_not_role('admin');


if(isset($_POST['delete_news']) && isset($_POST['news_id'])) {
    $news_id = (int)$_POST['news_id'];
    
    $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
    $stmt->bind_param("i", $news_id);
    
    if($stmt->execute()) {
        $_SESSION['success_message'] = "تم حذف الخبر بنجاح.";
    } else {
        $_SESSION['error_message'] = "حدث خطأ أثناء حذف الخبر.";
    }
    
    
    header("Location: /news/manage.php");
    exit;
}


if(isset($_POST['change_status']) && isset($_POST['news_id']) && isset($_POST['status'])) {
    $news_id = (int)$_POST['news_id'];
    $status = clean_data($_POST['status'], $conn);
    
    
    if($status == 'approved' || $status == 'draft' || $status == 'denied') {
        $stmt = $conn->prepare("UPDATE news SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $news_id);
        
        if($stmt->execute()) {
            $_SESSION['success_message'] = "تم تغيير حالة الخبر بنجاح.";
        } else {
            $_SESSION['error_message'] = "حدث خطأ أثناء تغيير حالة الخبر.";
        }
    }
    

    header("Location: /news/manage.php");
    exit;
}


$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$status = isset($_GET['status']) ? clean_data($_GET['status'], $conn) : '';


$sql = "SELECT n.*, c.name as category_name, u.name as author_name 
        FROM news n 
        JOIN category c ON n.category_id = c.id 
        JOIN user u ON n.author_id = u.id 
        WHERE 1=1";

$params = [];
$types = "";

if($category_id > 0) {
    $sql .= " AND n.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

if(!empty($status)) {
    $sql .= " AND n.status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY n.dateposted DESC";


$stmt = $conn->prepare($sql);

if(!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$news_items = [];

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $news_items[] = $row;
    }
}


$categories = get_categories($conn);


include '../templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>إدارة الأخبار</h1>
                <a href="/dashboard/admin.php" class="btn btn-secondary">العودة للوحة التحكم</a>
            </div>
            
            <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
            </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
            </div>
            <?php endif; ?>
            
           
            <div class="card mb-4">
                <div class="card-header">
                    <h3>تصفية الأخبار</h3>
                </div>
                <div class="card-body">
                    <form method="get" action="" class="row">
                        <div class="col-md-5">
                            <label for="category_id" class="form-label">القسم</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="0">جميع الأقسام</option>
                                <?php foreach($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($category_id == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo $category['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label for="status" class="form-label">الحالة</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">جميع الحالات</option>
                                <option value="approved" <?php echo ($status == 'approved') ? 'selected' : ''; ?>>تمت الموافقة</option>
                                <option value="draft" <?php echo ($status == 'draft') ? 'selected' : ''; ?>>قيد المراجعة</option>
                                <option value="denied" <?php echo ($status == 'denied') ? 'selected' : ''; ?>>مرفوض</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">تصفية</button>
                        </div>
                    </form>
                </div>
            </div>
            
           
            <div class="card">
                <div class="card-header">
                    <h3>قائمة الأخبار</h3>
                </div>
                <div class="card-body">
                    <?php if(empty($news_items)): ?>
                    <div class="alert alert-info">لا توجد أخبار تطابق معايير البحث.</div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>العنوان</th>
                                    <th>الكاتب</th>
                                    <th>القسم</th>
                                    <th>تاريخ النشر</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($news_items as $news): ?>
                                <tr>
                                    <td><?php echo $news['title']; ?></td>
                                    <td><?php echo $news['author_name']; ?></td>
                                    <td><?php echo $news['category_name']; ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($news['dateposted'])); ?></td>
                                    <td>
                                        <?php 
                                        switch($news['status']) {
                                            case 'draft':
                                                echo '<span class="badge bg-warning">قيد المراجعة</span>';
                                                break;
                                            case 'approved':
                                                echo '<span class="badge bg-success">تمت الموافقة</span>';
                                                break;
                                            case 'denied':
                                                echo '<span class="badge bg-danger">مرفوض</span>';
                                                break;
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="/news/details.php?id=<?php echo $news['id']; ?>" class="btn btn-sm btn-info">عرض</a>
                                            <a href="/news/edit.php?id=<?php echo $news['id']; ?>" class="btn btn-sm btn-primary mx-1">تعديل</a>
                                            
                                            
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="statusDropdown<?php echo $news['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                    تغيير الحالة
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="statusDropdown<?php echo $news['id']; ?>">
                                                    <li>
                                                        <form method="post" action="">
                                                            <input type="hidden" name="news_id" value="<?php echo $news['id']; ?>">
                                                            <input type="hidden" name="status" value="approved">
                                                            <button type="submit" name="change_status" class="dropdown-item">موافقة</button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="post" action="">
                                                            <input type="hidden" name="news_id" value="<?php echo $news['id']; ?>">
                                                            <input type="hidden" name="status" value="draft">
                                                            <button type="submit" name="change_status" class="dropdown-item">قيد المراجعة</button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="post" action="">
                                                            <input type="hidden" name="news_id" value="<?php echo $news['id']; ?>">
                                                            <input type="hidden" name="status" value="denied">
                                                            <button type="submit" name="change_status" class="dropdown-item">رفض</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                            
                                            
                                            <form method="post" action="" class="d-inline-block">
                                                <input type="hidden" name="news_id" value="<?php echo $news['id']; ?>">
                                                <button type="submit" name="delete_news" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذا الخبر؟')">حذف</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>
