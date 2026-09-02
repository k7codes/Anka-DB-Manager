<?php

$kurulum = ankaayarlari ();

$tumbaglantilar = Veritabani::ayarlarioku();

$aktifbaglanti = ankaaktifbaglanti ();

$menuler = [
    'anasayfa' => ['Genel Bakış', 'gauge'],
    'tablolar' => ['Tablolar', 'table-cells'],
    'gezgin' => ['Veritabanı Gezgini', 'sitemap'],
    'er' => ['ER Diyagramı', 'diagram-project'],
    'fark' => ['Şema Karşılaştır', 'code-compare'],
    'sorgu' => ['SQL Konsolu', 'terminal'],
'kanvas' => ['6D Kanvas', 'cubes'],
    'zeuger' => ['Veri Okyanusu', 'water'],
    'crypter' => ['Crypter', 'shield-halved'],
    'migrasyonlar' => ['Migrasyonlar', 'arrows-rotate'],
    'yedekleme' => ['Yedekleme', 'database'],
    'enjeksiyon' => ['Proje Enjeksiyonu', 'syringe'],
    'baglantilar' => ['Bağlantılar', 'link'],
    'yapilandirma' => ['Ayarlar', 'gears'],
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Anka DB Manager</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Dancing+Script:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="gorsel/stil.css?v=25">
<script src="gorsel/three.min.js?v=25"></script>
</head>
<body>

<div class="uygulama">

    <aside class="kenarCubugu">
        <div class="kenarLogo">
            <span class="logoIsaret"><i class="fa-solid fa-database"></i></span>
            <div>
                <strong>Anka <em>DB</em></strong>
                <small>yönetim paneli</small>
            </div>
        </div>

        <nav class="kenarMenü">
            <?php foreach ($menuler as $adres => $bilgi): ?>
                <a href="?sayfa=<?php echo $adres; ?>" class="<?php echo $sayfa === $adres ? 'aktif' : ''; ?>">
                    <i class="fa-solid fa-<?php echo $bilgi[1]; ?> menüSimge"></i>
                    <?php echo $bilgi[0]; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="kenarAlt">
            <a href="?sayfa=cikis" class="cikisBağı"><i class="fa-solid fa-right-from-bracket"></i> çıkış yap</a>
        </div>
    </aside>

    <main class="içerik">

        <header class="üstBar">
            <div class="üstBaşlık">
                <h1><?php echo htmlspecialchars($menuler[$sayfa][0] ?? 'Anka'); ?></h1>
                <span class="sürümRozeti">v<?php echo ANKA_SURUM; ?></span>
            </div>

            <div class="üstBilgi">
                <span class="canliSaat" id="canliSaat"></span>

                <?php if ($aktifbaglanti): ?>
                    <a href="?sayfa=baglantilar" class="bağlantıRozeti" title="aktif bağlantı">
                        <span class="durumNoktası"></span>
                        <i class="fa-solid fa-database"></i>
                        <?php echo htmlspecialchars($aktifbaglanti); ?>
                    </a>
                <?php else: ?>
                    <a href="?sayfa=baglantilar" class="bağlantıRozeti bağlantıYok"><i class="fa-solid fa-triangle-exclamation"></i> bağlantı yok</a>
                <?php endif; ?>

                <span class="kullanıcıAdı"><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars((string) ankaoturum ('kullaniciadi')); ?></span>
            </div>
        </header>

        <div class="sayfaAlani">
            <?php echo ankabildirimgoster (); ?>

            <?php echo $icerik; ?>
        </div>

        <footer class="altÇizgi">
            <span><i class="fa-solid fa-shield-halved"></i> Anka DB Manager &middot; v<?php echo ANKA_SURUM; ?></span>
            <span><i class="fa-solid fa-layer-group"></i> PHP <?php echo PHP_VERSION; ?></span>
            <span><i class="fa-solid fa-cube"></i> <?php echo ANKA_GOMULU ? 'gömülü mod' : 'bağımsız mod'; ?></span>
        </footer>

    </main>

</div>

<script src="gorsel/arac.js?v=25"></script>
<?php if ($sayfa === 'kanvas'): ?>
<script src="gorsel/kanvas.js?v=25"></script>
<?php elseif ($sayfa === 'zeuger'): ?>
<script src="gorsel/zeuger.js?v=25"></script>
<?php endif; ?>
</body>
</html>
