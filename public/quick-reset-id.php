<?php
try {
    $mysqli = new mysqli('localhost', 'root', '', 'eticaret_staj');

    if ($mysqli->connect_error) {
        die("Database connection failed: " . $mysqli->connect_error);
    }

    // Get max ID
    $result = $mysqli->query("SELECT MAX(id) as max_id FROM products");
    $row = $result->fetch_assoc();
    $maxId = $row['max_id'] ?? 0;
    $nextId = $maxId + 1;

    // Reset auto_increment
    $resetSql = "ALTER TABLE products AUTO_INCREMENT = {$nextId}";

    if ($mysqli->query($resetSql)) {
        echo "<h2 style='color:green'>✅ Success!</h2>";
        echo "<p>Auto_increment reset to: <strong>{$nextId}</strong></p>";
        echo "<p>Next product will get ID: <strong>{$nextId}</strong></p>";
    } else {
        echo "<h2 style='color:red'>❌ Failed!</h2>";
        echo "<p>Error: " . $mysqli->error . "</p>";
    }

    $mysqli->close();

} catch (Exception $e) {
    echo "<h2 style='color:red'>❌ Error!</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}

echo "<br><a href='/eticaret-staj/public/'>← Back to Main</a>";
echo " | <a href='/eticaret-staj/public/quick-test.php'>Test Product Add</a>";
?>

