const WebSocket = require('ws');
const server = new WebSocket.Server({ 
    port: 8080,
    // Allow connections from any origin
    verifyClient: () => true,
    // Add CORS headers
    headers: {
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
        'Access-Control-Allow-Headers': 'Content-Type'
    }
});

const clients = new Set();
let sharedKey = null;

server.on('connection', (ws, req) => {
    console.log(`New connection from ${req.socket.remoteAddress}`);
    clients.add(ws);
    console.log('New client connected. Total clients:', clients.size);

    // Send a welcome message to the new client
    ws.send(JSON.stringify({
        type: 'welcome',
        message: 'Connected to server successfully'
    }));

    // If there's already a shared key, send it to the new client
    if (sharedKey) {
        ws.send(JSON.stringify({
            type: 'keyShare',
            key: sharedKey
        }));
    }

    ws.on('message', (message) => {
        try {
            const data = JSON.parse(message);
            console.log('Received message type:', data.type);
            
            if (data.type === 'keyShare') {
                // Store the shared key
                sharedKey = data.key;
                console.log('Received shared key');
            }
            
            // Broadcast the message to all other clients
            let messageSent = false;
            clients.forEach((client) => {
                if (client !== ws && client.readyState === WebSocket.OPEN) {
                    client.send(JSON.stringify(data));
                    messageSent = true;
                    console.log('Forwarded message to other client');
                }
            });
            
            if (!messageSent) {
                console.log('No other clients available to forward message');
            }
        } catch (error) {
            console.error('Error processing message:', error);
        }
    });

    ws.on('close', () => {
        clients.delete(ws);
        console.log('Client disconnected. Total clients:', clients.size);
    });

    ws.on('error', (error) => {
        console.error('WebSocket error:', error);
    });
});

server.on('error', (error) => {
    console.error('Server error:', error);
});

console.log('WebSocket server is running on port 8080'); 