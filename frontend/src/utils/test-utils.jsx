import React from 'react';
import { render } from '@testing-library/react';
import { Provider } from 'react-redux';
import { BrowserRouter } from 'react-router-dom';
import { configureStore } from '@reduxjs/toolkit';
import authReducer from '../store/authSlice';
import tasksReducer from '../store/tasksSlice';

function createTestStore(initialState = {}) {
  return configureStore({
    reducer: {
      auth: authReducer,
      tasks: tasksReducer,
    },
    preloadedState: initialState,
  });
}

function AllTheProviders({ children, initialState = {} }) {
  const store = createTestStore(initialState);

  return (
    <Provider store={store}>
      <BrowserRouter future={{
        v7_startTransition: true,
        v7_relativeSplatPath: true
      }}>
        {children}
      </BrowserRouter>
    </Provider>
  );
}

function customRender(ui, options = {}) {
  const { initialState, ...renderOptions } = options;

  return render(ui, {
    wrapper: (props) => <AllTheProviders {...props} initialState={initialState} />,
    ...renderOptions,
  });
}

// Re-export everything from React Testing Library
export * from '@testing-library/react';

// Override render method
export { customRender as render, createTestStore };
