import { legacyApiUrl } from '../apiBase';

export type LegacyMessage<T> = {
  code: number;
  message: T;
};

/** 拉取 PHP 首页 JSON（需 api_on、CORS 等） */
export async function fetchLegacyHomeJson(): Promise<LegacyMessage<unknown>> {
  const res = await fetch(legacyApiUrl('/'), {
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    credentials: 'include',
  });
  const text = await res.text();
  let parsed: unknown;
  try {
    parsed = JSON.parse(text) as LegacyMessage<unknown>;
  } catch {
    const snippet = text.replace(/\s+/g, ' ').slice(0, 200);
    throw new Error(`返回不是 JSON（检查 api_on、路径、CORS）。片段: ${snippet}`);
  }
  const body = parsed as LegacyMessage<unknown>;
  if (typeof body.code !== 'number') {
    throw new Error('响应格式异常：缺少 code');
  }
  return body;
}
