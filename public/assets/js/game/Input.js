/**
 * VOIDSTRIKE ARENA — Centralized Input Abstraction
 * Unifies Keyboard, Mouse Raycasting, Gamepad API, and Virtual Touch.
 */

import * as THREE from '../vendor/three/three.module.js';

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

        this.touchMoveX = 0;
        this.touchMoveZ = 0;
        this.touchFire = false;
        this.touchDash = false;
        this.touchSpecial = false;
        this.touchBoost = false;

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
            this.mousePos.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
            this.mousePos.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;
        });

        // Mouse Buttons
        window.addEventListener('mousedown', (e) => {
            if (e.button === 0) this.fire = true;
            if (e.button === 2) {
                e.preventDefault();
                this.special = true;
            }
        });

        window.addEventListener('mouseup', (e) => {
            if (e.button === 0) this.fire = false;
            if (e.button === 2) this.special = false;
        });

        window.addEventListener('contextmenu', (e) => e.preventDefault());
    }

    update() {
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

        // Action Buttons
        this.fire = (this.keys['KeyJ'] || this.fire || this.touchFire);
        this.dash = (this.keys['Space'] || this.touchDash);
        this.special = (this.keys['KeyE'] || this.keys['KeyK'] || this.special || this.touchSpecial);
        this.boost = (this.keys['ShiftLeft'] || this.keys['ShiftRight'] || this.touchBoost);

        // 2. Mouse Aim Raycasting
        this.raycaster.setFromCamera(this.mousePos, this.camera);
        const intersectPoint = new THREE.Vector3();
        if (this.raycaster.ray.intersectPlane(this.groundPlane, intersectPoint)) {
            this.aimPoint.copy(intersectPoint);
        }

        // 3. Gamepad API
        this.pollGamepad();
    }

    pollGamepad() {
        const gamepads = navigator.getGamepads ? navigator.getGamepads() : [];
        if (!gamepads || !gamepads[0]) return;
        const gp = gamepads[0];

        // Left stick
        const lx = gp.axes[0] || 0;
        const ly = gp.axes[1] || 0;
        if (Math.hypot(lx, ly) > 0.15) {
            this.moveX = lx;
            this.moveZ = ly;
        }

        // Buttons: 0=A (Fire/Confirm), 1=B (Dash), 2=X (Special), 7=R2 (Fire)
        if (gp.buttons[0]?.pressed || gp.buttons[7]?.pressed) this.fire = true;
        if (gp.buttons[1]?.pressed) this.dash = true;
        if (gp.buttons[2]?.pressed) this.special = true;
        if (gp.buttons[6]?.pressed || gp.buttons[10]?.pressed) this.boost = true;
        if (gp.buttons[9]?.pressed) this.pause = !this.pause;
    }
}
