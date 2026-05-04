import { useCallback, useMemo, useState } from 'react';

export type NavCate = {
  cid: number;
  name: string;
  url: string;
  children?: { name: string; url: string }[];
};

type Props = {
  siteName: string;
  siteUrl: string;
  logoUrl: string;
  mobileQrUrl: string;
  categories: NavCate[];
  searchPrefix: string;
};

function buildSearchUrl(prefix: string, keyword: string): string {
  const k = encodeURIComponent(keyword);
  if (prefix.includes('__KW__')) {
    return prefix.replace('__KW__', k);
  }
  if (prefix.includes('keyword=')) {
    return prefix.endsWith('=') ? `${prefix}${k}` : `${prefix}${encodeURIComponent(keyword)}`;
  }
  const join = prefix.includes('?') ? (prefix.endsWith('&') || prefix.endsWith('?') ? '' : '&') : '?';
  return `${prefix}${join}keyword=${k}`;
}

export function DabaoHeader({
  siteName,
  siteUrl,
  logoUrl,
  mobileQrUrl,
  categories,
  searchPrefix,
}: Props) {
  const [hoverCid, setHoverCid] = useState<number | null>(null);
  const [mobileNavOpen, setMobileNavOpen] = useState(false);
  const [mobileSearchOpen, setMobileSearchOpen] = useState(false);
  const [kwPc, setKwPc] = useState('');
  const [kwM, setKwM] = useState('');

  const placeholders = useMemo(() => ['请输入影片或演员名', '输入关键词'], []);

  const submitSearch = useCallback(
    (raw: string) => {
      const keyword = raw.trim();
      if (!keyword || placeholders.includes(keyword)) {
        return false;
      }
      window.location.href = buildSearchUrl(searchPrefix, keyword);
      return false;
    },
    [placeholders, searchPrefix],
  );

  return (
    <div className="header-all">
      <div className="top clearfix">
        <ul className="logo">
          <li>
            <a href={siteUrl}>
              <img
                className="site-nav-logo"
                src={logoUrl}
                title={siteName}
                alt={siteName}
                style={{
                  maxWidth: 119,
                  maxHeight: 47,
                  width: 'auto',
                  height: 'auto',
                  objectFit: 'contain',
                  verticalAlign: 'middle',
                }}
              />
            </a>
          </li>
        </ul>

        <ul className="top-nav">
          <li>
            <a className="now on" href={siteUrl}>
              首页
            </a>
          </li>
          {categories.map((c) => (
            <li
              key={c.cid}
              className=""
              onMouseEnter={() => setHoverCid(c.cid)}
              onMouseLeave={() => setHoverCid(null)}
            >
              <a href={c.url}>
                {c.name}
                <i className="sjbgs" />
                <i className="sjbgx" />
              </a>
            </li>
          ))}
        </ul>

        <ul className="search so">
          <form
            id="search_form_pc"
            onSubmit={(e) => {
              e.preventDefault();
              submitSearch(kwPc);
            }}
          >
            <input
              type="text"
              name="keyword"
              id="keyword_pc"
              autoComplete="off"
              className="input"
              placeholder="请输入影片或演员名"
              value={kwPc}
              onChange={(e) => setKwPc(e.target.value)}
            />
            <div className="so-key" />
            <input type="submit" className="imgbt" value="" />
          </form>
        </ul>

        <ul className="nav-qt aa">
          <li className="bb">
            <strong className="ma">
              <i className="mabg" />
              手机观看
            </strong>
            <div className="cc maw">
              <img
                src={mobileQrUrl}
                alt="手机观看"
                className="ewmbg"
                width={150}
                height={150}
                loading="lazy"
                decoding="async"
              />
              <p>扫描二维码用手机观看</p>
            </div>
          </li>
        </ul>

        <div className="m-mobile-top-actions">
          <ul className="sj-navhome">
            <li>
              <a href={siteUrl} className="m-mobile-top-pill-link" title="首页">
                <span className="m-mobile-top-pill">
                  <span className="m-mobile-top-pill-txt">首页</span>
                </span>
              </a>
            </li>
          </ul>
          <ul className="sj-nav">
            <li
              className="sbtn1 m-nav-trigger"
              title="分类导航"
              onClick={() => {
                setMobileNavOpen((v) => !v);
                setMobileSearchOpen(false);
              }}
            >
              <span className="m-mobile-top-pill">
                <span className="m-mobile-top-pill-txt">导航</span>
              </span>
            </li>
          </ul>
          <ul className="sj-search">
            <li
              className="sbtn2 m-search-trigger"
              title="全站搜索"
              onClick={() => {
                setMobileSearchOpen((v) => !v);
                setMobileNavOpen(false);
              }}
            >
              <span className="m-mobile-top-pill">
                <span className="m-mobile-top-pill-txt">搜索</span>
              </span>
            </li>
          </ul>
        </div>
      </div>

      <div className="nav-down clearfix">
        {categories.map((c) => {
          const kids = c.children || [];
          if (kids.length === 0) {
            return null;
          }
          const show = hoverCid === c.cid;
          return (
            <div
              key={c.cid}
              id={`topnav-${c.cid}`}
              className="nav-down-1"
              style={{ display: show ? 'block' : 'none' }}
            >
              <div className="nav-down-2 clearfix">
                <ul>
                  {kids.map((s) => (
                    <li key={s.url}>
                      <a href={s.url}>{s.name}</a>
                    </li>
                  ))}
                </ul>
              </div>
            </div>
          );
        })}

        <div
          id="sj-nav-1"
          className="nav-down-1 sy1 sj-noover"
          style={{ display: mobileNavOpen ? 'block' : 'none' }}
        >
          <div className="nav-down-2 sj-nav-down-2 clearfix">
            <ul>
              <li>
                <a href={siteUrl} className="on">
                  首页
                </a>
              </li>
              {categories.map((c) => (
                <li key={c.cid}>
                  <a href={c.url}>{c.name}</a>
                </li>
              ))}
            </ul>
          </div>
        </div>

        <div
          id="sj-nav-search"
          className="nav-down-1 sy2 sj-noover m-search-dropdown"
          style={{ display: mobileSearchOpen ? 'block' : 'none' }}
        >
          <div className="nav-down-2 sj-nav-down-search m-search-panel clearfix">
            <div className="m-search-panel-head">
              <strong className="m-search-panel-title">全站搜索</strong>
              <span className="m-search-panel-sub">输入影片名、演员或关键词</span>
            </div>
            <form
              id="search_form_m"
              onSubmit={(e) => {
                e.preventDefault();
                submitSearch(kwM);
              }}
            >
              <div className="m-search-row">
                <input
                  type="text"
                  name="keyword"
                  id="keyword_m"
                  inputMode="search"
                  enterKeyHint="search"
                  autoComplete="off"
                  className="input m-search-field"
                  placeholder="输入关键词"
                  value={kwM}
                  onChange={(e) => setKwM(e.target.value)}
                />
                <input type="submit" className="imgbt m-search-submit" value="立即搜索" />
              </div>
            </form>
          </div>
        </div>
      </div>

      <div className="leaveNavInfo">
        <div className="topone clearfix" />
      </div>
    </div>
  );
}
