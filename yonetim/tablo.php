<?php

$baglantiadi = ankaaktifbaglanti ();

$tablo = trim(ankasorgu ('tablo', ''));

if ($tablo === '' || !$baglantiadi) {
    ankayonlendir ('?sayfa=tablolar');
}

$seki = ankasorgu ('seki', 'veri');

if ($seki !== 'yapi') {
    $seki = 'veri';
}

$sutunlar = Schema::sutunlar($tablo, $baglantiadi);

$indexler = [];

try {
    $indexler = Schema::indeksler($tablo, $baglantiadi);
} catch (Exception $e) {
    $indexler = [];
}

$birincil = null;

foreach ($sutunlar as $sutun) {
    if (!empty($sutun['birincil'])) {
        $birincil = $sutun['ad'];

        break;
    }
}

if ($birincil === null) {
    foreach ($sutunlar as $sutun) {
        if ($sutun['ad'] === 'id') {
            $birincil = 'id';

            break;
        }
    }
}

if ($birincil === null && count($sutunlar) > 0) {
    $birincil = $sutunlar[0]['ad'];
}

$islem = ankagirdi ('islem', ankasorgu ('islem'));

if ($islem) {
    try {
        if ($islem === 'veriekle') {
            $veri = [];

            foreach ($sutunlar as $sutun) {
                $alan = $sutun['ad'];

                if (!empty($sutun['otomatik'])) {
                    continue;
                }

                if (array_key_exists($alan, $_POST)) {
                    $gelen = $_POST[$alan];

                    $veri[$alan] = $gelen === '' ? null : $gelen;
                }
            }

            Sorgu::tablo($tablo, $baglantiadi)->ekle($veri);

            ankabildirim ('kayıt eklendi');
        } elseif ($islem === 'veriguncelle') {
            $kayitid = ankagirdi ('kayit_id');

            $veri = [];

            foreach ($sutunlar as $sutun) {
                $alan = $sutun['ad'];

                if ($alan === $birincil || !empty($sutun['otomatik'])) {
                    continue;
                }

                if (array_key_exists($alan, $_POST)) {
                    $gelen = $_POST[$alan];

                    $veri[$alan] = $gelen === '' ? null : $gelen;
                }
            }

            Sorgu::tablo($tablo, $baglantiadi)->nerede($birincil, $kayitid)->guncelle($veri);

            ankabildirim ('kayıt güncellendi');
        } elseif ($islem === 'verisil') {
            $kayitid = ankagirdi ('kayit_id');

            Sorgu::tablo($tablo, $baglantiadi)->nerede($birincil, $kayitid)->sil();

            ankabildirim ('kayıt silindi');
        } elseif ($islem === 'sutunekle') {
            $tanim = [
                'ad' => trim(ankagirdi ('yeni_sutun_ad', '')),
                'tip' => ankagirdi ('yeni_sutun_tip', 'varchar'),
                'uzunluk' => ankagirdi ('yeni_sutun_uzunluk', 255),
                'boskalabilir' => ankagirdi ('yeni_sutun_bos', '') === '1',
                'varsayilan' => null,
                'tekil' => ankagirdi ('yeni_sutun_tekil', '') === '1',
            ];

            $yenivarsayilan = ankagirdi ('yeni_sutun_varsayilan', '');

            if ($yenivarsayilan !== '') {
                $tanim['varsayilan'] = $yenivarsayilan;
            }

            if ($tanim['ad'] === '') {
                throw new Exception('sütun adı gerekli');
            }

            Schema::sutunekle ($tablo, $tanim, $baglantiadi);

            ankabildirim ($tanim['ad'] . ' sütunu eklendi');
        } elseif ($islem === 'sutunsil') {
            $alan = ankagirdi ('sutun_ad');

            if ($alan === $birincil) {
                throw new Exception('birincil anahtar sütunu silinemez');
            }

Schema::sutunsil ($tablo, $alan, $baglantiadi);

            ankabildirim ($alan . ' sütunu silindi');
        } elseif ($islem === 'indeksekle') {
            $indeksad = trim(ankagirdi ('yeni_indeks_ad', ''));

            $indeksalanlar = (array) ankagirdi ('yeni_indeks_alanlar', []);

            $indekstekil = ankagirdi ('yeni_indeks_tekil', '') === '1';

            if ($indeksad === '' || empty($indeksalanlar)) {
                throw new Exception('indeks adı ve en az bir alan gerekli');
            }

            Schema::indeksekle ($tablo, $indeksad, $indeksalanlar, $indekstekil, $baglantiadi);

            ankaaudit ('İNDEKS EKLE', $tablo . ' -> ' . $indeksad . ' (' . implode(',', $indeksalanlar) . ')');

            ankabildirim ($indeksad . ' indeksi eklendi');
        } elseif ($islem === 'indekssil') {
            $indeksad = trim(ankagirdi ('indeks_ad', ''));

            if ($indeksad === '') {
                throw new Exception('indeks adı gerekli');
            }

            Schema::indekssil ($tablo, $indeksad, $baglantiadi);

            ankaaudit ('İNDEKS SİL', $tablo . ' -> ' . $indeksad);

            ankabildirim ($indeksad . ' indeksi silindi');
        } elseif ($islem === 'temizle') {
            Schema::tablotemizle ($tablo, $baglantiadi);

            ankabildirim ('tablo boşaltıldı');
        } elseif ($islem === 'tablosil') {
            Schema::tablosil ($tablo, $baglantiadi);

            ankayonlendir ('?sayfa=tablolar');
        } elseif ($islem === 'csvindir') {
            $kayitlar = Sorgu::tablo($tablo, $baglantiadi)->getir();

            header('Content-Type: text/csv; charset=utf-8');

            header('Content-Disposition: attachment; filename="' . $tablo . '.csv"');

            $cikti = fopen('php://output', 'w');

            $basliklar = [];

            foreach ($sutunlar as $sutun) {
                $basliklar[] = $sutun['ad'];
            }

            fputcsv($cikti, $basliklar, ';');

            foreach ($kayitlar as $kayit) {
                $satir = [];

                foreach ($basliklar as $baslik) {
                    $satir[] = isset($kayit[$baslik]) ? $kayit[$baslik] : '';
                }

                fputcsv($cikti, $satir, ';');
            }

            fclose($cikti);

            exit;
        } elseif ($islem === 'sqlindir') {
            $icerik = Schema::yedekal ([$tablo], $baglantiadi);

            header('Content-Type: application/sql; charset=utf-8');

            header('Content-Disposition: attachment; filename="' . $tablo . '.sql"');

            echo $icerik;

            exit;
        }
    } catch (Exception $hata) {
        ankabildirim ($hata->getMessage(), 'hata');
    }

    ankayonlendir ('?sayfa=tablo&tablo=' . urlencode($tablo) . '&seki=' . $seki);
}

$sutunlar = Schema::sutunlar($tablo, $baglantiadi);

$filtre = trim(ankasorgu ('q', ''));

$sayfanum = max(1, (int) ankasorgu ('sayfa', 1));

$adetsayfa = 20;

$kayitlar = [];

$satirsayisi = 0;

if ($sutunlar) {
    $temelsorgu = Sorgu::tablo($tablo, $baglantiadi);

    if ($filtre !== '') {
        $esas = true;

        foreach ($sutunlar as $sutun) {
            if ($esas) {
                $temelsorgu->nerede($sutun['ad'], 'like', '%' . $filtre . '%');

                $esas = false;
            } else {
                $temelsorgu->veyanerede ($sutun['ad'], 'like', '%' . $filtre . '%');
            }
        }
    }

    $verisorgu = clone $temelsorgu;

    $satirsayisi = $temelsorgu->say();

    $kayitlar = $verisorgu->sayfa($sayfanum, $adetsayfa)->getir();
}

$toplamsayfa = max(1, (int) ceil($satirsayisi / $adetsayfa));

$duzenlenenkayit = null;

$duzenlenenid = ankasorgu ('duzenle', '');

if ($duzenlenenid !== '' && $birincil) {
    $duzenlenenkayit = Sorgu::tablo($tablo, $baglantiadi)->nerede($birincil, $duzenlenenid)->ilk();
}

function ankahucra ($deger)
{
    if ($deger === null) {
        return '<span class="null">null</span>';
    }

    $deger = (string) $deger;

    if (strlen($deger) > 60) {
        return '<span title="' . htmlspecialchars($deger) . '">' . htmlspecialchars(substr($deger, 0, 60)) . '...</span>';
    }

    return htmlspecialchars($deger);
}

function ankaalanturu ($tip)
{
    $tip = strtolower($tip);

    if (strpos($tip, 'text') !== false || strpos($tip, 'blob') !== false || strpos($tip, 'json') !== false) {
        return 'textarea';
    }

    if (strpos($tip, 'date') !== false) {
        return 'date';
    }

    return 'text';
}
?>
<div class="sekiSırası">
    <a href="?sayfa=tablolar" class="buton butonKüçük geriBağ"><i class="fa-solid fa-arrow-left"></i> tablolara dön</a>

    <div class="sekiSekmeler">
        <a href="?sayfa=tablo&tablo=<?php echo urlencode($tablo); ?>&seki=veri" class="<?php echo $seki === 'veri' ? 'aktif' : ''; ?>">veri</a>
        <a href="?sayfa=tablo&tablo=<?php echo urlencode($tablo); ?>&seki=yapi" class="<?php echo $seki === 'yapi' ? 'aktif' : ''; ?>">yapı</a>
    </div>

    <div class="sekiEylemler">
        <a href="?sayfa=tablo&tablo=<?php echo urlencode($tablo); ?>&islem=csvindir" class="buton butonKüçük"><i class="fa-solid fa-file-csv"></i> csv</a>
        <a href="?sayfa=tablo&tablo=<?php echo urlencode($tablo); ?>&islem=sqlindir" class="buton butonKüçük"><i class="fa-solid fa-code"></i> sql</a>
        <form method="post" class="satırİçiForm" data-onay="tablonun tüm verisi boşaltılacak, emin misin?">
            <input type="hidden" name="islem" value="temizle">
            <button type="submit" class="buton butonKüçük"><i class="fa-solid fa-trash"></i> boşalt</button>
        </form>
        <form method="post" class="satırİçiForm" data-onay="tablo kalıcı olarak silinecek, emin misin?">
            <input type="hidden" name="islem" value="tablosil">
            <button type="submit" class="buton butonKüçük butonTehlikeli"><i class="fa-solid fa-trash-can"></i> tabloyu sil</button>
        </form>
    </div>
</div>

<?php if ($seki === 'veri'): ?>

    <div class="kart">
        <div class="kartBaşlık">
            <h3><?php echo htmlspecialchars($tablo); ?></h3>

            <form method="get" class="satırİçiForm">
                <input type="hidden" name="sayfa" value="tablo">
                <input type="hidden" name="tablo" value="<?php echo htmlspecialchars($tablo); ?>">
                <input type="hidden" name="seki" value="veri">
                <input type="text" name="q" class="metinAlanı" placeholder="ara..." value="<?php echo htmlspecialchars($filtre); ?>">
                <button type="submit" class="buton butonKüçük"><i class="fa-solid fa-magnifying-glass"></i> bul</button>
            </form>
        </div>

        <?php if (!$sutunlar): ?>
            <div class="bildirim bildirim-bilgi">Bu tablonun sütunu yok</div>
        <?php else: ?>

            <div class="tabloSarıcı">
                <table class="veriTablo">
                    <thead>
                        <tr>
                            <?php foreach ($sutunlar as $sutun): ?>
                                <th><?php echo htmlspecialchars($sutun['ad']); ?><?php echo !empty($sutun['birincil']) ? ' <span class="anahtar">id</span>' : ''; ?></th>
                            <?php endforeach; ?>
                            <th class="hizalıSağ">işlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kayitlar as $kayit): ?>
                            <tr>
                                <?php foreach ($sutunlar as $sutun): ?>
                                    <td><?php echo ankahucra (isset($kayit[$sutun['ad']]) ? $kayit[$sutun['ad']] : null); ?></td>
                                <?php endforeach; ?>
                                <td class="hizalıSağ">
                                    <a href="?sayfa=tablo&tablo=<?php echo urlencode($tablo); ?>&seki=veri&duzenle=<?php echo urlencode((string) $kayit[$birincil]); ?>" class="buton butonKüçük"><i class="fa-solid fa-pen"></i> düzenle</a>
                                    <form method="post" class="satırİçiForm" data-onay="bu kayıt silinecek, emin misin?">
                                        <input type="hidden" name="islem" value="verisil">
                                        <input type="hidden" name="kayit_id" value="<?php echo htmlspecialchars((string) $kayit[$birincil]); ?>">
                                        <button type="submit" class="buton butonKüçük butonTehlikeli"><i class="fa-solid fa-trash-can"></i> sil</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (!$kayitlar): ?>
                            <tr>
                                <td colspan="<?php echo count($sutunlar) + 1; ?>" class="boşMesaj">kayıt bulunamadı</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="sayfalama">
                <span><?php echo number_format($satirsayisi, 0, ',', '.'); ?> kayıt</span>

                <?php if ($toplamsayfa > 1): ?>
                    <div class="sayfaBağları">
                        <?php
                        $bas = max(1, $sayfanum - 2);

                        $son = min($toplamsayfa, $sayfanum + 2);

                        $onceki = $sayfanum > 1 ? max(1, $sayfanum - 1) : 1;

                        $sonraki = $sayfanum < $toplamsayfa ? min($toplamsayfa, $sayfanum + 1) : $toplamsayfa;

                        $bag = '?sayfa=tablo&tablo=' . urlencode($tablo) . '&seki=veri&q=' . urlencode($filtre) . '&sayfa=';
                        ?>
                        <a href="<?php echo $bag . $onceki; ?>" class="buton butonKüçük">&laquo;</a>
                        <?php for ($s = $bas; $s <= $son; $s++): ?>
                            <a href="<?php echo $bag . $s; ?>" class="buton butonKüçük <?php echo $s == $sayfanum ? 'butonAna' : ''; ?>"><?php echo $s; ?></a>
                        <?php endfor; ?>
                        <a href="<?php echo $bag . $sonraki; ?>" class="buton butonKüçük">&raquo;</a>
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>
    </div>

    <?php if ($duzenlenenkayit): ?>

        <div class="kart">
            <div class="kartBaşlık">
                <h3>kayıt düzenle (<?php echo htmlspecialchars((string) $duzenlenenkayit[$birincil]); ?>)</h3>
            </div>

            <form method="post" class="formIzgara">
                <input type="hidden" name="islem" value="veriguncelle">
                <input type="hidden" name="kayit_id" value="<?php echo htmlspecialchars((string) $duzenlenenkayit[$birincil]); ?>">

                <?php foreach ($sutunlar as $sutun): ?>
                    <?php
                    $alan = $sutun['ad'];

                    if ($alan === $birincil || !empty($sutun['otomatik'])) {
                        continue;
                    }

                    $deger = isset($duzenlenenkayit[$alan]) ? $duzenlenenkayit[$alan] : '';

                    $tur = ankaalanturu ($sutun['tip']);
                    ?>
                    <label class="alan">
                        <?php echo htmlspecialchars($alan); ?>
                        <?php if ($tur === 'textarea'): ?>
                            <textarea name="<?php echo htmlspecialchars($alan); ?>" rows="3"><?php echo htmlspecialchars((string) $deger); ?></textarea>
                        <?php else: ?>
                            <input type="<?php echo $tur; ?>" name="<?php echo htmlspecialchars($alan); ?>" value="<?php echo htmlspecialchars((string) $deger); ?>">
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>

                <div class="formEylemler">
                    <button type="submit" class="buton butonAna"><i class="fa-solid fa-floppy-disk"></i> kaydet</button>
                    <a href="?sayfa=tablo&tablo=<?php echo urlencode($tablo); ?>&seki=veri" class="buton"><i class="fa-solid fa-xmark"></i> vazgeç</a>
                </div>
            </form>
        </div>

    <?php else: ?>

        <div class="kart">
            <div class="kartBaşlık">
                <h3>yeni kayıt ekle</h3>
            </div>

            <form method="post" class="formIzgara" id="kayıtEkleForm">
                <input type="hidden" name="islem" value="veriekle">

                <?php foreach ($sutunlar as $sutun): ?>
                    <?php
                    $alan = $sutun['ad'];

                    if (!empty($sutun['otomatik'])) {
                        continue;
                    }

                    $tur = ankaalanturu ($sutun['tip']);
                    ?>
                    <label class="alan">
                        <?php echo htmlspecialchars($alan); ?> <small><?php echo htmlspecialchars($sutun['tip']); ?></small>
                        <?php if ($tur === 'textarea'): ?>
                            <textarea name="<?php echo htmlspecialchars($alan); ?>" rows="2"></textarea>
                        <?php else: ?>
                            <input type="<?php echo $tur; ?>" name="<?php echo htmlspecialchars($alan); ?>">
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>

                <div class="formEylemler">
                    <button type="submit" class="buton butonAna"><i class="fa-solid fa-plus"></i> ekle</button>
                </div>
</form>
    </div>

<?php endif; ?>

<?php else: ?>

    <div class="kart">
        <div class="kartBaşlık">
            <h3>tablo yapısı: <?php echo htmlspecialchars($tablo); ?></h3>
        </div>

        <div class="tabloSarıcı">
            <table class="veriTablo">
                <thead>
                    <tr>
                        <th>sütun</th>
                        <th>tip</th>
                        <th>null</th>
                        <th>varsayılan</th>
                        <th>ekstra</th>
                        <th class="hizalıSağ">işlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sutunlar as $sutun): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($sutun['ad']); ?></strong></td>
                            <td><?php echo htmlspecialchars($sutun['tip']); ?></td>
                            <td><?php echo empty($sutun['boskalabilir']) ? 'hayır' : 'evet'; ?></td>
                            <td><?php echo htmlspecialchars((string) $sutun['varsayilan']); ?></td>
                            <td>
                                <?php
                                $ekstralar = [];

                                if (!empty($sutun['birincil'])) {
                                    $ekstralar[] = 'birincil';
                                }

                                if (!empty($sutun['otomatik'])) {
                                    $ekstralar[] = 'otomatik artış';
                                }

                                echo htmlspecialchars(implode(', ', $ekstralar));
                                ?>
                            </td>
                            <td class="hizalıSağ">
                                <?php if ($sutun['ad'] !== $birincil): ?>
                                    <form method="post" class="satırİçiForm" data-onay="<?php echo htmlspecialchars($sutun['ad'] . ' sütunu silinecek, emin misin?'); ?>">
                                        <input type="hidden" name="islem" value="sutunsil">
                                        <input type="hidden" name="sutun_ad" value="<?php echo htmlspecialchars($sutun['ad']); ?>">
                                        <button type="submit" class="buton butonKüçük butonTehlikeli"><i class="fa-solid fa-trash-can"></i> sütunu sil</button>
                                    </form>
                                <?php else: ?>
                                    <span class="rozet">birincil</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="kart">
        <div class="kartBaşlık">
            <h3>sütun ekle</h3>
        </div>

        <form method="post" class="formIzgara">
            <input type="hidden" name="islem" value="sutunekle">

            <label class="alan">sütun adı
                <input type="text" name="yeni_sutun_ad">
            </label>

            <label class="alan">tip
                <select name="yeni_sutun_tip">
                    <?php foreach (['varchar', 'int', 'bigint', 'text', 'longtext', 'datetime', 'date', 'timestamp', 'decimal', 'double', 'boolean', 'json'] as $tip): ?>
                        <option value="<?php echo $tip; ?>"><?php echo $tip; ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="alan">uzunluk
                <input type="text" name="yeni_sutun_uzunluk" value="255">
            </label>

            <label class="alan">varsayılan <small>boş bırakılırsa null</small>
                <input type="text" name="yeni_sutun_varsayilan">
            </label>

            <label class="işaretle">
                <input type="checkbox" name="yeni_sutun_bos" value="1" checked>
                null olabilir
            </label>

            <label class="işaretle">
                <input type="checkbox" name="yeni_sutun_tekil" value="1">
                tekil (unique)
            </label>

<div class="formEylemler">
                <button type="submit" class="buton butonAna"><i class="fa-solid fa-plus"></i> sütun ekle</button>
            </div>
        </form>
    </div>

    <div class="kart">
        <div class="kartBaşlık">
            <h3><i class="fa-solid fa-layer-group"></i> İndeks Yöneticisi</h3>
            <span class="küçükNot">bu tablodaki tüm indeksler • <?php echo count($indexler); ?> adet</span>
        </div>

        <?php if ($indexler): ?>
            <div class="tabloSarıcı">
                <table class="veriTablo">
                    <thead>
                        <tr>
                            <th>Ad</th>
                            <th>Tip</th>
                            <th>Alanlar</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($indexler as $ix): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($ix['ad']); ?></code></td>
                                <td><span class="rozet"><?php echo!empty($ix['tekil']) ? 'UNIQUE' : 'INDEX'; ?></span></td>
                                <td><?php echo htmlspecialchars(implode(', ', (array) ($ix['alanlar'] ?? []))); ?></td>
                                <td>
                                    <?php if ($ix['ad'] !== 'PRIMARY'): ?>
                                        <form method="post" style="margin:0" data-onay="Bu indeksi silmek istediğine emin misin?">
                                            <input type="hidden" name="islem" value="indekssil">
                                            <input type="hidden" name="indeks_ad" value="<?php echo htmlspecialchars($ix['ad']); ?>">
                                            <button type="submit" class="buton butonKüçük butonTehlikeli"><i class="fa-solid fa-trash-can"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="bildirim bildirim-bilgi">Bu tabloda henüz yönetilebilir indeks yok.</div>
        <?php endif; ?>

        <h4 class="gezginBaşlık" style="margin-top:16px"><i class="fa-solid fa-plus"></i> Yeni indeks</h4>
        <form method="post" class="formIzgara">
            <input type="hidden" name="islem" value="indeksekle">

            <label class="alan">indeks adı
                <input type="text" name="yeni_indeks_ad" placeholder="idx_alan">
            </label>

            <label class="alan">alanlar <small>birden fazla için ctrl+basılı tut</small>
                <select name="yeni_indeks_alanlar[]" multiple size="5">
                    <?php foreach ($sutunlar as $sutun): ?>
                        <option value="<?php echo htmlspecialchars($sutun['ad']); ?>"><?php echo htmlspecialchars($sutun['ad']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="işaretle">
                <input type="checkbox" name="yeni_indeks_tekil" value="1">
                tekil (unique index)
            </label>

            <div class="formEylemler">
                <button type="submit" class="buton butonAna"><i class="fa-solid fa-layer-group"></i> indeks ekle</button>
            </div>
        </form>
    </div>

<?php endif; ?>
