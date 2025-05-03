<?php

$host = getenv('PGHOST');
$port = getenv('PGPORT');
$dbname = getenv('PGDATABASE');
$username = getenv('PGUSER');
$password = getenv('PGPASSWORD');


$connection_string = "host=$host port=$port dbname=$dbname user=$username password=$password";


$conn = pg_connect($connection_string);


if (!$conn) {
    die("Connection failed: " . pg_last_error());
}


$sql = "CREATE TABLE IF NOT EXISTS \"user\" (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(10) NOT NULL DEFAULT 'author' CHECK (role IN ('author', 'editor', 'admin'))
)";

$result = pg_query($conn, $sql);
if (!$result) {
    die("Error creating user table: " . pg_last_error($conn));
}


$sql = "CREATE TABLE IF NOT EXISTS category (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
)";

$result = pg_query($conn, $sql);
if (!$result) {
    die("Error creating category table: " . pg_last_error($conn));
}


$sql = "CREATE TABLE IF NOT EXISTS news (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    image VARCHAR(255),
    dateposted TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    category_id INTEGER NOT NULL REFERENCES category(id),
    author_id INTEGER NOT NULL REFERENCES \"user\"(id),
    status VARCHAR(10) NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'approved', 'denied'))
)";

$result = pg_query($conn, $sql);
if (!$result) {
    die("Error creating news table: " . pg_last_error($conn));
}


$check_sql = "SELECT COUNT(*) as count FROM category";
$check_result = pg_query($conn, $check_sql);
$row = pg_fetch_assoc($check_result);

if ((int)$row['count'] === 0) {
 
    $categories = [
        ["سياسة", "أخبار سياسية محلية ودولية"],
        ["اقتصاد", "أخبار اقتصادية ومالية"],
        ["رياضة", "أخبار رياضية من كافة أنحاء العالم"],
        ["صحة", "أخبار ونصائح صحية"]
    ];

    foreach ($categories as $key => $category) {
        $stmt_name = "insert_category_" . $key;
        $stmt = pg_prepare($conn, $stmt_name, "INSERT INTO category (name, description) VALUES ($1, $2)");
        $result = pg_execute($conn, $stmt_name, [$category[0], $category[1]]);
        if (!$result) {
            echo "Error inserting category: " . pg_last_error($conn) . "<br>";
        }
    }
}


$check_sql = "SELECT COUNT(*) as count FROM \"user\" WHERE email = 'admin@newsportal.com'";
$check_result = pg_query($conn, $check_sql);
$row = pg_fetch_assoc($check_result);

if ((int)$row['count'] === 0) {
    
    $admin_name = "Admin";
    $admin_email = "admin@newsportal.com";
    $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
    $admin_role = "admin";

    $stmt = pg_prepare($conn, "insert_admin", "INSERT INTO \"user\" (name, email, password, role) VALUES ($1, $2, $3, $4)");
    $result = pg_execute($conn, "insert_admin", [$admin_name, $admin_email, $admin_password, $admin_role]);
    if (!$result) {
        echo "Error inserting admin user: " . pg_last_error($conn) . "<br>";
    }
}

echo "Database setup complete! Default admin credentials:<br>";
echo "Email: admin@newsportal.com<br>";
echo "Password: admin123<br>";
echo "You can now <a href='/index.php'>go to the homepage</a> or <a href='/auth/login.php'>login</a>.";


pg_close($conn);
?>
