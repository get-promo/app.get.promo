<?php
// Wyczyść OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache wyczyszczony!<br>";
} else {
    echo "ℹ️ OPcache niedostępny<br>";
}

// Wyczyść realpath cache
clearstatcache(true);
echo "✅ Realpath cache wyczyszczony!<br>";

echo "<br><strong>Gotowe! Odśwież główną stronę aplikacji.</strong>";


