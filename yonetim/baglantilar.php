<?php

$baglantilar = Veritabani::ayarlarioku();

$aktifbaglanti = ankaoturum ('aktifbaglanti');

$islem = ankagirdi ('islem', ankasorgu ('islem'));

if ($islem) {
    try {
        if ($islem === 'ekle' || $islem === 'guncelle') {
            $ad = trim(ankagirdi ('ad', ''));

            if ($ad === '') {
                throw new Exception('bağlantı adı gerekli');
            }

            $eskiad = ankagirdi ('eski_ad', $ad);

            $ayar = [
                'surucu' => ankagirdi ('surucu', 'mysql'),
                'sunucu' => ankagirdi ('sunucu', 'localhost'),
                'port' => ankagirdi ('port', '3306'),
                'veritabaniadi' => ankagirdi ('veritabaniadi', ''),
                'kullaniciadi' => ankagirdi ('dbkullanici', ''),
                'parola' => ankagirdi ('dbparola', ''),
                'karakter' => ankagirdi ('karakter', 'utf8mb4'),
            ];

            Veritabani::kaydet($ayar, $ad);

            if ($eskiad !== $ad) {
                Veritabani::kaldir($eskiad);

                ankaoturum ('aktifbaglanti', $ad);
            }

            ankabildirim ('bağlantı kaydedildi: ' . $ad);
        } elseif ($islem === 'sil') {
            $ad = ankagirdi ('ad', '');

            Veritabani::kaldir($ad);

            if (ankaoturum ('aktifbaglanti') === $ad) {
                ankaoturumsil ('aktifbaglanti');
            }

            ankabildirim ('bağlantı silindi: ' . $ad);
        } elseif ($islem === 'sec') {
            $ad = ankagirdi ('ad', '');

            ankaoturum ('aktifbaglanti', $ad);

            ankabildirim ('aktif bağlantı olarak seçildi: ' . $ad);
        } elseif ($islem === 'testhepsi') {
            $sayim = 0;

            $basarili = 0;

            foreach ($baglantilar as $ad => $ayar) {
                $sayim++;

                try {
                    $sonuc = Veritabani::baglantitest ($ayar);

                    if ($sonuc[0]) {
                        $basarili++;
                    }
                } catch (Exception $hata) {
                }
            }

            ankabildirim ($basarili . ' / ' . $sayim . ' bağlantı çalışıyor');
        } elseif ($islem === 'demokur') {
            $vtad = 'demonet';

            $vtayar = [
                'surucu' => 'sqlite',
                'sunucu' => 'localhost',
                'port' => '',
                'veritabaniadi' => ANKA_VERI . DIRECTORY_SEPARATOR . $vtad . '.sqlite',
                'kullaniciadi' => '',
                'parola' => '',
            ];

            $sonuc = Schema::demoveritabaniolustur ($vtad, $vtayar);

            $vtayar['veritabaniadi'] = $sonuc;

            Veritabani::kaydet($vtayar, $vtad);

            ankaoturum ('aktifbaglanti', $vtad);

            ankabildirim ('demo veritabanı kuruldu, paneller için hazır: ' . $vtad);
        } elseif ($islem === 'vtolustur') {
            $vtad = trim(ankagirdi ('vtolustur_ad', ''));

            $vtayar = [
                'surucu' => ankagirdi ('surucu', 'mysql'),
                'sunucu' => ankagirdi ('sunucu', 'localhost'),
                'port' => ankagirdi ('port', '3306'),
                'veritabaniadi' => '',
                'kullaniciadi' => ankagirdi ('dbkullanici', ''),
                'parola' => ankagirdi ('dbparola', ''),
            ];

            $sonuc = Schema::veritabaniolustur ($vtad, $vtayar);

            $vtayar['veritabaniadi'] = $sonuc;

            Veritabani::kaydet($vtayar, $vtad);

            ankaoturum ('aktifbaglanti', $vtad);

            ankabildirim ('veritabanı oluşturuldu ve bağlandı: ' . $vtad);
        } elseif ($islem === 'test') {
            $ad = ankagirdi ('ad', '');

            $sonuc = Veritabani::baglantitest (Veritabani::ayarlarioku($ad));

            ankabildirim ($ad . ': ' . $sonuc[1], $sonuc[0] ? 'bilgi' : 'hata');
        }
    } catch (Exception $hata) {
        ankabildirim ($hata->getMessage(), 'hata');
    }

    ankayonlendir ('?sayfa=baglantilar');
}

$duzenlenen = ankasorgu ('duzenle', '');

$duzenlenenayar = null;

if ($duzenlenen !== '' && isset($baglantilar[$duzenlenen])) {
    $duzenlenenayar = $baglantilar[$duzenlenen];
}
?>
<div class="kart">
    <div class="kartBaşlık">
        <h3><i class="fa-solid fa-link"></i> bağlantılar</h3>
        <div class="kartEylemler">
            <span class="küçükNot">aktif olanı seç, paneldeki tüm ekranlar onu kullanır</span>
            <a href="?sayfa=baglantilar&islem=demokur" class="buton butonMavi butonKüçük" title="tek tıkla, örnek verili demoyu kur ve bağla"><i class="fa-solid fa-wand-magic-sparkles"></i> demo veritabanı kur</a>
            <a href="?sayfa=baglantilar&islem=testhepsi" class="buton butonKüçük"><i class="fa-solid fa-stethoscope"></i> tümünü test et</a>
        </div>
    </div>

    <?php if (!$baglantilar): ?>
        <div class="bildirim bildirim-bilgi">Kayıtlı bağlantı yok, aşağıdan ilkini ekle</div>
    <?php endif; ?>

    <div class="bağlantıListesi">
        <?php foreach ($baglantilar as $ad => $ayar): ?>
            <div class="bağlantıKartı <?php echo $ad === $aktifbaglanti ? 'seçili' : ''; ?>">
                <div class="bağlantıBilgi">
                    <strong>
                        <i class="fa-solid <?php
                        $surucuikon = 'fa-database';
                        if ($ayar['surucu'] === 'pgsql') $surucuikon = 'fa-database';
                        elseif ($ayar['surucu'] === 'sqlite') $surucuikon = 'fa-file-code';
                        elseif ($ayar['surucu'] === 'sqlsrv') $surucuikon = 'fa-server';
                        echo $surucuikon;
                        ?> surucuIkon"></i>
                        <?php echo htmlspecialchars($ad); ?>
                    </strong>
                    <small><?php echo htmlspecialchars($ayar['surucu'] . ' / ' . $ayar['veritabaniadi']); ?></small>
                </div>

                <div class="bağlantıİşlemler">
                    <?php if ($ad !== $aktifbaglanti): ?>
                        <a href="?sayfa=baglantilar&islem=sec&ad=<?php echo urlencode($ad); ?>" class="buton butonKüçük"><i class="fa-solid fa-check"></i> seç</a>
                    <?php else: ?>
                        <span class="rozet"><i class="fa-solid fa-circle-check"></i> aktif</span>
                    <?php endif; ?>

                    <a href="?sayfa=baglantilar&islem=test&ad=<?php echo urlencode($ad); ?>" class="buton butonKüçük"><i class="fa-solid fa-stethoscope"></i> test</a>
                    <a href="?sayfa=baglantilar&duzenle=<?php echo urlencode($ad); ?>" class="buton butonKüçük"><i class="fa-solid fa-pen"></i> düzenle</a>
                    <form method="post" class="satırİçiForm" data-onay="bu bağlantıyı silmek istediğine emin misin?">
                        <input type="hidden" name="islem" value="sil">
                        <input type="hidden" name="ad" value="<?php echo htmlspecialchars($ad); ?>">
                        <button type="submit" class="buton butonKüçük butonTehlikeli"><i class="fa-solid fa-trash-can"></i> sil</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="kart">
    <div class="kartBaşlık">
        <h3><?php echo $duzenlenenayar ? '<i class="fa-solid fa-pen"></i> bağlantıyı düzenle: <strong>' . htmlspecialchars($duzenlenen) . '</strong>' : '<i class="fa-solid fa-circle-plus"></i> yeni bağlantı ekle'; ?></h3>
    </div>

    <form method="post" class="formIzgara">
        <input type="hidden" name="islem" value="<?php echo $duzenlenenayar ? 'guncelle' : 'ekle'; ?>">

        <?php if ($duzenlenenayar): ?>
            <input type="hidden" name="eski_ad" value="<?php echo htmlspecialchars($duzenlenen); ?>">
        <?php endif; ?>

        <label class="alan">bağlantı adı
            <input type="text" name="ad" value="<?php echo htmlspecialchars($duzenlenenayar ? $duzenlenen : 'varsayilan'); ?>">
        </label>

        <label class="alan">sürücü
            <select name="surucu">
                <?php
                $secili = ($duzenlenenayar ? $duzenlenenayar['surucu'] : 'mysql');

                foreach (['mysql' => 'MySQL / MariaDB', 'pgsql' => 'PostgreSQL', 'sqlite' => 'SQLite', 'sqlsrv' => 'SQL Server'] as $deger => $etiket) {
                    $secim = $secili === $deger ? 'selected' : '';

                    echo '<option value="' . $deger . '" ' . $secim . '>' . $etiket . '</option>';
                }
                ?>
            </select>
        </label>

        <label class="alan">sunucu
            <input type="text" name="sunucu" value="<?php echo htmlspecialchars($duzenlenenayar ? $duzenlenenayar['sunucu'] : 'localhost'); ?>">
        </label>

        <label class="alan">port
            <input type="text" name="port" value="<?php echo htmlspecialchars($duzenlenenayar ? $duzenlenenayar['port'] : '3306'); ?>">
        </label>

        <label class="alan">veritabanı adı <small>sqlite ise dosya yolu</small>
            <input type="text" name="veritabaniadi" value="<?php echo htmlspecialchars($duzenlenenayar ? $duzenlenenayar['veritabaniadi'] : ''); ?>">
        </label>

        <label class="alan">kullanıcı
            <input type="text" name="dbkullanici" value="<?php echo htmlspecialchars($duzenlenenayar ? $duzenlenenayar['kullaniciadi'] : ''); ?>">
        </label>

        <label class="alan">parola
            <input type="password" name="dbparola" value="<?php echo htmlspecialchars($duzenlenenayar ? $duzenlenenayar['parola'] : ''); ?>">
        </label>

        <label class="alan">karakter seti
            <input type="text" name="karakter" value="<?php echo htmlspecialchars($duzenlenenayar && !empty($duzenlenenayar['karakter']) ? $duzenlenenayar['karakter'] : 'utf8mb4'); ?>">
        </label>

        <div class="formEylemler">
            <button type="submit" class="buton butonAna"><i class="fa-solid fa-floppy-disk"></i> kaydet</button>

            <?php if ($duzenlenenayar): ?>
                <a href="?sayfa=baglantilar" class="buton"><i class="fa-solid fa-xmark"></i> vazgeç</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="kart">
    <div class="kartBaşlık">
        <h3><i class="fa-solid fa-database"></i> yeni veritabanı oluştur</h3>
        <span class="küçükNot">mevcut sunucuda yeni bir veritabanı kurar ve bağlar</span>
    </div>

    <form method="post" class="formIzgara">
        <input type="hidden" name="islem" value="vtolustur">

        <label class="alan">veritabanı adı
            <input type="text" name="vtolustur_ad" value="" placeholder="ornek_market" required>
        </label>

        <label class="alan">sürücü
            <select name="surucu">
                <?php
                foreach (['mysql' => 'MySQL / MariaDB', 'pgsql' => 'PostgreSQL', 'sqlite' => 'SQLite', 'sqlsrv' => 'SQL Server'] as $deger => $etiket) {
                    echo '<option value="' . $deger . '">' . $etiket . '</option>';
                }
                ?>
            </select>
        </label>

        <label class="alan">sunucu
            <input type="text" name="sunucu" value="localhost">
        </label>

        <label class="alan">port
            <input type="text" name="port" value="3306">
        </label>

        <label class="alan">kullanıcı
            <input type="text" name="dbkullanici" value="root">
        </label>

        <label class="alan">parola
            <input type="password" name="dbparola" value="">
        </label>

        <div class="formEylemler">
            <button type="submit" class="buton butonAna"><i class="fa-solid fa-circle-plus"></i> veritabanı oluştur</button>
        </div>
    </form>
</div>