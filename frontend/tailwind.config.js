/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './index.html',
    './src/**/*.{js,jsx}',
  ],
  theme: {
    extend: {
      colors: {
        primary: '#1a1a1a',
        secondary: '#ff9500',
        accent: '#ff9500',
        'primary-dark': '#000000',
        'primary-light': '#333333',
      },
    },
  },
  plugins: [],
}
