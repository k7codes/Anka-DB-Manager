<?php

$baglantilar = Veritabani::ayarlarioku();

$aktifbaglanti = ankaaktifbaglanti ();

$verierisilebilir = false;

$tablosayisi = 0;

$kayittoplami = 0;

$hatamesaji = null;

$onecikanlar = [];

if ($aktifbaglanti && isset($baglantilar[$aktifbaglanti])) {
    try {
        $tablolar = Schema::tablolar($aktifbaglanti);

        $tablosayisi = count($tablolar);

        $verierisilebilir = true;

        $onecikanlar = array_slice($tablolar, 0, 6);

        foreach ($onecikanlar as $tablo) {
            $kayittoplami += Sorgu::tablo($tablo, $aktifbaglanti)->say();
        }
    } catch (Exception $hata) {
        $hatamesaji = $hata->getMessage();
    }
}
?>
<div class="kart ızgaraDört">

    <div class="kartİçi">
        <div class="kartRakam"><i class="fa-solid fa-link"></i> <?php echo count($baglantilar); ?></div>
        <div class="kartEtiket">kayıtlı bağlantı</div>
        <div class="kartAlt"><?php echo $aktifbaglanti ? 'aktif: ' . htmlspecialchars($aktifbaglanti) : 'aktif yok'; ?></div>
    </div>

    <div class="kartİçi">
        <div class="kartRakam"><i class="fa-solid fa-table"></i> <?php echo $tablosayisi; ?></div>
        <div class="kartEtiket">toplam tablo</div>
        <div class="kartAlt"><?php echo $verierisilebilir ? 'bağlı veritabanı' : 'şu an erişilemiyor'; ?></div>
    </div>

    <div class="kartİçi">
        <div class="kartRakam"><i class="fa-solid fa-server"></i> <?php echo number_format($kayittoplami, 0, ',', '.'); ?></div>
        <div class="kartEtiket">öne çıkan kayıt</div>
        <div class="kartAlt">örneklem tablolardan</div>
    </div>

    <div class="kartİçi">
        <div class="kartRakam"><i class="fa-solid fa-code-branch"></i> <?php echo ANKA_SURUM; ?></div>
        <div class="kartEtiket">sürüm / php</div>
        <div class="kartAlt">PHP <?php echo PHP_VERSION; ?></div>
    </div>
</div>

<?php if ($verierisilebilir): ?>

    <div class="kart">
        <div class="kartBaşlık">
            <h3>aktif bağlantı: <?php echo htmlspecialchars($aktifbaglanti); ?></h3>
            <a href="?sayfa=tablolar" class="buton butonKüçük"><i class="fa-solid fa-list"></i> tüm tablolar</a>
        </div>

        <div class="tabloSarıcı">
            <table class="veriTablo">
                <thead>
                    <tr>
                        <th>tablo</th>
                        <th>kayıt sayısı</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($onecikanlar as $tablo): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($tablo); ?></strong></td>
                            <td><?php echo number_format(Sorgu::tablo($tablo, $aktifbaglanti)->say(), 0, ',', '.'); ?></td>
                            <td class="hizalıSağ">
                                <a href="?sayfa=tablo&tablo=<?php echo urlencode($tablo); ?>&seki=veri" class="buton butonKüçük"><i class="fa-solid fa-arrow-right"></i> aç</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$onecikanlar): ?>
                        <tr>
                            <td colspan="3" class="boşMesaj">bu veritabanında tablo yok</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($hatamesaji !== null): ?>

    <div class="kart">
        <div class="bildirim bildirim-hata">Bağlantı hatası: <?php echo htmlspecialchars($hatamesaji); ?></div>
        <p>aktif bağlantı kurulamıyor. Bağlantılar sayfasından ayarları kontrol et.</p>
    </div>

<?php else: ?>

    <div class="kart">
        <div class="bildirim bildirim-bilgi">Henüz bir veritabanı bağlantısı eklenmemiş</div>
        <p>Test için tek tıkla örnek verili demo veritabanını kur ve bağla, ya da kendi bağlantını ekle.</p>
        <a href="?sayfa=baglantilar&islem=demokur" class="buton butonMavi"><i class="fa-solid fa-wand-magic-sparkles"></i> demo veritabanı kur</a>
        <a href="?sayfa=baglantilar" class="buton butonAna"><i class="fa-solid fa-plus"></i> bağlantı ekle</a>
    </div>

<?php endif; ?>

<div class="kart">
    <div class="kartBaşlık">
        <h3><i class="fa-solid fa-bolt"></i> hızlı erişim</h3>
    </div>

    <div class="hızlıBağlantılar">
        <a href="?sayfa=sorgu" class="hızlı"><i class="fa-solid fa-terminal"></i> SQL sorgusu çalıştır <small>herhangi bir sorguyu dene</small></a>
<a href="?sayfa=kanvas" class="hızlı"><i class="fa-solid fa-cubes"></i> 6D kanvas <small>sorguyu 3B grafiğe dönüştür</small></a>
        <a href="?sayfa=zeuger" class="hızlı"><i class="fa-solid fa-water"></i> veri okyanusu <small>tablo sütununu dalgalara çevir</small></a>
        <a href="?sayfa=enjeksiyon" class="hızlı"><i class="fa-solid fa-syringe"></i> proje enjeksiyonu <small>kurumsal projene anka'yı göm</small></a>
        <a href="?sayfa=yedekleme" class="hızlı"><i class="fa-solid fa-database"></i> yedek alma <small>çalışan verinin dökümünü indir</small></a>
    </div>
</div>
