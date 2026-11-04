<?php
header('Content: application/json"; charset=utf-8'); //统一设置所有接口的返回类型

// require __DIR__ . '/vendor/autoload.php'; // 如果之前没装，这行会报错，可以暂时删掉或不管

//获取请求信息
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

//去除路径末尾的斜杠，防止出现 /api/users/ 匹配失败的情况
$requestUri = rtrim($requestUri, '/');

//简易的路由分发（相当于Gin的r.Get / r.POST）
switch ($requestUri) {
    case '/':
        if ($method === 'GET') {
            echo json_encode(["message" => "Hello, PHP!"]);
            /*
            echo "Hello, PHP!\n";
        } else {
            http_response_code(405);
            echo "Method Not Allowed\n";
*/
        }
        break;

    case '/api/users':
        if ($method === 'GET') {
            // 【新增】从数据库查数据
            $dsn = 'mysql:host=127.0.0.1;port=3306;dbname=learn;charset=utf8mb4';
            try {
                $pdo = new PDO($dsn, 'root', 'myzx'); // 用户名 root, 密码 myzx
                $stmt = $pdo->query('SELECT * FROM users');
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($users, JSON_UNESCAPED_UNICODE);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["error" => "DB Error: " . $e->getMessage()]);
            }
        } elseif ($method === 'POST') {
            // 【新增】写入数据到数据库
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || !isset($input['name'])) {
                http_response_code(400);
                echo json_encode(["error" => "Missing name parameter"]);
                exit;
            }

            $dsn = 'mysql:host=127.0.0.1;port=3306;dbname=learn;charset=utf8mb4';
            try {
                $pdo = new PDO($dsn, 'root', 'myzx');
                $stmt = $pdo->prepare('INSERT INTO users (name) VALUES (:name)');
                $stmt->execute(['name' => $input['name']]);

                echo json_encode([
                    "status" => "Created",
                    "data" => ["id" => $pdo->lastInsertId(), "name" => $input['name']]
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["error" => "DB Error: " . $e->getMessage()]);
            }
        }
        break;
    /*
        if ($method === 'GET') {
            $users = [
                ["id" => 1, "name" => "Admin"],
                ["id" => 2, "name" => "User"]
            ];
            header('Content-Type: application/json');
            echo json_encode($users);
        } elseif ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input === null) {
                http_response_code(400);
                echo "Invalid JSON\n";
            } else {
                header('Content-Type: application/json');
                echo json_encode(["status" => "Created", "user" => $input]);
            }
        } else {
            http_response_code(405);
            echo "Method Not Allowed\n";
        }
        break;
*/

    default:
        http_response_code(404);
        echo "Not Found\n";
        break;
}
