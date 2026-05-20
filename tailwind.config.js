/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        'midnight': '#1d1d1f',
        'cloud': '#6b6c6c',
        'frost': '#f3f6f6',
        'steel': '#cccfcf',
        'charcoal': '#313131',
        'slate-echo': '#444545',
        'alabaster': '#e8e8ed',
        'pearl': '#dedfe2',
        'ocean': '#0066cc',
        'teal': '#00a1b3',
        'violet': '#8668ff',
        'vivid': '#0071e3',
        'flame': '#b64400',
        'sunset': '#ed6300',
      },
      fontFamily: {
        'sf-text': ['"SF Pro Text"', 'ui-sans-serif', 'system-ui', '-apple-system', 'BlinkMacSystemFont', '"Segoe UI"', 'Roboto', 'sans-serif'],
        'sf-display': ['"SF Pro Display"', 'ui-sans-serif', 'system-ui', '-apple-system', 'BlinkMacSystemFont', '"Segoe UI"', 'Roboto', 'sans-serif'],
      },
      fontSize: {
        'body': ['14px', { lineHeight: '1.29', letterSpacing: '-0.168px' }],
        'body-lg': ['17px', { lineHeight: '1.29', letterSpacing: '-0.42px' }],
        'sub': ['18px', { lineHeight: '1.24', letterSpacing: '-0.342px' }],
        'heading-sm': ['20px', { lineHeight: '1.2', letterSpacing: '-0.4px' }],
        'heading': ['24px', { lineHeight: '1.18', letterSpacing: '-0.288px' }],
        'heading-lg': ['44px', { lineHeight: '1.14', letterSpacing: '-0.484px' }],
        'display': ['56px', { lineHeight: '1.07', letterSpacing: '-0.8px' }],
        'display-lg': ['80px', { lineHeight: '1.07', letterSpacing: '-0.8px' }],
      },
      borderRadius: {
        'card': '28px',
        'btn': '28px',
        'nav': '980px',
        'default': '12px',
      },
      boxShadow: {
        'subtle': 'rgba(0, 0, 0, 0.11) 0px 0px 1px 0px inset',
        'xl': 'rgba(0, 0, 0, 0.05) 0px 0px 35px 20px',
      },
      animation: {
        'fade-in': 'fadeIn 0.3s ease-out',
        'slide-in': 'slideIn 0.3s ease-out',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0', transform: 'translateY(-8px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        slideIn: {
          '0%': { opacity: '0', transform: 'translateX(100%)' },
          '100%': { opacity: '1', transform: 'translateX(0)' },
        },
      },
      screens: {
        'xs': '320px',
        'sm': '640px',
        'md': '768px',
        'lg': '1024px',
        'xl': '1280px',
        '2xl': '1536px',
      },
    },
  },
  plugins: [],
}
