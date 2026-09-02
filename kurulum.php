<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'anka.php';

$ayarlar = ankaayarlari ();

if (!empty($ayarlar['admin_parola'])) {
    ankayonlendir ('index.php');
}

$hata = null;

$kaydedildi = false;

if (ankagirdi ('gonder')) {
    $kullanici = trim(ankagirdi ('kullanici', ''));

    $parola = (string) ankagirdi ('parola', '');

    $parolatekrar = (string) ankagirdi ('parola_tekrar', '');

    $surucu = ankagirdi ('surucu', 'mysql');

    $sunucu = ankagirdi ('sunucu', 'localhost');

    $port = ankagirdi ('port', '3306');

    $veritabani = ankagirdi ('veritabaniadi', '');

    $dbkullanici = ankagirdi ('dbkullanici', '');

    $dbparola = ankagirdi ('dbparola', '');

    $veri = [];

    if ($kullanici === '' || $parola === '') {
        $hata = 'kullanıcı adı ve parola zorunludur';
    } elseif ($parola !== $parolatekrar) {
        $hata = 'parolalar birbiriyle eşleşmiyor';
    } else {
        $veri = [
            'admin_kullanici' => $kullanici,
            'admin_parola' => password_hash($parola, PASSWORD_DEFAULT),
            'kurulum_tarihi' => date('Y-m-d H:i:s'),
        ];

        if (ankagirdi ('baglanti_kaydet') === '1' && $veritabani !== '') {
            $ayar = [
                'surucu' => $surucu,
                'sunucu' => $sunucu,
                'port' => $port,
                'veritabaniadi' => $veritabani,
                'kullaniciadi' => $dbkullanici,
                'parola' => $dbparola,
                'karakter' => 'utf8mb4',
            ];

            list($basarili, $mesaj) = Veritabani::baglantitest ($ayar);

            if (!$basarili) {
                $hata = 'veritabanı bağlantısı kurulamadı: ' . $mesaj;
            } else {
                Veritabani::kaydet($ayar, 'varsayilan');

                ankaoturum ('aktifbaglanti', 'varsayilan');
            }
        } elseif ($veritabani !== '' && ankagirdi ('baglanti_kaydet') !== '1') {
            $ayar = [
                'surucu' => $surucu,
                'sunucu' => $sunucu,
                'port' => $port,
                'veritabaniadi' => $veritabani,
                'kullaniciadi' => $dbkullanici,
                'parola' => $dbparola,
                'karakter' => 'utf8mb4',
            ];

            Veritabani::kaydet($ayar, 'varsayilan');

            ankaoturum ('aktifbaglanti', 'varsayilan');
        }

        if ($hata === null) {
            ankaayarlariguncelle ($veri);

            ankaklasorac (ANKA_VERI . DIRECTORY_SEPARATOR . 'yedekler');

            ankaklasorac (ANKA_VERI . DIRECTORY_SEPARATOR . 'migrasyonlar');

            $kaydedildi = true;
        }
    }
}

if ($kaydedildi) {
    ankayonlendir ('index.php');
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Anka DB Manager - Kurulum</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="gorsel/stil.css">
</head>
<body class="kurulumSayfasi">

<div class="kurulumKutusu">
    <div class="kurulumLogo">
        <span class="logoIsaret"><i class="fa-solid fa-database"></i></span>
        <h1>Anka <span>DB</span> Manager</h1>
        <p>kurulum sihirbazı</p>
    </div>

    <?php if ($hata !== null): ?>
        <div class="bildirim bildirim-hata"><?php echo htmlspecialchars($hata); ?></div>
    <?php endif; ?>

    <form method="post" class="kurulumForm">
        <div class="kartBaslik">yönetici hesabı</div>

        <label class="alan">kullanıcı adı
            <input type="text" name="kullanici" value="<?php echo htmlspecialchars(ankagirdi ('kullanici', 'admin')); ?>" autocomplete="off">
        </label>

        <label class="alan">parola
            <input type="password" name="parola" autocomplete="new-password">
        </label>

        <label class="alan">parola tekrar
            <input type="password" name="parola_tekrar" autocomplete="new-password">
        </label>

        <div class="kartBaslik">veritabanı bağlantısı</div>

        <label class="alan">sürücü
            <select name="surucu">
                <option value="mysql" <?php echo ankagirdi ('surucu', 'mysql') === 'mysql' ? 'selected' : ''; ?>>MySQL / MariaDB</option>
                <option value="pgsql" <?php echo ankagirdi ('surucu') === 'pgsql' ? 'selected' : ''; ?>>PostgreSQL</option>
                <option value="sqlite" <?php echo ankagirdi ('surucu') === 'sqlite' ? 'selected' : ''; ?>>SQLite</option>
                <option value="sqlsrv" <?php echo ankagirdi ('surucu') === 'sqlsrv' ? 'selected' : ''; ?>>SQL Server</option>
            </select>
        </label>

        <div class="alanSatiri">
            <label class="alan">sunucu
                <input type="text" name="sunucu" value="<?php echo htmlspecialchars(ankagirdi ('sunucu', 'localhost')); ?>">
            </label>

            <label class="alan alanDar">port
                <input type="text" name="port" value="<?php echo htmlspecialchars(ankagirdi ('port', '3306')); ?>">
            </label>
        </div>

        <label class="alan">veritabanı adı <small>sqlite için dosya yolu</small>
            <input type="text" name="veritabaniadi" value="<?php echo htmlspecialchars(ankagirdi ('veritabaniadi', '')); ?>">
        </label>

        <div class="alanSatiri">
            <label class="alan">kullanıcı
                <input type="text" name="dbkullanici" value="<?php echo htmlspecialchars(ankagirdi ('dbkullanici', '')); ?>">
            </label>

            <label class="alan">parola
                <input type="password" name="dbparola">
            </label>
        </div>

        <label class="isaretle">
            <input type="checkbox" name="baglanti_kaydet" value="1" checked>
            bağlantıyı test et ve kaydet
        </label>

        <button type="submit" name="gonder" value="1" class="buton butonAna"><i class="fa-solid fa-rocket"></i> kurulumu tamamla</button>
    </form>
</div>

</body>
</html>