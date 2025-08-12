<?php
echo "<h1>Fix Auto Increment ID</h1>";

try {
    $mysqli = new mysqli('localhost', 'root', '', 'eticaret_staj');

    if ($mysqli->connect_error) {
        die("Database connection failed: " . $mysqli->connect_error);
    }

    echo "<h3>Current Products:</h3>";
    $result = $mysqli->query("SELECT id, title, brand FROM products ORDER BY id");

    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Current ID</th><th>Brand</th><th>Title</th></tr>";

        $maxId = 0;
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td><strong>{$row['id']}</strong></td>";
            echo "<td>{$row['brand']}</td>";
            echo "<td>{$row['title']}</td>";
            echo "</tr>";
            $maxId = max($maxId, $row['id']);
        }
        echo "</table>";

        $nextId = $maxId + 1;
        echo "<p><strong>Max ID:</strong> {$maxId}</p>";
        echo "<p><strong>Next ID should be:</strong> {$nextId}</p>";
    } else {
        echo "<p>No products found.</p>";
        $nextId = 1;
    }

    echo "<hr>";
    echo "<h3>Auto Increment Status:</h3>";

    $result = $mysqli->query("SHOW TABLE STATUS LIKE 'products'");
    if ($result) {
        $status = $result->fetch_assoc();
        echo "<p><strong>Current Auto_increment:</strong> {$status['Auto_increment']}</p>";

        if ($status['Auto_increment'] != $nextId) {
            echo "<p style='color:orange'>⚠️ Auto_increment needs to be reset to {$nextId}</p>";

            // Reset auto_increment
            $resetSql = "ALTER TABLE products AUTO_INCREMENT = {$nextId}";
            echo "<p><strong>Reset SQL:</strong> <code>{$resetSql}</code></p>";

            if ($mysqli->query($resetSql)) {
                echo "<p style='color:green'>✅ Auto_increment reset successfully!</p>";

                // Verify
                $result = $mysqli->query("SHOW TABLE STATUS LIKE 'products'");
                $status = $result->fetch_assoc();
                echo "<p><strong>New Auto_increment:</strong> {$status['Auto_increment']}</p>";
            } else {
                echo "<p style='color:red'>❌ Failed to reset: " . $mysqli->error . "</p>";
            }
        } else {
            echo "<p style='color:green'>✅ Auto_increment is already correct!</p>";
        }
    }

    echo "<hr>";
    echo "<h3>Test Next Insert:</h3>";
    echo "<p>Next product will get ID: <strong>" . $nextId . "</strong></p>";

    $mysqli->close();

} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<br><a href='/eticaret-staj/public/'>← Back to Main</a>";
echo " | <a href='/eticaret-staj/public/quick-test.php'>Test Product Add</a>";
?>

