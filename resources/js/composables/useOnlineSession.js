import { nextTick, onBeforeUnmount, ref } from 'vue';
import axios from 'axios';

export function useOnlineSession({ role, token, sessionId }) {
    const localVideo = ref(null);
    const remoteVideo = ref(null);
    const status = ref('connecting');
    const error = ref('');
    const mediaWarning = ref('');
    const controlError = ref('');
    const connectionNotice = ref('');
    const participantNotice = ref('');
    const chatUnread = ref(false);
    const entryRequest = ref(false);
    const waitingForApproval = ref(false);
    const entryApproved = ref(false);
    const entryBlocked = ref(false);
    const sessionSeconds = ref(0);
    const microphoneEnabled = ref(true);
    const cameraEnabled = ref(true);
    const remoteMicrophoneEnabled = ref(true);
    const remoteCameraEnabled = ref(true);
    const audioInputs = ref([]);
    const videoInputs = ref([]);
    const messages = ref([]);
    let localStream = null;
    let peerConnection = null;
    let channel = null;
    let pendingIceCandidates = [];
    let makingOffer = false;
    let setupPhase = 'início';
    let sessionTimer = null;
    let participantNoticeTimer = null;
    let sessionStartedAt = null;
    let currentSession = null;
    let notificationAudioContext = null;
    let patientPresenceTimer = null;
    let approvalRequestTimer = null;
    let approvalRequestInFlight = false;
    let patientApprovalSent = false;
    let patientApprovalPollingTimer = null;
    let patientApprovalPollingInFlight = false;
    const connectionId = typeof crypto?.randomUUID === 'function'
        ? crypto.randomUUID()
        : `connection-${Date.now()}-${Math.random().toString(36).slice(2)}`;

    const playNotificationSound = async () => {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;

            notificationAudioContext ??= new AudioContext();
            if (notificationAudioContext.state === 'suspended') await notificationAudioContext.resume();

            const now = notificationAudioContext.currentTime;
            const oscillator = notificationAudioContext.createOscillator();
            const gain = notificationAudioContext.createGain();
            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(880, now);
            oscillator.frequency.setValueAtTime(660, now + 0.08);
            gain.gain.setValueAtTime(0.0001, now);
            gain.gain.exponentialRampToValueAtTime(0.08, now + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.18);
            oscillator.connect(gain);
            gain.connect(notificationAudioContext.destination);
            oscillator.start(now);
            oscillator.stop(now + 0.2);
        } catch {
            // O navegador pode bloquear áudio iniciado sem interação do usuário.
        }
    };

    const showParticipantNotice = (message) => {
        participantNotice.value = message;
        messages.value.push({ author: 'Sistema', message });
        chatUnread.value = true;
        void playNotificationSound();
        window.clearTimeout(participantNoticeTimer);
        participantNoticeTimer = window.setTimeout(() => {
            participantNotice.value = '';
        }, 5000);
    };

    const startSessionTimer = () => {
        if (sessionStartedAt) return;
        sessionStartedAt = Date.now();
        sessionTimer = window.setInterval(() => {
            sessionSeconds.value = Math.floor((Date.now() - sessionStartedAt) / 1000);
        }, 1000);
    };

    const stopSessionTimer = () => {
        window.clearInterval(sessionTimer);
        sessionTimer = null;
        sessionStartedAt = null;
    };

    const setStatus = (nextStatus, source) => {
        console.info('[online-session] status transition', {
            from: status.value,
            to: nextStatus,
            source,
        });
        status.value = nextStatus;
    };

    const handleOffline = () => {
        connectionNotice.value = 'Sua conexão com a internet foi interrompida.';
        if (status.value === 'active' || status.value === 'waiting') {
            setStatus('reconnecting', 'browser offline');
        }
    };

    const handleOnline = () => {
        connectionNotice.value = 'Conexão restaurada. Verificando a sala...';
        if (status.value === 'reconnecting') setStatus('waiting', 'browser online');
    };

    const signalEndpoint = () => role === 'patient'
        ? '/api/online-sessions/join/' + token + '/signal'
        : '/api/online-sessions/' + sessionId + '/signal';

    const iceServersEndpoint = () => role === 'patient'
        ? '/api/online-sessions/join/' + token + '/ice-servers'
        : '/api/online-sessions/' + sessionId + '/ice-servers';

    const requestIceServers = async () => {
        try {
            const { data } = await axios.get(iceServersEndpoint());
            return Array.isArray(data?.ice_servers) ? data.ice_servers : [];
        } catch (cause) {
            console.warn('[online-session] TURN servers unavailable', {
                status: cause?.response?.status,
            });
            return [];
        }
    };

    const setupSignalChannel = (session) => {
        if (!window.Echo || !session.channel) {
            console.error('[online-session] echo channel unavailable', {
                echo: Boolean(window.Echo),
                channelPresent: Boolean(session?.channel),
            });
            return false;
        }

        console.info('[online-session] echo channel setup', {
            echo: true,
            channelPresent: true,
            echoState: window.Echo.connector?.pusher?.connection?.state,
        });
        channel = window.Echo.channel(session.channel);
        channel.listen('.online-session.signal', (signal) => {
            void handleSignal(signal).catch((cause) => {
                handleSignalError(cause, signal?.type);
            });
        });
        return true;
    };

    const sendSignal = async (type, payload = {}) => {
        const socketId = window.Echo?.socketId?.();
        const signalPayload = role === 'patient'
            ? { ...payload, connection_id: connectionId }
            : payload;
        console.info('[online-session] signal request', {
            type,
            socketIdPresent: Boolean(socketId),
        });
        try {
            const response = await axios.post(signalEndpoint(), { type, payload: signalPayload }, {
                headers: socketId ? { 'X-Socket-ID': socketId } : {},
            });
            console.info('[online-session] signal sent', {
                type,
                status: response.status,
            });
        } catch (cause) {
            console.error('[online-session] signal failed', {
                type,
                name: cause?.name,
                code: cause?.code,
                status: cause?.response?.status,
            });
            throw cause;
        }
    };

    const presencePayload = (state) => ({
        role,
        state,
        ...(role === 'patient' ? { connection_id: connectionId } : {}),
    });

    const startPatientPresenceHeartbeat = () => {
        window.clearInterval(patientPresenceTimer);
        if (role !== 'patient') return;

        patientPresenceTimer = window.setInterval(() => {
            if (channel) void sendSignal('presence', presencePayload('heartbeat')).catch(() => {});
        }, 20000);
    };

    const refreshApprovalRequest = async () => {
        if (role !== 'psychologist' || !currentSession?.id || patientApprovalSent || approvalRequestInFlight) return;

        approvalRequestInFlight = true;
        try {
            const { data } = await axios.get('/api/online-sessions/' + currentSession.id);
            if (!data.patient_connection_active && status.value === 'active') {
                entryRequest.value = false;
                resetParticipantConnection('patient connection expired');
            } else if (data.patient_waiting_for_approval) {
                entryRequest.value = true;
                console.info('[online-session] patient approval request confirmed by polling');
            }
        } catch (cause) {
            console.warn('[online-session] approval request polling failed', {
                status: cause?.response?.status,
            });
        } finally {
            approvalRequestInFlight = false;
        }
    };

    const startApprovalRequestPolling = () => {
        window.clearInterval(approvalRequestTimer);
        if (role !== 'psychologist') return;

        void refreshApprovalRequest();
        approvalRequestTimer = window.setInterval(() => {
            void refreshApprovalRequest();
        }, 3000);
    };

    const resetParticipantConnection = (source) => {
        peerConnection?.close();
        peerConnection = null;
        pendingIceCandidates = [];
        makingOffer = false;
        if (remoteVideo.value) remoteVideo.value.srcObject = null;
        stopSessionTimer();
        remoteMicrophoneEnabled.value = true;
        remoteCameraEnabled.value = true;
        if (status.value !== 'waiting') setStatus('waiting', source);
    };

    const acceptPatientEntry = async () => {
        if (role !== 'patient' || !waitingForApproval.value || entryApproved.value) return;

        waitingForApproval.value = false;
        entryApproved.value = true;
        showParticipantNotice('Sua entrada foi autorizada pelo psicólogo.');
        console.info('[online-session] patient entry approved');
        if (currentSession) await start(currentSession);
    };

    const refreshPatientApproval = async () => {
        if (role !== 'patient' || !token || !waitingForApproval.value || patientApprovalPollingInFlight) return;

        patientApprovalPollingInFlight = true;
        try {
            const { data } = await axios.get('/api/online-sessions/join/' + token);
            if (data.entry_approved) await acceptPatientEntry();
        } catch (cause) {
            console.warn('[online-session] patient approval polling failed', {
                status: cause?.response?.status,
            });
        } finally {
            patientApprovalPollingInFlight = false;
        }
    };

    const startPatientApprovalPolling = () => {
        window.clearInterval(patientApprovalPollingTimer);
        if (role !== 'patient') return;

        void refreshPatientApproval();
        patientApprovalPollingTimer = window.setInterval(() => {
            void refreshPatientApproval();
        }, 3000);
    };

    const createOffer = async () => {
        if (!peerConnection || makingOffer || peerConnection.signalingState !== 'stable' || peerConnection.remoteDescription) {
            console.info('[online-session] offer skipped', {
                hasPeerConnection: Boolean(peerConnection),
                makingOffer,
                signalingState: peerConnection?.signalingState,
                hasRemoteDescription: Boolean(peerConnection?.remoteDescription),
            });
            return;
        }

        makingOffer = true;
        try {
            const offer = await peerConnection.createOffer();
            await peerConnection.setLocalDescription(offer);
            await sendSignal('offer', {
                description: {
                    type: offer.type,
                    sdp: offer.sdp,
                },
            });
        } finally {
            makingOffer = false;
        }
    };

    const normalizeSessionDescription = (description) => {
        if (!description) return null;

        const sdp = typeof description.sdp === 'string'
            ? description.sdp.replace(/\r?\n/g, '\r\n').trimEnd() + '\r\n'
            : null;

        if (!description.type || !sdp) return null;

        return { type: description.type, sdp };
    };

    const attachStream = async (videoElement, stream, target) => {
        if (!videoElement || !stream) {
            console.error('[online-session] video attachment failed', {
                target,
                hasElement: Boolean(videoElement),
                hasStream: Boolean(stream),
            });
            return;
        }
        videoElement.srcObject = stream;
        try {
            await videoElement.play();
        } catch {
            // O navegador pode aguardar uma interação mesmo com muted/autoplay.
        }
    };

    const refreshDevices = async () => {
        if (!navigator.mediaDevices?.enumerateDevices) return;

        const devices = await navigator.mediaDevices.enumerateDevices();
        audioInputs.value = devices
            .filter((device) => device.kind === 'audioinput')
            .map((device, index) => ({ id: device.deviceId, label: device.label || `Microfone ${index + 1}` }));
        videoInputs.value = devices
            .filter((device) => device.kind === 'videoinput')
            .map((device, index) => ({ id: device.deviceId, label: device.label || `Câmera ${index + 1}` }));
    };

    const toggleTrack = (kind) => {
        const track = localStream?.getTracks().find((item) => item.kind === kind);
        if (!track) return false;

        track.enabled = !track.enabled;
        if (kind === 'audio') microphoneEnabled.value = track.enabled;
        if (kind === 'video') cameraEnabled.value = track.enabled;
        console.info('[online-session] track toggled', { kind, enabled: track.enabled });
        void sendMediaState().catch((cause) => handleSignalError(cause, 'media-state'));
        return true;
    };

    const sendMediaState = async () => {
        if (!localStream) return;
        await sendSignal('media-state', {
            role,
            audioEnabled: microphoneEnabled.value,
            videoEnabled: cameraEnabled.value,
        });
    };

    const replaceTrack = async (kind, deviceId) => {
        if (!deviceId || !localStream) return;

        controlError.value = '';
        try {
            const constraints = kind === 'audio'
                ? { audio: { deviceId: { exact: deviceId } }, video: false }
                : { audio: false, video: { deviceId: { exact: deviceId } } };
            const nextStream = await navigator.mediaDevices.getUserMedia(constraints);
            const nextTrack = nextStream.getTracks()[0];
            const currentTrack = localStream.getTracks().find((track) => track.kind === kind);
            const sender = peerConnection?.getSenders().find((item) => item.track?.kind === kind);

            if (sender) await sender.replaceTrack(nextTrack);
            if (currentTrack) {
                localStream.removeTrack(currentTrack);
                currentTrack.stop();
            }
            localStream.addTrack(nextTrack);

            if (kind === 'audio') microphoneEnabled.value = nextTrack.enabled;
            if (kind === 'video') {
                cameraEnabled.value = nextTrack.enabled;
                await attachStream(localVideo.value, localStream, 'local');
            }
            await refreshDevices();
            await sendMediaState();
            console.info('[online-session] device changed', { kind, replacedTrack: Boolean(sender) });
        } catch (cause) {
            console.error('[online-session] device change failed', {
                kind,
                name: cause?.name,
                code: cause?.code,
            });
            controlError.value = kind === 'audio'
                ? 'Não foi possível trocar o microfone.'
                : 'Não foi possível trocar a câmera.';
        }
    };

    const flushPendingIceCandidates = async () => {
        if (!peerConnection?.remoteDescription || !pendingIceCandidates.length) return;

        const candidates = pendingIceCandidates;
        pendingIceCandidates = [];
        await Promise.all(candidates.map((candidate) => peerConnection.addIceCandidate(candidate)));
    };

    const handleSignal = async ({ type, payload }) => {
        if (!payload || (!peerConnection && !['presence', 'entry-approved'].includes(type))) {
            console.info('[online-session] signal ignored', {
                type,
                hasPeerConnection: Boolean(peerConnection),
                hasPayload: Boolean(payload),
            });
            return;
        }
        console.info('[online-session] signal received', {
            type,
            role,
            payloadRole: type === 'presence' ? payload.role : undefined,
        });

        if (type === 'presence' && payload.role && payload.role !== role) {
            const participantLabel = payload.role === 'patient' ? 'Paciente' : 'Psicólogo';
            showParticipantNotice(payload.state === 'left'
                ? `${participantLabel} saiu da sala.`
                : `${participantLabel} entrou na sala.`);
            if (payload.state === 'left') {
                if (role === 'psychologist') {
                    entryRequest.value = false;
                    resetParticipantConnection('participant left');
                }
                return;
            }
        }

        if (type === 'entry-approved' && role === 'patient' && payload.role === 'psychologist') {
            await acceptPatientEntry();
        } else if (type === 'presence' && role === 'psychologist' && payload.role === 'patient' && payload.state === 'requesting') {
            entryRequest.value = true;
            console.info('[online-session] patient entry requested');
        } else if (type === 'presence' && role === 'psychologist' && payload.role === 'patient' && payload.state === 'joined') {
            entryRequest.value = false;
            console.info('[online-session] patient presence accepted');
            await createOffer();
            console.info('[online-session] offer sent');
        } else if (type === 'presence' && role === 'patient' && payload.role === 'psychologist') {
            console.info('[online-session] psychologist presence received', {
                waitingForApproval: waitingForApproval.value,
            });
            if (waitingForApproval.value) {
                await sendSignal('presence', presencePayload('requesting'));
            } else {
                await sendSignal('presence', presencePayload('joined'));
            }
        } else if (type === 'offer' && role === 'patient') {
            const description = normalizeSessionDescription(payload.description);
            if (!description || peerConnection.remoteDescription) return;

            await peerConnection.setRemoteDescription(description);
            await flushPendingIceCandidates();
            const answer = await peerConnection.createAnswer();
            await peerConnection.setLocalDescription(answer);
            await sendSignal('answer', {
                description: {
                    type: answer.type,
                    sdp: answer.sdp,
                },
            });
            console.info('[online-session] answer sent');
        } else if (type === 'answer' && role === 'psychologist') {
            const description = normalizeSessionDescription(payload.description);
            if (!description || peerConnection.remoteDescription) return;

            await peerConnection.setRemoteDescription(description);
            await flushPendingIceCandidates();
        } else if (type === 'ice-candidate' && payload.candidate) {
            if (peerConnection.remoteDescription) {
                await peerConnection.addIceCandidate(payload.candidate);
            } else {
                pendingIceCandidates.push(payload.candidate);
            }
        } else if (type === 'chat' && typeof payload.message === 'string') {
            const message = payload.message.trim();
            if (message) {
                messages.value.push({ author: 'Participante', message });
                chatUnread.value = true;
                void playNotificationSound();
            }
        } else if (type === 'media-state' && payload.role && payload.role !== role) {
            remoteMicrophoneEnabled.value = payload.audioEnabled !== false;
            remoteCameraEnabled.value = payload.videoEnabled !== false;
            console.info('[online-session] remote media state', {
                audioEnabled: remoteMicrophoneEnabled.value,
                videoEnabled: remoteCameraEnabled.value,
            });
        } else {
            console.info('[online-session] signal branch not handled', { type, role });
        }
    };

    const handleSignalError = (cause, type) => {
        console.error('[online-session] signal handling failed', {
            type,
            name: cause?.name,
            code: cause?.code,
            status: cause?.response?.status,
            signalingState: peerConnection?.signalingState,
            remoteDescription: Boolean(peerConnection?.remoteDescription),
        });
    };

    const requestAvailableMedia = async () => {
        try {
            return await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
        } catch (cause) {
            if (cause?.name !== 'NotFoundError') throw cause;

            const devices = await navigator.mediaDevices.enumerateDevices();
            console.info('[online-session] media devices', {
                videoInputs: devices.filter((device) => device.kind === 'videoinput').length,
                audioInputs: devices.filter((device) => device.kind === 'audioinput').length,
            });

            const tracks = [];
            let videoFailure = null;
            let audioFailure = null;

            try {
                const videoStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                tracks.push(...videoStream.getVideoTracks());
            } catch (videoCause) {
                videoFailure = videoCause;
            }

            try {
                const audioStream = await navigator.mediaDevices.getUserMedia({ video: false, audio: true });
                tracks.push(...audioStream.getAudioTracks());
            } catch (audioCause) {
                audioFailure = audioCause;
            }

            console.info('[online-session] media fallback', {
                video: tracks.some((track) => track.kind === 'video'),
                audio: tracks.some((track) => track.kind === 'audio'),
                videoError: videoFailure?.name,
                audioError: audioFailure?.name,
            });

            if (!tracks.length) throw cause;

            mediaWarning.value = tracks.some((track) => track.kind === 'video')
                ? 'Microfone não encontrado. O atendimento foi iniciado somente com vídeo.'
                : 'Câmera não encontrada. O atendimento foi iniciado somente com áudio.';

            return new MediaStream(tracks);
        }
    };

    const startWaitingRoom = async (session) => {
        stop({ announce: false });
        currentSession = session;
        waitingForApproval.value = true;
        entryApproved.value = false;
        entryRequest.value = false;
        entryBlocked.value = false;
        error.value = '';
        connectionNotice.value = '';
        setStatus('waiting', 'patient waiting for approval');
        console.info('[online-session] patient waiting for approval');

        try {
            if (!setupSignalChannel(session)) throw new Error('echo-channel-unavailable');
            await sendSignal('presence', presencePayload('requesting'));
            startPatientPresenceHeartbeat();
            startPatientApprovalPolling();
        } catch (cause) {
            console.error('[online-session] approval request failed', {
                name: cause?.name,
                code: cause?.code,
                status: cause?.response?.status,
            });
            entryBlocked.value = cause?.response?.status === 409;
            waitingForApproval.value = !entryBlocked.value;
            error.value = entryBlocked.value
                ? (cause?.response?.data?.message ?? 'Esta sala já está sendo usada por outro paciente.')
                : 'Não foi possível solicitar entrada na sala. Tente novamente.';
            setStatus('error', 'approval request failed');
        }
    };

    const approvePatient = async () => {
        if (role !== 'psychologist' || !entryRequest.value) return;

        try {
            await sendSignal('entry-approved', { role });
            patientApprovalSent = true;
            entryRequest.value = false;
            console.info('[online-session] patient entry approved by psychologist');
        } catch (cause) {
            controlError.value = 'Não foi possível autorizar a entrada do paciente.';
            handleSignalError(cause, 'entry-approved');
        }
    };

    const start = async (session) => {
        stop({ announce: false });
        currentSession = session;
        patientApprovalSent = false;
        entryRequest.value = false;
        waitingForApproval.value = false;
        entryBlocked.value = false;
        pendingIceCandidates = [];
        makingOffer = false;
        setupPhase = 'início';
        error.value = '';
        mediaWarning.value = '';
        controlError.value = '';
        connectionNotice.value = '';
        remoteMicrophoneEnabled.value = true;
        remoteCameraEnabled.value = true;
        setStatus('connecting', 'start');
        console.info('[online-session] start', { role });

        try {
            if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
                const secureContextError = new Error('secure-context-required');
                secureContextError.name = 'SecureContextError';
                throw secureContextError;
            }

            console.info('[online-session] media request', {
                secureContext: window.isSecureContext,
                mediaApi: Boolean(navigator.mediaDevices?.getUserMedia),
            });
            localStream = await requestAvailableMedia();
            microphoneEnabled.value = Boolean(localStream.getAudioTracks().length);
            cameraEnabled.value = Boolean(localStream.getVideoTracks().length);
            await refreshDevices();
            console.info('[online-session] media ready', {
                audioTracks: localStream.getAudioTracks().length,
                videoTracks: localStream.getVideoTracks().length,
            });
        } catch (cause) {
            console.error('[online-session] media failed', {
                name: cause?.name,
                code: cause?.code,
            });
            const messagesByError = {
                SecureContextError: 'A sala precisa ser aberta em HTTPS ou em localhost para usar câmera e microfone.',
                NotAllowedError: 'O acesso à câmera ou ao microfone foi bloqueado. Permita os dispositivos nas configurações do navegador e tente novamente.',
                NotFoundError: 'Não encontramos uma câmera ou um microfone disponíveis neste dispositivo.',
                NotReadableError: 'A câmera ou o microfone já está sendo usado por outro aplicativo. Feche-o e tente novamente.',
                OverconstrainedError: 'A câmera ou o microfone não atende aos requisitos deste dispositivo.',
                SecurityError: 'O navegador bloqueou o acesso aos dispositivos por motivos de segurança.',
                AbortError: 'O navegador interrompeu o acesso à câmera ou ao microfone. Tente novamente.',
            };

            error.value = messagesByError[cause?.name] ?? 'Não foi possível iniciar os dispositivos de áudio e vídeo. Verifique as permissões do navegador e tente novamente.';
            setStatus('error', `media failure:${cause?.name ?? 'unknown'}`);
            return;
        }

        try {
            setupPhase = 'preparando mídia';
            await nextTick();
            console.info('[online-session] video elements', {
                local: Boolean(localVideo.value),
                remote: Boolean(remoteVideo.value),
            });
            await attachStream(localVideo.value, localStream, 'local');

            setupPhase = 'criando conexão WebRTC';
            let iceServers = await requestIceServers();
            try {
                if (!iceServers.length) iceServers = JSON.parse(import.meta.env.VITE_WEBRTC_ICE_SERVERS ?? '[]');
            } catch {
                iceServers = [];
            }
            peerConnection = new RTCPeerConnection({ iceServers });
            console.info('[online-session] peer created', {
                iceServers: iceServers.length,
            });
            localStream.getTracks().forEach((track) => peerConnection.addTrack(track, localStream));
            peerConnection.ontrack = ({ streams }) => {
                void attachStream(remoteVideo.value, streams[0], 'remote');
            };
            peerConnection.onconnectionstatechange = () => {
                console.info('[online-session] connection state', { state: peerConnection.connectionState, role });
                if (peerConnection.connectionState === 'connected') {
                    error.value = '';
                    connectionNotice.value = '';
                    startSessionTimer();
                    setStatus('active', 'connectionState:connected');
                } else if (peerConnection.connectionState === 'failed') {
                    connectionNotice.value = 'A conexão com o participante falhou.';
                    error.value = 'A conexão com o participante falhou. Tente reconectar.';
                    setStatus('error', 'connectionState:failed');
                } else if (peerConnection.connectionState === 'disconnected') {
                    connectionNotice.value = 'A conexão ficou instável. Tentando reconectar...';
                    setStatus('reconnecting', 'connectionState:disconnected');
                } else {
                    setStatus(peerConnection.connectionState, `connectionState:${peerConnection.connectionState}`);
                }
            };
            peerConnection.onsignalingstatechange = () => {
                console.info('[online-session] signaling state', {
                    state: peerConnection.signalingState,
                    role,
                });
            };
            peerConnection.oniceconnectionstatechange = () => {
                console.info('[online-session] ICE connection state', {
                    state: peerConnection.iceConnectionState,
                    role,
                });
                if (peerConnection.iceConnectionState === 'disconnected') {
                    connectionNotice.value = 'A conexão ficou instável. Tentando reconectar...';
                    setStatus('reconnecting', 'iceConnectionState:disconnected');
                }
            };
            peerConnection.onicegatheringstatechange = () => {
                console.info('[online-session] ICE gathering state', {
                    state: peerConnection.iceGatheringState,
                    role,
                });
            };
            peerConnection.onicecandidate = ({ candidate }) => {
                if (candidate) {
                    void sendSignal('ice-candidate', { candidate }).catch((cause) => {
                        handleSignalError(cause, 'ice-candidate');
                    });
                }
            };

            if (setupSignalChannel(session)) {
                setupPhase = 'entrando no canal de sinalização';
            } else throw new Error('echo-channel-unavailable');

            setupPhase = 'enviando presença';
            await sendSignal('presence', presencePayload('joined'));
            startPatientPresenceHeartbeat();
            error.value = '';
            console.info('[online-session] presence sent');
            await sendMediaState();
            if (status.value !== 'active') {
                setStatus('waiting', 'presence response');
                console.info('[online-session] waiting for participant');
            } else {
                console.info('[online-session] connection already active');
            }
            startApprovalRequestPolling();
            console.info('[online-session] ready', { status: status.value });
        } catch (cause) {
            console.error('[online-session] connection setup failed', {
                phase: setupPhase,
                name: cause?.name,
                code: cause?.code,
                status: cause?.response?.status,
                echoState: window.Echo?.connector?.pusher?.connection?.state,
                hasSession: Boolean(session),
                channelPresent: Boolean(session?.channel),
            });
            error.value = `Câmera e microfone liberados, mas não foi possível conectar à sala em tempo real (etapa: ${setupPhase}). Verifique o servidor Reverb e tente novamente.`;
            setStatus('error', `setup failure:${setupPhase}`);
        }
    };

    const sendChat = async (message) => {
        const cleanMessage = String(message ?? '').trim();
        if (!cleanMessage) return;
        messages.value.push({ author: 'Você', message: cleanMessage });
        await sendSignal('chat', { message: cleanMessage });
    };

    const clearChatUnread = () => {
        chatUnread.value = false;
    };

    const stop = ({ announce = true } = {}) => {
        if (announce && channel) {
            void sendSignal('presence', presencePayload('left')).catch(() => {});
        }
        window.clearInterval(patientPresenceTimer);
        patientPresenceTimer = null;
        window.clearInterval(patientApprovalPollingTimer);
        patientApprovalPollingTimer = null;
        patientApprovalPollingInFlight = false;
        window.clearInterval(approvalRequestTimer);
        approvalRequestTimer = null;
        approvalRequestInFlight = false;
        patientApprovalSent = false;
        channel?.stopListening('.online-session.signal');
        channel = null;
        localStream?.getTracks().forEach((track) => track.stop());
        localStream = null;
        peerConnection?.close();
        peerConnection = null;
        pendingIceCandidates = [];
        makingOffer = false;
        stopSessionTimer();
        sessionSeconds.value = 0;
        if (announce) {
            currentSession = null;
            entryRequest.value = false;
            waitingForApproval.value = false;
            entryApproved.value = false;
            entryBlocked.value = false;
        }
    };

    onBeforeUnmount(stop);
    window.addEventListener('offline', handleOffline);
    window.addEventListener('online', handleOnline);

    onBeforeUnmount(() => {
        window.removeEventListener('offline', handleOffline);
        window.removeEventListener('online', handleOnline);
        window.clearTimeout(participantNoticeTimer);
    });

    return {
        localVideo,
        remoteVideo,
        status,
        error,
        mediaWarning,
        connectionNotice,
        participantNotice,
        controlError,
        microphoneEnabled,
        cameraEnabled,
        remoteMicrophoneEnabled,
        remoteCameraEnabled,
        audioInputs,
        videoInputs,
        toggleMicrophone: () => toggleTrack('audio'),
        toggleCamera: () => toggleTrack('video'),
        replaceAudioTrack: (deviceId) => replaceTrack('audio', deviceId),
        replaceVideoTrack: (deviceId) => replaceTrack('video', deviceId),
        messages,
        chatUnread,
        sessionSeconds,
        entryRequest,
        waitingForApproval,
        entryApproved,
        entryBlocked,
        start,
        startWaitingRoom,
        approvePatient,
        sendChat,
        clearChatUnread,
        stop,
    };
}
