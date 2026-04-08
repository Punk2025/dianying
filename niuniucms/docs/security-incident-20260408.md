# 安全事件处置记录（2026-04-08）

本文档用于记录本次线上扫描与高危入口暴露的排查结论、已执行修复和后续动作，便于运维与开发交接。

## 1. 事件摘要

- 现象：访问日志出现大量探测请求（含 `/.git`、`/.env` 等常见路径）。
- 关键发现：安装入口可公网访问，`/install/index.php` 与 `/install/index.php?action=database` 返回 200。
- 风险等级：高危（可能被重装、重置管理员、覆盖数据）。

## 2. 已确认证据

- 运行日志中出现外网 IP 访问安装数据库动作：
  - 文件：`runtime/log/202604/db_exec.php`
  - 特征：存在批量 `DROP TABLE` / `INSERT` 记录
  - 路径：`/install/index.php?action=database`
- 公网探测结果（修复前）：
  - `https://sesewu3.eu.cc/install/index.php` -> 200
  - `https://sesewu3.eu.cc/install/index.php?action=database` -> 200

## 3. 已完成修复

### 3.1 安装器强制封禁（代码层）

- 文件：`install/index.php`
- 改动：在入口最前置增加已安装检查。
  - 条件：存在 `install.lock` 或 `config/config.php`
  - 行为：直接返回 `403 Forbidden` 并退出
- 目的：彻底阻断线上环境再次触发安装流程。

### 3.2 探测路径前置拦截（应用层）

- 文件：`index.php`
- 改动：对常见扫描路径前置拒绝（命中即 403）：
  - `/.git` `/.env` `/.svn` `/.ht`
  - `/phpmyadmin` `/wp-admin` `/wp-login.php` `/adminer`
  - `/vendor/` `/composer.json` `/composer.lock` `/install/`
- 目的：减少探测请求进入业务逻辑，降低被误用风险。

### 3.3 修复后外网验证

- `https://sesewu3.eu.cc/install/index.php` -> 403
- `https://sesewu3.eu.cc/install/index.php?action=database` -> 403
- 首页 `https://sesewu3.eu.cc/` -> 200（业务正常）

## 4. 当前影响评估

- 现阶段：核心高危入口已封堵，风险显著下降。
- 仍需警惕：若修复前被恶意触发安装，可能已造成账号/配置/数据异常变更。

## 5. 待执行清单（建议当天完成）

1. 立即修改凭据：
   - 后台管理员密码
   - 数据库账号密码
   - 面板与 SSH 凭据
2. 核查管理员与配置是否被异常改动。
3. 执行数据库与站点文件备份（保留事件前后快照）。
4. 在 Nginx 增加服务器层拦截（建议）：
   - 永久拒绝访问 `/.git` `/.env` `/install` 等敏感路径。
5. 保留日志证据：
   - `runtime/log/202604/db_exec.php`
   - 相关访问日志与错误日志

## 6. 复盘建议

- 部署策略调整：生产目录避免直接暴露仓库目录（不放 `.git`）。
- 上线检查清单加入“安装器不可访问”项。
- 建议将安全拦截优先放在 Nginx/WAF 层，应用层作为补充。

