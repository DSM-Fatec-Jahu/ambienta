/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './app/Views/**/*.php',
    './public/assets/js/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50:      '#EFF6FF',
          100:     '#E8F4FB',
          200:     '#BBD9F0',
          300:     '#7DBDE8',
          400:     '#3D9DD6',
          DEFAULT: '#1D6FA4',
          600:     '#1A6090',
          dark:    '#145680',
          700:     '#104A6B',
          light:   '#E8F4FB',
        },
        success: {
          DEFAULT: '#1A8C5B',
          50:  '#ECFDF5',
          100: '#D1FAE5',
        },
        warning: {
          DEFAULT: '#D4A017',
          50:  '#FFFBEB',
          100: '#FEF3C7',
        },
        danger: {
          DEFAULT: '#C0392B',
          50:  '#FEF2F2',
          100: '#FEE2E2',
        },
      },
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'sans-serif'],
      },
      fontSize: {
        '2xs': ['0.65rem', { lineHeight: '1rem' }],
      },
      boxShadow: {
        'card':  '0 1px 3px 0 rgb(0 0 0 / 0.10), 0 1px 2px -1px rgb(0 0 0 / 0.08)',
        'card-hover': '0 6px 18px 0 rgb(0 0 0 / 0.12), 0 2px 6px -2px rgb(0 0 0 / 0.08)',
        'modal': '0 20px 60px -10px rgb(0 0 0 / 0.25)',
        'topbar': '0 1px 0 0 rgb(0 0 0 / 0.08)',
      },
      borderRadius: {
        'xl':  '0.75rem',
        '2xl': '1rem',
        '3xl': '1.5rem',
      },
      transitionDuration: {
        '150': '150ms',
      },
      keyframes: {
        'fade-in': {
          '0%': { opacity: '0', transform: 'translateY(-4px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        'slide-in': {
          '0%': { transform: 'translateX(-100%)' },
          '100%': { transform: 'translateX(0)' },
        },
      },
      animation: {
        'fade-in': 'fade-in 0.15s ease-out',
        'slide-in': 'slide-in 0.2s ease-out',
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
};
