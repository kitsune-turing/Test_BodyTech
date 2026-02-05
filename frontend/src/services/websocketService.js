const WS_URL = import.meta.env.VITE_WS_URL || 'ws://localhost:9501';
const RECONNECT_INTERVAL = 3000; // Intervalo base de reconexión (ms).
const MAX_RECONNECT_ATTEMPTS = 10;
const DISCONNECT_DELAY = 100; // Retardo para manejar double-mount en React StrictMode.

class WebSocketService {
  constructor() {
    this.ws = null;
    this.token = null;
    this.reconnectAttempts = 0;
    this.reconnectTimeout = null;
    this.messageHandlers = [];
    this.isIntentionallyClosed = false;
    this.disconnectTimeout = null;
    this.dispatch = null;
  }

  connect(token, dispatch) {
    if (this.disconnectTimeout) {
      clearTimeout(this.disconnectTimeout);
      this.disconnectTimeout = null;
      this.isIntentionallyClosed = false;
    }

    this.dispatch = dispatch;
    if (this.ws) {
      const state = this.ws.readyState;
      if ((state === WebSocket.OPEN || state === WebSocket.CONNECTING) && this.token === token) {
        return;
      }
      if (state === WebSocket.OPEN || state === WebSocket.CONNECTING) {
        this.ws.onclose = null; // Evita disparar reconexión.
        this.ws.close();
      }
    }

    this.token = token;
    this.isIntentionallyClosed = false;

    try {
      this.ws = new WebSocket(`${WS_URL}?token=${token}`);

      this.ws.onopen = () => {
        console.log('WebSocket connected');
        this.reconnectAttempts = 0;
        if (this.dispatch) {
          this.dispatch({ type: 'websocket/connected' });
        }
      };

      this.ws.onmessage = (event) => {
        try {
          const message = JSON.parse(event.data);
          console.log('WebSocket message received:', message);

          this.handleMessage(message);
        } catch (error) {
          console.error('Error parsing WebSocket message:', error);
        }
      };

      this.ws.onerror = (error) => {
        console.error('WebSocket error:', error);
      };

      this.ws.onclose = (event) => {
        console.log('WebSocket closed:', event.code, event.reason);

        if (this.dispatch) {
          this.dispatch({ type: 'websocket/disconnected' });
        }
        if (!this.isIntentionallyClosed && this.reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
          this.scheduleReconnect();
        }
      };
    } catch (error) {
      console.error('Error creating WebSocket connection:', error);
      this.scheduleReconnect();
    }
  }

  handleMessage(message) {
    if (!this.dispatch) return;
    const type = message.type || message.event;
    const data = message.data;

    switch (type) {
      case 'connected':
        console.log('WebSocket server acknowledged connection');
        break;

      case 'task.created':
        this.dispatch({
          type: 'tasks/taskCreatedViaWebSocket',
          payload: data,
        });
        break;

      case 'task.updated':
        this.dispatch({
          type: 'tasks/taskUpdatedViaWebSocket',
          payload: data,
        });
        break;

      case 'task.deleted':
        this.dispatch({
          type: 'tasks/taskDeletedViaWebSocket',
          payload: data.id,
        });
        break;

      default:
        console.log('Unknown message type:', type);
    }

    this.messageHandlers.forEach((handler) => {
      try {
        handler(message);
      } catch (error) {
        console.error('Error in message handler:', error);
      }
    });
  }

  scheduleReconnect() {
    if (this.reconnectTimeout) {
      clearTimeout(this.reconnectTimeout);
    }

    this.reconnectAttempts++;
    const delay = Math.min(
      RECONNECT_INTERVAL * Math.pow(2, this.reconnectAttempts - 1),
      30000
    );

    console.log(`Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts})`);

    this.reconnectTimeout = setTimeout(() => {
      if (this.token && !this.isIntentionallyClosed) {
        this.connect(this.token, this.dispatch);
      }
    }, delay);
  }

  disconnect() {
    if (this.disconnectTimeout) {
      clearTimeout(this.disconnectTimeout);
    }

    this.disconnectTimeout = setTimeout(() => {
      this.performDisconnect();
    }, DISCONNECT_DELAY);
  }

  performDisconnect() {
    this.isIntentionallyClosed = true;
    this.disconnectTimeout = null;

    if (this.reconnectTimeout) {
      clearTimeout(this.reconnectTimeout);
      this.reconnectTimeout = null;
    }

    if (this.ws) {
      this.ws.close();
      this.ws = null;
    }

    this.token = null;
    this.reconnectAttempts = 0;
  }

  addMessageHandler(handler) {
    this.messageHandlers.push(handler);
  }

  removeMessageHandler(handler) {
    this.messageHandlers = this.messageHandlers.filter((h) => h !== handler);
  }

  getConnectionState() {
    if (!this.ws) return 'disconnected';

    switch (this.ws.readyState) {
      case WebSocket.CONNECTING:
        return 'connecting';
      case WebSocket.OPEN:
        return 'connected';
      case WebSocket.CLOSING:
        return 'closing';
      case WebSocket.CLOSED:
        return 'disconnected';
      default:
        return 'unknown';
    }
  }
}

export const websocketService = new WebSocketService();
