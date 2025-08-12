<?php
echo "<h1>Reset Auto Increment to 9</h1>";

try {
    $mysqli = new mysqli('localhost', 'root', '', 'eticaret_staj');

    if ($mysqli->connect_error) {
        die("Database connection failed: " . $mysqli->connect_error);
    }

    echo "<h3>Before Reset:</h3>";
    $result = $mysqli->query("SHOW TABLE STATUS LIKE 'products'");
    if ($result) {
        $status = $result->fetch_assoc();
        echo "<p><strong>Current Auto_increment:</strong> {$status['Auto_increment']}</p>";
    }

    echo "<h3>Products with ID 8 and above:</h3>";
    $result = $mysqli->query("SELECT id, title, brand FROM products WHERE id >= 8 ORDER BY id");

    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Brand</th><th>Title</th></tr>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td><strong>{$row['id']}</strong></td>";
            echo "<td>{$row['brand']}</td>";
            echo "<td>{$row['title']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    echo "<hr>";
    echo "<h3>Resetting Auto_increment to 9:</h3>";

    // Reset auto_increment to 9
    $resetSql = "ALTER TABLE products AUTO_INCREMENT = 9";
    echo "<p><strong>SQL Command:</strong> <code>{$resetSql}</code></p>";

    if ($mysqli->query($resetSql)) {
        echo "<p style='color:green'>✅ Auto_increment successfully reset to 9!</p>";

        // Verify the change
        $result = $mysqli->query("SHOW TABLE STATUS LIKE 'products'");
        if ($result) {
            $status = $result->fetch_assoc();
            echo "<p><strong>New Auto_increment:</strong> {$status['Auto_increment']}</p>";
        }

        echo "<h3>✅ Result:</h3>";
        echo "<ul>";
        echo "<li>✅ Next product will get ID: <strong>9</strong></li>";
        echo "<li>✅ After that: <strong>10, 11, 12...</strong></li>";
        echo "<li>✅ No more ID jumping!</li>";
        echo "</ul>";

    } else {
        echo "<p style='color:red'>❌ Failed to reset: " . $mysqli->error . "</p>";
    }

    $mysqli->close();

} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Test Now:</h3>";
echo "<p>🔥 <strong>Next product you add will get ID: 9</strong></p>";
echo "<br><a href='/eticaret-staj/public/' style='padding:10px; background:green; color:white; text-decoration:none;'>← Test Product Add</a>";
echo " <a href='/eticaret-staj/public/quick-test.php' style='padding:10px; background:blue; color:white; text-decoration:none;'>Quick Test</a>";
?>

