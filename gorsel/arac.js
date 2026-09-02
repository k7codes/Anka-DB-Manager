(function () {
    var buton = document.createElement('style');
    buton.innerHTML = 'select, input[type=text], input[type=password], textarea { font-family: inherit; }';
    document.head.appendChild(buton);

    var onayi = document.querySelectorAll('form[data-onay], button[data-onay-var]');
    for (var i = 0; i < onayi.length; i++) {
        var form = onayi[i];
        form.addEventListener('submit', function (olay) {
            var mesaj = this.getAttribute('data-onay') || this.getAttribute('data-onay-var') || 'bu işlemi yapmak istediğine emin misin?';
            if (!window.confirm(mesaj)) {
                olay.preventDefault();
                return false;
            }
        });
    }

    var bildirims = document.querySelectorAll('.bildirim');
    for (var j = 0; j < bildirims.length; j++) {
        (function (eleman) {
            var tik = document.createElement('span');
            tik.className = 'bildirimKapat';
            tik.innerHTML = '\u2715';
            tik.title = 'kapat';
            tik.addEventListener('click', function () {
                eleman.classList.add('bildirimKapanis');
                setTimeout(function () { eleman.remove(); }, 320);
            });
            eleman.appendChild(tik);
        })(bildirims[j]);
    }

    var imlecParilti = document.createElement('div');
    imlecParilti.id = 'imlecParilti';
    imlecParilti.style.cssText = 'position:fixed;width:360px;height:360px;border-radius:50%;' +
        'pointer-events:none;z-index:0;transform:translate(-50%,-50%);' +
        'background:radial-gradient(circle, rgba(255,45,45,0.10), transparent 60%);' +
        'transition:left 0.08s linear, top 0.08s linear;';
    document.body.appendChild(imlecParilti);

    document.addEventListener('mousemove', function (olay) {
        imlecParilti.style.left = olay.clientX + 'px';
        imlecParilti.style.top = olay.clientY + 'px';
    });

    var sayfaAlani = document.querySelector('.sayfaAlani');
    if (sayfaAlani) {
        sayfaAlani.classList.add('girisFade');
    }

    var canliSaat = document.getElementById('canliSaat');
    if (canliSaat) {
        var tazele = function () {
            var simdi = new Date();
            var saat = String(simdi.getHours()).padStart(2, '0');
            var dakika = String(simdi.getMinutes()).padStart(2, '0');
            var saniye = String(simdi.getSeconds()).padStart(2, '0');
            var gun = String(simdi.getDate()).padStart(2, '0');
            var ay = String(simdi.getMonth() + 1).padStart(2, '0');
            canliSaat.textContent = gun + '.' + ay + '.' + simdi.getFullYear() + ' ' + saat + ':' + dakika + ':' + saniye;
        };
        tazele();
        setInterval(tazele, 1000);
    }

    var gecmisDugmeleri = document.querySelectorAll('.gecmisOnge, .cipler');
    var konsol = document.querySelector('.konsolAlan');
    if (gecmisDugmeleri.length && konsol) {
        for (var g = 0; g < gecmisDugmeleri.length; g++) {
            gecmisDugmeleri[g].addEventListener('click', function () {
                konsol.value = this.getAttribute('data-sql');
                konsol.focus();
            });
        }
    }

    function ankaDrpKapat(dtp) {
        dtp.classList.remove('dtp-acik');
    }

    function ankaDrpKapat(dtp) {
        dtp.classList.remove('dtp-acik');
    }

    var tumSelect = document.querySelectorAll('select');
    for (var s = 0; s < tumSelect.length; s++) {
        var sec = tumSelect[s];
        if (sec.closest('.dtp')) { continue; }

        var sari = document.createElement('div');
        sari.className = 'dtp';
        sari.setAttribute('data-anka-select', '1');
        var ilkSecim = (sec.selectedOptions && sec.selectedOptions.length) ? sec.selectedOptions[0].textContent : 'seç';
        sari.innerHTML = '<button type="button" class="dtp-buton"><span class="dtp-etiket"></span><i class="fa-solid fa-chevron-down dtp-ok"></i></button><div class="dtp-menü"></div>';
        var etk = sari.querySelector('.dtp-etiket');
        etk.textContent = (ilkSecim || '').trim() || 'seç';

        sec.insertAdjacentElement('beforebegin', sari);
        sec.style.display = 'none';
        sec.setAttribute('data-anka-sarılı', '1');

        sari._ankanSelect = sec;
    }

    var dtpler = document.querySelectorAll('.dtp');
    for (var d = 0; d < dtpler.length; d++) {
        (function (k) {
            var buton = k.querySelector('.dtp-buton');
            var menu = k.querySelector('.dtp-menü');
            var bildir = k.getAttribute('data-dtp-git');
            var sariSec = k._ankanSelect;
            var tazeMenü = function () {
                if (!menu) { return; }
                menu.innerHTML = '';
                var ops = [];
                var secili = k.getAttribute('data-dtp-value') || '';
                if (k._ankanSelect) {
                    var lst = k._ankanSelect.options;
                    for (var i = 0; i < lst.length; i++) {
                        ops.push({ deger: lst[i].value, metin: lst[i].textContent });
                    }
                    if (k._ankanSelect.selectedIndex >= 0) {
                        secili = k._ankanSelect.options[k._ankanSelect.selectedIndex].value;
                    }
                } else {
                    try {
                        var j = JSON.parse(k.getAttribute('data-dtp-options') || '[]');
                        for (var m = 0; m < j.length; m++) { ops.push({ deger: j[m], metin: j[m] }); }
                    } catch (e) { ops = []; }
                }
                if (!ops.length) {
                    var bb = document.createElement('span');
                    bb.className = 'dtp-bos';
                    bb.textContent = 'seçenek yok';
                    menu.appendChild(bb);
                    return;
                }
                for (var t = 0; t < ops.length; t++) {
                    var o = document.createElement('button');
                    o.type = 'button';
                    o.className = 'dtp-secim';
                    o.setAttribute('data-deger', ops[t].deger);
                    o.textContent = ops[t].metin;
                    if (String(ops[t].deger) === String(secili)) { o.classList.add('on'); }
                    o.addEventListener('click', function (ev) {
                        ev.stopPropagation();
                        var deger = this.getAttribute('data-deger');
                        var metin = this.textContent;
                        var etiket = k.querySelector('.dtp-etiket');
                        if (etiket) { etiket.textContent = metin; }
                        k.setAttribute('data-dtp-value', deger);
                        if (k._ankanSelect) {
                            k._ankanSelect.value = deger;
                            k._ankanSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        ankaDrpKapat(k);
                        var git = k.getAttribute('data-dtp-git');
                        if (git) {
                            window.location.href = git + encodeURIComponent(deger);
                        }
                    });
                    menu.appendChild(o);
                }
            };
            if (buton) {
                buton.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var acik = k.classList.toggle('dtp-acik');
                    if (acik) { tazeMenü(); }
                });
            }
        })(dtpler[d]);
    }

    document.addEventListener('click', function (e) {
        var acıklar = document.querySelectorAll('.dtp-acik');
        for (var i = 0; i < acıklar.length; i++) {
            if (!acıklar[i].contains(e.target)) {
                ankaDrpKapat(acıklar[i]);
            }
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            var acıklar = document.querySelectorAll('.dtp-acik');
            for (var i = 0; i < acıklar.length; i++) { ankaDrpKapat(acıklar[i]); }
        }
    });
})();
