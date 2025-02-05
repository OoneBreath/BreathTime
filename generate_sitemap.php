<?php
require_once __DIR__ . '/includes/config.php';
header('Content-Type: application/xml; charset=utf-8');

function generateSitemap($pdo) {
    $xml = new DOMDocument('1.0', 'UTF-8');
    $urlset = $xml->createElement('urlset');
    $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    $xml->appendChild($urlset);

    // Strona główna
    $url = $xml->createElement('url');
    $loc = $xml->createElement('loc', 'https://breathtime.pl/');
    $lastmod = $xml->createElement('lastmod', date('Y-m-d'));
    $priority = $xml->createElement('priority', '1.0');
    $url->appendChild($loc);
    $url->appendChild($lastmod);
    $url->appendChild($priority);
    $urlset->appendChild($url);

    // Pobierz wszystkie petycje
    $stmt = $pdo->query("SELECT id, updated_at FROM petitions WHERE status = 'active'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $url = $xml->createElement('url');
        
        $loc = $xml->createElement('loc', 'https://breathtime.pl/view-petition.php?id=' . $row['id']);
        $lastmod = $xml->createElement('lastmod', date('Y-m-d', strtotime($row['updated_at'])));
        $priority = $xml->createElement('priority', '0.8');
        
        $url->appendChild($loc);
        $url->appendChild($lastmod);
        $url->appendChild($priority);
        $urlset->appendChild($url);
    }

    return $xml->saveXML();
}

try {
    echo generateSitemap($pdo);
    
    // Zapisz sitemap do pliku
    file_put_contents(__DIR__ . '/sitemap.xml', generateSitemap($pdo));
} catch (Exception $e) {
    error_log("Błąd generowania sitemap: " . $e->getMessage());
    header("HTTP/1.1 500 Internal Server Error");
    echo "<?xml version='1.0' encoding='UTF-8'?><error>Internal Server Error</error>";
}
