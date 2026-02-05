export const LoadingSpinner = ({ size = 'md' }) => {
  const sizes = {
    sm: 'w-4 h-4',
    md: 'w-8 h-8',
    lg: 'w-12 h-12',
  };

  return (
    <div className="flex justify-center items-center" role="status" aria-label="Loading">
      <style>{`
        @keyframes modern-spin {
          from {
            transform: rotate(0deg);
            border-color: rgba(255, 149, 0, 0.1) transparent rgba(26, 26, 26, 0.3) transparent;
          }
          50% {
            border-color: rgba(255, 149, 0, 0.5) transparent rgba(26, 26, 26, 0.1) transparent;
          }
          to {
            transform: rotate(360deg);
            border-color: rgba(255, 149, 0, 0.1) transparent rgba(26, 26, 26, 0.3) transparent;
          }
        }
      `}</style>
      <div className={`${sizes[size]} border-4 border-orange-300 border-t-secondary rounded-full`} style={{ animation: 'modern-spin 1.2s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite' }}></div>
    </div>
  );
};
