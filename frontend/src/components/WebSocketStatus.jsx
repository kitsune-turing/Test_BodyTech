import { useWebSocket } from '../hooks/useWebSocket';

export const WebSocketStatus = () => {
  const { connectionState, isConnected } = useWebSocket();

  const statusConfig = {
    connected: {
      color: 'bg-green-500',
      text: 'Conectado',
      icon: '●',
    },
    connecting: {
      color: 'bg-yellow-500',
      text: 'Conectando...',
      icon: '◐',
    },
    disconnected: {
      color: 'bg-red-500',
      text: 'Desconectado',
      icon: '○',
    },
    closing: {
      color: 'bg-orange-500',
      text: 'Cerrando...',
      icon: '◐',
    },
  };

  const config = statusConfig[connectionState] || statusConfig.disconnected;

  return (
    <div className="fixed bottom-4 right-4 z-50">
      <div
        className={`flex items-center gap-2 px-3 py-2 rounded-lg shadow-lg ${
          isConnected
            ? 'bg-white border-2 border-green-500'
            : 'bg-white border-2 border-gray-300'
        } transition-all duration-300`}
        title={`Estado de conexión en tiempo real: ${config.text}`}
      >
        <span className={`${config.color} w-3 h-3 rounded-full animate-pulse`}></span>
        <span className="text-xs font-semibold text-gray-700">{config.text}</span>
      </div>
    </div>
  );
};
