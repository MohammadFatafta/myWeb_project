<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_connect.php';


function clean_data($data, $conn = null) {
    if (!$conn) {
        global $conn;
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = mysqli_escape_string($conn, $data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}


function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function has_role($role) {
    return is_logged_in() && $_SESSION['role'] == $role;
}

function redirect_if_not_logged_in() {
    if (!is_logged_in()) {
        header("Location: /auth/login.php");
        exit;
    }
}

function redirect_if_not_role($role) {
    if (!has_role($role)) {
        header("Location: /index.php");
        exit;
    }
}


function get_user_by_id($user_id, $conn) {
    $query = "SELECT id, name, email, role FROM \"user\" WHERE id = $1";
    $result = mysqli_query_params($conn, $query, [$user_id]);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return false;
}


function get_news($conn, $options = []) {
    $sql = "SELECT n.*, c.name as category_name, u.name as author_name 
            FROM news n 
            JOIN category c ON n.category_id = c.id 
            JOIN \"user\" u ON n.author_id = u.id";

    $conditions = [];
    $params = [];
    $paramCount = 1;

    if (isset($options['status'])) {
        $conditions[] = "n.status = $" . $paramCount;
        $params[] = $options['status'];
        $paramCount++;
    }

    if (isset($options['category_id'])) {
        $conditions[] = "n.category_id = $" . $paramCount;
        $params[] = $options['category_id'];
        $paramCount++;
    }

    if (isset($options['author_id'])) {
        $conditions[] = "n.author_id = $" . $paramCount;
        $params[] = $options['author_id'];
        $paramCount++;
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY n.dateposted DESC";

    if (isset($options['limit']) && is_numeric($options['limit'])) {
        $sql .= " LIMIT " . intval($options['limit']);
    }

    $result = mysqli_query_params($conn, $sql, $params);
    $news = [];

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $news[] = $row;
        }
    }

    return $news;
}


function get_news_by_id($news_id, $conn) {
    $query = "SELECT n.*, c.name as category_name, u.name as author_name 
              FROM news n 
              JOIN category c ON n.category_id = c.id 
              JOIN \"user\" u ON n.author_id = u.id 
              WHERE n.id = $1";
    $result = mysqli_query_params($conn, $query, [$news_id]);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return false;
}


function get_categories($conn) {
    $query = "SELECT * FROM category ORDER BY name";
    $result = mysqli_query($conn, $query);
    $categories = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
    }
    return $categories;
}


function get_category_by_id($category_id, $conn) {
    $query = "SELECT * FROM category WHERE id = $1";
    $result = mysqli_query_params($conn, $query, [$category_id]);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return false;
}


function upload_image($file) {
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';

    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $allowed_exts = ['jmysqli', 'jpeg', 'png', 'gif'];
    $max_file_size = 2 * 1024 * 1024; 

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (
        !in_array($file['type'], $allowed_types) ||
        !in_array($ext, $allowed_exts) ||
        $file['size'] > $max_file_size
    ) {
        return false;
    }

    $filename = uniqid() . '_' . basename($file['name']);
    $target_file = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $filename;
    }

    return false;
}
?>
