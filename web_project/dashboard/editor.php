<?php

session_start();


require_once '../config/db_connect.php';
require_once '../config/functions.php';


redirect_if_not_logged_in();
redirect_if_not_role('editor');


if(isset($_POST['action']) && isset($_POST['news_id'])) {
    $news_id = (int)$_POST['news_id'];
    $action = clean_data($_POST['action'], $conn);
    
    if($action == 'approve' || $action == 'deny') {
        $status = ($action == 'approve') ? 'approved' : 'denied';
        
        $stmt = $conn->prepare("UPDATE news SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $news_id);
        $stmt->execute();
        
       
        header("Location: /dashboard/editor.php");
        exit;
    }
}


$sql = "SELECT n.*, c.name as category_name, u.name as author_name 
        FROM news n 
        JOIN category c ON n.category_id = c.id 
        JOIN user u ON n.author_id = u.id 
        ORDER BY 
            CASE 
                WHEN n.status = 'draft' THEN 1
                WHEN n.status = 'approved' THEN 2
                WHEN n.status = 'denied' THEN 3
            END,
            n.dateposted DESC";

$result = $conn->query($sql);
$news_items = [];

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $news_items[] = $row;
    }
}


include '../templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1>لوحة تحكم المحرر</h1>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h3>مراجعة الأخبار</h3>
                </div>
                <div class="card-body">
                    <?php if(empty($news_items)): ?>
                    <div class="alert alert-info">لا توجد أخبار للمراجعة.</div>
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
                                            
                                            <?php if($news['status'] == 'draft'): ?>
                                            <form method="post" action="">
                                                <input type="hidden" name="news_id" value="<?php echo $news['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-sm btn-success mx-1">موافقة</button>
                                            </form>
                                            
                                            <form method="post" action="">
                                                <input type="hidden" name="news_id" value="<?php echo $news['id']; ?>">
                                                <input type="hidden" name="action" value="deny">
                                                <button type="submit" class="btn btn-sm btn-danger">رفض</button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <?php if($news['status'] == 'denied'): ?>
                                            <form method="post" action="">
                                                <input type="hidden" name="news_id" value="<?php echo $news['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-sm btn-success mx-1">موافقة</button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <?php if($news['status'] == 'approved'): ?>
                                            <form method="post" action="">
                                                <input type="hidden" name="news_id" value="<?php echo $news['id']; ?>">
                                                <input type="hidden" name="action" value="deny">
                                                <button type="submit" class="btn btn-sm btn-danger mx-1">رفض</button>
                                            </form>
                                            <?php endif; ?>
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
