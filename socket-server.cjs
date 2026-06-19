// socket-server.cjs
const { Server } = require('socket.io');
const http = require('http');

const server = http.createServer();
const io = new Server(server, {
    cors: {
        origin: ["http://localhost:5173", "http://localhost:3000", "http://localhost:8000"],
        methods: ["GET", "POST"],
        credentials: true
    },
    transports: ['websocket', 'polling']
});

// Stockage des utilisateurs en ligne
const onlineUsers = new Map();

io.on('connection', (socket) => {
    console.log('🔌 Client connecté:', socket.id);

    // Authentification
    socket.on('authenticate', (userData) => {
        socket.userId = userData.id;
        socket.userName = userData.name;
        onlineUsers.set(userData.id, { ...userData, socketId: socket.id });
        io.emit('user.joined', { user_id: userData.id, name: userData.name });
        io.emit('onlineUsers', Array.from(onlineUsers.values()));
        console.log(`Utilisateur ${userData.name} authentifié`);
    });

    // Rejoindre un channel
    socket.on('subscribe', (channel) => {
        socket.join(channel);
        console.log(` Client ${socket.id} abonné au canal: ${channel}`);
    });

    // Quitter un channel
    socket.on('unsubscribe', (channel) => {
        socket.leave(channel);
        console.log(` Client ${socket.id} désabonné du canal: ${channel}`);
    });

    // Événement task.moved
    socket.on('task.moved', (data) => {
        console.log(' Événement task.moved reçu:', data);
        io.to('tasks').emit('task.moved', data);
    });

    // Événement notification.new
    socket.on('notification.new', (data) => {
        console.log('Événement notification.new reçu:', data);
        io.to('notifications').emit('notification.new', data);
    });

    // Événement task.note.comment.created
    socket.on('task.note.comment.created', (data) => {
        console.log('Événement commentaire reçu:', data);
        io.to(`task-note-${data.task_id}-${data.note_id}`).emit('task.note.comment.created', data);
    });

    socket.on('new.comment', (data) => {
    console.log(' Événement new.comment reçu:', data);
    io.to('comments').emit('new.comment', data);
   });

    // Déconnexion
    socket.on('disconnect', () => {
        if (socket.userId) {
            onlineUsers.delete(socket.userId);
            io.emit('user.left', { user_id: socket.userId });
            io.emit('onlineUsers', Array.from(onlineUsers.values()));
            console.log(`Utilisateur ${socket.userName} déconnecté`);
        }
        console.log('Client déconnecté:', socket.id);
    });
});

const PORT = 3001;
server.listen(PORT, '0.0.0.0', () => {
    console.log(`Socket.IO server running on port ${PORT}`);
    console.log(`http://localhost:${PORT}`);
});