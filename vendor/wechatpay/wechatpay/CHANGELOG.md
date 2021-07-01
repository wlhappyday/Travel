# 变更历史

## 1.0.3 - 2021-06-28

[变更细节](../../compare/v1.0.2...v1.0.3)

- 初始化`jsonBased`入参判断，`平台证书及序列号`结构体内不能含`商户序列号`，相关问题 [#8](https://github.com/wechatpay-apiv3/wechatpay-php/issues/8);
- 修复文档错误，相关 [#7](https://github.com/wechatpay-apiv3/wechatpay-php/issues/7);
- 优化 `github actions`，针对PHP7.2单独缓存依赖(`PHP7.2`下只能跑`PHPUnit8`，`PHP7.3`以上均可跑`PHPUnit9`);
- 增加 `composer test` 命令并集成进 `CI` 内（测试用例持续增加中）；
- 修复 `PHPStan` 所有遗留问题；

## 1.0.2 - 2021-06-24

[变更细节](../../compare/v1.0.1...v1.0.2)

- 优化了一些性能；
- 增加 `github actions` 覆盖 PHP7.2/7.3/7.4/8.0 + Linux/macOs/Windows环境；
- 提升 `phpstan` 至 `level8` 最严谨级别，并修复大量遗留问题；
- 优化 `\WeChatPay\Exception\WeChatPayException` 异常类接口；
- 完善文档及平台证书下载器用法说明；

## 1.0.1 - 2021-06-21

[变更细节](../../compare/v1.0.0...v1.0.1)

- 优化了一些性能；
- 修复了大量 `phpstan level6` 静态分析遗留问题；
- 新增`\WeChatPay\Exception\WeChatPayException`异常类接口；
- 完善文档及方法类型签名；

## 1.0.0 - 2021-06-18

源自 `wechatpay-guzzle-middleware`，不兼容源版，顾自 `v1.0.0` 开始。

- `APIv2` & `APIv3` 同质化调用SDK，默认为 `APIv3` 版；
- 标记 `APIv2` 为不推荐调用，预期 `v2.0` 会移除掉；
- 支持 `同步(sync)`（默认）及 `异步(async)` 请求服务端接口；
- 支持 `链式(chain)` 请求服务端接口；
