(function () {
    "use strict";

    var konteynır = document.querySelector(".kanvasKonteynır");
    if (!konteynır) { return; }

    var grafik = window.__ankaKanvasGrafik;
    var boş = !grafik || !grafik.satirlar || !grafik.satirlar.length;

    if (typeof THREE === "undefined") {
        konteynır.innerHTML = '<div class="bildirim bildirim-hata"><i class="fa-solid fa-triangle-exclamation"></i> 3B kütüphanesi yüklenemedi — internet bağlantısını kontrol et (Three.js CDN)</div>';
        return;
    }

    if (boş) { return; }

    var renkler = [0x2da6ff, 0xff2d2d, 0x5cc8ff, 0xff5c5c, 0x3ff2ff, 0xc056ff, 0x2fd67a, 0xff8a3c];

    function sayi(deger) {
        if (typeof deger === "number") { return deger; }
        var n = parseFloat(deger);
        return isNaN(n) ? 0 : n;
    }

    var turSelect = document.getElementById("kanvasTur");
    var kolonSelect = document.getElementById("kanvasKolon");

    (grafik.sayisal || []).forEach(function (k) {
        var opt = document.createElement("option");
        opt.value = k;
        opt.textContent = k;
        kolonSelect.appendChild(opt);
    });
    if (kolonSelect.options.length) {
        kolonSelect.selectedIndex = 0;
    }

    var hud = document.createElement("div");
    hud.className = "kanvasHud";
    var kaynak = (grafik.etiket || "satır") + " → " + (kolonSelect.value || "?");
    var ornekRozet = window.__ankaKanvasOrnek ? "<span class='kanvasHudOrnek'>örnek veri</span>" : "";
    hud.innerHTML = "<span class='kanvasHudSayi'>" + grafik.satirlar.length + " puan</span>" + ornekRozet + "<span class='kanvasHudKaynak'>" + kaynak + "</span>";
    konteynır.parentNode.insertBefore(hud, konteynır);

    function guncelleHud() {
        hud.querySelector(".kanvasHudKaynak").textContent = (grafik.etiket || "satır") + " → " + kolonSelect.value;
    }

    var sahne, kamera, olusturucu, icerik, gridYardimci, zemin;
    var baralar = [], tdir = 0;
    var kutu;

    function buyukUnsuz() {
        var gy = konteynır.getBoundingClientRect();
        kutu = { genis: gy.width || 700, yuksek: gy.height || 460 };
    }

    function sahneKur() {
        if (konteynır.firstChild) {
            konteynır.innerHTML = "";
        }
        baralar = [];
        tdir = 0;

        var tur = turSelect.value;
        var kolon = kolonSelect.value;

        var satirlar = grafik.satirlar;
        var etiket = grafik.etiket || "satır";

        var n = Math.min(satirlar.length, satirlar.length > 300 ? 140 : satirlar.length > 150 ? 100 : 60);
        var ham = [];
        for (var i = 0; i < n; i++) {
            var deg = kolon ? sayi(satirlar[i][kolon]) : i;
            var ad = satirlar[i][etiket] !== undefined ? String(satirlar[i][etiket]) : String(i);
            ham.push({ ad: ad, deg: deg });
        }
        var mmax = 1;
        for (var q = 0; q < ham.length; q++) { if (Math.abs(ham[q].deg) > mmax) { mmax = Math.abs(ham[q].deg); } }

        buyukUnsuz();

        sahne = new THREE.Scene();
        sahne.background = new THREE.Color(0x05080c);

        kamera = new THREE.PerspectiveCamera(50, kutu.genis / kutu.yuksek, 0.1, 1000);
        if (tur === "dagitim") {
            kamera.position.set(18, 18, 30);
        } else {
            kamera.position.set(18, 16, 26);
        }
        kamera.lookAt(0, 3, 0);

        olusturucu = new THREE.WebGLRenderer({ antialias: true, powerPreference: "high-performance" });
        olusturucu.setSize(kutu.genis, kutu.yuksek);
        olusturucu.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
        konteynır.appendChild(olusturucu.domElement);

        sahne.add(new THREE.AmbientLight(0x223344, 1.1));
        var üst = new THREE.PointLight(0x2da6ff, 1.2, 80);
        üst.position.set(0, 34, 0);
        sahne.add(üst);
        var yan = new THREE.PointLight(0xff2d2d, 0.7, 80);
        yan.position.set(-22, 8, 24);
        sahne.add(yan);
        var sag = new THREE.PointLight(0x5cc8ff, 0.6, 80);
        sag.position.set(24, 6, -20);
        sahne.add(sag);
        sahne.userData.isiklar = { üst: üst };

        zemin = new THREE.Mesh(
            new THREE.PlaneGeometry(46, 46),
            new THREE.MeshStandardMaterial({ color: 0x0a1220, roughness: 0.6 })
        );
        zemin.rotation.x = -Math.PI / 2;
        zemin.position.y = -0.06;
        sahne.add(zemin);

        gridYardimci = new THREE.GridHelper(40, 16, 0x2da6ff, 0x14305a);
        gridYardimci.position.y = 0.01;
        sahne.add(gridYardimci);

        icerik = new THREE.Group();
        sahne.add(icerik);

        if (tur === "bar") {
            cizCubuk(ham, mmax, satirlar, etiket, kolon);
        } else if (tur === "cizgi") {
            cizDalga(ham, mmax);
        } else {
            cizDagitim(ham, mmax);
        }

        kameraKonumunuSet();
        animasyon();
    }

    function konumYogunlugu(v) {
        return Math.abs(v) / 1 * 0.4 + 0.25;
    }

    function cizCubuk(ham, mmax, satirlar, etiket, kolon) {
        var n = ham.length;
        var aralik = n <= 10 ? 3.2 : n <= 24 ? 2.3 : n <= 60 ? 1.55 : 1.05;
        if (n > 120) { aralik *= 0.8; }
        var x0 = -((n - 1) * aralik) / 2;
        var renk = kolon ? renkler[1] : renkler[0];

        if (grafik.sayisal && grafik.sayisal.length) {
            renk = 0x2da6ff;
        }

        var govGenis = n <= 10 ? 1.7 : n <= 24 ? 1.25 : n <= 60 ? 0.9 : 0.62;
        var capYari = govGenis * 0.5;

        for (var i = 0; i < n; i++) {
            var h = ham[i];
            var yuk = Math.abs(h.deg) / mmax * 7 + 0.35;
            var x = x0 + i * aralik;

            var mat = new THREE.MeshStandardMaterial({ color: renkler[i % renkler.length], emissive: renkler[i % renkler.length], emissiveIntensity: 0.55, roughness: 0.25, metalness: 0.35 });
            var gov = new THREE.Mesh(new THREE.BoxGeometry(govGenis, 1, govGenis), mat);
            gov.position.y = 0.01;
            gov.scale.y = 0.01;

            var cap = new THREE.Mesh(
                new THREE.SphereGeometry(capYari, 20, 20),
                new THREE.MeshStandardMaterial({ color: renkler[i % renkler.length], emissive: renkler[i % renkler.length], emissiveIntensity: 0.95, roughness: 0.2 })
            );
            cap.position.y = 0.01;
            cap.scale.y = 0.01;

            var grp = new THREE.Group();
            grp.position.set(x, 0, 0);
            grp.userData.satir = satirlar[i];
            grp.userData.ad = h.ad;
            grp.userData.idx = i;
            try { satirlar[i].__ankaIdx = i; } catch (hz) {}
            gov.userData.satir = satirlar[i];
            cap.userData.satir = satirlar[i];
            grp.add(gov);
            grp.add(cap);
            icerik.add(grp);

            baralar.push({
                grp: grp, gov: gov, cap: cap,
                hedefY: yuk,
                hedefC: Math.max(0.6, Math.min(2.2, yuk * 0.35)),
                hiz: 0.05 + (i % 4) * 0.012,
                x: x, ad: h.ad, orj: h.deg
            });

            var ad = h.ad.length > 14 ? h.ad.slice(0, 13) + "…" : h.ad;
            var tuv = document.createElement("canvas");
            var fontBuyuk = n <= 24 ? 34 : 26;
            tuv.width = 256; tuv.height = 96;
            var ctx2 = tuv.getContext("2d");
            ctx2.fillStyle = "rgba(5,10,18,0.7)";
            ctx2.fillRect(4, 4, 248, 88);
            ctx2.strokeStyle = "#2da6ff";
            ctx2.lineWidth = 3;
            ctx2.strokeRect(4, 4, 248, 88);
            ctx2.fillStyle = "#cfe8ff";
            ctx2.font = "bold " + fontBuyuk + "px Segoe UI";
            ctx2.textAlign = "center";
            ctx2.textBaseline = "middle";
            ctx2.fillText(ad, 128, 34);
            ctx2.fillStyle = "#6fdcff";
            ctx2.font = (fontBuyuk - 8) + "px Segoe UI";
            ctx2.fillText("= " + (Math.round(h.deg * 100) / 100), 128, 70);

            var spr = new THREE.Sprite(new THREE.SpriteMaterial({ map: new THREE.CanvasTexture(tuv), transparent: true, depthWrite: false }));
            var sprOlcek = n <= 24 ? 3.6 : n <= 60 ? 3.2 : Math.max(2.2, 3.2 - (n - 60) * 0.02);
            spr.scale.set(sprOlcek, sprOlcek * 0.38, 1);
            spr.position.set(x, -1.9, 0);
            icerik.add(spr);
        }
    }

    function cizDalga(ham, mmax) {
        var n = ham.length;
        var x0 = -14, x1 = 14;
        var pts = [];
        for (var i = 0; i < n; i++) {
            var t = n === 1 ? 0 : i / (n - 1);
            var x = x0 + t * (x1 - x0);
            var y = Math.abs(ham[i].deg) / mmax * 7 + 0.35;
            pts.push(new THREE.Vector3(x, y, 0));
        }
        var egr = new THREE.CatmullRomCurve3(pts);
        var geo = new THREE.BufferGeometry().setFromPoints(egr.getPoints(n * 4));
        var cizgi = new THREE.Line(geo, new THREE.LineBasicMaterial({ color: 0x3ff2ff, linewidth: 2 }));
        icerik.add(cizgi);

        var pts2 = [];
        for (var j = 0; j < n; j++) {
            var t2 = n === 1 ? 0 : j / (n - 1);
            pts2.push(new THREE.Vector3(x0 + t2 * (x1 - x0), Math.abs(ham[j].deg) / mmax * 6 + 0.3, -1.4));
        }
        var egr2 = new THREE.CatmullRomCurve3(pts2);
        var geo2 = new THREE.BufferGeometry().setFromPoints(egr2.getPoints(n * 4));
        icerik.add(new THREE.Line(geo2, new THREE.LineBasicMaterial({ color: 0xff2d2d, transparent: true, opacity: 0.5 })));

        for (var k = 0; k < n; k++) {
            var tt = n === 1 ? 0 : k / (n - 1);
            var sx = x0 + tt * (x1 - x0);
            var sy = Math.abs(ham[k].deg) / mmax * 7 + 0.35;
            var rk = renkler[k % renkler.length];
            var nokta = new THREE.Mesh(
                new THREE.SphereGeometry(0.3, 16, 16),
                new THREE.MeshStandardMaterial({ color: rk, emissive: rk, emissiveIntensity: 0.9 })
            );
            nokta.position.set(sx, sy, 0);
            nokta.userData.satir = grafik.satirlar[k];
            nokta.userData.ad = ham[k].ad;
            try { grafik.satirlar[k].__ankaIdx = k; } catch (hz) {}
            icerik.add(nokta);
            baralar.push({ nokta: nokta, x: sx });
        }
    }

    function cizDagitim(ham, mmax) {
        var n = ham.length;
        var genislik = 20, derinlik = 20;
        for (var i = 0; i < n; i++) {
            var t = n === 1 ? 0.5 : i / (n - 1);
            var x = -genislik / 2 + t * genislik;
            var y = Math.abs(ham[i].deg) / mmax * 7 + 0.35;
            var z = (i % 8) * 2.4 - 8; // katmanlı kuşak
            var r = 0.22 + (ham[i].deg / mmax) * 0.28;
            var rk = renkler[i % renkler.length];
            var nokta = new THREE.Mesh(
                new THREE.SphereGeometry(r, 18, 18),
                new THREE.MeshStandardMaterial({ color: rk, emissive: rk, emissiveIntensity: 1.0, roughness: 0.2 })
            );
            nokta.position.set(x, y, z);
            nokta.userData.satir = grafik.satirlar[i];
            nokta.userData.ad = ham[i].ad;
            try { grafik.satirlar[i].__ankaIdx = i; } catch (hz) {}
            icerik.add(nokta);
            var cizgiGeo = new THREE.BufferGeometry().setFromPoints([new THREE.Vector3(x, 0, z), new THREE.Vector3(x, y, z)]);
            icerik.add(new THREE.Line(cizgiGeo, new THREE.LineBasicMaterial({ color: 0x2da6ff, transparent: true, opacity: 0.4 })));
        }
    }

    function nCizgi(n) { return n; }

    var t = 0;
    function animasyon() {
        requestAnimationFrame(animasyon);
        if (!sahne) { return; }
        t += 0.02;
        tdir += 0.02;

        if (icerik) {
            icerik.rotation.y += 0.003;
        }
        if (sahne.userData.isiklar && sahne.userData.isiklar.üst) {
            sahne.userData.isiklar.üst.intensity = 1.1 + Math.sin(t * 2) * 0.25;
        }

        baralar.forEach(function (b) {
            if (b.gov) {
                if (b.gov.position.y < b.hedefY) {
                    var yeni = Math.min(b.hedefY, b.gov.position.y + b.hiz);
                    b.gov.position.y = yeni / 2 + 0.001;
                    b.gov.scale.y = yeni || 0.001;
                    b.cap.position.y = yeni / 2 + 0.001 + 0.42;
                    b.cap.scale.y = Math.min(1, yeni / b.hedefY) * b.hedefC || 0.001;
                }
                b.cap.rotation.y += 0.03;
            } else if (b.nokta) {
                b.nokta.material.emissiveIntensity = 0.7 + Math.sin(t * 3 + b.x) * 0.3;
            }
        });

        if (kamera) { kamera.lookAt(0, 3, 0); }
        if (olusturucu) { olusturucu.render(sahne, kamera); }
    }

    var hedefKonum = new THREE.Vector3(0, 3, 0);
    var suruk = false, bx = 0, by = 0;
    var kartSag = 1;             // görünüm yönü (sürüklemeyle döner)
    var benimR = 26;             // kamera uzaklığı (zoom)
    var kutup = 1.0;             // dikey açı

    function kameraKonumunuSet() {
        var sag = new THREE.Vector3(Math.cos(kartSag), 0, Math.sin(kartSag));
        var cup = new THREE.Vector3(
            Math.sin(kartSag) * Math.sin(kutup),
            Math.cos(kutup),
            -Math.cos(kartSag) * Math.sin(kutup)
        );
        if (kamera) {
            kamera.position.copy(hedefKonum).add(sag.multiplyScalar(benimR * Math.sin(kutup))).add(cup.multiplyScalar(benimR * Math.cos(kutup)));
        }
    }

    konteynır.addEventListener("mousedown", function (e) {
        if (e.button !== 0) { return; }
        suruk = true; bx = e.clientX; by = e.clientY;
    });
    window.addEventListener("mouseup", function () { suruk = false; });
    window.addEventListener("mousemove", function (e) {
        if (!suruk || !kamera) { return; }
        kartSag += (e.clientX - bx) * 0.008;
        kutup = Math.max(0.15, Math.min(Math.PI - 0.15, kutup + (e.clientY - by) * 0.008));
        bx = e.clientX; by = e.clientY;
        kameraKonumunuSet();
    });
    konteynır.addEventListener("wheel", function (e) {
        e.preventDefault();
        benimR = Math.max(8, Math.min(60, benimR + (e.deltaY > 0 ? 2 : -2)));
        kameraKonumunuSet();
    }, { passive: false });

    (function ilkKamerayiOturt() {
        if (!kamera) { return; }
        var pos = kamera.position.clone();
        var off = pos.sub(hedefKonum);
        benimR = Math.max(8, off.length());
        kutup = Math.max(0.15, Math.min(Math.PI - 0.15, Math.acos(off.y / benimR)));
        kartSag = Math.atan2(off.z, off.x);
    })();

    window.addEventListener("resize", function () {
        buyukUnsuz();
        if (kamera && olusturucu) {
            kamera.aspect = kutu.genis / kutu.yuksek;
            kamera.updateProjectionMatrix();
            olusturucu.setSize(kutu.genis, kutu.yuksek);
        }
    });

    var raycaster = new THREE.Raycaster();
    var fare = new THREE.Vector2();
    var downX = 0, downY = 0, kilidi = false;

    konteynır.addEventListener("mousedown", function (e) { downX = e.clientX; downY = e.clientY; });
    konteynır.addEventListener("mouseup", function (e) {
        var mes = Math.hypot(e.clientX - downX, e.clientY - downY);
        if (mes > 10) { return; }
        if (!olusturucu || !kamera) { return; }
        var r = konteynır.getBoundingClientRect();
        fare.x = ((e.clientX - r.left) / r.width) * 2 - 1;
        fare.y = -((e.clientY - r.top) / r.height) * 2 + 1;
        raycaster.setFromCamera(fare, kamera);
        var hedefler = [];
        if (icerik) {
            icerik.traverse(function (ob) {
                if (ob.isMesh) { hedefler.push(ob); }
            });
        }
        var isabet = raycaster.intersectObjects(hedefler, false);
        if (isabet.length) {
            var sb = isabet[0].object;
            var satir = (sb.userData && sb.userData.satir) || null;
            if (satir) { gosterDetay(sb.userData.ad, satir); }
        }
    });

    var detayDom = document.createElement("div");
    detayDom.className = "kanvasDetay gizli";
    konteynır.parentNode.appendChild(detayDom);

    function galifdefnull(v) {
        if (v === null || v === undefined) { return "—"; }
        if (typeof v === "object") { return JSON.stringify(v); }
        return String(v);
    }

    function gosterDetay(ad, satir) {
        var satirlar = [];
        var keys = Object.keys(satir);
        for (var i = 0; i < keys.length; i++) {
            satirlar.push(keys[i] + ":\u00a0" + galifdefnull(satir[keys[i]]));
        }
        detayDom.innerHTML = "";
        var isim = document.createElement("div");
        isim.className = "kanvasDetayAd";
        isim.innerHTML = "seçili: <b>" + (ad || "satır") + "</b> · <small>tıkladığın veri noktası</small>";
        detayDom.appendChild(isim);
        var tek = document.createElement("div");
        tek.className = "kanvasDetayKip";
        tek.textContent = satirlar.join("  ·  ");
        detayDom.appendChild(tek);
        detayDom.classList.remove("gizli");
        var td = document.querySelector(".kanvasTablo .veriTablo tbody");
        if (td) {
            var satirIndeks = satir.__ankaIdx;
            if (satirIndeks !== undefined) {
                var k = td.rows;
                for (var r2 = 0; r2 < k.length; r2++) {
                    k[r2].style.background = "";
                }
                if (k[satirIndeks]) {
                    k[satirIndeks].style.background = "rgba(45,166,255,0.18)";
                    k[satirIndeks].scrollIntoView({ block: "nearest", behavior: "smooth" });
                }
            }
        }
    }

    turSelect.addEventListener("change", function () {
        kolonSelect.style.display = turSelect.value === "bar" && grafik.sayisal.length > 1 ? "" : "";
        guncelleHud();
        sahneKur();
    });
    kolonSelect.addEventListener("change", function () {
        guncelleHud();
        sahneKur();
    });

    sahneKur();
})();
