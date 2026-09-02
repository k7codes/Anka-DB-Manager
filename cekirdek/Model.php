<?php

class Model
{
    private $tabloadi;

    private $baglantiadi = 'varsayilan';

    private $birincilalani = 'id';

    private $veriler = [];

    private $kayitivar = false;

    private $hazirsorgu;

    public static function tablo($tabloadi, $baglantiadi = 'varsayilan')
    {
        $ornek = new self;

        $ornek->tabloadi = $tabloadi;

        $ornek->baglantiadi = $baglantiadi;

        return $ornek;
    }

    public function tabloadi()
    {
        return $this->tabloadi;
    }

    private function sorgu()
    {
        return Sorgu::tablo($this->tabloadi, $this->baglantiadi);
    }

    private function hazirsorgu()
    {
        if ($this->hazirsorgu === null) {
            $this->hazirsorgu = Sorgu::tablo($this->tabloadi, $this->baglantiadi);
        }

        return $this->hazirsorgu;
    }

    public function bul($deger, $birincilalan = null)
    {
        if ($birincilalan !== null) {
            $this->birincilalani = $birincilalan;
        }

        $satir = $this->sorgu()->nerede($this->birincilalani, $deger)->ilk();

        if (!$satir) {
            return null;
        }

        $this->veriler = $satir;

        $this->kayitivar = true;

        return $this;
    }

    public function yeni($veri = [])
    {
        $this->veriler = $veri;

        $this->kayitivar = false;

        return $this;
    }

    public function doldur($veri)
    {
        $this->veriler = array_merge($this->veriler, $veri);

        return $this;
    }

    public function kaydet()
    {
        if ($this->kayitivar) {
            $anahtar = $this->birincildeger();

            if ($anahtar === null) {
                throw new Exception('birincil anahtar değeri yok');
            }

            return $this->sorgu()->nerede($this->birincilalani, $anahtar)->guncelle($this->veriler);
        }

        $id = $this->sorgu()->ekle($this->veriler);

        $this->birincilyaz($id);

        $this->kayitivar = true;

        return $id;
    }

    public function sil()
    {
        if (!$this->kayitivar) {
            return false;
        }

        $anahtar = $this->birincildeger();

        if ($anahtar === null) {
            return false;
        }

        return $this->sorgu()->nerede($this->birincilalani, $anahtar)->sil();
    }

    private function birincildeger()
    {
        return isset($this->veriler[$this->birincilalani]) ? $this->veriler[$this->birincilalani] : null;
    }

    private function birincilyaz($deger)
    {
        $this->veriler[$this->birincilalani] = $deger;
    }

    public function nerede($alan, $operatorveyadeger, $deger = null)
    {
        if ($deger === null) {
            $this->hazirsorgu()->nerede($alan, $operatorveyadeger);
        } else {
            $this->hazirsorgu()->nerede($alan, $operatorveyadeger, $deger);
        }

        return $this;
    }

    public function sirala($alan, $yon = 'asc')
    {
        $this->hazirsorgu()->sirala($alan, $yon);

        return $this;
    }

    public function sinir($adet)
    {
        $this->hazirsorgu()->sinir($adet);

        return $this;
    }

    public function atla($adet)
    {
        $this->hazirsorgu()->atla($adet);

        return $this;
    }

    public function sayfa($sayfanumarasi, $adet)
    {
        $this->hazirsorgu()->sayfa($sayfanumarasi, $adet);

        return $this;
    }

    public function getir()
    {
        return $this->hazirsorgu()->getir();
    }

    public function ilksatir()
    {
        return $this->hazirsorgu()->ilk();
    }

    public function adet()
    {
        return $this->hazirsorgu()->say();
    }

    public function silkosullu()
    {
        return $this->hazirsorgu()->sil();
    }

    public function guncellekosullu($veri)
    {
        return $this->hazirsorgu()->guncelle($veri);
    }

    public function tumveri()
    {
        return $this->veriler;
    }

    public function __set($alan, $deger)
    {
        $this->veriler[$alan] = $deger;
    }

    public function __get($alan)
    {
        return isset($this->veriler[$alan]) ? $this->veriler[$alan] : null;
    }

    public function __isset($alan)
    {
        return isset($this->veriler[$alan]);
    }
}