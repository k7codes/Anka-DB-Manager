<?php

if (Yetki::girislimi()) {
    ankayonlendir ('?sayfa=anasayfa');
}

$ayarlar = ankaayarlari ();

$parolaayarli = !empty($ayarlar['admin_parola']);

if (!$parolaayarli && !ANKA_GOMULU) {
    ankayonlendir ('kurulum.php');
}

if (!$parolaayarli && ANKA_GOMULU) {
    ankayonlendir ('?sayfa=yapilandirma');
}

$hata = null;

if (ankagirdi ('gonder')) {
    $kullanici = trim(ankagirdi ('kullanici', ''));

    $parola = (string) ankagirdi ('parola', '');

    if (Yetki::girisyap($kullanici, $parola)) {
        ankayonlendir ('?sayfa=anasayfa');
    }

    $hata = 'kullanıcı adı veya parola hatalı';
}
?>
<div class="girişKutusu">

    <div class="girişLogo"><i class="fa-solid fa-database"></i> Anka <span>DB</span></div>

    <?php if ($hata !== null): ?>
        <div class="bildirim bildirim-hata"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($hata); ?></div>
    <?php endif; ?>

    <form method="post" class="girişForm">
        <label class="alan">kullanıcı adı
            <input type="text" name="kullanici" autocomplete="username" placeholder="admin">
        </label>

        <label class="alan">parola
            <input type="password" name="parola" autocomplete="current-password" placeholder="••••••••">
        </label>

        <button type="submit" name="gonder" value="1" class="buton butonAna"><i class="fa-solid fa-right-to-bracket"></i> giriş yap</button>

        <p class="küçükNot girişNot"><i class="fa-solid fa-shield-halved"></i> Yetkisiz erişim denemeleri kayıt altındadır.</p>
    </form>
</div>