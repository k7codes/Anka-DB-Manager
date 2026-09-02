<?php

$tumbaglanti = Veritabani::ayarlarioku();

$baglantiyok = empty($tumbaglanti);

$adlar = array_keys($tumbaglanti);

$kaynak = ankagirdi ('kaynak', ankasorgu ('kaynak', $adlar[0] ?? ''));
$hedef = ankagirdi ('hedef', ankasorgu ('hedef', $adlar[1] ?? $adlar[0] ?? ''));

$sonuc = null;

if (ankagirdi ('karsilastir') && $kaynak && $hedef) {
    if ($kaynak === $hedef) {
        $sonuc = ['hata' => 'İki farklı bağlantı seçmelisin.'];
    } else {
        try {
            $sonuc = farksemakarsilastir ($kaynak, $hedef);
        } catch (Exception $e) {
            $sonuc = ['hata' => $e->getMessage()];
        }
        if (isset($sonuc['hata']) === false) {
            ankaaudit ('ŞEMA KARŞILAŞTIR', $kaynak . ' → ' . $hedef);
        }
    }
}

function farksemakarsilastir ($kaynak, $hedef)
{
    $kaynaktablo = Schema::tablolar($kaynak);
    $hedeftablo = Schema::tablolar($hedef);

    $kaynakset = array_flip($kaynaktablo);
    $hedefset = array_flip($hedeftablo);

    $veri = [
        'kaynak' => $kaynak,
        'hedef' => $hedef,
        'sadeceKaynak' => [],
        'sadeceHedef' => [],
        'sutunFarklari' => [],
        'sql' => [],
    ];

    foreach ($kaynakset as $tbl => $x) {
        if (!array_key_exists($tbl, $hedefset)) {
            $veri['sadeceKaynak'][] = $tbl;
            $veri['sql'][] = farktabloyarat ($tbl, $kaynak);
            continue;
        }

        $kaynaksutun = Schema::sutunlar($tbl, $kaynak);
        $hedefsutun = Schema::sutunlar($tbl, $hedef);

        $ks = [];
        foreach ($kaynaksutun as $st) {
            $ks[$st['ad']] = $st;
        }

        $hs = [];
        foreach ($hedefsutun as $st) {
            $hs[$st['ad']] = $st;
        }

        foreach ($ks as $ad => $tanim) {
            if (!array_key_exists($ad, $hs)) {
                $veri['sutunFarklari'][$tbl][] = ['tip' => 'ekle', 'ad' => $ad, 'tanim' => $tanim];
                $veri['sql'][] = farksutunekle ($tbl, $tanim, $hedef);
            }
        }
    }

    foreach ($hedefset as $tbl => $x) {
        if (!array_key_exists($tbl, $kaynakset)) {
            $veri['sadeceHedef'][] = $tbl;
        }
    }

    return $veri;
}

function farktabloyarat ($tablo, $baglantiadi)
{
    $surucu = Veritabani::surucuturu ($baglantiadi);

    if ($surucu === 'mysql') {
        $satir = Veritabani::ilksatir ('show create table ' . Veritabani::tirnakla($tablo, $baglantiadi), [], $baglantiadi);
        return $satir ? ($satir['Create Table'] ?? '') . ';' : '';
    }

    if ($surucu === 'pgsql') {
        $satir = Veritabani::ilksatir ('select pg_get_tabledef(?) as tanim', [$tablo], $baglantiadi);
        if (!$satir) {
            $satir = Veritabani::ilksatir ('select sql from pg_tables where tablename = ?', [$tablo], $baglantiadi);
        }
        return $satir && !empty($satir['tanim']) ? $satir['tanim'] : '';
    }

    $satir = Veritabani::ilksatir ('select sql from sqlite_master where type = "table" and name = ?', [$tablo], $baglantiadi);
    return $satir ? ($satir['sql'] ?? '') . ';' : '';
}

function farksutunekle ($tablo, $tanim, $baglantiadi)
{
    $surucu = Veritabani::surucuturu ($baglantiadi);

    $ifade = Schema::sutunyaz($tanim, $surucu, $baglantiadi);

    if ($ifade === '') {
        return '';
    }

    return 'alter table ' . Veritabani::tirnakla($tablo, $baglantiadi) . ' add column ' . $ifade . ';';
}
?>

<?php if ($baglantiyok): ?>
    <div class="kart">
        <div class="bildirim bildirim-bilgi">Önce en az iki veritabanı bağlantısı eklemelisin</div>
        <a href="?sayfa=baglantilar" class="buton butonAna"><i class="fa-solid fa-plug"></i> bağlantılar sayfasına git</a>
    </div>
<?php else: ?>

    <div class="kart">
        <div class="kartBaşlık">
            <h3><i class="fa-solid fa-code-compare"></i> Şema Karşılaştır</h3>
            <span class="küçükNot">kaynak şemayı hedef şemaya taşıyacak migration üretir</span>
        </div>

        <form method="post" class="farkForm">
            <div class="farkEksen">
                <label class="küçükNot"><i class="fa-solid fa-arrow-up"></i> kaynak (REFERANS)</label>
                <select name="kaynak" class="dtpSelect">
                    <?php foreach ($adlar as $a): ?>
                        <option value="<?php echo htmlspecialchars($a); ?>" <?php echo $a === $kaynak ? 'selected' : ''; ?>><?php echo htmlspecialchars($a); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="farkOk"><i class="fa-solid fa-arrow-right-arrow-left"></i></div>
            <div class="farkEksen">
                <label class="küçükNot"><i class="fa-solid fa-arrow-down"></i> hedef (UYGULANACAK)</label>
                <select name="hedef" class="dtpSelect">
                    <?php foreach ($adlar as $a): ?>
                        <option value="<?php echo htmlspecialchars($a); ?>" <?php echo $a === $hedef ? 'selected' : ''; ?>><?php echo htmlspecialchars($a); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="farkGonder">
                <button type="submit" name="karsilastir" value="1" class="buton butonAna"><i class="fa-solid fa-magnifying-glass"></i> karşılaştır</button>
            </div>
        </form>
    </div>

    <?php if (is_array($sonuc)): ?>
        <?php if (isset($sonuc['hata'])): ?>
            <div class="bildirim bildirim-hata"><?php echo htmlspecialchars($sonuc['hata']); ?></div>
        <?php else: ?>
            <div class="farkRapor">
                <?php
                $toplamfark = count($sonuc['sadeceKaynak']) + count($sonuc['sadeceHedef']) + array_sum(array_map(function ($v) { return count($v); }, $sonuc['sutunFarklari']));
                ?>
                <div class="kart">
                    <div class="kartBaşlık">
                        <h3><i class="fa-solid fa-file-circle-question"></i> Karşılaştırma Sonucu</h3>
                        <span class="küçükNot"><?php echo htmlspecialchars($sonuc['kaynak']); ?> ↔ <?php echo htmlspecialchars($sonuc['hedef']); ?></span>
                    </div>

                    <div class="farkOzet">
                        <div class="farkOzetKutu"><b><?php echo count($sonuc['sadeceKaynak']); ?></b> kaynakta yeni tablo</div>
                        <div class="farkOzetKutu"><b><?php echo count($sonuc['sadeceHedef']); ?></b> hedefte fazla tablo</div>
                        <div class="farkOzetKutu"><b><?php echo array_sum(array_map(function ($v) { return count($v); }, $sonuc['sutunFarklari'])); ?></b> sütun farkı</div>
                    </div>

                    <?php if ($sonuc['sadeceKaynak']): ?>
                        <h4 class="gezginBaşlık"><i class="fa-solid fa-table-plus"></i> Hedefte olmayan tablolar (kaynaktan gelecek)</h4>
                        <div class="ciplerKumesi">
                            <?php foreach ($sonuc['sadeceKaynak'] as $s): ?>
                                <span class="cip"><i class="fa-solid fa-table"></i> <?php echo htmlspecialchars($s); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($sonuc['sadeceHedef']): ?>
                        <h4 class="gezginBaşlık"><i class="fa-solid fa-table-list"></i> Kaynakta olmayan tablolar (hedefte fazla)</h4>
                        <div class="ciplerKumesi">
                            <?php foreach ($sonuc['sadeceHedef'] as $s): ?>
                                <span class="cip"><?php echo htmlspecialchars($s); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($sonuc['sutunFarklari']): ?>
                        <h4 class="gezginBaşlık"><i class="fa-solid fa-arrow-right"></i> Sütun farkları</h4>
                        <?php foreach ($sonuc['sutunFarklari'] as $tabload => $farklar): ?>
                            <div class="farkTablo">
                                <b><?php echo htmlspecialchars($tabload); ?></b>
                                <?php foreach ($farklar as $f): ?>
                                    <div class="farkSatir"><span class="farkRozet farkEkle">+ EKLE</span> <code><?php echo htmlspecialchars($f['ad']); ?></code> <span class="küçükNot"><?php echo htmlspecialchars((string) ($f['tanim']['tip'] ?? '')); ?></span></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <h4 class="gezginBaşlık"><i class="fa-solid fa-code"></i> Üretilen Migration</h4>
                    <?php if ($sonuc['sql'] && array_filter($sonuc['sql'])): ?>
                        <pre class="ciktiAlani farkKod"><code><?php echo htmlspecialchars(implode("\n\n", array_filter($sonuc['sql']))); ?></code></pre>
                        <button type="button" class="buton butonYan farkKopyala" data-metin="<?php echo htmlspecialchars(implode("\n\n", array_filter($sonuc['sql'])), ENT_QUOTES); ?>"><i class="fa-solid fa-copy"></i> kopyala</button>
                    <?php else: ?>
                        <div class="bildirim bildirim-bilgi">Oluşturulacak migration yok — şemalar aynı görünüyor 🎉</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

<?php endif; ?>

<script>
(function () {
    var kop = document.querySelector('.farkKopyala');
    if (kop) {
        kop.addEventListener('click', function () {
            var mtin = this.getAttribute('data-metin');
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(mtin);
            }
        });
    }
})();
</script>