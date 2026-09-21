const { createEditorAPI } = require('../../admin/editor-api.cjs');

const editor = createEditorAPI(process.cwd());

module.exports = async function handler(req, res) {
  const url = new URL(req.url, `https://${req.headers.host || 'localhost'}`);
  await editor(req, res, url);
};
