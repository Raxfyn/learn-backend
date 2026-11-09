//
- Job ≠ 立即执行：dispatch 只写 jobs 表，worker 才是执行者
- payload 里模型只存主键（SerializesModels），worker 重查库
- 失败会被 release 回队列，$tries 用尽才进 failed_jobs
- backoff 让 available_at 推后 → "--once 看不到活" 不是 bug
- at-least-once：重试 = 可能重复执行 → 先问"跑两遍会怎样"
- Event 解耦：Controller 只喊事件，副作用靠 Listener 订阅
- sync vs database：差值是"请求内同步做重活"的成本


//
测试
- 测 API 一律 getJson/postJson（自动 Accept: application/json）
- 碰数据库就要 RefreshDatabase；no such table = 忘了它
- fake 会截断调用链，按层断言：
    Event::fake  → 断言事件
    Queue::fake  → 断言 Job（queued listener 在 CallQueuedListener 桶里，不是自己类名）
    Mail::fake   → 断言邮件；但不渲染视图，内容要单独 render() 测
- handle() 是普通方法，可跳过队列直接调用
迁移
- 迁移必须能从零重建（migrate:fresh 能过）
- 补历史迁移的时间和顺序靠文件名，make:migration 不行
- 加列前 hasColumn 守卫，建表前 hasTable 守卫


//
鉴权（Sanctum）
- 密码：迁移 nullable（老数据）→ 守卫 empty(password) → 401 而不是 500
- Model：$hidden 隐藏序列化，casts()['password' => 'hashed'] 自动 bcrypt
- 登录失败三种情况统一 '凭据不正确'，防邮箱枚举
- token 明文只在响应出现一次，库里存 sha256（{id}|{plain}）
- 撤销：currentAccessToken()->delete() 单端；tokens()->delete() 全端
- 接口公开判据：拿不到 token 的人看到响应会不会有损失

测试陷阱
- 同一测试内多次请求，app 不重启 → guard 缓存 user → 必须 forgetGuards()
- actingAs 覆盖缓存，所以多身份测试用 actingAs 不会暴露这个问题
- worker 是长驻进程，Job 里禁用 Auth::user()，用户必须显式传入
- "An error occurred inside PHPUnit" = PHP 没加载成功，先 php -l


//
授权（Policy）
- 认证解决"你是谁"(401)，授权解决"你能干啥"(403)，别混
- 关卡顺序：auth(401) → 绑定(404) → authorize(403) → rules(422)
- Policy 参数顺序：(当前用户, 目标对象)；$user->is($target) 比主键
- Form Request 里用 Gate::allows()，$user->can() 需要 AuthorizesRequests trait
- authorize() 在 rules() 之前执行，越权请求不浪费验证、不泄露字段
- 管理员状态只能 forceFill/SQL 产生，$fillable + validated() 双重拦截
- 删除前先 $user->tokens()->delete()，否则留孤儿 token
- 唯一索引是最后防线，应用层验证是为了给用户 422 而不是 500
- 测试：先断言前提（assertTrue($admin->is_admin)）再断言结果
- 取库里真实值用 fresh()，内存实例不会自动同步


//
### 响应结构（唯一出口 app/Http/ApiResponse.php）
成功：{"data": ..., "message": "..."}
失败：{"data": null, "message": "...", "errors": {...}}
分页：{"data": [...], "meta": {current_page, per_page, total, last_page}, "message": "..."}

### 状态码约定
200 成功 / 201 创建成功 / 401 未认证 / 403 无权限 / 404 不存在 / 422 校验失败 / 429 限流
删除用 200 + message（不用 204，因为 204 不能带响应体）

### 三条规则
1. 控制器里不写裸 response()->json()，只用 ApiResponse
2. 输出字段只由 Resource 白名单决定；$hidden 是第二道防线
3. 异常统一在 bootstrap/app.php 的 withExceptions 里转成同一结构

### 踩过的坑
- JsonResource 默认包一层 data → 自建统一响应时用 withoutWrapping()，否则 {data:{data:...}}
- Handler::prepareException 会先翻译异常类型：
  ModelNotFound → NotFoundHttpException，AuthorizationException → AccessDeniedHttpException
  → 回调要接 HttpException，写 AuthorizationException 永远不触发
- 回调 return null = 不接管，web 路由不受影响
- 测试取值路径：JSON 用 data.token；数据库列用 token
- assertJsonStructure 用嵌套数组，点号路径只给 assertJsonPath


//
## Day 10 · 文件上传

### 磁盘选择
- public  → 封面/头像，storage:link 软链，可直接访问
- local   → 私有文件（身份证、合同），不在 web 根，必须走控制器或签名 URL
- s3      → 换 .env 即可，代码只认 Storage::disk()

### 三条安全决策
- 文件名必须重命名（hashName），绝不用 getClientOriginalName()：防路径穿越/双扩展名
- 存储目录不可执行：文件在 storage/app/public，不在 public/
- cover_path 不进 $fillable：路径只能服务端决定

### 校验
- image   → 读文件头判断真实内容（改名的 .php 过不去）
- mimes   → 白名单扩展名
- max:2048 → 单位 KB（2MB）

### 孤儿文件
- Post::deleting 事件里删文件：覆盖所有 Eloquent 删除路径
- 数据库 cascade 不触发 Eloquent 事件 → 删用户时文件会残留 → 改为应用层逐篇删
- 兜底：covers:prune 命令定期对账（文件系统不在事务里）

### 私有文件访问
- 本人访问：登录 + Policy + Storage::download()
- 分享链接：URL::temporarySignedRoute + middleware('signed')


//