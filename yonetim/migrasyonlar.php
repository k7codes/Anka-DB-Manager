<?php

$baglantiadi = ankaaktifbaglanti ();

$baglantiyok = !$baglantiadi || !Veritabani::ayarlarioku($baglantiadi);

$islem = ankagirdi ('islem', ankasorgu ('islem'));

if ($islem && !$baglantiyok) {
    try {
        if ($islem === 'olustur') {
            $ad = trim(ankagirdi ('ad', ''));

            if ($ad === '') {
                throw new Exception('migrasyon adı gerekli');
            }

            $dosya = Migrasyon::olustur($ad);

            ankabildirim ('migrasyon oluşturuldu: ' . basename($dosya));
        } elseif ($islem === 'calistir') {
            $uygulananlar = Migrasyon::calistir($baglantiadi);

            ankabildirim (count($uygulananlar) ? 'uygulanan: ' . implode(', ', $uygulananlar) : 'bekleyen migrasyon yok');
        } elseif ($islem === 'gerial') {
            $ad = ankagirdi ('ad', '');

            $geridonenler = Migrasyon::gerial ($ad !== '' ? $ad : null, $baglantiadi);

            ankabildirim (count($geridonenler) ? 'geri alınan: ' . implode(', ', $geridonenler) : 'geri alınacak migrasyon yok');
        }
    } catch (Exception $hata) {
        ankabildirim ($hata->getMessage(), 'hata');
    }

    ankayonlendir ('?sayfa=migrasyonlar');
}

$bekleyen = [];

$uygulanan = [];

if (!$baglantiyok) {
    $bekleyen = Migrasyon::bekleyen($baglantiadi);

    try {
        $uygulanan = Veritabani::satirlar('select id, ad, uygulanma_vakti from anka_migrasyonlar order by id desc', [], $baglantiadi);
    } catch (Exception $hata) {
        $uygulanan = [];
    }
}
?>
<?php if ($baglantiyok): ?>

    <div class="kart">
        <div class="bildirim bildirim-bilgi">Önce bir veritabanı bağlantısı eklemelisin</div>
        <a href="?sayfa=baglantilar" class="buton butonAna">bağlantılar sayfasına git</a>
    </div>

<?php else: ?>

    <div class="kart">
        <div class="kartBaşlık">
            <h3><i class="fa-solid fa-file-circle-plus"></i> yeni migrasyon</h3>
        </div>

        <form method="post" class="satırİçiForm satırGeniş">
            <input type="hidden" name="islem" value="olustur">
            <input type="text" name="ad" class="metinAlanı" placeholder="orn: kullanicilar_tablosu">
            <button type="submit" class="buton butonAna"><i class="fa-solid fa-file-circle-plus"></i> dosya oluştur</button>
        </form>

        <p class="küçükNot">dosyalar <code><?php echo htmlspecialchars(ANKA_MIGRASYON); ?></code> altında oluşur. içindeki yukari / asagi dizileri sql ifadeleriyle doldurulur.</p>
    </div>

    <div class="kart">
        <div class="kartBaşlık">
            <h3><i class="fa-solid fa-hourglass-half"></i> bekleyen migrasyonlar (<?php echo count($bekleyen); ?>)</h3>

            <form method="post" class="satırİçiForm">
                <input type="hidden" name="islem" value="calistir">
                <button type="submit" class="buton butonAna"><i class="fa-solid fa-play"></i> hepsini çalıştır</button>
            </form>
        </div>

        <?php if (!$bekleyen): ?>
            <div class="bildirim bildirim-bilgi">Bekleyen migrasyon yok</div>
        <?php else: ?>
            <div class="bağlantıListesi">
                <?php foreach ($bekleyen as $ad => $dosya): ?>
                    <div class="bağlantıKartı">
                        <div class="bağlantıBilgi">
                            <strong><?php echo htmlspecialchars($ad); ?></strong>
                            <small>bekliyor</small>
                        </div>
                        <div class="bağlantıİşlemler">
                            <form method="post" class="satırİçiForm">
                                <input type="hidden" name="islem" value="calistir">
                                <button type="submit" class="buton butonKüçük"><i class="fa-solid fa-play"></i> çalıştır</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="kart">
        <div class="kartBaşlık">
            <h3><i class="fa-solid fa-check-double"></i> uygulanan migrasyonlar (<?php echo count($uygulanan); ?>)</h3>
        </div>

        <?php if (!$uygulanan): ?>
            <div class="bildirim bildirim-bilgi">Henüz migrasyon uygulanmamış</div>
        <?php else: ?>
            <div class="bağlantıListesi">
                <?php foreach ($uygulanan as $kayit): ?>
                    <div class="bağlantıKartı">
                        <div class="bağlantıBilgi">
                            <strong><?php echo htmlspecialchars($kayit['ad']); ?></strong>
                            <small><?php echo htmlspecialchars((string) $kayit['uygulanma_vakti']); ?></small>
                        </div>
                        <div class="bağlantıİşlemler">
                            <form method="post" class="satırİçiForm" data-onay="<?php echo htmlspecialchars($kayit['ad'] . ' migrasyonu geri alınacak, emin misin?'); ?>">
                                <input type="hidden" name="islem" value="gerial">
                                <input type="hidden" name="ad" value="<?php echo htmlspecialchars($kayit['ad']); ?>">
                                <button type="submit" class="buton butonKüçük butonTehlikeli"><i class="fa-solid fa-rotate-left"></i> geri al</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<?php endif; ?>
