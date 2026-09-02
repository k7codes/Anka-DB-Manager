<?php

$baglantiadi = ankaaktifbaglanti ();

$baglantiyok = !$baglantiadi || !Veritabani::ayarlarioku($baglantiadi);

$erveri = ['tablolar' => [], 'fk' => []];

if (!$baglantiyok) {
    $tablodizisi = Schema::tablolar($baglantiadi);

    foreach ($tablodizisi as $t) {
        try {
            $sutunlar = Schema::sutunlar($t, $baglantiadi);
        } catch (Exception $e) {
            $sutunlar = [];
        }

        $alanlar = [];

        foreach ($sutunlar as $st) {
            $alanlar[] = [
                'ad' => $st['ad'],
                'tip' => (string) ($st['tip'] ?? ''),
                'pk' => !empty($st['birincil']),
            ];
        }

        $erveri['tablolar'][$t] = $alanlar;
    }

    foreach ($tablodizisi as $t) {
        try {
            $fkler = Schema::foreignkeys ($t, $baglantiadi);
        } catch (Exception $e) {
            $fkler = [];
        }

        foreach ($fkler as $fk) {
            if (isset($erveri['tablolar'][$fk['hedeftablo']])) {
                $erveri['fk'][] = [
                    'kaynak' => $t,
                    'alan' => $fk['alan'],
                    'hedef' => $fk['hedeftablo'],
                    'hedefalan' => $fk['hedefalan'],
                ];
            }
        }
    }
}

$erjson = json_encode($erveri, JSON_UNESCAPED_UNICODE);
?>

<?php if ($baglantiyok): ?>
    <div class="kart">
        <div class="bildirim bildirim-bilgi">Önce bir veritabanı bağlantısı eklemelisin</div>
        <a href="?sayfa=baglantilar" class="buton butonAna"><i class="fa-solid fa-plug"></i> bağlantılar sayfasına git</a>
    </div>
<?php else: ?>

    <div class="kart">
        <div class="kartBaşlık">
            <h3><i class="fa-solid fa-diagram-project"></i> ER Diyagramı</h3>
            <span class="küçükNot">bağlantı: <?php echo htmlspecialchars($baglantiadi); ?> • <b><?php echo count($erveri['tablolar']); ?></b> tablo • <b><?php echo count($erveri['fk']); ?></b> ilişki</span>
            <span class="erKontroller">
                <button type="button" class="erBtn" id="erHaritayaSigdir"><i class="fa-solid fa-expand"></i> sığdır</button>
                <button type="button" class="erBtn" id="erOtomatikDuz"><i class="fa-solid fa-wand-magic-sparkles"></i> düzenle</button>
            </span>
        </div>

        <div class="erAlan" id="erAlan" data-er='<?php echo htmlspecialchars($erjson, ENT_QUOTES); ?>'>
            <div class="erBos">yükleniyor…</div>
        </div>
        <div class="küçükNot erIpucu"><i class="fa-regular fa-hand"></i> sürükle = tablo taşı • sağa sürükle/tekerlek = kaydır & yakınlaş • nesneye tıkla = detay</div>
    </div>

<?php endif; ?>

<script src="gorsel/er.js?v=24"></script>