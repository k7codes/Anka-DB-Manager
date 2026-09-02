<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ANKA_GOMULU')) {
    define('ANKA_GOMULU', false);
}
define('ANKA_YOL', dirname(__FILE__));
define('ANKA_CEKIRDEK', ANKA_YOL . DIRECTORY_SEPARATOR . 'cekirdek');
define('ANKA_YONETIM', ANKA_YOL . DIRECTORY_SEPARATOR . 'yonetim');
define('ANKA_GORSEL', ANKA_YOL . DIRECTORY_SEPARATOR . 'gorsel');
define('ANKA_VERI', ANKA_YOL . DIRECTORY_SEPARATOR . 'veri');
define('ANKA_MIGRASYON', ANKA_VERI . DIRECTORY_SEPARATOR . 'migrasyonlar');
define('ANKA_YEDEK', ANKA_VERI . DIRECTORY_SEPARATOR . 'yedekler');
define('ANKA_SURUM', '1.0.0');
function ankaoturum ($anahtar, $deger = null)
{
    if (func_num_args() === 1) {
        return isset($_SESSION['anka'][$anahtar]) ? $_SESSION['anka'][$anahtar] : null;
    }
    $_SESSION['anka'][$anahtar] = $deger;
    return $deger;
}
function ankaoturumsil ($anahtar)
{
    if (isset($_SESSION['anka'][$anahtar])) {
        unset($_SESSION['anka'][$anahtar]);
    }
}
function ankaaktifbaglanti ()
{
    $aktif = ankaoturum ('aktifbaglanti');
    if ($aktif && Veritabani::ayarlarioku($aktif)) {
        return $aktif;
    }
    $liste = Veritabani::ayarlarioku();
    if (!$liste) {
        return null;
    }
    $adi = null;
    foreach ($liste as $ad => $ayar) {
        try {
            $tablolar = Schema::tablolar($ad);
            foreach ((array) $tablolar as $t) {
                $adet = (int) Veritabani::tekil('SELECT COUNT(*) FROM ' . $t . '', [], $ad);
                if ($adet > 0) {
                    $adi = $ad;
                    break 2;
                }
            }
            if ($adi === null) {
                $adi = $ad;
            }
        } catch (Exception $e) {
        }
    }
    if ($adi === null) {
        $adi = array_keys($liste)[0];
    }
    ankaoturum ('aktifbaglanti', $adi);
    return $adi;
}
function ankayukleyici ($sinifadi)
{
    $sinifadi = ltrim($sinifadi, '\\');
    $dosya = ANKA_CEKIRDEK . DIRECTORY_SEPARATOR . $sinifadi . '.php';
    if (is_file($dosya)) {
        require_once $dosya;
        return true;
    }
    return false;
}
spl_autoload_register('ankaYukleyici');
function ankaayarlari ()
{
    static $ayarlar = null;
    if (is_array($ayarlar)) {
        return $ayarlar;
    }
    $ayarlar = [];
    $dosya = ANKA_VERI . DIRECTORY_SEPARATOR . 'kurulum.json';
    if (is_file($dosya)) {
        $okunan = json_decode(file_get_contents($dosya), true);
        if (is_array($okunan)) {
            $ayarlar = $okunan;
        }
    }
    return $ayarlar;
}
function ankaayarlariguncelle ($yeniayarlar)
{
    ankaklasorac (ANKA_VERI);
    $yol = ANKA_VERI . DIRECTORY_SEPARATOR . 'kurulum.json';
    file_put_contents($yol, json_encode($yeniayarlar, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
function ankaklasorac ($yol)
{
    if (!is_dir($yol)) {
        @mkdir($yol, 0777, true);
    }
}
function ankaistek ($anahtar = null, $varsayilan = null)
{
    if ($anahtar === null) {
        return $_REQUEST;
    }
    return isset($_REQUEST[$anahtar]) ? $_REQUEST[$anahtar] : $varsayilan;
}
function ankagirdi ($anahtar, $varsayilan = null)
{
    return isset($_POST[$anahtar]) ? $_POST[$anahtar] : $varsayilan;
}
function ankasorgu ($anahtar, $varsayilan = null)
{
    return isset($_GET[$anahtar]) ? $_GET[$anahtar] : $varsayilan;
}
function ankayonlendir ($adres)
{
    header('Location: ' . $adres);
    exit;
}
function ankabildirim ($mesaj, $tur = 'bilgi')
{
    ankaoturum ('bildirim', ['mesaj' => $mesaj, 'tur' => $tur]);
}
function ankabildirimgoster ()
{
    $bildirim = ankaoturum ('bildirim');
    if (!$bildirim) {
        return '';
    }
    ankaoturumsil ('bildirim');
    $tur = isset($bildirim['tur']) ? $bildirim['tur'] : 'bilgi';
    $mesaj = isset($bildirim['mesaj']) ? htmlspecialchars($bildirim['mesaj'], ENT_QUOTES, 'UTF-8') : '';
    return '<div class="bildirim bildirim-' . $tur . '">' . $mesaj . '</div>';
}
function ankagoruntule ($deger)
{
    echo '<pre class="ciktiAlani">' . htmlspecialchars(print_r($deger, true)) . '</pre>';
}
/* ================= GÃ¼venlik: CSRF ================= */
function ankacsrftoken ()
{
    if (empty($_SESSION['anka']['csrf'])) {
        $_SESSION['anka']['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['anka']['csrf'];
}
function ankacsrfalan ()
{
    return '<input type="hidden" name="anka_csrf" value="' . htmlspecialchars(ankacsrftoken (), ENT_QUOTES, 'UTF-8') . '">';
}
function ankacsrfdogrula ()
{
    if (empty($_POST)) {
        return true;
    }
    return isset($_POST['anka_csrf'])
        && is_string($_POST['anka_csrf'])
        && hash_equals(ankacsrftoken (), $_POST['anka_csrf']);
}
function ankacsrfkoruma ()
{
    if (!ankacsrfdogrula ()) {
        ankabildirim ('GÃ¼venlik doÄŸrulamasÄ± geÃ§ersiz (CSRF). LÃ¼tfen tekrar deneyin.', 'hata');
        ankayonlendir ('index.php');
    }
}
/* ================= GÃ¼venlik: Audit Log ================= */
function ankaaudityolu ()
{
    return ANKA_VERI . DIRECTORY_SEPARATOR . 'audit.log';
}
function ankaaudit ($eylem, $ayrinti = '')
{
    try {
        ankaklasorac (ANKA_VERI);
        $kullanici = isset($_SESSION['anka']['kullaniciadi']) ? $_SESSION['anka']['kullaniciadi'] : 'misafir';
        $kayit = json_encode([
            'zaman' => date('Y-m-d H:i:s'),
            'kullanici' => $kullanici,
            'eylem' => (string) $eylem,
            'ayrinti' => (string) $ayrinti,
        ], JSON_UNESCAPED_UNICODE);
        file_put_contents(ankaaudityolu (), $kayit . PHP_EOL, FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {
    }
}
function ankaauditoku ($limit = 200)
{
    $yol = ankaaudityolu ();
    if (!is_file($yol)) {
        return [];
    }
    $satirlar = array_reverse((array) file($yol, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
    $kayitlar = [];
    foreach ($satirlar as $satir) {
        if (count($kayitlar) >= $limit) {
            break;
        }
        $kayit = json_decode($satir, true);
        if (is_array($kayit)) {
            $kayitlar[] = $kayit;
        }
    }
    return $kayitlar;
}