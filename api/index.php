<?php
header('Content-Type: text/html; charset=utf-8');
$base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
$webhook = $base . '/api/make_webhook';
$feed = $base . '/api/feed';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Upwork RSS Feed API</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 640px; margin: 2rem auto; padding: 0 1rem; color: #333; }
    h1 { font-size: 1.5rem; }
    code { background: #f0f0f0; padding: 0.2em 0.4em; border-radius: 4px; font-size: 0.9em; }
    a { color: #0070f3; }
    .endpoint { margin: 1rem 0; padding: 1rem; background: #f8f8f8; border-radius: 8px; }
    .endpoint strong { display: block; margin-bottom: 0.25rem; }
  </style>
</head>
<body>
  <h1>Upwork RSS Feed API</h1>
  <p>Use these endpoints with Make.com to generate and read Upwork job RSS feeds.</p>
  <div class="endpoint">
    <strong>Webhook (POST)</strong>
    <code><?php echo htmlspecialchars($webhook); ?></code>
    <p>POST body: <code>output</code>, <code>feed_name</code>, <code>email_address</code></p>
  </div>
  <div class="endpoint">
    <strong>RSS feed (GET)</strong>
    <code><?php echo htmlspecialchars($feed); ?>?name=YourFeedName</code>
    <p>Use this URL in your RSS reader.</p>
  </div>
</body>
</html>
