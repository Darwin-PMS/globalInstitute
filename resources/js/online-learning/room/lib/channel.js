/**
 * RoomChannel — the room's update transport.
 *
 * THIS IS THE REAL-TIME SEAM. Panels never poll and never touch WebSockets; they
 * subscribe to named topics here and receive payloads. Today those payloads come
 * from interval polling of the delta endpoints. To go real-time, implement
 * `connectRealtime()` against Echo/Pusher and have it publish to the same topics
 * — no panel changes, because the payload shape is identical either way.
 *
 * Topics: 'state' | 'presence' | 'messages' | 'timeline'
 */
export class RoomChannel {
    constructor(api, intervals = {}) {
        this.api = api;
        this.intervals = {
            state: 15000,
            presence: 20000,
            messages: 5000,
            timeline: 30000,
            ...intervals,
        };

        this.subscribers = new Map();
        this.timers = new Map();
        this.cursors = { messages: null, timeline: null };
        this.paused = false;
        this.realtime = false;

        // A hidden tab should not keep polling; it catches up on return.
        this.visibilityHandler = () => {
            if (document.hidden) this.pause();
            else this.resume();
        };
        document.addEventListener('visibilitychange', this.visibilityHandler);
    }

    /** Subscribe to a topic. Returns an unsubscribe function. */
    on(topic, handler) {
        if (!this.subscribers.has(topic)) this.subscribers.set(topic, new Set());
        this.subscribers.get(topic).add(handler);

        return () => this.subscribers.get(topic)?.delete(handler);
    }

    emit(topic, payload) {
        this.subscribers.get(topic)?.forEach((handler) => {
            try {
                handler(payload);
            } catch (err) {
                console.error(`[RoomChannel] ${topic} subscriber failed`, err);
            }
        });
    }

    /**
     * Start a topic's poller. Only topics with subscribers are polled, so an
     * unopened panel costs nothing.
     */
    start(topic) {
        if (this.realtime || this.timers.has(topic)) return;

        const tick = async () => {
            if (this.paused) return;
            try {
                await this.fetch(topic);
            } catch (err) {
                if (err.sessionExpired) {
                    this.emit('session-expired', err);
                    this.stopAll();
                }
                // Transient errors are ignored; the next tick retries.
            }
        };

        tick();
        this.timers.set(topic, setInterval(tick, this.intervals[topic] || 15000));
    }

    stop(topic) {
        const timer = this.timers.get(topic);
        if (timer) {
            clearInterval(timer);
            this.timers.delete(topic);
        }
    }

    stopAll() {
        this.timers.forEach((t) => clearInterval(t));
        this.timers.clear();
    }

    pause() { this.paused = true; }

    resume() {
        if (!this.paused) return;
        this.paused = false;
        // Catch up immediately rather than waiting out the interval.
        this.timers.forEach((_, topic) => this.fetch(topic).catch(() => {}));
    }

    /** Fetch one topic and publish whatever is new. */
    async fetch(topic) {
        if (topic === 'state') {
            const res = await this.api.state();
            if (res?.data) this.emit('state', res.data);
            return;
        }

        if (topic === 'presence') {
            const res = await this.api.presence();
            if (res?.data) this.emit('presence', res.data);
            return;
        }

        if (topic === 'messages') {
            const res = await this.api.messages(this.cursors.messages);
            if (!res?.data) return;

            const messages = res.data.messages || [];
            if (res.meta?.last_id) this.cursors.messages = res.meta.last_id;

            // Only emit when something actually arrived, so panels do not
            // re-render on every empty poll.
            if (messages.length) {
                this.emit('messages', { messages, pinned: res.data.pinned || [], isDelta: !!res.meta?.is_delta });
            }
            return;
        }

        if (topic === 'timeline') {
            const res = await this.api.activity(this.cursors.timeline);
            if (!res?.data) return;

            const events = res.data.events || [];
            if (res.meta?.last_id) this.cursors.timeline = res.meta.last_id;
            if (events.length) this.emit('timeline', { events });
        }
    }

    /** Seed a cursor from a first load so the next poll returns only deltas. */
    setCursor(topic, id) {
        if (id) this.cursors[topic] = id;
    }

    /**
     * Where a WebSocket transport plugs in.
     *
     * Publish into `this.emit(topic, payload)` with the same shapes the pollers
     * use and set `this.realtime = true` so polling stands down. Server side, the
     * Room*Changed events already carry a per-room channel name.
     */
    connectRealtime() {
        return false; // not enabled yet — polling remains the transport
    }

    destroy() {
        this.stopAll();
        this.subscribers.clear();
        document.removeEventListener('visibilitychange', this.visibilityHandler);
    }
}
