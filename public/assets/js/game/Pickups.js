/**
 * VOIDSTRIKE ARENA — Pickups & Resources Manager
 * Floating interactive collectibles with magnetic attraction and procedural geometry.
 */

import * as THREE from '../vendor/three/three.module.js';

export class PickupsManager {
    constructor(scene, audio) {
        this.scene = scene;
        this.audio = audio;
        this.items = [];
        this.spawnTimer = 0;
        this.spawnInterval = 7.0; // spawn a pickup every 7 seconds
    }

    spawn(type, x, z) {
        let color = 0x00ff88;
        let geo = new THREE.BoxGeometry(0.6, 0.6, 0.6);

        if (type === 'energy') {
            color = 0x00f0ff;
            geo = new THREE.OctahedronGeometry(0.45);
        } else if (type === 'overcharge') {
            color = 0xbf00ff;
            geo = new THREE.TetrahedronGeometry(0.5);
        } else if (type === 'multiplier') {
            color = 0xffcc00;
            geo = new THREE.DodecahedronGeometry(0.45);
        }

        const mat = new THREE.MeshBasicMaterial({ color, wireframe: false });
        const mesh = new THREE.Mesh(geo, mat);
        mesh.position.set(x, 0.7, z);
        this.scene.add(mesh);

        this.items.push({
            type,
            mesh,
            position: mesh.position,
            rotationSpeed: 2.0 + Math.random(),
            bobPhase: Math.random() * Math.PI,
            lifetime: 25.0,
        });
    }

    update(dt, player, arenaRadius) {
        this.spawnTimer += dt;
        if (this.spawnTimer >= this.spawnInterval && this.items.length < 8) {
            this.spawnTimer = 0;
            const angle = Math.random() * Math.PI * 2;
            const r = Math.random() * (arenaRadius * 0.7);
            const types = ['health', 'energy', 'multiplier', 'health'];
            const chosen = types[Math.floor(Math.random() * types.length)];
            this.spawn(chosen, Math.cos(angle) * r, Math.sin(angle) * r);
        }

        const pPos = player.position;

        for (let i = this.items.length - 1; i >= 0; i--) {
            const item = this.items[i];
            item.lifetime -= dt;

            // Rotation & Hover Bobbing
            item.mesh.rotation.y += item.rotationSpeed * dt;
            item.mesh.rotation.x += (item.rotationSpeed * 0.5) * dt;
            item.mesh.position.y = 0.7 + Math.sin(performance.now() * 0.005 + item.bobPhase) * 0.2;

            // Magnetic Attraction towards player within 6 units
            const dist = item.position.distanceTo(pPos);
            if (dist < 6.0) {
                const pullDir = pPos.clone().sub(item.position).normalize();
                item.position.addScaledVector(pullDir, (12 - dist) * dt);
            }

            // Collection Check
            if (dist < 1.8) {
                this.collect(item, player);
                this.scene.remove(item.mesh);
                this.items.splice(i, 1);
                continue;
            }

            // Expiry Check
            if (item.lifetime <= 0) {
                this.scene.remove(item.mesh);
                this.items.splice(i, 1);
            }
        }
    }

    collect(item, player) {
        if (this.audio) this.audio.pickup();

        switch (item.type) {
            case 'health':
                player.heal(35);
                break;
            case 'energy':
                player.restoreEnergy(50);
                break;
            case 'multiplier':
                player.bonusMultiplier = (player.bonusMultiplier || 1) + 1;
                break;
            case 'overcharge':
                player.damageMult = 2.0;
                setTimeout(() => { player.damageMult = 1.0; }, 6000);
                break;
        }
    }

    clear() {
        this.items.forEach(item => this.scene.remove(item.mesh));
        this.items = [];
    }
}
