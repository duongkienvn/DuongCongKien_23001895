<?php
// Run: php bai4/tests/test_integration.php [--keep-browser]
// Creates/drops only a unique thuc_hanh_8_test_* database; never imports into the real database.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../../bai2/config/database.php';

$passed = 0;
$failed = 0;
function expect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function test(string $label, callable $callback): void
{
    global $passed, $failed;
    try {
        $callback(); $passed++; echo "[PASS] $label\n";
    } catch (Throwable $error) {
        $failed++; echo "[FAIL] $label: {$error->getMessage()}\n";
    }
}
function http(string $base, string $method = 'GET', string $query = '', ?string $body = null, string $type = 'application/json'): array
{
    $curl = curl_init($base . '/bai2/api/products.php' . $query);
    $headers = [];
    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Content-Type: ' . $type, 'Accept: application/json'],
        CURLOPT_HEADERFUNCTION => function ($curl, $line) use (&$headers) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
            return strlen($line);
        },
    ]);
    if ($body !== null) curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
    $raw = curl_exec($curl);
    if ($raw === false) throw new RuntimeException('HTTP transport: ' . curl_error($curl));
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    return ['status' => $status, 'headers' => $headers, 'raw' => $raw];
}
function api(array $response, int $status, bool $success): array
{
    expect($response['status'] === $status, "HTTP {$response['status']} instead of $status: {$response['raw']}");
    expect(stripos($response['headers']['content-type'] ?? '', 'application/json') === 0, 'Missing JSON Content-Type');
    expect(($response['headers']['cache-control'] ?? '') === 'no-store', 'Missing no-store');
    $json = json_decode($response['raw'], true, 512, JSON_THROW_ON_ERROR);
    expect(is_array($json) && ($json['success'] ?? null) === $success, 'Wrong success envelope');
    if (!$success) expect(is_string($json['message'] ?? null) && $json['message'] !== '', 'Missing error message');
    return $json;
}
function startServer(string $root, array $environment, string $log): array
{
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    if (!$socket) throw new RuntimeException($error);
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    $process = proc_open([PHP_BINARY, '-S', $address, '-t', $root], [
        0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a'],
    ], $pipes, $root, $environment);
    if (!is_resource($process)) throw new RuntimeException('Cannot start PHP server');
    fclose($pipes[0]);
    $base = 'http://' . $address;
    for ($i = 0; $i < 60; $i++) {
        $ready = @fsockopen('127.0.0.1', (int) substr(strrchr($address, ':'), 1), $errno, $error, 0.1);
        if ($ready) { fclose($ready); return [$process, $base]; }
        usleep(50000);
    }
    proc_terminate($process); proc_close($process);
    throw new RuntimeException('PHP server did not become ready; see ' . $log);
}

$database = 'thuc_hanh_8_test_' . bin2hex(random_bytes(6));
$root = realpath(__DIR__ . '/../..');
$admin = null;
$created = false;
$processes = [];
$log = sys_get_temp_dir() . '/' . $database . '.log';
try {
    expect(extension_loaded('pdo_mysql') && extension_loaded('curl'), 'pdo_mysql and curl are required');
    $admin = connectDatabase();
    echo 'PHP ' . PHP_VERSION . '; MySQL ' . $admin->query('SELECT VERSION()')->fetchColumn() . "\n";
    $admin->exec("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $schema = str_replace('thuc_hanh_8', $database, file_get_contents($root . '/bai1/database.sql'));
    $admin->exec($schema);
    $environment = array_merge(getenv(), ['DB_NAME' => $database]);
    [$server, $base] = startServer($root, $environment, $log); $processes[] = $server;
    echo "TEST DATABASE: $database; URL: $base; LOG: $log\n";
    $count = fn() => (int) $admin->query('SELECT COUNT(*) FROM products')->fetchColumn();
    $valid = json_encode(['name' => '  Bút chì  ', 'price' => 4998, 'stock' => 10], JSON_UNESCAPED_UNICODE);

    test('SQL schema, seed and GET 200 typed/sorted data', function () use ($admin, $base) {
        $columns = $admin->query('SHOW COLUMNS FROM products')->fetchAll(PDO::FETCH_UNIQUE);
        expect($columns['id']['Key'] === 'PRI' && str_contains($columns['id']['Extra'], 'auto_increment'), 'id must be auto increment PK');
        expect($columns['name']['Type'] === 'varchar(100)' && $columns['name']['Null'] === 'NO', 'name schema');
        expect($columns['price']['Type'] === 'int' && $columns['stock']['Type'] === 'int', 'INT schema');
        $rows = api(http($base), 200, true)['data'];
        expect($rows === [
            ['id'=>1,'name'=>'Bút bi','price'=>5000,'stock'=>20],
            ['id'=>2,'name'=>'Vở ô ly','price'=>12000,'stock'=>15],
            ['id'=>3,'name'=>'Thước kẻ','price'=>8000,'stock'=>0],
        ], 'Wrong seed or numeric types');
    });
    test('POST 201 creates ID 4, trim, GET/reload persists and DB agrees', function () use ($base, $valid, $admin) {
        $data = api(http($base, 'POST', '', $valid), 201, true);
        expect($data['data']['id'] === 4, 'Expected ID 4');
        $row = api(http($base), 200, true)['data'][3];
        expect($row === ['id'=>4,'name'=>'Bút chì','price'=>4998,'stock'=>10], 'GET differs from insert');
        expect($admin->query('SELECT name FROM products WHERE id=4')->fetchColumn() === 'Bút chì', 'DB differs');
    });
    test('PUT 200 changes price; identical PUT remains 200', function () use ($base, $admin) {
        $body = '{"name":"Bút chì","price":6000,"stock":0}';
        api(http($base, 'PUT', '?id=4', $body), 200, true);
        api(http($base, 'PUT', '?id=4', $body), 200, true);
        expect((int)$admin->query('SELECT price FROM products WHERE id=4')->fetchColumn() === 6000, 'PUT did not persist');
        expect(api(http($base), 200, true)['data'][3]['stock'] === 0, 'GET stale');
    });
    test('DELETE ID 4 -> 200 JSON, DB absent, reload absent; repeat -> 404 JSON', function () use ($base, $admin) {
        $response = http($base, 'DELETE', '?id=4'); api($response, 200, true);
        echo '  DELETE evidence: HTTP ' . $response['status'] . '; ' . $response['headers']['content-type'] . '; ' . $response['raw'] . "\n";
        expect($admin->query('SELECT id FROM products WHERE id=4')->fetchColumn() === false, 'DELETE did not persist');
        expect(count(api(http($base), 200, true)['data']) === 3, 'Deleted row returned on reload');
        api(http($base, 'DELETE', '?id=4'), 404, false);
    });
    test('PUT nonexistent ID -> 404 without insertion', function () use ($base, $valid, $count) {
        api(http($base, 'PUT', '?id=2147483647', $valid), 404, false); expect($count() === 3, 'Unexpected insert');
    });
    foreach (['', '?id=0', '?id=-1', '?id=1.5', '?id=abc', '?id=2147483648', '?id[]=1', '?id=1%20OR%201=1'] as $query) {
        test('PUT/DELETE reject ID ' . ($query ?: '(missing)'), function () use ($base, $query, $valid, $count) {
            api(http($base, 'PUT', $query, $valid), 400, false);
            api(http($base, 'DELETE', $query), 400, false); expect($count() === 3, 'Invalid ID changed rows');
        });
    }
    $invalid = [
        'negative stock'=>'{"name":"A","price":1,"stock":-1}',
        'zero price'=>'{"name":"A","price":0,"stock":0}',
        'decimal price'=>'{"name":"A","price":1.5,"stock":0}',
        'string price'=>'{"name":"A","price":"1","stock":0}',
        'boolean stock'=>'{"name":"A","price":1,"stock":false}',
        'overflow'=>'{"name":"A","price":2147483648,"stock":0}',
        'missing stock'=>'{"name":"A","price":1}',
        'wrong name type'=>'{"name":[],"price":1,"stock":0}',
        'whitespace name'=>'{"name":"  ","price":1,"stock":0}',
        'Unicode whitespace'=>'{"name":"\u00a0\u2003\ufeff","price":1,"stock":0}',
        '101 chars'=>json_encode(['name'=>str_repeat('😀',101),'price'=>1,'stock'=>0]),
        'invalid UTF8'=>"{\"name\":\"\xFF\",\"price\":1,\"stock\":0}",
        'syntax'=>'{oops', 'empty'=>'', 'array'=>'[]', 'null'=>'null', 'scalar'=>'true',
    ];
    foreach ($invalid as $label => $body) {
        test('POST/PUT invalid ' . $label . ' -> 400, DB unchanged', function () use ($base, $body, $count, $admin) {
            $before = $admin->query('SELECT * FROM products ORDER BY id')->fetchAll();
            api(http($base, 'POST', '', $body), 400, false);
            api(http($base, 'PUT', '?id=1', $body), 400, false);
            expect($count() === 3 && $before === $admin->query('SELECT * FROM products ORDER BY id')->fetchAll(), 'Invalid body wrote to DB');
        });
    }
    foreach (['text/plain', 'application/x-www-form-urlencoded', ''] as $type) test('Reject non-JSON Content-Type ' . $type . ' -> 415', function () use ($base, $valid, $type, $count) {
        api(http($base, 'POST', '', $valid, $type), 415, false);
        api(http($base, 'PUT', '?id=1', $valid, $type), 415, false);
        expect($count() === 3, 'Wrong MIME inserted row');
    });
    test('Unsupported PATCH/OPTIONS -> 405 JSON and Allow', function () use ($base) {
        foreach (['PATCH','OPTIONS'] as $method) {
            $r=http($base,$method); api($r,405,false);
            expect($r['headers']['allow'] === 'GET, POST, PUT, DELETE', 'Wrong Allow');
        }
    });
    test('Prepared statements preserve injection/XSS text; Unicode/INT boundaries round-trip', function () use ($base, $count) {
        foreach (["' OR 1=1; DROP TABLE products; --", '<img src=x onerror=alert(1)>', str_repeat('😀',100), "\u{00A0}Bút\u{FEFF}"] as $name) {
            $body=json_encode(['name'=>$name,'price'=>2147483647,'stock'=>2147483647]);
            $id=api(http($base,'POST','',$body,'Application/JSON; charset=utf-8'),201,true)['data']['id'];
            $rows=api(http($base),200,true)['data']; $row=end($rows);
            expect($row['name'] === ($name === "\u{00A0}Bút\u{FEFF}" ? 'Bút' : $name), 'Name corrupted');
            expect($row['price']===2147483647 && $row['stock']===2147483647,'Overflow');
            api(http($base,'DELETE','?id='.$id),200,true);
        }
        expect($count()===3,'Injection changed table');
    });
    test('Database CHECK constraints reject negative stock / zero price', function () use ($admin, $count) {
        foreach (['(\'bad\',1,-1)', '(\'bad\',0,0)'] as $values) {
            $rejected=false;
            try {$admin->exec('INSERT INTO products(name,price,stock) VALUES '.$values);} catch(PDOException $e) {$rejected=true;}
            expect($rejected,'DB accepted invalid row');
        }
        expect($count()===3,'Constraint wrote row');
    });
    test('Missing table: all CRUD -> 500 JSON without SQL/credentials leakage', function () use ($admin, $base, $valid) {
        $admin->exec('RENAME TABLE products TO products_backup');
        try {
            foreach (['GET','POST','PUT','DELETE'] as $method) {
                $data=api(http($base,$method,in_array($method,['PUT','DELETE'])?'?id=1':'',in_array($method,['POST','PUT'])?$valid:null),500,false);
                expect(!preg_match('/SQLSTATE|password|root|products_backup/i',$data['message']),'DB details leaked');
            }
        } finally {$admin->exec('RENAME TABLE products_backup TO products');}
    });
    test('Foreign key blocks DELETE -> 500 JSON and product stays in DB', function () use ($admin, $base) {
        $admin->exec('CREATE TABLE product_refs(product_id INT, FOREIGN KEY(product_id) REFERENCES products(id)) ENGINE=InnoDB');
        try {
            $admin->exec('INSERT INTO product_refs VALUES(1)');
            api(http($base,'DELETE','?id=1'),500,false);
            expect($admin->query('SELECT id FROM products WHERE id=1')->fetchColumn()!==false,'Failed DELETE removed row');
        } finally {$admin->exec('DROP TABLE product_refs');}
    });
    [$badServer,$badBase]=startServer($root,array_merge($environment,['DB_PORT'=>'1']),$log);$processes[]=$badServer;
    test('Connection refused: all CRUD -> 500 JSON; validation still runs before DB',function() use($badBase,$valid){
        foreach(['GET','POST','PUT','DELETE'] as $method) api(http($badBase,$method,in_array($method,['PUT','DELETE'])?'?id=1':'',in_array($method,['POST','PUT'])?$valid:null),500,false);
        api(http($badBase,'POST','','{}'),400,false);
        api(http($badBase,'DELETE','?id=0'),400,false);
        api(http($badBase,'PATCH'),405,false);
    });
    [$authServer,$authBase]=startServer($root,array_merge($environment,['DB_PASSWORD'=>'invalid-test-'.bin2hex(random_bytes(8))]),$log);$processes[]=$authServer;
    test('Environment password overrides local config: denied login -> 500 JSON for all CRUD',function() use($authBase,$valid){
        foreach(['GET','POST','PUT','DELETE'] as $method) {
            $data=api(http($authBase,$method,in_array($method,['PUT','DELETE'])?'?id=1':'',in_array($method,['POST','PUT'])?$valid:null),500,false);
            expect(!preg_match('/SQLSTATE|password|root|Access denied/i',$data['message']),'Authentication details leaked');
        }
    });
    test('10 consecutive POST -> PUT -> DELETE cycles leave no extra rows',function()use($base,$count){
        for($i=0;$i<10;$i++) {
            $id=api(http($base,'POST','','{"name":"Cycle","price":1,"stock":0}'),201,true)['data']['id'];
            api(http($base,'PUT','?id='.$id,'{"name":"Cycle changed","price":2,"stock":3}'),200,true);
            api(http($base,'DELETE','?id='.$id),200,true);
        }
        expect($count()===3,'CRUD cycle changed final count');
    });
    test('No PHP warning/fatal/parse errors in HTTP server log',function()use($log){
        expect(!preg_match('/PHP (?:Warning|Fatal error|Parse error)|<br\s*\/>/i',file_get_contents($log)),'PHP emitted warning/fatal; see log');
    });
    echo "RESULT: $passed PASS, $failed FAIL\n";
    if (in_array('--keep-browser',$argv,true) && $failed===0) {
        $admin->exec('DELETE FROM products');$admin->exec('ALTER TABLE products AUTO_INCREMENT=1');$admin->exec($schema);
        echo "BROWSER READY: $base/bai3/ (database $database). Send newline to clean up.\n";
        fgets(STDIN);
    }
} catch(Throwable $error) {
    $failed++; echo '[FAIL] Setup/run: '.$error->getMessage()."\n";
} finally {
    foreach(array_reverse($processes) as $process) {proc_terminate($process);proc_close($process);}
    if($created) {$admin->exec("DROP DATABASE IF EXISTS `$database`");echo "CLEANUP: dropped $database\n";}
}
exit($failed===0?0:1);
