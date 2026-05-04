/**
 * dabao 静态资源 URL。
 * - 默认：相对路径 /template/dabao/...（随 dist 内 public 拷贝发布）
 * - 生产可选 VITE_TEMPLATE_ASSET_ORIGIN=https://主站域名 指向 niuniucms 线上同路径，改 dabao 资源即生效、dist 可不拷大图
 */
export function dabaoAsset(relativePath: string): string {
  const normalized = relativePath.replace(/^\/+/, '');
  const origin = (import.meta.env.VITE_TEMPLATE_ASSET_ORIGIN || '').trim().replace(/\/$/, '');
  if (origin) {
    return `${origin}/template/dabao/${normalized}`;
  }
  return `/template/dabao/${normalized}`;
}
