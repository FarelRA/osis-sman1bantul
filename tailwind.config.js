/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./**/*.{php,html}', './public/js/**/*.js', './dash/**/*.js'],
  darkMode: 'class',
  plugins: [require('@tailwindcss/typography')],
}
