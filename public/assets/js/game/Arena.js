/**
 * VOIDSTRIKE ARENA — 3D Arena Environments
 * Procedural generation for Neon Core, Orbital Station, and Magma Foundry.
 */

import * as THREE from '../vendor/three/three.module.js';

export class Arena {
    constructor(arenaId = 'neon_core', scene) {
        this.id = arenaId;
        this.scene = scene;
        this.obstacles = []; // { x, z, radius, type }
        this.hazards = []; // { x, z, radius, damage, type, mesh }
        this.rotatingPillars = [];

        this.initParameters(arenaId);
        this.buildEnvironment();
    }

    initParameters(id) {
        switch (id) {
            case 'orbital_station':
                this.radius = 55;
                this.ambientColor = 0x0a0718;
                this.fogColor = 0x04020a;
                this.primaryColor = 0x9d4edd;
                this.floorColor = 0x0a0c16;
                break;

            case 'magma_foundry':
                this.radius = 48;
                this.ambientColor = 0x1c0604;
                this.fogColor = 0x0d0302;
                this.primaryColor = 0xff4800;
                this.floorColor = 0x120807;
                break;

            case 'neon_core':
            default:
                this.radius = 50;
                this.ambientColor = 0x07111e;
                this.fogColor = 0x050811;
                this.primaryColor = 0x00f0ff;
                this.floorColor = 0x060912;
                break;
        }
    }

    buildEnvironment() {
        // Fog & Ambient Lighting
        this.scene.fog = new THREE.FogExp2(this.fogColor, 0.015);

        const hemiLight = new THREE.HemisphereLight(this.primaryColor, 0x000000, 0.8);
        this.scene.add(hemiLight);

        const dirLight = new THREE.DirectionalLight(0xffffff, 1.2);
        dirLight.position.set(30, 45, 20);
        dirLight.castShadow = true;
        dirLight.shadow.mapSize.width = 1024;
        dirLight.shadow.mapSize.height = 1024;
        dirLight.shadow.camera.near = 10;
        dirLight.shadow.camera.far = 120;
        dirLight.shadow.camera.left = -this.radius;
        dirLight.shadow.camera.right = this.radius;
        dirLight.shadow.camera.top = this.radius;
        dirLight.shadow.camera.bottom = -this.radius;
        this.scene.add(dirLight);

        // Floor Platform
        const floorGeo = new THREE.CylinderGeometry(this.radius, this.radius, 1.5, 64);
        const floorMat = new THREE.MeshStandardMaterial({
            color: this.floorColor,
            roughness: 0.25,
            metalness: 0.8,
        });
        const floor = new THREE.Mesh(floorGeo, floorMat);
        floor.position.y = -0.75;
        floor.receiveShadow = true;
        this.scene.add(floor);

        // Cyber Grid Overlay
        const gridHelper = new THREE.GridHelper(this.radius * 2, 40, this.primaryColor, 0x18243b);
        gridHelper.position.y = 0.02;
        this.scene.add(gridHelper);

        // Outer Perimeter Barrier Ring
        const ringGeo = new THREE.TorusGeometry(this.radius, 0.4, 8, 64);
        ringGeo.rotateX(Math.PI / 2);
        const ringMat = new THREE.MeshBasicMaterial({ color: this.primaryColor });
        const ring = new THREE.Mesh(ringGeo, ringMat);
        ring.position.y = 0.2;
        this.scene.add(ring);

        // Arena Specific Layout
        if (this.id === 'orbital_station') {
            this.buildOrbitalStation();
        } else if (this.id === 'magma_foundry') {
            this.buildMagmaFoundry();
        } else {
            this.buildNeonCore();
        }
    }

    buildNeonCore() {
        const pillarMat = new THREE.MeshStandardMaterial({
            color: 0x091424,
            metalness: 0.9,
            roughness: 0.2,
        });
        const neonCyan = new THREE.MeshBasicMaterial({ color: 0x00f0ff });
        const neonCrimson = new THREE.MeshBasicMaterial({ color: 0xff2a5f });

        // 8 Energy Pillars around mid ring
        for (let i = 0; i < 8; i++) {
            const angle = (i / 8) * Math.PI * 2;
            const r = 26;
            const px = Math.cos(angle) * r;
            const pz = Math.sin(angle) * r;

            const pillarGeo = new THREE.CylinderGeometry(1.4, 1.8, 8, 8);
            const pillar = new THREE.Mesh(pillarGeo, pillarMat);
            pillar.position.set(px, 4, pz);
            pillar.castShadow = true;
            this.scene.add(pillar);

            // Glowing ring on pillar
            const pRing = new THREE.Mesh(new THREE.TorusGeometry(1.6, 0.15, 6, 16), neonCyan);
            pRing.rotateX(Math.PI / 2);
            pRing.position.set(px, 4.5, pz);
            this.scene.add(pRing);

            this.obstacles.push({ x: px, z: pz, radius: 2.2, type: 'pillar' });
        }

        // 2 Rotating Laser Barrier Hazards
        const beamGeo = new THREE.CylinderGeometry(0.12, 0.12, 22, 6);
        beamGeo.rotateZ(Math.PI / 2);

        const laserMesh1 = new THREE.Mesh(beamGeo, neonCrimson);
        laserMesh1.position.set(0, 1.2, 0);
        this.scene.add(laserMesh1);
        this.rotatingPillars.push({ mesh: laserMesh1, speed: 0.4 });

        this.hazards.push({
            type: 'rotating_laser',
            damage: 25,
            mesh: laserMesh1,
            length: 22,
        });
    }

    buildOrbitalStation() {
        const rockMat = new THREE.MeshStandardMaterial({
            color: 0x22242f,
            roughness: 0.9,
            metalness: 0.1,
        });

        // Floating Asteroids & Debris as obstacles
        const positions = [
            [-18, -12], [18, 12], [-14, 18], [14, -18],
            [0, 22], [0, -22], [-24, 0], [24, 0]
        ];

        positions.forEach(([x, z]) => {
            const rockGeo = new THREE.DodecahedronGeometry(2.4, 1);
            const rock = new THREE.Mesh(rockGeo, rockMat);
            rock.position.set(x, 1.5, z);
            rock.rotation.set(Math.random(), Math.random(), Math.random());
            rock.castShadow = true;
            this.scene.add(rock);

            this.obstacles.push({ x, z, radius: 2.8, type: 'asteroid' });
        });

        // Gravitational Repulsion Well in Center
        const wellGeo = new THREE.CylinderGeometry(4.5, 4.5, 0.2, 32);
        const wellMat = new THREE.MeshBasicMaterial({ color: 0xbf00ff, wireframe: true });
        const well = new THREE.Mesh(wellGeo, wellMat);
        well.position.set(0, 0.1, 0);
        this.scene.add(well);

        this.hazards.push({
            type: 'gravity_well',
            x: 0,
            z: 0,
            radius: 5.5,
            pushForce: 45,
            damage: 8,
        });
    }

    buildMagmaFoundry() {
        const rockMat = new THREE.MeshStandardMaterial({
            color: 0x1f1412,
            roughness: 0.85,
            metalness: 0.2,
        });
        const lavaMat = new THREE.MeshBasicMaterial({ color: 0xff3b00 });

        // Coolant Columns (Obstacles)
        const colGeo = new THREE.BoxGeometry(3.5, 10, 3.5);
        const colPos = [
            [-16, -16], [16, -16], [-16, 16], [16, 16],
            [-22, 0], [22, 0]
        ];

        colPos.forEach(([x, z]) => {
            const col = new THREE.Mesh(colGeo, rockMat);
            col.position.set(x, 5, z);
            col.castShadow = true;
            this.scene.add(col);
            this.obstacles.push({ x, z, radius: 2.6, type: 'column' });
        });

        // Lava Trench Hazards (Damage over time)
        const trench1Geo = new THREE.PlaneGeometry(8, 38);
        trench1Geo.rotateX(-Math.PI / 2);
        const trench1 = new THREE.Mesh(trench1Geo, lavaMat);
        trench1.position.set(0, 0.05, 0);
        this.scene.add(trench1);

        this.hazards.push({
            type: 'lava_trench',
            minX: -4,
            maxX: 4,
            minZ: -19,
            maxZ: 19,
            dps: 22,
        });
    }

    update(dt, player) {
        // Rotate laser hazards
        this.rotatingPillars.forEach(p => {
            p.mesh.rotation.y += p.speed * dt;
        });

        // Check hazard interactions with player
        this.hazards.forEach(h => {
            if (h.type === 'rotating_laser') {
                // Ray-distance check from center
                const pPos = player.position;
                const angle = h.mesh.rotation.y;
                const halfLen = h.length / 2;

                const laserDir = new THREE.Vector3(Math.cos(angle), 0, -Math.sin(angle));
                const toPlayer = new THREE.Vector3(pPos.x, 0, pPos.z);
                const proj = toPlayer.dot(laserDir);

                if (Math.abs(proj) <= halfLen) {
                    const closest = laserDir.clone().multiplyScalar(proj);
                    const dist = toPlayer.distanceTo(closest);
                    if (dist < 1.2) {
                        player.takeDamage(h.damage * dt * 2.5);
                    }
                }
            } else if (h.type === 'gravity_well') {
                const dist = Math.hypot(player.position.x - h.x, player.position.z - h.z);
                if (dist < h.radius) {
                    const push = new THREE.Vector3(player.position.x - h.x, 0, player.position.z - h.z).normalize();
                    player.velocity.add(push.multiplyScalar(h.pushForce * dt));
                    player.takeDamage(h.damage * dt);
                }
            } else if (h.type === 'lava_trench') {
                if (player.position.x >= h.minX && player.position.x <= h.maxX &&
                    player.position.z >= h.minZ && player.position.z <= h.maxZ) {
                    player.takeDamage(h.dps * dt);
                }
            }
        });

        // Handle obstacle collisions for player
        this.obstacles.forEach(obs => {
            const dx = player.position.x - obs.x;
            const dz = player.position.z - obs.z;
            const dist = Math.hypot(dx, dz);
            const minDist = obs.radius + 1.2;

            if (dist < minDist && dist > 0.01) {
                const overlap = minDist - dist;
                const nx = dx / dist;
                const nz = dz / dist;

                player.position.x += nx * overlap;
                player.position.z += nz * overlap;
                // Damp velocity
                player.velocity.multiplyScalar(0.7);
            }
        });
    }

    dispose() {
        this.obstacles = [];
        this.hazards = [];
        this.rotatingPillars = [];
    }
}
