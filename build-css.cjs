const fs = require('fs');
const postcss = require('postcss');
const tw = require('@tailwindcss/postcss');

postcss([tw({ base: process.cwd(), optimize: true })])
  .process(fs.readFileSync('src/input.css', 'utf8'), {
    from: process.cwd() + '/src/input.css',
    to: process.cwd() + '/assets/css/style.css',
  })
  .then((result) => {
    fs.writeFileSync('assets/css/style.css', result.css);
    console.log('Tailwind compiled: ' + result.css.length + ' bytes');
  })
  .catch((error) => {
    console.error(error);
    process.exit(1);
  });
