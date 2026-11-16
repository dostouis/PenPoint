/**
 * Vendo - The PenPoint Smart Stationery Kiosk 3D Mascot
 * Offline-ready, fully self-contained Three.js mascot module
 */
(function() {
    function initVendoMascot(stageId, sayHiBtnId) {
        const stageEl = document.getElementById(stageId);
        if (!stageEl || typeof THREE === 'undefined') return null;

        const T = THREE;
        const mat = (c, r = 0.4, m = 0) => new T.MeshStandardMaterial({ color: c, roughness: r, metalness: m });
        const glow = (c, i = 1.5) => new T.MeshStandardMaterial({ color: c, emissive: c, emissiveIntensity: i });
        const add = (p, g, m, x = 0, y = 0, z = 0) => { const o = new T.Mesh(g, m); o.position.set(x, y, z); p.add(o); return o; };
        const PI = Math.PI;

        function tex(w, h, draw) {
            const cv = document.createElement('canvas'); 
            cv.width = w; 
            cv.height = h;
            const x = cv.getContext('2d');
            const t = new T.CanvasTexture(cv); 
            let cur = draw;
            const paint = () => { x.clearRect(0, 0, w, h); cur(x, w, h); t.needsUpdate = true; };
            paint(); 
            if (document.fonts) {
                Promise.all([
                    document.fonts.load('900 40px "Noto Sans JP"', '文具販売中'),
                    document.fonts.load('40px "Dela Gothic One"', 'PenPoint'),
                    document.fonts.load('500 20px "DM Mono"', 'PEN')
                ]).then(paint).catch(() => {});
            }
            return { t, set(f) { cur = f; paint(); } };
        }

        function mkBot() {
            const g = new T.Group();
            const navy = mat(0x2a3142, 0.35, 0.5);
            const dark = mat(0x151821, 0.5, 0.3);
            const steel = mat(0x8a93aa, 0.3, 0.8);
            const red = mat(0xd63031, 0.35);

            for (const s of [-1, 1]) {
                add(g, new T.CylinderGeometry(0.42, 0.42, 0.5, 28), dark, s * 0.8, 0.42, 0).rotation.z = PI / 2;
                add(g, new T.CylinderGeometry(0.2, 0.2, 0.54, 16), steel, s * 0.8, 0.42, 0).rotation.z = PI / 2;
            }
            add(g, new T.BoxGeometry(1.9, 0.3, 1.4), navy, 0, 0.65, 0);
            add(g, new T.BoxGeometry(1.9, 1.3, 1.35), navy, 0, 1.45, 0);
            add(g, new T.CylinderGeometry(0.25, 0.3, 0.2, 16), steel, 0, 2.15, 0);
            add(g, new T.BoxGeometry(1.7, 1.0, 1.2), navy, 0, 2.65, 0);

            const drawLogo = (x, w, h) => {
                const rr = (a, b, c, d, r, f) => {
                    x.beginPath();
                    if (x.roundRect) x.roundRect(a, b, c, d, r); else x.rect(a, b, c, d);
                    x.fillStyle = f;
                    x.fill();
                };
                const S = 142, tx = (w - S) / 2, ty = 14;
                rr(tx, ty + 7, S, S, 18, '#7e0c0d');
                rr(tx, ty, S, S, 18, '#ffffff');
                const grad = x.createLinearGradient(tx, ty, tx + S, ty + S);
                grad.addColorStop(0, '#d63031');
                grad.addColorStop(1, '#b71516');
                rr(tx + 5, ty + 5, S - 10, S - 10, 14, grad);

                x.fillStyle = '#fff'; 
                x.textAlign = 'center'; 
                x.textBaseline = 'middle';
                x.font = '900 66px "Noto Sans JP", sans-serif'; 
                x.fillText('文具', w / 2, ty + 62);
                x.font = '500 22px "DM Mono", monospace'; 
                x.fillText('PEN', w / 2, ty + 112);
                x.fillStyle = '#fbf7ee'; 
                x.font = '400 42px "Dela Gothic One", sans-serif'; 
                x.fillText('PenPoint', w / 2, 188);
                rr(w / 2 - 62, 212, 124, 22, 5, '#f39c12'); 
                x.fillStyle = '#2d3436'; 
                x.font = '500 13px "DM Mono", monospace'; 
                x.fillText('SMART KIOSK', w / 2, 223.5);
            };

            const kiosk = tex(390, 240, drawLogo);
            add(g, new T.PlaneGeometry(1.5, 0.92), new T.MeshBasicMaterial({ map: kiosk.t, transparent: true }), 0, 1.55, 0.686);
            add(g, new T.BoxGeometry(0.8, 0.24, 0.12), dark, 0, 0.92, 0.68);

            const flap = new T.Group(); 
            flap.position.set(0, 1.04, 0.75); 
            g.add(flap); 
            add(flap, new T.BoxGeometry(0.72, 0.2, 0.03), red, 0, -0.1, 0);

            const pen = new T.Group(); 
            pen.visible = false; 
            g.add(pen);
            add(pen, new T.CylinderGeometry(0.05, 0.05, 0.55, 12), red).rotation.z = PI / 2; 
            add(pen, new T.CylinderGeometry(0.052, 0.052, 0.12, 12), mat(0xfbf7ee), 0.2, 0, 0).rotation.z = PI / 2;

            const face = st => (x, w, h) => {
                x.fillStyle = '#0b0f19'; 
                x.fillRect(0, 0, w, h); 
                x.fillStyle = x.strokeStyle = '#22c55e'; 
                x.shadowColor = '#22c55e'; 
                x.shadowBlur = 16; 
                x.lineWidth = 12; 
                x.lineCap = 'round';
                for (const ex of [84, 196]) {
                    if (st === 'happy') {
                        x.beginPath(); 
                        x.arc(ex, 76, 24, PI * 1.05, PI * 1.95); 
                        x.stroke();
                    } else {
                        const eh = st === 'blink' ? 6 : 44; 
                        x.fillRect(ex - 20, 62 - eh / 2, 40, eh);
                    }
                }
                x.beginPath(); 
                if (st === 'happy') x.arc(140, 98, 26, 0.2, PI - 0.2); 
                else { x.moveTo(112, 114); x.lineTo(168, 114); } 
                x.stroke();
            };

            const fx = tex(280, 150, face('idle')); 
            let cur = 'idle';
            add(g, new T.PlaneGeometry(1.4, 0.76), new T.MeshBasicMaterial({ map: fx.t }), 0, 2.66, 0.605);

            for (const s of [-1, 1]) {
                add(g, new T.CylinderGeometry(0.3, 0.3, 0.2, 24), red, s * 0.95, 2.65, 0).rotation.z = PI / 2;
                add(g, new T.CylinderGeometry(0.14, 0.14, 0.24, 16), steel, s * 0.95, 2.65, 0).rotation.z = PI / 2;
            }
            add(g, new T.CylinderGeometry(0.04, 0.04, 0.45, 8), steel, 0, 3.38, 0);
            const lampM = glow(0xd63031, 1.2); 
            add(g, new T.SphereGeometry(0.13, 16, 12), lampM, 0, 3.7, 0);

            const curve = new T.Curve(); 
            curve.getPoint = t => new T.Vector3(0.12 * Math.cos(t * 38), -t * 0.9, 0.12 * Math.sin(t * 38));
            const arms = [-1, 1].map(s => {
                const p = new T.Group(); 
                p.position.set(s * 1.07, 1.95, 0); 
                g.add(p);
                add(p, new T.TubeGeometry(curve, 120, 0.035, 6), steel); 
                add(p, new T.SphereGeometry(0.2, 16, 12), red, 0, -0.98, 0); 
                return p;
            });

            let hap = 0, pt = 0, bl = false, fl = 0;
            return {
                g,
                poke() { hap = 1; pt = 1; fl = 1; },
                blink(o) { bl = o < 1; },
                tick(t, dt, wb, lift) {
                    hap = Math.max(0, hap - dt); 
                    fl = Math.max(0, fl - dt * 2);
                    const st = hap > 0 ? 'happy' : bl ? 'blink' : 'idle'; 
                    if (st !== cur) { cur = st; fx.set(face(st)); }
                    lampM.emissiveIntensity = 1.0 + Math.sin(t * 4) * 0.5 + fl * 3;
                    flap.rotation.x = -(hap > 0 ? 1.0 : 0); 
                    pt = Math.max(0, pt - dt * 1.4); 
                    pen.visible = pt > 0;
                    pen.position.set(0, 1.0 - (1 - pt) * 0.45, 0.8); 
                    pen.rotation.z = (1 - pt) * 1.3;
                    arms[0].rotation.z = -(0.12 + lift) + Math.sin(t * 2) * 0.03; 
                    arms[1].rotation.z = (0.12 + lift) * (1 - wb) + (2.4 + Math.sin(t * 9) * 0.3) * wb;
                }
            };
        }

        const reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        let renderer;
        try {
            renderer = new T.WebGLRenderer({ antialias: true, alpha: true, preserveDrawingBuffer: true });
        } catch (e) {
            stageEl.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:0.75rem;color:#9ba6be;">Vendo Mascot Ready</div>';
            return null;
        }

        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        stageEl.appendChild(renderer.domElement);

        const scene = new T.Scene();
        const camera = new T.PerspectiveCamera(30, 1, 0.1, 100);
        scene.add(new T.HemisphereLight(0xffffff, 0x8a93aa, 0.95));
        const key = new T.DirectionalLight(0xffffff, 0.85); 
        key.position.set(2, 4, 5); 
        scene.add(key);
        const rim = new T.DirectionalLight(0x9bb4ff, 0.7); 
        rim.position.set(-4, 3, -3); 
        scene.add(rim);

        const root = new T.Group(); 
        scene.add(root);

        const sc = document.createElement('canvas'); 
        sc.width = sc.height = 128; 
        const sx = sc.getContext('2d');
        const gr = sx.createRadialGradient(64, 64, 0, 64, 64, 64);
        gr.addColorStop(0, 'rgba(0,0,0,.6)'); 
        gr.addColorStop(1, 'rgba(0,0,0,0)'); 
        sx.fillStyle = gr; 
        sx.fillRect(0, 0, 128, 128);
        const shadow = new T.Mesh(new T.PlaneGeometry(3.4, 3.4), new T.MeshBasicMaterial({ map: new T.CanvasTexture(sc), transparent: true, depthWrite: false }));
        shadow.rotation.x = -PI / 2; 
        shadow.position.y = 0.01; 
        scene.add(shadow);

        function resize() {
            const w = stageEl.clientWidth || 240;
            const h = stageEl.clientHeight || 280;
            renderer.setSize(w, h, false);
            camera.aspect = w / h;
            camera.position.set(0, 2.3, Math.max(9, 7 / camera.aspect));
            camera.lookAt(0, 1.7, 0);
            camera.updateProjectionMatrix();
        }
        if (window.ResizeObserver) {
            new ResizeObserver(resize).observe(stageEl);
        }
        resize();

        let ch = mkBot();
        root.add(ch.g);
        let ap = reduce ? 1 : 0;

        let nx = 0, ny = 0, rotY = 0, rotX = 0, jy = 0, vy = 0, land = 0, wave = 0, wb = 0, blinkIn = 2.5, blink = 0;
        window.addEventListener('pointermove', e => {
            nx = Math.max(-1, Math.min(1, (e.clientX / window.innerWidth) * 2 - 1));
            ny = Math.max(-1, Math.min(1, (e.clientY / window.innerHeight) * 2 - 1));
        }, { passive: true });

        const greet = () => { wave = 2.2; };
        const pops = ['click!', '販売中!', 'Ready!', 'VEND!'];

        renderer.domElement.addEventListener('pointerdown', () => {
            ch.poke(); 
            if (!reduce) vy = 7.5;
            if (typeof playRetroSound === 'function') playRetroSound('click');
            const p = document.createElement('div'); 
            p.className = 'pop-vendo'; 
            p.textContent = pops[Math.floor(Math.random() * pops.length)]; 
            p.style.left = (35 + Math.random() * 25) + '%'; 
            stageEl.appendChild(p); 
            setTimeout(() => p.remove(), 850);
        });

        if (!reduce) setTimeout(greet, 900);

        const clock = new T.Clock();
        renderer.setAnimationLoop(() => {
            const dt = Math.min(clock.getDelta(), 0.05);
            const t = clock.elapsedTime;
            const k = 1 - Math.exp(-dt * 6);

            rotY += ((reduce ? 0 : nx * 0.55) - rotY) * k; 
            rotX += ((reduce ? 0 : ny * 0.1) - rotX) * k; 
            ch.g.rotation.set(rotX, rotY, 0);

            if (jy > 0 || vy !== 0) { 
                vy -= 22 * dt; 
                jy += vy * dt; 
                if (jy <= 0) { if (vy < -3) land = 1; jy = 0; vy = 0; } 
            }
            land = Math.max(0, land - dt * 4); 
            ap = Math.min(1, ap + dt * 2.5);

            const st = Math.max(-6, Math.min(6, vy)) * 0.012 - land * 0.16 * Math.sin(land * PI);
            const br = reduce ? 0 : Math.sin(t * 2) * 0.012;
            const pop = 1 - Math.pow(1 - ap, 3) * Math.cos(ap * 9);

            ch.g.scale.set((1 - st * 0.6 - br) * pop, (1 + st + br) * pop, (1 - st * 0.6 - br) * pop);
            root.position.y = jy + (reduce ? 0 : Math.sin(t * 2) * 0.04);

            const sh = Math.max(0.55, 1 - jy * 0.12); 
            shadow.scale.set(sh, sh, sh); 
            shadow.material.opacity = sh;

            wave = Math.max(0, wave - dt); 
            wb += ((wave > 0 ? 1 : 0) - wb) * k;

            blinkIn -= dt; 
            if (blinkIn < 0) { blink = 0.13; blinkIn = 2.5 + Math.random() * 3; } 
            blink = Math.max(0, blink - dt); 
            ch.blink(blink > 0 ? 0.1 : 1);

            ch.tick(t, dt, wb, Math.min(jy, 1) * 0.9); 
            renderer.render(scene, camera);
        });

        if (sayHiBtnId) {
            const btn = document.getElementById(sayHiBtnId);
            if (btn) btn.onclick = greet;
        }

        window.VendoMascot = {
            poke: () => ch.poke(),
            greet: greet,
            renderer: renderer
        };

        return window.VendoMascot;
    }

    window.initVendoMascot = initVendoMascot;
})();
