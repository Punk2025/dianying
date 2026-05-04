/**
 * 开发：走 Vite 代理 /legacy-api → VITE_API_ORIGIN
 * 生产：必须配置 VITE_API_ORIGIN（PHP 站点根，无尾斜杠），否则请求会打到 Pages 自身域名而失败
 */
export function legacyApiUrl(pathWithQuery: string): string {
  const q = pathWithQuery.includes('?') ? '&json=1' : '?json=1';
  if (import.meta.env.DEV) {
    return `/legacy-api${pathWithQuery}${q}`;
  }
  const origin = (import.meta.env.VITE_API_ORIGIN || '').trim().replace(/\/$/, '');
  if (!origin) {
    throw new Error(
      '生产包未配置 VITE_API_ORIGIN：在 web-spa-cf 目录创建 .env 写入 VITE_API_ORIGIN=https://你的PHP站点根（无尾斜杠），执行 npm run build 后重新上传 dist。',
    );
  }
  return `${origin}${pathWithQuery}${q}`;
}
