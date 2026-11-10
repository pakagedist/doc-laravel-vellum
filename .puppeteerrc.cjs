const path = require('node:path');

/**
 * Puppeteer guarda los navegadores dentro de la propia aplicación (`.browsers/`)
 * para que la imagen de despliegue los lleve consigo y no haya que instalar
 * Chromium en el servidor.
 */
module.exports = {
    cacheDirectory: path.join(__dirname, '.browsers'),
};
