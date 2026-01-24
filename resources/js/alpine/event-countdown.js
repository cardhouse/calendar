// resources/js/alpine/event-countdown.js
// Event countdown component - updates every 60 seconds (events don't need per-second precision)
// ZERO server polling - all calculations happen in browser

export default function eventCountdown(targetTimestamp) {
    return {
        targetTime: new Date(targetTimestamp * 1000),
        countdown: '',
        interval: null,

        startTimer() {
            this.updateCountdown();
            // Events update every minute (not every second - less critical)
            this.interval = setInterval(() => this.updateCountdown(), 60000);
        },

        updateCountdown() {
            const now = new Date();
            const diff = this.targetTime - now;

            if (diff <= 0) {
                this.countdown = 'Now';
                return;
            }

            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

            if (days > 7) {
                this.countdown = `${days} days`;
            } else if (days >= 2) {
                this.countdown = `${days} days, ${hours} hours`;
            } else if (days === 1) {
                this.countdown = `1 day, ${hours} hours`;
            } else if (hours > 0) {
                this.countdown = `${hours} hours, ${minutes} min`;
            } else {
                this.countdown = `${minutes} minutes`;
            }
        },

        destroy() {
            if (this.interval) {
                clearInterval(this.interval);
            }
        }
    };
}


