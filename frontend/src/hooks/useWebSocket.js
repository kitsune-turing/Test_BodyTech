import { useEffect, useState, useRef } from 'react';
import { useSelector, useDispatch } from 'react-redux';
import { websocketService } from '../services/websocketService';
import { authService } from '../services/authService';

export const useWebSocket = () => {
  const dispatch = useDispatch();
  const dispatchRef = useRef(dispatch);
  const { token, isAuthenticated } = useSelector((state) => state.auth);
  const [connectionState, setConnectionState] = useState('disconnected');

  useEffect(() => {
    dispatchRef.current = dispatch;
  }, [dispatch]);

  useEffect(() => {
    let activeToken = token;
    if (!activeToken) {
      activeToken = authService.getStoredToken();
    }

    if ((isAuthenticated || activeToken) && activeToken) {
      websocketService.connect(activeToken, dispatchRef.current);

      const interval = setInterval(() => {
        setConnectionState(websocketService.getConnectionState());
      }, 1000);

      return () => {
        clearInterval(interval);
        websocketService.disconnect();
      };
    } else {
      websocketService.disconnect();
      setConnectionState('disconnected');
    }
  }, [isAuthenticated, token]);

  return {
    connectionState,
    isConnected: connectionState === 'connected',
  };
};
