<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

const props = defineProps({
    imageUrl: {
        type: String,
        default: null,
    },
    productName: {
        type: String,
        default: 'Artikel Distro',
    },
    categoryName: {
        type: String,
        default: 'Pakaian Distro',
    },
});

const containerRef = ref(null);
const isAutoRotate = ref(true);
const activeAngle = ref('front');

let scene, camera, renderer, controls, animationFrameId;
let showcaseGroup, frontMesh, backMesh, pedestalMesh;

// Helper: Buat tekstur kain realistis dengan gambar produk terpusat sempurna
const generateProductTexture = (imageElement = null) => {
    const canvas = document.createElement('canvas');
    canvas.width = 2048;
    canvas.height = 2048;
    const ctx = canvas.getContext('2d');

    // 1. Warna Dasar Kain Hitam Washed Streetwear
    ctx.fillStyle = '#0b0f17';
    ctx.fillRect(0, 0, 2048, 2048);

    // 2. Efek Serat Tekstur Kain Katun (Micro Noise)
    ctx.fillStyle = 'rgba(255, 255, 255, 0.025)';
    for (let i = 0; i < 2048; i += 8) {
        ctx.fillRect(i, 0, 4, 2048);
        ctx.fillRect(0, i, 2048, 4);
    }

    if (imageElement) {
        // 3. Gambar Produk Terpusat Presisi (Aspect-Fit Center)
        const imgW = imageElement.naturalWidth || imageElement.width;
        const imgH = imageElement.naturalHeight || imageElement.height;
        const targetSize = 1750;

        let drawW, drawH;
        if (imgW > imgH) {
            drawW = targetSize;
            drawH = (imgH / imgW) * targetSize;
        } else {
            drawH = targetSize;
            drawW = (imgW / imgH) * targetSize;
        }

        const drawX = (2048 - drawW) / 2;
        const drawY = (2048 - drawH) / 2;

        ctx.drawImage(imageElement, drawX, drawY, drawW, drawH);
    } else {
        // Fallback jika belum upload foto
        ctx.strokeStyle = '#f59e0b';
        ctx.lineWidth = 6;
        ctx.strokeRect(300, 400, 1448, 1248);

        ctx.fillStyle = '#f59e0b';
        ctx.font = 'bold 80px monospace';
        ctx.textAlign = 'center';
        ctx.fillText('DISTRO OPERATIONS', 1024, 850);

        ctx.fillStyle = '#ffffff';
        ctx.font = '900 110px sans-serif';
        ctx.fillText('STREETWEAR APPAREL', 1024, 1000);

        ctx.fillStyle = '#a855f7';
        ctx.font = 'bold 60px monospace';
        ctx.fillText(props.productName.toUpperCase().slice(0, 28), 1024, 1120);

        ctx.fillStyle = '#64748b';
        ctx.font = '40px monospace';
        ctx.fillText('3D STUDIO SHOWCASE • DISTRO WMS', 1024, 1350);
    }

    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;
    texture.generateMipmaps = true;
    texture.minFilter = THREE.LinearMipmapLinearFilter;
    return texture;
};

// Buat Tekstur Bagian Belakang Pakaian
const generateBackTexture = () => {
    const canvas = document.createElement('canvas');
    canvas.width = 1024;
    canvas.height = 1024;
    const ctx = canvas.getContext('2d');

    ctx.fillStyle = '#0b0f17';
    ctx.fillRect(0, 0, 1024, 1024);

    // Label Belakang Leher Distro
    ctx.fillStyle = '#1e293b';
    ctx.fillRect(412, 120, 200, 80);

    ctx.strokeStyle = '#334155';
    ctx.lineWidth = 2;
    ctx.strokeRect(412, 120, 200, 80);

    ctx.fillStyle = '#f59e0b';
    ctx.font = 'bold 24px monospace';
    ctx.textAlign = 'center';
    ctx.fillText('DISTRO WMS', 512, 155);

    ctx.fillStyle = '#94a3b8';
    ctx.font = '16px monospace';
    ctx.fillText('AUTHENTIC APPAREL', 512, 180);

    // Garis Jahitan Pundak & Punggung
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.08)';
    ctx.setLineDash([8, 8]);
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(100, 240);
    ctx.lineTo(924, 240);
    ctx.stroke();

    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;
    return texture;
};

// Bangun 3D Studio Showcase Model
const buildShowcase = (imageElement = null) => {
    if (showcaseGroup) {
        scene.remove(showcaseGroup);
    }

    showcaseGroup = new THREE.Group();

    // 1. Geometri Panel Pakaian 3D Melengkung Ergonomis (Curved Torso Geometry)
    const width = 2.4;
    const height = 2.8;
    const segmentsX = 32;
    const segmentsY = 32;

    const planeGeom = new THREE.PlaneGeometry(width, height, segmentsX, segmentsY);
    const pos = planeGeom.attributes.position;

    // Berikan lengkungan alami seperti pakaian yang digantung/dipajang di studio
    for (let i = 0; i < pos.count; i++) {
        const x = pos.getX(i);
        const y = pos.getY(i);
        // Lengkungan horizontal & vertikal lembut
        const z = -Math.pow(x / (width * 0.5), 2) * 0.18 + Math.sin(y * 1.5) * 0.04;
        pos.setZ(i, z);
    }
    planeGeom.computeVertexNormals();

    const frontTexture = generateProductTexture(imageElement);
    const frontMaterial = new THREE.MeshStandardMaterial({
        map: frontTexture,
        roughness: 0.75,
        metalness: 0.08,
        side: THREE.FrontSide,
    });

    frontMesh = new THREE.Mesh(planeGeom, frontMaterial);
    frontMesh.castShadow = true;
    frontMesh.receiveShadow = true;
    showcaseGroup.add(frontMesh);

    // 2. Bagian Belakang Pakaian (Back Side)
    const backGeom = planeGeom.clone();
    const backTexture = generateBackTexture();
    const backMaterial = new THREE.MeshStandardMaterial({
        map: backTexture,
        roughness: 0.8,
        metalness: 0.05,
        side: THREE.FrontSide,
    });

    backMesh = new THREE.Mesh(backGeom, backMaterial);
    backMesh.rotation.y = Math.PI;
    backMesh.position.z = -0.06;
    showcaseGroup.add(backMesh);

    // 3. Gantungan Hanger Studio Distro Minimalis (Atas)
    const hangerGeom = new THREE.CylinderGeometry(0.025, 0.025, 2.2, 16);
    const hangerMat = new THREE.MeshStandardMaterial({ color: 0x334155, metalness: 0.8, roughness: 0.2 });
    const hanger = new THREE.Mesh(hangerGeom, hangerMat);
    hanger.rotation.z = Math.PI / 2;
    hanger.position.set(0, 1.45, -0.03);
    showcaseGroup.add(hanger);

    // Hook Hanger
    const hookGeom = new THREE.TorusGeometry(0.12, 0.02, 12, 24, Math.PI * 1.3);
    const hook = new THREE.Mesh(hookGeom, hangerMat);
    hook.position.set(0, 1.58, -0.03);
    hook.rotation.z = -Math.PI / 2;
    showcaseGroup.add(hook);

    // 4. Pedestal Showroom Studio (Panggung Melingkar)
    const stageGroup = new THREE.Group();
    
    // Alas Utama
    const stageGeom = new THREE.CylinderGeometry(1.9, 2.05, 0.15, 64);
    const stageMat = new THREE.MeshStandardMaterial({
        color: 0x070a12,
        roughness: 0.25,
        metalness: 0.85,
    });
    pedestalMesh = new THREE.Mesh(stageGeom, stageMat);
    pedestalMesh.position.y = -1.8;
    pedestalMesh.receiveShadow = true;
    stageGroup.add(pedestalMesh);

    // Ring Cahaya Neon Amber & Purple
    const neonRingGeom = new THREE.RingGeometry(1.91, 1.96, 64);
    const neonRingMat = new THREE.MeshBasicMaterial({
        color: 0xf59e0b,
        side: THREE.DoubleSide,
    });
    const neonRing = new THREE.Mesh(neonRingGeom, neonRingMat);
    neonRing.rotation.x = Math.PI / 2;
    neonRing.position.y = -1.72;
    stageGroup.add(neonRing);

    showcaseGroup.add(stageGroup);
    showcaseGroup.position.y = 0.15;

    scene.add(showcaseGroup);
};

const loadTextureAndBuild = () => {
    if (props.imageUrl) {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.src = props.imageUrl;
        img.onload = () => {
            buildShowcase(img);
        };
        img.onerror = () => {
            buildShowcase(null);
        };
    } else {
        buildShowcase(null);
    }
};

const initThree = () => {
    if (!containerRef.value) return;
    const width = containerRef.value.clientWidth;
    const height = containerRef.value.clientHeight || 450;

    // 1. Scene
    scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(0x020617, 0.05);

    // 2. Camera
    camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 100);
    camera.position.set(0, 0.2, 4.3);

    // 3. Renderer
    renderer = new THREE.WebGLRenderer({
        antialias: true,
        alpha: true,
        powerPreference: 'high-performance',
    });
    renderer.setSize(width, height);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    renderer.outputColorSpace = THREE.SRGBColorSpace;

    containerRef.value.replaceChildren(renderer.domElement);

    // 4. OrbitControls (Putar Bebas 360 Derajat Penuh)
    controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.06;
    controls.autoRotate = isAutoRotate.value;
    controls.autoRotateSpeed = 2.0;
    controls.minDistance = 2.4;
    controls.maxDistance = 6.0;
    controls.maxPolarAngle = Math.PI / 2 + 0.12;
    controls.target.set(0, 0, 0);

    // 5. Studio Lighting
    const ambientLight = new THREE.AmbientLight(0xffffff, 1.6);
    scene.add(ambientLight);

    // Key Light Depan Lembut
    const keyLight = new THREE.DirectionalLight(0xfff7ed, 3.2);
    keyLight.position.set(2.5, 4, 4);
    keyLight.castShadow = true;
    keyLight.shadow.mapSize.width = 1024;
    keyLight.shadow.mapSize.height = 1024;
    scene.add(keyLight);

    // Spot Light Fokus ke Sablon Pakaian
    const spotLight = new THREE.SpotLight(0xffffff, 2.5, 10, Math.PI / 5, 0.4);
    spotLight.position.set(0, 3, 3.5);
    spotLight.target.position.set(0, 0, 0);
    scene.add(spotLight);
    scene.add(spotLight.target);

    // Rim Light Ungu & Kuning Belakang
    const rimPurple = new THREE.PointLight(0xa855f7, 3.5, 7);
    rimPurple.position.set(-3, 2, -2.5);
    scene.add(rimPurple);

    const rimAmber = new THREE.PointLight(0xf59e0b, 3.5, 7);
    rimAmber.position.set(3, 2, -2.5);
    scene.add(rimAmber);

    // 6. Muat Tekstur & Render
    loadTextureAndBuild();

    // 7. Render Loop
    const animate = () => {
        animationFrameId = requestAnimationFrame(animate);
        controls.update();
        renderer.render(scene, camera);
    };
    animate();
};

const onWindowResize = () => {
    if (!containerRef.value || !renderer || !camera) return;
    const width = containerRef.value.clientWidth;
    const height = containerRef.value.clientHeight || 450;
    camera.aspect = width / height;
    camera.updateProjectionMatrix();
    renderer.setSize(width, height);
};

const toggleAutoRotate = () => {
    isAutoRotate.value = !isAutoRotate.value;
    if (controls) {
        controls.autoRotate = isAutoRotate.value;
    }
};

const setCameraView = (view) => {
    activeAngle.value = view;
    if (!camera || !controls) return;

    if (view === 'front') {
        camera.position.set(0, 0.2, 4.3);
    } else if (view === 'back') {
        camera.position.set(0, 0.2, -4.3);
    } else if (view === 'left') {
        camera.position.set(-4.3, 0.2, 0);
    } else if (view === 'right') {
        camera.position.set(4.3, 0.2, 0);
    }
    controls.target.set(0, 0, 0);
    controls.update();
};

const zoomIn = () => {
    if (!camera) return;
    camera.position.multiplyScalar(0.85);
    controls.update();
};

const zoomOut = () => {
    if (!camera) return;
    camera.position.multiplyScalar(1.15);
    controls.update();
};

onMounted(() => {
    initThree();
    window.addEventListener('resize', onWindowResize);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', onWindowResize);
    if (animationFrameId) {
        cancelAnimationFrame(animationFrameId);
    }
    if (renderer) {
        renderer.dispose();
    }
});

watch(() => props.imageUrl, () => {
    loadTextureAndBuild();
});
</script>

<template>
    <div class="relative w-full rounded-3xl overflow-hidden bg-slate-950 border border-slate-800 shadow-2xl flex flex-col">

        <!-- HEADER OVERLAY VIEWPORT 3D -->
        <div class="absolute top-4 inset-x-4 z-20 flex items-center justify-between pointer-events-none">
            <div class="flex items-center gap-2 pointer-events-auto">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-[11px] font-mono font-bold uppercase tracking-wider text-white px-2.5 py-1 rounded-xl bg-slate-900/90 border border-slate-700/80 backdrop-blur-md shadow-md">
                    3D Studio Showroom
                </span>
            </div>

            <div class="flex items-center gap-2 pointer-events-auto">
                <button
                    type="button"
                    @click="toggleAutoRotate"
                    :class="[
                        'text-[10px] font-mono px-3 py-1.5 rounded-xl backdrop-blur-md border transition cursor-pointer flex items-center gap-1.5',
                        isAutoRotate
                            ? 'bg-amber-500 text-slate-950 font-black border-amber-400 shadow-lg shadow-amber-500/25'
                            : 'bg-slate-900/80 text-slate-400 border-slate-700 hover:text-white'
                    ]"
                >
                    <span :class="isAutoRotate ? 'animate-spin' : ''">⟳</span>
                    <span>{{ isAutoRotate ? '360° Putar Aktif' : 'Putar Jeda' }}</span>
                </button>
            </div>
        </div>

        <!-- THREE.JS CANVAS CONTAINER (PUTAR BEBAS 360°) -->
        <div
            ref="containerRef"
            class="w-full h-[440px] sm:h-[490px] cursor-grab active:cursor-grabbing relative z-10"
        ></div>

        <!-- FLOATING INSTRUCTION HINT -->
        <div class="absolute bottom-16 inset-x-0 z-20 flex justify-center pointer-events-none">
            <span class="text-[10px] font-mono text-slate-400 bg-slate-900/85 px-3.5 py-1 rounded-full border border-slate-800 backdrop-blur-md shadow">
                🖱️ Klik & Geser untuk Memutar 360° Bebas • Scroll untuk Zoom
            </span>
        </div>

        <!-- TOOLBAR KONTROL SUDUT PANDANG 3D (BAWAH) -->
        <div class="relative z-20 p-3 bg-slate-900/95 border-t border-slate-800/90 flex flex-wrap items-center justify-between gap-2">
            <!-- Preset Sudut 360 -->
            <div class="flex items-center gap-1.5">
                <span class="text-[10px] font-mono text-slate-400 hidden sm:inline mr-1">SUDUT:</span>
                <button
                    type="button"
                    @click="setCameraView('front')"
                    :class="[
                        'px-2.5 py-1 rounded-lg text-[10px] font-mono font-bold transition cursor-pointer border',
                        activeAngle === 'front' ? 'bg-purple-500 text-slate-950 border-purple-400' : 'bg-slate-950 text-slate-300 border-slate-800 hover:text-white'
                    ]"
                >
                    Depan
                </button>
                <button
                    type="button"
                    @click="setCameraView('back')"
                    :class="[
                        'px-2.5 py-1 rounded-lg text-[10px] font-mono font-bold transition cursor-pointer border',
                        activeAngle === 'back' ? 'bg-purple-500 text-slate-950 border-purple-400' : 'bg-slate-950 text-slate-300 border-slate-800 hover:text-white'
                    ]"
                >
                    Belakang
                </button>
                <button
                    type="button"
                    @click="setCameraView('left')"
                    :class="[
                        'px-2.5 py-1 rounded-lg text-[10px] font-mono font-bold transition cursor-pointer border',
                        activeAngle === 'left' ? 'bg-purple-500 text-slate-950 border-purple-400' : 'bg-slate-950 text-slate-300 border-slate-800 hover:text-white'
                    ]"
                >
                    Kiri
                </button>
                <button
                    type="button"
                    @click="setCameraView('right')"
                    :class="[
                        'px-2.5 py-1 rounded-lg text-[10px] font-mono font-bold transition cursor-pointer border',
                        activeAngle === 'right' ? 'bg-purple-500 text-slate-950 border-purple-400' : 'bg-slate-950 text-slate-300 border-slate-800 hover:text-white'
                    ]"
                >
                    Kanan
                </button>
            </div>

            <!-- Kontrol Zoom & Reset -->
            <div class="flex items-center gap-1.5">
                <button
                    type="button"
                    @click="zoomIn"
                    class="w-7 h-7 rounded-lg bg-slate-950 border border-slate-800 hover:border-slate-700 text-slate-200 text-xs font-mono font-bold flex items-center justify-center cursor-pointer"
                    title="Perbesar"
                >
                    +
                </button>
                <button
                    type="button"
                    @click="zoomOut"
                    class="w-7 h-7 rounded-lg bg-slate-950 border border-slate-800 hover:border-slate-700 text-slate-200 text-xs font-mono font-bold flex items-center justify-center cursor-pointer"
                    title="Perkecil"
                >
                    -
                </button>
                <button
                    type="button"
                    @click="setCameraView('front')"
                    class="px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-800 hover:border-slate-700 text-slate-400 hover:text-white text-[10px] font-mono cursor-pointer"
                    title="Reset Posisi Default"
                >
                    ↺ Reset
                </button>
            </div>
        </div>

    </div>
</template>
