import { useState } from 'react';
import { useSelector } from 'react-redux';
import { taskService } from '../services/taskService';
import { authService } from '../services/authService';
import { Button } from './Button';

export const ExportButtons = () => {
  const { token } = useSelector((state) => state.auth);
  const { filter, search } = useSelector((state) => state.tasks);
  const [exporting, setExporting] = useState(false);

  const handleExport = async (format) => {
    setExporting(true);

    try {
      let activeToken = token;
      if (!activeToken) {
        activeToken = authService.getStoredToken();
      }

      const filters = {
        status: filter !== 'all' ? filter : null,
        search: search || null,
      };

      let blob;
      let filename;

      if (format === 'csv') {
        blob = await taskService.exportCSV(activeToken, filters);
        filename = `tareas_${new Date().toISOString().split('T')[0]}.csv`;
      } else {
        blob = await taskService.exportPDF(activeToken, filters);
        filename = `tareas_${new Date().toISOString().split('T')[0]}.pdf`;
      }

      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Export failed:', error);
      alert('Error al exportar. Por favor intenta nuevamente.');
    } finally {
      setExporting(false);
    }
  };

  return (
    <div className="flex gap-2">
      <Button
        variant="outline"
        size="sm"
        onClick={() => handleExport('csv')}
        disabled={exporting}
        className="flex items-center gap-2"
      >
        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        {exporting ? 'Exportando...' : 'Exportar CSV'}
      </Button>

      <Button
        variant="outline"
        size="sm"
        onClick={() => handleExport('pdf')}
        disabled={exporting}
        className="flex items-center gap-2"
      >
        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
        </svg>
        {exporting ? 'Exportando...' : 'Exportar PDF'}
      </Button>
    </div>
  );
};
