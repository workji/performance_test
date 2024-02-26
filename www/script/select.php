<?php
// データベース接続情報
// ClousSQLへ
//$host = '0.0.0.0';
// Local DBへ
$host = 'mysql';
$dbname = 'performance_test';
$username = 'test_user';
$password = 'test_pass';

function select_data_type01($pdo) {
    $start_time = microtime(true);

    // SELECT 文を準備
    $sql = "SELECT * FROM test_data limit 100000";

    // SELECT 文を実行してデータを取得
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $end_time = microtime(true);

    // 実行時間を計算
    $execution_time = $end_time - $start_time;

    // 取得したデータを表示（例）
//    foreach ($data as $row) {
//        echo "ID: " . $row['id'] . ", Name: " . $row['name'] . ", Email: " . $row['email'] . "\n";
//    }

    echo "SELECT 文の実行時間: ".$execution_time." 秒です。"; // 実行時間を表示
    return $execution_time; // 実行時間を返す
}

function select_data_type02($pdo) {
    $start_time = microtime(true);

    // SELECT 文を準備
    $sql = "SELECT * FROM test_data";

    // SELECT 文を実行してデータを取得
    $stmt = $pdo->query($sql);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // データを処理する
//        echo "ID: " . $row['id'] . ", Name: " . $row['name'] . ", Email: " . $row['email'] . "\n";
    }

    $end_time = microtime(true);

    // 実行時間を計算
    $execution_time = $end_time - $start_time;

    echo "SELECT 文の実行時間: ".$execution_time." 秒です。"; // 実行時間を表示
    return $execution_time; // 実行時間を返す
}

function select_data_type03($pdo, $sql, $exec_num) {
    $query_times = [];

    for ($i = 0; $i < $exec_num; $i++) {

        $start_time = microtime(true);
        $stmt = $pdo->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // データを処理する
        }
        $end_time = microtime(true);
        $query_times[] = $end_time - $start_time;

        sleep(2);
    }

    return $query_times;
}

// 実行Script

// PDOインスタンスを作成
try {
    $dsn = "mysql:host=$host;dbname=$dbname";
    echo "\n接続先: ".$dsn;
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("\n接続失敗: " . $e->getMessage());
}

$num_selects = 1000000;
$sql = "SELECT * FROM test_data limit " . $num_selects;
$exec_num = 10;
$query_times = select_data_type03($pdo, $sql, $exec_num);
echo "\n" . $num_selects . "件データのselect時間です。\n";
print_r($query_times);

