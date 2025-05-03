<?php

session_start();


require_once 'config/db_connect.php';
require_once 'config/functions.php';


$top_news = get_news($conn, [
    'status' => 'approved',
    'limit' => 5
]);


$categories = get_categories($conn);


$news_by_category = [];
foreach ($categories as $category) {
    $news_by_category[$category['id']] = get_news($conn, [
        'status' => 'approved',
        'category_id' => $category['id'],
        'limit' => 5
    ]);
}


include 'templates/header.php';
?>

<main class="container">
  
    <section class="top-news">
        <div class="row">
            <?php if(!empty($top_news) && isset($top_news[0])): ?>

            <div class="col-md-8">
                <div class="featured-news">
                    <a href="/news/details.php?id=<?php echo $top_news[0]['id']; ?>">
                        <?php if(!empty($top_news[0]['image'])): ?>
                        <div class="news-image">
                            <img src="/uploads/<?php echo $top_news[0]['image']; ?>" alt="<?php echo $top_news[0]['title']; ?>" class="img-fluid">
                        </div>
                        <?php endif; ?>
                        <div class="news-content">
                            <h2><?php echo $top_news[0]['title']; ?></h2>
                            <p><?php echo substr(strip_tags($top_news[0]['body']), 0, 200) . '...'; ?></p>
                            <span class="more-link">المزيد</span>
                        </div>
                    </a>
                </div>
            </div>
            <?php endif; ?>

           
            <div class="col-md-4">
                <div class="secondary-news">
                    <?php
                    
                    for($i = 1; $i < min(3, count($top_news)); $i++):
                    ?>
                    <div class="news-item">
                        <a href="/news/details.php?id=<?php echo $top_news[$i]['id']; ?>">
                            <?php if(!empty($top_news[$i]['image'])): ?>
                            <div class="news-image">
                                <img src="/uploads/<?php echo $top_news[$i]['image']; ?>" alt="<?php echo $top_news[$i]['title']; ?>" class="img-fluid">
                            </div>
                            <?php endif; ?>
                            <div class="news-content">
                                <h3><?php echo $top_news[$i]['title']; ?></h3>
                                <span class="more-link">المزيد</span>
                            </div>
                        </a>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        
        <div class="row secondary-featured">
            <?php
            
            for($i = 3; $i < count($top_news); $i++):
            ?>
            <div class="col-md-4">
                <div class="news-item">
                    <a href="/news/details.php?id=<?php echo $top_news[$i]['id']; ?>">
                        <?php if(!empty($top_news[$i]['image'])): ?>
                        <div class="news-image">
                            <img src="/uploads/<?php echo $top_news[$i]['image']; ?>" alt="<?php echo $top_news[$i]['title']; ?>" class="img-fluid">
                        </div>
                        <?php endif; ?>
                        <div class="news-content">
                            <h3><?php echo $top_news[$i]['title']; ?></h3>
                            <span class="more-link">المزيد</span>
                        </div>
                    </a>
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </section>

    
    <?php foreach($categories as $category): ?>
    <section class="category-section">
        <div class="section-header">
            <h2><?php echo $category['name']; ?></h2>
            <a href="/news/category.php?id=<?php echo $category['id']; ?>" class="view-all">عرض الكل</a>
        </div>
        
        <div class="row">
            <?php 
            if(isset($news_by_category[$category['id']]) && !empty($news_by_category[$category['id']])):
                foreach($news_by_category[$category['id']] as $news_item):
            ?>
            <div class="col-md-4">
                <div class="news-item">
                    <a href="/news/details.php?id=<?php echo $news_item['id']; ?>">
                        <?php if(!empty($news_item['image'])): ?>
                        <div class="news-image">
                            <img src="/uploads/<?php echo $news_item['image']; ?>" alt="<?php echo $news_item['title']; ?>" class="img-fluid">
                        </div>
                        <?php endif; ?>
                        <div class="news-content">
                            <h3><?php echo $news_item['title']; ?></h3>
                            <p><?php echo substr(strip_tags($news_item['body']), 0, 100) . '...'; ?></p>
                            <span class="more-link">المزيد</span>
                        </div>
                    </a>
                </div>
            </div>
            <?php 
                endforeach;
            else:
            ?>
            <div class="col-12">
                <p class="no-news">لا توجد أخبار في هذا القسم حالياً</p>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endforeach; ?>
</main>

<?php include 'templates/footer.php'; ?>
