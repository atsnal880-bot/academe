document.addEventListener('DOMContentLoaded', () => {
    // Spotlight Hover pada Kartu
    const cards = document.querySelectorAll('.card-custom');
    cards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            card.style.setProperty('--mouse-x', `${e.clientX - rect.left}px`);
            card.style.setProperty('--mouse-y', `${e.clientY - rect.top}px`);
        });
    });

    // Navbar Scrolled State
    const navbar = document.querySelector('.navbar-custom');
    if (navbar) {
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 40);
        });
    }
});

// =========================================================
// 1. SPECULAR BUTTON WEBGL2 ENGINE
// =========================================================
(function initSpecularButtons() {
    const PAD = 24;
    const TAU = Math.PI * 2;

    const VERTEX = `#version 300 es
    in vec2 aPosition;
    void main() {
      gl_Position = vec4(aPosition, 0.0, 1.0);
    }`;

    const FRAGMENT = `#version 300 es
    precision highp float;
    uniform vec2 uCenter;
    uniform vec2 uHalf;
    uniform float uRadius;
    uniform float uPx;
    uniform float uSpot;
    uniform vec2 uEntry;
    uniform vec3 uLineColor;
    uniform vec3 uBaseColor;
    uniform float uIntensity;
    uniform vec2 uWindow;
    uniform float uThickness;
    uniform float uGlow;
    out vec4 outColor;

    float roundedBox(vec2 p, vec2 b, float r) {
      vec2 q = abs(p) - b + r;
      return length(max(q, 0.0)) + min(max(q.x, q.y), 0.0) - r;
    }

    float loopPosition(vec2 p, vec2 b, float r) {
      vec2 inner = b - r;
      float arc = 1.5707963 * r;
      float quarter = inner.x + inner.y + arc;
      vec2 a = abs(p);
      vec2 q = a - inner;
      float s = q.x > 0.0 && q.y > 0.0
        ? inner.y + atan(q.y, q.x) * r
        : (q.x > q.y ? min(a.y, inner.y) : inner.y + arc + inner.x - min(a.x, inner.x));
      if (p.x < 0.0) s = p.y >= 0.0 ? 2.0 * quarter - s : 2.0 * quarter + s;
      else if (p.y < 0.0) s = 4.0 * quarter - s;
      return s / max(4.0 * quarter, 0.0001);
    }

    float lobe(float position, float center) {
      float gap = abs(fract(position - center + 0.5) - 0.5);
      return 1.0 - smoothstep(uWindow.x, uWindow.x + uWindow.y, gap);
    }

    void main() {
      vec2 p = gl_FragCoord.xy - uCenter;
      float d = roundedBox(p, uHalf, uRadius);
      float position = loopPosition(p, uHalf, uRadius);
      float key = lobe(position, uSpot);
      float back = lobe(position, uSpot + 0.5);
      float streak = key + back * 0.45;
      float width = max(uThickness * uPx * mix(1.0, max(key, back), 0.6), 0.35);
      float band = smoothstep(-width - 0.6, -width + 0.6, d) * (1.0 - smoothstep(-0.6, 0.6, d));
      float hair = smoothstep(-uPx - 0.6, -uPx + 0.6, d) * (1.0 - smoothstep(-0.6, 0.6, d));
      float inside = 1.0 - smoothstep(-0.6, 0.6, d);
      vec2 pool = (p - uEntry) / (uHalf.y * vec2(1.6, 1.1));
      float glow = streak * 0.2 * exp(-abs(d + width * 0.5) / (5.0 * uPx)) + inside * 0.08 * exp(-dot(pool, pool));
      float light = clamp((band * streak + glow * uGlow) * uIntensity, 0.0, 1.0);
      vec4 base = vec4(uBaseColor, 1.0) * hair * 0.6;
      outColor = vec4(uLineColor, 1.0) * light + base * (1.0 - light);
    }`;

    const UNIFORMS = [
        'uCenter', 'uHalf', 'uRadius', 'uPx', 'uSpot', 'uEntry',
        'uLineColor', 'uBaseColor', 'uIntensity', 'uWindow', 'uThickness', 'uGlow'
    ];

    const clamp = (val, min, max) => Math.min(max, Math.max(min, val));
    const wrap = val => val - Math.floor(val);

    const loopPosition = (x, y, halfX, halfY, r) => {
        const innerX = halfX - r, innerY = halfY - r;
        const arc = (Math.PI / 2) * r;
        const quarter = innerX + innerY + arc;
        const ax = Math.abs(x), ay = Math.abs(y);
        const qx = ax - innerX, qy = ay - innerY;
        let s;
        if (qx > 0 && qy > 0) s = innerY + Math.atan2(qy, qx) * r;
        else if (qx > qy) s = Math.min(ay, innerY);
        else s = innerY + arc + innerX - Math.min(ax, innerX);
        if (x < 0) s = y >= 0 ? 2 * quarter - s : 2 * quarter + s;
        else if (y < 0) s = 4 * quarter - s;
        return wrap(s / Math.max(4 * quarter, 0.0001));
    };

    const rimPoint = (position, halfX, halfY, r) => {
        const innerX = halfX - r, innerY = halfY - r;
        const arc = (Math.PI / 2) * r;
        const quarter = innerX + innerY + arc;
        let s = wrap(position) * 4 * quarter;
        let signX = 1, signY = 1;
        if (s > 3 * quarter) { s = 4 * quarter - s; signY = -1; }
        else if (s > 2 * quarter) { s -= 2 * quarter; signX = -1; signY = -1; }
        else if (s > quarter) { s = 2 * quarter - s; signX = -1; }
        let x = innerX - (s - innerY - arc), y = halfY;
        if (s <= innerY) { x = halfX; y = s; }
        else if (s <= innerY + arc) {
            const angle = (s - innerY) / Math.max(r, 0.0001);
            x = innerX + Math.cos(angle) * r;
            y = innerY + Math.sin(angle) * r;
        }
        return [x * signX, y * signY];
    };

    const compile = (gl, type, source) => {
        const shader = gl.createShader(type);
        if (!shader) return null;
        gl.shaderSource(shader, source);
        gl.compileShader(shader);
        if (gl.getShaderParameter(shader, gl.COMPILE_STATUS)) return shader;
        gl.deleteShader(shader);
        return null;
    };

    const link = gl => {
        const vertex = compile(gl, gl.VERTEX_SHADER, VERTEX);
        const fragment = compile(gl, gl.FRAGMENT_SHADER, FRAGMENT);
        const program = gl.createProgram();
        if (!vertex || !fragment || !program) return null;
        gl.attachShader(program, vertex);
        gl.attachShader(program, fragment);
        gl.bindAttribLocation(program, 0, 'aPosition');
        gl.linkProgram(program);
        gl.deleteShader(vertex);
        gl.deleteShader(fragment);
        if (gl.getProgramParameter(program, gl.LINK_STATUS)) return program;
        gl.deleteProgram(program);
        return null;
    };

    const mountSpecularButton = (button) => {
        if (button.dataset.sbMounted) return;
        button.dataset.sbMounted = "true";

        const size = button.dataset.size || 'sm';
        const radius = parseFloat(button.dataset.radius ?? 18);
        const tint = button.dataset.tint || '#000080';
        const tintOpacity = parseFloat(button.dataset.tintOpacity ?? 0.25);
        const blur = parseFloat(button.dataset.blur ?? 8);
        const textColor = button.dataset.textColor || '#f0f4ff';

        const s = {
            radius: Math.max(0, radius),
            lineColor: button.dataset.lineColor || '#8ab4f8',
            baseColor: button.dataset.baseColor || '#1e3a8a',
            intensity: Math.max(0, parseFloat(button.dataset.intensity ?? 1.2)),
            idleIntensity: clamp(parseFloat(button.dataset.idleIntensity ?? 0.45), 0, 1),
            shineSize: Math.max(0, parseFloat(button.dataset.shineSize ?? 12)),
            shineFade: Math.max(0, parseFloat(button.dataset.shineFade ?? 36)),
            thickness: Math.max(0, parseFloat(button.dataset.thickness ?? 1.3)),
            glow: Math.max(0, parseFloat(button.dataset.glow ?? 1.1)),
            speed: parseFloat(button.dataset.speed ?? 0.35),
            followMouse: button.dataset.followMouse !== 'false',
            proximity: Math.max(1, parseFloat(button.dataset.proximity ?? 260)),
            autoAnimate: button.dataset.autoAnimate === 'true',
            disabled: button.hasAttribute('disabled')
        };

        button.classList.add('specular-button', `specular-button--${size}`);
        button.style.setProperty('--sb-radius', `${s.radius}px`);
        button.style.setProperty('--sb-tint', tint);
        button.style.setProperty('--sb-tint-opacity', tintOpacity);
        button.style.setProperty('--sb-blur', `${blur}px`);
        button.style.setProperty('--sb-text-color', textColor);

        const labelText = button.innerHTML;
        button.innerHTML = '';
        const canvas = document.createElement('canvas');
        canvas.className = 'specular-button__light';
        canvas.setAttribute('aria-hidden', 'true');
        const label = document.createElement('span');
        label.className = 'specular-button__label';
        label.innerHTML = labelText;
        button.appendChild(canvas);
        button.appendChild(label);

        const gl = canvas.getContext('webgl2', {
            alpha: true, premultipliedAlpha: true, antialias: false, depth: false, stencil: false
        });
        if (!gl) return;
        const program = link(gl);
        if (!program) return;

        const uniforms = {};
        for (const name of UNIFORMS) uniforms[name] = gl.getUniformLocation(program, name);
        const buffer = gl.createBuffer();
        const vao = gl.createVertexArray();
        gl.bindVertexArray(vao);
        gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
        gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 3, -1, -1, 3]), gl.STATIC_DRAW);
        gl.enableVertexAttribArray(0);
        gl.vertexAttribPointer(0, 2, gl.FLOAT, false, 0, 0);
        gl.bindVertexArray(null);

        const probe = document.createElement('canvas').getContext('2d', { willReadFrequently: true });
        const colors = new Map();
        const view = { width: 1, height: 1, ratio: 1 };
        const pointer = { x: 0, y: 0, known: false };
        const state = { spot: 0, level: 0, press: 0, ready: false };
        let raf = 0, last = 0, visible = true;

        const toRgb = value => {
            if (colors.has(value)) return colors.get(value);
            let rgb = [1, 1, 1];
            if (probe) {
                probe.clearRect(0, 0, 1, 1);
                probe.fillStyle = value;
                probe.fillRect(0, 0, 1, 1);
                const [r, g, b] = probe.getImageData(0, 0, 1, 1).data;
                rgb = [r / 255, g / 255, b / 255];
            }
            colors.set(value, rgb);
            return rgb;
        };

        const cornerRadius = () => Math.min(s.radius, view.width / 2, view.height / 2);
        const restSpot = () => {
            const r = cornerRadius();
            const inset = r * (1 - Math.SQRT1_2);
            return loopPosition(inset - view.width / 2, view.height / 2 - inset, view.width / 2, view.height / 2, r);
        };

        const aim = () => {
            if (!s.followMouse || s.disabled || !pointer.known) return null;
            const rect = button.getBoundingClientRect();
            const scale = rect.width / view.width || 1;
            const halfX = view.width / 2, halfY = view.height / 2;
            const x = (pointer.x - rect.left - rect.width / 2) / scale;
            const y = (rect.top + rect.height / 2 - pointer.y) / scale;
            const distance = Math.hypot(Math.max(Math.abs(x) - halfX, 0), Math.max(Math.abs(y) - halfY, 0));
            const t = clamp(1 - distance / s.proximity, 0, 1);
            const near = t * t * (3 - 2 * t);
            const nearest = loopPosition(x, y, halfX, halfY, cornerRadius());
            return { spot: nearest, near };
        };

        const draw = () => {
            const { width, height } = view;
            const scaleX = canvas.width / (width + PAD * 2);
            const scaleY = canvas.height / (height + PAD * 2);
            gl.viewport(0, 0, canvas.width, canvas.height);
            gl.clearColor(0, 0, 0, 0);
            gl.clear(gl.COLOR_BUFFER_BIT);
            gl.useProgram(program);
            gl.uniform2f(uniforms.uCenter, (PAD + width / 2) * scaleX, (PAD + height / 2) * scaleY);
            gl.uniform2f(uniforms.uHalf, (width / 2) * scaleX, (height / 2) * scaleY);
            gl.uniform1f(uniforms.uRadius, Math.min(s.radius, width / 2, height / 2) * scaleX);
            gl.uniform1f(uniforms.uPx, view.ratio);
            const r = Math.min(s.radius, width / 2, height / 2);
            const [rimX, rimY] = rimPoint(state.spot, width / 2, height / 2, r);
            const pull = 1 - Math.min(0.35 * (height / 2), Math.hypot(rimX, rimY)) / Math.max(Math.hypot(rimX, rimY), 0.0001);
            gl.uniform1f(uniforms.uSpot, state.spot);
            gl.uniform2f(uniforms.uEntry, rimX * pull * scaleX, rimY * pull * scaleY);
            gl.uniform3fv(uniforms.uLineColor, toRgb(s.lineColor));
            gl.uniform3fv(uniforms.uBaseColor, toRgb(s.baseColor));
            gl.uniform1f(uniforms.uIntensity, s.intensity * (state.level + state.press * 0.8));
            gl.uniform2f(uniforms.uWindow, s.shineSize / 360 + state.press * 0.06, s.shineFade / 360 + 0.0001);
            gl.uniform1f(uniforms.uThickness, s.thickness);
            gl.uniform1f(uniforms.uGlow, s.glow);
            gl.bindVertexArray(vao);
            gl.drawArrays(gl.TRIANGLES, 0, 3);
            gl.bindVertexArray(null);
        };

        const frame = now => {
            raf = 0;
            if (!visible) return;
            const dt = last ? Math.min(0.05, (now - last) / 1000) : 1 / 60;
            last = now;
            const target = aim();
            const steering = !!target && target.near > 0;
            if (!state.ready) {
                state.spot = restSpot();
                state.level = s.idleIntensity;
                state.ready = true;
            }
            let diff = (steering ? target.spot : restSpot()) - state.spot;
            diff -= Math.round(diff);
            state.spot = wrap(state.spot + diff * (1 - Math.exp(-dt * 8)));
            const level = s.idleIntensity + (1 - s.idleIntensity) * (target ? target.near : 0);
            state.level += (level - state.level) * (1 - Math.exp(-dt * 7));
            state.press *= Math.exp(-dt * 5);
            draw();
            if (Math.abs(diff) > 0.0002 || Math.abs(level - state.level) > 0.001 || state.press > 0.002) {
                raf = requestAnimationFrame(frame);
            } else {
                last = 0;
            }
        };

        const wake = () => { if (!raf && visible) raf = requestAnimationFrame(frame); };

        const measure = () => {
            view.width = Math.max(1, button.offsetWidth);
            view.height = Math.max(1, button.offsetHeight);
            view.ratio = Math.min(window.devicePixelRatio || 1, 2.5);
            canvas.width = Math.max(1, Math.round((view.width + PAD * 2) * view.ratio));
            canvas.height = Math.max(1, Math.round((view.height + PAD * 2) * view.ratio));
            if (state.ready) draw();
            wake();
        };

        new ResizeObserver(measure).observe(button);
        window.addEventListener('pointermove', e => { pointer.x = e.clientX; pointer.y = e.clientY; pointer.known = true; wake(); }, { passive: true });
        button.addEventListener('pointerdown', e => { pointer.x = e.clientX; pointer.y = e.clientY; pointer.known = true; state.press = 1; wake(); });
        measure();
    };

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-specular-button]').forEach(mountSpecularButton);
    });
})();

// =========================================================
// 2. DARK VEIL WEBGL ENGINE (NAVY #000080 SMOKE EDITION)
// =========================================================
(function initDarkVeil() {
    const VERTEX_SHADER = `
        attribute vec2 a_position;
        void main() {
            gl_Position = vec4(a_position, 0.0, 1.0);
        }
    `;

    const FRAGMENT_SHADER = `
        precision highp float;
        uniform float uTime;
        uniform vec2 uResolution;
        uniform vec3 uColor;
        uniform float uWarp;

        float hash(vec2 p) {
            return fract(sin(dot(p, vec2(127.1, 311.7))) * 43758.5453123);
        }

        float noise(vec2 p) {
            vec2 i = floor(p);
            vec2 f = fract(p);
            vec2 u = f * f * (3.0 - 2.0 * f);
            return mix(
                mix(hash(i + vec2(0.0, 0.0)), hash(i + vec2(1.0, 0.0)), u.x),
                mix(hash(i + vec2(0.0, 1.0)), hash(i + vec2(1.0, 1.0)), u.x),
                u.y
            );
        }

        float fbm(vec2 p) {
            float v = 0.0;
            float a = 0.52;
            mat2 rot = mat2(cos(0.5), sin(0.5), -sin(0.5), cos(0.5));
            for (int i = 0; i < 6; i++) {
                v += a * noise(p);
                p = rot * p * 2.03 + vec2(50.0);
                a *= 0.5;
            }
            return v;
        }

        void main() {
            vec2 uv = (gl_FragCoord.xy - 0.5 * uResolution.xy) / min(uResolution.y, uResolution.x);
            float t = uTime * 0.45;

            vec2 q = vec2(fbm(uv * 1.4 + t * 0.25), fbm(uv * 1.4 + vec2(2.3, 1.7)));
            vec2 r = vec2(
                fbm(uv * 1.8 + (1.2 + uWarp) * q + vec2(1.7, 9.2) + 0.2 * t),
                fbm(uv * 1.8 + (1.2 + uWarp) * q + vec2(8.3, 2.8) + 0.16 * t)
            );

            float f = fbm(uv * 1.5 + r * 1.8);

            float ribbon1 = sin(uv.x * 3.2 + uv.y * 1.5 + f * 5.5 - t) * 0.5 + 0.5;
            float ribbon2 = cos(uv.x * 2.0 - uv.y * 2.2 + r.x * 4.5 + t * 0.7) * 0.5 + 0.5;
            float veil = pow(ribbon1, 2.2) * 0.75 + pow(ribbon2, 2.5) * 0.55;

            // Palet Asap Navy (#000080): Deep Midnight -> Pure Navy -> Electric Royal Glow
            vec3 darkBg = vec3(0.008, 0.015, 0.07); // #020412
            vec3 midVeil = uColor * 1.55 + vec3(0.0, 0.04, 0.15); // Navy #000080 kaya
            vec3 brightCrest = uColor * 2.8 + vec3(0.12, 0.28, 0.55); // Puncak asap biru navy bercahaya

            vec3 col = mix(darkBg, midVeil, smoothstep(0.12, 0.85, f));
            col = mix(col, brightCrest, veil * smoothstep(1.35, 0.05, length(uv * vec2(0.7, 1.0))));

            // Vignette Navy
            col *= smoothstep(1.65, 0.2, length(uv * vec2(0.85, 1.0)));

            gl_FragColor = vec4(col, 1.0);
        }
    `;

    const hexToRgb = (hex) => {
        const clean = hex.replace('#', '');
        const num = parseInt(clean.length === 3 ? clean.replace(/(.)/g, '$1$1') : clean, 16);
        return [((num >> 16) & 255) / 255, ((num >> 8) & 255) / 255, (num & 255) / 255];
    };

    const createShader = (gl, type, source) => {
        const s = gl.createShader(type);
        gl.shaderSource(s, source);
        gl.compileShader(s);
        return gl.getShaderParameter(s, gl.COMPILE_STATUS) ? s : null;
    };

    const mountDarkVeil = (container) => {
        if (container.dataset.dvMounted) return;
        container.dataset.dvMounted = "true";

        const rgb = hexToRgb(container.dataset.color || '#000080');
        const speed = parseFloat(container.dataset.speed ?? 0.65);
        const warp = parseFloat(container.dataset.warp ?? 0.45);

        const canvas = document.createElement('canvas');
        canvas.className = 'dark-veil-canvas';
        container.appendChild(canvas);

        const gl = canvas.getContext('webgl', { alpha: false, antialias: false });
        if (!gl) return;

        const vs = createShader(gl, gl.VERTEX_SHADER, VERTEX_SHADER);
        const fs = createShader(gl, gl.FRAGMENT_SHADER, FRAGMENT_SHADER);
        const prog = gl.createProgram();
        gl.attachShader(prog, vs);
        gl.attachShader(prog, fs);
        gl.linkProgram(prog);
        gl.useProgram(prog);

        const buf = gl.createBuffer();
        gl.bindBuffer(gl.ARRAY_BUFFER, buf);
        gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1,-1, 1,-1, -1,1, -1,1, 1,-1, 1,1]), gl.STATIC_DRAW);

        const pos = gl.getAttribLocation(prog, 'a_position');
        gl.enableVertexAttribArray(pos);
        gl.vertexAttribPointer(pos, 2, gl.FLOAT, false, 0, 0);

        const uTime = gl.getUniformLocation(prog, 'uTime');
        const uRes = gl.getUniformLocation(prog, 'uResolution');
        const uColor = gl.getUniformLocation(prog, 'uColor');
        const uWarp = gl.getUniformLocation(prog, 'uWarp');

        const resize = () => {
            const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
            canvas.width = Math.max(1, Math.floor(container.clientWidth * dpr));
            canvas.height = Math.max(1, Math.floor(container.clientHeight * dpr));
            gl.viewport(0, 0, canvas.width, canvas.height);
        };

        new ResizeObserver(resize).observe(container);
        resize();

        const start = performance.now();
        const render = (now) => {
            gl.uniform1f(uTime, ((now - start) / 1000) * speed);
            gl.uniform2f(uRes, canvas.width, canvas.height);
            gl.uniform3f(uColor, rgb[0], rgb[1], rgb[2]);
            gl.uniform1f(uWarp, warp);
            gl.drawArrays(gl.TRIANGLES, 0, 6);
            requestAnimationFrame(render);
        };
        requestAnimationFrame(render);
    };

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-dark-veil]').forEach(mountDarkVeil);
    });
})();

// =========================================================
// 3. ANIMATED CONTENT ENGINE
// =========================================================
(function initAnimatedContent() {
    const EASE_MAP = {
        'power3.out': 'cubic-bezier(0.215, 0.61, 0.355, 1)',
        'power2.out': 'cubic-bezier(0.25, 0.46, 0.45, 0.94)',
        'power4.out': 'cubic-bezier(0.165, 0.84, 0.44, 1)',
        'expo.out': 'cubic-bezier(0.19, 1, 0.22, 1)',
        'back.out': 'cubic-bezier(0.34, 1.56, 0.64, 1)'
    };

    const mountAnimatedContent = (container) => {
        if (container.dataset.acMounted) return;
        container.dataset.acMounted = "true";

        const distance = parseFloat(container.dataset.distance ?? 100);
        const direction = container.dataset.direction || 'vertical';
        const reverse = container.dataset.reverse === 'true';
        const duration = parseFloat(container.dataset.duration ?? 0.8);
        const easeName = container.dataset.ease || 'power3.out';
        const initialOpacity = parseFloat(container.dataset.initialOpacity ?? 0);
        const animateOpacity = container.dataset.animateOpacity !== 'false';
        const scale = parseFloat(container.dataset.scale ?? 1);
        const blur = parseFloat(container.dataset.blur ?? 0);
        const stagger = parseFloat(container.dataset.stagger ?? 0.08);
        const threshold = parseFloat(container.dataset.threshold ?? 0.1);
        const delay = parseFloat(container.dataset.delay ?? 0);

        const easing = EASE_MAP[easeName] || easeName;
        const children = Array.from(container.children);
        const targets = children.length > 0 ? children : [container];

        const offset = reverse ? -distance : distance;
        const translateProp = direction === 'horizontal'
            ? `translate3d(${offset}px, 0, 0)`
            : `translate3d(0, ${offset}px, 0)`;

        targets.forEach((el) => {
            el.style.opacity = animateOpacity ? String(initialOpacity) : '1';
            el.style.transform = `${translateProp} scale(${scale})`;
            if (blur > 0) el.style.filter = `blur(${blur}px)`;
            el.style.willChange = 'transform, opacity, filter';
        });

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    targets.forEach((el, index) => {
                        const itemDelay = delay + (index * stagger);
                        el.style.transition = [
                            `transform ${duration}s ${easing} ${itemDelay}s`,
                            `opacity ${duration}s ${easing} ${itemDelay}s`,
                            blur > 0 ? `filter ${duration}s ${easing} ${itemDelay}s` : ''
                        ].filter(Boolean).join(', ');

                        requestAnimationFrame(() => {
                            el.style.opacity = '1';
                            el.style.transform = 'translate3d(0, 0, 0) scale(1)';
                            if (blur > 0) el.style.filter = 'blur(0px)';
                        });
                    });
                    observer.unobserve(container);
                }
            });
        }, { threshold });

        observer.observe(container);
    };

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-animated-content]').forEach(mountAnimatedContent);
    });
})();