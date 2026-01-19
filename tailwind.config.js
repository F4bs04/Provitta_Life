/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./**/*.{html,js,php}"],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Geologica', 'sans-serif'],
      },
      colors: {
        background: '#1A1A24',
        surface: '#1F2833',
        primary: '#66FCF1',
        secondary: '#45A29E',
        text: '#C5C6C7',
      },
      backgroundImage: {
        'brand-gradient': 'linear-gradient(180deg, rgba(30, 58, 138, 0.2) 0%, #1A1A24 100%)',
        'tech-gradient': 'linear-gradient(135deg, #1F2833 0%, #1A1A24 100%)',
        'glow': 'radial-gradient(circle at center, rgba(102, 252, 241, 0.15) 0%, rgba(26, 26, 36, 0) 70%)',
      }
    },
  },
  plugins: [],
}
