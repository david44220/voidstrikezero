/**
 * VOIDSTRIKE ARENA — Mobile Touch Controls Controller
 * Virtual Joystick and Multi-Touch Action Buttons for mobile devices.
 */

export class TouchControls {
    constructor(inputManager) {
        this.input = inputManager;
        this.joystickZone = document.querySelector('.touch-joystick-zone');
        this.stick = document.querySelector('.touch-stick');

        this.fireBtn = document.querySelector('.touch-btn--fire');
        this.dashBtn = document.querySelector('.touch-btn--dash');
        this.specialBtn = document.querySelector('.touch-btn--special');

        this.touchId = null;
        this.originX = 0;
        this.originY = 0;
        this.maxRadius = 50;

        this.init();
    }

    init() {
        if (!this.joystickZone || !this.stick) return;

        // Prevent accidental gestures/scrolling on whole screen during gameplay
        document.body.addEventListener('touchstart', (e) => {
            if (e.target.closest('.hud-overlay') || e.target.closest('.game-viewport')) {
                // Keep default only for interactive links
                if (e.target.tagName !== 'A' && e.target.tagName !== 'BUTTON') {
                    e.preventDefault();
                }
            }
        }, { passive: false });

        // Virtual Joystick Touch Handlers
        this.joystickZone.addEventListener('touchstart', (e) => {
            e.preventDefault();
            const touch = e.changedTouches[0];
            this.touchId = touch.identifier;
            const rect = this.joystickZone.getBoundingClientRect();
            this.originX = rect.left + rect.width / 2;
            this.originY = rect.top + rect.height / 2;
            this.handleMove(touch.clientX, touch.clientY);
        }, { passive: false });

        window.addEventListener('touchmove', (e) => {
            if (this.touchId === null) return;
            for (let i = 0; i < e.changedTouches.length; i++) {
                if (e.changedTouches[i].identifier === this.touchId) {
                    this.handleMove(e.changedTouches[i].clientX, e.changedTouches[i].clientY);
                    break;
                }
            }
        }, { passive: false });

        const endTouch = (e) => {
            if (this.touchId === null) return;
            for (let i = 0; i < e.changedTouches.length; i++) {
                if (e.changedTouches[i].identifier === this.touchId) {
                    this.touchId = null;
                    this.stick.style.transform = 'translate(-50%, -50%)';
                    this.input.touchMoveX = 0;
                    this.input.touchMoveZ = 0;
                    break;
                }
            }
        };

        window.addEventListener('touchend', endTouch);
        window.addEventListener('touchcancel', endTouch);

        // Virtual Action Buttons
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

    bindTouchButton(btn, callback) {
        if (!btn) return;
        btn.addEventListener('touchstart', (e) => {
            e.preventDefault();
            btn.style.transform = 'scale(0.9)';
            callback(true);
        }, { passive: false });

        const end = (e) => {
            e.preventDefault();
            btn.style.transform = 'none';
            callback(false);
        };

        btn.addEventListener('touchend', end);
        btn.addEventListener('touchcancel', end);
    }
}
