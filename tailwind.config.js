/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      fontFamily: {
        'sans': ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
      },
      colors: {
        // SYDNEY MARKETS COMPLIANT COLORS ONLY
        // Using CSS variables from sydney-markets-colors.css
        'sm-white': {
          'pure': '#FFFFFF',
          'warm': '#FEFEFE',
          'soft': '#FDFDFD',
          'pearl': '#FCFCFC',
        },
        'sm-black': {
          'pure': '#000000',
          'rich': '#0A0A0A',
          'deep': '#1A1A1A',
          'soft': '#2A2A2A',
        },
        'sm-gray': {
          50: '#FAFAFA',
          100: '#F5F5F5',
          200: '#EEEEEE',
          300: '#E0E0E0',
          400: '#BDBDBD',
          500: '#9E9E9E',
          600: '#757575',
          700: '#616161',
          800: '#424242',
          900: '#212121',
        },
        'sm-green': {
          50: '#F0FDF4',
          100: '#DCFCE7',
          200: '#BBF7D0',
          300: '#86EFAC',
          400: '#4ADE80',
          500: '#22C55E',
          600: '#16A34A',
          700: '#15803D',
          800: '#166534',
          900: '#14532D',
        },
        // SPECIALIZED GREENS
        'sm-fresh-mint': '#10B981',
        'sm-forest-green': '#059669',
        'sm-emerald': '#047857',
        'sm-sage': '#065F46',
      }
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
}