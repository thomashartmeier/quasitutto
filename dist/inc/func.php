<?php

function getClientIpAddress()
{
    $candidates = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'REMOTE_ADDR'
    ];

    foreach ($candidates as $header)
    {
        if (empty($_SERVER[$header]))
        {
            continue;
        }

        $value = $_SERVER[$header];
        $ip = trim(explode(',', $value)[0]);

        if (filter_var($ip, FILTER_VALIDATE_IP))
        {
            return $ip;
        }
    }

    return '';
}

function resolveCountryCodeFromIp($ip)
{
    // If behind Cloudflare, this is the cheapest and most reliable country hint.
    if (!empty($_SERVER['HTTP_CF_IPCOUNTRY']))
    {
        $countryCode = strtoupper(trim($_SERVER['HTTP_CF_IPCOUNTRY']));
        if (preg_match('/^[A-Z]{2}$/', $countryCode))
        {
            return $countryCode;
        }
    }

    $context = stream_context_create([
        'http' => [
            'timeout' => 1.5,
            'header' => "User-Agent: quasitutto-ip-check\r\n"
        ]
    ]);

    $response = @file_get_contents("https://ipapi.co/{$ip}/country/", false, $context);
    if ($response === false)
    {
        return null;
    }

    $countryCode = strtoupper(trim($response));
    if (preg_match('/^[A-Z]{2}$/', $countryCode))
    {
        return $countryCode;
    }

    return null;
}

function isSwissVisitor($ip)
{
    if (empty($ip))
    {
        return false;
    }

    // Allow local/private addresses (useful for local testing and internal deployments).
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))
    {
        return true;
    }

    $cacheValidForSeconds = 3600;
    $hasValidCache = !empty($_SESSION['countryCacheIp'])
        && !empty($_SESSION['countryCacheCode'])
        && !empty($_SESSION['countryCacheAt'])
        && $_SESSION['countryCacheIp'] === $ip
        && (time() - $_SESSION['countryCacheAt']) < $cacheValidForSeconds;

    if ($hasValidCache)
    {
        return $_SESSION['countryCacheCode'] === 'CH';
    }

    $countryCode = resolveCountryCodeFromIp($ip);
    $_SESSION['countryCacheIp'] = $ip;
    $_SESSION['countryCacheCode'] = $countryCode;
    $_SESSION['countryCacheAt'] = time();

    return $countryCode === 'CH';
}

function getGuestbookEntriesHtml($conn, $frontPageOnly)
{
    $htmlOutput = "";

    $sql = "SELECT createDate, clientId, feedback, female FROM guestbookentries WHERE active = 1";
    if ($frontPageOnly)
    {
        $sql .= " AND frontpage = 1";
    }

    $sql .= " ORDER BY createDate DESC";
    $query = mysqli_query($conn, $sql) or die("Could not run SQL query.");
    $entries = [];

    while ($row = mysqli_fetch_assoc($query))
    {
        // look up prename and city in the clients table
        $clientId = $row['clientId'];
        $sqlClient = "SELECT prename, city FROM clients WHERE id = $clientId";
        $queryClient = mysqli_query($conn, $sqlClient) or die("Could not run SQL query.");
        if ($client = mysqli_fetch_assoc($queryClient))
        {
            $row['prename'] = $client['prename'];
            $row['city'] = $client['city'];
        }

        // compile html output
        $htmlOutput .= "<div class=\"text-center\">\n";
        $htmlOutput .= "    <div class=\"mb-2 fst-italic\">\"" . nl2br(htmlspecialchars($row['feedback'])) . "\"</div>";
        $htmlOutput .= "    <div class=\"d-flex align-items-center justify-content-center\">\n";
        if ($row['female'])
        {
            $htmlOutput .= "        <img class=\"rounded-circle me-3\" src=\"img/f.png\" alt=\"...\" />\n";
        }
        else
        {
            $htmlOutput .= "        <img class=\"rounded-circle me-3\" src=\"img/m.png\" alt=\"...\" />\n";
        }
        $htmlOutput .= "    <div class=\"fw-bold\">\n";
        $htmlOutput .= "        " . htmlspecialchars($row['prename']) . ", " . date("d. F Y", strtotime($row['createDate'])) . "\n";
        $htmlOutput .= "        <span class=\"fw-bold text-primary mx-1\">/</span>\n";
        $htmlOutput .= "        " . htmlspecialchars($row['city']) . "\n";
        $htmlOutput .= "    </div>\n";
        $htmlOutput .= "</div>\n";
        $htmlOutput .= "<p class=\"mb-5\"></p>\n";
    }

    return $htmlOutput;
}

?>