<?php

session_start();


require_once '../config/db_connect.php';
require_once '../config/functions.php';


redirect_if_not_logged_in();
if(!has_role('author') && !has_role('admin')) {
    header("Location: /index.php");
    exit;
}


$categories = get_categories($conn);

$error = '';
$success = '';


if(isset($_POST['submit'])) {
   
    $title = clean_data($_POST['title'], $conn);
    $body = $_POST['body']; 
    $category_id = (int)$_POST['category_id'];
    
    
    if(empty($title)) {
        $error = "عنوان الخبر مطلوب";
    }
    
    
    if(empty($body)) {
        $error = "محتوى الخبر مطلوب";
    }
    
    
    if($category_id <= 0) {
        $error = "يجب اختيار قسم";
    }
    
   
    $image_filename = null;
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_filename = upload_image($_FILES['image']);
        
        if(!$image_filename) {
            $error = "حدث خطأ أثناء رفع الصورة. يرجى التأكد من أن الصورة بتنسيق مدعوم (JPG, PNG, GIF).";
        }
    }
    
    
    if(empty($error)) {
        
        $status = 'draft';
        
        
        $author_id = $_SESSION['user_id'];
        
       
        $stmt = $conn->prepare("INSERT INTO news (title, body, image, category_id, author_id, status, dateposted) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssiis", $title, $body, $image_filename, $category_id, $author_id, $status);
        
        if($stmt->execute()) {
            $success = "تم إضافة الخبر بنجاح! سيتم مراجعته من قبل المحرر قبل النشر.";
           
            $title = '';
            $body = '';
            $category_id = 0;
        } else {
            $error = "حدث خطأ أثناء إضافة الخبر. الرجاء المحاولة مرة أخرى.";
        }
    }
}


include '../templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>إضافة خبر جديد</h1>
                <a href="<?php echo has_role('admin') ? '/dashboard/admin.php' : '/dashboard/author.php'; ?>" class="btn btn-secondary">العودة للوحة التحكم</a>
            </div>
            
            <?php if(!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="post" action="" enctype="multipart/form-data">
                        <div class="form-group mb-3">
                            <label for="title">عنوان الخبر</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo isset($title) ? $title : ''; ?>" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="category_id">القسم</label>
                            <select class="form-control" id="category_id" name="category_id" required>
                                <option value="">-- اختر القسم --</option>
                                <?php foreach($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo (isset($category_id) && $category_id == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo $category['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="image">صورة الخبر</label>
                            <input type="file" class="form-control" id="image" name="image">
                            <small class="form-text text-muted">الصورة اختيارية. الأنواع المدعومة: JPG, PNG, GIF.</small>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="body">محتوى الخبر</label>
                            <textarea class="form-control" id="body" name="body" rows="10" required><?php echo isset($body) ? $body : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="submit" class="btn btn-primary">إضافة الخبر</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    
    document.addEventListener('DOMContentLoaded', function() {
        
    });
</script>

<?php include '../templates/footer.php'; ?>
