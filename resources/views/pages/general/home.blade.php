@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection


@section('mainContent')
    <div class="content-wrapper">
        <div id="hero3d">
            {{-- Nền hạt bay nhẹ (parallax theo chuột) --}}
            <canvas id="heroParticles"></canvas>

            {{-- Vòng tròn đồng tâm mờ --}}
            <div class="hero-rings">
                <span></span><span></span><span></span><span></span>
            </div>

            {{-- Sân khấu 3D: nghiêng theo chuột --}}
            <div class="hero-scene">
                <div class="hero-stage" id="heroStage">

                    {{-- Logo trung tâm: khối 3D liền mảng, tự xoay + kéo xoay được --}}
                    <div class="logo-core" id="logoCore" title="PMS | Production Management System — kéo để xoay">
                        <div class="logo-glow"></div>
                        <div class="logo-spin" id="logoSpin">
                            @for ($i = 0; $i < 13; $i++)
                                <svg class="logo-layer" style="--z: {{ $i - 6 }}" viewBox="0 0 60 53"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="m116.5 2718.38-23.354 11.12v5.06a8.664 8.664 0 0 0 8.573 8.75h16.125a1.175 1.175 0 0 1 0 2.35h-16.125a8.664 8.664 0 0 0 -8.573 8.75v11.29a1.153 1.153 0 1 1 -2.305 0v-11.29a8.664 8.664 0 0 0 -8.572-8.75h-16.114a1.175 1.175 0 0 1 0-2.35h16.114a8.664 8.664 0 0 0 8.572-8.75v-5.06l-23.341-11.12a3.861 3.861 0 0 0 -5.5 3.56v29.21a5.167 5.167 0 0 0 2.52 4.45l24.963 14.7a4.944 4.944 0 0 0 5.038 0l24.963-14.7a5.167 5.167 0 0 0 2.519-4.45v-29.21a3.861 3.861 0 0 0 -5.503-3.56z"
                                        fill-rule="evenodd" transform="translate(-62 -2718)" />
                                </svg>
                            @endfor
                        </div>
                    </div>

                    {{-- 4 giá trị: khối 3D nổi tự xoay, bay quanh logo --}}
                    <div class="orbit-item" data-key="star">
                        <div class="orbit-card">
                            <div class="planet3d" style="--spin-delay: 0s">
                                @for ($i = 0; $i < 7; $i++)
                                    <svg class="planet-layer" style="--z: {{ $i - 3 }}" viewBox="0 0 100 100">
                                        <path d="M50 4 L58 42 L96 50 L58 58 L50 96 L42 58 L4 50 L42 42 Z" fill="none"
                                            stroke="currentColor" stroke-width="6" stroke-linejoin="round" />
                                    </svg>
                                @endfor
                            </div>
                            <div class="orbit-label">star<small>Hy vọng / Niềm tin</small></div>
                        </div>
                    </div>
                    <div class="orbit-item" data-key="heart">
                        <div class="orbit-card">
                            <div class="planet3d" style="--spin-delay: -3.5s">
                                @for ($i = 0; $i < 7; $i++)
                                    <svg class="planet-layer" style="--z: {{ $i - 3 }}" viewBox="0 0 100 100">
                                        <path
                                            d="M50 88 C20 66 8 48 8 32 C8 18 19 8 32 8 C40 8 47 12 50 19 C53 12 60 8 68 8 C81 8 92 18 92 32 C92 48 80 66 50 88 Z"
                                            fill="none" stroke="currentColor" stroke-width="7" stroke-linejoin="round" />
                                    </svg>
                                @endfor
                            </div>
                            <div class="orbit-label">heart<small>Tâm nghề Dược / Say mê</small></div>
                        </div>
                    </div>
                    <div class="orbit-item" data-key="shield">
                        <div class="orbit-card">
                            <div class="planet3d" style="--spin-delay: -7s">
                                @for ($i = 0; $i < 7; $i++)
                                    <svg class="planet-layer" style="--z: {{ $i - 3 }}" viewBox="0 0 100 100">
                                        <path d="M50 6 L88 20 V48 C88 70 72 86 50 95 C28 86 12 70 12 48 V20 Z"
                                            fill="none" stroke="currentColor" stroke-width="7" stroke-linejoin="round" />
                                    </svg>
                                @endfor
                            </div>
                            <div class="orbit-label">shield<small>Bảo vệ / Che chở</small></div>
                        </div>
                    </div>
                    <div class="orbit-item" data-key="medic">
                        <div class="orbit-card">
                            <div class="planet3d" style="--spin-delay: -10.5s">
                                @for ($i = 0; $i < 7; $i++)
                                    <svg class="planet-layer" style="--z: {{ $i - 3 }}" viewBox="0 0 100 100">
                                        <path d="M36 8 H64 V36 H92 V64 H64 V92 H36 V64 H8 V36 H36 Z" fill="currentColor" />
                                    </svg>
                                @endfor
                            </div>
                            <div class="orbit-label">medic<small>Y tế / Dược phẩm</small></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tiêu đề --}}
            <div class="hero-caption">
                <h1>P<span>MS</span> System</h1>
                <p class="hero-tagline">Production Management System — Hệ thống quản lý sản xuất</p>
            </div>
        </div>
    </div>

    <style>
        :root {
            --hero-gold: #cdc818;
            --hero-gold-dark: #9c980f;
            --hero-navy: #003A4F;
        }

        #hero3d {
            position: relative;
            width: 100%;
            height: calc(100vh - 57px);
            min-height: 520px;
            overflow: hidden;
            background: #ffffff;
            cursor: grab;
            user-select: none;
        }

        #hero3d.dragging {
            cursor: grabbing;
        }

        #heroParticles {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
        }

        /* ---- Vòng tròn đồng tâm nền ---- */
        .hero-rings {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }

        .hero-rings span {
            position: absolute;
            border: 1px solid rgba(0, 58, 79, 0.045);
            border-radius: 50%;
            animation: ringPulse 9s ease-in-out infinite;
        }

        .hero-rings span:nth-child(1) {
            width: 48vmin;
            height: 48vmin;
            animation-delay: 0s;
        }

        .hero-rings span:nth-child(2) {
            width: 70vmin;
            height: 70vmin;
            animation-delay: 2.2s;
        }

        .hero-rings span:nth-child(3) {
            width: 92vmin;
            height: 92vmin;
            animation-delay: 4.4s;
        }

        .hero-rings span:nth-child(4) {
            width: 114vmin;
            height: 114vmin;
            animation-delay: 6.6s;
        }

        @keyframes ringPulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 0.9;
            }

            50% {
                transform: scale(1.03);
                opacity: 0.35;
            }
        }

        /* ---- Sân khấu 3D ---- */
        .hero-scene {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            perspective: 1300px;
        }

        .hero-stage {
            position: relative;
            width: 0;
            height: 0;
            transform-style: preserve-3d;
            will-change: transform;
        }

        /* ---- Logo trung tâm: khối 3D liền mảng ---- */
        .logo-core {
            position: absolute;
            width: 340px;
            height: 300px;
            left: -170px;
            top: -150px;
            transform-style: preserve-3d;
            cursor: grab;
        }

        .logo-core:active {
            cursor: grabbing;
        }

        .logo-spin {
            position: absolute;
            inset: 0;
            transform-style: preserve-3d;
            will-change: transform;
        }

        .logo-layer {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            transform: translateZ(calc(var(--z) * 4.5px));
            fill: #b3ae13;
            backface-visibility: visible;
        }

        /* Hai mặt ngoài sáng nhất, lõi tối hơn -> cảm giác khối dày */
        .logo-layer:first-child,
        .logo-layer:last-child {
            fill: var(--hero-gold);
        }

        .logo-layer:nth-child(3),
        .logo-layer:nth-child(11) {
            fill: #c2bd15;
        }

        .logo-layer:nth-child(6),
        .logo-layer:nth-child(7),
        .logo-layer:nth-child(8) {
            fill: var(--hero-gold-dark);
        }

        .logo-layer:last-child {
            filter: drop-shadow(0 0 20px rgba(205, 200, 24, 0.4));
        }

        .logo-glow {
            position: absolute;
            inset: -34%;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(205, 200, 24, 0.22) 0%, transparent 62%);
            animation: glowBreath 4.5s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes glowBreath {

            0%,
            100% {
                opacity: 0.5;
            }

            50% {
                opacity: 1;
            }
        }

        /* ---- Hành tinh 3D ---- */
        .orbit-item {
            position: absolute;
            left: 0;
            top: 0;
            transform-style: preserve-3d;
            will-change: transform;
        }

        .orbit-card {
            position: relative;
            width: 160px;
            margin-left: -80px;
            margin-top: -92px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transform-style: preserve-3d;
        }

        .planet3d {
            position: relative;
            width: 104px;
            height: 104px;
            transform-style: preserve-3d;
            color: var(--hero-gold);
            animation: planetSpin 14s linear infinite;
            animation-delay: var(--spin-delay);
            transition: filter 0.25s ease;
            filter: drop-shadow(0 10px 20px rgba(0, 58, 79, 0.12));
        }

        @keyframes planetSpin {
            from {
                transform: rotateY(0deg);
            }

            to {
                transform: rotateY(360deg);
            }
        }

        .planet-layer {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            transform: translateZ(calc(var(--z) * 3.5px));
            color: #b3ae13;
        }

        .planet-layer:first-child,
        .planet-layer:last-child {
            color: var(--hero-gold);
        }

        .planet-layer:nth-child(3),
        .planet-layer:nth-child(4),
        .planet-layer:nth-child(5) {
            color: var(--hero-gold-dark);
        }

        .orbit-item:hover .planet3d {
            animation-play-state: paused;
            filter: drop-shadow(0 0 26px rgba(205, 200, 24, 0.7));
        }

        .orbit-label {
            text-align: center;
            font-size: 16px;
            color: #9aa0a6;
            line-height: 1.3;
            white-space: nowrap;
            transition: color 0.25s ease;
        }

        .orbit-label small {
            display: block;
            font-size: 13px;
            color: #8a9096;
        }

        .orbit-item:hover .orbit-label {
            color: var(--hero-navy);
            font-weight: 600;
        }

        /* ---- Tiêu đề ---- */
        .hero-caption {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 30px;
            text-align: center;
            pointer-events: none;
            z-index: 5;
        }

        .hero-caption h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--hero-navy);
            letter-spacing: 1px;
            margin-bottom: 2px;
        }

        .hero-caption h1 span {
            color: var(--hero-gold);
        }

        .hero-tagline {
            color: #9aa0a6;
            font-size: 0.95rem;
            margin-bottom: 0;
        }

        @media (max-width: 768px) {
            .logo-core {
                width: 220px;
                height: 194px;
                left: -110px;
                top: -97px;
            }

            .planet3d {
                width: 72px;
                height: 72px;
            }

            .orbit-label {
                font-size: 13px;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .hero-rings span,
            .logo-glow,
            .planet3d {
                animation: none !important;
            }
        }
    </style>
@endsection

@section('script')
    <script>
        (function() {
            'use strict';

            const hero = document.getElementById('hero3d');
            const stage = document.getElementById('heroStage');
            const logoCore = document.getElementById('logoCore');
            const logoSpin = document.getElementById('logoSpin');
            const items = Array.from(document.querySelectorAll('.orbit-item'));
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            /* ================= TRẠNG THÁI ================= */
            let tiltX = 0,
                tiltY = 0;
            let targetTiltX = 0,
                targetTiltY = 0;
            let orbitAngle = 0;
            let orbitSpeed = 0.22,
                targetSpeed = 0.22;
            let mouseX = 0.5,
                mouseY = 0.5;
            let dragging = false,
                lastDragX = 0;

            // Xoay logo trung tâm
            const LOGO_IDLE_SPEED = 0.35; // độ/frame khi tự xoay
            let logoAngle = 0;
            let logoVel = LOGO_IDLE_SPEED;
            let logoDragging = false,
                logoLastX = 0;

            // Quỹ đạo hành tinh
            function orbitRadius() {
                const R = Math.min(hero.clientWidth, hero.clientHeight) * 0.44;
                return Math.min(R, hero.clientWidth * 0.36);
            }

            /* ================= TƯƠNG TÁC CHUỘT ================= */
            hero.addEventListener('mousemove', function(e) {
                const r = hero.getBoundingClientRect();
                mouseX = (e.clientX - r.left) / r.width;
                mouseY = (e.clientY - r.top) / r.height;

                if (logoDragging) {
                    const dx = e.clientX - logoLastX;
                    logoAngle += dx * 0.6;
                    logoVel = dx * 0.6;
                    logoLastX = e.clientX;
                    return;
                }
                if (dragging) {
                    orbitAngle += (e.clientX - lastDragX) * 0.45;
                    lastDragX = e.clientX;
                    return;
                }
                targetTiltY = (mouseX - 0.5) * 22;
                targetTiltX = (0.5 - mouseY) * 14;
            });

            hero.addEventListener('mouseleave', function() {
                targetTiltX = 0;
                targetTiltY = 0;
            });

            // Kéo nền: quay quỹ đạo hành tinh
            hero.addEventListener('mousedown', function(e) {
                dragging = true;
                lastDragX = e.clientX;
                targetSpeed = 0;
                hero.classList.add('dragging');
            });

            // Kéo logo: xoay chính logo (không quay quỹ đạo)
            logoCore.addEventListener('mousedown', function(e) {
                e.stopPropagation();
                logoDragging = true;
                logoLastX = e.clientX;
            });

            window.addEventListener('mouseup', function() {
                if (logoDragging) {
                    logoDragging = false; // giữ lại quán tính logoVel, sẽ tự hãm dần
                }
                if (dragging) {
                    dragging = false;
                    targetSpeed = 0.22;
                    hero.classList.remove('dragging');
                }
            });

            // Vuốt cảm ứng (tablet)
            hero.addEventListener('touchstart', function(e) {
                dragging = true;
                lastDragX = e.touches[0].clientX;
                targetSpeed = 0;
            }, {
                passive: true
            });
            logoCore.addEventListener('touchstart', function(e) {
                e.stopPropagation();
                logoDragging = true;
                logoLastX = e.touches[0].clientX;
            }, {
                passive: true
            });
            hero.addEventListener('touchmove', function(e) {
                const x = e.touches[0].clientX;
                if (logoDragging) {
                    logoAngle += (x - logoLastX) * 0.6;
                    logoVel = (x - logoLastX) * 0.6;
                    logoLastX = x;
                    return;
                }
                if (dragging) {
                    orbitAngle += (x - lastDragX) * 0.45;
                    lastDragX = x;
                }
            }, {
                passive: true
            });
            hero.addEventListener('touchend', function() {
                logoDragging = false;
                dragging = false;
                targetSpeed = 0.22;
            });

            // Hover hành tinh: quỹ đạo chậm lại để xem
            items.forEach(function(item) {
                item.addEventListener('mouseenter', function() {
                    targetSpeed = 0.02;
                });
                item.addEventListener('mouseleave', function() {
                    if (!dragging) targetSpeed = 0.22;
                });
            });

            /* ================= VÒNG LẶP RENDER ================= */
            let t0 = performance.now();

            function frame(now) {
                const t = now - t0;

                // Nghiêng sân khấu theo chuột (lerp mượt)
                tiltX += (targetTiltX - tiltX) * 0.06;
                tiltY += (targetTiltY - tiltY) * 0.06;
                orbitSpeed += (targetSpeed - orbitSpeed) * 0.05;
                if (!reduceMotion) orbitAngle += orbitSpeed;

                stage.style.transform = 'rotateX(' + tiltX + 'deg) rotateY(' + tiltY + 'deg)';

                // Logo: quán tính sau khi thả tay -> hãm dần về tốc độ tự xoay
                if (!logoDragging) {
                    logoVel += (LOGO_IDLE_SPEED - logoVel) * 0.03;
                    if (!reduceMotion) logoAngle += logoVel;
                }
                // Bồng bềnh nhẹ theo thời gian
                const bob = reduceMotion ? 0 : Math.sin(t * 0.0012) * 10;
                logoSpin.style.transform = 'translateY(' + bob + 'px) rotateY(' + logoAngle + 'deg)';

                // Hành tinh quay quanh logo
                const R = orbitRadius();
                items.forEach(function(item, idx) {
                    const a = orbitAngle + idx * 90;
                    const rad = a * Math.PI / 180;
                    const x = Math.sin(rad) * R;
                    const z = Math.cos(rad) * R * 0.55;
                    const y = Math.cos(rad) * R * -0.14;
                    const depth = (z / (R * 0.55) + 1) / 2;
                    const scale = 0.6 + depth * 0.5;
                    item.style.transform =
                        'translate3d(' + x + 'px,' + y + 'px,' + z + 'px)' +
                        ' rotateY(' + (-tiltY) + 'deg) rotateX(' + (-tiltX) + 'deg)' +
                        ' scale(' + scale + ')';
                    item.style.opacity = 0.4 + depth * 0.6;
                    item.style.zIndex = Math.round(depth * 100);
                });

                requestAnimationFrame(frame);
            }

            /* ================= NỀN HẠT BAY ================= */
            const bg = document.getElementById('heroParticles');
            const bctx = bg.getContext('2d');
            let bgDots = [];

            function initBg() {
                bg.width = hero.clientWidth;
                bg.height = hero.clientHeight;
                const count = Math.min(60, Math.floor(bg.width / 26));
                bgDots = [];
                for (let i = 0; i < count; i++) {
                    bgDots.push({
                        x: Math.random() * bg.width,
                        y: Math.random() * bg.height,
                        r: 1 + Math.random() * 2.2,
                        depth: 0.3 + Math.random() * 0.7,
                        vy: -(0.06 + Math.random() * 0.24),
                        drift: (Math.random() - 0.5) * 0.16,
                        gold: Math.random() < 0.6
                    });
                }
            }

            function drawBg() {
                bctx.clearRect(0, 0, bg.width, bg.height);
                const px = (mouseX - 0.5) * 36;
                const py = (mouseY - 0.5) * 36;
                bgDots.forEach(function(p) {
                    if (!reduceMotion) {
                        p.y += p.vy * p.depth;
                        p.x += p.drift * p.depth;
                        if (p.y < -10) {
                            p.y = bg.height + 10;
                            p.x = Math.random() * bg.width;
                        }
                        if (p.x < -10) p.x = bg.width + 10;
                        if (p.x > bg.width + 10) p.x = -10;
                    }
                    bctx.beginPath();
                    bctx.arc(p.x - px * p.depth, p.y - py * p.depth, p.r * p.depth, 0, Math.PI * 2);
                    bctx.fillStyle = p.gold ?
                        'rgba(205, 200, 24, ' + (0.10 + p.depth * 0.24) + ')' :
                        'rgba(0, 58, 79, ' + (0.04 + p.depth * 0.09) + ')';
                    bctx.fill();
                });
                requestAnimationFrame(drawBg);
            }

            /* ================= KHỞI ĐỘNG ================= */
            initBg();
            requestAnimationFrame(frame);
            requestAnimationFrame(drawBg);
            window.addEventListener('resize', initBg);
        })();
    </script>
@endsection
