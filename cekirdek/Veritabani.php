<?php

class Veritabani
{
    private static $ornekler = [];

    private static $geciciayarlar = [];




    public static function ayarla($ad, $ayar)
    {
        self::$geciciayarlar[$ad] = $ayar;
    }




    public static function ayarlarioku($ad = null)
    {
        $liste = [];

        $dosya = ANKA_VERI . DIRECTORY_SEPARATOR . 'baglantilar.json';

        if (is_file($dosya)) {
            $okunan = json_decode(file_get_contents($dosya), true);

            if (is_array($okunan)) {
                $liste = $okunan;
            }
        }

        $liste = array_merge(self::$geciciayarlar, $liste);

        if ($ad === null) {
            return $liste;
        }

        return isset($liste[$ad]) ? $liste[$ad] : null;
    }




    public static function kaydet($ayar, $ad = 'varsayilan')
    {
        $liste = self::ayarlarioku();

        $liste[$ad] = $ayar;

        ankaklasorac (ANKA_VERI);

        $dosya = ANKA_VERI . DIRECTORY_SEPARATOR . 'baglantilar.json';

        file_put_contents($dosya, json_encode($liste, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }




    public static function kaldir($ad)
    {
        $liste = self::ayarlarioku();

        if (isset($liste[$ad])) {
            unset($liste[$ad]);

            ankaklasorac (ANKA_VERI);

            $dosya = ANKA_VERI . DIRECTORY_SEPARATOR . 'baglantilar.json';

            file_put_contents($dosya, json_encode($liste, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }




    public static function baglan($ad = 'varsayilan')
    {
        if (isset(self::$ornekler[$ad])) {
            return self::$ornekler[$ad];
        }

        $ayar = self::ayarlarioku($ad);

        if (!$ayar) {
            throw new Exception('bağlantı bulunamadı: ' . $ad);
        }

        $pdo = self::pdoolustur ($ayar);

        self::$ornekler[$ad] = $pdo;

        return $pdo;
    }




    public static function sunucupdo ($ayar)
    {
        $kopya = $ayar;

        $kopya['veritabaniadi'] = '';

        return self::pdoolustur ($kopya);
    }




    private static function pdoolustur ($ayar)
    {
        $surucu = isset($ayar['surucu']) ? $ayar['surucu'] : 'mysql';

        $sunucu = isset($ayar['sunucu']) ? $ayar['sunucu'] : 'localhost';

        $port = isset($ayar['port']) && $ayar['port'] !== '' ? $ayar['port'] : '3306';

        $veritabani = isset($ayar['veritabaniadi']) ? $ayar['veritabaniadi'] : '';

        $kullanici = isset($ayar['kullaniciadi']) ? $ayar['kullaniciadi'] : '';

        $parola = isset($ayar['parola']) ? $ayar['parola'] : '';

        $karakter = isset($ayar['karakter']) && $ayar['karakter'] !== '' ? $ayar['karakter'] : 'utf8mb4';

        $dsn = '';

        if ($surucu === 'mysql') {
            $dsn = 'mysql:host=' . $sunucu . ';port=' . $port;

            if ($veritabani !== '') {
                $dsn .= ';dbname=' . $veritabani;
            }

            $dsn .= ';charset=' . $karakter;
        } elseif ($surucu === 'pgsql') {
            $dsn = 'pgsql:host=' . $sunucu . ';port=' . $port;

            if ($veritabani !== '') {
                $dsn .= ';dbname=' . $veritabani;
            }
        } elseif ($surucu === 'sqlite') {
            $dsn = 'sqlite:' . $veritabani;
        } elseif ($surucu === 'sqlsrv') {
            $dsn = 'sqlsrv:Server=' . $sunucu . ',' . $port;

            if ($veritabani !== '') {
                $dsn .= ';Database=' . $veritabani;
            }
        } else {
            throw new Exception('desteklenmeyen sürücü: ' . $surucu);
        }

        $secenekler = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        if ($surucu !== 'sqlsrv') {
            $secenekler[PDO::ATTR_EMULATE_PREPARES] = false;
        }

        if ($surucu === 'mysql' && defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $secenekler[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $karakter;
        }

        return new PDO($dsn, $kullanici, $parola, $secenekler);
    }




    public static function calistir($sql, $parametreler = [], $ad = 'varsayilan')
    {
        $hazirlik = self::baglan($ad)->prepare($sql);

        $hazirlik->execute($parametreler);

        return $hazirlik;
    }




    public static function satirlar($sql, $parametreler = [], $ad = 'varsayilan')
    {
        return self::calistir($sql, $parametreler, $ad)->fetchAll();
    }




    public static function ilksatir ($sql, $parametreler = [], $ad = 'varsayilan')
    {
        $sonuc = self::calistir($sql, $parametreler, $ad)->fetch();

        return $sonuc === false ? null : $sonuc;
    }




    public static function tekil($sql, $parametreler = [], $ad = 'varsayilan')
    {
        $satir = self::ilksatir ($sql, $parametreler, $ad);

        if ($satir === null) {
            return null;
        }

        $deger = reset($satir);

        return $deger;
    }




    public static function soneklenenid ($ad = 'varsayilan')
    {
        return self::baglan($ad)->lastInsertId();
    }




    public static function islembaslat ($ad = 'varsayilan')
    {
        return self::baglan($ad)->beginTransaction();
    }




    public static function islemonayla ($ad = 'varsayilan')
    {
        return self::baglan($ad)->commit();
    }




    public static function islemgerial ($ad = 'varsayilan')
    {
        if (self::baglan($ad)->inTransaction()) {
            self::baglan($ad)->rollBack();
        }
    }




    public static function baglantitest ($ayar)
    {
        try {
            self::pdoolustur ($ayar);

            return [true, 'bağlantı başarılı'];
        } catch (Exception $hata) {
            return [false, $hata->getMessage()];
        }
    }




    public static function surucuturu ($ad = 'varsayilan')
    {
        $ayar = self::ayarlarioku($ad);

        if (!$ayar) {
            return 'mysql';
        }

        return isset($ayar['surucu']) && $ayar['surucu'] !== '' ? $ayar['surucu'] : 'mysql';
    }




    public static function tirnakla($alan, $ad = 'varsayilan')
    {
        $surucu = self::surucuturu ($ad);

        $muhafaza = $surucu === 'pgsql' ? '"' : '';

        if (strpos($alan, '.') !== false) {
            $parcalar = explode('.', $alan);

            $temizlenmis = [];

            foreach ($parcalar as $parca) {
                $temizlenmis[] = $muhafaza . $parca . $muhafaza;
            }

            return implode('.', $temizlenmis);
        }

        return $muhafaza . $alan . $muhafaza;
    }
}