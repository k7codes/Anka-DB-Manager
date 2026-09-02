<?php

$baglantiadi = ankaaktifbaglanti ();

$baglantiyok = !$baglantiadi || !Veritabani::ayarlarioku($baglantiadi);

$sonuclar = [];
$hatamesaji = null;
$grafik = null;

$orneksorgu = 'SELECT * FROM yerine_aktif_tablo LIMIT 20';

if (!$baglantiyok) {
    try {
        $tablolar = Schema::tablolar($baglantiadi);

        if ($tablolar) {
            $hedeftablo = $tablolar[0];

            foreach ($tablolar as $t) {
                try {
                    $adet = (int) Veritabani::tekil('SELECT COUNT(*) FROM ' . $t . '', [], $baglantiadi);

                    if ($adet > 0) {
                        $hedeftablo = $t;

                        break;
                    }
                } catch (Exception $ie) {
                }
            }

            $orneksorgu = 'SELECT * FROM ' . $hedeftablo . ' LIMIT 20';

            ankaoturum ('kanvasornek', $orneksorgu);
        }
    } catch (Exception $e) {
    }
}

$mevcutsql = trim(ankagirdi ('sql', ''));

if ($mevcutsql === '' && !ankagirdi ('onceki')) {
    $mevcutsql = (string) ankaoturum ('kanvasornek', '');
}

$calistirildi = (bool) ankagirdi ('calistir') || ($mevcutsql !== '' && !ankagirdi ('onceki'));

if (!$baglantiyok && $calistirildi) {
    $sqlkodu = $mevcutsql;

    ankaoturum ('sonsql', $sqlkodu);

    if ($sqlkodu === '') {
        $hatamesaji = 'sorgu boş görünüyor — hazır örnek sorgulardan birini seç';
    } else {
        try {
            $sqlkodu = rtrim(trim($sqlkodu), ';');

            $veriler = Veritabani::satirlar($sqlkodu, [], $baglantiadi);

            if ($veriler) {
                $ilk = reset($veriler);

                $sayisal = [];

                foreach ($ilk as $kolon => $deger) {
                    if (strpos($kolon, '') === 0) {
                        $kolon = trim($kolon, '');
                    }

                    if (is_numeric($deger)) {
                        $sayisal[] = $kolon;
                    }
                }

                $etiket = array_keys($ilk)[0];
                if (strpos($etiket, '') === 0) {
                    $etiket = trim($etiket, '');
                }

                $satirlar = [];
                foreach ($veriler as $satir) {
                    $cikti = [];
                    foreach ($satir as $k => $v) {
                        $kk = strpos($k, '') === 0 ? trim($k, '') : $k;
                        $cikti[$kk] = $v;
                    }
                    $satirlar[] = $cikti;
                }

                $grafik = [
                    'etiket' => $etiket,
                    'sayisal' => $sayisal ? array_slice($sayisal, 0, 4) : [],
                    'satirlar' => $satirlar,
                ];
            }
        } catch (Exception $e) {
            $hatamesaji = $e->getMessage();

            ankabildirim ('sorgu çalıştırılamadı: ' . $e->getMessage(), 'hata');
        }
    }
}

if (!$grafik) {
    $satirlar2 = [];
    $n = 32;
    for ($i = 0; $i < $n; $i++) {
        $satirlar2[] = [
            'satır' => 'nokta_' . ($i + 1),
            'değer' => round(sin($i / 3.1) * 40 + 60 + ($i % 5) * 6, 2),
            'ikincil' => round(cos($i / 2.6) * 30 + 50, 2),
        ];
    }
    $grafik = [
        'etiket' => 'satır',
        'sayisal' => ['değer', 'ikincil'],
        'satirlar' => $satirlar2,
    ];
    $ornekkip = true;
} else {
    $ornekkip = false;
}

$grafikjson = $grafik ? json_encode($grafik, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) : 'null';
?>
<div class="kart kanvasKart">
    <div class="kartBaşlık">
        <h3><i class="fa-solid fa-cubes"></i> 6D Kanvas</h3>
        <span class="küçükNot">sorgu sonucunu canlı 3B grafiklere çevir: çubuk · dalga · dağılım</span>
    </div>

    <?php if ($baglantiyok): ?>
        <div class="bildirim bildirim-bilgi">Önce bir veritabanı bağlantısı eklemelisin — hazırsa <a href="?sayfa=baglantilar&islem=demokur" class="buton butonMavi butonKüçük"><i class="fa-solid fa-wand-magic-sparkles"></i> demo kur</a></div>
    <?php else: ?>

    <div class="kanvasÖrnekler" id="kanvasOrnekler">
        <span class="küçükNot"><i class="fa-solid fa-bolt"></i> hazır örnek:</span>
        <a href="#" class="cipler" data-sql="<?php echo htmlspecialchars($orneksorgu, ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-table"></i> aktif tablo</a>
        <a href="#" class="cipler" data-sql="SELECT COUNT(*) AS adet FROM <?php echo htmlspecialchars($tablolar[0] ?? '', ENT_QUOTES); ?>"><i class="fa-solid fa-hashtag"></i> sayı</a>
        <a href="#" class="cipler" data-sql="SELECT id, fiyat, stok FROM <?php echo htmlspecialchars($tablolar[0] ?? '', ENT_QUOTES); ?> LIMIT 40"><i class="fa-solid fa-sliders"></i> çift sütun</a>
    </div>

    <form method="post" class="satırİçiForm" style="padding:4px 20px 0;">
        <textarea name="sql" id="kanvasSql" class="konsolAlan" rows="3" placeholder="SELECT ..."><?php echo htmlspecialchars($mevcutsql); ?></textarea>
        <button type="submit" name="calistir" value="1" class="buton butonAna"><i class="fa-solid fa-play"></i> görselleştir</button>
    </form>

    <?php if ($hatamesaji): ?>
        <div class="bildirim bildirim-hata" style="margin-top:12px;"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($hatamesaji); ?></div>
    <?php endif; ?>

    <div id="kanvasKontroller" class="kanvasKontroller <?php echo $grafik ? '' : 'gizli'; ?>">
        <label class="kanvasKontrol"><span>grafik</span>
            <select id="kanvasTur">
                <option value="bar">3B çubuk</option>
                <option value="cizgi">3B dalga</option>
                <option value="dagitim">3D dağılım</option>
            </select>
        </label>
        <label class="kanvasKontrol" id="kanvasKolonKapsayici"><span>değer sütunu</span>
            <select id="kanvasKolon"><option value="">—</option></select>
        </label>
        <label class="kanvasKontrol"><span class="bos">eta</span></label>
    </div>

    <div class="kanvasAlan">
        <div class="kanvasKonteynır" data-grafik="<?php echo $grafikjson; ?>">
            <?php if (!$grafik): ?>
                <div class="kanvasBos"><i class="fa-solid fa-wand-magic-sparkles"></i><br>Sayısal sütunlu bir sorgu çalıştır, 3B görselleştirmeyi burada izle<br><small>üstteki hazır örneklerden birini seçip "görselleştir"e basabilirsin</small></div>
            <?php endif; ?>
        </div>

        <?php if ($grafik): ?>
            <div class="kanvasTablo">
                <div class="kanvasTabloBaşlık">ham veri</div>
                <table class="veriTablo">
                    <thead><tr>
                        <th><?php echo htmlspecialchars($grafik['etiket']); ?></th>
                        <?php foreach ($grafik['sayisal'] as $k): ?>
                            <th><?php echo htmlspecialchars($k); ?></th>
                        <?php endforeach; ?>
                    </tr></thead>
                    <tbody>
                        <?php foreach (array_slice($grafik['satirlar'], 0, 40) as $satir): ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string) $satir[$grafik['etiket']]); ?></td>
                                <?php foreach ($grafik['sayisal'] as $k): ?>
                                    <td><?php echo htmlspecialchars((string) ($satir[$k] ?? '')); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php endif; ?>
</div>

<script>window.__ankaKanvasGrafik = <?php echo $grafikjson; ?>;window.__ankaKanvasOrnek = <?php echo $ornekkip ? 'true' : 'false'; ?>;</script>