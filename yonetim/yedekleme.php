<?php

$baglantiadi = ankaaktifbaglanti ();

$baglantiyok = !$baglantiadi || !Veritabani::ayarlarioku($baglantiadi);

$islem = ankagirdi ('islem', ankasorgu ('islem'));

if ($islem) {
    try {
        if ($islem === 'yedek_al') {
            $secilenler = ankagirdi ('tablolar');

            if (!is_array($secilenler) || !$secilenler) {
                $secilenler = Schema::tablolar($baglantiadi);
            }

            $icerik = Schema::yedekal ($secilenler, $baglantiadi);

            header('Content-Type: application/sql; charset=utf-8');

            header('Content-Disposition: attachment; filename="anka_yedek_' . date('Ymd_His') . '.sql"');

            echo $icerik;

            exit;
        } elseif ($islem === 'kayit_yedek') {
            $secilenler = ankagirdi ('tablolar');

            if (!is_array($secilenler) || !$secilenler) {
                $secilenler = Schema::tablolar($baglantiadi);
            }

            ankaklasorac (ANKA_YEDEK);

            $ad = 'yedek_' . date('Ymd_His') . '.sql';

            file_put_contents(ANKA_YEDEK . DIRECTORY_SEPARATOR . $ad, Schema::yedekal ($secilenler, $baglantiadi));

            ankabildirim ($ad . ' sunucuya kaydedildi');
        } elseif ($islem === 'geri_yukle') {
            $secilenler = ankagirdi ('tablolar');

            if (empty($_FILES['dosya']['tmp_name']) && (!is_array($secilenler) || !$secilenler)) {
                throw new Exception('bir yedek dosyası seç');
            }

            $icerik = '';

            if (!empty($_FILES['dosya']['tmp_name'])) {
                $icerik = file_get_contents($_FILES['dosya']['tmp_name']);
            } else {
                $a = array_keys($secilenler)[0];

                $dosya = ANKA_YEDEK . DIRECTORY_SEPARATOR . basename($a);

                if (!is_file($dosya)) {
                    throw new Exception('kayıt bulunamadı');
                }

                $icerik = file_get_contents($dosya);
            }

            if ($icerik === '') {
                throw new Exception('yedek içeriği boş');
            }

            $adet = Schema::geriyukle ($icerik, $baglantiadi);

            ankabildirim ($adet . ' sql ifadesi uygulandı');
        } elseif ($islem === 'kayit_indir') {
            $ad = ankagirdi ('ad', '');

            $dosya = ANKA_YEDEK . DIRECTORY_SEPARATOR . basename($ad);

            if (!is_file($dosya)) {
                throw new Exception('yedek bulunamadı');
            }

            header('Content-Type: application/sql; charset=utf-8');

            header('Content-Disposition: attachment; filename="' . basename($ad) . '"');

            echo file_get_contents($dosya);

            exit;
        } elseif ($islem === 'kayit_sil') {
            $ad = ankagirdi ('ad', '');

            $dosya = ANKA_YEDEK . DIRECTORY_SEPARATOR . basename($ad);

            if (is_file($dosya)) {
                unlink($dosya);

                ankabildirim ('yedek silindi');
            }
        }
    } catch (Exception $hata) {
        ankabildirim ($hata->getMessage(), 'hata');
    }

    ankayonlendir ('?sayfa=yedekleme');
}

$kayitliyedekler = [];

if (is_dir(ANKA_YEDEK)) {
    $dosyalar = glob(ANKA_YEDEK . DIRECTORY_SEPARATOR . '*.sql');

    rsort($dosyalar);

    $kayitliyedekler = $dosyalar;
}
?>
<?php if ($baglantiyok): ?>

    <div class="kart">
        <div class="bildirim bildirim-bilgi">Önce bir veritabanı bağlantısı eklemelisin</div>
        <a href="?sayfa=baglantilar" class="buton butonAna"><i class="fa-solid fa-plug"></i> bağlantılar sayfasına git</a>
    </div>

<?php else: ?>

    <?php $tablolar = Schema::tablolar($baglantiadi); ?>

    <div class="kart">
        <div class="kartBaşlık">
            <h3><i class="fa-solid fa-database"></i> yeni yedek al</h3>
            <span class="küçükNot">seçili tabloların şema ve veri dökümü</span>
        </div>

        <form method="post" class="yerelForm">
            <input type="hidden" name="islem" value="yedek_al">

            <div class="işaretListesi">
                <?php foreach ($tablolar as $tablo): ?>
                    <label class="işaretle">
                        <input type="checkbox" name="tablolar[]" value="<?php echo htmlspecialchars($tablo); ?>" checked>
                        <?php echo htmlspecialchars($tablo); ?>
                    </label>
                <?php endforeach; ?>

                <?php if (!$tablolar): ?>
                    <span class="boşMesaj">tablo yok</span>
                <?php endif; ?>
            </div>

            <div class="formEylemler">
                <button type="submit" class="buton butonAna"><i class="fa-solid fa-download"></i> indir (sql)</button>
            </div>
        </form>

        <form method="post" class="yerelForm">
            <input type="hidden" name="islem" value="kayit_yedek">

            <div class="formEylemler">
                <button type="submit" class="buton"><i class="fa-solid fa-server"></i> sunucuya kaydet</button>
            </div>
        </form>
    </div>

    <div class="kart">
        <div class="kartBaşlık">
            <h3><i class="fa-solid fa-rotate"></i> geri yükle</h3>
        </div>

        <form method="post" enctype="multipart/form-data" class="yerelForm">
            <input type="hidden" name="islem" value="geri_yukle">

            <input type="file" name="dosya" class="dosyaAlanı">

            <div class="formEylemler">
                <button type="submit" class="buton butonAna butonTehlikeli" data-onay-var="yedek geri yüklemek mevcut veriyi değiştirir, emin misin?"><i class="fa-solid fa-rotate"></i> geri yükle</button>
            </div>
        </form>
    </div>

    <div class="kart">
        <div class="kartBaşlık">
            <h3><i class="fa-solid fa-floppy-disk"></i> sunucuda kayıtlı yedekler</h3>
        </div>

        <?php if (!$kayitliyedekler): ?>
            <div class="bildirim bildirim-bilgi">Henüz kayıtlı yedek yok</div>
        <?php else: ?>
            <div class="bağlantıListesi">
                <?php foreach ($kayitliyedekler as $dosya): ?>
                    <?php $ad = basename($dosya); ?>
                    <div class="bağlantıKartı">
                        <div class="bağlantıBilgi">
                            <strong><?php echo htmlspecialchars($ad); ?></strong>
                            <small><?php echo number_format(filesize($dosya), 0, ',', '.') . ' bayt'; ?></small>
                        </div>

                        <div class="bağlantıİşlemler">
                            <form method="post" class="satırİçiForm">
                                <input type="hidden" name="islem" value="geri_yukle">
                                <input type="hidden" name="tablolar[]" value="<?php echo htmlspecialchars($ad); ?>">
                                <button type="submit" class="buton butonKüçük"><i class="fa-solid fa-rotate"></i> geri yükle</button>
                            </form>

                            <a href="?sayfa=yedekleme&islem=kayit_indir&ad=<?php echo urlencode($ad); ?>" class="buton butonKüçük"><i class="fa-solid fa-download"></i> indir</a>

                            <form method="post" class="satırİçiForm" data-onay="bu yedek dosyası silinecek, emin misin?">
                                <input type="hidden" name="islem" value="kayit_sil">
                                <input type="hidden" name="ad" value="<?php echo htmlspecialchars($ad); ?>">
                                <button type="submit" class="buton butonKüçük butonTehlikeli"><i class="fa-solid fa-trash-can"></i> sil</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<?php endif; ?>
