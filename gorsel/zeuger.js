(function () {
    "use strict";

    var konteynır = document.querySelector(".zeugerKonteynır");
    var canvas = document.getElementById("zeugerCanvas");
    if (!konteynır || !canvas) { return; }

    if (typeof THREE === "undefined") {
        konteynır.innerHTML = '<div class="bildirim bildirim-hata"><i class="fa-solid fa-triangle-exclamation"></i> 3B kütüphanesi yüklenemedi — internet bağlantısını kontrol et (Three.js CDN)</div>';
        return;
    }

    var veri = (window.__ankaZeugerVeri || []).slice();
    if (!veri.length) {
        for (var i = 0; i < 240; i++) { veri.push(Math.sin(i / 14) * 50); }
    }

    var durduruldu = false;
    var hiz = 1;
    var durBtn = document.getElementById("zeugerDur");
    var hizBtn = document.getElementById("zeugerHiz");
    if (durBtn) {
        durBtn.addEventListener("click", function () {
            durduruldu = !durduruldu;
            durBtn.innerHTML = durduruldu ? '<i class="fa-solid fa-play"></i>' : '<i class="fa-solid fa-pause"></i>';
        });
    }
    if (hizBtn) {
        hizBtn.addEventListener("click", function () {
            hiz = hiz === 1 ? 2 : hiz === 2 ? 4 : 1;
        });
    }

    var kutu = konteynır.getBoundingClientRect();
    var genis = Math.max(kutu.width, 300);
    var yuksek = Math.max(kutu.height, 260);

    var sahne = new THREE.Scene();
    sahne.background = new THREE.Color(0x02070d);

    var kamera = new THREE.PerspectiveCamera(50, genis / yuksek, 0.1, 1000);
    kamera.position.set(0, 24, 34);
    kamera.lookAt(0, 0, 0);

    var olusturucu;
    try {
        olusturucu = new THREE.WebGLRenderer({ canvas: canvas, antialias: true });
    } catch (hz) {
        konteynır.innerHTML = '<div class="bildirim bildirim-hata"><i class="fa-solid fa-triangle-exclamation"></i> WebGL başlatılamadı — tarayıcı/grafik sürücünü kontrol et</div>';
        return;
    }
    olusturucu.setSize(genis, yuksek);
    olusturucu.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));

    sahne.add(new THREE.AmbientLight(0x334455, 1.1));
    var ana = new THREE.PointLight(0x3ff2ff, 1.4, 120);
    ana.position.set(0, 26, 0);
    sahne.add(ana);
    var kizil = new THREE.PointLight(0xff2d2d, 0.7, 100);
    kizil.position.set(-30, 6, 14);
    sahne.add(kizil);
    var mavi = new THREE.PointLight(0x2da6ff, 1.0, 120);
    mavi.position.set(26, 14, -18);
    sahne.add(mavi);

    var vmin = 0, vmax = 0;
    for (var q = 0; q < veri.length; q++) {
        if (veri[q] < vmin) { vmin = veri[q]; }
        if (veri[q] > vmax) { vmax = veri[q]; }
    }
    var varalik = (vmax - vmin) || 1;
    var norm = veri.map(function (v) { return (v - vmin) / varalik; });

    var SEG_X = 48, SEG_Z = 34;
    var GCI = (SEG_X) * (SEG_Z);
    var geo = new THREE.PlaneGeometry(34, 26, SEG_X - 1, SEG_Z - 1);
    geo.rotateX(-Math.PI / 2);
    geo.translate(0, 0, 0);

    var colors = new Float32Array(geo.attributes.position.count * 3);
    geo.setAttribute("color", new THREE.BufferAttribute(colors, 3));

    var mat = new THREE.MeshStandardMaterial({
        vertexColors: true,
        flatShading: false,
        roughness: 0.25,
        metalness: 0.3,
        transparent: true,
        opacity: 0.92,
        side: THREE.DoubleSide,
        emissive: 0x02101c,
        emissiveIntensity: 0.3,
    });
    var su = new THREE.Mesh(geo, mat);
    var suPerde = new THREE.Mesh(geo, new THREE.MeshBasicMaterial({
        vertexColors: true,
        transparent: true,
        opacity: 0.18,
        side: THREE.DoubleSide,
        depthWrite: false,
    }));
    suPerde.position.y = -0.35;

    var grup = new THREE.Group();
    grup.add(su);
    grup.add(suPerde);

    var wire = new THREE.LineSegments(
        new THREE.WireframeGeometry(new THREE.PlaneGeometry(34, 26, SEG_X - 1, SEG_Z - 1)),
        new THREE.LineBasicMaterial({ color: 0x2da6ff, transparent: true, opacity: 0.18 })
    );
    wire.rotation.x = -Math.PI / 2;
    grup.add(wire);

    sahne.add(grup);

    function lerp(a, b, t) { return a + (b - a) * t; }
    function renkRenk(g, r) {
        var R, Gr, B;
        if (r < 0.5) {
            var t = r / 0.5;
            R = lerp(20, 45, t);
            Gr = lerp(90, 200, t);
            B = lerp(240, 255, t);
        } else {
            var t2 = (r - 0.5) / 0.5;
            R = lerp(45, 255, t2);
            Gr = lerp(200, 60, t2);
            B = lerp(255, 45, t2);
        }
        g.setRGB(R / 255, Gr / 255, B / 255);
    }

    var pozisyon = geo.attributes.position;
    var colAttr = geo.attributes.color;

    var renkTmp = new THREE.Color();

    function yukseklik(x, z, t) {
        var i0 = Math.floor(((x % 34 + 34) % 34) / 34 * (veri.length - 1));
        i0 = Math.max(0, Math.min(veri.length - 1, i0));
        var oran = norm[i0];
        if (typeof oran !== "number" || isNaN(oran)) { oran = 0.5; }
        var dalga = Math.sin(x * 0.55 + t * 1.6) * 0.5 + Math.sin(z * 0.45 - t * 1.1) * 0.5;
        return oran * 3.4 * (0.8 + 0.2 * Math.sin(t * 0.8)) + dalga * 1.2;
    }

    var t = 0;
    var suruk = false, bx = 0, by = 0;
    var hedefKonum = new THREE.Vector3(0, 0, 0);
    var kartSag = Math.PI / 3;
    var benimR = 40;
    var kutup = 0.9;

    function kameraYurunge() {
        if (!kamera) { return; }
        var sag = new THREE.Vector3(Math.cos(kartSag), 0, Math.sin(kartSag));
        var cup = new THREE.Vector3(
            Math.sin(kartSag) * Math.sin(kutup),
            Math.cos(kutup),
            -Math.cos(kartSag) * Math.sin(kutup)
        );
        kamera.position.copy(hedefKonum).add(sag.multiplyScalar(benimR * Math.sin(kutup))).add(cup.multiplyScalar(benimR * Math.cos(kutup)));
    }

    konteynır.addEventListener("mousedown", function (e) {
        if (e.button !== 0) { return; }
        suruk = true; bx = e.clientX; by = e.clientY;
    });
    window.addEventListener("mouseup", function () { suruk = false; });
    window.addEventListener("mousemove", function (e) {
        if (!suruk || !kamera) { return; }
        kartSag += (e.clientX - bx) * 0.008;
        kutup = Math.max(0.2, Math.min(Math.PI - 0.2, kutup + (e.clientY - by) * 0.008));
        bx = e.clientX; by = e.clientY;
        kameraYurunge();
    });
    konteynır.addEventListener("wheel", function (e) {
        e.preventDefault();
        benimR = Math.max(14, Math.min(80, benimR + (e.deltaY > 0 ? 2.5 : -2.5)));
        kameraYurunge();
    }, { passive: false });

    (function ilkOturt() {
        if (!kamera) { return; }
        var off = kamera.position.clone().sub(hedefKonum);
        benimR = off.length();
        kutup = Math.max(0.2, Math.min(Math.PI - 0.2, Math.acos(off.y / benimR)));
        kartSag = Math.atan2(off.z, off.x);
    })();

    function animasyon() {
        requestAnimationFrame(animasyon);
        if (durduruldu) { return; }
        t += 0.016 * hiz;

        for (var i = 0; i < pozisyon.count; i++) {
            var x = pozisyon.getX(i);
            var z = pozisyon.getZ(i);
            var y = yukseklik(x, z, t);
            pozisyon.setY(i, y);

            var r = (y / 5.0 + 0.5);
            r = Math.max(0, Math.min(1, r));
            renkRenk(renkTmp, r);
            colAttr.setXYZ(i, renkTmp.r, renkTmp.g, renkTmp.b);
        }
        pozisyon.needsUpdate = true;
        colAttr.needsUpdate = true;

        ana.intensity = 1.3 + Math.sin(t * 1.4) * 0.35;
        ana.position.x = Math.sin(t * 0.7) * 8;
        ana.position.z = Math.cos(t * 0.7) * 8;

        kamera.lookAt(0, 0, 0);
        olusturucu.render(sahne, kamera);
    }
    animasyon();

    window.addEventListener("resize", function () {
        var nb = konteynır.getBoundingClientRect();
        kamera.aspect = nb.width / nb.height;
        kamera.updateProjectionMatrix();
        olusturucu.setSize(nb.width, nb.height);
    });
})();
