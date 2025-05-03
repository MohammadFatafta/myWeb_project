<?php

session_start();


require_once '../config/db_connect.php';
require_once '../config/functions.php';


if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: /index.php");
    exit;
}

$news_id = (int)$_GET['id'];


$news = get_news_by_id($news_id, $conn);


if(!$news || ($news['status'] != 'approved' && 
             (!is_logged_in() || 
              ($_SESSION['user_id'] != $news['author_id'] && 
               !has_role('admin') && 
               !has_role('editor'))))) {
    header("Location: /index.php");
    exit;
}


$related_news = get_news($conn, [
    'status' => 'approved',
    'category_id' => $news['category_id'],
    'limit' => 3
]);


$related_news = array_filter($related_news, function($item) use ($news_id) {
    return $item['id'] != $news_id;
});


include '../templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        
        <div class="col-12 mb-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/index.php">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="/news/category.php?id=<?php echo $news['category_id']; ?>"><?php echo $news['category_name']; ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo $news['title']; ?></li>
                </ol>
            </nav>
        </div>
        
       
        <div class="col-12 mb-4">
            <h1 class="article-title"><?php echo $news['title']; ?></h1>
            <div class="article-meta">
                <span class="article-date">
                    <i class="fas fa-calendar-alt"></i> <?php echo date('Y-m-d', strtotime($news['dateposted'])); ?>
                </span>
                <span class="article-author">
                    <i class="fas fa-user"></i> <?php echo $news['author_name']; ?>
                </span>
                <span class="article-category">
                    <i class="fas fa-folder"></i> <?php echo $news['category_name']; ?>
                </span>
            </div>
            
            <?php if($news['status'] != 'approved'): ?>
            <div class="alert alert-warning mt-2">
                هذا الخبر غير منشور بعد. <?php echo ($news['status'] == 'draft') ? 'قيد المراجعة' : 'مرفوض'; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-8">
            
            <?php if(!empty($news['image'])): ?>
            <div class="article-image mb-4">
                <img src="/uploads/<?php echo $news['image']; ?>" alt="<?php echo $news['title']; ?>" class="img-fluid rounded">
            </div>
            <?php endif; ?>
            
            
            <div class="article-content">
                <?php echo $news['body']; ?>
            </div>
            
           
            <div class="social-sharing mt-4">
                <h4>مشاركة الخبر</h4>
                <div class="social-buttons">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-facebook">
                        <i class="fab fa-facebook-f"></i> فيسبوك
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($news['title']); ?>" target="_blank" class="btn btn-twitter">
                        <i class="fab fa-twitter"></i> تويتر
                    </a>
                    <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($news['title'] . ' - http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-whatsapp">
                        <i class="fab fa-whatsapp"></i> واتساب
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
           
            <div class="card mb-4">
                <div class="card-header">
                    <h3>أخبار ذات صلة</h3>
                </div>
                <div class="card-body">
                    <?php if(empty($related_news)): ?>
                    <p class="text-muted">لا توجد أخبار ذات صلة</p>
                    <?php else: ?>
                    <ul class="list-unstyled">
                        <?php foreach($related_news as $related): ?>
                        <li class="media mb-3">
                            <?php if(!empty($related['image'])): ?>
                            <img src="/uploads/<?php echo $related['image']; ?>" alt="<?php echo $related['title']; ?>" class="mr-3 img-thumbnail" style="width: 80px;">
                            <?php endif; ?>
                            <div class="media-body">
                                <h5 class="mt-0">
                                    <a href="/news/details.php?id=<?php echo $related['id']; ?>"><?php echo $related['title']; ?></a>
                                </h5>
                                <small class="text-muted"><?php echo date('Y-m-d', strtotime($related['dateposted'])); ?></small>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
            
           
            <div class="card">
                <div class="card-header">
                    <h3>أحدث الأخبار</h3>
                </div>
                <div class="card-body">
                    <?php 
                    $latest_news = get_news($conn, [
                        'status' => 'approved',
                        'limit' => 5
                    ]);
                    
                    if(!empty($latest_news)):
                    ?>
                    <ul class="list-unstyled">
                        <?php foreach($latest_news as $latest): ?>
                        <li class="mb-2">
                            <a href="/news/details.php?id=<?php echo $latest['id']; ?>"><?php echo $latest['title']; ?></a>
                            <small class="text-muted d-block"><?php echo date('Y-m-d', strtotime($latest['dateposted'])); ?></small>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <p class="text-muted">لا توجد أخبار</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>
