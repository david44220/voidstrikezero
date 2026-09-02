/**
 * VOIDSTRIKE ARENA — Vehicle Classes & Physics Controller
 * Procedurally assembled 3D combat chassis: Striker, Titan, Phantom.
 */

import * as THREE from '/assets/vendor/three/three.module.js';

export class Vehicle {
    constructor(type = 'striker', scene, audio) {
        this.type = type;
        this.scene = scene;
        this.audio = audio;

        this.position = new THREE.Vector3(0, 0.6, 0);
        this.velocity = new THREE.Vector3();
        this.rotation = 0; // facing angle
        this.rollAngle = 0; // banking roll

        // Load chassis parameters
        this.initParameters(type);

        this.health = this.maxHealth;
        this.energy = this.maxEnergy;

        this.dashCooldownTimer = 0;
        this.specialCooldownTimer = 0;
        this.specialActiveTimer = 0;
        this.dashActiveTimer = 0;
        this.isInvulnerable = false;

        this.mesh = this.buildMesh(type);
        this.scene.add(this.mesh);

        // Shield Mesh (for Titan Kinetic Dome & Overdrive glow)
        this.shieldMesh = this.buildShieldMesh();
        this.mesh.add(this.shieldMesh);
        this.shieldMesh.visible = false;
    }

    initParameters(type) {
        switch (type) {
            case 'titan':
                this.maxHealth = 160;
                this.maxEnergy = 120;
                this.speed = 18;
                this.accel = 55;
                this.drag = 3.5;
                this.energyRegen = 15;
                this.dashCost = 45;
                this.dashCooldown = 2.2;
                this.specialCooldown = 18.0;
                this.specialDuration = 3.5;
                this.specialCost = 70;
                this.damageMult = 1.0;
                this.color = 0xff3b00;
                break;

            case 'phantom':
                this.maxHealth = 100;
                this.maxEnergy = 110;
                this.speed = 24;
                this.accel = 75;
                this.drag = 3.8;
                this.energyRegen = 22;
                this.dashCost = 25;
                this.dashCooldown = 0.9;
                this.specialCooldown = 12.0;
                this.specialDuration = 2.2;
                this.specialCost = 50;
                this.damageMult = 1.0;
                this.color = 0xbf00ff;
                break;

            case 'striker':
            default:
                this.maxHealth = 80;
                this.maxEnergy = 100;
                this.speed = 28;
                this.accel = 90;
                this.drag = 4.0;
                this.energyRegen = 20;
                this.dashCost = 30;
                this.dashCooldown = 1.2;
                this.specialCooldown = 14.0;
                this.specialDuration = 4.0;
                this.specialCost = 60;
                this.damageMult = 1.0;
                this.color = 0x00f0ff;
                break;
        }
    }

    buildMesh(type) {
        const group = new THREE.Group();

        if (type === 'titan') {
            // Titan: Heavy blocky hull, thick plating, central cannon
            const bodyMat = new THREE.MeshStandardMaterial({
                color: 0x1a1e28,
                metalness: 0.85,
                roughness: 0.3,
            });
            const glowMat = new THREE.MeshBasicMaterial({ color: 0xff3b00 });

            // Main Hull
            const hullGeo = new THREE.BoxGeometry(2.2, 0.7, 3.2);
            const hull = new THREE.Mesh(hullGeo, bodyMat);
            group.add(hull);

            // Side Armor Plates
            const plateGeo = new THREE.BoxGeometry(0.5, 0.6, 2.6);
            const leftPlate = new THREE.Mesh(plateGeo, bodyMat);
            leftPlate.position.set(-1.3, 0.1, 0);
            const rightPlate = new THREE.Mesh(plateGeo, bodyMat);
            rightPlate.position.set(1.3, 0.1, 0);
            group.add(leftPlate, rightPlate);

            // Heavy Cannon Barrel
            const cannonGeo = new THREE.CylinderGeometry(0.25, 0.3, 2.4, 8);
            cannonGeo.rotateX(Math.PI / 2);
            const cannon = new THREE.Mesh(cannonGeo, bodyMat);
            cannon.position.set(0, 0.2, 1.4);
            group.add(cannon);

            // Engine Thrusters
            const thrusterGeo = new THREE.CylinderGeometry(0.3, 0.4, 0.6, 8);
            thrusterGeo.rotateX(Math.PI / 2);
            const leftThruster = new THREE.Mesh(thrusterGeo, glowMat);
            leftThruster.position.set(-0.8, 0, -1.7);
            const rightThruster = new THREE.Mesh(thrusterGeo, glowMat);
            rightThruster.position.set(0.8, 0, -1.7);
            group.add(leftThruster, rightThruster);

        } else if (type === 'phantom') {
            // Phantom: Sleek twin-fork fuselage with glowing energy core
            const bodyMat = new THREE.MeshStandardMaterial({
                color: 0x121420,
                metalness: 0.9,
                roughness: 0.2,
            });
            const glowMat = new THREE.MeshBasicMaterial({ color: 0xbf00ff });

            // Twin hulls
            const forkGeo = new THREE.ConeGeometry(0.45, 3.6, 5);
            forkGeo.rotateX(Math.PI / 2);

            const leftFork = new THREE.Mesh(forkGeo, bodyMat);
            leftFork.position.set(-0.8, 0, 0.2);
            const rightFork = new THREE.Mesh(forkGeo, bodyMat);
            rightFork.position.set(0.8, 0, 0.2);
            group.add(leftFork, rightFork);

            // Center Energy Crystal
            const coreGeo = new THREE.OctahedronGeometry(0.55);
            const core = new THREE.Mesh(coreGeo, glowMat);
            core.position.set(0, 0.1, -0.3);
            group.add(core);

            // Wings
            const wingGeo = new THREE.BoxGeometry(2.8, 0.08, 1.2);
            const wing = new THREE.Mesh(wingGeo, bodyMat);
            wing.position.set(0, 0, -0.6);
            group.add(wing);

        } else {
            // Striker: Sharp aerodynamic needle/arrowhead with cyan glowing vents
            const bodyMat = new THREE.MeshStandardMaterial({
                color: 0x141a29,
                metalness: 0.8,
                roughness: 0.25,
            });
            const glowMat = new THREE.MeshBasicMaterial({ color: 0x00f0ff });

            // Central Fuselage
            const fuseGeo = new THREE.ConeGeometry(0.65, 3.4, 4);
            fuseGeo.rotateX(Math.PI / 2);
            const fuse = new THREE.Mesh(fuseGeo, bodyMat);
            fuse.scale.set(1.1, 0.6, 1);
            group.add(fuse);

            // Swept Wings
            const wingGeo = new THREE.BoxGeometry(3.2, 0.08, 1.4);
            const wing = new THREE.Mesh(wingGeo, bodyMat);
            wing.position.set(0, 0.05, -0.4);
            group.add(wing);

            // Wingtip Blaster Cannons
            const gunGeo = new THREE.CylinderGeometry(0.08, 0.08, 1.2, 6);
            gunGeo.rotateX(Math.PI / 2);
            const leftGun = new THREE.Mesh(gunGeo, glowMat);
            leftGun.position.set(-1.4, 0.05, 0.2);
            const rightGun = new THREE.Mesh(gunGeo, glowMat);
            rightGun.position.set(1.4, 0.05, 0.2);
            group.add(leftGun, rightGun);

            // Rear Thruster
            const thrusterGeo = new THREE.CylinderGeometry(0.2, 0.35, 0.6, 8);
            thrusterGeo.rotateX(Math.PI / 2);
            const thruster = new THREE.Mesh(thrusterGeo, glowMat);
            thruster.position.set(0, 0, -1.6);
            group.add(thruster);
        }

        group.castShadow = true;
        group.receiveShadow = true;
        return group;
    }

    buildShieldMesh() {
        const geo = new THREE.SphereGeometry(2.6, 16, 12);
        const mat = new THREE.MeshBasicMaterial({
            color: this.color,
            wireframe: true,
            transparent: true,
            opacity: 0.45,
        });
        return new THREE.Mesh(geo, mat);
    }

    update(dt, input, arenaRadius) {
        // Cooldowns countdown
        if (this.dashCooldownTimer > 0) this.dashCooldownTimer -= dt;
        if (this.specialCooldownTimer > 0) this.specialCooldownTimer -= dt;

        // Active timers
        if (this.dashActiveTimer > 0) {
            this.dashActiveTimer -= dt;
            if (this.dashActiveTimer <= 0) {
                this.isInvulnerable = false;
            }
        }

        if (this.specialActiveTimer > 0) {
            this.specialActiveTimer -= dt;
            if (this.specialActiveTimer <= 0) {
                this.endSpecial();
            }
        }

        // Capacitor Energy Regeneration
        this.energy = Math.min(this.maxEnergy, this.energy + (this.energyRegen * dt));

        // Movement Physics
        let targetSpeed = this.speed;
        let accel = this.accel;

        // Boost modifier
        if (input.boost && this.energy > 15 * dt) {
            targetSpeed *= 1.4;
            accel *= 1.3;
            this.energy -= 18 * dt;
        }

        // Striker Overdrive active boost
        if (this.specialActiveTimer > 0 && this.type === 'striker') {
            targetSpeed *= 1.35;
        }

        const inputDir = new THREE.Vector3(input.moveX, 0, input.moveZ);
        if (inputDir.lengthSq() > 0.01) {
            inputDir.normalize();
            this.velocity.x += inputDir.x * accel * dt;
            this.velocity.z += inputDir.z * accel * dt;

            // Clamp max velocity
            const currentSpeed = Math.hypot(this.velocity.x, this.velocity.z);
            if (currentSpeed > targetSpeed) {
                const scale = targetSpeed / currentSpeed;
                this.velocity.x *= scale;
                this.velocity.z *= scale;
            }
        } else {
            // Apply drag when no input
            this.velocity.x -= this.velocity.x * this.drag * dt;
            this.velocity.z -= this.velocity.z * this.drag * dt;
        }

        // Apply Velocity to Position
        this.position.x += this.velocity.x * dt;
        this.position.z += this.velocity.z * dt;

        // Clamp to Arena Perimeter Circle
        const distFromCenter = Math.hypot(this.position.x, this.position.z);
        const maxR = arenaRadius - 1.5;
        if (distFromCenter > maxR) {
            const angle = Math.atan2(this.position.z, this.position.x);
            this.position.x = Math.cos(angle) * maxR;
            this.position.z = Math.sin(angle) * maxR;
            // Damp velocity on boundary collision
            this.velocity.multiplyScalar(0.5);
        }

        // Aiming & Rotation
        const dx = input.aimPoint.x - this.position.x;
        const dz = input.aimPoint.z - this.position.z;
        if (Math.hypot(dx, dz) > 0.5) {
            this.rotation = Math.atan2(dx, dz);
        }

        // Banking roll effect when turning
        const targetRoll = -this.velocity.x * 0.03;
        this.rollAngle += (targetRoll - this.rollAngle) * 8 * dt;

        // Update 3D Mesh
        this.mesh.position.copy(this.position);
        this.mesh.rotation.set(0, this.rotation, this.rollAngle);

        // Hover bobbing motion
        this.mesh.position.y = 0.6 + Math.sin(performance.now() * 0.004) * 0.08;

        // Handle Abilities
        if (input.dash) this.triggerDash(inputDir);
        if (input.special) this.triggerSpecial();
    }

    triggerDash(inputDir) {
        if (this.dashCooldownTimer > 0 || this.energy < this.dashCost) return;

        this.energy -= this.dashCost;
        this.dashCooldownTimer = this.dashCooldown;
        this.dashActiveTimer = 0.35;
        this.isInvulnerable = true;

        // Dash impulse in movement direction or facing direction
        let dashDir = inputDir.lengthSq() > 0.1 ? inputDir : new THREE.Vector3(Math.sin(this.rotation), 0, Math.cos(this.rotation));
        this.velocity.add(dashDir.clone().multiplyScalar(this.speed * 2.2));

        if (this.audio) this.audio.dash();
    }

    triggerSpecial() {
        if (this.specialCooldownTimer > 0 || this.energy < this.specialCost) return;

        this.energy -= this.specialCost;
        this.specialCooldownTimer = this.specialCooldown;
        this.specialActiveTimer = this.specialDuration;
        this.specialsUsed = (this.specialsUsed || 0) + 1;

        if (this.type === 'titan') {
            // Kinetic Dome: Invulnerable spherical forcefield
            this.isInvulnerable = true;
            this.shieldMesh.visible = true;
            if (this.audio) this.audio.shield();
        } else if (this.type === 'phantom') {
            // Phase Shift: Subspace blink forward
            this.isInvulnerable = true;
            this.shieldMesh.visible = true;
            this.phaseShiftsCount = (this.phaseShiftsCount || 0) + 1;
            const blinkVec = new THREE.Vector3(Math.sin(this.rotation), 0, Math.cos(this.rotation)).multiplyScalar(14);
            this.position.add(blinkVec);
            if (this.audio) this.audio.dash();
        } else if (this.type === 'striker') {
            // Overdrive: Supercharged weapons
            this.shieldMesh.visible = true;
            if (this.audio) this.audio.shield();
        }
    }

    endSpecial() {
        if (this.type === 'titan' || this.type === 'phantom') {
            this.isInvulnerable = false;
        }
        this.shieldMesh.visible = false;
    }

    takeDamage(amount) {
        if (this.isInvulnerable) {
            if (this.type === 'titan' && this.specialActiveTimer > 0) {
                this.damageAbsorbed = (this.damageAbsorbed || 0) + amount;
            }
            return 0; // Completely absorbed
        }

        this.health = Math.max(0, this.health - amount);
        if (this.audio) this.audio.hit();

        // Visual damage flash
        this.flashDamage();

        return amount;
    }

    dispose() {
        this.mesh.traverse((child) => {
            if (child.isMesh) {
                if (child.geometry) child.geometry.dispose();
                if (child.material) {
                    if (Array.isArray(child.material)) {
                        child.material.forEach(m => m.dispose());
                    } else {
                        child.material.dispose();
                    }
                }
            }
        });
        this.scene.remove(this.mesh);
    }

    flashDamage() {
        this.mesh.traverse((child) => {
            if (child.isMesh && child.material) {
                const origColor = child.material.color?.getHex();
                if (origColor !== undefined) {
                    child.material.color.setHex(0xff0044);
                    setTimeout(() => {
                        if (child.material) child.material.color.setHex(origColor);
                    }, 80);
                }
            }
        });
    }

    heal(amount) {
        this.health = Math.min(this.maxHealth, this.health + amount);
    }

    restoreEnergy(amount) {
        this.energy = Math.min(this.maxEnergy, this.energy + amount);
    }

    destroy() {
        this.scene.remove(this.mesh);
    }
}
