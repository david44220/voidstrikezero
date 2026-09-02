/**
 * VOIDSTRIKE ARENA — Weapons & Projectile Engine
 * Projectile object pooling, laser raycasts, and impact effect particle bursts.
 */

import * as THREE from '../vendor/three/three.module.js';

export class WeaponsManager {
    constructor(scene, audio) {
        this.scene = scene;
        this.audio = audio;

        this.playerBullets = [];
        this.enemyBullets = [];
        this.particles = [];

        this.fireCooldown = 0;
        this.shotsFired = 0;
        this.shotsHit = 0;
        this.totalDamageDealt = 0;

        // Shared geometries and materials for pooling
        this.plasmaGeo = new THREE.SphereGeometry(0.2, 6, 6);
        this.plasmaMat = new THREE.MeshBasicMaterial({ color: 0x00f0ff });

        this.heavyGeo = new THREE.SphereGeometry(0.45, 8, 8);
        this.heavyMat = new THREE.MeshBasicMaterial({ color: 0xff3b00 });

        this.phaseGeo = new THREE.CylinderGeometry(0.1, 0.1, 3.0, 6);
        this.phaseGeo.rotateX(Math.PI / 2);
        this.phaseMat = new THREE.MeshBasicMaterial({ color: 0xbf00ff });

        this.enemyGeo = new THREE.SphereGeometry(0.22, 6, 6);
        this.enemyMat = new THREE.MeshBasicMaterial({ color: 0xff2a5f });
    }

    firePlayerWeapon(vehicle) {
        if (this.fireCooldown > 0) return;

        const pos = vehicle.position;
        const rot = vehicle.rotation;
        const vType = vehicle.type;

        let baseRate = 0.2;
        let damage = 20;

        if (vType === 'striker') {
            baseRate = 0.16;
            damage = 18;
            if (vehicle.specialActiveTimer > 0) {
                baseRate *= 0.55; // Overdrive fire rate boost
            }
            this.spawnBullet(pos, rot, 42, damage, 'plasma', -0.9);
            this.spawnBullet(pos, rot, 42, damage, 'plasma', 0.9);
            if (this.audio) this.audio.laser('plasma');
        } else if (vType === 'titan') {
            baseRate = 0.55;
            damage = 60;
            this.spawnBullet(pos, rot, 30, damage, 'heavy', 0, 3.5); // AoE radius 3.5
            if (this.audio) this.audio.laser('heavy');
        } else if (vType === 'phantom') {
            baseRate = 0.28;
            damage = 32;
            this.spawnBullet(pos, rot, 48, damage, 'phase', 0);
            if (this.audio) this.audio.laser('phase');
        }

        this.fireCooldown = baseRate;
        this.shotsFired++;
    }

    spawnBullet(pos, rot, speed, damage, type, lateralOffset = 0, aoe = 0) {
        let geo = this.plasmaGeo;
        let mat = this.plasmaMat;

        if (type === 'heavy') {
            geo = this.heavyGeo;
            mat = this.heavyMat;
        } else if (type === 'phase') {
            geo = this.phaseGeo;
            mat = this.phaseMat;
        }

        const mesh = new THREE.Mesh(geo, mat);

        const forward = new THREE.Vector3(Math.sin(rot), 0, Math.cos(rot));
        const right = new THREE.Vector3(Math.cos(rot), 0, -Math.sin(rot));

        mesh.position.copy(pos).add(right.clone().multiplyScalar(lateralOffset));
        mesh.position.y = 0.6;
        mesh.rotation.y = rot;

        this.scene.add(mesh);

        this.playerBullets.push({
            mesh,
            velocity: forward.multiplyScalar(speed),
            damage,
            type,
            aoe,
            lifetime: 1.8,
        });
    }

    fireEnemyWeapon(enemyPos, targetPos, speed = 22, damage = 14) {
        const mesh = new THREE.Mesh(this.enemyGeo, this.enemyMat);
        mesh.position.copy(enemyPos);
        mesh.position.y = 0.6;
        this.scene.add(mesh);

        const dir = targetPos.clone().sub(enemyPos).setY(0).normalize();

        this.enemyBullets.push({
            mesh,
            velocity: dir.multiplyScalar(speed),
            damage,
            lifetime: 2.2,
        });
    }

    update(dt, enemies, player, arena) {
        if (this.fireCooldown > 0) this.fireCooldown -= dt;

        // 1. Update Player Bullets
        for (let i = this.playerBullets.length - 1; i >= 0; i--) {
            const b = this.playerBullets[i];
            b.lifetime -= dt;
            b.mesh.position.addScaledVector(b.velocity, dt);

            let hit = false;

            // Check collision with enemies
            for (let j = 0; j < enemies.length; j++) {
                const enemy = enemies[j];
                const dist = b.mesh.position.distanceTo(enemy.position);

                if (dist < (enemy.radius + 0.3)) {
                    hit = true;
                    this.shotsHit++;
                    this.totalDamageDealt += b.damage;

                    if (b.aoe > 0) {
                        // Heavy explosive splash damage
                        enemies.forEach(e => {
                            const splashDist = b.mesh.position.distanceTo(e.position);
                            if (splashDist < b.aoe) {
                                e.takeDamage(b.damage * (1 - (splashDist / b.aoe)));
                            }
                        });
                        this.spawnExplosion(b.mesh.position, 0xff3b00, 24);
                        if (this.audio) this.audio.explosion('medium');
                    } else {
                        enemy.takeDamage(b.damage);
                        this.spawnHitSparks(b.mesh.position, 0x00f0ff, 8);
                    }
                    break;
                }
            }

            // Check collision with arena obstacles
            if (!hit) {
                for (let k = 0; k < arena.obstacles.length; k++) {
                    const obs = arena.obstacles[k];
                    const d = Math.hypot(b.mesh.position.x - obs.x, b.mesh.position.z - obs.z);
                    if (d < obs.radius) {
                        hit = true;
                        this.spawnHitSparks(b.mesh.position, 0x88bbff, 6);
                        break;
                    }
                }
            }

            if (hit || b.lifetime <= 0) {
                this.scene.remove(b.mesh);
                this.playerBullets.splice(i, 1);
            }
        }

        // 2. Update Enemy Bullets
        for (let i = this.enemyBullets.length - 1; i >= 0; i--) {
            const eb = this.enemyBullets[i];
            eb.lifetime -= dt;
            eb.mesh.position.addScaledVector(eb.velocity, dt);

            let hit = false;
            const distToPlayer = eb.mesh.position.distanceTo(player.position);

            if (distToPlayer < 1.4) {
                hit = true;
                player.takeDamage(eb.damage);
                this.spawnHitSparks(eb.mesh.position, 0xff2a5f, 8);
            }

            if (!hit) {
                for (let k = 0; k < arena.obstacles.length; k++) {
                    const obs = arena.obstacles[k];
                    const d = Math.hypot(eb.mesh.position.x - obs.x, eb.mesh.position.z - obs.z);
                    if (d < obs.radius) {
                        hit = true;
                        this.spawnHitSparks(eb.mesh.position, 0xff5577, 4);
                        break;
                    }
                }
            }

            if (hit || eb.lifetime <= 0) {
                this.scene.remove(eb.mesh);
                this.enemyBullets.splice(i, 1);
            }
        }

        // 3. Update Visual Particle Sparks
        for (let i = this.particles.length - 1; i >= 0; i--) {
            const p = this.particles[i];
            p.life -= dt;
            p.mesh.position.addScaledVector(p.velocity, dt);
            p.mesh.scale.multiplyScalar(0.92);

            if (p.life <= 0) {
                this.scene.remove(p.mesh);
                this.particles.splice(i, 1);
            }
        }
    }

    spawnHitSparks(pos, color, count = 8) {
        const geo = new THREE.BoxGeometry(0.08, 0.08, 0.08);
        const mat = new THREE.MeshBasicMaterial({ color });

        for (let i = 0; i < count; i++) {
            const mesh = new THREE.Mesh(geo, mat);
            mesh.position.copy(pos);
            this.scene.add(mesh);

            const vel = new THREE.Vector3(
                (Math.random() - 0.5) * 12,
                Math.random() * 8,
                (Math.random() - 0.5) * 12
            );

            this.particles.push({ mesh, velocity: vel, life: 0.25 });
        }
    }

    spawnExplosion(pos, color, count = 20) {
        const geo = new THREE.DodecahedronGeometry(0.18);
        const mat = new THREE.MeshBasicMaterial({ color });

        for (let i = 0; i < count; i++) {
            const mesh = new THREE.Mesh(geo, mat);
            mesh.position.copy(pos);
            this.scene.add(mesh);

            const vel = new THREE.Vector3(
                (Math.random() - 0.5) * 22,
                Math.random() * 14,
                (Math.random() - 0.5) * 22
            );

            this.particles.push({ mesh, velocity: vel, life: 0.45 });
        }
    }

    clear() {
        this.playerBullets.forEach(b => this.scene.remove(b.mesh));
        this.enemyBullets.forEach(eb => this.scene.remove(eb.mesh));
        this.particles.forEach(p => this.scene.remove(p.mesh));
        this.playerBullets = [];
        this.enemyBullets = [];
        this.particles = [];
    }
}
