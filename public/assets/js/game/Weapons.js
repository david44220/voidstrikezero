/**
 * VOIDSTRIKE ARENA — High-Performance Projectile Engine
 * Real Object Pooling for Projectiles and Particle Sparks with zero runtime GPU allocations.
 */

import * as THREE from '/assets/vendor/three/three.module.js';

export class WeaponsManager {
    constructor(scene, audio) {
        this.scene = scene;
        this.audio = audio;

        this.playerBullets = [];
        this.enemyBullets = [];
        this.particles = [];

        // Inactive Pools for reuse
        this.bulletPool = [];
        this.particlePool = [];

        this.fireCooldown = 0;
        this.shotsFired = 0;
        this.shotsHit = 0;
        this.totalDamageDealt = 0;

        // Shared geometries & materials to eliminate memory leaks
        this.plasmaGeo = new THREE.SphereGeometry(0.2, 6, 6);
        this.plasmaMat = new THREE.MeshBasicMaterial({ color: 0x00f0ff });

        this.heavyGeo = new THREE.SphereGeometry(0.45, 8, 8);
        this.heavyMat = new THREE.MeshBasicMaterial({ color: 0xff3b00 });

        this.phaseGeo = new THREE.CylinderGeometry(0.1, 0.1, 3.0, 6);
        this.phaseGeo.rotateX(Math.PI / 2);
        this.phaseMat = new THREE.MeshBasicMaterial({ color: 0xbf00ff });

        this.enemyGeo = new THREE.SphereGeometry(0.22, 6, 6);
        this.enemyMat = new THREE.MeshBasicMaterial({ color: 0xff2a5f });

        this.sparkGeo = new THREE.BoxGeometry(0.08, 0.08, 0.08);
        this.sparkMatCyan = new THREE.MeshBasicMaterial({ color: 0x00f0ff });
        this.sparkMatOrange = new THREE.MeshBasicMaterial({ color: 0xff3b00 });
        this.sparkMatRed = new THREE.MeshBasicMaterial({ color: 0xff2a5f });

        this.explosionGeo = new THREE.DodecahedronGeometry(0.18);
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
            this.spawnBullet(pos, rot, 30, damage, 'heavy', 0, 3.5);
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

        let mesh = null;
        // Check inactive bullet pool
        if (this.bulletPool.length > 0) {
            mesh = this.bulletPool.pop();
            mesh.geometry = geo;
            mesh.material = mat;
            mesh.visible = true;
        } else {
            mesh = new THREE.Mesh(geo, mat);
            this.scene.add(mesh);
        }

        const forward = new THREE.Vector3(Math.sin(rot), 0, Math.cos(rot));
        const right = new THREE.Vector3(Math.cos(rot), 0, -Math.sin(rot));

        mesh.position.copy(pos).add(right.clone().multiplyScalar(lateralOffset));
        mesh.position.y = 0.6;
        mesh.rotation.y = rot;

        this.playerBullets.push({
            mesh,
            velocity: forward.multiplyScalar(speed),
            damage,
            type,
            aoe,
            lifetime: 2.2,
        });
    }

    spawnEnemyBullet(pos, targetPos, speed = 22, damage = 12) {
        let mesh = null;
        if (this.bulletPool.length > 0) {
            mesh = this.bulletPool.pop();
            mesh.geometry = this.enemyGeo;
            mesh.material = this.enemyMat;
            mesh.visible = true;
        } else {
            mesh = new THREE.Mesh(this.enemyGeo, this.enemyMat);
            this.scene.add(mesh);
        }

        mesh.position.copy(pos);
        mesh.position.y = 0.6;

        const dir = targetPos.clone().sub(pos);
        dir.y = 0;
        dir.normalize();

        this.enemyBullets.push({
            mesh,
            velocity: dir.multiplyScalar(speed),
            damage,
            lifetime: 3.5,
        });
    }

    update(dt, enemies, player, arena) {
        this.fireCooldown = Math.max(0, this.fireCooldown - dt);

        // 1. Update Player Projectiles
        for (let i = this.playerBullets.length - 1; i >= 0; i--) {
            const b = this.playerBullets[i];
            b.lifetime -= dt;
            b.mesh.position.addScaledVector(b.velocity, dt);

            let hit = false;

            // Check collision with enemies
            for (let j = 0; j < enemies.length; j++) {
                const enemy = enemies[j];
                if (enemy.isDead) continue;

                const dist = b.mesh.position.distanceTo(enemy.position);
                if (dist < (enemy.radius + 0.4)) {
                    hit = true;
                    this.shotsHit++;
                    this.totalDamageDealt += b.damage;

                    if (b.aoe > 0) {
                        for (let e = 0; e < enemies.length; e++) {
                            const oEnemy = enemies[e];
                            if (oEnemy.isDead) continue;
                            const aoeDist = b.mesh.position.distanceTo(oEnemy.position);
                            if (aoeDist < b.aoe) {
                                const falloff = 1 - (aoeDist / b.aoe);
                                oEnemy.takeDamage(Math.round(b.damage * falloff));
                            }
                        }
                        this.spawnExplosion(b.mesh.position, 16);
                        if (this.audio) this.audio.explosion('medium');
                    } else {
                        enemy.takeDamage(b.damage);
                        this.spawnHitSparks(b.mesh.position, this.sparkMatCyan, 6);
                    }
                    break;
                }
            }

            // Check collision with obstacles
            if (!hit) {
                for (let k = 0; k < arena.obstacles.length; k++) {
                    const obs = arena.obstacles[k];
                    const d = Math.hypot(b.mesh.position.x - obs.x, b.mesh.position.z - obs.z);
                    if (d < obs.radius) {
                        hit = true;
                        this.spawnHitSparks(b.mesh.position, this.sparkMatCyan, 4);
                        break;
                    }
                }
            }

            if (hit || b.lifetime <= 0) {
                b.mesh.visible = false;
                this.bulletPool.push(b.mesh);
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
                this.spawnHitSparks(eb.mesh.position, this.sparkMatRed, 6);
            }

            if (!hit) {
                for (let k = 0; k < arena.obstacles.length; k++) {
                    const obs = arena.obstacles[k];
                    const d = Math.hypot(eb.mesh.position.x - obs.x, eb.mesh.position.z - obs.z);
                    if (d < obs.radius) {
                        hit = true;
                        this.spawnHitSparks(eb.mesh.position, this.sparkMatRed, 3);
                        break;
                    }
                }
            }

            if (hit || eb.lifetime <= 0) {
                eb.mesh.visible = false;
                this.bulletPool.push(eb.mesh);
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
                p.mesh.visible = false;
                this.particlePool.push(p.mesh);
                this.particles.splice(i, 1);
            }
        }
    }

    spawnHitSparks(pos, material, count = 6) {
        for (let i = 0; i < count; i++) {
            let mesh = null;
            if (this.particlePool.length > 0) {
                mesh = this.particlePool.pop();
                mesh.geometry = this.sparkGeo;
                mesh.material = material;
                mesh.scale.set(1, 1, 1);
                mesh.visible = true;
            } else {
                mesh = new THREE.Mesh(this.sparkGeo, material);
                this.scene.add(mesh);
            }

            mesh.position.copy(pos);
            const vel = new THREE.Vector3(
                (Math.random() - 0.5) * 12,
                Math.random() * 8,
                (Math.random() - 0.5) * 12
            );
            this.particles.push({ mesh, velocity: vel, life: 0.22 });
        }
    }

    spawnExplosion(pos, count = 16) {
        for (let i = 0; i < count; i++) {
            let mesh = null;
            if (this.particlePool.length > 0) {
                mesh = this.particlePool.pop();
                mesh.geometry = this.explosionGeo;
                mesh.material = this.sparkMatOrange;
                mesh.scale.set(1, 1, 1);
                mesh.visible = true;
            } else {
                mesh = new THREE.Mesh(this.explosionGeo, this.sparkMatOrange);
                this.scene.add(mesh);
            }

            mesh.position.copy(pos);
            const vel = new THREE.Vector3(
                (Math.random() - 0.5) * 20,
                Math.random() * 12,
                (Math.random() - 0.5) * 20
            );
            this.particles.push({ mesh, velocity: vel, life: 0.38 });
        }
    }

    clear() {
        this.playerBullets.forEach(b => { b.mesh.visible = false; this.bulletPool.push(b.mesh); });
        this.enemyBullets.forEach(eb => { eb.mesh.visible = false; this.bulletPool.push(eb.mesh); });
        this.particles.forEach(p => { p.mesh.visible = false; this.particlePool.push(p.mesh); });
        this.playerBullets = [];
        this.enemyBullets = [];
        this.particles = [];
    }

    dispose() {
        this.clear();
        this.bulletPool.forEach(m => { this.scene.remove(m); });
        this.particlePool.forEach(m => { this.scene.remove(m); });
        this.bulletPool = [];
        this.particlePool = [];

        this.plasmaGeo.dispose();
        this.plasmaMat.dispose();
        this.heavyGeo.dispose();
        this.heavyMat.dispose();
        this.phaseGeo.dispose();
        this.phaseMat.dispose();
        this.enemyGeo.dispose();
        this.enemyMat.dispose();
        this.sparkGeo.dispose();
        this.sparkMatCyan.dispose();
        this.sparkMatOrange.dispose();
        this.sparkMatRed.dispose();
        this.explosionGeo.dispose();
    }
}
