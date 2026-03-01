<?php
/**
 * Make.com webhook – Vercel serverless version.
 * Stores RSS in Upstash Redis and sends email via Resend.
 * POST: output, feed_name, email_address
 */
header('Content-Type: text/plain; charset=utf-8');

$redisUrl = getenv('UPSTASH_REDIS_REST_URL') ?: getenv('KV_REST_API_URL');
$redisToken = getenv('UPSTASH_REDIS_REST_TOKEN') ?: getenv('KV_REST_API_TOKEN');
$resendKey = getenv('RESEND_API_KEY');
$fromEmail = getenv('FROM_EMAIL') ?: 'onboarding@resend.dev';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['output'], $_POST['feed_name'], $_POST['email_address'])) {
    http_response_code(400);
    echo 'Invalid request';
    return;
}

$html = $_POST['output'];
$feedName = trim($_POST['feed_name']);
$emailAddress = trim($_POST['email_address']);
if ($feedName === '' || $emailAddress === '') {
    http_response_code(400);
    echo 'Invalid request';
    return;
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$rssFeedUrl = $protocol . '://' . $host . '/api/feed?name=' . rawurlencode($feedName);

$redisKey = 'feed:' . $feedName;

// Check if feed already existed (for email only on first time)
$isNew = false;
if ($redisUrl && $redisToken) {
    $getUrl = rtrim($redisUrl, '/') . '/get/' . rawurlencode($redisKey);
    $ch = curl_init($getUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $redisToken],
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code === 200) {
        $data = json_decode($res, true);
        $isNew = empty($data['result']);
    } else {
        $isNew = true;
    }
} else {
    $isNew = true;
}

$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML($html);
libxml_clear_errors();

$xpath = new DOMXPath($dom);
$jobListings = $xpath->query('//section[contains(@class, "card-list-container")]/article');
$jobs = [];

foreach ($jobListings as $job) {
    $titleElem = $xpath->query('.//h2[contains(@class, "job-tile-title")]/a', $job);
    $title = $titleElem->length > 0 ? trim($titleElem[0]->textContent) : 'No title found';
    $link = $titleElem->length > 0 ? 'https://www.upwork.com' . $titleElem[0]->getAttribute('href') : 'No link found';

    $descriptionElem = $xpath->query('.//div[contains(@class, "air3-line-clamp-wrapper clamp mb-3")]/div[contains(@class, "air3-line-clamp")]/p[contains(@class, "mb-0 text-body-sm")]', $job);
    $description = $descriptionElem->length > 0 ? trim($descriptionElem[0]->textContent) : 'No description found';

    $publishedDateElem = $xpath->query('.//small[@data-test="job-pubilshed-date"]/span[last()]', $job);
    $publishedDate = $publishedDateElem->length > 0 ? trim($publishedDateElem[0]->textContent) : 'No date found';

    $jobs[] = [
        'title' => $title,
        'link' => $link,
        'description' => $description,
        'publishedDate' => $publishedDate,
    ];
}

$rssContent = '<?xml version="1.0" encoding="UTF-8"?>' .
    '<rss version="2.0">' .
    '<channel>' .
    '<title>Upwork Job Feed RSS</title>' .
    '<description>RSS Feed for Upwork Jobs</description>' .
    '<link>' . htmlspecialchars($rssFeedUrl) . '</link>';

foreach ($jobs as $job) {
    $rssContent .= '<item>' .
        '<title>' . htmlspecialchars($job['title']) . '</title>' .
        '<link>' . htmlspecialchars($job['link']) . '</link>' .
        '<description>' . htmlspecialchars($job['description']) . '</description>' .
        '<pubDate>' . htmlspecialchars($job['publishedDate']) . '</pubDate>' .
        '</item>';
}
$rssContent .= '</channel></rss>';

// Store in Redis (Upstash REST: POST to /set/key with body = value)
if ($redisUrl && $redisToken) {
    $setUrl = rtrim($redisUrl, '/') . '/set/' . rawurlencode($redisKey);
    $ch = curl_init($setUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $rssContent,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $redisToken,
            'Content-Type: application/xml; charset=utf-8',
        ],
    ]);
    curl_exec($ch);
    $setCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($setCode !== 200) {
        http_response_code(502);
        echo 'Failed to store feed.';
        return;
    }
} else {
    http_response_code(500);
    echo 'Redis not configured. Set UPSTASH_REDIS_REST_URL and UPSTASH_REDIS_REST_TOKEN in Vercel.';
    return;
}

if ($isNew && $resendKey) {
    $subject = 'Your Upwork Job Feed RSS Link';
    $htmlBody = "Hello,<br><br>Here is your RSS feed link: <a href='" . htmlspecialchars($rssFeedUrl) . "'>" . htmlspecialchars($rssFeedUrl) . "</a><br><br>Add this link to your RSS reader to receive job updates.";
    $payload = json_encode([
        'from' => $fromEmail,
        'to' => [$emailAddress],
        'subject' => $subject,
        'html' => $htmlBody,
    ]);
    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $resendKey,
            'Content-Type: application/json',
        ],
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code >= 200 && $code < 300) {
        echo 'Check your email for the RSS feed URL';
    } else {
        echo 'RSS feed saved. Failed to send email (check Resend API key and domain).';
    }
} elseif ($isNew && !$resendKey) {
    echo 'RSS feed saved. Add RESEND_API_KEY in Vercel to send the feed link by email.';
} else {
    echo 'RSS feed updated successfully.';
}
