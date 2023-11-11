/** @type {import('tailwindcss').Config} */
module.exports = {
  prefix: '',
  corePlugins: {
    preflight: false,
  },
  content: [
    //'../../app/design/frontend/rwd/**/**/*.{phtml,xml}',
  ],
  theme: {
    extend: {},
  },
  plugins: [
    //require('@tailwindcss/forms'),
    //require('@tailwindcss/typography'),
  ],
}