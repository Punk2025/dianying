/**
 * 构建前从 niuniucms 同步 dabao 静态资源到 public，避免与 CMS 二开样式脱节。
 * 在 web-spa-cf 目录执行：node scripts/sync-dabao.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const spaRoot = path.join(__dirname, '..');
const srcRoot = path.join(spaRoot, '..', 'niuniucms', 'template', 'dabao');
const destRoot = path.join(spaRoot, 'public', 'template', 'dabao');

function copyDir(name) {
  const from = path.join(srcRoot, name);
  const to = path.join(destRoot, name);
  if (!fs.existsSync(from)) {
    console.warn(`[sync-dabao] 跳过（不存在）: ${from}`);
    return;
  }
  fs.mkdirSync(to, { recursive: true });
  fs.cpSync(from, to, { recursive: true });
  console.log(`[sync-dabao] 已同步: ${name}/`);
}

if (!fs.existsSync(srcRoot)) {
  console.warn(
    `[sync-dabao] 未找到 CMS 目录，跳过同步（仅使用仓库内已有 public/template/dabao）：\n  ${srcRoot}`,
  );
  process.exit(0);
}

fs.mkdirSync(destRoot, { recursive: true });
for (const dir of ['css', 'Images', 'js']) {
  copyDir(dir);
}
const confSrc = path.join(srcRoot, 'conf.json');
if (fs.existsSync(confSrc)) {
  fs.copyFileSync(confSrc, path.join(destRoot, 'conf.json'));
  console.log('[sync-dabao] 已同步: conf.json');
}

console.log('[sync-dabao] 完成 →', destRoot);
