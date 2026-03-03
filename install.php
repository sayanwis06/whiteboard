<?php
try {
    $modulePath = dirname(__FILE__);
    
    // Create cache and logs directories
    @mkdir($modulePath . '/cache', 0755, true);
    @mkdir($modulePath . '/logs', 0755, true);
    @mkdir($modulePath . '/public/css', 0755, true);
    @mkdir($modulePath . '/public/js', 0755, true);
    
    echo "✓ Whiteboard module installed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
