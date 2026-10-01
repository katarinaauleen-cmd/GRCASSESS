<?php

require_once "db.php";


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}


$url = trim($_POST['url'] ?? '');


if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
    die("Invalid URL.");
}


$parts = parse_url($url);

if (!isset($parts['scheme']) || !in_array($parts['scheme'], ['http', 'https'])) {
    die("Only HTTP and HTTPS URLs are supported.");
}


$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_NOBODY => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 3,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_USERAGENT => 'GRC-Security-Assessment/1.0'
]);

$response = curl_exec($ch);

$curl_error = curl_error($ch);

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

$final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

curl_close($ch);


if ($response === false) {

    die("
        Unable to connect to the website.
        <br>
        Error: " . htmlspecialchars($curl_error) . "
        <br><br>
        <a href='index.php'>Back</a>
    ");

}


$headers_raw = substr($response, 0, $header_size);



$headers = [];

$header_lines = explode("\r\n", $headers_raw);

foreach ($header_lines as $line) {

    if (strpos($line, ':') !== false) {

        [$name, $value] = explode(':', $line, 2);

        $headers[strtolower(trim($name))] = trim($value);

    }

}


$findings = [];

$recommendations = [];

$score = 0;


$is_https = strtolower($parts['scheme']) === 'https';

if ($is_https) {

    $findings[] = [
        'name' => 'HTTPS',
        'status' => 'PASS',
        'message' => 'Website uses HTTPS.'
    ];

} else {

    $score += 25;

    $findings[] = [
        'name' => 'HTTPS',
        'status' => 'FAIL',
        'message' => 'Website is not using HTTPS.'
    ];

    $recommendations[] =
        'Enable HTTPS and redirect HTTP traffic to HTTPS.';

}


if (isset($headers['content-security-policy'])) {

    $findings[] = [
        'name' => 'Content Security Policy',
        'status' => 'PASS',
        'message' => 'Content-Security-Policy header detected.'
    ];

} else {

    $score += 15;

    $findings[] = [
        'name' => 'Content Security Policy',
        'status' => 'WARNING',
        'message' => 'Content-Security-Policy header was not detected.'
    ];

    $recommendations[] =
        'Consider implementing a Content-Security-Policy header.';

}


if (isset($headers['x-content-type-options'])) {

    $findings[] = [
        'name' => 'X-Content-Type-Options',
        'status' => 'PASS',
        'message' => 'X-Content-Type-Options header detected.'
    ];

} else {

    $score += 10;

    $findings[] = [
        'name' => 'X-Content-Type-Options',
        'status' => 'WARNING',
        'message' => 'X-Content-Type-Options header was not detected.'
    ];

    $recommendations[] =
        'Add X-Content-Type-Options: nosniff.';

}


if (isset($headers['strict-transport-security'])) {

    $findings[] = [
        'name' => 'HSTS',
        'status' => 'PASS',
        'message' => 'Strict-Transport-Security header detected.'
    ];

} else {

    $score += 10;

    $findings[] = [
        'name' => 'HSTS',
        'status' => 'WARNING',
        'message' => 'Strict-Transport-Security header was not detected.'
    ];

    $recommendations[] =
        'Consider enabling HTTP Strict Transport Security (HSTS).';

}


if (isset($headers['x-frame-options'])) {

    $findings[] = [
        'name' => 'X-Frame-Options',
        'status' => 'PASS',
        'message' => 'X-Frame-Options header detected.'
    ];

} else {

    $score += 10;

    $findings[] = [
        'name' => 'X-Frame-Options',
        'status' => 'WARNING',
        'message' => 'X-Frame-Options header was not detected.'
    ];

    $recommendations[] =
        'Add X-Frame-Options or an equivalent CSP frame-ancestors policy.';

}



if ($score <= 30) {

    $risk_level = 'Low';

} elseif ($score <= 60) {

    $risk_level = 'Medium';

} else {

    $risk_level = 'High';

}


$findings_json = json_encode($findings);

$recommendations_json = json_encode($recommendations);


$stmt = $conn->prepare("
    INSERT INTO assessments
    (
        target_url,
        score,
        risk_level,
        findings,
        recommendations
    )
    VALUES (?, ?, ?, ?, ?)
");


$stmt->bind_param(
    "sisss",
    $url,
    $score,
    $risk_level,
    $findings_json,
    $recommendations_json
);


$stmt->execute();

$stmt->close();


?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Assessment Result</title>

    <link rel="stylesheet" href="style.css">

</head>


<body>


<nav class="navbar">

    <div class="logo">
        GRC<span>ASSESS</span>
    </div>

    <a href="index.php" class="back">
        ← New Assessment
    </a>

</nav>


<main class="container">


<section class="result-header">

    <div>

        <div class="badge">
            ASSESSMENT RESULT
        </div>

        <h1>
            Security Assessment
        </h1>

        <p class="target">
            <?= htmlspecialchars($url) ?>
        </p>

    </div>

</section>


<section class="result-grid">


<div class="score-card">

    <span class="label">
        RISK SCORE
    </span>

    <div class="score">
        <?= $score ?>
        <small>/100</small>
    </div>

    <div class="risk large <?= strtolower($risk_level) ?>">
        <?= $risk_level ?> Risk
    </div>

</div>


<div class="summary-card">

    <span class="label">
        ASSESSMENT SUMMARY
    </span>

    <h2>
        <?= count($findings) ?> security controls assessed
    </h2>

    <p>
        The assessment identified basic security
        configuration findings for the submitted website.
    </p>

</div>


</section>


<section class="results-section">

    <div class="section-title">

        <div>

            <span>01 — FINDINGS</span>

            <h2>
                Security Controls
            </h2>

        </div>

    </div>


    <div class="findings">

        <?php foreach ($findings as $finding): ?>

            <div class="finding-card">

                <div>

                    <h3>
                        <?= htmlspecialchars($finding['name']) ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars($finding['message']) ?>
                    </p>

                </div>


                <span class="status
                    <?= strtolower($finding['status']) ?>">

                    <?= htmlspecialchars($finding['status']) ?>

                </span>

            </div>

        <?php endforeach; ?>

    </div>

</section>


<section class="results-section">

    <div class="section-title">

        <div>

            <span>02 — RECOMMENDATIONS</span>

            <h2>
                Recommended Actions
            </h2>

        </div>

    </div>


    <?php if (count($recommendations) > 0): ?>

        <div class="recommendations">

            <?php foreach ($recommendations as $index => $recommendation): ?>

                <div class="recommendation">

                    <span>
                        <?= str_pad($index + 1, 2, '0', STR_PAD_LEFT) ?>
                    </span>

                    <p>
                        <?= htmlspecialchars($recommendation) ?>
                    </p>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="success-box">

            No major recommendations were identified
            by the basic assessment.

        </div>

    <?php endif; ?>

</section>


<section class="methodology">

    <span class="label">
        METHODOLOGY
    </span>

    <h2>
        Basic Security Control Assessment
    </h2>

    <p>
        This project evaluates basic website security
        controls including HTTPS, Content Security Policy,
        HSTS, X-Content-Type-Options and X-Frame-Options.
        The result is intended for educational and
        portfolio purposes.
    </p>

</section>


</main>


<footer>

    <p>
        GRC Security Assessment Tool
    </p>

    <p>
        Academic / Portfolio Project
    </p>

</footer>


</body>

</html>