/**
 * VOIDSTRIKE ARENA — Centralized Input Abstraction
 * Unifies Keyboard, Mouse Raycasting, Gamepad API, and Virtual Touch.
 * Includes edge-detected gamepad triggers and twin-stick mobile aiming.
 */

import * as THREE from '/assets/vendor/three/three.module.js';

export class InputManager {
    constructor(canvas, camera) {
        this.canvas = canvas;
        this.camera = camera;

        this.moveX = 0;
        this.moveZ = 0;
        this.aimPoint = new THREE.Vector3();
        this.fire = false;
        this.dash = false;
        this.special = false;
        this.boost = false;
        this.pause = false;

        this.keys = {};
        this.mousePos = new THREE.Vector2();
        this.raycaster = new THREE.Raycaster();
        this.groundPlane = new THREE.Plane(new THREE.Vector3(0, 1, 0), 0);

        // Explicit mouse states to prevent sticky input
        this.isMouseDown = false;
        this.isRightMouseDown = false;

        // Mobile touch states
        this.touchMoveX = 0;
        this.touchMoveZ = 0;
        this.touchAimX = 0;
        this.touchAimZ = 0;
        this.touchAimActive = false;
        this.touchFire = false;
        this.touchDash = false;
        this.touchSpecial = false;
        this.touchBoost = false;

        // Gamepad states
        this.gamepadFire = false;
        this.gamepadSpecial = false;
        this.gamepadDash = false;
        this.gamepadBoost = false;
        this.prevGamepadPause = false;

        this.initListeners();
    }

    initListeners() {
        // Keyboard
        window.addEventListener('keydown', (e) => {
            this.keys[e.code] = true;
            if (e.code === 'KeyP' || e.code === 'Escape') {
                this.pause = !this.pause;
            }
        });

        window.addEventListener('keyup', (e) => {
            this.keys[e.code] = false;
        });

        // Mouse Move
        window.addEventListener('mousemove', (e) => {
            const rect = this.canvas.getBoundingClientRect();
            if (rect.width > 0 && rect.height > 0) {
                this.mousePos.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
                this.mousePos.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;
            }
        });

        // Mouse Buttons with explicit edge tracking
        window.addEventListener('mousedown', (e) => {
            if (e.button === 0) this.isMouseDown = true;
            if (e.button === 2) {
                e.preventDefault();
                this.isRightMouseDown = true;
            }
        });

        window.addEventListener('mouseup', (e) => {
            if (e.button === 0) this.isMouseDown = false;
            if (e.button === 2) this.isRightMouseDown = false;
        });

        window.addEventListener('mouseleave', () => {
            this.isMouseDown = false;
            this.isRightMouseDown = false;
        });

        window.addEventListener('contextmenu', (e) => e.preventDefault());
    }

    update(playerRef = null, enemies = []) {
        // 1. Keyboard Movement
        let kx = 0;
        let kz = 0;

        if (this.keys['KeyW'] || this.keys['ArrowUp']) kz -= 1;
        if (this.keys['KeyS'] || this.keys['ArrowDown']) kz += 1;
        if (this.keys['KeyA'] || this.keys['ArrowLeft']) kx -= 1;
        if (this.keys['KeyD'] || this.keys['ArrowRight']) kx += 1;

        // Normalize keyboard diagonal
        const kLen = Math.hypot(kx, kz);
        if (kLen > 0) {
            kx /= kLen;
            kz /= kLen;
        }

        // Combine Keyboard & Touch
        this.moveX = Math.abs(this.touchMoveX) > 0.05 ? this.touchMoveX : kx;
        this.moveZ = Math.abs(this.touchMoveZ) > 0.05 ? this.touchMoveZ : kz;

        // 2. Poll Gamepad API
        this.pollGamepad();

        // 3. Action Triggers: strictly re-evaluated each frame (NO sticky state accumulation)
        this.fire = Boolean(this.keys['KeyJ'] || this.isMouseDown || this.touchFire || this.gamepadFire);
        this.dash = Boolean(this.keys['Space'] || this.touchDash || this.gamepadDash);
        this.special = Boolean(this.keys['KeyE'] || this.keys['KeyK'] || this.isRightMouseDown || this.touchSpecial || this.gamepadSpecial);
        this.boost = Boolean(this.keys['ShiftLeft'] || this.keys['ShiftRight'] || this.touchBoost || this.gamepadBoost);

        // 4. Aiming Resolution: Touch Aimstick -> Mouse Raycast -> Auto-Aim Target
        if (this.touchAimActive && playerRef) {
            // Mobile right stick direction
            this.aimPoint.set(
                playerRef.position.x + this.touchAimX * 20,
                0,
                playerRef.position.z + this.touchAimZ * 20
            );
        } else if (playerRef && !this.isMouseDown && Math.abs(this.touchMoveX) > 0.1 && enemies && enemies.length > 0 && ('ontouchstart' in window)) {
            // Mobile Auto-Aim Assist to nearest hostile drone
            let nearest = null;
            let nearestDistSq = 45 * 45;
            for (const enemy of enemies) {
                if (enemy.isDead) continue;
                const dSq = playerRef.position.distanceToSquared(enemy.position);
                if (dSq < nearestDistSq) {
                    nearestDistSq = dSq;
                    nearest = enemy;
                }
            }
            if (nearest) {
                this.aimPoint.copy(nearest.position);
            } else {
                this.aimPoint.set(
                    playerRef.position.x + this.touchMoveX * 20,
                    0,
                    playerRef.position.z + this.touchMoveZ * 20
                );
            }
        } else {
            // Standard Mouse Raycasting onto ground plane
            this.raycaster.setFromCamera(this.mousePos, this.camera);
            const intersectPoint = new THREE.Vector3();
            if (this.raycaster.ray.intersectPlane(this.groundPlane, intersectPoint)) {
                this.aimPoint.copy(intersectPoint);
            }
        }
    }

    pollGamepad() {
        const gamepads = navigator.getGamepads ? navigator.getGamepads() : [];
        if (!gamepads || !gamepads[0]) {
            this.gamepadFire = false;
            this.gamepadSpecial = false;
            this.gamepadDash = false;
            this.gamepadBoost = false;
            return;
        }
        const gp = gamepads[0];

        // Left stick movement
        const lx = gp.axes[0] || 0;
        const ly = gp.axes[1] || 0;
        if (Math.hypot(lx, ly) > 0.15) {
            this.moveX = lx;
            this.moveZ = ly;
        }

        // Right stick aiming
        const rx = gp.axes[2] || 0;
        const ry = gp.axes[3] || 0;
        if (Math.hypot(rx, ry) > 0.2) {
            this.touchAimActive = true;
            this.touchAimX = rx;
            this.touchAimZ = ry;
        } else {
            this.touchAimActive = false;
        }

        // Action buttons
        this.gamepadFire = Boolean((gp.buttons[0] && gp.buttons[0].pressed) || (gp.buttons[7] && gp.buttons[7].pressed)); // A or R2
        this.gamepadDash = Boolean(gp.buttons[1] && gp.buttons[1].pressed); // B
        this.gamepadSpecial = Boolean((gp.buttons[2] && gp.buttons[2].pressed) || (gp.buttons[5] && gp.buttons[5].pressed)); // X or R1
        this.gamepadBoost = Boolean((gp.buttons[3] && gp.buttons[3].pressed) || (gp.buttons[6] && gp.buttons[6].pressed)); // Y or L2

        // Edge detection / debouncing for Gamepad Pause (Start / Options button index 9)
        const currentPause = Boolean(gp.buttons[9] && gp.buttons[9].pressed);
        if (currentPause && !this.prevGamepadPause) {
            this.pause = !this.pause;
        }
        this.prevGamepadPause = currentPause;
    }
}
