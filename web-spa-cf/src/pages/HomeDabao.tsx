import { useCallback, useEffect, useMemo, useState } from 'react';
import { fetchLegacyHomeJson } from '../api/legacyHome';
import { dabaoAsset } from '../publicUrls';

type HomePayload = {
  videolist?: VideoItem[];
  linklist?: { url?: string; name?: string }[];
  seo?: { t?: string };
};

type VideoItem = {
  url?: string;
  name?: string;
  pic?: string;
  actor?: string;
  cate_name?: string;
  year?: string;
  area?: string;
  remarks?: string;
  i?: number;
};

export function HomeDabao() {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [payload, setPayload] = useState<HomePayload | null>(null);

  const fetchHome = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const body = await fetchLegacyHomeJson();
      if (body.code !== 0) {
        setError(`接口 code=${body.code}`);
        setPayload(null);
        return;
      }
      const msg = body.message as HomePayload | string | undefined;
      setPayload(typeof msg === 'object' && msg !== null ? msg : {});
    } catch (e) {
      setError(e instanceof Error ? e.message : String(e));
      setPayload(null);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (import.meta.env.DEV) {
      void fetchHome();
      return;
    }
    if ((import.meta.env.VITE_API_ORIGIN || '').trim()) {
      void fetchHome();
    }
  }, [fetchHome]);

  const videolist = payload?.videolist;
  const linklist = payload?.linklist;

  const grid = useMemo(() => {
    if (!Array.isArray(videolist) || videolist.length === 0) {
      return null;
    }
    return (
      <div className="index-tj clearfix">
        <div className="index-tj-l">
          <h1 className="title index-color clearfix">
            <span className="hitkey"> </span>最新视频:
          </h1>
          <ul>
            {videolist.map((v, idx) => {
              const i = typeof v.i === 'number' ? v.i : idx + 1;
              const edge = i === 6 || i === 12 ? ' mmr0 pmr0' : '';
              return (
                <li key={`${v.url}-${idx}`} className={`p2 m1${edge}`}>
                  <a className="link-hover" href={v.url || '#'} title={v.name || ''}>
                    <img
                      className="lazy"
                      src={v.pic || dabaoAsset('Images/load.gif')}
                      alt={v.name || ''}
                      loading="lazy"
                    />
                    <span className="video-bg" />
                    <span className="lzbz">
                      <p className="name">{v.name}</p>
                      <p className="actor">{v.actor}</p>
                      <p className="actor">{v.cate_name}</p>
                      <p className="actor">
                        {v.year}/{v.area}
                      </p>
                    </span>
                    <p className="other">
                      <i>{v.remarks}</i>
                    </p>
                  </a>
                </li>
              );
            })}
          </ul>
        </div>
        <div className="index-tj-r">
          <h1 className="title index-color">随机推荐</h1>
          <ul>
            <li>
              <span className="az" style={{ color: '#999' }}>
                请在对接 block/vod_top 接口后展示排行
              </span>
            </li>
          </ul>
        </div>
      </div>
    );
  }, [videolist]);

  const missingApiOrigin =
    import.meta.env.PROD && !(import.meta.env.VITE_API_ORIGIN || '').trim();

  return (
    <>
      {missingApiOrigin ? (
        <div className="main" style={{ padding: '12px 0', color: '#721c24', background: '#f8d7da' }}>
          <strong>未配置接口域名：</strong>
          在 <code>web-spa-cf/.env</code> 中设置 <code>VITE_API_ORIGIN=https://你的PHP站点</code>，重新执行{' '}
          <code>npm run build</code> 后再上传 <code>dist</code>。仅上传 dist 而不带正确环境变量打包，无法拉取数据。
        </div>
      ) : null}
      <div className="channel-focus">
        <div className="channel-silder layout">
          <ul className="channel-silder-cnt">
            <li className="channel-silder-panel">
              <span className="channel-silder-img" style={{ cursor: 'default' }}>
                <img src={dabaoAsset('Images/background.png')} alt="" style={{ maxHeight: 280, objectFit: 'cover' }} />
              </span>
              <div className="channel-silder-intro">
                <div className="channel-silder-title">
                  <h2>
                    <span title="dabao 模版壳">dabao 模版 + CF Pages 骨架</span>
                  </h2>
                  <span> </span>
                </div>
                <ul className="channel-silder-info">
                  <li className="long">
                    <label>说明：</label>
                    <span>样式来自 niuniucms/template/dabao，已拷贝到 public/template/dabao</span>
                  </li>
                  <li>
                    类型：<span>React SPA</span>
                  </li>
                </ul>
                <p className="channel-silder-desc">
                  剧情：
                  <span>
                    已配置 VITE_API_ORIGIN 的生产环境会自动请求首页 JSON；也可点按钮重试。列表按 dabao 栅格渲染。
                  </span>
                </p>
                <a
                  className="channel-silder-play"
                  href="javascript:;"
                  title="拉取首页 JSON"
                  onClick={(e) => {
                    e.preventDefault();
                    if (!loading) {
                      void fetchHome();
                    }
                  }}
                  style={{ opacity: loading ? 0.6 : 1, pointerEvents: loading ? 'none' : 'auto' }}
                >
                  {loading ? '加载中…' : '拉取 PHP 首页数据'}
                </a>
              </div>
            </li>
          </ul>
          <ul className="channel-silder-nav">
            <li>
              <span>
                <img className="lazy" src={dabaoAsset('Images/logo.png')} alt="" width={120} height={67} />
              </span>
            </li>
          </ul>
        </div>
      </div>

      <div className="main">
        {error ? (
          <div className="mb clearfix" style={{ color: '#c00', padding: '0.5rem 0' }}>
            {error}
          </div>
        ) : null}
        {grid}
      </div>

      {Array.isArray(linklist) && linklist.length > 0 ? (
        <div className="ylink clearfix">
          友情链接：
          {linklist.map((l, i) => (
            <a key={`${l.url}-${i}`} href={l.url || '#'} target="_blank" rel="noreferrer">
              {l.name}{' '}
            </a>
          ))}
        </div>
      ) : null}
    </>
  );
}
