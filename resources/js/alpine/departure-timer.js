// resources/js/alpine/departure-timer.js
// Departure countdown timer - receives Unix timestamp, calculates display locally
// ZERO server polling - urgency states calculated in browser

export default function departureTimer(targetTimestamp) {
    return {
        targetTime: new Date(targetTimestamp * 1000),
        secondsRemaining: 0,
        display: '',
        label: '',
        textColorClass: 'text-white',
        labelColorClass: 'text-slate-400',
        interval: null,

        startTimer() {
            this.updateCountdown();
            this.interval = setInterval(() => this.updateCountdown(), 1000);
        },

        updateCountdown() {
            const now = new Date();
            this.secondsRemaining = Math.max(0, Math.floor((this.targetTime - now) / 1000));

            const h = Math.floor(this.secondsRemaining / 3600);
            const m = Math.floor((this.secondsRemaining % 3600) / 60);
            const s = this.secondsRemaining % 60;

            if (this.secondsRemaining <= 0) {
                this.display = 'Go now!';
                this.label = 'Time to leave';
            } else if (h > 0) {
                this.display = `${h}:${String(m).padStart(2, '0')}`;
                this.label = h === 1 ? '1 hour' : `${h} hours`;
            } else {
                this.display = `${m}:${String(s).padStart(2, '0')}`;
                this.label = m === 1 ? '1 minute' : `${m} min`;
            }

            this.updateColors();
        },

        updateColors() {
            if (this.secondsRemaining <= 0) {
                this.textColorClass = 'text-slate-500';
                this.labelColorClass = 'text-slate-600';
            } else if (this.secondsRemaining < 300) {
                // Less than 5 minutes - Critical (red, pulsing)
                this.textColorClass = 'text-red-400 animate-pulse';
                this.labelColorClass = 'text-red-400/70';
            } else if (this.secondsRemaining < 900) {
                // Less than 15 minutes - Urgent (orange)
                this.textColorClass = 'text-orange-400';
                this.labelColorClass = 'text-orange-400/70';
            } else if (this.secondsRemaining < 1800) {
                // Less than 30 minutes - Approaching (yellow)
                this.textColorClass = 'text-yellow-400';
                this.labelColorClass = 'text-yellow-400/70';
            } else {
                // Normal state (white)
                this.textColorClass = 'text-white';
                this.labelColorClass = 'text-slate-400';
            }
        },

        destroy() {
            if (this.interval) {
                clearInterval(this.interval);
            }
        }
    };
}

