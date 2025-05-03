<?php

session_start();


require_once '../config/db_connect.php';
require_once '../config/functions.php';


redirect_if_not_logged_in();
redirect_if_not_role('author');


$author_news = get_news($conn, [
    'author_id' => $_SESSION['user_id']
]);


include '../templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>لوحة تحكم الكاتب</h1>
                <a href="/news/add.php" class="btn btn-primary">إضافة خبر جديد</a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>أخباري</h3>
                </div>
                <div class="card-body">
                    <?php if(empty($author_news)): ?>
                    <div class="alert alert-info">لم تقم بإضافة أي أخبار بعد.</div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>العنوان</th>
                                    <th>القسم</th>
                                    <th>تاريخ النشر</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($author_news as $news): ?>
                                <tr>
                                    <td><?php echo $news['title']; ?></td>
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
                                        <a href="/news/edit.php?id=<?php echo $news['id']; ?>" class="btn btn-sm btn-primary">تعديل</a>
                                        <a href="/news/details.php?id=<?php echo $news['id']; ?>" class="btn btn-sm btn-info">عرض</a>
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
