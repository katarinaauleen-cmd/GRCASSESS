<?php
require_once "db.php";

$result = $conn->query("
    SELECT *
    FROM assessments
    ORDER BY created_at DESC
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>GRC Security Assessment</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<nav class="navbar">
    <div class="logo">
        GRC<span>ASSESS</span>
    </div>

    <div class="nav-text">
        Security Risk Assessment
    </div>
</nav>


<main class="container">

    <section class="hero">

        <div class="badge">
            GRC SECURITY TOOL
        </div>

        <h1>
            Website Security<br>
            <span>Risk Assessment</span>
        </h1>

        <p>
            Assess basic website security controls and identify
            potential security risks.
        </p>

    </section>


    <section class="assessment-card">

        <form action="assess.php" method="POST">

            <label for="url">
                Website URL
            </label>

            <div class="input-group">

                <input
                    type="url"
                    name="url"
                    id="url"
                    placeholder="https://example.com"
                    required
                >

                <button type="submit">
                    Assess Website
                </button>

            </div>

            <small>
                Example: https://example.com
            </small>

        </form>

    </section>


    <section class="info-grid">

        <div class="info-card">
            <div class="icon">01</div>

            <h3>Security Controls</h3>

            <p>
                Checks HTTPS, security headers,
                cookies and TLS configuration.
            </p>
        </div>


        <div class="info-card">
            <div class="icon">02</div>

            <h3>Risk Analysis</h3>

            <p>
                Findings are converted into
                a simple risk score.
            </p>
        </div>


        <div class="info-card">
            <div class="icon">03</div>

            <h3>Recommendations</h3>

            <p>
                Provides basic recommendations
                for improving security.
            </p>
        </div>

    </section>


    <section class="history">

        <div class="section-title">

            <div>
                <span>ASSESSMENT HISTORY</span>
                <h2>Recent Assessments</h2>
            </div>

        </div>


        <?php if ($result && $result->num_rows > 0): ?>

            <div class="table-wrapper">

                <table>

                    <thead>
                        <tr>
                            <th>Website</th>
                            <th>Score</th>
                            <th>Risk Level</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($row['target_url']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['score']) ?>/100
                            </td>

                            <td>

                                <span class="risk
                                    <?= strtolower($row['risk_level']) ?>">
                                    <?= htmlspecialchars($row['risk_level']) ?>
                                </span>

                            </td>

                            <td>
                                <?= htmlspecialchars($row['created_at']) ?>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty">
                No assessments yet.
            </div>

        <?php endif; ?>

    </section>

</main>


<footer>

    <p>
        GRC Security Assessment Tool
    </p>

    <p>
        Portfolio Project
    </p>

</footer>

</body>
</html>