const path = require('node:path');
const { createEditorAPI } = require('../../admin/editor-api.cjs');

// Resolve the project root from the bundled function location.
const editor = createEditorAPI(path.resolve(__dirname, '../..'));

module.exports = async function handler(req, res) {
  const url = new URL(req.url, `https://${req.headers.host || 'localhost'}`);
  await editor(req, res, url);
};
