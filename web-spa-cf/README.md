# CF 版前端（讨论稿 / 骨架）

本目录与 `niuniucms/` **完全独立**，用于评估：**用 React SPA 部署在 Cloudflare Pages**，业务接口仍由现有 **PHP 源站**提供。

## dabao 模版

- **构建时自动同步**：`npm run build` 前会执行 `npm run sync:dabao`（`scripts/sync-dabao.mjs`），从 **`../niuniucms/template/dabao`** 拷贝 **`css/`、`Images/`、`js/`** 与 **`conf.json`** 到 **`public/template/dabao/`**。在仓库根与 `niuniucms` 同级的布局下，Cloudflare 构建也会拿到最新二开样式与图片。若单独克隆本目录且无上级 `niuniucms`，脚本会跳过并沿用仓库里已有 `public`。
- **可选：样式与图走主站**：设置 **`VITE_TEMPLATE_ASSET_ORIGIN=https://你的PHP主站`** 后，`index.html` 里的 dabao CSS 与 React 内的 `dabaoAsset()` 会生成 **主站绝对 URL**，改 CMS 里 dabao 资源后 **无需重新上传 dist 大图**（仍须处理跨域与 HTTPS）。
- **务必把 `public/` 提交进 Git**（或完全依赖上面主站 origin）：否则在无 `niuniucms` 的构建环境里可能缺静态文件，表现为 **CSS MIME 为 text/html** 等。
- 布局参考 `html/public/nva.html`、`index_top.html`、`foot.html`，用 React 重写交互；**影片/广告数据**仍以 PHP JSON 为准，见 `src/api/legacyHome.ts`。
- 分类导航、随机榜等需后续对接接口再在 `App.tsx` 里传入 `categories` 等 props。

## 可行性结论（摘要）

| 维度 | 结论 |
|------|------|
| **技术可行** | 是。前端用 Vite/React 构建静态资源，由 Pages 托管；数据通过 `fetch` 调 PHP。 |
| **现成接口** | 部分有。路由里在 `$json` 为真且 **`api_on` 开启** 时，首页/分类等会通过 `message(0, $api_json)` 返回 JSON（见 `route/index.php`、`route/type.php` 等）。**并非**完整 REST，列表/详情/播放/会员等需逐项对接或新增专用 API。 |
| **CORS** | SPA 与 API **不同源**时，必须在 **PHP 侧或 Nginx** 增加 `Access-Control-Allow-Origin`（及 `OPTIONS` 预检）。本仓库**未改** PHP，上线前需自行加。 |
| **Cookie / 登录** | 跨子域（如 `app.xxx.pages.dev` → `api.xxx.com`）时浏览器 Cookie 策略严格，通常要 **Token 放 Header** 或 **同站反向代理**（见下「推荐架构」）。 |
| **SEO** | 纯 CSR SPA 对爬虫不友好；若要强 SEO，需 **SSR（如 Next on CF）** 或 **关键页仍走 PHP**。 |
| **与「CF 部署」关系** | Pages 只托管 **构建后的 JS/CSS/HTML**；**不能**在 Workers/Pages 上跑 PHP；数据库仍在源站。 |

## 推荐架构（生产向）

1. **同一主域**（减轻跨站问题）  
   - 例：`www.example.com` → Pages（或 Workers 反代静态）  
   - `www.example.com/api/*` → 反代到 PHP（Workers 或源站 Nginx location）  
   这样浏览器视为**同源**或仅路径区分，Cookie 与 CORS 简单很多。

2. **拆 API 层（中长期）**  
   - 新建 `api/v1/` 下稳定 JSON 契约（列表分页、详情、播放地址策略、用户态），避免直接依赖各 `route/*.php` 的 URL 形态与 `message` 结构。

3. **Cloudflare**  
   - **Pages**：`npm run build` 产出 `dist/`，`wrangler pages deploy dist` 或接 Git。  
   - **Workers**：可做 BFF、鉴权、缓存、把 `/api` 转发到源站 PHP。

## 本地开发

```bash
cd web-spa-cf
npm install
cp .env.example .env
# 编辑 .env：VITE_API_ORIGIN 指向本地或线上 PHP 根（含协议与域名）
npm run dev
```

Vite 已配置 **开发代理**：请求 `/legacy-api` 会转发到 `VITE_API_ORIGIN`，避免浏览器直连时的 CORS（仅开发环境）。

## 与现有 PHP 联调的最小约定

- 请求需触发 JSON 分支：`?json=1` **或** 请求头 `X-Requested-With: XMLHttpRequest`（见 `core/core.php`）。  
- 后台 **`api_on`** 需为开启状态，否则接口返回「关闭」类提示。  
- 响应体一般为：`{ "code": 0, "message": { ...业务字段 } }`（`code !== 0` 为错误）。

具体字段以各 `route` 里组装的 `$api_json` 为准。

## 本骨架不包含

- 未修改 `niuniucms` 内任何文件。  
- 未实现完整站业务页（只做演示组件 + 文档）。  
- 未配置生产 CORS / 域名 / Worker，需你方运维补充。

## 能否「直接」部署到 Cloudflare Pages？

**可以部署静态产物 `dist/`，但不是「零配置开箱即用」。**

1. **构建**（在 `web-spa-cf` 目录）：`npm ci && npm run build`  
2. **生产环境变量（构建时注入，必做）**：在 **`web-spa-cf/.env`**（或 Cloudflare **Build** 环境变量）里配置 **`VITE_API_ORIGIN`** = 你的 PHP 站点根（如 `https://www.example.com`，无尾斜杠），**再执行 build**。Vite 会把该值写进 JS；若未配置，请求会打到 Pages 自身域名，只能拿到 HTML，**永远拉不到 JSON**。手工上传 **dist** 前必须在本地先带好 `.env` 打一次包。其余见 `.env.example`。  
3. **跨域**：SPA 与 PHP 不同源时，PHP 或网关需放行 **CORS**（若 `fetch` 带 `credentials: 'include'` 还需 `Access-Control-Allow-Credentials` 与具体 Origin）。  
4. **搜索跳转**：生产环境建议设置 **`VITE_SEARCH_PREFIX`** 为完整带 `keyword=` 的前缀（见 `.env.example`）。  
5. **客户端路由**：当前几乎只有 `/`；若以后加 `react-router` 且需「刷新子路径可用」，需在 Cloudflare 侧配置 **SPA 回退**（见官方 [Serving Pages](https://developers.cloudflare.com/pages/configuration/serving-pages/) / Functions），本仓库未内置 `200` 伪静态规则以免与平台行为冲突。

部署命令示例：`npx wrangler pages deploy dist --project-name=你的项目名`（需已登录 Cloudflare CLI）。

### 若使用控制台「直接上传 / 已上传资产」

- **错误**：把 `web-spa-cf` 整个文件夹（含 `src/`、`package.json`、`node_modules` 等）打成包上传。根目录会出现大量源码文件（例如两千多个文件），**站点根没有可用的静态资源结构**，页面会打不开或行为异常。  
- **正确**：在本机先执行 `npm ci && npm run build`，**只上传 `dist/` 目录里的内容**（根下应是 `index.html`、`assets/`、`template/` 等，通常只有几十个文件量级），**不要**包含 `node_modules`。  
- **更推荐**：用 **Git 连接 Pages** + 填写构建命令与输出目录（见上文），由云端构建，避免手工打包错目录。
