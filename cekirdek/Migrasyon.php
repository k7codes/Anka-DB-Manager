<?php

class Migrasyon
{
    public static function klasoru()
    {
        ankaklasorac (ANKA_MIGRASYON);

        return ANKA_MIGRASYON;
    }

    public static function tablohazirla($baglantiadi = 'varsayilan')
    {
        $surucu = Veritabani::surucuturu ($baglantiadi);

        if ($surucu === 'pgsql') {
            $sql = "create table if not exists anka_migrasyonlar (id serial primary key, ad varchar(255) not null, uygulanma_vakti timestamp not null default now())";
        } elseif ($surucu === 'sqlite') {
            $sql = "create table if not exists anka_migrasyonlar (id integer primary key autoincrement, ad varchar(255) not null, uygulanma_vakti timestamp not null default current_timestamp)";
        } else {
            $sql = "create table if not exists anka_migrasyonlar (id int not null auto_increment primary key, ad varchar(255) not null, uygulanma_vakti datetime not null) engine=InnoDB default charset=utf8mb4";
        }

        Veritabani::calistir($sql, [], $baglantiadi);
    }

    public static function olustur($ad)
    {
        $ad = preg_replace('/[^a-zA-Z0-9_\-]/', '', str_replace(' ', '_', trim($ad)));

        if ($ad === '') {
            throw new Exception('migrasyon adı geçersiz');
        }

        self::klasoru();

        $dosyaadi = date('Ymd_His') . '_' . $ad . '.php';

        $dosyayolu = ANKA_MIGRASYON . DIRECTORY_SEPARATOR . $dosyaadi;

        $icerik = '<?php' . "\n\n" . 'return [' . "\n    'yukari' => []," . "\n    'asagi' => []," . "\n];" . "\n";

        file_put_contents($dosyayolu, $icerik);

        return $dosyayolu;
    }

    public static function bekleyen($baglantiadi = 'varsayilan')
    {
        self::tablohazirla($baglantiadi);

        $liste = glob(self::klasoru() . DIRECTORY_SEPARATOR . '*.php');

        $kayitlilar = [];

        try {
            $satirlar = Veritabani::satirlar('select ad from anka_migrasyonlar', [], $baglantiadi);

            foreach ($satirlar as $satir) {
                $kayitlilar[] = $satir['ad'];
            }
        } catch (Exception $hata) {
            $kayitlilar = [];
        }

        $bekleyen = [];

        foreach ($liste as $dosya) {
            $aktifad = substr(basename($dosya), 0, -4);

            if (!in_array($aktifad, $kayitlilar)) {
                $bekleyen[$aktifad] = $dosya;
            }
        }

        return $bekleyen;
    }

    public static function calistir($baglantiadi = 'varsayilan')
    {
        $bekleyen = self::bekleyen($baglantiadi);

        $sonuclar = [];

        if (!$bekleyen) {
            return $sonuclar;
        }

        $dondu = false;

        try {
            Veritabani::islembaslat ($baglantiadi);

            $dondu = true;

            foreach ($bekleyen as $ad => $dosya) {
                $tanim = include $dosya;

                if (!is_array($tanim)) {
                    $tanim = [];
                }

                $yukarilar = isset($tanim['yukari']) ? $tanim['yukari'] : [];

                foreach ($yukarilar as $kur) {
                    if (!is_string($kur) || trim($kur) === '') {
                        continue;
                    }

                    Veritabani::calistir($kur, [], $baglantiadi);
                }

                if (Veritabani::surucuturu ($baglantiadi) === 'sqlite') {
                    Veritabani::calistir('insert into anka_migrasyonlar (ad, uygulanma_vakti) values (?, current_timestamp)', [$ad], $baglantiadi);
                } else {
                    Veritabani::calistir('insert into anka_migrasyonlar (ad, uygulanma_vakti) values (?, now())', [$ad], $baglantiadi);
                }

                $sonuclar[] = $ad;
            }

            Veritabani::islemonayla ($baglantiadi);
        } catch (Exception $hata) {
            if ($dondu) {
                Veritabani::islemgerial ($baglantiadi);
            }

            throw $hata;
        }

        return $sonuclar;
    }

    public static function gerial ($ad = null, $baglantiadi = 'varsayilan')
    {
        self::tablohazirla($baglantiadi);

        $satirlar = Veritabani::satirlar('select ad from anka_migrasyonlar order by id desc', [], $baglantiadi);

        $liste = [];

        foreach ($satirlar as $satir) {
            if ($ad === null || $satir['ad'] === $ad) {
                $liste[] = $satir['ad'];
            }
        }

        $geridonenler = [];

        if (!$liste) {
            return $geridonenler;
        }

        $dondu = false;

        try {
            Veritabani::islembaslat ($baglantiadi);

            $dondu = true;

            foreach ($liste as $kayit) {
                $dosya = self::dosyaara($kayit);

                if (!$dosya) {
                    continue;
                }

                $tanim = include $dosya;

                if (!is_array($tanim)) {
                    $tanim = [];
                }

                $asagilar = isset($tanim['asagi']) ? $tanim['asagi'] : [];

                foreach ($asagilar as $kur) {
                    if (!is_string($kur) || trim($kur) === '') {
                        continue;
                    }

                    Veritabani::calistir($kur, [], $baglantiadi);
                }

                Veritabani::calistir('delete from anka_migrasyonlar where ad = ?', [$kayit], $baglantiadi);

                $geridonenler[] = $kayit;
            }

            Veritabani::islemonayla ($baglantiadi);
        } catch (Exception $hata) {
            if ($dondu) {
                Veritabani::islemgerial ($baglantiadi);
            }

            throw $hata;
        }

        return $geridonenler;
    }

    public static function dosyaara($ad)
    {
        foreach (glob(self::klasoru() . DIRECTORY_SEPARATOR . '*.php') as $dosya) {
            $aktifad = substr(basename($dosya), 0, -4);

            if ($aktifad === $ad) {
                return $dosya;
            }
        }

        return null;
    }
}