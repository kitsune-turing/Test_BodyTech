import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useDispatch } from 'react-redux';
import { deleteTask } from '../store/tasksSlice';
import { Button } from './index';
import { Card } from './Card';

const getStatusBadge = (status) => {
  const badges = {
    pending: { bg: 'bg-orange-100', text: 'text-orange-700', icon: '⏱', label: 'Pendiente' },
    in_progress: { bg: 'bg-primary', text: 'text-secondary', icon: '⟳', label: 'En Progreso' },
    done: { bg: 'bg-green-100', text: 'text-green-700', icon: '✓', label: 'Completado' },
  };
  
  return badges[status] || badges.pending;
};

export const TaskCard = ({ task }) => {
  const dispatch = useDispatch();
  const [isDeleting, setIsDeleting] = useState(false);
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);

  const handleDeleteClick = () => {
    setShowDeleteConfirm(true);
  };

  const handleConfirmDelete = async () => {
    setIsDeleting(true);
    await dispatch(deleteTask(task.id));
    setIsDeleting(false);
    setShowDeleteConfirm(false);
  };

  const handleCancelDelete = () => {
    setShowDeleteConfirm(false);
  };

  const statusBadge = getStatusBadge(task.status);

  return (
    <>
      <Card className="hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 group">
        <div className="flex justify-between items-start gap-4">
          <div className="flex-1">
            <h3 className="text-lg font-bold text-gray-800 mb-2 group-hover:text-secondary transition-colors">{task.title}</h3>
            {task.description && (
              <p className="text-gray-600 text-sm mb-3 line-clamp-2">{task.description}</p>
            )}
            <div className="flex items-center gap-2">
              <span className={`inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold ${statusBadge.bg} ${statusBadge.text} shadow-sm`}>
                <span>{statusBadge.icon}</span>
                {statusBadge.label}
              </span>
            </div>
          </div>
          
          <div className="flex gap-2 flex-shrink-0">
            <Link to={`/tasks/${task.id}/edit`}>
              <Button 
                variant="secondary" 
                size="sm"
              >
                Editar
              </Button>
            </Link>
            <Button 
              variant="danger" 
              size="sm"
              onClick={handleDeleteClick}
              disabled={isDeleting}
            >
              {isDeleting ? 'Eliminando...' : 'Eliminar'}
            </Button>
          </div>
        </div>
      </Card>

      {showDeleteConfirm && (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex justify-center items-center z-50 p-4 animate-fade-in">
          <Card className="max-w-sm w-full shadow-2xl">
            <h3 className="text-xl font-bold text-primary mb-2">Confirmar eliminación</h3>
            <p className="text-gray-600 text-sm mb-6">
              ¿Estás seguro de que deseas eliminar "<strong>{task.title}</strong>"? Esta acción no se puede deshacer.
            </p>
            <div className="flex gap-3">
              <Button 
                variant="outline"
                fullWidth
                onClick={handleCancelDelete}
                disabled={isDeleting}
              >
                Cancelar
              </Button>
              <Button 
                variant="danger"
                fullWidth
                onClick={handleConfirmDelete}
                disabled={isDeleting}
              >
                {isDeleting ? 'Eliminando...' : 'Eliminar'}
              </Button>
            </div>
          </Card>
        </div>
      )}
    </>
  );
};
