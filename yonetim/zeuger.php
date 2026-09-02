<?php

$baglantiadi = ankaaktifbaglanti ();

$verinoktalari = [];

$kaynak = null;

$secilikol = (string) ankagirdi ('kol', '');

$mevcuttablo = null;

$mevcutsayisal = [];

if ($baglantiadi && Veritabani::ayarlarioku($baglantiadi)) {
    try {
        $tablolar = Schema::tablolar($baglantiadi);

        if ($tablolar) {
            $mevcuttablo = $tablolar[0];

            foreach ($tablolar as $t) {
                try {
                    $adet = (int) Veritabani::tekil('SELECT COUNT(*) FROM ' . $t . '', [], $baglantiadi);

                    if ($adet > 0) {
                        $mevcuttablo = $t;

                        break;
                    }
                } catch (Exception $ie) {
                }
            }

            $satirlar = Veritabani::satirlar('select * from ' . $mevcuttablo . ' limit 240', [], $baglantiadi);

            if ($satirlar) {
                $ilk = reset($satirlar);

                $hedefkolon = null;

                foreach ($ilk as $kolon => $deger) {
                    if (is_numeric($deger)) {
                        $mevcutsayisal[] = $kolon;
                    }
                }

                $hedefkolon = $secilikol !== '' && in_array($secilikol, $mevcutsayisal, true)
                    ? $secilikol
                    : ($mevcutsayisal[0] ?? null);

                if ($hedefkolon !== null) {
                    foreach ($satirlar as $satir) {
                        $v = $satir[$hedefkolon];

                        if (is_numeric($v)) {
                            $verinoktalari[] = (float) $v;
                        }
                    }

                    $kaynak = $mevcuttablo . '.' . str_replace('', '', (string) $hedefkolon);
                }
            }
        }
    } catch (Exception $e) {
    }
}

if (!$verinoktalari) {
    mt_srand(42);

    for ($i = 0; $i < 240; $i++) {
        $verinoktalari[] = sin($i / 14) * 50 + mt_rand(-18, 18);
    }
}

$verijson = json_encode($verinoktalari);

$verimin = count($verinoktalari) ? min($verinoktalari) : 0;
$verimax = count($verinoktalari) ? max($verinoktalari) : 0;
$veriort = count($verinoktalari) ? array_sum($verinoktalari) / count($verinoktalari) : 0;
?>
<div class="kart zeugerKart">
    <div class="kartBaşlık">
        <h3><i class="fa-solid fa-water"></i> Veri Okyanusu <em>— canlı 3B dalga</em></h3>
        <span class="küçükNot">gerçek tablo sütununu seç, dalgaları izle · sürükle = döndür, tekerlek = yakınlaş/uzaklaş</span>
    </div>

    <div class="zeugerÜst">
        <span class="hudEtiket"><i class="fa-solid fa-wave-square"></i> <b><?php echo htmlspecialchars($kaynak ?: 'sinyal üreteci'); ?></b></span>

        <?php if ($mevcutsayisal): ?>
            <form method="get" class="zeugerSecici">
                <input type="hidden" name="sayfa" value="zeuger">
                <label>sütun
                    <select name="kol" onchange="this.form.submit()">
                        <?php foreach ($mevcutsayisal as $ks): ?>
                            <option value="<?php echo htmlspecialchars($ks); ?>" <?php echo $ks === $secilikol || (!$secilikol && $ks === ($mevcutsayisal[0] ?? '')) ? 'selected' : ''; ?>><?php echo htmlspecialchars($ks); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </form>
        <?php endif; ?>

        <span class="zeugerIstatistik">
            <span>min <b><?php echo round($verimin, 2); ?></b></span>
            <span>ort <b><?php echo round($veriort, 2); ?></b></span>
            <span>max <b><?php echo round($verimax, 2); ?></b></span>
        </span>
        <span class="zeugerKontroller">
            <button type="button" class="buton butonMavi butonKüçük" id="zeugerDur" title="duraklat"><i class="fa-solid fa-pause"></i></button>
            <button type="button" class="buton butonKırmızı butonKüçük" id="zeugerHiz" title="dalga hızı"><i class="fa-solid fa-gauge-high"></i></button>
        </span>
    </div>

    <div class="zeugerKonteynır">
        <canvas id="zeugerCanvas"></canvas>
    </div>
</div>

<script>window.__ankaZeugerVeri = <?php echo $verijson; ?>;</script>