# 广告图片加密与前端解密开发说明

本文档用于记录当前项目广告图片的加密链路，供后续开发、排错和二次扩展使用。

## 1. 当前链路（结论）

- 上传（后台）：
  - 当 `ad_image_encrypt=1` 时，广告图按 `AES-256-CBC` 加密后落盘为 `.enc`。
  - 数据库 `ad.image` 保存的是 `adimg-<token>` 形式的可访问地址（不是明文 `/upload/ad/...`）。
- 展示（前台）：
  - 当 `ad_image_client_decrypt=1` 时，前端会请求 `adenc-<token>` 获取密文，浏览器解密后再展示。
  - 默认展示地址是 `blob:`（可改为 `data:`）。
  - 解密失败时，自动回退到 `adimg`（服务端解密输出图片）。

## 2. 相关配置

文件：`niuniucms/config/config.php`（默认值见 `config.default.php`）

- `ad_image_encrypt`
  - `1`：上传时加密保存（开启加密体系）
  - `0`：明文保存到 `/upload/ad/...`
- `ad_image_client_decrypt`
  - `1`：前端解密展示（`adenc` + JS）
  - `0`：不走前端解密，直接用 `adimg`（服务端解密）
- `ad_image_client_url_mode`
  - `blob`（默认）：解密后 `URL.createObjectURL(blob)` 展示
  - `data`：解密后转 `data:image/...;base64,...` 展示
- `ad_enc_cors_origin`
  - `''`：同域场景默认即可
  - 非空：`adenc` 响应增加 `Access-Control-Allow-Origin`
- `ad_image_secret`
  - 非空时参与派生 AES 密钥
  - 为空则回退到站点密钥派生

## 3. 路由与职责

- `adimg-<token>`：`niuniucms/route/adimg.php`
  - 服务端读取 `.enc` -> 解密 -> 按 MIME 输出 `image/*`
- `adenc-<token>`：`niuniucms/route/adenc.php`
  - 服务端直接输出密文二进制（`application/octet-stream`），供前端解密

前台路由注册在：`niuniucms/index.inc.php`

## 4. 核心代码位置

- 加密/解密核心：`niuniucms/model/ad.php`
  - `ad_image_encrypt_blob()` / `ad_image_decrypt_blob()`
  - `ad_image_save_encrypted_upload()`
  - `ad_serve_encrypted_image()`（adimg）
  - `ad_serve_encrypted_ciphertext()`（adenc）
  - `ad_render_slot_html()`（生成广告 `<img>`，含 `data-nn-ad-cipher`）
  - `ad_inject_nncms()`（注入前端解密脚本和密钥）
- 前端解密脚本：`niuniucms/static/js/nn_ad_client_decrypt.js`
  - 统一入口：`window.resolveImageUrl(cipherUrl)`
  - `fetch(arrayBuffer)` -> WebCrypto AES-CBC 解密 -> `blob/data` URL

## 5. 数据与文件格式

- 密文路径：`niuniucms/upload/encrypt_image/d/<token>.enc`
- 文件格式：`IV(16字节) + ciphertext`（二进制拼接）
- 算法：`AES-256-CBC`，填充与 OpenSSL 默认一致（PKCS7）

## 6. 旧数据迁移（明文 -> 加密）

后台广告列表页提供 `批量转加密` 按钮：

- 入口：`ad-batchencrypt`（`admin/route/ad.php`）
- 逻辑：
  - 读取旧的本地明文图（通常是 `/upload/ad/...`）
  - 生成 `.enc` 与 `adimg-<token>`
  - 回写到 `ad.image`
- 注意：目录 `niuniucms/upload/encrypt_image/d` 必须可写。

## 7. 常见现象与说明

- 数据库里还是 `adimg-...`，但页面显示 `blob:...`
  - 正常：`adimg` 是存储地址；`blob` 是前端解密后的临时展示地址。
- `blob:` 复制到别的标签/设备打不开
  - 正常：`blob:` 仅在当前页面上下文有效。
- Network 里看到 `adimg` 而不是 `adenc`
  - 可能是前端解密未启用，或解密失败触发回退。

## 8. 排错清单（建议按顺序）

1. 看配置是否为：
   - `ad_image_encrypt=1`
   - `ad_image_client_decrypt=1`
2. 看广告管理页状态灯是否显示“前端解密(blob/data)”
3. 浏览器 Network 检查：
   - `nn_ad_client_decrypt.js` 是否 200
   - `adenc-<token>` 是否 200 且 `content-type=application/octet-stream`
4. 检查 `upload/encrypt_image/d` 目录是否可写
5. 模板缓存问题时，清理 `runtime/cache` 后强刷页面

## 9. 开发建议

- 对新开发同学，优先记住：**存储地址与展示地址是两层语义**。
  - 存储：`adimg` / `.enc`
  - 展示：`blob` 或 `data`（前端运行期）
- 如无跨域需求，`adenc` 建议保持同域，减少 CORS 问题。
- 如需对外可复制的长期 URL，请使用 `adimg`（不要用 `blob`）。

