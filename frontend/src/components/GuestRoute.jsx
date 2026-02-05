import { Navigate } from 'react-router-dom';
import { useSelector } from 'react-redux';
import { LoadingSpinner } from './LoadingSpinner';

export const GuestRoute = ({ children }) => {
  const { isAuthenticated, token } = useSelector((state) => state.auth);
  const loading = useSelector((state) => state.auth.loading);

  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <LoadingSpinner />
      </div>
    );
  }

  if (isAuthenticated && token) {
    return <Navigate to="/" replace />;
  }

  return children;
};
