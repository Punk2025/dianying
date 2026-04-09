# SafeW 频道通知接入说明

本文档描述如何在当前项目里启用 SafeW 机器人通知（自动发现频道 chat_id + 定时推送视频更新）。

## 1. 先决条件

- 已通过 `@BotFather` 获取 `BOT_TOKEN`
- 已将机器人拉进目标群/频道，并设置为管理员
- 服务器可访问 SafeW Bot API：`https://api.safew.org`

官方文档：<https://docs.safew.org/bot-api/>

## 2. 表结构

执行 SQL（可选，脚本首次运行也会自动创建）：

- `install/upgrade_safew_20260409.sql`

涉及数据表：

- `nncms_safew_chat`：保存已发现的群/频道（chat_id）
- `nncms_safew_push_log`：发送日志与去重记录

## 3. 自动发现 chat_id

在群/频道里发送一条消息后，执行：

```bash
php /www/wwwroot/sesewu3.eu.cc/niuniucms/cli/safew_sync_updates.php "<BOT_TOKEN>" 100
```

返回示例：

```json
{"ok":true,"updates":1,"saved_chats":1,"last_update_id":1775...}
```

## 4. 手动测试推送

```bash
php /www/wwwroot/sesewu3.eu.cc/niuniucms/cli/safew_push_worker.php "<BOT_TOKEN>" "https://你的站点域名" 5
```

返回示例：

```json
{"ok":true,"sent":3,"failed":0,"last_push_ts":1775...,"rows":3}
```

## 5. 建议定时任务

建议两个任务：

1) 每 1 分钟同步更新（发现新 chat_id）

```bash
php /www/wwwroot/sesewu3.eu.cc/niuniucms/cli/safew_sync_updates.php "<BOT_TOKEN>" 100 >> /www/wwwlogs/safew_sync.log 2>&1
```

2) 每 5 分钟推送新视频

```bash
php /www/wwwroot/sesewu3.eu.cc/niuniucms/cli/safew_push_worker.php "<BOT_TOKEN>" "https://你的站点域名" 10 >> /www/wwwlogs/safew_push.log 2>&1
```

## 6. 说明

- 推送优先使用 `sendPhoto`；若返回图片参数错误，会自动回退 `sendMessage`
- 去重键：`vod:{vid}:{create_date}`，同频道同一条更新只发一次
- 首次推送会从近 1 小时新增视频开始，避免一次性推太多历史数据

