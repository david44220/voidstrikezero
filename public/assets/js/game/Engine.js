/**
 * VOIDSTRIKE ARENA — Master 3D Game Engine
 * Coordinates Three.js render loop, physics, AI waves, anti-cheat telemetry, and API validation.
 */

import * as THREE from '../vendor/three/three.module.js';
import { GameAudio } from './Audio.js';
import { InputManager } from './Input.js';
import { TouchControls } from './TouchControls.js';
import { Vehicle } from './Vehicle.js';
import { Arena } from './Arena.js';
import { WeaponsManager } from './Weapons.js';
import { PickupsManager } from './Pickups.js';
import { AIManager } from './AI.js';
import { HUDManager } from './HUD.js';

export class GameEngine {
    constructor(config) {
        this.canvas = document.getElementById('game-canvas');
        this.vehicleType = config.vehicle || 'striker';
        this.arenaId = config.arena || 'neon_core';
        this.difficulty = config.difficulty || 'normal';
        this.mode = config.mode || 'quick';
        this.challengeId = config.challengeId || null;

        // Core systems
        this.audio = new GameAudio();
        this.audio.init();

        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(55, window.innerWidth / window.innerHeight, 0.5, 300);
        this.camera.position.set(0, 32, 22);
        this.camera.lookAt(0, 0, 0);

        this.renderer = new THREE.WebGLRenderer({
            canvas: this.canvas,
            antialias: true,
            powerPreference: 'high-performance',
        });
        this.renderer.setSize(window.innerWidth, window.innerHeight);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2.0));
        this.renderer.shadowMap.enabled = true;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;

        // Game Components
        this.hud = new HUDManager();
        this.input = new InputManager(this.canvas, this.camera);
        this.touchControls = new TouchControls(this.input);

        this.arena = new Arena(this.arenaId, this.scene);
        this.weapons = new WeaponsManager(this.scene, this.audio);
        this.pickups = new PickupsManager(this.scene, this.audio);
        this.ai = new AIManager(this.scene, this.weapons, this.audio, this.difficulty);
        this.player = new Vehicle(this.vehicleType, this.scene, this.audio);

        // Gameplay Metrics
        this.score = 0;
        this.combo = 1.0;
        this.comboTimer = 0;
        this.comboMax = 1.0;
        this.wave = 1;
        this.kills = 0;
        this.matchStartTime = 0;
        this.matchDuration = 0;
        this.isGameOver = false;
        this.isSubmitting = false;

        // Anti-Cheat & Telemetry
        this.runToken = null;
        this.startNonce = null;
        this.telemetrySnapshots = [];
        this.lastSnapshotTime = 0;

        // Wire AI kill callback
        this.ai.onEnemyKilled = (enemy) => this.handleEnemyKilled(enemy);

        this.initResizeListener();
    }

    initResizeListener() {
        window.addEventListener('resize', () => {
            this.camera.aspect = window.innerWidth / window.innerHeight;
            this.camera.updateProjectionMatrix();
            this.renderer.setSize(window.innerWidth, window.innerHeight);
        });
    }

    async start() {
        this.hud.showNotice('SYNCHRONIZING WITH DEFENSE GRID...', 2000);

        // 1. Handshake with server to obtain cryptographic run token
        try {
            const resp = await window.vsFetch('/api/match/start', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    vehicle: this.vehicleType,
                    arena: this.arenaId,
                    difficulty: this.difficulty,
                    mode: this.mode,
                    challenge_id: this.challengeId,
                })
            });

            const data = await resp.json();
            if (data.success && data.handshake) {
                this.runToken = data.handshake.run_token;
                this.startNonce = data.handshake.start_nonce;
            }
        } catch (e) {
            console.warn('Offline / local run token fallback: ', e);
            this.runToken = 'offline_token_' + Date.now();
        }

        this.matchStartTime = performance.now();
        this.lastSnapshotTime = this.matchStartTime;
        this.lastFrameTime = performance.now();

        this.hud.showNotice('ENGAGE COMBAT // WAVE 1', 2200);
        this.spawnWave(this.wave);

        // Start animation loop
        requestAnimationFrame((now) => this.loop(now));
    }

    loop(now) {
        if (this.isGameOver) return;

        const dt = Math.min((now - this.lastFrameTime) / 1000, 0.1); // clamp delta to prevent spiral
        this.lastFrameTime = now;

        if (!this.input.pause) {
            this.update(dt, now);
            this.hud.setPause(false);
        } else {
            this.hud.setPause(true);
        }

        this.renderer.render(this.scene, this.camera);
        requestAnimationFrame((t) => this.loop(t));
    }

    update(dt, now) {
        this.matchDuration = (now - this.matchStartTime) / 1000;

        // 1. Input & Player update
        this.input.update();

        // Fire player weapon
        if (this.input.fire) {
            this.weapons.firePlayerWeapon(this.player);
        }

        this.player.update(dt, this.input, this.arena.radius);

        // Smooth Camera Follow
        const targetCamX = this.player.position.x * 0.6;
        const targetCamZ = this.player.position.z * 0.6 + 24;
        this.camera.position.x += (targetCamX - this.camera.position.x) * 4 * dt;
        this.camera.position.z += (targetCamZ - this.camera.position.z) * 4 * dt;
        this.camera.lookAt(this.player.position.x * 0.3, 0, this.player.position.z * 0.3);

        // 2. Arena, Weapons, Pickups, AI updates
        this.arena.update(dt, this.player);
        this.weapons.update(dt, this.ai.enemies, this.player, this.arena);
        this.pickups.update(dt, this.player, this.arena.radius);
        this.ai.update(dt, this.player, this.arena, this.pickups);

        // 3. Wave Progression
        if (this.ai.enemies.length === 0 && !this.isGameOver) {
            this.wave++;
            this.score += 1500 * this.wave;
            this.hud.showNotice(`WAVE ${this.wave} INCOMING!`, 1800);
            setTimeout(() => {
                if (!this.isGameOver) this.spawnWave(this.wave);
            }, 1200);
        }

        // 4. Combo Decay
        if (this.comboTimer > 0) {
            this.comboTimer -= dt;
            if (this.comboTimer <= 0) {
                this.combo = 1.0;
            }
        }

        // 5. Periodic Telemetry Snapshots (every 3 seconds)
        if (now - this.lastSnapshotTime >= 3000) {
            this.lastSnapshotTime = now;
            this.telemetrySnapshots.push({
                time: Math.round(this.matchDuration),
                score: this.score,
                hull: Math.round(this.player.health),
                energy: Math.round(this.player.energy),
                kills: this.kills,
            });
        }

        // 6. HUD Update
        this.hud.update(this.player, this.score, this.combo, this.wave, this.kills);

        // 7. Check Player Defeat
        if (this.player.health <= 0 && !this.isGameOver) {
            this.handleGameOver(false);
        }
    }

    spawnWave(waveNum) {
        const count = 3 + waveNum;
        for (let i = 0; i < count; i++) {
            const angle = (i / count) * Math.PI * 2 + Math.random();
            const r = (this.arena.radius * 0.5) + (Math.random() * 12);
            const x = Math.cos(angle) * r;
            const z = Math.sin(angle) * r;

            let type = 'scout';
            if (waveNum >= 2 && i % 3 === 0) type = 'assault';
            if (waveNum >= 4 && i % 5 === 0) type = 'heavy';

            this.ai.spawn(type, x, z);
        }
    }

    handleEnemyKilled(enemy) {
        this.kills++;

        // Combo system
        this.combo = Math.min(5.0, this.combo + 0.5);
        this.comboTimer = 3.8; // 3.8s window to maintain combo
        if (this.combo > this.comboMax) {
            this.comboMax = this.combo;
        }

        if (this.audio) this.audio.combo(this.combo);

        // Calculate points
        const earned = Math.round(enemy.points * this.combo);
        this.score += earned;
    }

    async handleGameOver(victory = false) {
        this.isGameOver = true;
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        this.hud.showNotice(victory ? 'VICTORY // SECTOR SECURED' : 'CHASSIS DESTROYED // MISSION OVER', 3000);

        const duration = Math.max(1, Math.round(this.matchDuration));
        const accuracy = this.weapons.shotsFired > 0 
            ? Math.round((this.weapons.shotsHit / this.weapons.shotsFired) * 1000) / 10 
            : 0;

        const submissionData = {
            run_token: this.runToken,
            start_nonce: this.startNonce,
            score: this.score,
            waves: this.wave,
            kills: this.kills,
            duration: duration,
            accuracy: accuracy,
            combo_max: Math.round(this.comboMax),
            shots_fired: this.weapons.shotsFired,
            shots_hit: this.weapons.shotsHit,
            damage_dealt: this.weapons.totalDamageDealt,
            damage_taken: Math.round(this.player.maxHealth - this.player.health),
            telemetry: this.telemetrySnapshots,
        };

        try {
            const resp = await window.vsFetch('/api/match/finish', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(submissionData),
            });

            const data = await resp.json();
            if (data.success && data.result) {
                this.hud.showResults(data.result);
            } else {
                this.hud.showResults({
                    score: this.score,
                    waves: this.wave,
                    kills: this.kills,
                    duration: duration,
                    xp_gained: 0,
                    status: 'error_or_unranked',
                });
            }
        } catch (e) {
            console.error('Failed to submit match telemetry:', e);
            this.hud.showResults({
                score: this.score,
                waves: this.wave,
                kills: this.kills,
                duration: duration,
                xp_gained: 0,
                status: 'offline',
            });
        }
    }
}
