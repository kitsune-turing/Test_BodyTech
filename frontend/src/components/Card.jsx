import { memo } from 'react';

const CardComponent = ({ children, className = '' }) => {
  return (
    <div className={`bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-shadow duration-300 border border-gray-100 p-4 sm:p-6 ${className}`}>
      {children}
    </div>
  );
};

export const Card = memo(CardComponent);
