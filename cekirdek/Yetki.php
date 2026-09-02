<?php

class Yetki
{
    public static function girisyap($kullanici, $parola)
    {
        $ayarlar = ankaayarlari ();

        $kayitlikullanici = isset($ayarlar['admin_kullanici']) ? $ayarlar['admin_kullanici'] : '';

        $kayitliparola = isset($ayarlar['admin_parola']) ? $ayarlar['admin_parola'] : '';

        if ($kayitliparola === '') {
            return false;
        }

        $dogru = ($kullanici === $kayitlikullanici) && password_verify($parola, $kayitliparola);

        if ($dogru) {
            session_regenerate_id(true);

            ankaoturum ('giris', true);

            ankaoturum ('kullaniciadi', $kullanici);

            ankaaudit ('GİRİŞ', 'başarılı giriş');
        } else {
            ankaaudit ('GİRİŞ HATASI', 'başarısız giriş denemesi');
        }

        return $dogru;
    }

    public static function cikis()
    {
        ankaoturumsil ('giris');

        ankaoturumsil ('kullaniciadi');
    }

    public static function girislimi()
    {
        return ankaoturum ('giris') === true;
    }

    public static function girisgerektir()
    {
        $ayarlar = ankaayarlari ();

        $kurulu = !empty($ayarlar['admin_parola']);

        $sayfa = ankasorgu ('sayfa', 'anasayfa');

        if (!$kurulu && !ANKA_GOMULU) {
            ankayonlendir ('kurulum.php');
        }

        if (in_array($sayfa, ['giris'])) {
            return;
        }

        if (!$kurulu && ANKA_GOMULU) {
            if ($sayfa === 'yapilandirma') {
                return;
            }

            ankayonlendir ('?sayfa=yapilandirma');
        }

        if ($sayfa === 'yapilandirma') {
            return;
        }

        if (!self::girislimi()) {
            ankayonlendir ('?sayfa=giris');
        }
    }
}