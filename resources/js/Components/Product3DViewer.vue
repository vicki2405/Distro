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
const isWireframe = ref(false);
const activeAngle = ref('front');

let scene, camera, renderer, controls, animationFrameId;
let shirtGroup, shirtFrontMesh, shirtBackMesh, leftSleeveMesh, rightSleeveMesh;
let textureLoader = new THREE.TextureLoader();

// Buat Procedural Fallback Texture jika belum ada gambar yang diupload
const createFallbackTexture = () => {
    const canvas = document.createElement('canvas');
    canvas.width = 1024;
    canvas.height = 1024;
    const ctx = canvas.getContext('2d');

    // Background kain hitam pekat tekstur washed cotton
    ctx.fillStyle = '#0f172a';
    ctx.fillRect(0, 0, 1024, 1024);

    // Grid halus & bintik tekstur kain
    ctx.strokeStyle = '#1e293b';
    ctx.lineWidth = 1;
    for (let i = 0; i < 1024; i += 32) {
        ctx.beginPath();
        ctx.moveTo(i, 0);
        ctx.lineTo(i, 1024);
        ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(0, i);
        ctx.lineTo(1024, i);
        ctx.stroke();
    }

    // Streetwear Graphic Banner
    ctx.fillStyle = '#f59e0b';
    ctx.font = 'bold 54px monospace';
    ctx.textAlign = 'center';
    ctx.fillText('DISTRO OPERATIONS', 512, 380);

    ctx.fillStyle = '#ffffff';
    ctx.font = '900 68px sans-serif';
    ctx.fillText('STREETWEAR WMS', 512, 470);

    ctx.fillStyle = '#a855f7';
    ctx.font = '600 32px monospace';
    ctx.fillText(props.productName.toUpperCase().slice(0, 24), 512, 530);

    ctx.strokeStyle = '#f59e0b';
    ctx.lineWidth = 4;
    ctx.strokeRect(200, 280, 624, 320);

    ctx.fillStyle = '#64748b';
    ctx.font = '24px monospace';
    ctx.fillText('3D APPAREL MODEL • REALTIME WEBGL', 512, 700);

    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;
    return texture;
};

// Buat 3D T-Shirt / Apparel Geometry
const buildShirtModel = () => {
    shirtGroup = new THREE.Group();

    // 1. Torso Depan & Belakang (Bentuk Badan Kaos Melengkung Ergonomis)
    const shirtShape = new THREE.Shape();
    // Kerah & Bahu
    shirtShape.moveTo(-0.4, 1.1);
    shirtShape.quadraticCurveTo(0, 0.95, 0.4, 1.1); // Kerah leher
    shirtShape.lineTo(0.95, 0.9);                   // Bahu kanan
    shirtShape.lineTo(1.15, 0.5);                   // Lengan atas kanan
    shirtShape.lineTo(0.85, 0.35);                  // Ketiak kanan
    shirtShape.lineTo(0.75, -1.0);                  // Pinggang kanan ke bawah
    shirtShape.quadraticCurveTo(0, -1.05, -0.75, -1.0); // Bawah kaos melengkung
    shirtShape.lineTo(-0.85, 0.35);                 // Ketiak kiri
    shirtShape.lineTo(-1.15, 0.5);                  // Lengan atas kiri
    shirtShape.lineTo(-0.95, 0.9);                  // Bahu kiri
    shirtShape.closePath();

    // Ekstrusi tebal kain 3D
    const extrudeSettings = {
        depth: 0.22,
        bevelEnabled: true,
        bevelSegments: 4,
        steps: 2,
        bevelSize: 0.04,
        bevelThickness: 0.04,
    };

    const geometry = new THREE.ExtrudeGeometry(shirtShape, extrudeSettings);
    geometry.center();

    // Bahan Kaos (Tekstur Kain Katun Hitam Streetwear)
    const backMaterial = new THREE.MeshStandardMaterial({
        color: 0x111827,
        roughness: 0.75,
        metalness: 0.1,
        wireframe: isWireframe.value,
    });

    let frontMaterial;
    if (props.imageUrl) {
        const texture = textureLoader.load(props.imageUrl);
        texture.colorSpace = THREE.SRGBColorSpace;
        texture.wrapS = THREE.ClampToEdgeWrapping;
        texture.wrapT = THREE.ClampToEdgeWrapping;

        frontMaterial = new THREE.MeshStandardMaterial({
            map: texture,
            roughness: 0.65,
            metalness: 0.08,
            wireframe: isWireframe.value,
        });
    } else {
        frontMaterial = new THREE.MeshStandardMaterial({
            map: createFallbackTexture(),
            roughness: 0.7,
            metalness: 0.1,
            wireframe: isWireframe.value,
        });
    }

    // Material Array: [Depan/Belakang, Sisi samping kain]
    const materials = [frontMaterial, backMaterial];
    shirtFrontMesh = new THREE.Mesh(geometry, materials);
    shirtFrontMesh.castShadow = true;
    shirtFrontMesh.receiveShadow = true;
    shirtGroup.add(shirtFrontMesh);

    // 2. Rib Kerah Leher 3D (Collar Ring)
    const collarGeom = new THREE.TorusGeometry(0.36, 0.04, 12, 32);
    const collarMat = new THREE.MeshStandardMaterial({
        color: 0x1e293b,
        roughness: 0.9,
    });
    const collar = new THREE.Mesh(collarGeom, collarMat);
    collar.rotation.x = Math.PI / 2.2;
    collar.position.set(0, 1.05, 0.02);
    collar.scale.set(1.2, 0.7, 1);
    shirtGroup.add(collar);

    // 3. Manset Lengan Kiri & Kanan 3D (Sleeve Cuffs)
    const cuffGeom = new THREE.CylinderGeometry(0.18, 0.19, 0.1, 16);
    const cuffMat = new THREE.MeshStandardMaterial({ color: 0x0f172a, roughness: 0.8 });

    leftSleeveMesh = new THREE.Mesh(cuffGeom, cuffMat);
    leftSleeveMesh.rotation.z = Math.PI / 3.2;
    leftSleeveMesh.position.set(-1.05, 0.42, 0);
    shirtGroup.add(leftSleeveMesh);

    rightSleeveMesh = new THREE.Mesh(cuffGeom, cuffMat);
    rightSleeveMesh.rotation.z = -Math.PI / 3.2;
    rightSleeveMesh.position.set(1.05, 0.42, 0);
    shirtGroup.add(rightSleeveMesh);

    // 4. Pedestal Showroom Studio di Bawah Pakaian (Circular Turntable Stage)
    const pedestalGroup = new THREE.Group();
    const stageGeom = new THREE.CylinderGeometry(1.6, 1.7, 0.12, 48);
    const stageMat = new THREE.MeshStandardMaterial({
        color: 0x090d16,
        roughness: 0.35,
        metalness: 0.75,
    });
    const stage = new THREE.Mesh(stageGeom, stageMat);
    stage.position.y = -1.45;
    stage.receiveShadow = true;
    pedestalGroup.add(stage);

    // Ring Cahaya Neon Turntable
    const ringGeom = new THREE.RingGeometry(1.61, 1.66, 48);
    const ringMat = new THREE.MeshBasicMaterial({
        color: 0xa855f7,
        side: THREE.DoubleSide,
    });
    const ring = new THREE.Mesh(ringGeom, ringMat);
    ring.rotation.x = Math.PI / 2;
    ring.position.y = -1.38;
    pedestalGroup.add(ring);

    shirtGroup.add(pedestalGroup);
    shirtGroup.position.y = 0.2;

    scene.add(shirtGroup);
};

const initThree = () => {
    if (!containerRef.value) return;
    const width = containerRef.value.clientWidth;
    const height = containerRef.value.clientHeight || 420;

    // 1. Scene & Background
    scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(0x020617, 0.06);

    // 2. Camera
    camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 100);
    camera.position.set(0, 0.3, 3.8);

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

    // 4. OrbitControls (Rotasi 360 Derajat Bebas)
    controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.05;
    controls.autoRotate = isAutoRotate.value;
    controls.autoRotateSpeed = 2.2;
    controls.minDistance = 2.2;
    controls.maxDistance = 5.5;
    controls.maxPolarAngle = Math.PI / 2 + 0.15; // Cegah tembus bawah pedestal
    controls.target.set(0, 0, 0);

    // 5. Studio Lighting
    // Ambient Light
    const ambientLight = new THREE.AmbientLight(0xffffff, 1.2);
    scene.add(ambientLight);

    // Key Light Depan
    const keyLight = new THREE.DirectionalLight(0xfff5eb, 2.8);
    keyLight.position.set(3, 4, 4);
    keyLight.castShadow = true;
    keyLight.shadow.mapSize.width = 1024;
    keyLight.shadow.mapSize.height = 1024;
    scene.add(keyLight);

    // Fill Light Samping Kiri (Purple Glow)
    const fillLight = new THREE.PointLight(0xa855f7, 2.5, 8);
    fillLight.position.set(-3, 2, 2);
    scene.add(fillLight);

    // Rim Light Belakang (Amber Warm Light)
    const rimLight = new THREE.PointLight(0xf59e0b, 3.0, 8);
    rimLight.position.set(2, 3, -3);
    scene.add(rimLight);

    // 6. Bangun Model Kaos 3D
    buildShirtModel();

    // 7. Animation Loop
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
    const height = containerRef.value.clientHeight || 420;
    camera.aspect = width / height;
    camera.updateProjectionMatrix();
    renderer.setSize(width, height);
};

// Controls Methods
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
        camera.position.set(0, 0.3, 3.8);
    } else if (view === 'back') {
        camera.position.set(0, 0.3, -3.8);
    } else if (view === 'left') {
        camera.position.set(-3.8, 0.3, 0);
    } else if (view === 'right') {
        camera.position.set(3.8, 0.3, 0);
    } else if (view === 'top') {
        camera.position.set(0, 3.8, 0.5);
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

const toggleWireframe = () => {
    isWireframe.value = !isWireframe.value;
    if (shirtFrontMesh) {
        shirtFrontMesh.material.forEach((mat) => {
            mat.wireframe = isWireframe.value;
        });
    }
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
    if (scene && shirtGroup) {
        scene.remove(shirtGroup);
        buildShirtModel();
    }
});
</script>

<template>
    <div class="relative w-full rounded-3xl overflow-hidden bg-slate-950 border border-slate-800 shadow-2xl flex flex-col">

        <!-- HEADER OVERLAY VIEWPORT 3D -->
        <div class="absolute top-4 inset-x-4 z-20 flex items-center justify-between pointer-events-none">
            <div class="flex items-center gap-2 pointer-events-auto">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
                <span class="text-[11px] font-mono font-bold uppercase tracking-wider text-white px-2.5 py-1 rounded-xl bg-slate-900/90 border border-slate-700/80 backdrop-blur-md shadow-md">
                    WebGL 3D Studio
                </span>
            </div>

            <div class="flex items-center gap-2 pointer-events-auto">
                <button
                    type="button"
                    @click="toggleWireframe"
                    :class="[
                        'text-[10px] font-mono px-2.5 py-1 rounded-xl backdrop-blur-md border transition cursor-pointer',
                        isWireframe
                            ? 'bg-purple-500 text-slate-950 font-black border-purple-400'
                            : 'bg-slate-900/80 text-slate-300 border-slate-700 hover:text-white'
                    ]"
                    title="Toggle Wireframe 3D Mesh"
                >
                    {{ isWireframe ? 'Grid Mesh ON' : 'Mesh' }}
                </button>
                <button
                    type="button"
                    @click="toggleAutoRotate"
                    :class="[
                        'text-[10px] font-mono px-2.5 py-1 rounded-xl backdrop-blur-md border transition cursor-pointer flex items-center gap-1.5',
                        isAutoRotate
                            ? 'bg-amber-500 text-slate-950 font-black border-amber-400 shadow-lg shadow-amber-500/20'
                            : 'bg-slate-900/80 text-slate-400 border-slate-700 hover:text-white'
                    ]"
                >
                    <span :class="isAutoRotate ? 'animate-spin' : ''">⟳</span>
                    <span>{{ isAutoRotate ? '360° Putar ON' : 'Putar Pause' }}</span>
                </button>
            </div>
        </div>

        <!-- THREE.JS CANVAS CONTAINER (DRAGGABLE 360°) -->
        <div
            ref="containerRef"
            class="w-full h-[430px] sm:h-[480px] cursor-grab active:cursor-grabbing relative z-10"
        ></div>

        <!-- FLOATING INSTRUCTION HINT -->
        <div class="absolute bottom-16 inset-x-0 z-20 flex justify-center pointer-events-none">
            <span class="text-[10px] font-mono text-slate-400 bg-slate-900/80 px-3 py-1 rounded-full border border-slate-800 backdrop-blur-md">
                🖱️ Drag/Geser untuk Putar 360° • Scroll untuk Zoom
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
