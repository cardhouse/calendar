// resources/js/alpine/clock-display.js
// Clock display component - updates every second, formats time/date
// ZERO server polling - all calculations happen in browser

export default function clockDisplay() {
    return {
        timeDisplay: '',
        amPm: '',
        dateDisplay: '',
        interval: null,

        startClock() {
            this.updateClock();
            this.interval = setInterval(() => this.updateClock(), 1000);
        },

        updateClock() {
            const now = new Date();
            this.timeDisplay = now.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            }).replace(/\s?(AM|PM)/, '');
            this.amPm = now.toLocaleTimeString('en-US', { hour12: true }).slice(-2);
            this.dateDisplay = now.toLocaleDateString('en-US', {
                weekday: 'long',
                month: 'long',
                day: 'numeric',
                year: 'numeric'
            });
        },

        destroy() {
            if (this.interval) {
                clearInterval(this.interval);
            }
        }
    };
}


