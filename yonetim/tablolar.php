<?php

$baglantiadi = ankaaktifbaglanti ();

$baglantiyok = !$baglantiadi || !Veritabani::ayarlarioku($baglantiadi);

$islem = ankagirdi ('islem', ankasorgu ('islem'));

if ($islem && !$baglantiyok) {
    try {
        $tablo = trim(ankagirdi ('tablo', ankasorgu ('tablo', '')));

        if ($islem === 'temizle' && $tablo !== '') {
            Schema::tablotemizle ($tablo, $baglantiadi);

            ankabildirim ($tablo . ' tablosu boşaltıldı');
        } elseif ($islem === 'tablosil' && $tablo !== '') {
            Schema::tablosil ($tablo, $baglantiadi);

            ankabildirim ($tablo . ' tablosu silindi');
        } elseif ($islem === 'tabloekle') {
            $ad = trim(ankagirdi ('tablo_ad', ''));

            if ($ad === '') {
                throw new Exception('tablo adı gerekli');
            }

            Schema::olustur(
                $ad,
                [
                    ['ad' => 'id', 'tip' => 'bigint', 'birincil' => true, 'otomatik' => true],
                    ['ad' => 'olusturulma_zamani', 'tip' => 'datetime', 'boskalabilir' => true, 'varsayilan' => 'CURRENT_TIMESTAMP'],
                    ['ad' => 'guncellenme_zamani', 'tip' => 'datetime', 'boskalabilir' => true]
                ],
                ['birincil' => ['id']],
                $baglantiadi
            );

            ankabildirim ($ad . ' tablosu oluşturuldu');
        }
    } catch (Exception $hata) {
        ankabildirim ($hata->getMessage(), 'hata');
    }

    ankayonlendir ('?sayfa=tablolar');
}

$tablolar = [];

$surucu = '';

if (!$baglantiyok) {
    $tablolar = Schema::tablolar($baglantiadi);

    $surucu = Veritabani::surucuturu ($baglantiadi);
}
?>
<?php if ($baglantiyok): ?>

    <div class="kart">
        <div class="bildirim bildirim-bilgi">Önce bir veritabanı bağlantısı eklemelisin</div>
        <a href="?sayfa=baglantilar" class="buton butonAna"><i class="fa-solid fa-plus"></i> bağlantılar sayfasına git</a>
    </div>

<?php else: ?>

    <div class="kart">
        <div class="kartBaşlık">
            <h3><i class="fa-solid fa-table"></i> tablolar <small>(<?php echo count($tablolar); ?> adet &middot; <?php echo $surucu; ?>)</small></h3>
        </div>

        <?php if (!$tablolar): ?>
            <div class="bildirim bildirim-bilgi">Veritabanında tablo yok, aşağıdan ilkini oluştur</div>
        <?php endif; ?>

        <div class="tabloSarıcı">
            <table class="veriTablo">
                <thead>
                    <tr>
                        <th>tablo</th>
                        <th>sütun</th>
                        <th>kayıt</th>
                        <th class="hizalıSağ">işlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tablolar as $tablo): ?>
                        <?php
                        $sutunsayisi = 0;

                        $kayitsayisi = 0;

                        try {
                            $sutunsayisi = count(Schema::sutunlar($tablo, $baglantiadi));

                            $kayitsayisi = Sorgu::tablo($tablo, $baglantiadi)->say();
                        } catch (Exception $hata) {
                            $kayitsayisi = null;
                        }
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($tablo); ?></strong></td>
                            <td><?php echo $sutunsayisi; ?></td>
                            <td><?php echo $kayitsayisi === null ? '?' : number_format($kayitsayisi, 0, ',', '.'); ?></td>
                            <td class="hizalıSağ">
                                <a href="?sayfa=tablo&tablo=<?php echo urlencode($tablo); ?>&seki=veri" class="buton butonKüçük"><i class="fa-solid fa-table"></i> veri</a>
                                <a href="?sayfa=tablo&tablo=<?php echo urlencode($tablo); ?>&seki=yapi" class="buton butonKüçük"><i class="fa-solid fa-sliders"></i> yapı</a>
                                <form method="post" class="satırİçiForm" data-onay="<?php echo htmlspecialchars($tablo . ' tablosunun tüm verisini silecek, emin misin?'); ?>" title="boşalt">
                                    <input type="hidden" name="islem" value="temizle">
                                    <input type="hidden" name="tablo" value="<?php echo htmlspecialchars($tablo); ?>">
                                    <button type="submit" class="buton butonKüçük"><i class="fa-solid fa-trash"></i> boşalt</button>
                                </form>
                                <form method="post" class="satırİçiForm" data-onay="<?php echo htmlspecialchars($tablo . ' tablosu kalıcı olarak silinecek, emin misin?'); ?>">
                                    <input type="hidden" name="islem" value="tablosil">
                                    <input type="hidden" name="tablo" value="<?php echo htmlspecialchars($tablo); ?>">
                                    <button type="submit" class="buton butonKüçük butonTehlikeli"><i class="fa-solid fa-trash-can"></i> sil</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="kart">
        <div class="kartBaşlık">
            <h3><i class="fa-solid fa-plus"></i> yeni tablo oluştur</h3>
        </div>

        <form method="post" class="satırİçiForm satırGeniş">
            <input type="hidden" name="islem" value="tabloekle">
            <input type="text" name="tablo_ad" class="metinAlanı" placeholder="tablo adı" value="">
            <button type="submit" class="buton butonAna"><i class="fa-solid fa-plus"></i> oluştur</button>
        </form>

        <p class="küçükNot">id, olusturulma_zamani ve guncellenme_zamani sütunlarıyla hazır gelir.</p>
    </div>

<?php endif; ?>
