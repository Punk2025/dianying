/**
 * 开发：走 Vite 代理 /legacy-api → VITE_API_ORIGIN
 * 生产：必须配置 VITE_API_ORIGIN（PHP 站点根，无尾斜杠），否则请求会打到 Pages 自身域名而失败
 */
export function legacyApiUrl(pathWithQuery: string): string {
  const q = pathWithQuery.includes('?') ? '&json=1' : '?json=1';
  if (import.meta.env.DEV) {
    return `/legacy-api${pathWithQuery}${q}`;
  }
  const origin = (import.meta.env.VITE_API_ORIGIN || '').replace(/\/$/, '');
  if (!origin) {
    console.warn('[web-spa-cf] 生产构建未设置 VITE_API_ORIGIN，接口请求地址可能错误。');
  }
  return `${origin}${pathWithQuery}${q}`;
}
