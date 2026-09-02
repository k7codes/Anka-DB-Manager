<?php

$ayarlar = ankaayarlari ();

$kurulu = !empty($ayarlar['admin_parola']);

if ($kurulu && !Yetki::girislimi()) {
    ankayonlendir ('?sayfa=giris');
}

$hata = null;

if (ankagirdi ('gonder')) {
    $kullanici = trim(ankagirdi ('kullanici', ''));

    $parola = (string) ankagirdi ('parola', '');

    $parolatekrar = (string) ankagirdi ('parola_tekrar', '');

    if ($kullanici === '' || $parola === '') {
        $hata = 'kullanıcı adı ve parola zorunludur';
    } elseif ($parola !== $parolatekrar) {
        $hata = 'parolalar birbiriyle eşleşmiyor';
    } else {
        $ayarlar['admin_kullanici'] = $kullanici;

        $ayarlar['admin_parola'] = password_hash($parola, PASSWORD_DEFAULT);

        $ayarlar['kurulum_tarihi'] = isset($ayarlar['kurulum_tarihi']) ? $ayarlar['kurulum_tarihi'] : date('Y-m-d H:i:s');

        ankaayarlariguncelle ($ayarlar);

        if (Yetki::girislimi()) {
            ankaoturum ('kullaniciadi', $kullanici);
        }

        ankabildirim ('ayarlar güncellendi');

        ankayonlendir ('?sayfa=anasayfa');
    }
}

$hedefveri = ANKA_VERI;

$yazilabilir = is_dir($hedefveri) ? is_writable($hedefveri) : is_writable(ANKA_YOL);
?>
<div class="izgaraİki">

    <div class="kart">
        <div class="kartBaşlık">
            <h3><?php echo $kurulu ? 'yönetici hesabını değiştir' : 'ilk adım: yönetici hesabı oluştur'; ?></h3>
        </div>

        <?php if ($hata !== null): ?>
            <div class="bildirim bildirim-hata"><?php echo htmlspecialchars($hata); ?></div>
        <?php endif; ?>

        <form method="post" class="formIzgara">
            <label class="alan">kullanıcı adı
                <input type="text" name="kullanici" value="<?php echo htmlspecialchars($ayarlar['admin_kullanici'] ?? ''); ?>">
            </label>

            <label class="alan">yeni parola
                <input type="password" name="parola">
            </label>

            <label class="alan">parola tekrar
                <input type="password" name="parola_tekrar">
            </label>

            <div class="formEylemler">
                <button type="submit" name="gonder" value="1" class="buton butonAna">kaydet</button>
            </div>
        </form>
    </div>

    <div class="kart">
        <div class="kartBaşlık">
            <h3><i class="fa-solid fa-gears"></i> sistem bilgisi</h3>
        </div>

        <ul class="bilgiListesi">
            <li><span>sürüm</span><strong><?php echo ANKA_SURUM; ?></strong></li>
            <li><span>mod</span><strong><?php echo ANKA_GOMULU ? 'gömülü (enjekte edilmiş)' : 'bağımsız'; ?></strong></li>
            <li><span>php</span><strong><?php echo PHP_VERSION; ?></strong></li>
            <li><span>sunucu</span><strong><?php echo isset($_SERVER['SERVER_SOFTWARE']) ? htmlspecialchars($_SERVER['SERVER_SOFTWARE']) : 'yerel'; ?></strong></li>
            <li><span>veri klasörü</span><strong><?php echo htmlspecialchars($hedefveri); ?></strong></li>
            <li><span>yazma izni</span><strong class="<?php echo $yazilabilir ? 'olguİyi' : 'olguKötü'; ?>"><?php echo $yazilabilir ? 'açık' : 'dosya kopyalanamaz'; ?></strong></li>
            <li><span>kurulum</span><strong><?php echo isset($ayarlar['kurulum_tarihi']) ? htmlspecialchars($ayarlar['kurulum_tarihi']) : '-'; ?></strong></li>
        </ul>

        <div class="bildirim bildirim-bilgi">
            gömülü paneller, veritabanı bağlantısını <a href="?sayfa=baglantilar">bağlantılar</a> sayfasından o projenin veritabanına göre kurar.
        </div>
    </div>

</div>

<div class="kart">
    <div class="kartBaşlık">
        <h3>güvenlik notları</h3>
    </div>

    <ul class="yönergeler">
        <li>parola hash'li tutulur: <code><?php echo isset($ayarlar['admin_parola']) ? substr($ayarlar['admin_parola'], 0, 14) . '...' : 'henüz yok'; ?></code></li>
        <li><code>veri/</code> klasörü <code>.htaccess</code> ile korunur; bağlantı bilgileri orada json olarak durur</li>
        <li>enjeksiyon işlemi hedef projeye <code>ankamanager/</code> klasörü ve bir satırlık başlatıcı ekler; kaldırmak için o satırı ve klasörü silmen yeterli</li>
        <li>üretim sunucusunda yönetici sayfaları için ek kimlik doğrulama katmanı önerilir</li>
    </ul>
</div>