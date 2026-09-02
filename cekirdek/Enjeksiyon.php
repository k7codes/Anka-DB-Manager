<?php

class Enjeksiyon
{
    public $kayitlar = [];

    public function kayit($mesaj, $tur = 'ok')
    {
        $this->kayitlar[] = ['zaman' => date('H:i:s'), 'mesaj' => $mesaj, 'tur' => $tur];

        return true;
    }

    public function calistir($hedefyol)
    {
        $this->kayitlar = [];

        $hedefyol = rtrim(str_replace('\\', '/', $hedefyol), '/ ');

        if ($hedefyol === '' || !is_dir($hedefyol)) {
            $this->kayit('geçerli bir proje klasörü girilmedi', 'hata');

            return $this->kayitlar;
        }

        $kendiklasoru = str_replace('\\', '/', ANKA_YOL);

        if ($hedefyol === $kendiklasoru || strpos($hedefyol, $kendiklasoru . '/') === 0) {
            $this->kayit('hedef klasör anka yönetim sistemi dışında olmalı', 'hata');

            return $this->kayitlar;
        }

        $cerceve = $this->cercevetespit($hedefyol);

        $this->kayit('proje tarandı: ' . $cerceve['etiket']);

        $hedefpaket = $hedefyol . '/ankamanager';

        if (is_dir($hedefpaket)) {
            $this->kayit('önceki ankamanager kopyası bulundu, güncelleniyor', 'uyari');
        }

        if (!$this->paketkopyala($hedefyol, $hedefpaket)) {
            $this->kayit('paket kopyalanamadı, yazma iznini kontrol et', 'hata');

            return $this->kayitlar;
        }

        $this->kayit('ankamanager paketi ' . $hedefpaket . ' konumuna kopyalandı');

        $girisler = $this->girisnoktalari($hedefyol);

        if (!$girisler) {
            $this->kayit('giriş noktası bulunamadı, index.php dosyanı elinle kontrol et', 'uyari');

            return $this->kayitlar;
        }

        foreach ($girisler as $giris) {
            if ($this->girisekle($hedefyol, $giris)) {
                $this->kayit(str_replace($hedefyol . '/', '', $giris) . ' dosyasına enjeksiyon yapıldı');
            } else {
                $this->kayit(str_replace($hedefyol . '/', '', $giris) . ' dosyasına ekleme yapılamadı', 'uyari');
            }
        }

        $this->oncekiayarlaribirles($hedefpaket);

        $adres = 'proje kökünün web adresi' . '/' . basename($hedefpaket);

        $this->kayit('tamamlandı, ' . $girisler[0] . ' etkin');

        $this->kayit('tarayıcıdan: ' . $adres, 'yol');

        return $this->kayitlar;
    }

    public function cercevetespit($yol)
    {
        $yol = rtrim(str_replace('\\', '/', $yol), '/');

        if (is_file($yol . '/wp-config.php') || is_dir($yol . '/wp-content')) {
            return ['ad' => 'wordpress', 'etiket' => 'WordPress', 'giris' => 'index.php'];
        }

        if (is_file($yol . '/artisan') && is_dir($yol . '/public')) {
            return ['ad' => 'laravel', 'etiket' => 'Laravel', 'giris' => 'public/index.php'];
        }

        if (is_file($yol . '/application/config/config.php')) {
            return ['ad' => 'codeigniter', 'etiket' => 'CodeIgniter', 'giris' => 'index.php'];
        }

        if (is_file($yol . '/index.php')) {
            return ['ad' => 'duzphp', 'etiket' => 'Düz PHP', 'giris' => 'index.php'];
        }

        return ['ad' => 'temel', 'etiket' => 'Temel PHP', 'giris' => ''];
    }

    public function girisnoktalari($yol)
    {
        $yol = rtrim(str_replace('\\', '/', $yol), '/');

        $cerceve = $this->cercevetespit($yol);

        $adaylar = [];

        if ($cerceve['ad'] === 'laravel') {
            $adaylar[] = $yol . '/public/index.php';
        } elseif ($cerceve['ad'] === 'wordpress') {
            $adaylar[] = $yol . '/index.php';

            $tema = $this->wordpresstemadizini($yol);

            if ($tema) {
                $adaylar[] = $tema . '/functions.php';
            }
        } else {
            $bulunanlar = $this->dosyaara($yol, '/index\.php$/i');

            $adaylar = array_merge($adaylar, $bulunanlar);

            if ($hisler = glob($yol . '/route*.php')) {
                $adaylar = array_merge($adaylar, $hisler);
            }

            if (is_file($yol . '/admin.php')) {
                $adaylar[] = $yol . '/admin.php';
            }

            if (is_file($yol . '/router.php')) {
                $adaylar[] = $yol . '/router.php';
            }
        }

        $sonuc = [];

        foreach ($adaylar as $giris) {
            if (is_file($giris)) {
                $sonuc[] = $giris;
            }
        }

        return array_slice(array_unique($sonuc), 0, 3);
    }

    public function wordpresstemadizini($yol)
    {
        $yol = rtrim(str_replace('\\', '/', $yol), '/');

        $temaklasorleri = glob($yol . '/wp-content/themes/*');

        if (!$temaklasorleri) {
            return null;
        }

        foreach ($temaklasorleri as $klasor) {
            if (is_file($klasor . '/functions.php')) {
                return $klasor;
            }
        }

        return null;
    }

    public function dosyaara($yol, $desen, $derinlik = 0)
    {
        $sonuc = [];

        if ($derinlik > 6) {
            return $sonuc;
        }

        $yol = rtrim(str_replace('\\', '/', $yol), '/');

        $ogeler = glob($yol . '/*');

        if (!$ogeler) {
            return $sonuc;
        }

        foreach ($ogeler as $oge) {
            if (is_dir($oge)) {
                $ad = basename($oge);

                if (in_array($ad, ['vendor', 'node_modules', 'ankamanager', '.git', '.svn', 'storage', 'cache', 'uploads'])) {
                    continue;
                }

                $sonuc = array_merge($sonuc, $this->dosyaara($oge, $desen, $derinlik + 1));
            } elseif (preg_match($desen, basename($oge))) {
                $sonuc[] = $oge;
            }
        }

        return $sonuc;
    }

    public function girisekle($projeyolu, $girisdosyasi)
    {
        if (!is_file($girisdosyasi)) {
            return false;
        }

        $icerik = file_get_contents($girisdosyasi);

        if ($icerik === false) {
            return false;
        }

        if (strpos($icerik, 'ankamanager.php') !== false) {
            return true;
        }

        $satir = 'require_once __DIR__ . ' . var_export($this->dahilyolu($projeyolu, $girisdosyasi), true) . ';';

        $blok = "\n" . $satir . "\n";

        $eski = $icerik;

        if (preg_match('/(declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;)/i', $icerik)) {
            $icerik = preg_replace('/(declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;)/i', '$1' . $blok, $icerik, 1);
        } elseif (preg_match('/<\?php/i', $icerik)) {
            $icerik = preg_replace('/<\?php/i', '<?php' . $blok, $icerik, 1);
        } else {
            $icerik = "<?php\n" . $blok . $icerik;
        }

        if ($icerik === $eski) {
            return false;
        }

        if (@file_put_contents($girisdosyasi, $icerik) === false) {
            return false;
        }

        return true;
    }

    public function dahilyolu($projeyolu, $girisdosyasi)
    {
        $girisklasoru = str_replace('\\', '/', dirname($girisdosyasi));

        $projeyolu = rtrim(str_replace('\\', '/', $projeyolu), '/');

        $ankaklasoru = $projeyolu . '/ankamanager';

        $goreli = $this->goreliyol($girisklasoru, $ankaklasoru);

        if ($goreli === '') {
            $goreli = '.';
        }

        return $goreli . '/ankamanager.php';
    }

    public function goreliyol($kaynak, $hedef)
    {
        $kaynakparcalar = explode('/', trim(str_replace('\\', '/', $kaynak), '/'));

        $hedefparcalar = explode('/', trim(str_replace('\\', '/', $hedef), '/'));

        if (count($kaynakparcalar) === 1 && $kaynakparcalar[0] === '') {
            $kaynakparcalar = [];
        }

        if (count($hedefparcalar) === 1 && $hedefparcalar[0] === '') {
            $hedefparcalar = [];
        }

        $ortak = 0;

        $uzunluk = min(count($kaynakparcalar), count($hedefparcalar));

        for ($i = 0; $i < $uzunluk; $i++) {
            if ($kaynakparcalar[$i] === $hedefparcalar[$i]) {
                $ortak++;
            } else {
                break;
            }
        }

        $parcalar = [];

        for ($i = $ortak; $i < count($kaynakparcalar); $i++) {
            $parcalar[] = '..';
        }

        for ($i = $ortak; $i < count($hedefparcalar); $i++) {
            $parcalar[] = $hedefparcalar[$i];
        }

        return implode('/', $parcalar);
    }

    public function paketkopyala($projeyolu, $hedefpaket)
    {
        $klasorler = ['cekirdek', 'yonetim', 'gorsel'];

        $dosyalar = ['anka.php', 'index.php'];

        ankaklasorac ($hedefpaket);

        foreach ($klasorler as $klasor) {
            $kaynak = ANKA_YOL . DIRECTORY_SEPARATOR . $klasor;

            if (is_dir($kaynak)) {
                $this->klasorkopyala($kaynak, $hedefpaket . DIRECTORY_SEPARATOR . $klasor);
            }
        }

        foreach ($dosyalar as $dosya) {
            $kaynak = ANKA_YOL . DIRECTORY_SEPARATOR . $dosya;

            if (is_file($kaynak)) {
                @copy($kaynak, $hedefpaket . DIRECTORY_SEPARATOR . $dosya);
            }
        }

        $bootstrap = $this->ankamanagerkodu();

        if (@file_put_contents($hedefpaket . DIRECTORY_SEPARATOR . 'ankamanager.php', $bootstrap) === false) {
            return false;
        }

        ankaklasorac ($hedefpaket . DIRECTORY_SEPARATOR . 'veri' . DIRECTORY_SEPARATOR . 'yedekler');

        ankaklasorac ($hedefpaket . DIRECTORY_SEPARATOR . 'veri' . DIRECTORY_SEPARATOR . 'migrasyonlar');

        @file_put_contents($hedefpaket . DIRECTORY_SEPARATOR . 'veri' . DIRECTORY_SEPARATOR . '.htaccess', "Deny from all\n");

        return is_file($hedefpaket . DIRECTORY_SEPARATOR . 'cekirdek' . DIRECTORY_SEPARATOR . 'Veritabani.php');
    }

    public function klasorkopyala($kaynak, $hedef)
    {
        if (!is_dir($kaynak)) {
            return false;
        }

        if (!is_dir($hedef)) {
            ankaklasorac ($hedef);
        }

        $ogeler = scandir($kaynak);

        foreach ($ogeler as $oge) {
            if ($oge === '.' || $oge === '..') {
                continue;
            }

            $kaynakyolu = $kaynak . DIRECTORY_SEPARATOR . $oge;

            $hedefyolu = $hedef . DIRECTORY_SEPARATOR . $oge;

            if (is_dir($kaynakyolu)) {
                $this->klasorkopyala($kaynakyolu, $hedefyolu);
            } else {
                @copy($kaynakyolu, $hedefyolu);
            }
        }

        return true;
    }

    public function oncekiayarlaribirles($hedefpaket)
    {
        $kaynakdosya = ANKA_VERI . DIRECTORY_SEPARATOR . 'kurulum.json';

        $hedefdosya = $hedefpaket . DIRECTORY_SEPARATOR . 'veri' . DIRECTORY_SEPARATOR . 'kurulum.json';

        if (is_file($kaynakdosya) && !is_file($hedefdosya)) {
            @copy($kaynakdosya, $hedefdosya);
        }
    }

    public function ankamanagerkodu()
    {
        $kod = <<<'ANKA'

<?php

if (PHP_SAPI === 'cli') {
    return;
}

if (defined('ANKA_GOMULU')) {
    return;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$istekyolu = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

$tetikle = strpos($istekyolu, 'ankamanager') !== false;

if (!$tetikle) {
    return;
}

define('ANKA_GOMULU', true);

define('ANKA_GOMULU_YOL', __DIR__);

require_once __DIR__ . '/index.php';

exit;

ANKA;

        return $kod;
    }
}