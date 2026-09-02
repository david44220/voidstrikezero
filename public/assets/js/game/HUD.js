/**
 * VOIDSTRIKE ARENA — HUD & UI State Manager
 * Real-time HUD updates, combo animations, and match conclusion modals.
 */

export class HUDManager {
    constructor() {
        this.hullFill = document.querySelector('.hud-bar__fill-hull');
        this.energyFill = document.querySelector('.hud-bar__fill-energy');
        this.hullText = document.querySelector('.hud-hull-text');
        this.energyText = document.querySelector('.hud-energy-text');

        this.scoreDisplay = document.querySelector('.hud-score');
        this.comboDisplay = document.querySelector('.hud-combo');
        this.waveDisplay = document.querySelector('.hud-wave-text');
        this.killsDisplay = document.querySelector('.hud-kills-text');

        this.centerNotice = document.querySelector('.hud-center-notice');
        this.dashCdSlot = document.querySelector('.hud-cd-slot--dash');
        this.specialCdSlot = document.querySelector('.hud-cd-slot--special');

        this.pauseModal = document.getElementById('pause-modal');
        this.resultsModal = document.getElementById('results-modal');
    }

    update(player, score, combo, wave, kills) {
        // Hull bar
        if (this.hullFill && player) {
            const hPct = Math.max(0, Math.min(100, (player.health / player.maxHealth) * 100));
            this.hullFill.style.width = `${hPct}%`;
            if (this.hullText) this.hullText.textContent = `${Math.ceil(player.health)} / ${player.maxHealth}`;
        }

        // Energy bar
        if (this.energyFill && player) {
            const ePct = Math.max(0, Math.min(100, (player.energy / player.maxEnergy) * 100));
            this.energyFill.style.width = `${ePct}%`;
            if (this.energyText) this.energyText.textContent = `${Math.ceil(player.energy)} / ${player.maxEnergy}`;
        }

        // Score & Combo
        if (this.scoreDisplay) {
            this.scoreDisplay.textContent = score.toLocaleString();
        }

        if (this.comboDisplay) {
            if (combo > 1) {
                this.comboDisplay.textContent = `${combo.toFixed(1)}x COMBO`;
                this.comboDisplay.style.opacity = '1';
            } else {
                this.comboDisplay.style.opacity = '0';
            }
        }

        // Wave & Kills
        if (this.waveDisplay) this.waveDisplay.textContent = `WAVE ${wave}`;
        if (this.killsDisplay) this.killsDisplay.textContent = `${kills}`;

        // Cooldown Overlays
        if (this.dashCdSlot && player) {
            const dashCd = Math.max(0, player.dashCooldownTimer);
            if (dashCd > 0) {
                this.dashCdSlot.style.opacity = '0.5';
            } else {
                this.dashCdSlot.style.opacity = '1';
            }
        }

        if (this.specialCdSlot && player) {
            const spCd = Math.max(0, player.specialCooldownTimer);
            if (spCd > 0) {
                this.specialCdSlot.style.opacity = '0.5';
            } else {
                this.specialCdSlot.style.opacity = '1';
            }
        }
    }

    showNotice(text, duration = 1800) {
        if (!this.centerNotice) return;
        this.centerNotice.textContent = text;
        this.centerNotice.style.opacity = '1';
        this.centerNotice.style.transform = 'scale(1.1)';
        this.centerNotice.style.transition = 'all 0.25s cubic-bezier(0.16, 1, 0.3, 1)';

        setTimeout(() => {
            if (this.centerNotice) {
                this.centerNotice.style.opacity = '0';
                this.centerNotice.style.transform = 'scale(0.9)';
            }
        }, duration);
    }

    setPause(isPaused) {
        if (this.pauseModal) {
            this.pauseModal.style.display = isPaused ? 'flex' : 'none';
        }
    }

    showResults(resultData) {
        if (!this.resultsModal) return;

        const resScore = document.getElementById('res-score');
        const resWaves = document.getElementById('res-waves');
        const resKills = document.getElementById('res-kills');
        const resDuration = document.getElementById('res-duration');
        const resXp = document.getElementById('res-xp');
        const resStatus = document.getElementById('res-status');
        const resAchievements = document.getElementById('res-achievements');

        if (resScore) resScore.textContent = (resultData.score || 0).toLocaleString();
        if (resWaves) resWaves.textContent = resultData.waves || 0;
        if (resKills) resKills.textContent = resultData.kills || 0;
        if (resDuration) resDuration.textContent = `${resultData.duration || 0}s`;
        if (resXp) resXp.textContent = `+${resultData.xp_gained || 0} XP`;

        if (resStatus) {
            if (resultData.status === 'completed') {
                resStatus.textContent = 'TELEMETRY VALIDATED // RANKED';
                resStatus.className = 'eyebrow font-mono text-cyan';
            } else {
                resStatus.textContent = `FLAGGED ANOMALY (${resultData.status.toUpperCase()})`;
                resStatus.className = 'eyebrow font-mono text-crimson';
            }
        }

        if (resAchievements && resultData.achievements && resultData.achievements.length > 0) {
            let html = '<div style="margin-top:1rem; text-align:left;"><strong>Unlocked Accolades:</strong><ul style="list-style:none; padding:0; margin-top:0.5rem;">';
            resultData.achievements.forEach(a => {
                html += `<li style="color:#00ff88; margin-bottom:0.25rem;">✦ ${a.name_en} (+${a.xp_reward} XP)</li>`;
            });
            html += '</ul></div>';
            resAchievements.innerHTML = html;
        }

        this.resultsModal.style.display = 'flex';
    }
}
