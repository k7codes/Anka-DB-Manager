(function () {
    var alan = document.getElementById('erAlan');
    if (!alan) { return; }

    var veri = null;
    try { veri = JSON.parse(alan.getAttribute('data-er')); } catch (e) { veri = null; }
    if (!veri || !veri.tablolar) {
        alan.innerHTML = '<div class="erBos">diyagram verisi alınamadı</div>';
        return;
    }

    var SVG = 'http://www.w3.org/2000/svg';
    var gor = document.createElement('div');
    gor.className = 'erKaydir';
    var svg = document.createElementNS(SVG, 'svg');
    svg.setAttribute('class', 'erSvg');
    gor.appendChild(svg);
    alan.innerHTML = '';
    alan.appendChild(gor);

    function yarat(kural) {
        return document.createElementNS(SVG, kural);
    }

    var SutYuk = 22, BasYuk = 30, OzG = 6, YatBosluk = 90, DikeyBosluk = 40, Sol = 40, Ust = 40;

    var tabloKonum = {};
    var tabloEleman = {};
    var tabloAdlari = Object.keys(veri.tablolar);

    var kolonGenislik = {}, kolonYuksek = {};
    for (var i = 0; i < tabloAdlari.length; i++) {
        var ad = tabloAdlari[i];
        var uzunluk = 0;
        veri.tablolar[ad].forEach(function (s) { if (s.ad.length > uzunluk) uzunluk = s.ad.length; });
        if (ad.length > uzunluk) uzunluk = ad.length;
        kolonGenislik[ad] = Math.max(170, uzunluk * 8 + 82);
        kolonYuksek[ad] = BasYuk + veri.tablolar[ad].length * SutYuk + OzG;
    }

    function cizKonum() {
        var maks = Math.ceil(Math.sqrt(tabloAdlari.length));
        for (var k2 = 0; k2 < tabloAdlari.length; k2++) {
            var a = tabloAdlari[k2];
            tabloKonum[a] = { x: Sol + (k2 % maks) * (330 + YatBosluk), y: Ust + Math.floor(k2 / maks) * (300 + DikeyBosluk) };
        }
    }
    cizKonum();

    var maxX = Sol, maxY = Ust;

    function cizTablo(ad) {
        var pos = tabloKonum[ad];
        var gw = kolonGenislik[ad], gh = kolonYuksek[ad];
        var g = yarat('g');
        g.setAttribute('class', 'erTablo');
        g.setAttribute('transform', 'translate(' + pos.x + ',' + pos.y + ')');
        g.setAttribute('data-ad', ad);

        var dik = yarat('rect');
        dik.setAttribute('width', gw);
        dik.setAttribute('height', gh);
        dik.setAttribute('rx', 10);
        g.appendChild(dik);

        var bas = yarat('rect');
        bas.setAttribute('class', 'erBaslık');
        bas.setAttribute('width', gw);
        bas.setAttribute('height', BasYuk);
        bas.setAttribute('rx', 10);
        g.appendChild(bas);

        var katil = yarat('text');
        katil.setAttribute('x', 12);
        katil.setAttribute('y', (BasYuk / 2) + 5);
        katil.setAttribute('class', 'erBaslıkMetin');
        katil.textContent = ad;
        g.appendChild(katil);

        var acBtn = yarat('a');
        acBtn.setAttribute('href', '?sayfa=tablo&tablo=' + encodeURIComponent(ad));
        acBtn.setAttribute('target', '_blank');
        acBtn.setAttribute('rel', 'noopener');
        acBtn.setAttribute('title', 'veri sayfasını aç');
        acBtn.setAttribute('class', 'erAç');
        var acIkon = yarat('text');
        acIkon.setAttribute('x', gw - 12);
        acIkon.setAttribute('y', (BasYuk / 2) + 5);
        acIkon.setAttribute('text-anchor', 'end');
        acIkon.textContent = '⤢';
        acBtn.appendChild(acIkon);
        g.appendChild(acBtn);

        var suts = veri.tablolar[ad];
        for (var s = 0; s < suts.length; s++) {
            var y = BasYuk + s * SutYuk;
            var col = suts[s];

            if (col.pk) {
                var pk = yarat('rect');
                pk.setAttribute('class', 'erPkKutu');
                pk.setAttribute('x', 6);
                pk.setAttribute('y', y + 3);
                pk.setAttribute('width', 20);
                pk.setAttribute('height', 16);
                pk.setAttribute('rx', 4);
                g.appendChild(pk);
                var pkM = yarat('text');
                pkM.setAttribute('class', 'erPkMetin');
                pkM.setAttribute('x', 16);
                pkM.setAttribute('y', y + 15);
                pkM.setAttribute('text-anchor', 'middle');
                pkM.textContent = 'PK';
                g.appendChild(pkM);
            }

            var tv = yarat('text');
            tv.setAttribute('x', col.pk ? 32 : 12);
            tv.setAttribute('y', y + SutYuk - 6);
            tv.setAttribute('class', 'erSutunAd');
            tv.textContent = col.ad;
            g.appendChild(tv);

            if (col.tip && col.tip !== '') {
                var tp = yarat('text');
                tp.setAttribute('x', gw - 8);
                tp.setAttribute('y', y + SutYuk - 6);
                tp.setAttribute('text-anchor', 'end');
                tp.setAttribute('class', 'erSutunTip');
                tp.textContent = col.tip;
                g.appendChild(tp);
            }
        }

        g.addEventListener('mousedown', function (ev) {
            ev.stopPropagation();
            baslatTabloyuSurukle(ev, g);
        });

        svg.appendChild(g);
        tabloEleman[ad] = g;
        if (pos.x + gw > maxX) maxX = pos.x + gw;
        if (pos.y + gh > maxY) maxY = pos.y + gh;
    }

    for (var ti = 0; ti < tabloAdlari.length; ti++) cizTablo(tabloAdlari[ti]);

    function baslatTabloyuSurukle(ev, g) {
        var ad = g.getAttribute('data-ad');
        var basx = ev.clientX, basy = ev.clientY;
        var suruklendi = false;
        var asildi = ev.target.closest ? ev.target.closest('a') : null;
        if (asildi) { return; }

        var hamla = function (me) {
            var dx = me.clientX - basx;
            var dy = me.clientY - basy;
            if (!suruklendi && Math.abs(dx) + Math.abs(dy) > 4) {
                suruklendi = true;
                g.style.cursor = 'grabbing';
            }
            if (suruklendi) {
                tabloKonum[ad].x += dx / olcek;
                tabloKonum[ad].y += dy / olcek;
                basx = me.clientX;
                basy = me.clientY;
                guncelleBaglantilar();
                guncelleKonum();
            }
        };
        var bırak = function () {
            window.removeEventListener('mousemove', hamla);
            window.removeEventListener('mouseup', bırak);
            g.style.cursor = '';
            if (!suruklendi) {
                window.open('?sayfa=tablo&tablo=' + encodeURIComponent(ad), '_blank');
            }
        };
        window.addEventListener('mousemove', hamla);
        window.addEventListener('mouseup', bırak);
    }

    function cizFk() {
        veri.fk.forEach(function (fk) {
            var k = tabloKonum[fk.kaynak], h = tabloKonum[fk.hedef];
            if (!k || !h) { return; }
            var x1 = k.x + kolonGenislik[fk.kaynak], y1 = k.y + BasYuk;
            var x2 = h.x, y2 = h.y + BasYuk;
            var orta = (x1 + x2) / 2;
            var d = 'M ' + x1 + ' ' + y1 + ' Q ' + orta + ' ' + y1 + ' ' + orta + ' ' + y2 + ' T ' + x2 + ' ' + y2;
            var yol = yarat('path');
            yol.setAttribute('d', d);
            yol.setAttribute('class', 'erCizgi');
            svg.appendChild(yol);
            svg.insertBefore(yol, svg.firstChild);
        });
    }

    cizFk();

    svg.setAttribute('width', maxX + Sol);
    svg.setAttribute('height', maxY + Ust);

    var pan = { x: 0, y: 0, surukle: false, bx: 0, by: 0, aktif: false };
    var olcek = 1;

    function uygula() {
        gor.style.transform = 'translate(' + pan.x + 'px,' + pan.y + 'px) scale(' + olcek + ')';
    }

    alan.addEventListener('mousedown', function (e) {
        var asildi = e.target.closest ? e.target.closest('.erTablo') : null;
        if (asildi) { return; }
        pan.surukle = true;
        pan.aktif = true;
        pan.bx = e.clientX;
        pan.by = e.clientY;
    });

    window.addEventListener('mousemove', function (e) {
        if (!pan.surukle) { return; }
        pan.x += e.clientX - pan.bx;
        pan.y += e.clientY - pan.by;
        pan.bx = e.clientX;
        pan.by = e.clientY;
        uygula();
    });

    window.addEventListener('mouseup', function () {
        pan.surukle = false;
        pan.aktif = false;
    });

    alan.addEventListener('wheel', function (e) {
        e.preventDefault();
        var r = alan.getBoundingClientRect();
        var mx = e.clientX - r.left;
        var my = e.clientY - r.top;
        var c = e.deltaY > 0 ? 0.9 : 1.1;
        var yeni = Math.min(2.5, Math.max(0.25, olcek * c));
        var mxx = (mx - pan.x) / olcek;
        var myy = (my - pan.y) / olcek;
        olcek = yeni;
        pan.x = mx - mxx * olcek;
        pan.y = my - myy * olcek;
        uygula();
    }, { passive: false });

    function guncelleKonum() {
        tabloAdlari.forEach(function (ad) {
            var el = tabloEleman[ad];
            var p = tabloKonum[ad];
            el.setAttribute('transform', 'translate(' + p.x + ',' + p.y + ')');
        });
    }

    function guncelleBaglantilar() {
        var cizgiler = svg.querySelectorAll('.erCizgi');
        [].forEach.call(cizgiler, function (c) { c.remove(); });
        cizFk();
    }

    function sigdir() {
        var r = alan.getBoundingClientRect();
        var sx = r.width / (maxX + Sol);
        var sy = r.height / (maxY + Ust);
        olcek = Math.min(1, Math.min(sx, sy) * 0.95);
        pan.x = (r.width - (maxX + Sol) * olcek) / 2;
        pan.y = (r.height - (maxY + Ust) * olcek) / 2;
        uygula();
    }

    var erSigdir = document.getElementById('erHaritayaSigdir');
    if (erSigdir) {
        erSigdir.addEventListener('click', function () {
            sigdir();
        });
    }

    var erDuz = document.getElementById('erOtomatikDuz');
    if (erDuz) {
        erDuz.addEventListener('click', function () {
            cizKonum();
            guncelleKonum();
            guncelleBaglantilar();
            sigdir();
        });
    }

    sigdir();
})();
