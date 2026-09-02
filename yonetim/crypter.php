<?php

$baglantiadi = ankaaktifbaglanti ();

$baglantiyok = !$baglantiadi || !Veritabani::ayarlarioku($baglantiadi);

$tablo = trim((string) (isset($_POST['tablo']) ? $_POST['tablo'] : (isset($_GET['tablo']) ? $_GET['tablo'] : ankaoturum ('crypter_tablo', ''))));

if (isset($_POST['tablo']) || isset($_GET['tablo'])) {
    ankaoturum ('crypter_tablo', $tablo);
}
$sutunlar = [];
$islem = '';
$algoritma = 'sha256';
$sifreleme = 'AES-256-CBC';
$anahtar = '';
$hedef = 'ustune';
$yenisutun = '';
$esik = 6;

$onizleme = [];
$onizlemesutun = '';
$onizlemetablo = '';
$uygulandi = null;
$hata = null;
$uretildi = null;
$secilisutunlar = [];

$dolusutunsecimi = [];
foreach ($_POST as $k => $v) {
    if (strpos($k, 'sutun_') === 0) {
        $dolusutunsecimi[] = $v;
    }
}
$dolusutunsecimi = array_values(array_unique($dolusutunsecimi));

if (!$baglantiyok && ankagirdi ('gonder')) {
    try {
        $tablo = trim(ankagirdi ('tablo', ''));
        $islem = (string) ankagirdi ('islem', 'hash');
        $algoritma = (string) ankagirdi ('algoritma', 'sha256');
        $sifreleme = (string) ankagirdi ('sifreleme', 'AES-256-CBC');
        $anahtar = (string) ankagirdi ('anahtar', '');
        $hedef = (string) ankagirdi ('hedef', 'ustune');
        $yenisutun = trim(ankagirdi ('yenisutun', ''));
        $esik = (int) ankagirdi ('esik', 6);

        if ($tablo === '') {
            throw new Exception('Tablо seçilmelidir');
        }

        $sema = Schema::sutunlar($tablo, $baglantiadi);

        $secili = [];

        foreach ($_POST as $aktarma => $v) {
            if (strpos($aktarma, 'sutun_') === 0) {
                $secili[] = $v;
            }
        }

        $secilisutunlar = $secili = array_values(array_unique($secili));

        $izinsiz = array_filter($secili, function ($a) use ($sema) {
            foreach ($sema as $s) {
                if ($s['ad'] === $a) {
                    return false;
                }
            }

            return true;
        });

        if ($izinsiz || !$secili) {
            throw new Exception('Geçerli bir veya daha fazla sütun seçin');
        }

        $row = Veritabani::ilksatir ('SELECT * FROM ' . Veritabani::tirnakla($tablo, $baglantiadi), [], $baglantiadi);

        $onizlemetablo = $tablo;
        $onizlemesutun = implode(', ', $secili);
        $onizleme = [];

        if ($row) {
            $limit = (int) Veritabani::tekil('SELECT COUNT(*) FROM ' . Veritabani::tirnakla($tablo, $baglantiadi), [], $baglantiadi);
            $limit = max(1, min(8, $limit));

            $ornek = Veritabani::satirlar('SELECT * FROM ' . Veritabani::tirnakla($tablo, $baglantiadi) . ' LIMIT ' . (int) $limit, [], $baglantiadi);

            foreach ($ornek as $satir) {
                $kayit = ['_yer' => []];

                foreach ($secili as $sc) {
                    $ham = isset($satir[$sc]) ? $satir[$sc] : null;
                    $kayit[$sc] = $ham;
                }

                $onizleme[] = $kayit;
            }
        }

        if ($islem === 'sifrele' || $islem === 'coz') {
            if ($anahtar === '') {
                $anahtar = bin2hex(random_bytes(24));
                $uretildi = $anahtar;
            }

            if (strlen($anahtar) < 16) {
                throw new Exception('Anahtar en az 16 karakter olmalıdır');
            }
        }

        if (ankagirdi ('uygula')) {
            $pdo = Veritabani::baglan($baglantiadi);

            $yerad = 'crypter_yedek_' . date('Ymd_His');

            Veritabani::calistir('DROP TABLE IF EXISTS ' . Veritabani::tirnakla($yerad, $baglantiadi), [], $baglantiadi);

            Veritabani::calistir('CREATE TABLE ' . Veritabani::tirnakla($yerad, $baglantiadi) . ' AS SELECT * FROM ' . Veritabani::tirnakla($tablo, $baglantiadi), [], $baglantiadi);

            ankaoturum ('crypter_yedek', ['tablo' => $tablo, 'yer' => $yerad, 'zaman' => date('Y-m-d H:i:s')]);

            $tumsatirlar = Veritabani::satirlar('SELECT * FROM ' . Veritabani::tirnakla($tablo, $baglantiadi), [], $baglantiadi);

            $birincil = null;

            $sorgu = 'SELECT 1';

            foreach ($sema as $s) {
                if ($s['birincil']) {
                    $birincil = $s['ad'];

                    break;
                }
            }

            $kullanilacaksutunlar = $secili;

            if ($hedef === 'yeni' && $yenisutun !== '') {
                foreach ($sema as $s) {
                    if (!in_array($yenisutun, array_column($sema, 'ad'))) {
                        Schema::sutunekle ($tablo, ['ad' => $yenisutun, 'tip' => 'text', 'boskalabilir' => true], $baglantiadi);
                    }
                }

                $kullanilacaksutunlar = [$yenisutun];
            }

            foreach ($tumsatirlar as $satir) {
                $guncel = [];

                if ($hedef === 'yeni' && $yenisutun !== '') {
                    $guncel[$yenisutun] = crypterisle ($islem, $satir[$secili[0]] ?? '', $algoritma, $sifreleme, $anahtar, $esik);
                } else {
                    foreach ($secili as $sc) {
                        $guncel[$sc] = crypterisle ($islem, $satir[$sc] ?? '', $algoritma, $sifreleme, $anahtar, $esik);
                    }
                }

                if ($birincil !== null && isset($satir[$birincil])) {
                    $yerler = [];

                    foreach ($guncel as $s => $v) {
                        $yerler[] = Veritabani::tirnakla($s, $baglantiadi) . ' = ?';
                    }

                    $sql = 'UPDATE ' . Veritabani::tirnakla($tablo, $baglantiadi) . ' SET ' . implode(', ', $yerler) . ' WHERE ' . Veritabani::tirnakla($birincil, $baglantiadi) . ' = ?';

                    $prm = array_values($guncel);
                    $prm[] = $satir[$birincil];

                    Veritabani::calistir($sql, $prm, $baglantiadi);
                }
            }

            $uygulandi = count($tumsatirlar);
            ankabildirim (count($tumsatirlar) . ' satır işlendi · yedek: ' . $yerad);
            ankayonlendir ('?sayfa=crypter');
        }
    } catch (Exception $e) {
        $hata = $e->getMessage();
    }
}

if (!$baglantiyok && !ankagirdi ('gonder')) {
    if ($dolusutunsecimi) {
        $secilisutunlar = $dolusutunsecimi;
    }
    $sema = Schema::sutunlar($tablo !== '' ? $tablo : '', $baglantiadi);
    $sutunlar = $sema ? array_column($sema, 'ad') : [];
}

$tablolar = [];

if (!$baglantiyok) {
    $tablolar = Schema::tablolar($baglantiadi);
}

if (!$baglantiyok && $tablo !== '' && !in_array($tablo, $tablolar, true)) {
    $tablo = '';
    ankaoturum ('crypter_tablo', '');
}

$kalan = ankaoturum ('crypter_yedek');

function crypterisle ($islem, $deger, $algoritma, $sifreleme, $anahtar, $esik)
{
    if ($deger === null) {
        return null;
    }

    $metin = (string) $deger;

    if ($islem === 'hash') {
        if ($algoritma === 'bcrypt') {
            return password_hash($metin, PASSWORD_BCRYPT);
        }

        return hash($algoritma, $metin);
    }

    if ($islem === 'base64') {
        return base64_encode($metin);
    }

    if ($islem === 'base64coz') {
        return base64_decode($metin, true) !== false ? base64_decode($metin, true) : $metin;
    }

    if ($islem === 'hex') {
        return bin2hex($metin);
    }

    if ($islem === 'hexcoz') {
        if (preg_match('/^[0-9a-fA-F]+$/', $metin) && strlen($metin) % 2 === 0) {
            return hex2bin($metin);
        }

        return $metin;
    }

    if ($islem === 'rot13') {
        return str_rot13($metin);
    }

    if ($islem === 'ters') {
        return implode('', array_reverse(mb_str_split($metin, 1, 'UTF-8')));
    }

    if ($islem === 'xor') {
        $k = $anahtar !== '' ? $anahtar : 'AnkaDB';

        $kb = strlen($k);

        if ($kb === 0) {
            return $metin;
        }

        $c = '';

        for ($i = 0; $i < strlen($metin); $i++) {
            $c .= chr(ord($metin[$i]) ^ ord($k[$i % $kb]));
        }

        return base64_encode($c);
    }

    if ($islem === 'sifrele') {
        $aes = in_array($sifreleme, ['AES-128-CBC', 'AES-256-CBC', 'AES-256-GCM'], true) ? $sifreleme : 'AES-256-CBC';
        $ivlen = openssl_cipher_iv_length($aes);

        $iv = random_bytes($ivlen);

        $cipher = openssl_encrypt($metin, $aes, $anahtar, OPENSSL_RAW_DATA, $iv);

        return base64_encode($iv . $cipher);
    }

    if ($islem === 'coz') {
        $aes = in_array($sifreleme, ['AES-128-CBC', 'AES-256-CBC', 'AES-256-GCM'], true) ? $sifreleme : 'AES-256-CBC';
        $ivlen = openssl_cipher_iv_length($aes);
        $raw = base64_decode($metin, true);

        if ($raw === false || strlen($raw) <= $ivlen) {
            return $metin;
        }

        $iv = substr($raw, 0, $ivlen);
        $cipher = substr($raw, $ivlen);

        $cozulen = openssl_decrypt($cipher, $aes, $anahtar, OPENSSL_RAW_DATA, $iv);

        return $cozulen !== false ? $cozulen : $metin;
    }

    if ($islem === 'mask') {
        return cryptermaskele ($metin, $esik);
    }

    return $metin;
}

function cryptermaskele ($metin, $esik)
{
    if (strpos($metin, '@') !== false && filter_var($metin, FILTER_VALIDATE_EMAIL)) {
        [$yer, $alan] = explode('@', $metin, 2);

        $yon = mb_substr($yer, 0, 1, 'UTF-8') . str_repeat('*', max(0, mb_strlen($yer, 'UTF-8') - 2)) . mb_substr($yer, -1, null, 'UTF-8');

        return $yon . '@' . $alan;
    }

    if (preg_match('/^[+]?[0-9 ]{7,}$/', $metin)) {
        return preg_replace('/\d(?=\d{2})/', '*', $metin);
    }

    $uzunluk = mb_strlen($metin, 'UTF-8');

    if ($uzunluk <= $esik) {
        return $metin;
    }

    $goster = max(1, (int) floor($esik / 2));

    $bas = mb_substr($metin, 0, $goster, 'UTF-8');
    $son = mb_substr($metin, -$goster, null, 'UTF-8');

    return $bas . str_repeat('*', max(0, $uzunluk - 2 * $goster)) . $son;
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
            <h3><i class="fa-solid fa-shield-halved"></i> crypter</h3>
            <span class="küçükNot">bağlantı: <?php echo htmlspecialchars($baglantiadi); ?></span>
        </div>

        <?php if ($hata): ?>
            <div class="bildirim bildirim-hata"><?php echo htmlspecialchars($hata); ?></div>
        <?php endif; ?>

        <?php if ($uretildi): ?>
            <div class="bildirim bildirim-bilgi">
                <b>üretilen anahtar</b> (bir yere kopyala — çözme için gerekir):
                <code style="word-break:break-all"><?php echo htmlspecialchars($uretildi); ?></code>
            </div>
        <?php endif; ?>

        <?php if ($uygulandi !== null): ?>
            <div class="bildirim bildirim-basari"><?php echo (int) $uygulandi; ?> satır başarıyla işlendi</div>
        <?php endif; ?>

        <?php if ($kalan && $kalan['tablo']): ?>
            <div class="bildirim bildirim-bilgi">
                <i class="fa-solid fa-clock-rotate-left"></i>
                son işlemin yedeği: <code><?php echo htmlspecialchars($kalan['yer']); ?></code>
                (<?php echo htmlspecialchars($kalan['tablo']); ?> · <?php echo htmlspecialchars($kalan['zaman']); ?>)
                <form method="post" style="display:inline">
                    <button type="submit" name="gonder" value="1" class="buton">işleme devam</button>
                </form>
            </div>
        <?php endif; ?>

        <form method="post" class="konsolForm">
            <div class="ornekCipleri">
                <label class="küçükNot"><i class="fa-solid fa-table"></i> tablo:</label>
                <div class="dtp" data-dtp-options="<?php echo htmlspecialchars(json_encode(array_values($tablolar), JSON_UNESCAPED_UNICODE), ENT_QUOTES); ?>" data-dtp-value="<?php echo htmlspecialchars($tablo); ?>" data-dtp-git="?sayfa=crypter&amp;tablo=">
                    <button type="button" class="dtp-buton"><span class="dtp-etiket"><?php echo $tablo !== '' ? htmlspecialchars($tablo) : '— tablo seç —'; ?></span><i class="fa-solid fa-chevron-down dtp-ok"></i></button>
                    <div class="dtp-menü"></div>
                </div>
            </div>

            <div class="ornekCipleri" id="crypterSema">
                <?php
                $mevcutsutunlar = [];

                if ($tablo !== '') {
                    try {
                        $mevcutsutunlar = array_column(Schema::sutunlar($tablo, $baglantiadi), 'ad');
                    } catch (Exception $e) {
                        $mevcutsutunlar = [];
                    }
                }

                if ($mevcutsutunlar):
                    ?>
                    <label class="küçükNot"><i class="fa-solid fa-list-check"></i> sütunlar:</label>
                    <div class="sorguGecmis">
                        <?php foreach ($mevcutsutunlar as $sc): ?>
                            <label class="gecmisOnge" style="cursor:pointer; display:inline-flex; align-items:center; gap:6px; padding:6px 10px">
                                <input type="checkbox" name="sutun_<?php echo $sc; ?>" value="<?php echo htmlspecialchars($sc); ?>" onchange="this.form.submit()" <?php echo in_array($sc, $secilisutunlar, true) ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($sc); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="ornekCipleri">
                <label class="küçükNot"><i class="fa-solid fa-wand-magic-sparkles"></i> işlem:</label>
                <select name="islem" class="konsolAlan" style="padding:10px" id="crypterIslem" onchange="crypteralangoster ()">
                    <option value="hash" <?php echo $islem === 'hash' ? 'selected' : ''; ?>>hashle (tek yönlü)</option>
                    <option value="sifrele" <?php echo $islem === 'sifrele' ? 'selected' : ''; ?>>şifrele (AES)</option>
                    <option value="coz" <?php echo $islem === 'coz' ? 'selected' : ''; ?>>şifreyi çöz (AES)</option>
                    <option value="base64" <?php echo $islem === 'base64' ? 'selected' : ''; ?>>base64 kodla</option>
                    <option value="base64coz" <?php echo $islem === 'base64coz' ? 'selected' : ''; ?>>base64 çöz</option>
                    <option value="hex" <?php echo $islem === 'hex' ? 'selected' : ''; ?>>hex kodla</option>
                    <option value="hexcoz" <?php echo $islem === 'hexcoz' ? 'selected' : ''; ?>>hex çöz</option>
                    <option value="rot13" <?php echo $islem === 'rot13' ? 'selected' : ''; ?>>rot13</option>
                    <option value="ters" <?php echo $islem === 'ters' ? 'selected' : ''; ?>>ters çevir</option>
                    <option value="xor" <?php echo $islem === 'xor' ? 'selected' : ''; ?>>xor (anahtar ile)</option>
                    <option value="mask" <?php echo $islem === 'mask' ? 'selected' : ''; ?>>maskele / anonimleştir</option>
                </select>
            </div>

            <div class="ornekCipleri" id="crypterAlgoritma">
                <label class="küçükNot"><i class="fa-solid fa-fingerprint"></i> algoritma:</label>
                <select name="algoritma" class="konsolAlan" style="padding:10px">
                    <option value="md5" <?php echo $algoritma === 'md5' ? 'selected' : ''; ?>>md5</option>
                    <option value="sha1" <?php echo $algoritma === 'sha1' ? 'selected' : ''; ?>>sha1</option>
                    <option value="sha256" <?php echo $algoritma === 'sha256' ? 'selected' : ''; ?>>sha256</option>
                    <option value="sha512" <?php echo $algoritma === 'sha512' ? 'selected' : ''; ?>>sha512</option>
                    <option value="sha3-256" <?php echo $algoritma === 'sha3-256' ? 'selected' : ''; ?>>sha3-256</option>
                    <option value="tiger192,3" <?php echo $algoritma === 'tiger192,3' ? 'selected' : ''; ?>>tiger192</option>
                    <option value="whirlpool" <?php echo $algoritma === 'whirlpool' ? 'selected' : ''; ?>>whirlpool</option>
                    <option value="bcrypt" <?php echo $algoritma === 'bcrypt' ? 'selected' : ''; ?>>bcrypt</option>
                </select>
            </div>

            <div class="ornekCipleri" id="crypterSifre">
                <label class="küçükNot"><i class="fa-solid fa-key"></i> anahtar / tuz:</label>
                <input type="text" name="anahtar" value="<?php echo htmlspecialchars($anahtar); ?>" class="konsolAlan" style="padding:10px" placeholder="boş bırakılırsa otomatik üretilir">
            </div>

            <div class="ornekCipleri" id="crypterEsik">
                <label class="küçükNot"><i class="fa-solid fa-eye-slash"></i> mask eşiği (görünen harf):</label>
                <input type="number" name="esik" value="<?php echo (int) $esik; ?>" class="konsolAlan" style="padding:10px; max-width:120px" min="1" max="20">
            </div>

            <div class="ornekCipleri">
                <label class="küçükNot"><i class="fa-solid fa-arrows-split-up-and-left"></i> hedef:</label>
                <select name="hedef" class="konsolAlan" style="padding:10px" id="crypterHedef" onchange="crypterHedefGoster()">
                    <option value="ustune" <?php echo $hedef === 'ustune' ? 'selected' : ''; ?>>seçili sütunların üzerine yaz</option>
                    <option value="yeni" <?php echo $hedef === 'yeni' ? 'selected' : ''; ?>>yeni bir sütuna yaz</option>
                </select>
                <?php if ($hedef === 'yeni'): ?>
                    <input type="text" name="yenisutun" value="<?php echo htmlspecialchars($yenisutun); ?>" class="konsolAlan" style="padding:10px; max-width:220px" placeholder="yeni sütun adı" id="crypterYeni1">
                <?php endif; ?>
            </div>

            <div class="formEylemler">
                <button type="submit" name="gonder" value="1" class="buton butonMavi"><i class="fa-solid fa-eye"></i> önizle</button>
                <button type="submit" name="uygula" value="1" class="buton butonAna" onclick="return confirm('Emin misin? İşlem öncesi otomatik yedek alınır.')"><i class="fa-solid fa-shield-halved"></i> Uygula</button>
            </div>
        </form>
    </div>

    <?php if ($onizlemetablo !== '' && ($onizleme || true)): ?>
        <div class="kart">
            <div class="kartBaşlık">
                <h3><i class="fa-solid fa-table-list"></i> önizleme</h3>
                <span class="küçükNot"><?php echo htmlspecialchars($onizlemetablo); ?> · sütunlar: <?php echo htmlspecialchars($onizlemesutun); ?> · işlem: <?php echo htmlspecialchars($islem); ?></span>
            </div>

            <?php if ($onizleme): ?>
                <div class="tabloSarıcı">
                    <table class="veriTablo">
                        <thead>
                            <tr>
                                <th>girdi</th>
                                <th>çıktı</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($onizleme as $satir): ?>
                                <?php
                                $girdisayisi = 0;
                                $ciktisayisi = 0;
                                ?>
                                <?php foreach ($satir as $sc => $deger): ?>
                                    <?php if ($sc === '_yer') { continue; } ?>
                                    <?php $girdisayisi++; ?>
                                    <?php $cikti = crypterisle ($islem, $deger, $algoritma, $sifreleme, $anahtar, $esik); ?>
                                    <?php $ciktisayisi++; ?>
                                    <tr>
                                        <td><code class="sqlÖnizleme"><?php echo $deger === null ? '<span class="null">null</span>' : htmlspecialchars(substr((string) $deger, 0, 60)); ?></code></td>
                                        <td><code class="sqlÖnizleme"><?php echo $cikti === null ? '<span class="null">null</span>' : htmlspecialchars(substr((string) $cikti, 0, 60)); ?></code></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="bildirim bildirim-bilgi">Tablo boş veya sorgu sonucu yok — önizleme üretilemedi.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="kart">
        <div class="kartBaşlık">
            <h3><i class="fa-solid fa-circle-info"></i> notlar</h3>
        </div>
        <ul class="sorguGecmis" style="list-style:none; margin:0; padding:0; display:block">
            <li class="küçükNot">• <b>hash</b>: tek yönlü, geri döndürülemez (parola/anonimleştirme için).</li>
            <li class="küçükNot">• <b>şifrele / çöz</b>: AES CBC, anahtar gerektirir; boş bırakılırsa rastgele üretilir ve gösterilir.</li>
            <li class="küçükNot">• <b>xor</b>: anahtar ile; aynı anahtar+ayrı op ile geri alınabilir.</li>
            <li class="küçükNot">• <b>mask</b>: eposta/telefon/kelime kısmen gizler (kişisel veri anonimleştirme).</li>
            <li class="küçükNot">• <b>güvenlik</b>: her Uygula öncesi tablo <code>crypter_yedek_*</code> adında otomatik yedeklenir; Bağlantılar sayfasından geri dönülebilir.</li>
        </ul>
    </div>

    <?php if ($onizlemetablo !== '' && $uygulandi === null && $onizleme && ankagirdi ('uygula')): ?>
        <div class="bildirim bildirim-hata">Sorgu çalıştırılırken hata oluştu. Yedekten geri yükleyebilirsin.</div>
    <?php endif; ?>

<?php endif; ?>

<script>
function crypteralangoster () {
    var i = document.getElementById('crypterIslem');
    var v = i ? i.value : '';
    var alg = document.getElementById('crypterAlgoritma');
    var sif = document.getElementById('crypterSifre');
    var esk = document.getElementById('crypterEsik');
    var yeni = document.querySelector('#crypterHedefGoster');
    if (alg) alg.style.display = v === 'hash' ? '' : 'none';
    if (sif) sif.style.display = (v === 'sifrele' || v === 'coz' || v === 'xor') ? '' : 'none';
    if (esk) esk.style.display = v === 'mask' ? '' : 'none';
}
crypteralangoster ();
</script>