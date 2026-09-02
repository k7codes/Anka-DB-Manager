<?php

class Sorgu
{
    private $tabloadi;

    private $secimler = [];

    private $kosullar = [];

    private $siralama = [];

    private $gruplama = [];

    private $sinirdegeri;

    private $atladegeri;

    private $birlesimler = [];

    private $baglantiadi = 'varsayilan';

    public static function tablo($tabloadi, $baglantiadi = 'varsayilan')
    {
        $ornek = new self;

        $ornek->tabloadi = $tabloadi;

        $ornek->baglantiadi = $baglantiadi;

        return $ornek;
    }

    public function sec(...$alanlar)
    {
        foreach ($alanlar as $alan) {
            if (is_array($alan)) {
                $this->secimler = array_merge($this->secimler, $alan);
            } else {
                $this->secimler[] = $alan;
            }
        }

        return $this;
    }

    public function nerede($alan, $operatorveyadeger, $deger = null)
    {
        return $this->kosulekle($alan, $operatorveyadeger, $deger, 've');
    }

    public function veyanerede ($alan, $operatorveyadeger, $deger = null)
    {
        return $this->kosulekle($alan, $operatorveyadeger, $deger, 'veya');
    }

    private function kosulekle($alan, $operatorveyadeger, $deger, $mantik)
    {
        if ($deger === null) {
            $deger = $operatorveyadeger;

            $operator = '=';
        } else {
            $operator = $operatorveyadeger;
        }

        $this->kosullar[] = [$alan, $operator, $deger, $mantik];

        return $this;
    }

    public function sirala($alan, $yon = 'asc')
    {
        $yon = strtolower($yon) === 'desc' ? 'desc' : 'asc';

        $this->siralama[] = [$alan, $yon];

        return $this;
    }

    public function grupla(...$alanlar)
    {
        foreach ($alanlar as $alan) {
            $this->gruplama[] = $alan;
        }

        return $this;
    }

    public function sinir($adet)
    {
        $this->sinirdegeri = max(1, (int) $adet);

        return $this;
    }

    public function atla($adet)
    {
        $this->atlaDegeri = max(0, (int) $adet);

        return $this;
    }

    public function sayfa($sayfanumarasi, $adet)
    {
        $sayfanumarasi = max(1, (int) $sayfanumarasi);

        $adet = max(1, (int) $adet);

        $this->sinirdegeri = $adet;

        $this->atlaDegeri = ($sayfanumarasi - 1) * $adet;

        return $this;
    }

    public function birles($tabloadi, $anahtar, $karsilik, $tur = 'inner')
    {
        $this->birlesimler[] = [$tabloadi, $anahtar, $karsilik, $tur];

        return $this;
    }

    private function alanyazi ($alan)
    {
        if (strpos($alan, '(') !== false || strpos($alan, '*') !== false) {
            return $alan;
        }

        if (preg_match('/\s+as\s+/i', $alan)) {
            $parcalar = preg_split('/\s+as\s+/i', $alan, 2);

            return Veritabani::tirnakla($parcalar[0], $this->baglantiadi) . ' as ' . $parcalar[1];
        }

        return Veritabani::tirnakla($alan, $this->baglantiadi);
    }

    private function kosulhazirla()
    {
        if (!$this->kosullar) {
            return ['', []];
        }

        $parcalar = [];

        $parametreler = [];

        $sayac = 0;

        $ilk = true;

        foreach ($this->kosullar as $kosul) {
            list($alan, $operatoryazi, $deger, $mantik) = $kosul;

            $operatorbuyuk = strtoupper(trim($operatoryazi));

            if (in_array($operatorbuyuk, ['IN', 'NOT IN'])) {
                if (!is_array($deger)) {
                    $deger = [$deger];
                }

                $yerler = [];

                foreach ($deger as $d) {
                    $yerler[] = ':p' . $sayac;

                    $parametreler['p' . $sayac] = $d;

                    $sayac++;
                }

                $baglac = $ilk ? '' : $mantik . ' ';

                $parcalar[] = $baglac . Veritabani::tirnakla($alan, $this->baglantiadi) . ' ' . $operatorbuyuk . ' (' . implode(', ', $yerler) . ')';

                $ilk = false;

                continue;
            }

            if (in_array($operatorbuyuk, ['IS NULL', 'IS NOT NULL'])) {
                $baglac = $ilk ? '' : $mantik . ' ';

                $parcalar[] = $baglac . Veritabani::tirnakla($alan, $this->baglantiadi) . ' ' . $operatorbuyuk;

                $ilk = false;

                continue;
            }

            $baglac = $ilk ? '' : $mantik . ' ';

            $parcalar[] = $baglac . Veritabani::tirnakla($alan, $this->baglantiadi) . ' ' . $operatorbuyuk . ' :p' . $sayac;

            $parametreler['p' . $sayac] = $deger;

            $sayac++;

            $ilk = false;
        }

        return [implode(' ', $parcalar), $parametreler];
    }

    public function kosulparametreleri()
    {
        list($icerik, $parametreler) = $this->kosulhazirla();

        return $parametreler;
    }

    public function sqlmetni()
    {
        list($kosulyazi, $secilmis) = $this->kosulhazirla();

        $secimalanlari = $this->secimler ? implode(', ', array_map([$this, 'alanYazi'], $this->secimler)) : '*';

        $sql = 'select ' . $secimalanlari . ' from ' . Veritabani::tirnakla($this->tabloadi, $this->baglantiadi);

        if ($this->birlesimler) {
            foreach ($this->birlesimler as $birlesim) {
                list($tablo, $anahtar, $karsilik, $tur) = $birlesim;

                $kosul = Veritabani::tirnakla($this->tabloadi, $this->baglantiadi) . '.' . Veritabani::tirnakla($anahtar, $this->baglantiadi) . ' = ' . Veritabani::tirnakla($tablo, $this->baglantiadi) . '.' . Veritabani::tirnakla($karsilik, $this->baglantiadi);

                $sql .= ' ' . strtolower($tur) . ' join ' . Veritabani::tirnakla($tablo, $this->baglantiadi) . ' on ' . $kosul;
            }
        }

        if ($kosulyazi !== '') {
            $sql .= ' where ' . $kosulyazi;
        }

        if ($this->gruplama) {
            $sql .= ' group by ' . implode(', ', array_map([$this, 'alanYazi'], $this->gruplama));
        }

        if ($this->siralama) {
            $parcalar = [];

            foreach ($this->siralama as $sira) {
                $parcalar[] = Veritabani::tirnakla($sira[0], $this->baglantiadi) . ' ' . $sira[1];
            }

            $sql .= ' order by ' . implode(', ', $parcalar);
        }

        if ($this->sinirdegeri !== null) {
            $sql .= ' limit ' . (int) $this->sinirdegeri;

            if ($this->atlaDegeri !== null) {
                $sql .= ' offset ' . (int) $this->atlaDegeri;
            }
        }

        return $sql;
    }

    public function kur()
    {
        return [$this->sqlmetni(), $this->kosulparametreleri()];
    }

    public function getir()
    {
        list($sql, $parametreler) = $this->kur();

        return Veritabani::satirlar($sql, $parametreler, $this->baglantiadi);
    }

    public function ilk()
    {
        list($sql, $parametreler) = $this->kur();

        return Veritabani::ilksatir ($sql, $parametreler, $this->baglantiadi);
    }

    public function deger($alan)
    {
        $kopya = clone $this;

        $kopya->secimler = [$alan];

        $kopya->siralama = [];

        $kopya->sinirdegeri = 1;

        $kopya->atlaDegeri = null;

        list($sql, $parametreler) = $kopya->kur();

        return Veritabani::tekil($sql, $parametreler, $this->baglantiadi);
    }

    public function say()
    {
        $kopya = clone $this;

        $kopya->secimler = ['count(*) as toplam'];

        $kopya->siralama = [];

        $kopya->gruplama = [];

        $kopya->sinirdegeri = null;

        $kopya->atlaDegeri = null;

        list($sql, $parametreler) = $kopya->kur();

        $satir = Veritabani::ilksatir ($sql, $parametreler, $this->baglantiadi);

        if (!$satir) {
            return 0;
        }

        $deger = reset($satir);

        return (int) $deger;
    }

    public function ekle($veri)
    {
        if (!$veri) {
            throw new Exception('eklenecek veri boş');
        }

        $alanlar = array_keys($veri);

        $yerler = [];

        $parametreler = [];

        $sayac = 0;

        foreach ($veri as $alan => $deger) {
            $yerler[] = ':ek' . $sayac;

            $parametreler['ek' . $sayac] = $deger;

            $sayac++;
        }

        $sql = 'insert into ' . Veritabani::tirnakla($this->tabloadi, $this->baglantiadi) . ' (' . implode(', ', array_map([$this, 'alanYazi'], $alanlar)) . ') values (' . implode(', ', $yerler) . ')';

        Veritabani::calistir($sql, $parametreler, $this->baglantiadi);

        return Veritabani::soneklenenid ($this->baglantiadi);
    }

    public function guncelle($veri)
    {
        if (!$veri) {
            throw new Exception('güncellenecek veri boş');
        }

        $parcalar = [];

        $parametreler = [];

        $sayac = 0;

        foreach ($veri as $alan => $deger) {
            $parcalar[] = Veritabani::tirnakla($alan, $this->baglantiadi) . ' = :gu' . $sayac;

            $parametreler['gu' . $sayac] = $deger;

            $sayac++;
        }

        list($kosulyazi, $kosulparametreleri) = $this->kosulhazirla();

        if ($kosulyazi === '') {
            throw new Exception('güncelleme için koşul gerekli');
        }

        $parametreler = array_merge($parametreler, $kosulparametreleri);

        $sql = 'update ' . Veritabani::tirnakla($this->tabloadi, $this->baglantiadi) . ' set ' . implode(', ', $parcalar) . ' where ' . $kosulyazi;

        return Veritabani::calistir($sql, $parametreler, $this->baglantiadi);
    }

    public function sil()
    {
        list($kosulyazi, $kosulparametreleri) = $this->kosulhazirla();

        if ($kosulyazi === '') {
            throw new Exception('silme için koşul gerekli');
        }

        $sql = 'delete from ' . Veritabani::tirnakla($this->tabloadi, $this->baglantiadi) . ' where ' . $kosulyazi;

        return Veritabani::calistir($sql, $kosulparametreleri, $this->baglantiadi);
    }
}