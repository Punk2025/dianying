# 代理线与跳转域名开发说明

本文档描述本项目（niuniucms）当前代理线能力与配置方式。

## 1. 目标

- 支持代理账号（唯一代理码）
- 支持入口 `/?agent=CODE` 自动 302
- 支持两种跳转模式：
  - `1`：H5 线（随机子域落地）
  - `2`：下载线（固定下载页路径）
- 记录基础入口跳转日志

## 2. 表结构

执行：`install/upgrade_agent_20260408.sql`

- `nncms_agent`：代理账号
- `nncms_agent_log`：代理日志（当前默认记录 `entry_jump`）

## 3. 后台入口

- 菜单：`代理线`
- 路由：`admin/index.php?0=agent&1=list`
- 功能：
  - 新增/编辑/删除代理
  - 设置跳转域名配置

## 4. 配置项（config.php）

- `qd_url`：默认前端落地（当前仅作兜底，不强制重定向首页）
- `agent_qd_url`：代理专用前端主机池（逗号分隔）
- `agent_h5_domain_pool`：H5 随机主域池（逗号分隔）
- `agent_download_path`：下载页 hash 路由（默认 `#/pages/download/download2`）
- `agent_skip_hosts`：这些 host 跳过代理入口跳转（逗号分隔）

## 5. 跳转逻辑

实现：`model/agent.php` -> `agent_entry_jump_if_needed()`

触发时机：前台入口请求（GET，route=index）且 URL 带 `?agent=`

流程：
1. 查 `nncms_agent.code`，且 `status=1`
2. 从 `agent_qd_url` 取随机 host（为空回退 `qd_url`）
3. 按 `jump_mode`：
   - `1`：`http://{随机子域}.{随机主域}/#/?agent=CODE`
   - `2`：`https://{frontHost}/{agent_download_path}?agent=CODE`
4. 记一条 `agent_log(type=entry_jump)`，然后 302

## 6. 与前台主路由的关系

挂载位置：`index.inc.php`

在常规前台路由分发前执行：

- `ad_inject_nncms(...)`
- `agent_entry_jump_if_needed($route)`（若命中会直接 exit）

## 7. 注意事项

- 生产请先配置 `agent_qd_url`，避免无可用 host 导致不跳转
- `agent_h5_domain_pool` 建议配可用主域并做好 DNS/证书
- `agent_skip_hosts` 用于 API/后台等不应跳前端的 host
- 如果要“无 agent 也强制跳 `qd_url`”，需另加开关与逻辑（当前默认不强制）

