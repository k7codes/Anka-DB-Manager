<?php

class Schema
{
    public static function tablolar($baglantiadi = 'varsayilan')
    {
        $surucu = Veritabani::surucuturu ($baglantiadi);

        $satirlar = [];

        if ($surucu === 'pgsql') {
            $satirlar = Veritabani::satirlar("select tablename as ad from pg_tables where schemaname = 'public' order by tablename asc", [], $baglantiadi);
        } elseif ($surucu === 'sqlite') {
            $satirlar = Veritabani::satirlar("select name as ad from sqlite_master where type = 'table' and name not like 'sqlite_%' order by name asc", [], $baglantiadi);
        } else {
            $satirlar = Veritabani::satirlar('show tables', [], $baglantiadi);
        }

        $liste = [];

        foreach ($satirlar as $satir) {
            $deger = array_values($satir)[0];

            $liste[] = $deger;
        }

        return $liste;
    }

    public static function sutunlar($tabloadi, $baglantiadi = 'varsayilan')
    {
        $surucu = Veritabani::surucuturu ($baglantiadi);

        $sutunlar = [];

        if ($surucu === 'mysql') {
            $satirlar = Veritabani::satirlar('show full columns from ' . Veritabani::tirnakla($tabloadi, $baglantiadi), [], $baglantiadi);

            foreach ($satirlar as $satir) {
                $sutunlar[] = [
                    'ad' => $satir['Field'],
                    'tip' => $satir['Type'],
                    'boskalabilir' => strtoupper($satir['Null']) === 'YES',
                    'birincil' => isset($satir['Key']) && $satir['Key'] === 'PRI',
                    'varsayilan' => $satir['Default'],
                    'otomatik' => strpos(isset($satir['Extra']) ? $satir['Extra'] : '', 'auto_increment') !== false,
                    'aciklama' => isset($satir['Comment']) ? $satir['Comment'] : '',
                    'anahtar' => isset($satir['Key']) ? $satir['Key'] : '',
                ];
            }
        } elseif ($surucu === 'pgsql') {
            $sql = "select column_name as ad, data_type as tip, is_nullable, coalesce(character_maximum_length, numeric_precision) as uzunluk, column_default as varsayilan from information_schema.columns where table_name = ? order by ordinal_position asc";

            $satirlar = Veritabani::satirlar($sql, [$tabloadi], $baglantiadi);

            $birinciller = self::pgsqlbirincilalanlar($tabloadi, $baglantiadi);

            foreach ($satirlar as $satir) {
                $sutunlar[] = [
                    'ad' => $satir['ad'],
                    'tip' => $satir['tip'],
                    'boskalabilir' => strtolower($satir['is_nullable']) === 'yes',
                    'birincil' => in_array($satir['ad'], $birinciller),
                    'varsayilan' => $satir['varsayilan'],
                    'otomatik' => strpos((string) $satir['varsayilan'], 'nextval') !== false,
                    'aciklama' => '',
                    'anahtar' => in_array($satir['ad'], $birinciller) ? 'PRI' : '',
                ];
            }
        } else {
            $satirlar = Veritabani::satirlar('pragma table_info(' . Veritabani::tirnakla($tabloadi, $baglantiadi) . ')', [], $baglantiadi);

            foreach ($satirlar as $satir) {
                $sutunlar[] = [
                    'ad' => $satir['name'],
                    'tip' => $satir['type'],
                    'boskalabilir' => (int) $satir['notnull'] === 0,
                    'birincil' => (int) $satir['pk'] === 1,
                    'varsayilan' => $satir['dflt_value'],
                    'otomatik' => (int) $satir['pk'] === 1,
                    'aciklama' => '',
                    'anahtar' => (int) $satir['pk'] === 1 ? 'PRI' : '',
                ];
            }
        }

        return $sutunlar;
    }

    private static function pgsqlbirincilalanlar($tabloadi, $baglantiadi)
    {
        $sql = "select a.attname as alan from pg_index i join pg_attribute a on a.attrelid = i.indrelid and a.attnum = any(i.indkey) where i.indrelid = ?::regclass and i.indisprimary";

        $satirlar = Veritabani::satirlar($sql, [$tabloadi], $baglantiadi);

        $liste = [];

        foreach ($satirlar as $satir) {
            $liste[] = $satir['alan'];
        }

        return $liste;
    }

    public static function foreignkeys ($tabloadi, $baglantiadi = 'varsayilan')
    {
        $liste = [];

        $surucu = Veritabani::surucuturu ($baglantiadi);

        if ($surucu === 'mysql') {
            $veritabaniadi = Veritabani::ayarlarioku($baglantiadi);

            $vtad = isset($veritabaniadi['veritabaniadi']) ? $veritabaniadi['veritabaniadi'] : '';

            $satirlar = Veritabani::satirlar(
                "select k.constraint_name as ad,
                        k.column_name as alan,
                        k.referenced_table_name as hedeftablo,
                        k.referenced_column_name as hedefalan
                 from information_schema.key_column_usage k
                 where k.table_schema = ? and k.table_name = ? and k.referenced_table_name is not null
                 order by k.ordinal_position",
                [$vtad, $tabloadi],
                $baglantiadi
            );

            foreach ($satirlar as $satir) {
                $liste[] = [
                    'ad' => $satir['ad'],
                    'alan' => $satir['alan'],
                    'hedeftablo' => $satir['hedeftablo'],
                    'hedefalan' => $satir['hedefalan'],
                ];
            }
        } elseif ($surucu === 'pgsql') {
            $satirlar = Veritabani::satirlar(
                "select tc.constraint_name as ad,
                        kcu.column_name as alan,
                        ccu.table_name as hedeftablo,
                        ccu.column_name as hedefalan
                 from information_schema.table_constraints tc
                 join information_schema.key_column_usage kcu
                      on tc.constraint_name = kcu.constraint_name and tc.table_schema = kcu.table_schema
                 join information_schema.constraint_column_usage ccu
                      on ccu.constraint_name = tc.constraint_name and ccu.table_schema = tc.table_schema
                 where tc.constraint_type = 'FOREIGN KEY' and tc.table_name = ?
                 order by kcu.ordinal_position",
                [$tabloadi],
                $baglantiadi
            );

            foreach ($satirlar as $satir) {
                $liste[] = [
                    'ad' => $satir['ad'],
                    'alan' => $satir['alan'],
                    'hedeftablo' => $satir['hedeftablo'],
                    'hedefalan' => $satir['hedefalan'],
                ];
            }
        } else {
            $satirlar = Veritabani::satirlar('pragma foreign_key_list(' . Veritabani::tirnakla($tabloadi, $baglantiadi) . ')', [], $baglantiadi);

            foreach ($satirlar as $satir) {
                $liste[] = [
                    'ad' => isset($satir['id']) ? ('fk_' . $tabloadi . '_' . $satir['id']) : 'fk_' . $tabloadi,
                    'alan' => $satir['from'],
                    'hedeftablo' => $satir['table'],
                    'hedefalan' => $satir['to'],
                ];
            }
        }

        return $liste;
    }

    public static function indeksler($tabloadi, $baglantiadi = 'varsayilan')
    {
        $liste = [];

        $surucu = Veritabani::surucuturu ($baglantiadi);

        if ($surucu === 'mysql') {
            $satirlar = Veritabani::satirlar('show index from ' . Veritabani::tirnakla($tabloadi, $baglantiadi), [], $baglantiadi);

            $gruplar = [];

            foreach ($satirlar as $satir) {
                $ad = $satir['Key_name'];

                if (!isset($gruplar[$ad])) {
                    $gruplar[$ad] = ['ad' => $ad, 'alanlar' => [], 'tekil' => (int) $satir['Non_unique'] === 0];
                }

                $gruplar[$ad]['alanlar'][] = $satir['Column_name'];
            }

            $liste = array_values($gruplar);
        } elseif ($surucu === 'pgsql') {
            $satirlar = Veritabani::satirlar('select indexname as ad, indexdef as tanim from pg_indexes where tablename = ? order by indexname asc', [$tabloadi], $baglantiadi);

            foreach ($satirlar as $satir) {
                $liste[] = ['ad' => $satir['ad'], 'tanim' => $satir['tanim']];
            }
        } else {
            $satirlar = Veritabani::satirlar('pragma index_list(' . Veritabani::tirnakla($tabloadi, $baglantiadi) . ')', [], $baglantiadi);

            foreach ($satirlar as $satir) {
                $liste[] = ['ad' => $satir['name'], 'tekil' => (int) $satir['unique'] === 1];
            }
        }

        return $liste;
    }

    public static function tabloyaratsql ($tabloadi, $baglantiadi = 'varsayilan')
    {
        $surucu = Veritabani::surucuturu ($baglantiadi);

        if ($surucu === 'mysql') {
            $satir = Veritabani::ilksatir ('show create table ' . Veritabani::tirnakla($tabloadi, $baglantiadi) . '', [], $baglantiadi);

            if (!$satir) {
                return '';
            }

            $deger = array_values($satir);

            return isset($deger[1]) ? $deger[1] : '';
        }

        if ($surucu === 'sqlite') {
            $satir = Veritabani::ilksatir ("select sql as yazi from sqlite_master where type = 'table' and name = ?", [$tabloadi], $baglantiadi);

            return $satir ? $satir['yazi'] : '';
        }

        $sutunlar = self::sutunlar($tabloadi, $baglantiadi);

        $parcalar = [];

        foreach ($sutunlar as $sutun) {
            $tip = !empty($sutun['otomatik']) ? 'serial' : self::tipdonustur($sutun['tip'], 'pgsql');

            $ifade = Veritabani::tirnakla($sutun['ad'], $baglantiadi) . ' ' . $tip . (empty($sutun['boskalabilir']) ? ' not null' : '');

            if (!empty($sutun['varsayilan']) && $sutun['varsayilan'] !== null && strpos((string) $sutun['varsayilan'], 'nextval') === false) {
                $ifade .= ' default ' . $sutun['varsayilan'];
            }

            $parcalar[] = $ifade;
        }

        $birinciller = self::pgsqlbirincilalanlar($tabloadi, $baglantiadi);

        if ($birinciller) {
            $son = [];

            foreach ($birinciller as $b) {
                $son[] = Veritabani::tirnakla($b, $baglantiadi);
            }

            $parcalar[] = 'primary key (' . implode(', ', $son) . ')';
        }

        return 'create table ' . Veritabani::tirnakla($tabloadi, $baglantiadi) . ' (' . implode(', ', $parcalar) . ');';
    }

    public static function olustur($tabloadi, $sutunlistesi, $ayarlar = [], $baglantiadi = 'varsayilan')
    {
        $surucu = Veritabani::surucuturu ($baglantiadi);

        $parcalar = [];

        foreach ($sutunlistesi as $tanim) {
            if (!isset($tanim['ad'])) {
                throw new Exception('sütun tanımında ad eksik');
            }

            $parcalar[] = self::sutunyaz($tanim, $surucu, $baglantiadi);
        }

        $akis = [];

        $otomatikbirincil = false;

        foreach ($sutunlistesi as $tanim) {
            if (!empty($tanim['otomatik']) && isset($ayarlar['birincil']) && in_array($tanim['ad'], (array) $ayarlar['birincil'])) {
                $otomatikbirincil = true;
            }
        }

        if (isset($ayarlar['birincil']) && $ayarlar['birincil'] && (!$otomatikbirincil || $surucu !== 'sqlite')) {
            $son = [];

            foreach ((array) $ayarlar['birincil'] as $alan) {
                $son[] = Veritabani::tirnakla($alan, $baglantiadi);
            }

            $akis[] = 'primary key (' . implode(', ', $son) . ')';
        }

        $sql = 'create table ' . Veritabani::tirnakla($tabloadi, $baglantiadi) . ' (' . implode(', ', array_merge($parcalar, $akis)) . ')';

        if ($surucu === 'mysql') {
            $sql .= ' engine=InnoDB default charset=utf8mb4';
        }

        Veritabani::calistir($sql, [], $baglantiadi);

        return $sql;
    }

    public static function sutunyaz($tanim, $surucu = 'mysql', $baglantiadi = 'varsayilan')
    {
        $ad = Veritabani::tirnakla($tanim['ad'], $baglantiadi);

        $tip = isset($tanim['tip']) ? $tanim['tip'] : 'text';

        $ifade = $ad . ' ' . self::tipdonustur($tip, $surucu);

        if (!empty($tanim['otomatik']) && $surucu === 'pgsql') {
            return $ad . ' serial';
        }

        if (!empty($tanim['otomatik']) && $surucu === 'sqlite') {
            return $ad . ' integer primary key autoincrement';
        }

        if (isset($tanim['uzunluk']) && $tanim['uzunluk'] && strpos($tip, '(') === false) {
            $ifade .= '(' . (int) $tanim['uzunluk'] . ')';
        }

        $ifade .= empty($tanim['boskalabilir']) ? ' not null' : ' null';

        if (array_key_exists('varsayilan', $tanim) && $tanim['varsayilan'] !== null && $tanim['varsayilan'] !== '') {
            $ifade .= ' default ' . self::varsayilanyaz($tanim['varsayilan'], $surucu, $baglantiadi);
        }

        if ($surucu === 'mysql' && !empty($tanim['otomatik'])) {
            $ifade .= ' auto_increment';
        }

        if (!empty($tanim['tekil'])) {
            $ifade .= ' unique';
        }

        return $ifade;
    }

    public static function tipdonustur($tip, $surucu = 'mysql')
    {
        $tip = strtolower(trim($tip));

        if ($surucu !== 'pgsql') {
            return $tip;
        }

        $harita = [
            'tinyint' => 'smallint',
            'mediumint' => 'integer',
            'bigint' => 'bigint',
            'int' => 'integer',
            'smallint' => 'smallint',
            'varchar' => 'varchar',
            'char' => 'char',
            'text' => 'text',
            'longtext' => 'text',
            'mediumtext' => 'text',
            'tinytext' => 'text',
            'blob' => 'bytea',
            'mediumblob' => 'bytea',
            'longblob' => 'bytea',
            'tinyblob' => 'bytea',
            'datetime' => 'timestamp',
            'timestamp' => 'timestamp',
            'date' => 'date',
            'time' => 'time',
            'double' => 'double precision',
            'float' => 'real',
            'decimal' => 'numeric',
            'boolean' => 'boolean',
        ];

        $parantez = '';

        if (strpos($tip, '(') !== false) {
            $parantez = substr($tip, strpos($tip, '('));

            $tip = trim(substr($tip, 0, strpos($tip, '(')));
        }

        return isset($harita[$tip]) ? $harita[$tip] . $parantez : $tip . $parantez;
    }

    public static function varsayilanyaz($deger, $surucu = 'mysql', $baglantiadi = 'varsayilan')
    {
        $kucuk = strtolower((string) $deger);

        if (in_array($kucuk, ['now()', 'current_timestamp', 'current_date', 'current_time', 'null'])) {
            return $deger;
        }

        if (strpos($kucuk, '(') !== false) {
            return $deger;
        }

        if (strpos($kucuk, '::') !== false) {
            return $deger;
        }

        return Veritabani::baglan($baglantiadi)->quote((string) $deger);
    }

    public static function sutunekle ($tabloadi, $tanim, $baglantiadi = 'varsayilan')
    {
        $surucu = Veritabani::surucuturu ($baglantiadi);

        $sql = 'alter table ' . Veritabani::tirnakla($tabloadi, $baglantiadi) . ' add column ' . self::sutunyaz($tanim, $surucu, $baglantiadi);

        Veritabani::calistir($sql, [], $baglantiadi);

        return $sql;
    }

    public static function sutundegistir ($tabloadi, $eskiad, $tanim, $baglantiadi = 'varsayilan')
    {
        $surucu = Veritabani::surucuturu ($baglantiadi);

        $tablo = Veritabani::tirnakla($tabloadi, $baglantiadi);

        if ($surucu === 'mysql') {
            $sql = 'alter table ' . $tablo . ' change column ' . Veritabani::tirnakla($eskiad, $baglantiadi) . ' ' . self::sutunyaz($tanim, $surucu, $baglantiadi);

            Veritabani::calistir($sql, [], $baglantiadi);

            return $sql;
        }

        $ad = Veritabani::tirnakla($tanim['ad'], $baglantiadi);

        if ($tanim['ad'] !== $eskiad) {
            Veritabani::calistir('alter table ' . $tablo . ' rename column ' . Veritabani::tirnakla($eskiad, $baglantiadi) . ' to ' . $ad, [], $baglantiadi);
        }

        if (!empty($tanim['otomatik'])) {
            return $tanim['ad'];
        }

        $yenitip = self::tipdonustur($tanim['tip'], $surucu);

        Veritabani::calistir('alter table ' . $tablo . ' alter column ' . $ad . ' type ' . $yenitip, [], $baglantiadi);

        $nullifade = empty($tanim['boskalabilir']) ? 'set not null' : 'drop not null';

        Veritabani::calistir('alter table ' . $tablo . ' alter column ' . $ad . ' ' . $nullifade, [], $baglantiadi);

        return $tanim['ad'];
    }

    public static function sutunsil ($tabloadi, $sutunadi, $baglantiadi = 'varsayilan')
    {
        $sql = 'alter table ' . Veritabani::tirnakla($tabloadi, $baglantiadi) . ' drop column ' . Veritabani::tirnakla($sutunadi, $baglantiadi);

        Veritabani::calistir($sql, [], $baglantiadi);

        return $sql;
    }

    public static function tablosil ($tabloadi, $baglantiadi = 'varsayilan')
    {
        $sql = 'drop table ' . Veritabani::tirnakla($tabloadi, $baglantiadi);

        Veritabani::calistir($sql, [], $baglantiadi);

        return $sql;
    }

    public static function tablotemizle ($tabloadi, $baglantiadi = 'varsayilan')
    {
        $surucu = Veritabani::surucuturu ($baglantiadi);

        $sql = $surucu === 'sqlite' ? 'delete from ' . Veritabani::tirnakla($tabloadi, $baglantiadi) : 'truncate table ' . Veritabani::tirnakla($tabloadi, $baglantiadi);

        Veritabani::calistir($sql, [], $baglantiadi);

        return $sql;
    }

    public static function indeksekle ($tabloadi, $indeksadi, $alanlar, $tekil = false, $baglantiadi = 'varsayilan')
    {
        $on = $tekil ? 'unique index' : 'index';

        $son = [];

        foreach ((array) $alanlar as $alan) {
            $son[] = Veritabani::tirnakla($alan, $baglantiadi);
        }

        $sql = 'create ' . $on . ' ' . Veritabani::tirnakla($indeksadi, $baglantiadi) . ' on ' . Veritabani::tirnakla($tabloadi, $baglantiadi) . ' (' . implode(', ', $son) . ')';

        Veritabani::calistir($sql, [], $baglantiadi);

        return $sql;
    }

    public static function indekssil ($tabloadi, $indeksadi, $baglantiadi = 'varsayilan')
    {
        $surucu = Veritabani::surucuturu ($baglantiadi);

        if ($surucu === 'mysql') {
            $sql = 'drop index ' . Veritabani::tirnakla($indeksadi, $baglantiadi) . ' on ' . Veritabani::tirnakla($tabloadi, $baglantiadi);
        } else {
            $sql = 'drop index ' . Veritabani::tirnakla($indeksadi, $baglantiadi);
        }

        Veritabani::calistir($sql, [], $baglantiadi);

        return $sql;
    }

    public static function yedekal ($tablolar, $baglantiadi = 'varsayilan')
    {
        $icerik = '-- anka veritabani yedegi' . "\n";

        $icerik .= '-- ' . date('Y-m-d H:i:s') . "\n\n";

        $pdo = Veritabani::baglan($baglantiadi);

        foreach ($tablolar as $tablo) {
            $icerik .= 'drop table if exists ' . Veritabani::tirnakla($tablo, $baglantiadi) . ';' . "\n";

            $icerik .= self::tabloyaratsql ($tablo, $baglantiadi) . "\n\n";

            $satirlar = Veritabani::satirlar('select * from ' . Veritabani::tirnakla($tablo, $baglantiadi), [], $baglantiadi);

            foreach ($satirlar as $satir) {
                $alanlar = [];

                $degerler = [];

                foreach ($satir as $alan => $deger) {
                    $alanlar[] = Veritabani::tirnakla($alan, $baglantiadi);

                    $degerler[] = $deger === null ? 'null' : $pdo->quote((string) $deger);
                }

                $icerik .= 'insert into ' . Veritabani::tirnakla($tablo, $baglantiadi) . ' (' . implode(', ', $alanlar) . ') values (' . implode(', ', $degerler) . ');' . "\n";
            }

            $icerik .= "\n";
        }

        return $icerik;
    }

    public static function geriyukle ($icerik, $baglantiadi = 'varsayilan')
    {
        $icerik = preg_replace('/--[^\r\n]*/', '', $icerik);

        $parcalar = preg_split('/;\s*(?:\r?\n|$)/', $icerik);

        $adet = 0;

        foreach ($parcalar as $parca) {
            $parca = trim($parca);

            if ($parca === '') {
                continue;
            }

            Veritabani::calistir($parca, [], $baglantiadi);

            $adet++;
        }

        return $adet;
    }

    public static function veritabaniolustur ($ad, $ayar)
    {
        $surucu = isset($ayar['surucu']) ? $ayar['surucu'] : 'mysql';

        if ($surucu === 'sqlite') {
            $yol = isset($ayar['veritabaniadi']) && $ayar['veritabaniadi'] !== '' ? $ayar['veritabaniadi'] : ANKA_VERI . DIRECTORY_SEPARATOR . $ad . '.sqlite';

            if (count(explode('.', $yol)) === 1) {
                $yol .= '.sqlite';
            }

            if (!is_dir(dirname($yol))) {
                ankaklasorac (dirname($yol));
            }

            if (is_file($yol)) {
                throw new Exception('bu adla bir veritabanı zaten var');
            }

            $pdo = new PDO('sqlite:' . $yol);

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $pdo = null;

            return $yol;
        }

        $ad = trim($ad);

        if ($ad === '') {
            throw new Exception('veritabanı adı gerekli');
        }

        if (!preg_match('/^[A-Za-z0-9_]+$/', $ad)) {
            throw new Exception('geçersiz veritabanı adı: sadece harf, rakam ve alt çizgi');
        }

        $pdo = Veritabani::sunucupdo ($ayar);

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($surucu === 'pgsql') {
            $sql = 'create database "' . $ad . '"';
        } else {
            $sql = 'create database ' . $ad . '';
        }

        $pdo->exec($sql);

        $pdo = null;

        return $ad;
    }

    public static function demoveritabaniolustur ($ad, $ayar)
    {
        $yol = self::veritabaniolustur ($ad, $ayar);

        if (isset($ayar['surucu']) && $ayar['surucu'] === 'sqlite') {
            $baglanti = new PDO('sqlite:' . $yol);
            $baglanti->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } else {
            $ayar['veritabaniadi'] = $yol;

            Veritabani::ayarla($ad, $ayar);

            $baglanti = Veritabani::baglan($ad);
        }

        $tbl = 'ürünler';
        $bugun = date('Y-m-d');

        $baglanti->exec('create table ' . $tbl . ' (
            id integer primary key autoincrement,
            ad text not null,
            kategori text not null,
            fiyat real not null,
            stok integer not null,
            eklenme text not null
        )');

        $ornek = [
            ['Siberfare Klavye', 'çevre', 189.99, 34, $bugun],
            ['Neon Mouse M712', 'çevre', 145.50, 21, $bugun],
            ['Algı Monitör 27"', 'görüntü', 2890.00, 8, $bugun],
            ['Işık Matı RGB', 'çevre', 62.99, 47, $bugun],
            ['Terminal Kulaklık', 'ses', 420.75, 12, $bugun],
            ['Anka web kamerası', 'çevre', 310.00, 15, $bugun],
            ['HDR Sarf Ekran', 'görüntü', 1230.40, 5, $bugun],
            ['Kademe Hoparlör', 'ses', 754.60, 9, $bugun],
        ];

        $hazir = $baglanti->prepare('insert into ' . $tbl . ' (ad, kategori, fiyat, stok, eklenme) values (?, ?, ?, ?, ?)');

        foreach ($ornek as $satir) {
            $hazir->execute($satir);
        }

        $baglanti->exec('create table müşteriler (
            id integer primary key autoincrement,
            ad text not null,
            sehir text not null,
            bakiye real not null default 0,
            kayit text not null
        )');

        $musteriler = [
            ['Aylin Karaca', 'İstanbul', 1250.00, $bugun],
            ['Barış Deniz', 'Ankara', 340.25, $bugun],
            ['Ceren Yıldız', 'İzmir', 8900.90, $bugun],
            ['Deniz Tekin', 'Bursa', 120.00, $bugun],
        ];

        $mhazir = $baglanti->prepare('insert into müşteriler (ad, sehir, bakiye, kayit) values (?, ?, ?, ?)');

        foreach ($musteriler as $satir) {
            $mhazir->execute($satir);
        }

        return $yol;
    }
}