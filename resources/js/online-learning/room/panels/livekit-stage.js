/**
 * LiveKit Video Stage Panel
 *
 * Handles the WebRTC video/audio connection via LiveKit.
 * Renders instructor video prominently, student grid, screen share, and controls.
 */
import {
    Room,
    RoomEvent,
    Track,
    ConnectionState,
    ParticipantEvent,
    TrackPublication,
} from 'livekit-client';

export default class LiveKitStagePanel {
    constructor({ api, channel, capabilities, userId, mount, shell }) {
        this.api = api;
        this.shell = shell;
        this.mount = mount;
        this.userId = userId;
        this.room = null;
        this.token = null;
        this.connected = false;
        this.localTracks = { camera: null, microphone: null };
        this.isMuted = false;
        this.isCameraOff = false;
        this.isScreenSharing = false;
        this.isHandRaised = capabilities.is_hand_raised || false;
        this.participants = new Map();
        this.destroyed = false;
    }

    async render() {
        this.mount.innerHTML = this.renderJoinStage();
        this.bindJoinEvents();
    }

    renderJoinStage() {
        const { room, klass, me } = this.shell.snapshot();
        const isHost = me.role === 'host' || me.role === 'moderator';
        const startTime = this.formatScheduleTime(klass);

        return `
            <div class="join-stage-card">
                <div class="join-stage-hero">
                    <div class="text-center space-y-5 p-8 relative z-10">
                        <div class="icon-ring mx-auto">
                            <span class="material-symbols-outlined text-[36px] text-indigo-400">${isHost ? 'school' : 'person'}</span>
                        </div>
                        <div>
                            <h2 class="text-xl font-extrabold text-white tracking-tight">${this.escapeHtml(klass.title)}</h2>
                            <p class="text-sm text-slate-400 mt-1.5 font-medium">${this.escapeHtml(klass.context || '')}</p>
                        </div>
                        <div class="flex items-center justify-center gap-4 text-xs text-slate-500">
                            <span class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[15px]">schedule</span>
                                ${this.escapeHtml(startTime)}
                            </span>
                            <span class="w-1 h-1 rounded-full bg-slate-600"></span>
                            <span class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[15px]">person</span>
                                ${this.escapeHtml(klass.teacher?.name || 'Instructor')}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 flex items-center justify-between border-t border-white/[0.04]">
                    <div class="flex items-center gap-2.5">
                        <div class="live-pulse w-2 h-2 rounded-full bg-emerald-500"></div>
                        <span class="text-xs text-slate-400 font-medium" id="stage-online">${room.participant_count || 0} online</span>
                    </div>
                    <button id="livekit-join-btn"
                        class="group px-7 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold transition-all duration-200 flex items-center gap-2.5 shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/30 hover:-translate-y-[1px]">
                        <span class="material-symbols-outlined text-[18px] group-hover:scale-110 transition-transform">videocam</span>
                        Join Classroom
                    </button>
                </div>
            </div>
        `;
    }

    formatScheduleTime(klass) {
        if (klass.time) return this.escapeHtml(klass.time);
        const start = klass.start_time || klass.starts_at;
        const end = klass.end_time || klass.ends_at;
        if (start) {
            let str = this.escapeHtml(start);
            if (end) str += ' – ' + this.escapeHtml(end);
            return str;
        }
        return 'Scheduled';
    }

    bindJoinEvents() {
        const btn = this.mount.querySelector('#livekit-join-btn');
        if (btn) {
            btn.addEventListener('click', () => this.joinRoom());
        }
    }

    async joinRoom() {
        const btn = this.mount.querySelector('#livekit-join-btn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span> Connecting…';
        }

        try {
            const onlineClassId = this.shell.state.klass?.id;
            const res = await fetch(
                `${this.api.liveClassesBase}/${onlineClassId}/join`,
                {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.api.csrfToken,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ mic_enabled: true, cam_enabled: true }),
                }
            );
            const data = await res.json();
            if (!data.success) throw new Error(data.message);

            this.token = data.token;
            this.livekitUrl = data.livekit_url;
            this.permissions = data.permissions;

            await this.connectToLiveKit();
        } catch (err) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined text-[18px]">videocam</span> Join Classroom';
            }
            this.showError(err.message || 'Unable to connect to classroom.');
        }
    }

    async connectToLiveKit() {
        this.room = new Room({
            adaptiveStream: true,
            dynacast: true,
            audioCaptureDefaults: { echoCancellation: true, noiseSuppression: true, autoGainControl: true },
            videoCaptureDefaults: { resolution: { width: 1280, height: 720, frameRate: 30 } },
        });

        this.room.on(RoomEvent.Connected, () => {
            this.connected = true;
            this.renderClassroom();
            this.setupTrackSubscriptions();
        });

        this.room.on(RoomEvent.Disconnected, () => {
            this.connected = false;
            this.handleDisconnect();
        });

        this.room.on(RoomEvent.ParticipantConnected, (p) => this.onParticipantJoined(p));
        this.room.on(RoomEvent.ParticipantDisconnected, (p) => this.onParticipantLeft(p));
        this.room.on(RoomEvent.TrackSubscribed, (track, pub, participant) => this.onTrackSubscribed(track, pub, participant));
        this.room.on(RoomEvent.TrackUnsubscribed, (track, pub, participant) => this.onTrackUnsubscribed(track, pub, participant));
        this.room.on(RoomEvent.ActiveSpeakerChanged, (speaker) => this.onActiveSpeakerChanged(speaker));
        this.room.on(RoomEvent.DataReceived, (payload, participant, kind, topic) => this.onDataReceived(payload, participant, topic));

        await this.room.connect(this.livekitUrl, this.token);

        // Publish local tracks
        await this.room.localParticipant.setCameraEnabled(true);
        await this.room.localParticipant.setMicrophoneEnabled(true);
    }

    renderClassroom() {
        const { room, klass, me } = this.shell.snapshot();
        const isHost = me.role === 'host' || me.role === 'moderator';

        this.mount.innerHTML = `
            <div class="classroom-container space-y-3">
                <!-- Video Grid -->
                <div id="livekit-video-grid" class="video-grid" style="aspect-ratio:16/9;">
                    <div id="speaker-view" class="absolute inset-0"></div>
                    <div id="participant-strip"></div>

                    <!-- Empty state before tracks arrive -->
                    <div id="video-empty" class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center space-y-3">
                            <div class="w-14 h-14 mx-auto rounded-2xl bg-white/[0.03] border border-white/[0.06] flex items-center justify-center">
                                <span class="material-symbols-outlined text-2xl text-slate-500">videocam</span>
                            </div>
                            <p class="text-xs text-slate-500 font-medium">Waiting for video feeds…</p>
                        </div>
                    </div>
                </div>

                <!-- Controls Bar -->
                <div class="controls-bar">
                    <div class="flex items-center gap-3">
                        <div class="recording-indicator live-pulse" style="font-size:11px;">
                            <span class="font-semibold">LIVE</span>
                        </div>
                        <span class="text-slate-700">|</span>
                        <span id="class-timer" class="text-xs text-slate-500 font-mono tabular-nums tracking-wider">00:00:00</span>
                    </div>

                    <div class="flex items-center gap-1.5" id="control-buttons">
                        <button id="ctrl-mic" data-tooltip="Toggle Microphone"
                            class="control-btn">
                            <span class="material-symbols-outlined text-[20px]">mic</span>
                        </button>
                        <button id="ctrl-cam" data-tooltip="Toggle Camera"
                            class="control-btn">
                            <span class="material-symbols-outlined text-[20px]">videocam</span>
                        </button>
                        <button id="ctrl-screen" data-tooltip="Share Screen"
                            class="control-btn">
                            <span class="material-symbols-outlined text-[20px]">screen_share</span>
                        </button>
                        <button id="ctrl-hand" data-tooltip="Raise Hand"
                            class="control-btn">
                            <span class="material-symbols-outlined text-[20px]">pan_tool</span>
                        </button>
                        ${isHost ? `
                        <button id="ctrl-record" data-tooltip="Record"
                            class="control-btn">
                            <span class="material-symbols-outlined text-[20px]">radio_button_checked</span>
                        </button>
                        ` : ''}
                        <button id="ctrl-leave" data-tooltip="Leave"
                            class="control-btn danger">
                            <span class="material-symbols-outlined text-[20px]">call_end</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-2.5">
                        <span id="connection-quality" class="flex items-center gap-1.5 text-[11px] text-slate-500 font-medium">
                            <span class="material-symbols-outlined text-[14px]">signal_cellular_alt</span>
                            Excellent
                        </span>
                        <button id="ctrl-participants" class="w-9 h-9 rounded-xl flex items-center justify-center hover:bg-white/[0.06] text-slate-400 transition-all" data-tooltip="Participants">
                            <span class="material-symbols-outlined text-[18px]">groups</span>
                        </button>
                    </div>
                </div>
            </div>
        `;

        this.bindControlEvents();
        this.startTimer();
        this.renderExistingParticipants();
    }

    bindControlEvents() {
        this.mount.querySelector('#ctrl-mic')?.addEventListener('click', () => this.toggleMic());
        this.mount.querySelector('#ctrl-cam')?.addEventListener('click', () => this.toggleCamera());
        this.mount.querySelector('#ctrl-screen')?.addEventListener('click', () => this.toggleScreenShare());
        this.mount.querySelector('#ctrl-hand')?.addEventListener('click', () => this.toggleHandRaise());
        this.mount.querySelector('#ctrl-record')?.addEventListener('click', () => this.toggleRecording());
        this.mount.querySelector('#ctrl-leave')?.addEventListener('click', () => this.leaveRoom());
        this.mount.querySelector('#ctrl-participants')?.addEventListener('click', () => {
            this.shell.openPanel('roster');
        });
    }

    // ─── Media Controls ──────────────────────────────────────────

    async toggleMic() {
        if (!this.room) return;
        const enabled = !this.isMuted;
        await this.room.localParticipant.setMicrophoneEnabled(enabled);
        this.isMuted = !enabled;
        this.updateControlState('ctrl-mic', enabled ? 'mic' : 'mic_off', !enabled);
    }

    async toggleCamera() {
        if (!this.room) return;
        const enabled = !this.isCameraOff;
        await this.room.localParticipant.setCameraEnabled(enabled);
        this.isCameraOff = !enabled;
        this.updateControlState('ctrl-cam', enabled ? 'videocam' : 'videocam_off', !enabled);
    }

    async toggleScreenShare() {
        if (!this.room) return;

        if (this.isScreenSharing) {
            await this.room.localParticipant.setScreenShareEnabled(false);
            this.isScreenSharing = false;
            this.updateControlState('ctrl-screen', 'screen_share', false);
        } else {
            try {
                await this.room.localParticipant.setScreenShareEnabled(true, { audio: true });
                this.isScreenSharing = true;
                this.updateControlState('ctrl-screen', 'stop_screen_share', true);
            } catch (err) {
                console.warn('Screen share failed:', err);
                this.showToast('Screen sharing is not supported or was denied.', 'error');
            }
        }
    }

    async toggleHandRaise() {
        this.isHandRaised = !this.isHandRaised;
        this.updateControlState('ctrl-hand', this.isHandRaised ? 'pan_tool' : 'back_hand', this.isHandRaised);

        const onlineClassId = this.shell.state.klass?.id;
        const endpoint = this.isHandRaised ? 'raise-hand' : 'lower-hand';
        try {
            await fetch(
                `${this.api.liveClassesBase}/${onlineClassId}/${endpoint}`,
                {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.api.csrfToken,
                    },
                    credentials: 'same-origin',
                }
            );
        } catch (err) {
            console.warn('Hand raise notification failed:', err);
        }
    }

    async toggleRecording() {
        const onlineClassId = this.shell.state.klass?.id;
        const btn = this.mount.querySelector('#ctrl-record');
        const isRecording = btn?.classList.contains('active');

        try {
            const endpoint = isRecording ? 'recording/stop' : 'recording/start';
            const res = await fetch(
                `${this.api.liveClassesBase}/${onlineClassId}/${endpoint}`,
                {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.api.csrfToken,
                    },
                    credentials: 'same-origin',
                }
            );
            const data = await res.json();

            if (data.success) {
                if (isRecording) {
                    this.updateControlState('ctrl-record', 'radio_button_checked', false);
                    this.showToast('Recording stopped.', 'info');
                } else {
                    this.updateControlState('ctrl-record', 'radio_button_checked', true);
                    this.showToast('Recording started.', 'success');
                }
            }
        } catch (err) {
            this.showToast('Failed to toggle recording.', 'error');
        }
    }

    updateControlState(btnId, icon, active) {
        const btn = this.mount.querySelector(`#${btnId}`);
        if (!btn) return;

        const span = btn.querySelector('.material-symbols-outlined');
        if (span) span.textContent = icon;

        if (active) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    }

    // ─── Participant Rendering ───────────────────────────────────

    setupTrackSubscriptions() {
        this.room.on(RoomEvent.TrackSubscribed, (track, publication, participant) => {
            this.attachTrack(track, publication, participant);
        });
    }

    attachTrack(track, publication, participant) {
        const identity = participant.identity;
        const isLocal = participant.isLocal;

        // Hide empty state on first track
        const empty = this.mount.querySelector('#video-empty');
        if (empty) empty.remove();

        if (track.kind === Track.Kind.Video) {
            const containerId = isLocal ? 'speaker-view' : `participant-${identity}`;
            let container = this.mount.querySelector(`#${containerId}`);

            if (!container) {
                container = document.createElement('div');
                container.id = containerId;
                container.className = 'video-tile';
                if (!isLocal) {
                    container.style.cssText = 'position:absolute;inset:0;';
                }
                this.mount.querySelector('#speaker-view')?.appendChild(container);
            }

            const element = track.attach();
            element.className = 'w-full h-full object-cover';
            container.appendChild(element);
        } else if (track.kind === Track.Kind.Audio) {
            const element = track.attach();
            element.id = `audio-${identity}`;
            this.mount.appendChild(element);
        }
    }

    onTrackUnsubscribed(track, publication, participant) {
        const elements = track.detach();
        elements.forEach((el) => el.remove());
    }

    onParticipantJoined(participant) {
        this.participants.set(participant.identity, participant);
        this.renderParticipantStrip();
    }

    onParticipantLeft(participant) {
        this.participants.delete(participant.identity);
        const el = this.mount.querySelector(`#participant-${participant.identity}`);
        if (el) el.remove();
        this.renderParticipantStrip();
    }

    renderExistingParticipants() {
        if (!this.room) return;

        this.room.remoteParticipants.forEach((participant) => {
            this.participants.set(participant.identity, participant);
            participant.tracks.forEach((publication) => {
                if (publication.track) {
                    this.attachTrack(publication.track, publication, participant);
                }
            });
        });

        this.renderParticipantStrip();
    }

    renderParticipantStrip() {
        const strip = this.mount.querySelector('#participant-strip');
        if (!strip) return;

        strip.innerHTML = '';
        this.participants.forEach((participant) => {
            const tile = document.createElement('div');
            tile.className = 'flex-shrink-0 rounded-xl bg-[#0c1018] border border-white/[0.06] overflow-hidden relative group';

            const name = document.createElement('div');
            name.className = 'absolute bottom-1.5 left-1.5 text-[10px] text-slate-400 bg-black/60 backdrop-blur-sm px-1.5 py-0.5 rounded-md font-medium';
            name.textContent = participant.name || participant.identity;
            tile.appendChild(name);

            strip.appendChild(tile);

            participant.tracks.forEach((pub) => {
                if (pub.track?.kind === Track.Kind.Video) {
                    const el = pub.track.attach();
                    el.className = 'w-full h-full object-cover';
                    tile.prepend(el);
                }
            });
        });
    }

    onActiveSpeakerChanged(speaker) {
        const allTiles = this.mount.querySelectorAll('.video-tile');
        allTiles.forEach((t) => t.classList.remove('speaking'));

        if (speaker && !speaker.isLocal) {
            const tile = this.mount.querySelector(`#participant-${speaker.identity}`);
            if (tile) tile.classList.add('speaking');
        }
    }

    onDataReceived(payload, participant, topic) {
        try {
            const data = JSON.parse(new TextDecoder().decode(payload));

            if (topic === 'hand-raise') {
                if (data.type === 'hand_raised') {
                    this.showToast(`${data.name} raised their hand`, 'info');
                }
            } else if (topic === 'recording') {
                if (data.type === 'recording_started') {
                    this.showToast(`Recording started by ${data.recorded_by}`, 'info');
                } else if (data.type === 'recording_stopped') {
                    this.showToast('Recording stopped.', 'info');
                }
            }
        } catch (e) {
            // Not JSON, ignore
        }
    }

    // ─── Timer ───────────────────────────────────────────────────

    startTimer() {
        const timerEl = this.mount.querySelector('#class-timer');
        if (!timerEl) return;

        const startTime = Date.now();
        this.timerInterval = setInterval(() => {
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            const h = Math.floor(elapsed / 3600);
            const m = Math.floor((elapsed % 3600) / 60);
            const s = elapsed % 60;
            timerEl.textContent = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        }, 1000);
    }

    // ─── Cleanup ─────────────────────────────────────────────────

    async leaveRoom() {
        if (!window.confirm('Leave this classroom?')) return;

        if (this.room) {
            await this.room.disconnect();
            this.room = null;
        }

        if (this.timerInterval) {
            clearInterval(this.timerInterval);
        }

        this.connected = false;
        this.shell.exit();
    }

    handleDisconnect() {
        this.showToast('Connection lost. Reconnecting…', 'warning');
    }

    showError(message) {
        this.mount.innerHTML = `
            <div class="join-stage-card">
                <div class="p-10 text-center">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-red-500/10 border border-red-500/20 flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-[28px] text-red-400">error</span>
                    </div>
                    <p class="text-sm text-slate-300 font-medium mb-1">${this.escapeHtml(message)}</p>
                    <p class="text-[11px] text-slate-500 mb-5">Please check your connection and try again.</p>
                    <button onclick="location.reload()"
                        class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition-all shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/30">
                        Try Again
                    </button>
                </div>
            </div>
        `;
    }

    showToast(message, type = 'info') {
        const toasts = document.getElementById('room-toasts');
        if (!toasts) return;

        const toast = document.createElement('div');
        const colors = {
            info: 'bg-[#0f1520] border-white/[0.08] text-slate-200',
            success: 'bg-emerald-950/90 border-emerald-500/30 text-emerald-200',
            error: 'bg-red-950/90 border-red-500/30 text-red-200',
            warning: 'bg-amber-950/90 border-amber-500/30 text-amber-200',
        };

        toast.className = `pointer-events-auto flex items-start gap-3 px-4 py-3 rounded-xl border backdrop-blur-lg shadow-2xl text-xs font-medium transition-all duration-300 translate-x-4 opacity-0 ${colors[type] || colors.info}`;
        toast.innerHTML = `
            <span class="material-symbols-outlined text-[16px] flex-shrink-0 mt-px" aria-hidden="true">${{ info: 'info', success: 'check_circle', error: 'error', warning: 'warning' }[type] || 'info'}</span>
            <span class="flex-1 leading-relaxed">${this.escapeHtml(message)}</span>
        `;

        toasts.appendChild(toast);
        requestAnimationFrame(() => {
            toast.classList.remove('translate-x-4', 'opacity-0');
        });

        setTimeout(() => {
            toast.classList.add('translate-x-4', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    destroy() {
        this.destroyed = true;
        if (this.room) {
            this.room.disconnect();
            this.room = null;
        }
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
        }
    }
}
