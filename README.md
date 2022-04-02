# Xhprof


## 概述

「Xhprof」是为了在生产环境中使用而打造的。它对性能的影响最小，同时收集足够的信息用于诊断性能问题。


## 运行环境
- PHP 7.0+
- xhprof extension
- redis extension

## 安装方法

通过composer管理您的项目依赖，可以在你的项目根目录运行：

    $ composer require phpxxb/xhprof


## 快速使用

### 常用方法

| 方法名 | 解释 |
|:------------------|:------------------------------------|
|Xhprof\Xhprof\index() | 页面输出方法，用于展示xhprof搜集到的运行信息 |
|Xhprof\Xhprof\xhprofStart() | 开启监听方法，用于处理数据的收集、存储、加工等 |

### OssClient初始化

SDK的OSS操作通过OssClient类完成的，下面代码创建一个OssClient对象:

```php
<?php
$accessKeyId = "<您从OSS获得的AccessKeyId>"; ;
$accessKeySecret = "<您从OSS获得的AccessKeySecret>";
$endpoint = "<您选定的OSS数据中心访问域名，例如oss-cn-hangzhou.aliyuncs.com>";
try {
    $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
} catch (OssException $e) {
    print $e->getMessage();
}
```

### 文件操作

文件(又称对象,Object)是OSS中最基本的数据单元，您可以把它简单地理解为文件，用下面代码可以实现一个Object的上传：

```php
<?php
$bucket = "<您使用的Bucket名字，注意命名规范>";
$object = "<您使用的Object名字，注意命名规范>";
$content = "Hello, OSS!"; // 上传的文件内容
try {
    $ossClient->putObject($bucket, $object, $content);
} catch (OssException $e) {
    print $e->getMessage();
}
```

### 存储空间操作

存储空间(又称Bucket)是一个用户用来管理所存储Object的存储空间,对于用户来说是一个管理Object的单元，所有的Object都必须隶属于某个Bucket。您可以按照下面的代码新建一个Bucket：

```php
<?php
$bucket = "<您使用的Bucket名字，注意命名规范>";
try {
    $ossClient->createBucket($bucket);
} catch (OssException $e) {
    print $e->getMessage();
}
```

### 返回结果处理

OssClient提供的接口返回返回数据分为两种：

* Put，Delete类接口，接口返回null，如果没有OssException，即可认为操作成功
* Get，List类接口，接口返回对应的数据，如果没有OssException，即可认为操作成功，举个例子：

```php
<?php
$bucketListInfo = $ossClient->listBuckets();
$bucketList = $bucketListInfo->getBucketList();
foreach($bucketList as $bucket) {
    print($bucket->getLocation() . "\t" . $bucket->getName() . "\t" . $bucket->getCreateDate() . "\n");
}
```
上面代码中的$bucketListInfo的数据类型是 `OSS\Model\BucketListInfo`


### 运行Sample程序

1. 修改 `samples/Config.php`， 补充配置信息
2. 执行 `cd samples/ && php RunAll.php`

### 运行单元测试

1. 执行`composer install`下载依赖的库
2. 设置环境变量

        export OSS_ACCESS_KEY_ID=access-key-id
        export OSS_ACCESS_KEY_SECRET=access-key-secret
        export OSS_ENDPOINT=endpoint
        export OSS_BUCKET=bucket-name

3. 执行 `php vendor/bin/phpunit`

## License

- MIT

## 联系我们

- [阿里云OSS官方网站](http://oss.aliyun.com)
- [阿里云OSS官方论坛](http://bbs.aliyun.com)
- [阿里云OSS官方文档中心](http://www.aliyun.com/product/oss#Docs)
- 阿里云官方技术支持：[提交工单](https://workorder.console.aliyun.com/#/ticket/createIndex)

[releases-page]: https://github.com/aliyun/aliyun-oss-php-sdk/releases
[phar-composer]: https://github.com/clue/phar-composer
