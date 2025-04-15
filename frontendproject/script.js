// VoIP Application with Encryption
class VoIPApplication {
    constructor() {
        this.localStream = null;
        this.peerConnection = null;
        this.symmetricKey = null;
        this.asymmetricKeys = null;
        this.isMuted = false;
        this.isConnected = false;
        this.isCallConnected = false;
        this.ws = null;
        this.pendingCandidates = [];
        this.isInitiator = false;
        this.messageArea = null;
        this.messageInput = null;
        this.sendMessageBtn = null;
        this.connectButton = null;
        this.disconnectButton = null;
        this.messagingSection = null;
        this.callStatus = null;
        this.muteToggle = null;

        // Initialize UI elements
        this.initializeUI();
    }

    initializeUI() {
        // Get DOM elements
        this.startCallBtn = document.getElementById('startCall');
        this.endCallBtn = document.getElementById('endCall');
        this.muteToggle = document.getElementById('muteToggle');
        this.connectionStatus = document.getElementById('connectionStatus');
        this.callStatus = document.getElementById('callStatus');
        this.symmetricKeyStatus = document.getElementById('symmetricKeyStatus');
        this.asymmetricKeyStatus = document.getElementById('asymmetricKeyStatus');
        this.messageArea = document.getElementById('messageArea');
        this.messageInput = document.getElementById('messageInput');
        this.sendMessageBtn = document.getElementById('sendMessage');
        this.connectButton = document.getElementById('connectButton');
        this.disconnectButton = document.getElementById('disconnectButton');
        this.messagingSection = document.getElementById('messagingSection');

        // Add event listeners
        this.startCallBtn.addEventListener('click', () => this.startCall());
        this.endCallBtn.addEventListener('click', () => this.endCall());
        this.muteToggle.addEventListener('click', () => this.toggleMute());
        this.sendMessageBtn.addEventListener('click', () => this.sendMessage());
        this.messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.sendMessage();
            }
        });
        this.connectButton.addEventListener('click', () => this.connect());
        this.disconnectButton.addEventListener('click', () => this.disconnect());
    }

    async connect() {
        try {
            // Initialize WebSocket first
            await this.initializeWebSocket();
            
            // Initialize WebRTC
            await this.initializeWebRTC();
            
            // Generate keys
            await this.generateKeys();
            
            // Show messaging section
            this.messagingSection.classList.remove('hidden');
            this.connectButton.disabled = true;
            this.disconnectButton.disabled = false;
            
            this.isConnected = true;
            this.updateConnectionStatus();
            this.log('Connected and ready to communicate');

            // Share the symmetric key with other users
            this.ws.send(JSON.stringify({
                type: 'keyShare',
                key: this.symmetricKey
            }));
        } catch (error) {
            this.log('Error connecting: ' + error.message);
        }
    }

    async disconnect() {
        try {
            // End any active call
            if (this.peerConnection) {
                await this.endCall();
            }

            // Close WebSocket connection
            if (this.ws) {
                this.ws.close();
                this.ws = null;
            }

            // Stop local stream
            if (this.localStream) {
                this.localStream.getTracks().forEach(track => track.stop());
                this.localStream = null;
            }

            // Reset state
            this.isConnected = false;
            this.symmetricKey = null;
            this.asymmetricKeys = null;
            this.pendingCandidates = [];
            this.isInitiator = false;

            // Update UI
            this.messagingSection.classList.add('hidden');
            this.connectButton.disabled = false;
            this.disconnectButton.disabled = true;
            this.startCallBtn.disabled = false;
            this.endCallBtn.disabled = true;
            this.messageArea.innerHTML = '';
            this.symmetricKeyStatus.textContent = 'Not Generated';
            this.asymmetricKeyStatus.textContent = 'Not Generated';
            
            this.updateConnectionStatus();
            this.log('Disconnected from server');
        } catch (error) {
            this.log('Error disconnecting: ' + error.message);
        }
    }

    sendMessage() {
        const messageInput = document.getElementById('messageInput');
        const message = messageInput.value.trim();
        
        if (message && this.isConnected) {
            try {
                if (!this.symmetricKey) {
                    this.log('Error: No symmetric key available for encryption');
                    return;
                }

                // Encrypt the message using the symmetric key
                const encryptedMessage = CryptoJS.AES.encrypt(message, this.symmetricKey).toString();
                
                // Send the encrypted message
                this.ws.send(JSON.stringify({
                    type: 'message',
                    message: encryptedMessage
                }));
                
                // Add the sent message to the message area
                this.addMessageToArea('Sent: ' + message);
                
                // Clear the input field
                messageInput.value = '';
            } catch (error) {
                this.log('Error sending message: ' + error.message);
            }
        }
    }

    addMessageToArea(message) {
        const messageElement = document.createElement('div');
        messageElement.className = 'mb-2 p-2 rounded';
        
        // Style sent messages differently from received messages
        if (message.startsWith('Sent:')) {
            messageElement.className += ' bg-blue-100 ml-auto';
        } else {
            messageElement.className += ' bg-gray-100';
        }
        
        messageElement.textContent = message;
        this.messageArea.appendChild(messageElement);
        
        // Scroll to the bottom of the message area
        this.messageArea.scrollTop = this.messageArea.scrollHeight;
    }

    initializeWebSocket() {
        return new Promise((resolve, reject) => {
            // Use the correct WebSocket URL with the current window location
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            const wsUrl = `${protocol}//${window.location.hostname}:8080`;
            this.log(`Attempting to connect to WebSocket server at ${wsUrl}`);
            
            try {
                this.ws = new WebSocket(wsUrl);

                this.ws.onopen = () => {
                    this.log('Connected to signaling server');
                    this.updateConnectionStatus();
                    resolve();
                };

                this.ws.onmessage = async (event) => {
                    try {
                        const data = JSON.parse(event.data);
                        this.log(`Received message type: ${data.type}`);
                        
                        if (data.type === 'welcome') {
                            this.log(data.message);
                            return;
                        }
                        
                        if (data.type === 'keyShare') {
                            // Store the shared symmetric key
                            this.symmetricKey = data.key;
                            this.symmetricKeyStatus.textContent = `Key: ${this.symmetricKey.substring(0, 8)}...`;
                            this.log('Received shared symmetric key');
                            return;
                        }
                        
                        if (data.type === 'offer') {
                            await this.handleOffer(data.offer);
                        } else if (data.type === 'answer') {
                            await this.handleAnswer(data.answer);
                        } else if (data.type === 'candidate') {
                            await this.handleCandidate(data.candidate);
                        } else if (data.type === 'endCall') {
                            await this.handleEndCall();
                        } else if (data.type === 'message') {
                            await this.handleMessage(data.message);
                        }
                    } catch (error) {
                        this.log('Error processing message: ' + error.message);
                    }
                };

                this.ws.onclose = (event) => {
                    this.log(`Disconnected from signaling server. Code: ${event.code}, Reason: ${event.reason}`);
                    this.isConnected = false;
                    this.updateConnectionStatus();
                };

                this.ws.onerror = (error) => {
                    this.log('WebSocket error: ' + (error.message || 'Unknown error'));
                    reject(error);
                };
            } catch (error) {
                this.log('Error creating WebSocket connection: ' + error.message);
                reject(error);
            }
        });
    }

    async initializeWebRTC() {
        try {
            // Request microphone access with specific audio constraints
            this.localStream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true
                }
            });
            
            // Log audio track details
            this.localStream.getAudioTracks().forEach(track => {
                this.log(`Local audio track enabled: ${track.enabled}`);
                this.log(`Local audio track muted: ${track.muted}`);
                this.log(`Local audio track readyState: ${track.readyState}`);
            });
            
            this.log('Microphone access granted');
        } catch (error) {
            this.log('Error accessing microphone: ' + error.message);
        }
    }

    async generateKeys() {
        // Generate symmetric key (AES)
        this.symmetricKey = CryptoJS.lib.WordArray.random(256 / 8).toString();
        // Show first 8 characters of the symmetric key
        this.symmetricKeyStatus.textContent = `Key: ${this.symmetricKey.substring(0, 8)}...`;
        this.log('Symmetric key generated');

        // Generate asymmetric keys (RSA)
        const keyPair = await window.crypto.subtle.generateKey(
            {
                name: "RSA-OAEP",
                modulusLength: 2048,
                publicExponent: new Uint8Array([1, 0, 1]),
                hash: "SHA-256",
            },
            true,
            ["encrypt", "decrypt"]
        );

        this.asymmetricKeys = keyPair;
        
        // Export and show truncated public key
        const exportedPublicKey = await window.crypto.subtle.exportKey(
            "spki",
            keyPair.publicKey
        );
        const publicKeyString = btoa(String.fromCharCode(...new Uint8Array(exportedPublicKey)));
        this.asymmetricKeyStatus.textContent = `Public Key: ${publicKeyString.substring(0, 20)}...`;
        
        this.log('Asymmetric keys generated');
    }

    async startCall() {
        try {
            // First ensure we have a local stream
            if (!this.localStream) {
                await this.initializeWebRTC();
            }
            
            if (!this.localStream) {
                this.log('Error: No local stream available');
                return;
            }
            
            // Setup peer connection without generating new keys
            this.setupPeerConnection();
            
            this.isConnected = true;
            this.isInitiator = true;
            this.updateConnectionStatus();
            this.startCallBtn.disabled = true;
            this.endCallBtn.disabled = false;
            this.log('Call started');

            // Create and send offer
            const offer = await this.peerConnection.createOffer();
            await this.peerConnection.setLocalDescription(offer);
            this.ws.send(JSON.stringify({
                type: 'offer',
                offer: offer
            }));
            this.log('Sent offer to peer');
        } catch (error) {
            this.log('Error starting call: ' + error.message);
        }
    }

    setupPeerConnection() {
        const configuration = {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' }
            ]
        };

        this.peerConnection = new RTCPeerConnection(configuration);
        
        // Add local stream
        this.localStream.getTracks().forEach(track => {
            this.peerConnection.addTrack(track, this.localStream);
            this.log(`Added local track: ${track.kind} (${track.enabled ? 'enabled' : 'disabled'})`);
        });

        // Handle incoming streams
        this.peerConnection.ontrack = (event) => {
            this.log('Received remote track');
            const remoteStream = event.streams[0];
            const remoteAudio = new Audio();
            remoteAudio.srcObject = remoteStream;
            
            // Add event listeners for debugging
            remoteAudio.onloadedmetadata = () => {
                this.log('Remote audio metadata loaded');
            };
            
            remoteAudio.onplay = () => {
                this.log('Remote audio started playing');
                this.isCallConnected = true;
                this.updateCallStatus();
            };
            
            remoteAudio.onerror = (error) => {
                this.log('Remote audio error: ' + error.message);
            };

            // Try to play the audio
            remoteAudio.play().catch(error => {
                this.log('Error playing remote audio: ' + error.message);
            });
            
            this.log('Playing remote audio stream');
        };

        // Handle ICE candidates
        this.peerConnection.onicecandidate = (event) => {
            if (event.candidate) {
                this.log('Generated ICE candidate');
                if (this.isInitiator) {
                    this.ws.send(JSON.stringify({
                        type: 'candidate',
                        candidate: event.candidate
                    }));
                    this.log('Sent ICE candidate to peer');
                } else {
                    this.pendingCandidates.push(event.candidate);
                    this.log('Stored ICE candidate for later');
                }
            }
        };

        // Handle connection state changes
        this.peerConnection.onconnectionstatechange = () => {
            this.log('Connection state changed to: ' + this.peerConnection.connectionState);
            if (this.peerConnection.connectionState === 'connected') {
                this.isCallConnected = true;
                this.updateCallStatus();
            } else if (this.peerConnection.connectionState === 'disconnected' || 
                      this.peerConnection.connectionState === 'failed' || 
                      this.peerConnection.connectionState === 'closed') {
                this.isCallConnected = false;
                this.updateCallStatus();
            }
        };

        // Handle ICE connection state changes
        this.peerConnection.oniceconnectionstatechange = () => {
            this.log('ICE connection state: ' + this.peerConnection.iceConnectionState);
            if (this.peerConnection.iceConnectionState === 'connected') {
                this.isCallConnected = true;
                this.updateCallStatus();
            } else if (this.peerConnection.iceConnectionState === 'disconnected' || 
                      this.peerConnection.iceConnectionState === 'failed' || 
                      this.peerConnection.iceConnectionState === 'closed') {
                this.isCallConnected = false;
                this.updateCallStatus();
            }
        };

        // Handle signaling state changes
        this.peerConnection.onsignalingstatechange = () => {
            this.log('Signaling state: ' + this.peerConnection.signalingState);
        };
    }

    async handleOffer(offer) {
        try {
            // First ensure we have a local stream
            if (!this.localStream) {
                await this.initializeWebRTC();
            }
            
            // Setup peer connection without generating new keys
            this.setupPeerConnection();
            
            // Set the remote description
            await this.peerConnection.setRemoteDescription(new RTCSessionDescription(offer));
            
            // Create and set local answer
            const answer = await this.peerConnection.createAnswer();
            await this.peerConnection.setLocalDescription(answer);
            
            // Send all pending candidates
            this.pendingCandidates.forEach(candidate => {
                this.ws.send(JSON.stringify({
                    type: 'candidate',
                    candidate: candidate
                }));
            });
            this.pendingCandidates = [];
            
            // Send the answer
            this.ws.send(JSON.stringify({
                type: 'answer',
                answer: answer
            }));
            
            this.isConnected = true;
            this.updateConnectionStatus();
            this.startCallBtn.disabled = true;
            this.endCallBtn.disabled = false;
            this.log('Received and handled offer');
        } catch (error) {
            this.log('Error handling offer: ' + error.message);
        }
    }

    async handleAnswer(answer) {
        try {
            if (!this.peerConnection) {
                this.log('Error: No peer connection available');
                return;
            }
            await this.peerConnection.setRemoteDescription(new RTCSessionDescription(answer));
            this.log('Received and handled answer');
        } catch (error) {
            this.log('Error handling answer: ' + error.message);
        }
    }

    async handleCandidate(candidate) {
        try {
            if (!this.peerConnection) {
                this.log('Storing ICE candidate for later');
                this.pendingCandidates.push(candidate);
                return;
            }

            if (this.peerConnection.remoteDescription) {
                await this.peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
                this.log('Added ICE candidate');
            } else {
                this.log('Storing ICE candidate until remote description is set');
                this.pendingCandidates.push(candidate);
            }
        } catch (error) {
            this.log('Error handling ICE candidate: ' + error.message);
        }
    }

    async endCall() {
        try {
            if (this.peerConnection) {
                // Close all data channels
                if (this.peerConnection.dataChannel) {
                    this.peerConnection.dataChannel.close();
                }

                // Close all transceivers
                this.peerConnection.getTransceivers().forEach(transceiver => {
                    if (transceiver.stop) {
                        transceiver.stop();
                    }
                });

                // Close the connection
                this.peerConnection.close();
                this.peerConnection = null;
            }

            // Stop local stream tracks but keep the stream
            if (this.localStream) {
                this.localStream.getTracks().forEach(track => {
                    track.stop();
                });
            }

            // Reset call-related state
            this.pendingCandidates = [];
            this.isInitiator = false;
            this.isCallConnected = false;
            this.updateCallStatus();

            // Reset mute state
            this.isMuted = false;
            this.muteToggle.textContent = 'Mute';
            this.muteToggle.classList.remove('bg-red-500');
            this.muteToggle.classList.add('bg-gray-500');

            // Update UI
            this.startCallBtn.disabled = false;
            this.endCallBtn.disabled = true;
            this.muteToggle.disabled = true;

            // Notify the other peer
            if (this.ws && this.ws.readyState === WebSocket.OPEN) {
                this.ws.send(JSON.stringify({
                    type: 'endCall'
                }));
            }

            this.log('Call ended');
        } catch (error) {
            this.log('Error ending call: ' + error.message);
        }
    }

    async handleEndCall() {
        try {
            // Clean up call-specific resources
            if (this.peerConnection) {
                this.peerConnection.close();
                this.peerConnection = null;
            }
            
            // Reset call-related state but maintain connection
            this.isInitiator = false;
            this.pendingCandidates = [];
            this.isCallConnected = false;
            this.updateCallStatus();
            
            // Reset mute state
            this.isMuted = false;
            this.muteToggle.textContent = 'Mute';
            this.muteToggle.classList.remove('bg-red-500');
            this.muteToggle.classList.add('bg-gray-500');
            
            // Update UI
            this.startCallBtn.disabled = false;
            this.endCallBtn.disabled = true;
            this.muteToggle.disabled = true;
            
            this.log('Call ended by other peer');
        } catch (error) {
            this.log('Error handling end call: ' + error.message);
        }
    }

    async handleMessage(encryptedMessage) {
        try {
            if (!this.symmetricKey) {
                this.log('Error: No symmetric key available for decryption');
                return;
            }

            // Decrypt the message using the symmetric key
            const decryptedMessage = CryptoJS.AES.decrypt(encryptedMessage, this.symmetricKey).toString(CryptoJS.enc.Utf8);
            this.log('Received message: ' + decryptedMessage);
            
            // Add the received message to the message area
            this.addMessageToArea('Received: ' + decryptedMessage);
        } catch (error) {
            this.log('Error decrypting message: ' + error.message);
        }
    }

    toggleMute() {
        if (this.localStream) {
            this.isMuted = !this.isMuted;
            this.localStream.getAudioTracks().forEach(track => {
                track.enabled = !this.isMuted;
            });
            
            // Update button text and style
            if (this.isMuted) {
                this.muteToggle.textContent = 'Unmute';
                this.muteToggle.classList.remove('bg-gray-500');
                this.muteToggle.classList.add('bg-red-500');
            } else {
                this.muteToggle.textContent = 'Mute';
                this.muteToggle.classList.remove('bg-red-500');
                this.muteToggle.classList.add('bg-gray-500');
            }
            
            this.log(this.isMuted ? 'Microphone muted' : 'Microphone unmuted');
        }
    }

    updateConnectionStatus() {
        const statusIndicator = this.connectionStatus.querySelector('div');
        const statusText = this.connectionStatus.querySelector('span');
        
        if (this.isConnected) {
            statusIndicator.className = 'w-3 h-3 bg-green-500 rounded-full mr-2 connected';
            statusText.textContent = 'Connected';
        } else {
            statusIndicator.className = 'w-3 h-3 bg-red-500 rounded-full mr-2';
            statusText.textContent = 'Disconnected';
        }
    }

    updateCallStatus() {
        const statusIndicator = this.callStatus.querySelector('div');
        const statusText = this.callStatus.querySelector('span');
        
        if (this.isCallConnected) {
            statusIndicator.className = 'w-3 h-3 bg-green-500 rounded-full mr-2';
            statusText.textContent = 'Call: Connected';
        } else {
            statusIndicator.className = 'w-3 h-3 bg-red-500 rounded-full mr-2';
            statusText.textContent = 'Call: Not Connected';
        }
    }

    log(message) {
        console.log(`[${new Date().toLocaleTimeString()}] ${message}`);
    }
}

// Initialize the application when the page loads
document.addEventListener('DOMContentLoaded', () => {
    window.voipApp = new VoIPApplication();
}); 