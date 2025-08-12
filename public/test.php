<?php
echo "<h1>Test - CodeIgniter Çalışıyor!</h1>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Sunucu Zamanı: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>mbstring extension: " . (extension_loaded('mbstring') ? 'YÜKLENDİ' : 'YÜKLENMEDİ') . "</p>";

// Database bağlantısı test et
try {
    // MySQL bağlantısını direkt test et
    $mysqli = new mysqli('localhost', 'root', '', 'eticaret_staj');

    if ($mysqli->connect_error) {
        echo "<p style='color:red'>❌ Database bağlantısı başarısız: " . $mysqli->connect_error . "</p>";
    } else {
        echo "<p style='color:green'>✅ Database bağlantısı başarılı!</p>";

        // Tabloları kontrol et
        $result = $mysqli->query("SHOW TABLES");
        $tables = [];
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        echo "<p>Mevcut tablolar: " . implode(', ', $tables) . "</p>";

        // Products tablosunu kontrol et
        if (in_array('products', $tables)) {
            $result = $mysqli->query("SELECT COUNT(*) as count FROM products");
            $row = $result->fetch_assoc();
            echo "<p>Products tablosunda {$row['count']} ürün var</p>";

            // Birkaç ürün örneği göster
            $result = $mysqli->query("SELECT id, title, brand FROM products LIMIT 3");
            echo "<p>Örnek ürünler:</p><ul>";
            while ($row = $result->fetch_assoc()) {
                echo "<li>ID: {$row['id']} - {$row['brand']} - {$row['title']}</li>";
            }
            echo "</ul>";
        }

        $mysqli->close();
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Database hatası: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>AJAX Test</h3>";
echo "<button onclick='testAjax()'>AJAX Test Et</button>";
echo "<div id='ajax-result'></div>";

echo "<script>
function testAjax() {
    document.getElementById('ajax-result').innerHTML = 'Testing...';

    fetch('" . (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http') . "://" . $_SERVER['HTTP_HOST'] . "/eticaret-staj/public/product/getCartCount')
        .then(response => response.json())
        .then(data => {
            document.getElementById('ajax-result').innerHTML = '<p style=\"color:green\">✅ AJAX çalışıyor! Response: ' + JSON.stringify(data) + '</p>';
        })
        .catch(error => {
            document.getElementById('ajax-result').innerHTML = '<p style=\"color:red\">❌ AJAX hatası: ' + error + '</p>';
        });
}
</script>";
?>
