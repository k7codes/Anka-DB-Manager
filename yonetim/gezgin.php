<?php

$baglantiadi = ankaaktifbaglanti ();

$baglantiyok = !$baglantiadi || !Veritabani::ayarlarioku($baglantiadi);

$surucu = Veritabani::surucuturu ($baglantiadi);

$secili = isset($_GET['nesne']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['nesne']) : '';

$yapi = 'tablo';

$tablolar = $gormeler = $tetikleyiciler = $prosedurler = $fonksiyonlar = $olaylar = [];

if (!$baglantiyok) {
    $tablolar = Schema::tablolar($baglantiadi);

    if ($surucu === 'mysql') {
        $satir = Veritabani::satirlar("select table_name as ad from information_schema.tables where table_schema = database() and table_type = 'VIEW' order by table_name asc", [], $baglantiadi);
        $gormeler = array_column($satir, 'ad');

        $ts = Veritabani::satirlar("select trigger_name as ad, event_object_table as tablo from information_schema.triggers where trigger_schema = database() order by trigger_name asc", [], $baglantiadi);
        foreach ($ts as $t) {
            $tetikleyiciler[$t['ad']] = $t['tablo'];
        }

        $ps = Veritabani::satirlar("select routine_name as ad from information_schema.routines where routine_schema = database() and routine_type = 'PROCEDURE' order by routine_name asc", [], $baglantiadi);
        $prosedurler = array_column($ps, 'ad');

        $fs = Veritabani::satirlar("select routine_name as ad from information_schema.routines where routine_schema = database() and routine_type = 'FUNCTION' order by routine_name asc", [], $baglantiadi);
        $fonksiyonlar = array_column($fs, 'ad');

        $es = Veritabani::satirlar('show events', [], $baglantiadi);
        $olaylar = array_column($es, 'Name');
    } else {
        $yerli = Veritabani::satirlar("select type, name from sqlite_master where name not like 'sqlite_%' order by name asc", [], $baglantiadi);

        $gormeler = [];
        $tetikleyiciler = [];

        foreach ($yerli as $r) {
            if ($r['type'] === 'view') {
                $gormeler[] = $r['name'];
            } elseif ($r['type'] === 'trigger') {
                $tetikleyiciler[$r['name']] = '';
            }
        }
    }

    if (in_array($secili, (array) $tablolar, true)) {
        $yapi = 'tablo';
    } elseif (in_array($secili, $gormeler, true)) {
        $yapi = 'gorme';
    } elseif (isset($tetikleyiciler[$secili])) {
        $yapi = 'tetikleyici';
    }

    $secili = $secili === '' ? null : $secili;
}

function gezgintanimigezdir ($ad, $baglantiadi, $surucu)
{
    if ($surucu === 'mysql') {
        $satir = Veritabani::ilksatir ('show create view ' . Veritabani::tirnakla($ad, $baglantiadi), [], $baglantiadi);
        return $satir ? ($satir['Create View'] ?? '') : '';
    }

    $satir = Veritabani::ilksatir ('select sql from sqlite_master where name = ?', [$ad], $baglantiadi);
    return $satir ? $satir['sql'] : '';
}

function gezgintetikleyicitanimi ($ad, $baglantiadi, $surucu)
{
    if ($surucu === 'mysql') {
        $satir = Veritabani::ilksatir ('show create trigger ' . Veritabani::tirnakla($ad, $baglantiadi), [], $baglantiadi);
        return $satir ? ($satir['SQL Original Statement'] ?? ($satir['Create Trigger'] ?? '')) : '';
    }

    $satir = Veritabani::ilksatir ('select sql from sqlite_master where type = "trigger" and name = ?', [$ad], $baglantiadi);
    return $satir ? $satir['sql'] : '';
}

$secilisutunlar = $seciliindexler = $secilifk = [];

if ($secili !== null && $yapi === 'tablo') {
    try {
        $secilisutunlar = Schema::sutunlar($secili, $baglantiadi);
    } catch (Exception $e) {
        $secilisutunlar = [];
    }

    try {
        $seciliindexler = Schema::indeksler($secili, $baglantiadi);
    } catch (Exception $e) {
        $seciliindexler = [];
    }

    try {
        $secilifk = Schema::foreignkeys ($secili, $baglantiadi);
    } catch (Exception $e) {
        $secilifk = [];
    }
}

$secilitanim = '';

if ($secili !== null && in_array($yapi, ['gorme', 'tetikleyici'], true)) {
    $secilitanim = $yapi === 'gorme'
        ? gezgintanimigezdir ($secili, $baglantiadi, $surucu)
        : gezgintetikleyicitanimi ($secili, $baglantiadi, $surucu);
}

function gezginagaclistitem ($adi, $tur)
{
    return '<button type="button" class="gezginNesne" data-nesne="' . htmlspecialchars($adi, ENT_QUOTES) . '" data-tur="' . htmlspecialchars($tur, ENT_QUOTES) . '"><i class="fa-solid fa-table"></i> ' . htmlspecialchars($adi) . '</button>';
}
?>

<?php if ($baglantiyok): ?>
    <div class="kart">
        <div class="bildirim bildirim-bilgi">Önce bir veritabanı bağlantısı eklemelisin</div>
        <a href="?sayfa=baglantilar" class="buton butonAna"><i class="fa-solid fa-plug"></i> bağlantılar sayfasına git</a>
    </div>
<?php else: ?>

    <div class="gezginYerleşimi">
        <aside class="gezginAğaç">
            <div class="kartBaşlık">
                <h3><i class="fa-solid fa-sitemap"></i> <?php echo htmlspecialchars($baglantiadi); ?></h3>
                <span class="küçükNot"><?php echo $surucu; ?> • <?php echo count($tablolar); ?> tablo</span>
            </div>

            <details open>
                <summary><i class="fa-solid fa-table-cells"></i> Tablolar <em>(<?php echo count($tablolar); ?>)</em></summary>
                <div class="gezginListe">
                    <?php foreach ($tablolar as $t): ?>
                        <?php if ($t === $secili): ?>
                            <a class="gezginNesne gezginAktif" href="?sayfa=gezgin&amp;nesne=<?php echo urlencode($t); ?>"><i class="fa-solid fa-table"></i> <?php echo htmlspecialchars($t); ?></a>
                        <?php else: ?>
                            <a class="gezginNesne" href="?sayfa=gezgin&amp;nesne=<?php echo urlencode($t); ?>"><i class="fa-solid fa-table"></i> <?php echo htmlspecialchars($t); ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </details>

            <?php if ($gormeler): ?>
                <details>
                    <summary><i class="fa-solid fa-eye"></i> Viewler <em>(<?php echo count($gormeler); ?>)</em></summary>
                    <div class="gezginListe">
                        <?php foreach ($gormeler as $v): ?>
                            <a class="gezginNesne <?php echo $secili === $v && $yapi === 'gorme' ? 'gezginAktif' : ''; ?>" href="?sayfa=gezgin&amp;nesne=<?php echo urlencode($v); ?>"><i class="fa-solid fa-eye"></i> <?php echo htmlspecialchars($v); ?></a>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endif; ?>

            <?php if ($tetikleyiciler): ?>
                <details>
                    <summary><i class="fa-solid fa-bolt"></i> Tetikleyiciler <em>(<?php echo count($tetikleyiciler); ?>)</em></summary>
                    <div class="gezginListe">
                        <?php foreach ($tetikleyiciler as $ad => $ana): ?>
                            <a class="gezginNesne <?php echo $secili === $ad && $yapi === 'tetikleyici' ? 'gezginAktif' : ''; ?>" href="?sayfa=gezgin&amp;nesne=<?php echo urlencode($ad); ?>"><i class="fa-solid fa-bolt"></i> <?php echo htmlspecialchars($ad); ?></a>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endif; ?>

            <?php if ($prosedurler): ?>
                <details>
                    <summary><i class="fa-solid fa-diagram-project"></i> Prosedürler <em>(<?php echo count($prosedurler); ?>)</em></summary>
                    <div class="gezginListe">
                        <?php foreach ($prosedurler as $p): ?>
                            <a class="gezginNesne" href="?sayfa=gezgin&amp;nesne=<?php echo urlencode($p); ?>"><i class="fa-solid fa-diagram-project"></i> <?php echo htmlspecialchars($p); ?></a>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endif; ?>

            <?php if ($fonksiyonlar): ?>
                <details>
                    <summary><i class="fa-solid fa-f"></i> Fonksiyonlar <em>(<?php echo count($fonksiyonlar); ?>)</em></summary>
                    <div class="gezginListe">
                        <?php foreach ($fonksiyonlar as $fn): ?>
                            <a class="gezginNesne" href="?sayfa=gezgin&amp;nesne=<?php echo urlencode($fn); ?>"><i class="fa-solid fa-f"></i> <?php echo htmlspecialchars($fn); ?></a>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endif; ?>

            <?php if ($olaylar): ?>
                <details>
                    <summary><i class="fa-solid fa-clock"></i> Olaylar <em>(<?php echo count($olaylar); ?>)</em></summary>
                    <div class="gezginListe">
                        <?php foreach ($olaylar as $ol): ?>
                            <a class="gezginNesne" href="?sayfa=gezgin&amp;nesne=<?php echo urlencode($ol); ?>"><i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($ol); ?></a>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endif; ?>
        </aside>

        <section class="gezginDetay">
            <?php if ($secili === null): ?>
                <div class="kart">
                    <div class="kartBaşlık">
                        <h3><i class="fa-solid fa-hand-pointer"></i> <i>bir nesne seç</i></h3>
                        <span class="küçükNot">soldaki ağaçtan bir tablo, view veya tetikleyici seç</span>
                    </div>
                    <div class="bildirim bildirim-bilgi">Bu veritabanında <b><?php echo count($tablolar); ?></b> tablo var. Ağaçtan birini seç, yapısını gör.</div>
                </div>
            <?php elseif ($yapi === 'tablo'): ?>
                <div class="kart">
                    <div class="kartBaşlık">
                        <h3><i class="fa-solid fa-table"></i> <?php echo htmlspecialchars($secili); ?></h3>
                        <span class="küçükNot"><?php echo count($secilisutunlar); ?> sütun • <?php echo count($seciliindexler); ?> indeks • <?php echo count($secilifk); ?> FK</span>
                        <a href="?sayfa=tablo&amp;tablo=<?php echo urlencode($secili); ?>" class="buton butonYan"><i class="fa-solid fa-expand"></i> aç</a>
                    </div>

                    <?php if ($secilisutunlar): ?>
                        <h4 class="gezginBaşlık"><i class="fa-solid fa-list"></i> Sütunlar</h4>
                        <div class="tabloSarıcı">
                            <table class="veriTablo">
                                <thead>
                                    <tr>
                                        <th>Sütun</th>
                                        <th>Tip</th>
                                        <th>Null</th>
                                        <th>Varsayılan</th>
                                        <th>Otomatik</th>
                                        <th>PK</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($secilisutunlar as $st): ?>
                                        <tr>
                                            <td><b><?php echo htmlspecialchars($st['ad']); ?></b></td>
                                            <td><code><?php echo htmlspecialchars((string) ($st['tip'] ?? '')); ?></code></td>
                                            <td><?php echo!empty($st['boskalabilir']) ? '✔' : '-'; ?></td>
                                            <td><?php echo $st['varsayilan'] !== null && $st['varsayilan'] !== '' ? '<code>' . htmlspecialchars((string) $st['varsayilan']) . '</code>' : '-'; ?></td>
                                            <td><?php echo!empty($st['otomatik']) ? '✔' : '-'; ?></td>
                                            <td><?php echo!empty($st['birincil']) ? '🔑' : '-'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <?php if ($secilifk): ?>
                        <h4 class="gezginBaşlık"><i class="fa-solid fa-link"></i> Dış anahtarlar</h4>
                        <div class="tabloSarıcı">
                            <table class="veriTablo">
                                <thead><tr><th>Ad</th><th>Sütun</th><th>Hedef</th><th>Hedef sütun</th></tr></thead>
                                <tbody>
                                    <?php foreach ($secilifk as $fk): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($fk['ad']); ?></code></td>
                                            <td><b><?php echo htmlspecialchars($fk['alan']); ?></b></td>
                                            <td><?php echo htmlspecialchars($fk['hedeftablo']); ?></td>
                                            <td><?php echo htmlspecialchars($fk['hedefalan']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <?php if ($seciliindexler): ?>
                        <h4 class="gezginBaşlık"><i class="fa-solid fa-layer-group"></i> İndeksler</h4>
                        <div class="tabloSarıcı">
                            <table class="veriTablo">
                                <thead><tr><th>Ad</th><th>Alanlar</th><th>Tekil</th></tr></thead>
                                <tbody>
                                    <?php foreach ($seciliindexler as $ix): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($ix['ad']); ?></code></td>
                                            <td><?php echo htmlspecialchars(implode(', ', (array) ($ix['alanlar'] ?? []))); ?></td>
                                            <td><?php echo!empty($ix['tekil']) ? '✔' : '-'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="kart">
                    <div class="kartBaşlık">
                        <h3><i class="fa-solid fa-<?php echo $yapi === 'gorme' ? 'eye' : 'bolt'; ?>"></i> <?php echo htmlspecialchars($secili); ?></h3>
                        <span class="küçükNot"><?php echo $yapi === 'gorme' ? 'view' : 'tetikleyici'; ?></span>
                    </div>
                    <?php if ($secilitanim !== ''): ?>
                        <pre class="ciktiAlani gezginKod"><code><?php echo htmlspecialchars($secilitanim); ?></code></pre>
                    <?php else: ?>
                        <div class="bildirim bildirim-bilgi">Tanım alınamadı</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

<?php endif; ?>