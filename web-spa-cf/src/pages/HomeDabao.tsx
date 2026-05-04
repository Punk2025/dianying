import { useCallback, useMemo, useState } from 'react';
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

  return (
    <>
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
                  剧情：<span>点击下方按钮从 PHP 拉取首页 JSON 后，会按 dabao 栅格渲染 videolist。</span>
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
