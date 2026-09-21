const fs = require('node:fs');
const path = require('node:path');
const { createEditorAPI } = require('../../admin/editor-api.cjs');

// Resolve the project root reliably in local and Vercel serverless runtimes.
const rootCandidates = [
  path.resolve(__dirname, '../..'),
  process.cwd(),
  path.resolve(process.cwd(), '..')
];
const projectRoot = rootCandidates.find(dir => fs.existsSync(path.join(dir, 'data.json'))) || process.cwd();
const editor = createEditorAPI(projectRoot);

module.exports = async function handler(req, res) {
  const url = new URL(req.url, `https://${req.headers.host || 'localhost'}`);
  await editor(req, res, url);
};
