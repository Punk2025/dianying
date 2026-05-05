# web-spa-cf · Cloudflare Pages 前端说明与使用教程

本目录与 **`niuniucms/`** 并列存放，**不修改 CMS 源码**。它是一个基于 **Vite + React** 的静态站点，用于部署到 **Cloudflare Pages**（或其它静态托管）；**影片、分类、广告等数据仍由现有 PHP（niuniucms）提供**，通过浏览器 `fetch` 拉 JSON。

视觉沿用 **`template/dabao`** 样式（构建前可从 CMS 目录同步 `css` / `Images` / `js`）。

---

## 目录

1. [适用场景与局限](#1-适用场景与局限)  
2. [仓库目录结构](#2-仓库目录结构)  
3. [环境要求](#3-环境要求)  
4. [环境变量说明](#4-环境变量说明)  
5. [本地开发教程](#5-本地开发教程)  
6. [生产构建与上传](#6-生产构建与上传)  
7. [Cloudflare Pages 配置示例](#7-cloudflare-pages-配置示例)  
8. [dabao 静态资源同步](#8-dabao-静态资源同步)  
9. [与 PHP 接口的约定](#9-与-php-接口的约定)  
10. [常见问题排查](#10-常见问题排查)

---

## 1. 适用场景与局限

| 适合 | 不适合（需另行方案） |
|------|----------------------|
| CF 上托管轻量 H5、落地页壳 | 在 Pages 上运行 PHP |
| 数据实时来自同一套 niuniucms 后端 | 指望上传源码文件夹就当网站发布 |
| 与 dabao 视觉大致一致（CSS 同源或同步） | 不做任何接口改造就 100% 复刻所有模版页 |

纯前端 SPA **SEO 弱于 PHP 整页输出**；要强 SEO 需 SSR 或重点页面仍走 CMS。

---

## 2. 仓库目录结构（概要）

```
web-spa-cf/
├── public/template/dabao/   # 构建前由 sync-dabao 从 niuniucms 拷贝；打进 dist
├── scripts/sync-dabao.mjs   # 同步 dabao 静态资源
├── src/                     # React 源码
├── index.html
├── vite.config.ts
├── package.json
├── .env.example             # 变量模板（可复制为 .env）
├── .env                     # 本地私密配置（已在 .gitignore，勿提交）
└── dist/                    # npm run build 产出；上传 CF 只用这里面的内容
```

---

## 3. 环境要求

- **Node.js**：建议 18+（与当前 Vite 5 兼容即可）  
- **磁盘布局**：若使用 **`npm run sync:dabao`**，仓库根目录下需能访问 **`../niuniucms/template/dabao`**（即 `web-spa-cf` 与 `niuniucms` 同级）。  
- **PHP 站**：需开启后台 **`api_on`**，否则 JSON 接口不可用。

---

## 4. 环境变量说明

所有 **`VITE_*`** 变量在 **`npm run build` 时写入 JS**，改完后必须 **重新 build** 再上传 **dist**。

| 变量 | 是否必填 | 说明 |
|------|----------|------|
| **`VITE_API_ORIGIN`** | **生产强烈建议必填** | PHP 站点根 URL，无尾斜杠，如 `https://www.example.com`。未配置时生产包会请求当前 Pages 域名，拿不到 JSON。 |
| **`VITE_TEMPLATE_ASSET_ORIGIN`** | 可选 | 非空时 dabao 的 CSS/图片走该域名绝对路径，可与主站资源完全一致、减小 dist 体积；需处理跨域与 HTTPS。 |
| **`VITE_SEARCH_PREFIX`** | 可选 | 搜索跳转完整前缀（一般以 `keyword=` 结尾）。不配则按 `VITE_API_ORIGIN` 推导默认搜索 URL。 |
| **`VITE_SITE_NAME` / `VITE_SITE_URL`** | 可选 | 页头展示名、首页链接。 |
| **`VITE_LOGO_URL` / `VITE_MOBILE_QR_URL`** | 可选 | 覆盖默认 dabao 内 logo、二维码图。 |

复制模板：

```bash
cp .env.example .env
# 编辑 .env 后切勿提交到公开仓库（.env 已在 .gitignore）
```

---

## 5. 本地开发教程

```bash
cd web-spa-cf
npm ci                    # 或 npm install
cp .env.example .env      # 首次：复制后编辑
```

在 **`.env`** 中至少设置：

```env
VITE_API_ORIGIN=https://你的PHP站点根
```

启动开发服务：

```bash
npm run dev
```

浏览器访问终端里提示的本地地址（一般为 `http://127.0.0.1:5173`）。

**说明：**

- 开发模式下请求接口走 **`/legacy-api`**，由 Vite 代理到 **`VITE_API_ORIGIN`**，减轻浏览器跨域问题。  
- 启动前会自动执行 **`sync:dabao`**（若存在上级 `niuniucms/template/dabao`）。

---

## 6. 生产构建与上传

### 6.1 构建

```bash
cd web-spa-cf
# 确认 .env 中 VITE_API_ORIGIN 等已正确
npm ci
npm run build
```

成功后产物在 **`dist/`**：应包含 **`index.html`**、**`assets/`**、**`template/`**（dabao 静态文件）。

### 6.2 上传到 Cloudflare（手工）

**只上传 `dist/` 目录内的所有文件**，不要上传：

- `src/`、`node_modules/`、`package.json` 等源码目录  
- 不要把整个 `dist` 文件夹名当成网站根（解压后根目录应直接是 `index.html`）

文件数量一般为 **几十～一两百**，若出现 **两千多个文件**，多半是误传了 **`node_modules`**。

### 6.3 更换接口域名后

修改 **`.env`** → 再执行 **`npm run build`** → 再上传新的 **`dist`**。

---

## 7. Cloudflare Pages 配置示例

### 方式 A：连接 Git（推荐）

在 Pages 项目 **Settings → Builds & deployments**：

| 配置项 | 填写 |
|--------|------|
| **Root directory（构建根目录）** | `web-spa-cf` |
| **Build command** | `npm ci && npm run build` |
| **Build output directory** | `dist` |

在 **Environment variables（构建环境）** 中添加与本地一致的 **`VITE_API_ORIGIN`**（以及其它需要的 **`VITE_*`**）。保存后重新部署。

### 方式 B：Wrangler CLI

```bash
cd web-spa-cf
npm run build
npx wrangler pages deploy dist --project-name=你的项目名
```

（需本机已登录 Cloudflare，且构建时已加载 `.env` 或使用 CLI 传入变量。）

---

## 8. dabao 静态资源同步

- **`npm run build`** / **`npm run dev`** 前会执行 **`npm run sync:dabao`**，从 **`../niuniucms/template/dabao`** 拷贝 **`css/`、`Images/`、`js/`、`conf.json`** 到 **`public/template/dabao/`**。  
- 你在 CMS 里二改 dabao 后，重新构建即可把新样式打进 **dist**。  
- 若构建环境 **没有** 上级 `niuniucms`，脚本会跳过同步，使用仓库里已有 **`public`**（因此 **`public/template/dabao` 建议纳入 Git**）。

可选 **`VITE_TEMPLATE_ASSET_ORIGIN`**：让 CSS/图片直接指向主站 URL，减少 dist 体积（需浏览器能访问该域名资源）。

---

## 9. 与 PHP 接口的约定

- 触发 JSON：URL 带 **`?json=1`** 或请求头 **`X-Requested-With: XMLHttpRequest`**（与 niuniucms `core/core.php` 一致）。  
- 后台 **`api_on`** 必须开启。  
- 典型响应：`{ "code": 0, "message": { ... } }`；`code !== 0` 表示业务错误。  

首页演示使用 **`src/api/legacyHome.ts`** 请求根路径 JSON；更多页面需自行扩展接口与组件。

**跨域：** SPA 域名与 PHP 域名不一致时，必须在 **PHP 或 Nginx** 配置 **CORS**；若使用 **`credentials: 'include'`**，需精确 **`Access-Control-Allow-Origin`**，不能用 `*`。

---

## 10. 常见问题排查

| 现象 | 可能原因 |
|------|----------|
| 页面空白，控制台 CSS MIME 为 `text/html` | 上传的不是 **dist** 内容，或 **`template`** 未打进包；检查构建日志与 **dist** 内是否有 **`template/dabao/css`**。 |
| 拉不到数据 / 「返回不是 JSON」 | **构建未带 `VITE_API_ORIGIN`**；**api_on** 关闭；请求打到错误域名；**CORS** 拦截。 |
| 更换 `.env` 后线上仍旧 | 必须 **重新 `npm run build` 并重新上传 dist**；浏览器 **强刷** 或无痕。 |
| Cloudflare 显示部署成功但 404 | **Output directory** 填错（应为 **`dist`**）；或构建根目录未设为 **`web-spa-cf`**。 |

---

## 附录：可行性摘要

| 维度 | 结论 |
|------|------|
| 技术栈 | Vite 5 + React 18，产物为静态文件。 |
| 数据 | 来自 niuniucms PHP；非完整 REST，需按需扩展 JSON。 |
| SEO | 纯 CSR；要强 SEO 需 SSR 或保留 PHP 页面。 |

---

如需扩展「分类页 / 详情 / 播放」等，建议在 PHP 侧逐步约定稳定 JSON，再在 `src/` 增加路由与页面组件。
