import { nextTick, onBeforeUnmount, ref } from 'vue';
import axios from 'axios';

export function useOnlineSession({ role, token, sessionId }) {
    const localVideo = ref(null);
    const remoteVideo = ref(null);
    const status = ref('connecting');
    const error = ref('');
    const messages = ref([]);
    let localStream = null;
    let peerConnection = null;
    let channel = null;

    const signalEndpoint = () => role === 'patient'
        ? '/api/online-sessions/join/' + token + '/signal'
        : '/api/online-sessions/' + sessionId + '/signal';

    const sendSignal = async (type, payload = {}) => {
        const socketId = window.Echo?.socketId?.();
        await axios.post(signalEndpoint(), { type, payload }, {
            headers: socketId ? { 'X-Socket-ID': socketId } : {},
        });
    };

    const createOffer = async () => {
        const offer = await peerConnection.createOffer();
        await peerConnection.setLocalDescription(offer);
        await sendSignal('offer', { description: offer });
    };

    const handleSignal = async ({ type, payload }) => {
        if (!peerConnection || !payload) return;

        if (type === 'presence' && role === 'psychologist') {
            await createOffer();
        } else if (type === 'offer' && role === 'patient') {
            await peerConnection.setRemoteDescription(payload.description);
            const answer = await peerConnection.createAnswer();
            await peerConnection.setLocalDescription(answer);
            await sendSignal('answer', { description: answer });
        } else if (type === 'answer' && role === 'psychologist') {
            await peerConnection.setRemoteDescription(payload.description);
        } else if (type === 'ice-candidate' && payload.candidate) {
            await peerConnection.addIceCandidate(payload.candidate);
        } else if (type === 'chat' && typeof payload.message === 'string') {
            messages.value.push({ author: 'Participante', message: payload.message });
        }
    };

    const start = async (session) => {
        try {
            if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
                const secureContextError = new Error('secure-context-required');
                secureContextError.name = 'SecureContextError';
                throw secureContextError;
            }

            localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
        } catch (cause) {
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
            status.value = 'error';
            return;
        }

        try {
            await nextTick();
            if (localVideo.value) localVideo.value.srcObject = localStream;

            let iceServers = [];
            try {
                iceServers = JSON.parse(import.meta.env.VITE_WEBRTC_ICE_SERVERS ?? '[]');
            } catch {
                iceServers = [];
            }
            peerConnection = new RTCPeerConnection({ iceServers });
            localStream.getTracks().forEach((track) => peerConnection.addTrack(track, localStream));
            peerConnection.ontrack = ({ streams }) => {
                if (remoteVideo.value && streams[0]) remoteVideo.value.srcObject = streams[0];
            };
            peerConnection.onconnectionstatechange = () => {
                status.value = peerConnection.connectionState === 'connected' ? 'active' : peerConnection.connectionState;
            };
            peerConnection.onicecandidate = ({ candidate }) => {
                if (candidate) sendSignal('ice-candidate', { candidate });
            };

            if (window.Echo && session.channel) {
                channel = window.Echo.channel(session.channel);
                channel.listen('.online-session.signal', handleSignal);
            }

            await sendSignal('presence', { role });
            status.value = 'waiting';
        } catch (cause) {
            error.value = 'Câmera e microfone liberados, mas não foi possível conectar à sala em tempo real. Verifique se o servidor Reverb está rodando e tente novamente.';
            status.value = 'error';
        }
    };

    const sendChat = async (message) => {
        const cleanMessage = String(message ?? '').trim();
        if (!cleanMessage) return;
        messages.value.push({ author: 'Você', message: cleanMessage });
        await sendSignal('chat', { message: cleanMessage });
    };

    const stop = () => {
        channel?.stopListening('.online-session.signal');
        localStream?.getTracks().forEach((track) => track.stop());
        peerConnection?.close();
    };

    onBeforeUnmount(stop);

    return { localVideo, remoteVideo, status, error, messages, start, sendChat, stop };
}
