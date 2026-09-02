/**
 * VOIDSTRIKE ARENA — AI Opponents & State Machine
 * Multi-tiered tactical AI: Scout Drone, Assault Mech, Enforcer Heavy with Easy/Normal/Hard behaviors.
 */

import * as THREE from '/assets/vendor/three/three.module.js';

export class AIManager {
    constructor(scene, weapons, audio, difficulty = 'normal') {
        this.scene = scene;
        this.weapons = weapons;
        this.audio = audio;
        this.difficulty = difficulty;
        this.enemies = [];

        this.initDifficulty(difficulty);
    }

    initDifficulty(diff) {
        switch (diff) {
            case 'hard':
                this.aimAccuracy = 0.95;
                this.reactionTime = 0.2;
                this.speedMult = 1.2;
                this.healthMult = 1.25;
                this.damageMult = 1.3;
                this.fireIntervalBase = 1.4;
                break;

            case 'easy':
                this.aimAccuracy = 0.65;
                this.reactionTime = 0.7;
                this.speedMult = 0.8;
                this.healthMult = 0.8;
                this.damageMult = 0.7;
                this.fireIntervalBase = 2.4;
                break;

            case 'normal':
            default:
                this.aimAccuracy = 0.85;
                this.reactionTime = 0.4;
                this.speedMult = 1.0;
                this.healthMult = 1.0;
                this.damageMult = 1.0;
                this.fireIntervalBase = 1.8;
                break;
        }
    }

    spawn(type, x, z) {
        let health = 45 * this.healthMult;
        let speed = 18 * this.speedMult;
        let radius = 1.1;
        let color = 0xff2a5f;
        let points = 250;

        let geo;
        if (type === 'heavy') {
            health = 140 * this.healthMult;
            speed = 10 * this.speedMult;
            radius = 1.8;
            color = 0xff3b00;
            points = 600;
            geo = new THREE.DodecahedronGeometry(radius, 1);
        } else if (type === 'assault') {
            health = 80 * this.healthMult;
            speed = 14 * this.speedMult;
            radius = 1.4;
            color = 0xff9900;
            points = 400;
            geo = new THREE.BoxGeometry(radius * 1.5, 0.8, radius * 1.5);
        } else {
            // scout
            geo = new THREE.TetrahedronGeometry(radius, 1);
        }

        const mat = new THREE.MeshStandardMaterial({
            color: 0x161c28,
            roughness: 0.3,
            metalness: 0.8,
        });

        const group = new THREE.Group();
        const core = new THREE.Mesh(geo, mat);
        group.add(core);

        // Glowing red eye/core
        const eyeGeo = new THREE.SphereGeometry(radius * 0.35, 8, 8);
        const eyeMat = new THREE.MeshBasicMaterial({ color });
        const eye = new THREE.Mesh(eyeGeo, eyeMat);
        eye.position.set(0, 0, radius * 0.6);
        group.add(eye);

        group.position.set(x, 0.7, z);
        this.scene.add(group);

        this.enemies.push({
            type,
            mesh: group,
            position: group.position,
            velocity: new THREE.Vector3(),
            rotation: 0,
            health,
            maxHealth: health,
            speed,
            radius,
            points,
            state: 'PURSUE', // PATROL, PURSUE, ATTACK, RETREAT
            fireCooldown: Math.random() * this.fireIntervalBase,
            strafeDir: Math.random() > 0.5 ? 1 : -1,
            strafeTimer: 2.0,
            patrolTarget: new THREE.Vector3(),
            takeDamage: function(amt) {
                this.health -= amt;
                // Flash white on hit
                mat.color.setHex(0xffffff);
                setTimeout(() => { if (mat) mat.color.setHex(0x161c28); }, 60);
            }
        });
    }

    update(dt, player, arena, pickups) {
        const pPos = player.position;

        for (let i = this.enemies.length - 1; i >= 0; i--) {
            const e = this.enemies[i];

            // 1. Check Death
            if (e.health <= 0) {
                this.weapons.spawnExplosion(e.position, 0xff2a5f, 25);
                if (this.audio) this.audio.explosion('medium');
                this.scene.remove(e.mesh);
                this.enemies.splice(i, 1);

                // Notify game engine
                if (this.onEnemyKilled) {
                    this.onEnemyKilled(e);
                }
                continue;
            }

            // 2. FSM State Transitions
            const distToPlayer = e.position.distanceTo(pPos);

            // If health < 30% and nearby health pickup exists, state becomes RETREAT
            if (e.health < (e.maxHealth * 0.3) && pickups.items.length > 0) {
                e.state = 'RETREAT';
            } else if (distToPlayer < 24) {
                e.state = 'ATTACK';
            } else {
                e.state = 'PURSUE';
            }

            // 3. Execute State Behavior
            let moveDir = new THREE.Vector3();

            if (e.state === 'RETREAT') {
                // Find nearest health pickup
                let nearestPickup = null;
                let minDist = Infinity;
                pickups.items.forEach(pk => {
                    const d = e.position.distanceTo(pk.position);
                    if (d < minDist) {
                        minDist = d;
                        nearestPickup = pk;
                    }
                });

                if (nearestPickup) {
                    moveDir = nearestPickup.position.clone().sub(e.position).setY(0).normalize();
                } else {
                    // Back away from player
                    moveDir = e.position.clone().sub(pPos).setY(0).normalize();
                }

            } else if (e.state === 'ATTACK') {
                // Strafe around player while keeping distance
                e.strafeTimer -= dt;
                if (e.strafeTimer <= 0) {
                    e.strafeDir *= -1;
                    e.strafeTimer = 1.5 + Math.random();
                }

                const toPlayer = pPos.clone().sub(e.position).setY(0).normalize();
                const strafe = new THREE.Vector3(-toPlayer.z * e.strafeDir, 0, toPlayer.x * e.strafeDir);

                if (distToPlayer > 16) {
                    moveDir = toPlayer.multiplyScalar(0.6).add(strafe.multiplyScalar(0.8)).normalize();
                } else if (distToPlayer < 8) {
                    moveDir = toPlayer.clone().negate().multiplyScalar(0.7).add(strafe.multiplyScalar(0.7)).normalize();
                } else {
                    moveDir = strafe;
                }

                // Fire weapon at player
                e.fireCooldown -= dt;
                if (e.fireCooldown <= 0) {
                    this.fireAtPlayer(e, player);
                    e.fireCooldown = this.fireIntervalBase * (0.8 + Math.random() * 0.4);
                }

            } else {
                // PURSUE: move directly toward player
                moveDir = pPos.clone().sub(e.position).setY(0).normalize();
            }

            // 4. Obstacle Avoidance Vector
            arena.obstacles.forEach(obs => {
                const d = Math.hypot(e.position.x - obs.x, e.position.z - obs.z);
                if (d < (obs.radius + e.radius + 3.0)) {
                    const avoid = new THREE.Vector3(e.position.x - obs.x, 0, e.position.z - obs.z).normalize();
                    moveDir.add(avoid.multiplyScalar(2.0)).normalize();
                }
            });

            // 5. Enemy Flocking / Separation (avoid clustering together)
            this.enemies.forEach(other => {
                if (other !== e) {
                    const sepDist = e.position.distanceTo(other.position);
                    if (sepDist < 3.2 && sepDist > 0.01) {
                        const push = e.position.clone().sub(other.position).setY(0).normalize();
                        moveDir.add(push.multiplyScalar(1.2)).normalize();
                    }
                }
            });

            // 6. Apply Movement
            e.velocity.x += moveDir.x * e.speed * 4 * dt;
            e.velocity.z += moveDir.z * e.speed * 4 * dt;
            e.velocity.multiplyScalar(0.88); // friction

            e.position.addScaledVector(e.velocity, dt);

            // Clamp to arena circle
            const r = Math.hypot(e.position.x, e.position.z);
            if (r > (arena.radius - 2)) {
                const ang = Math.atan2(e.position.z, e.position.x);
                e.position.x = Math.cos(ang) * (arena.radius - 2);
                e.position.z = Math.sin(ang) * (arena.radius - 2);
            }

            // Face direction of player
            const dx = pPos.x - e.position.x;
            const dz = pPos.z - e.position.z;
            e.mesh.rotation.y = Math.atan2(dx, dz);

            // Hover bob
            e.mesh.position.y = 0.7 + Math.sin(performance.now() * 0.005 + i) * 0.1;
        }
    }

    fireAtPlayer(enemy, player) {
        // Predictive Aim based on player velocity and difficulty
        const dist = enemy.position.distanceTo(player.position);
        const bulletSpeed = 22;
        const timeToHit = dist / bulletSpeed;

        let targetPos = player.position.clone().addScaledVector(player.velocity, timeToHit * (this.aimAccuracy * 0.8));

        // Add inaccuracy jitter based on difficulty
        const jitter = (1.0 - this.aimAccuracy) * 4.0;
        targetPos.x += (Math.random() - 0.5) * jitter;
        targetPos.z += (Math.random() - 0.5) * jitter;

        const damage = enemy.type === 'heavy' ? 24 * this.damageMult : 12 * this.damageMult;
        this.weapons.fireEnemyWeapon(enemy.position, targetPos, bulletSpeed, damage);
    }

    clear() {
        this.enemies.forEach(e => this.scene.remove(e.mesh));
        this.enemies = [];
    }
}
