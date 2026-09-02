<?php

$kayitlar = [];

$hedef = trim(ankagirdi ('hedef', ''));

$cercevebilgi = null;

if (ankagirdi ('gonder')) {
    if ($hedef === '' || !is_dir($hedef)) {
        $motor = new Enjeksiyon;

        $motor->kayit('hedef klasör mevcut değil: ' . $hedef, 'hata');

        $kayitlar = $motor->kayitlar;
    } else {
        $motor = new Enjeksiyon;

        $cercevebilgi = $motor->cercevetespit($hedef);

        $kayitlar = $motor->calistir($hedef);
    }
}
?>
<div class="kart">
    <div class="kartBaşlık">
        <h3><i class="fa-solid fa-syringe"></i> proje enjeksiyonu</h3>
        <span class="küçükNot">anka kendini hedef projeye kopyalar, giriş noktasını bulur ve oraya entegre eder</span>
    </div>

    <form method="post" class="enjeksiyonForm">
        <label class="alan alanBüyük">hedef proje klasörü
            <input type="text" name="hedef" value="<?php echo htmlspecialchars($hedef); ?>" placeholder="C:\projeler\kisi-yonetimi">
        </label>

        <div class="küçükNot blokNot">
            desteklenen yapılar: laravel &middot; wordpress &middot; codeigniter &middot; düz php. Klasör web root dışında olsa da olur,
            anka oraya bir <code>ankamanager</code> klasörü kurar ve ana giriş dosyasına (index.php / public/index.php) bir
            satırlık başlatıcı ekler. Sonra tarayıcıdan <code>/ankamanager</code> adresi ile paneline ulaşırsın.
        </div>

        <div class="formEylemler">
            <button type="submit" name="gonder" value="1" class="buton butonAna"><i class="fa-solid fa-syringe"></i> enjekte et</button>
        </div>
    </form>
</div>

<?php if ($cercevebilgi): ?>

    <div class="kart">
        <div class="kartBaşlık">
            <h3>tespit edilen yapı</h3>
        </div>
        <div class="bildirim bildirim-bilgi"><?php echo htmlspecialchars($cercevebilgi['etiket']); ?></div>
    </div>

<?php endif; ?>

<?php if ($kayitlar): ?>

    <div class="kart">
        <div class="kartBaşlık">
            <h3>işlem kaydı</h3>
        </div>

        <div class="işlemKayıtları">
            <?php foreach ($kayitlar as $kayit): ?>
                <div class="işlemKaydı işlem-<?php echo htmlspecialchars($kayit['tur']); ?>">
                    <code class="zaman"><?php echo $kayit['zaman']; ?></code>
                    <span><?php echo htmlspecialchars($kayit['mesaj']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

<?php endif; ?>

<div class="kart">
    <div class="kartBaşlık">
        <h3>nasıl çalışır?</h3>
    </div>

    <ol class="yönergeler">
        <li><strong>tara:</strong> hedef klasör taranır, framework tespit edilir</li>
        <li><strong>kopyala:</strong> çekirdek, panel ve görseller <code>ankamanager/</code> altına kopyalanır</li>
        <li><strong>tikla:</strong> giriş dosyasında <code>declare(strict_types)</code> varsa ondan sonra, yoksa açılış etiketi sonrasına <code>require_once</code> satırı eklenir</li>
        <li><strong>dinle:</strong> gömülü başlatıcı normal isteklerde sessizdir, sadece adresinde <code>ankamanager</code> geçen isteklerde devreye girer</li>
        <li><strong>yönet:</strong> <code>/proje/ankamanager</code> adresinden veritabanı yönetimine başla</li>
    </ol>
</div>