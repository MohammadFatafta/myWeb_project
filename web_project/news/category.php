<?php

session_start();


require_once '../config/db_connect.php';
require_once '../config/functions.php';


if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: /index.php");
    exit;
}

$category_id = (int)$_GET['id'];


$category = get_category_by_id($category_id, $conn);


if(!$category) {
    header("Location: /index.php");
    exit;
}


$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); 
$offset = ($page - 1) * $per_page;


$query = "SELECT COUNT(*) as total FROM news WHERE category_id = $1 AND status = 'approved'";
$count_result = pg_query_params($conn, $query, [$category_id]);
$row = pg_fetch_assoc($count_result);
$total_news = (int)$row['total'];
$total_pages = ceil($total_news / $per_page);


$query = "SELECT n.*, u.name as author_name 
         FROM news n 
         JOIN \"user\" u ON n.author_id = u.id 
         WHERE n.category_id = $1 AND n.status = 'approved' 
         ORDER BY n.dateposted DESC 
         LIMIT $2 OFFSET $3";
$result = pg_query_params($conn, $query, [$category_id, $per_page, $offset]);

$news_items = [];
if(pg_num_rows($result) > 0) {
    while($row = pg_fetch_assoc($result)) {
        $news_items[] = $row;
    }
}


include '../templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/index.php">الرئيسية</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo $category['name']; ?></li>
                </ol>
            </nav>
            
            <h1 class="category-title mb-4"><?php echo $category['name']; ?></h1>
            
            <?php if(!empty($category['description'])): ?>
            <p class="category-description mb-4"><?php echo $category['description']; ?></p>
            <?php endif; ?>
            
            <?php if(empty($news_items)): ?>
            <div class="alert alert-info">لا توجد أخبار في هذا القسم حالياً</div>
            <?php else: ?>
            
            <div class="row">
                <?php foreach($news_items as $news): ?>
                <div class="col-md-6 mb-4">
                    <div class="card news-card">
                        <div class="row g-0">
                            <?php if(!empty($news['image'])): ?>
                            <div class="col-md-4">
                                <img src="/uploads/<?php echo $news['image']; ?>" alt="<?php echo $news['title']; ?>" class="img-fluid rounded-start h-100" style="object-fit: cover;">
                            </div>
                            <div class="col-md-8">
                            <?php else: ?>
                            <div class="col-md-12">
                            <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="/news/details.php?id=<?php echo $news['id']; ?>"><?php echo $news['title']; ?></a>
                                    </h5>
                                    <p class="card-text"><?php echo substr(strip_tags($news['body']), 0, 100) . '...'; ?></p>
                                    <p class="card-text">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-alt"></i> <?php echo date('Y-m-d', strtotime($news['dateposted'])); ?>
                                            <span class="mx-2">|</span>
                                            <i class="fas fa-user"></i> <?php echo $news['author_name']; ?>
                                        </small>
                                    </p>
                                    <a href="/news/details.php?id=<?php echo $news['id']; ?>" class="btn btn-sm btn-primary">المزيد</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
           
            <?php if($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?id=<?php echo $category_id; ?>&page=<?php echo ($page - 1); ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for($i = max(1, $page - 2); $i <= min($page + 2, $total_pages); $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?id=<?php echo $category_id; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?id=<?php echo $category_id; ?>&page=<?php echo ($page + 1); ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
            
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>
