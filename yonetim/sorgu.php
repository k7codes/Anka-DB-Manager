<?php

$baglantiadi = ankaaktifbaglanti ();

$baglantiyok = !$baglantiadi || !Veritabani::ayarlarioku($baglantiadi);

$sonuclar = [];

$hatamesaji = null;

$calistirildi = false;

$islemsuresi = 0;

$islemlambasi = 0;

if (ankagirdi ('calistir')) {
    $sqlkodu = trim(ankagirdi ('sql', ''));

    $explainkip = ankagirdi ('explain') ? true : false;

    ankaoturum ('sonsql', $sqlkodu);

    $calistirildi = true;

    $islemlambasi = microtime(true);

    ankaaudit ('SQL ÇALIŞTIR', substr($sqlkodu, 0, 200));

    if ($sqlkodu === '') {
        $hatamesaji = 'sorgu boş görünüyor';
    } else {
        try {
            $sqlkodu = rtrim(trim($sqlkodu), ';');

            if ($explainkip) {
                $sqlkodu = 'EXPLAIN ' . ltrim($sqlkodu);
            }

            $parcalar = preg_split('/;\s*/', $sqlkodu);

            $parcalar = array_values(array_filter($parcalar, function ($parca) {
                return trim($parca) !== '';
            }));

            if (count($parcalar) > 1) {
                foreach ($parcalar as $parca) {
                    $sonuc = [];

                    $sonuc['sql'] = $parca;

                    $bas = microtime(true);

                    $ilksozcuk = strtolower(trim(preg_split('/\s+/', $parca)[0]));

                    if (in_array($ilksozcuk, ['select', 'show', 'describe', 'desc', 'pragma', 'explain', 'with'])) {
                        $sonuc['veriler'] = Veritabani::satirlar($parca, [], $baglantiadi);

                        $sonuc['satirsayisi'] = count($sonuc['veriler']);
                    } else {
                        $hazirlik = Veritabani::calistir($parca, [], $baglantiadi);

                        $sonuc['etkilenen'] = $hazirlik->rowCount();
                    }

                    $sonuc['sure'] = round((microtime(true) - $bas) * 1000, 2);

                    $sonuclar[] = $sonuc;
                }
            } else {
                $sonuc = [];

                $sonuc['sql'] = $sqlkodu;

                $bas = microtime(true);

                $ilksozcuk = strtolower(trim(preg_split('/\s+/', $sqlkodu)[0]));

                if (in_array($ilksozcuk, ['select', 'show', 'describe', 'desc', 'pragma', 'explain', 'with'])) {
                    $sonuc['veriler'] = Veritabani::satirlar($sqlkodu, [], $baglantiadi);

                    $sonuc['satirsayisi'] = count($sonuc['veriler']);
                } else {
                    $hazirlik = Veritabani::calistir($sqlkodu, [], $baglantiadi);

                    $sonuc['etkilenen'] = $hazirlik->rowCount();
                }

                $sonuc['sure'] = round((microtime(true) - $bas) * 1000, 2);

                $sonuclar[] = $sonuc;
            }
        } catch (Exception $hata) {
            $hatamesaji = $hata->getMessage();
        }

        $islemsuresi = round((microtime(true) - $islemlambasi) * 1000, 2);

        $gecmis = ankaoturum ('sorgecmisi', []);

        if (!is_array($gecmis)) {
            $gecmis = [];
        }

        $eklenecek = $sqlkodu;

        if (!in_array($eklenecek, $gecmis)) {
            array_unshift($gecmis, $eklenecek);
        }

        $gecmis = array_slice($gecmis, 0, 10);

        ankaoturum ('sorgecmisi', $gecmis);
    }
}

$sonsql = ankaoturum ('sonsql');

$sorgecmisi = ankaoturum ('sorgecmisi', []);

if (!is_array($sorgecmisi)) {
    $sorgecmisi = [];
}
?>
<?php if ($baglantiyok): ?>

    <div class="kart">
        <div class="bildirim bildirim-bilgi">Önce bir veritabanı bağlantısı eklemelisin</div>
        <a href="?sayfa=baglantilar" class="buton butonAna"><i class="fa-solid fa-plug"></i> bağlantılar sayfasına git</a>
    </div>

<?php else: ?>

    <div class="kart">
        <div class="kartBaşlık">
            <h3><i class="fa-solid fa-terminal"></i> sql konsolu</h3>
            <span class="küçükNot">bağlantı: <?php echo htmlspecialchars($baglantiadi); ?></span>
        </div>

        <form method="post" class="konsolForm">
            <textarea name="sql" rows="9" class="konsolAlan" spellcheck="false" placeholder="select * from tablo_adi"><?php echo htmlspecialchars((string) $sonsql); ?></textarea>

            <div class="ornekCipleri">
                <span class="küçükNot"><i class="fa-solid fa-bolt"></i> örnekler:</span>
                <button type="button" class="cipler" data-sql="select * from {tablo} limit 10">select limit 10</button>
                <button type="button" class="cipler" data-sql="select count(*) as toplam from {tablo}">count toplamı</button>
                <button type="button" class="cipler" data-sql="select sql from sqlite_master where type='table'">sqlite şema</button>
                <button type="button" class="cipler" data-sql="show tables">show tables</button>
            </div>

<div class="formEylemler">
                <button type="submit" name="calistir" value="1" class="buton butonAna"><i class="fa-solid fa-play"></i> çalıştır</button>
                <button type="submit" name="explain" value="1" class="buton butonYan"><i class="fa-solid fa-magnifying-glass-chart"></i> açıkla (EXPLAIN)</button>
            </div>
        </form>
    </div>

    <?php if ($sorgecmisi): ?>
        <div class="kart">
            <div class="kartBaşlık">
                <h3><i class="fa-solid fa-clock-rotate-left"></i> son sorgular</h3>
                <span class="küçükNot">tıklayınca sorgu alanına yükler</span>
            </div>

            <div class="sorguGecmis">
                <?php foreach ($sorgecmisi as $onceki): ?>
                    <button type="button" class="gecmisOnge" data-sql="<?php echo htmlspecialchars($onceki, ENT_QUOTES); ?>"><code><?php echo htmlspecialchars(substr($onceki, 0, 80)); ?></code></button>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($calistirildi && $hatamesaji): ?>
        <div class="bildirim bildirim-hata"><?php echo htmlspecialchars($hatamesaji); ?></div>
    <?php endif; ?>

<?php foreach ($sonuclar as $sonuc): ?>

        <div class="kart">
            <div class="kartBaşlık">
                <code class="sqlÖnizleme"><?php echo htmlspecialchars($sonuc['sql']); ?></code>
            </div>

            <div class="profilci">
                <span class="profilcıMetrik"><i class="fa-solid fa-stopwatch"></i> <b><?php echo number_format((float) $sonuc['sure'], 2, ',', '.'); ?> ms</b></span>
                <?php if (isset($sonuc['satirsayisi'])): ?>
                    <span class="profilcıMetrik"><i class="fa-solid fa-table-cells"></i> <b><?php echo number_format($sonuc['satirsayisi'], 0, ',', '.'); ?></b> satır döndü</span>
                <?php endif; ?>
                <?php if (isset($sonuc['etkilenen'])): ?>
                    <span class="profilcıMetrik"><i class="fa-solid fa-pen"></i> <b><?php echo (int) $sonuc['etkilenen']; ?></b> satır etkilendi</span>
                <?php endif; ?>
                <span class="profilcıMetrik"><i class="fa-solid fa-microchip"></i> <b><?php echo trim(wordwrap(number_format(memory_get_usage() / 1048576, 2), 1)); ?></b> MB bellek</span>
            </div>

            <?php if (isset($sonuc['etkilenen'])): ?><?php endif; ?>

            <?php if (isset($sonuc['veriler'])): ?>
                <?php if ($sonuc['veriler']): ?>
                    <div class="tabloSarıcı konsolSonuç">
                        <table class="veriTablo">
                            <thead>
                                <tr>
                                    <?php foreach (array_keys($sonuc['veriler'][0]) as $baslik): ?>
                                        <th><?php echo htmlspecialchars($baslik); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sonuc['veriler'] as $satir): ?>
                                    <tr>
                                        <?php foreach ($satir as $deger): ?>
                                            <td>
                                                <?php echo $deger === null ? '<span class="null">null</span>' : htmlspecialchars(substr((string) $deger, 0, 120)); ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="sayfalama">
                        <span><?php echo number_format(count($sonuc['veriler']), 0, ',', '.'); ?> satır • <?php echo number_format((float) $sonuc['sure'], 2, ',', '.'); ?> ms</span>
                    </div>
                <?php else: ?>
                    <div class="bildirim bildirim-bilgi">Sorgu sonucu boş</div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    <?php endforeach; ?>

    <?php if ($calistirildi && !$sonuclar && !$hatamesaji): ?>
        <div class="bildirim bildirim-bilgi">Çalıştırılacak bir şey yok</div>
    <?php endif; ?>

<?php endif; ?>
