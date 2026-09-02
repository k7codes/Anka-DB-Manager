<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'anka.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$sayfa = isset($_GET['sayfa']) ? preg_replace('/[^a-z0-9_]/i', '', $_GET['sayfa']) : 'anasayfa';

if ($sayfa === '') {
    $sayfa = 'anasayfa';
}

$izinlisayfalar = ['anasayfa', 'tablolar', 'tablo', 'gezgin', 'er', 'fark', 'sorgu', 'kanvas', 'zeuger', 'crypter', 'yedekleme', 'enjeksiyon', 'baglantilar', 'yapilandirma', 'giris', 'migrasyonlar', 'cikis'];

if (!in_array($sayfa, $izinlisayfalar)) {
    $sayfa = 'anasayfa';
}

if ($sayfa === 'cikis') {
    Yetki::cikis();

    ankayonlendir ('?sayfa=giris');
}

Yetki::girisgerektir();

if (!empty($_POST)) {
    ankacsrfkoruma ();
}

$dosya = ANKA_YONETIM . DIRECTORY_SEPARATOR . $sayfa . '.php';

if (!is_file($dosya)) {
    $sayfa = 'anasayfa';

    $dosya = ANKA_YONETIM . DIRECTORY_SEPARATOR . 'anasayfa.php';
}

ob_start();

try {
    include $dosya;
} catch (Exception $hata) {
    echo '<div class="bildirim bildirim-hata">' . htmlspecialchars($hata->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
}

$icerik = ob_get_clean();

$icerik = preg_replace_callback(
    '/<form\b([^>]*method\s*=\s*["\']post["\'][^>]*)>.*?<\/form>/is',
    function ($m) {
        return str_replace('</form>', ankacsrfalan () . "\n</form>", $m[0]);
    },
    $icerik
);

include ANKA_YONETIM . DIRECTORY_SEPARATOR . 'cerceve.php';