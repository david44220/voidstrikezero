/**
 * VOIDSTRIKE ARENA — Mobile Touch Controls Controller
 * Twin-Stick Virtual Controls:
 * - Left Zone: Omnidirectional Movement Joystick
 * - Right Drag Zone & Action Buttons: Directional Aiming and Action Triggers
 * Fully responsive across Portrait and Landscape viewports.
 */

export class TouchControls {
    constructor(inputManager) {
        this.input = inputManager;
        this.joystickZone = document.querySelector('.touch-joystick-zone');
        this.stick = document.querySelector('.touch-stick');

        this.fireBtn = document.querySelector('.touch-btn--fire');
        this.dashBtn = document.querySelector('.touch-btn--dash');
        this.specialBtn = document.querySelector('.touch-btn--special');

        this.moveTouchId = null;
        this.originX = 0;
        this.originY = 0;
        this.maxRadius = 50;

        // Right-side aim drag tracking
        this.aimTouchId = null;
        this.aimOriginX = 0;
        this.aimOriginY = 0;

        this.init();
    }

    init() {
        if (!this.joystickZone || !this.stick) return;

        // Prevent accidental gesture/scrolling on whole screen during gameplay
        document.body.addEventListener('touchstart', (e) => {
            if (e.target.closest('.hud-overlay') || e.target.closest('.game-viewport')) {
                if (e.target.tagName !== 'A' && e.target.tagName !== 'BUTTON') {
                    e.preventDefault();
                }
            }
        }, { passive: false });

        // 1. Virtual Movement Joystick (Left Side)
        this.joystickZone.addEventListener('touchstart', (e) => {
            e.preventDefault();
            const touch = e.changedTouches[0];
            this.moveTouchId = touch.identifier;
            const rect = this.joystickZone.getBoundingClientRect();
            this.originX = rect.left + rect.width / 2;
            this.originY = rect.top + rect.height / 2;
            this.handleMove(touch.clientX, touch.clientY);
        }, { passive: false });

        window.addEventListener('touchmove', (e) => {
            for (let i = 0; i < e.changedTouches.length; i++) {
                const touch = e.changedTouches[i];
                if (touch.identifier === this.moveTouchId) {
                    this.handleMove(touch.clientX, touch.clientY);
                } else if (touch.identifier === this.aimTouchId) {
                    this.handleAim(touch.clientX, touch.clientY);
                }
            }
        }, { passive: false });

        const endTouch = (e) => {
            for (let i = 0; i < e.changedTouches.length; i++) {
                const touch = e.changedTouches[i];
                if (touch.identifier === this.moveTouchId) {
                    this.moveTouchId = null;
                    this.stick.style.transform = 'translate(-50%, -50%)';
                    this.input.touchMoveX = 0;
                    this.input.touchMoveZ = 0;
                }
                if (touch.identifier === this.aimTouchId) {
                    this.aimTouchId = null;
                    this.input.touchAimActive = false;
                }
            }
        };

        window.addEventListener('touchend', endTouch);
        window.addEventListener('touchcancel', endTouch);

        // 2. Right Screen Drag-to-Aim Zone
        window.addEventListener('touchstart', (e) => {
            for (let i = 0; i < e.changedTouches.length; i++) {
                const touch = e.changedTouches[i];
                // Only capture touches on right half that are not on buttons or links
                if (touch.clientX > window.innerWidth * 0.45 && !e.target.closest('.touch-btn') && !e.target.closest('a') && !e.target.closest('button')) {
                    if (this.aimTouchId === null) {
                        this.aimTouchId = touch.identifier;
                        this.aimOriginX = touch.clientX;
                        this.aimOriginY = touch.clientY;
                    }
                }
            }
        });

        // 3. Virtual Action Buttons with clean release tracking
        this.bindTouchButton(this.fireBtn, (pressed) => { this.input.touchFire = pressed; });
        this.bindTouchButton(this.dashBtn, (pressed) => { this.input.touchDash = pressed; });
        this.bindTouchButton(this.specialBtn, (pressed) => { this.input.touchSpecial = pressed; });
    }

    handleMove(clientX, clientY) {
        const dx = clientX - this.originX;
        const dy = clientY - this.originY;
        const distance = Math.hypot(dx, dy);

        let clampedX = dx;
        let clampedY = dy;

        if (distance > this.maxRadius) {
            clampedX = (dx / distance) * this.maxRadius;
            clampedY = (dy / distance) * this.maxRadius;
        }

        this.stick.style.transform = `translate(calc(-50% + ${clampedX}px), calc(-50% + ${clampedY}px))`;

        // Normalize -1 to 1 for input
        this.input.touchMoveX = clampedX / this.maxRadius;
        this.input.touchMoveZ = clampedY / this.maxRadius;
    }

    handleAim(clientX, clientY) {
        const dx = clientX - this.aimOriginX;
        const dy = clientY - this.aimOriginY;
        const dist = Math.hypot(dx, dy);

        if (dist > 15) {
            this.input.touchAimActive = true;
            this.input.touchAimX = dx / dist;
            this.input.touchAimZ = dy / dist;
        }
    }

    bindTouchButton(btn, callback) {
        if (!btn) return;

        btn.addEventListener('touchstart', (e) => {
            e.preventDefault();
            e.stopPropagation();
            callback(true);
            btn.style.transform = 'scale(0.9)';
        }, { passive: false });

        const endBtn = (e) => {
            callback(false);
            btn.style.transform = 'scale(1.0)';
        };

        btn.addEventListener('touchend', endBtn);
        btn.addEventListener('touchcancel', endBtn);
    }
}
