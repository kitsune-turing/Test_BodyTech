import { useSelector } from 'react-redux';
import { useMemo } from 'react';

/**
 * Custom hook para memoizar resultados de selectores.
 * Previene re-renders innecesarios cuando el resultado del selector no ha cambiado.
 */
export const useMemoSelector = (selector) => {
  const value = useSelector(selector);
  return useMemo(() => value, [value]);
};

/**
 * Custom hook para comparación profunda de objetos.
 */
export const useDeepCompareMemoize = (value) => {
  const ref = React.useRef();
  const signalRef = React.useRef(0);

  if (!isDeepEqual(value, ref.current)) {
    ref.current = value;
    signalRef.current += 1;
  }

  return React.useMemo(() => ref.current, [signalRef.current]);
};

function isDeepEqual(a, b) {
  if (a === b) return true;
  if (a == null || b == null) return false;
  if (typeof a !== 'object' || typeof b !== 'object') return false;

  const keysA = Object.keys(a);
  const keysB = Object.keys(b);

  if (keysA.length !== keysB.length) return false;

  for (let key of keysA) {
    if (!isDeepEqual(a[key], b[key])) return false;
  }

  return true;
}
