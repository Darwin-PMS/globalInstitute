/**
 * LiveKit Video Stage Panel — Professional Classroom Video Experience
 *
 * Handles WebRTC video/audio via LiveKit. Renders the main video grid,
 * screen share, participant strip, and coordinates with the shell's
 * control bar and header.
 */
import {
    Room,
    RoomEvent,
    Track,
    ConnectionState,
    ParticipantEvent,
    TrackPublication,
    ConnectionQuality as LKConnectionQuality,
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
        this.isRecording = false;
        this.participants = new Map();
        this.destroyed = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectTimer = null;
        this.unsubscribers = [];
        this.recordingStartTime = null;
        this.recordingTimerInterval = null;
        this.controlsRegistered = false;

        this.permissionsState = { mic: 'unknown', camera: 'unknown' };
    }

    async render() {
        await this.checkPermissions();
        this.mount.innerHTML = this.renderJoinStage();
        this.bindJoinEvents();
        await this.startPreview();
    }

    async checkPermissions() {
        try {
            if (navigator.mediaDevices?.enumerateDevices) {
                const devices = await navigator.mediaDevices.enumerateDevices();
                this.permissionsState.mic = devices.some((d) => d.kind === 'audioinput' && d.deviceId) ? 'granted' : 'denied';
                this.permissionsState.camera = devices.some((d) => d.kind === 'videoinput' && d.deviceId) ? 'granted' : 'denied';
            }
        } catch {
            this.permissionsState.mic = 'unknown';
            this.permissionsState.camera = 'unknown';
        }
    }

    /* ═══════════════════════════════════════════════════════ JOIN SCREEN */

    renderJoinStage() {
        const { room, klass, me } = this.shell.snapshot();
        const isHost = me.role === 'host' || me.role === 'moderator';
        const startTime = this.formatScheduleTime(klass);

        return `
            <div class="cr-join-stage">
                <div class="cr-join-bg"></div>
                <div class="cr-join-content">
                    <div class="cr-join-ring">
                        <span class="material-symbols-outlined text-[36px] text-indigo-400">${isHost ? 'school' : 'person'}</span>
                    </div>

                    <div>
                        <h2 class="cr-join-title">${this.esc(klass.title)}</h2>
                        <p class="cr-join-subtitle">${this.esc(klass.context || '')}</p>
                    </div>

                    <div class="cr-join-meta">
                        <span class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined">schedule</span>
                            ${this.esc(startTime)}
                        </span>
                        <span class="w-1 h-1 rounded-full bg-slate-600"></span>
                        <span class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined">person</span>
                            ${this.esc(klass.host_name || 'Instructor')}
                        </span>
                    </div>

                    <div class="cr-join-preview" id="join-preview">
                        <div class="cr-video-avatar">
                            <div class="cr-avatar-circle">${this.getInitials(me.name || 'You')}</div>
                        </div>
                    </div>

                    <div class="cr-join-controls">
                        <button id="join-mic-toggle" class="cr-join-ctrl ${this.isMuted ? 'muted' : ''}"
                                data-tooltip="Toggle Microphone" aria-label="Toggle microphone">
                            <span class="material-symbols-outlined text-[20px]">${this.isMuted ? 'mic_off' : 'mic'}</span>
                        </button>
                        <button id="join-cam-toggle" class="cr-join-ctrl ${this.isCameraOff ? 'muted' : ''}"
                                data-tooltip="Toggle Camera" aria-label="Toggle camera">
                            <span class="material-symbols-outlined text-[20px]">${this.isCameraOff ? 'videocam_off' : 'videocam'}</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-emerald-500" style="position:relative">
                            <div class="absolute inset-[-3px] rounded-full bg-emerald-500/30" style="animation:crPulse 2s ease-in-out infinite"></div>
                        </div>
                        <span class="text-xs text-slate-400 font-medium" id="stage-online">${room.participant_count || 0} online</span>
                    </div>

                    <button id="livekit-join-btn" class="cr-join-btn">
                        <span class="material-symbols-outlined text-[20px]">videocam</span>
                        Join Classroom
                    </button>
                </div>
            </div>
        `;
    }

    formatScheduleTime(klass) {
        if (klass.time) return this.esc(klass.time);
        const start = klass.start_time || klass.starts_at;
        const end = klass.end_time || klass.ends_at;
        if (start) {
            let str = this.esc(start);
            if (end) str += ' – ' + this.esc(end);
            return str;
        }
        return 'Scheduled';
    }

    getInitials(name) {
        if (!name) return '?';
        return name.split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase();
    }

    bindJoinEvents() {
        this.mount.querySelector('#livekit-join-btn')?.addEventListener('click', () => this.joinRoom());

        this.mount.querySelector('#join-mic-toggle')?.addEventListener('click', async () => {
            this.isMuted = !this.isMuted;
            const btn = this.mount.querySelector('#join-mic-toggle');
            const icon = btn?.querySelector('.material-symbols-outlined');
            if (icon) icon.textContent = this.isMuted ? 'mic_off' : 'mic';
            btn?.classList.toggle('muted', this.isMuted);
            if (this.previewAudioTrack) {
                this.previewAudioTrack.enabled = !this.isMuted;
            }
        });

        this.mount.querySelector('#join-cam-toggle')?.addEventListener('click', async () => {
            this.isCameraOff = !this.isCameraOff;
            const btn = this.mount.querySelector('#join-cam-toggle');
            const icon = btn?.querySelector('.material-symbols-outlined');
            if (icon) icon.textContent = this.isCameraOff ? 'videocam_off' : 'videocam';
            btn?.classList.toggle('muted', this.isCameraOff);

            const preview = this.mount.querySelector('#join-preview');
            if (this.isCameraOff) {
                if (this.previewVideoTrack) {
                    this.previewVideoTrack.stop();
                    this.previewVideoTrack = null;
                }
                if (preview) {
                    preview.innerHTML = '<div class="cr-video-avatar"><div class="cr-avatar-circle">' + this.getInitials(this.shell.state.me?.name || 'You') + '</div></div>';
                }
            } else {
                await this.startPreview();
            }
        });
    }

    async startPreview() {
        if (this.isCameraOff) return;
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
            this.previewVideoTrack = stream.getVideoTracks()[0];
            const preview = this.mount.querySelector('#join-preview');
            if (preview && this.previewVideoTrack) {
                preview.innerHTML = '';
                const video = document.createElement('video');
                video.srcObject = new MediaStream([this.previewVideoTrack]);
                video.autoplay = true;
                video.muted = true;
                video.playsInline = true;
                video.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:16px';
                preview.appendChild(video);
            }
        } catch (err) {
            console.warn('Camera preview failed:', err);
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            this.previewAudioTrack = stream.getAudioTracks()[0];
            if (this.previewAudioTrack && this.isMuted) {
                this.previewAudioTrack.enabled = false;
            }
        } catch (err) {
            console.warn('Mic preview failed:', err);
        }
    }

    stopPreview() {
        if (this.previewVideoTrack) {
            this.previewVideoTrack.stop();
            this.previewVideoTrack = null;
        }
        if (this.previewAudioTrack) {
            this.previewAudioTrack.stop();
            this.previewAudioTrack = null;
        }
    }

    /* ═══════════════════════════════════════════ JOIN & CONNECT */

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
                    body: JSON.stringify({
                        mic_enabled: !this.isMuted,
                        cam_enabled: !this.isCameraOff,
                    }),
                }
            );
            const data = await res.json();
            if (!data.success) throw new Error(data.message);

            const payload = data.data || data;
            this.token = payload.token;
            this.livekitUrl = payload.livekit_url;
            this.permissions = payload.permissions;

            // Engage room presence tracking
            try { await this.api.enter(); } catch { /* best-effort */ }

            this.stopPreview();
            await this.connectToLiveKit();
        } catch (err) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined text-[20px]">videocam</span> Join Classroom';
            }
            this.showError(err.message || 'Unable to connect to classroom.');
        }
    }

    async connectToLiveKit() {
        this.renderConnecting();

        this.room = new Room({
            adaptiveStream: true,
            dynacast: true,
            audioCaptureDefaults: {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true,
            },
            videoCaptureDefaults: {
                resolution: { width: 1280, height: 720, frameRate: 30 },
            },
            reconnectPolicy: (retryCount) => {
                if (retryCount > this.maxReconnectAttempts) return -1;
                return Math.min(1000 * Math.pow(2, retryCount), 10000);
            },
        });

        this.room.on(RoomEvent.Connected, () => {
            this.connected = true;
            this.reconnectAttempts = 0;
            try {
                this.renderClassroom();
            } catch (e) {
                console.error('[LiveKit] renderClassroom error:', e);
            }
            this.updateConnectionQuality('excellent');
        });

        this.room.on(RoomEvent.Disconnected, () => {
            this.connected = false;
            this.handleDisconnect();
        });

        this.room.on(RoomEvent.Reconnecting, () => {
            this.updateConnectionQuality('reconnecting');
            this.showReconnectingOverlay();
        });

        this.room.on(RoomEvent.Reconnected, () => {
            this.connected = true;
            this.reconnectAttempts = 0;
            this.updateConnectionQuality('excellent');
            this.hideReconnectingOverlay();
            toast('Reconnected to classroom.', 'success');
        });

        this.room.on(RoomEvent.ParticipantConnected, (p) => this.onParticipantJoined(p));
        this.room.on(RoomEvent.ParticipantDisconnected, (p) => this.onParticipantLeft(p));
        this.room.on(RoomEvent.TrackSubscribed, (track, pub, participant) => this.onTrackSubscribed(track, pub, participant));
        this.room.on(RoomEvent.TrackUnsubscribed, (track, pub, participant) => this.onTrackUnsubscribed(track, pub, participant));
        this.room.on(RoomEvent.ActiveSpeakerChanged, (speaker) => this.onActiveSpeakerChanged(speaker));
        this.room.on(RoomEvent.DataReceived, (payload, participant, kind, topic) => this.onDataReceived(payload, participant, topic));

        this.room.on(RoomEvent.ConnectionQualityChanged, (quality, participant) => {
            if (participant?.isLocal) {
                this.updateConnectionQuality(this.mapQuality(quality));
            }
        });

        this.room.on(RoomEvent.RoomMetadataChanged, (metadata) => {
            try {
                const meta = JSON.parse(metadata);
                if (meta.active_screen_sharing !== undefined) {
                    this.handleScreenShareStateChange(meta.active_screen_sharing);
                }
            } catch { /* not JSON */ }
        });

        await this.room.connect(this.livekitUrl, this.token);

        // Publish local tracks based on pre-join choices
        await this.room.localParticipant.setCameraEnabled(!this.isCameraOff);
        await this.room.localParticipant.setMicrophoneEnabled(!this.isMuted);

        // Attach local tracks to DOM (local tracks don't fire TrackSubscribed)
        this.attachLocalTracks();

        // Register shell control listeners ONCE
        this.registerShellControls();

        // Sync initial control bar state to match pre-join choices
        this.syncControlBarState();

        // Enable the control bar buttons
        this.shell.setControlsEnabled(true);
    }

    attachLocalTracks() {
        if (!this.room?.localParticipant) return;
        const pubs = typeof this.room.localParticipant.getTrackPublications === 'function'
            ? this.room.localParticipant.getTrackPublications()
            : [];
        pubs.forEach((pub) => {
            if (pub.track) {
                this.attachTrack(pub.track, pub, this.room.localParticipant);
            }
        });
        // Also listen for future local track publications
        this.room.localParticipant.on(ParticipantEvent.TrackPublished, (pub) => {
            if (pub.track) {
                this.attachTrack(pub.track, pub, this.room.localParticipant);
            }
        });
    }

    /* ═══════════════════════════════════════════════════════ CLASSROOM UI */

    renderConnecting() {
        this.mount.innerHTML = `
            <div class="cr-video-grid" style="display:flex;align-items:center;justify-content:center">
                <div class="text-center space-y-4">
                    <div style="width:40px;height:40px;margin:0 auto;border:3px solid var(--cr-border);border-top-color:var(--cr-indigo);border-radius:50%;animation:crSpin 0.8s linear infinite"></div>
                    <div>
                        <p class="text-sm font-semibold text-white">Connecting to classroom…</p>
                        <p class="text-xs text-slate-400 mt-1">Establishing video connection</p>
                    </div>
                </div>
            </div>
        `;
    }

    renderClassroom() {
        const { room, klass, me } = this.shell.snapshot();

        this.mount.innerHTML = `
            <div class="cr-video-grid" id="cr-video-grid">
                <div id="speaker-view" class="absolute inset-0"></div>
                <div id="participant-strip" class="cr-participant-strip"></div>

                <div id="video-empty" class="absolute inset-0 flex items-center justify-center" style="z-index:1">
                    <div class="text-center space-y-3">
                        <div class="w-14 h-14 mx-auto rounded-2xl bg-white/[0.03] border border-white/[0.06] flex items-center justify-center">
                            <span class="material-symbols-outlined text-2xl text-slate-500">videocam</span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium">Waiting for video feeds…</p>
                    </div>
                </div>

                <div id="screen-share-view" class="cr-screen-share" style="display:none;z-index:2"></div>
                <div id="pip-view" class="cr-pip" style="display:none"></div>

                <div id="reconnecting-overlay" class="cr-reconnecting-overlay" style="display:none">
                    <div style="width:32px;height:32px;border:3px solid var(--cr-border);border-top-color:var(--cr-indigo);border-radius:50%;animation:crSpin 0.8s linear infinite"></div>
                    <p class="text-sm font-semibold text-white">Connection interrupted</p>
                    <p class="text-xs text-slate-400">Reconnecting…</p>
                </div>

                <div id="tile-controls" class="absolute top-3 right-3 flex gap-2" style="z-index:5;display:none">
                    <button id="ctrl-fullscreen" class="cr-ctrl-btn" style="width:36px;height:36px;background:rgba(0,0,0,0.5);backdrop-filter:blur(8px)"
                            data-tooltip="Fullscreen (F)" aria-label="Toggle fullscreen">
                        <span class="material-symbols-outlined text-[18px]">fullscreen</span>
                    </button>
                    <button id="ctrl-pip" class="cr-ctrl-btn" style="width:36px;height:36px;background:rgba(0,0,0,0.5);backdrop-filter:blur(8px)"
                            data-tooltip="Picture-in-Picture" aria-label="Picture in picture">
                        <span class="material-symbols-outlined text-[18px]">picture_in_picture_alt</span>
                    </button>
                </div>
            </div>
        `;

        this.bindStageControls();
        this.renderExistingParticipants();
        this.setupGridHover();
    }

    bindStageControls() {
        this.mount.querySelector('#ctrl-fullscreen')?.addEventListener('click', () => this.toggleFullscreen());
        this.mount.querySelector('#ctrl-pip')?.addEventListener('click', () => this.togglePiP());
    }

    registerShellControls() {
        if (this.controlsRegistered) return;
        this.controlsRegistered = true;

        this.unsubscribers.push(this.shell.on('control:microphone', () => this.toggleMic()));
        this.unsubscribers.push(this.shell.on('control:camera', () => this.toggleCamera()));
        this.unsubscribers.push(this.shell.on('control:screenShare', () => this.toggleScreenShare()));
        this.unsubscribers.push(this.shell.on('control:handRaise', () => this.toggleHandRaise()));
        this.unsubscribers.push(this.shell.on('control:recording', () => this.toggleRecording()));
        this.unsubscribers.push(this.shell.on('control:fullscreen', () => this.toggleFullscreen()));
    }

    syncControlBarState() {
        this.shell.updateControlBtn('ctrl-mic', {
            muted: this.isMuted,
            icon: this.isMuted ? 'mic_off' : 'mic',
        });
        this.shell.updateControlBtn('ctrl-cam', {
            muted: this.isCameraOff,
            icon: this.isCameraOff ? 'videocam_off' : 'videocam',
        });
    }

    setupGridHover() {
        const grid = this.mount.querySelector('#cr-video-grid');
        const controls = this.mount.querySelector('#tile-controls');
        if (!grid || !controls) return;

        let hideTimer;
        grid.addEventListener('mousemove', () => {
            controls.style.display = 'flex';
            clearTimeout(hideTimer);
            hideTimer = setTimeout(() => { controls.style.display = 'none'; }, 3000);
        });
        grid.addEventListener('mouseleave', () => {
            controls.style.display = 'none';
        });
    }

    /* ═══════════════════════════════════════════════════ MEDIA CONTROLS */

    async toggleMic() {
        if (!this.room) return;
        const enabled = this.isMuted;
        await this.room.localParticipant.setMicrophoneEnabled(enabled);
        this.isMuted = !enabled;
        this.shell.updateControlBtn('ctrl-mic', {
            muted: this.isMuted,
            icon: this.isMuted ? 'mic_off' : 'mic',
        });
    }

    async toggleCamera() {
        if (!this.room) return;
        const enabled = this.isCameraOff;
        await this.room.localParticipant.setCameraEnabled(enabled);
        this.isCameraOff = !enabled;
        this.shell.updateControlBtn('ctrl-cam', {
            muted: this.isCameraOff,
            icon: this.isCameraOff ? 'videocam_off' : 'videocam',
        });
    }

    async toggleScreenShare() {
        if (!this.room) return;

        if (this.isScreenSharing) {
            await this.room.localParticipant.setScreenShareEnabled(false);
            this.isScreenSharing = false;
            this.shell.updateControlBtn('ctrl-screen', { active: false, icon: 'screen_share' });
            this.hideScreenShare();
        } else {
            try {
                await this.room.localParticipant.setScreenShareEnabled(true, { audio: true });
                this.isScreenSharing = true;
                this.shell.updateControlBtn('ctrl-screen', { active: true, icon: 'stop_screen_share' });
            } catch (err) {
                console.warn('Screen share failed:', err);
                toast('Screen sharing is not supported or was denied.', 'error');
            }
        }
    }

    async toggleHandRaise() {
        this.isHandRaised = !this.isHandRaised;
        this.shell.updateControlBtn('ctrl-hand', {
            active: this.isHandRaised,
            icon: this.isHandRaised ? 'back_hand' : 'pan_tool',
        });

        const onlineClassId = this.shell.state.klass?.id;
        const endpoint = this.isHandRaised ? 'raise-hand' : 'lower-hand';
        try {
            await fetch(`${this.api.liveClassesBase}/${onlineClassId}/${endpoint}`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.api.csrfToken,
                },
                credentials: 'same-origin',
            });
        } catch (err) {
            console.warn('Hand raise notification failed:', err);
        }
    }

    async toggleRecording() {
        const onlineClassId = this.shell.state.klass?.id;
        const wasRecording = this.isRecording;

        try {
            const endpoint = wasRecording ? 'recording/stop' : 'recording/start';
            const res = await fetch(`${this.api.liveClassesBase}/${onlineClassId}/${endpoint}`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.api.csrfToken,
                },
                credentials: 'same-origin',
            });
            const data = await res.json();

            if (data.success) {
                if (wasRecording) {
                    this.isRecording = false;
                    this.stopRecordingTimer();
                    this.shell.updateControlBtn('ctrl-record', { active: false });
                    this.hideRecordingIndicator();
                    toast('Recording stopped.', 'info');
                } else {
                    this.isRecording = true;
                    this.recordingStartTime = Date.now();
                    this.startRecordingTimer();
                    this.shell.updateControlBtn('ctrl-record', { active: true });
                    this.showRecordingIndicator();
                    toast('Recording started.', 'success');
                }
            }
        } catch (err) {
            toast('Failed to toggle recording.', 'error');
        }
    }

    /* ═══════════════════════════════════════════════════ FULLSCREEN */

    async toggleFullscreen() {
        const grid = this.mount.querySelector('#cr-video-grid');
        if (!grid) return;
        if (document.fullscreenElement) {
            await document.exitFullscreen();
        } else {
            await grid.requestFullscreen();
        }
    }

    /* ═══════════════════════════════════════════════════ PiP */

    togglePiP() {
        const pip = this.mount.querySelector('#pip-view');
        const speaker = this.mount.querySelector('#speaker-view');
        if (!pip || !speaker) return;

        if (pip.style.display === 'none') {
            const localVideo = speaker.querySelector('video[data-local="true"]');
            if (localVideo) {
                pip.innerHTML = '';
                const clone = localVideo.cloneNode(true);
                pip.appendChild(clone);
                pip.style.display = 'block';
            }
        } else {
            pip.style.display = 'none';
            pip.innerHTML = '';
        }
    }

    /* ═══════════════════════════════════════════════ RECORDING INDICATOR */

    showRecordingIndicator() {
        const recEl = document.getElementById('ctrl-recording');
        if (recEl) recEl.style.display = 'inline-flex';
    }

    hideRecordingIndicator() {
        const recEl = document.getElementById('ctrl-recording');
        if (recEl) recEl.style.display = 'none';
    }

    startRecordingTimer() {
        const timerEl = document.getElementById('ctrl-rec-timer');
        if (!timerEl) return;
        this.recordingTimerInterval = setInterval(() => {
            const elapsed = Math.floor((Date.now() - this.recordingStartTime) / 1000);
            const h = Math.floor(elapsed / 3600);
            const m = Math.floor((elapsed % 3600) / 60);
            const s = elapsed % 60;
            timerEl.textContent = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        }, 1000);
    }

    stopRecordingTimer() {
        if (this.recordingTimerInterval) {
            clearInterval(this.recordingTimerInterval);
            this.recordingTimerInterval = null;
        }
    }

    /* ═══════════════════════════════════════════ SCREEN SHARE */

    handleScreenShareStateChange(active) {
        if (!active) this.hideScreenShare();
    }

    showScreenShare(track) {
        const view = this.mount.querySelector('#screen-share-view');
        if (!view) return;
        view.innerHTML = '';
        const element = track.attach();
        element.style.maxWidth = '100%';
        element.style.maxHeight = '100%';
        element.style.objectFit = 'contain';
        view.appendChild(element);
        view.style.display = 'flex';
    }

    hideScreenShare() {
        const view = this.mount.querySelector('#screen-share-view');
        if (view) {
            view.style.display = 'none';
            view.innerHTML = '';
        }
    }

    /* ═════════════════════════════════════════════ RECONNECTION */

    handleDisconnect() {
        this.updateConnectionQuality('lost');
        this.showReconnectingOverlay();
        if (!this.destroyed) this.reconnectAttempts++;
    }

    showReconnectingOverlay() {
        const overlay = this.mount.querySelector('#reconnecting-overlay');
        if (overlay) overlay.style.display = 'flex';
    }

    hideReconnectingOverlay() {
        const overlay = this.mount.querySelector('#reconnecting-overlay');
        if (overlay) overlay.style.display = 'none';
    }

    /* ══════════════════════════════════════════ CONNECTION QUALITY */

    mapQuality(quality) {
        const map = {
            [LKConnectionQuality.EXCELLENT]: 'excellent',
            [LKConnectionQuality.GOOD]: 'good',
            [LKConnectionQuality.POOR]: 'poor',
            [LKConnectionQuality.LOST]: 'lost',
        };
        return map[quality] || 'good';
    }

    updateConnectionQuality(quality) {
        const badge = document.getElementById('header-connection');
        if (badge) badge.setAttribute('data-quality', quality);
        const label = document.getElementById('header-connection-label');
        if (label) {
            const labels = { excellent: 'Excellent', good: 'Good', poor: 'Poor', reconnecting: 'Reconnecting…', lost: 'Disconnected' };
            label.textContent = labels[quality] || quality;
        }
    }

    /* ═════════════════════════════════════════ PARTICIPANT TRACKS */

    onTrackSubscribed(track, publication, participant) {
        this.attachTrack(track, publication, participant);
    }

    attachTrack(track, publication, participant) {
        const identity = participant.identity;
        const isLocal = participant.isLocal;

        const empty = this.mount.querySelector('#video-empty');
        if (empty) empty.remove();

        if (track.kind === Track.Kind.Video) {
            const element = track.attach();
            element.className = 'w-full h-full object-cover';
            if (isLocal) element.setAttribute('data-local', 'true');

            if (publication.source === Track.Source.Camera) {
                const containerId = isLocal ? 'local-camera' : `participant-${identity}`;
                let container = this.mount.querySelector(`#${containerId}`);
                if (!container) {
                    container = document.createElement('div');
                    container.id = containerId;
                    container.className = 'cr-video-tile';
                    this.mount.querySelector('#speaker-view')?.appendChild(container);
                }
                container.innerHTML = '';
                container.appendChild(element);
                if (!isLocal) this.addTileOverlay(container, participant);
            } else if (publication.source === Track.Source.ScreenShare) {
                this.showScreenShare(track);
            }
        } else if (track.kind === Track.Kind.Audio) {
            const element = track.attach();
            element.id = `audio-${identity}`;
            if (!this.mount.querySelector(`#audio-${identity}`)) {
                this.mount.appendChild(element);
            }
        }
    }

    onTrackUnsubscribed(track, publication, participant) {
        const elements = track.detach();
        elements.forEach((el) => el.remove());
        if (publication.source === Track.Source.ScreenShare) this.hideScreenShare();
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
        const participants = this.room.remoteParticipants;
        if (!participants || typeof participants.forEach !== 'function') return;
        participants.forEach((participant) => {
            this.participants.set(participant.identity, participant);
            const pubs = typeof participant.getTrackPublications === 'function'
                ? participant.getTrackPublications()
                : [];
            pubs.forEach((publication) => {
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

        if (this.room?.localParticipant) {
            const localTile = this.createStripTile(this.room.localParticipant, true);
            if (localTile) strip.appendChild(localTile);
        }

        this.participants.forEach((participant) => {
            const tile = this.createStripTile(participant, false);
            if (tile) strip.appendChild(tile);
        });
    }

    createStripTile(participant, isLocal) {
        const tile = document.createElement('div');
        tile.className = 'cr-strip-tile';
        if (participant.isSpeaking) tile.classList.add('speaking');

        const pubs = typeof participant.getTrackPublications === 'function'
            ? participant.getTrackPublications()
            : [];
        pubs.forEach((pub) => {
            if (pub.track?.kind === Track.Kind.Video && pub.source === Track.Source.Camera) {
                const el = pub.track.attach();
                el.className = 'w-full h-full object-cover';
                tile.appendChild(el);
            }
        });

        const name = document.createElement('div');
        name.className = 'cr-strip-name';
        name.textContent = isLocal ? 'You' : (participant.name || participant.identity.split('_').pop());
        tile.appendChild(name);

        tile.addEventListener('click', () => this.swapToMainParticipant(participant));
        return tile;
    }

    swapToMainParticipant(participant) {
        const speakerView = this.mount.querySelector('#speaker-view');
        if (!speakerView) return;
        const tile = this.mount.querySelector(`#participant-${participant.identity}`);
        if (tile) {
            const video = tile.querySelector('video');
            if (video) {
                speakerView.innerHTML = '';
                const container = document.createElement('div');
                container.id = `participant-${participant.identity}`;
                container.className = 'cr-video-tile';
                container.appendChild(video.cloneNode(true));
                this.addTileOverlay(container, participant);
                speakerView.appendChild(container);
            }
        }
    }

    addTileOverlay(container, participant) {
        const name = participant.name || participant.identity.split('_').pop();
        const overlay = document.createElement('div');
        overlay.className = 'cr-tile-overlay';
        overlay.innerHTML = `
            <div class="cr-tile-name">
                <span class="material-symbols-outlined" style="font-size:12px;opacity:0.7">mic</span>
                ${this.esc(name)}
            </div>
        `;
        container.appendChild(overlay);
    }

    onActiveSpeakerChanged(speaker) {
        const allTiles = this.mount.querySelectorAll('.cr-video-tile, .cr-strip-tile');
        allTiles.forEach((t) => t.classList.remove('speaking'));
        if (speaker && !speaker.isLocal) {
            const tile = this.mount.querySelector(`#participant-${speaker.identity}`);
            if (tile) tile.classList.add('speaking');
        }
    }

    onDataReceived(payload, participant, topic) {
        try {
            const data = JSON.parse(new TextDecoder().decode(payload));
            if (topic === 'hand-raise' && data.type === 'hand_raised') {
                toast(`${data.name} raised their hand`, 'info');
            } else if (topic === 'recording') {
                if (data.type === 'recording_started') {
                    this.isRecording = true;
                    this.showRecordingIndicator();
                    this.shell.updateControlBtn('ctrl-record', { active: true });
                } else if (data.type === 'recording_stopped') {
                    this.isRecording = false;
                    this.hideRecordingIndicator();
                    this.shell.updateControlBtn('ctrl-record', { active: false });
                }
            }
        } catch { /* not JSON */ }
    }

    /* ═══════════════════════════════════════════════ ERRORS */

    showError(message) {
        this.mount.innerHTML = `
            <div class="cr-video-grid" style="display:flex;align-items:center;justify-content:center">
                <div class="cr-error">
                    <div class="cr-error-icon">
                        <span class="material-symbols-outlined text-[28px] text-red-400">error</span>
                    </div>
                    <p class="text-sm text-slate-300 font-medium mb-1">${this.esc(message)}</p>
                    <p class="text-[11px] text-slate-500 mb-5">Please check your connection and try again.</p>
                    <button onclick="location.reload()" class="cr-join-btn" style="font-size:12px">Try Again</button>
                </div>
            </div>
        `;
    }

    /* ═════════════════════════════════════════════════ CLEANUP */

    async leaveRoom() {
        if (this.room) {
            await this.room.disconnect();
            this.room = null;
        }
        this.stopRecordingTimer();
        this.connected = false;
        this.unsubscribers.forEach((unsub) => unsub());
        this.unsubscribers = [];
        this.controlsRegistered = false;
    }

    destroy() {
        this.destroyed = true;
        if (this.room) {
            this.room.disconnect();
            this.room = null;
        }
        this.stopRecordingTimer();
        this.unsubscribers.forEach((unsub) => unsub());
        this.unsubscribers = [];
        this.controlsRegistered = false;
    }

    esc(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}

function toast(message, type = 'info') {
    const host = document.getElementById('room-toasts');
    if (!host) return;
    const tones = {
        success: 'bg-emerald-950/90 border-emerald-500/30 text-emerald-100',
        error: 'bg-red-950/90 border-red-500/30 text-red-100',
        warning: 'bg-amber-950/90 border-amber-500/30 text-amber-100',
        info: 'bg-[#0f1520]/90 border-white/[0.08] text-slate-100',
    };
    const icons = { success: 'check_circle', error: 'error', warning: 'warning', info: 'info' };
    const el = document.createElement('div');
    el.className = `pointer-events-auto flex items-start gap-3 px-4 py-3 rounded-xl border backdrop-blur-lg shadow-2xl text-xs font-medium transition-all duration-300 translate-x-4 opacity-0 ${tones[type] || tones.info}`;
    el.innerHTML = `
        <span class="material-symbols-outlined text-[16px] flex-shrink-0 mt-px" aria-hidden="true">${icons[type] || 'info'}</span>
        <span class="flex-1 leading-relaxed">${message}</span>
    `;
    host.appendChild(el);
    requestAnimationFrame(() => el.classList.remove('translate-x-4', 'opacity-0'));
    setTimeout(() => {
        el.classList.add('translate-x-4', 'opacity-0');
        setTimeout(() => el.remove(), 300);
    }, 4000);
}
