<?php
$requiredFields = [
    'PUSHER_APP_ID' => 'Pusher App ID',
    'PUSHER_APP_KEY' => 'Pusher App Key',
    'PUSHER_APP_SECRET' => 'Pusher App Secret',
    'PUSHER_APP_CLUSTER' => 'Pusher App Cluster'
];

foreach ($requiredFields as $field => $label) {
    if (empty($configuration[$field])) {
        throw new \Exception("Missing required configuration: {$label}");
    }
}

echo "✓ Configuration validation passed\n";
?>
