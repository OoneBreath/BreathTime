<?php
function optimizeImage($source) {
    if (!file_exists($source)) return false;
    
    $info = getimagesize($source);
    if (!$info) return false;

    $quality = 85; // Balans między jakością a rozmiarem
    $maxWidth = 1920; // Maksymalna szerokość
    
    list($width, $height) = $info;
    $mime = $info['mime'];
    
    // Sprawdź czy obraz wymaga zmniejszenia
    if ($width > $maxWidth) {
        $newWidth = $maxWidth;
        $newHeight = floor($height * ($maxWidth / $width));
    } else {
        return true; // Obraz nie wymaga optymalizacji
    }
    
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        default:
            return false;
    }
    
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Zachowaj przezroczystość dla PNG
    if ($mime === 'image/png') {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
    }
    
    imagecopyresampled(
        $newImage, $image,
        0, 0, 0, 0,
        $newWidth, $newHeight, $width, $height
    );
    
    // Backup oryginalnego pliku
    rename($source, $source . '.bak');
    
    // Zapisz zoptymalizowany obraz
    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($newImage, $source, $quality);
            break;
        case 'image/png':
            imagepng($newImage, $source, 9);
            break;
    }
    
    imagedestroy($image);
    imagedestroy($newImage);
    
    // Jeśli nowy plik jest większy niż oryginalny, przywróć oryginał
    if (filesize($source) > filesize($source . '.bak')) {
        unlink($source);
        rename($source . '.bak', $source);
        return false;
    }
    
    unlink($source . '.bak');
    return true;
}

// Ścieżka do folderu z obrazami
$imageDir = __DIR__ . '/../uploads/images';

// Przeszukaj folder z obrazami
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($imageDir)
);

foreach ($iterator as $file) {
    if ($file->isFile()) {
        $path = $file->getPathname();
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            if (optimizeImage($path)) {
                echo "Zoptymalizowano: " . $path . "\n";
            }
        }
    }
}
