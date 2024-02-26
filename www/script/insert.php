<?php
// データベース接続情報
// ClousSQLへ
$host = '0.0.0.0';
// Local DBへ
//$host = 'mysql';
$dbname = 'performance_test';
$username = 'test_user';
$password = 'test_pass';

// 関数定義
// client側で計算する方法
function insert_data_type01($numInserts, $pdo) {
    $start_time = microtime(true);
    try {
        $stmt = $pdo->prepare("INSERT INTO test_data (name, email) VALUES (:name, :email)");

        $pdo->beginTransaction();

        for ($i = 0; $i < $numInserts; $i++) {
            $uuid = generateUUID();
            $randomString = generateRandomString(10);
            $randomNumber = rand(0, 999999);

            $name = 'test_data_' . $randomNumber. '_'. $i;
            $email = 'test_data_' . $uuid . '_' . $i . '@' . $randomString . '.com';

            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
        }

        $pdo->commit();

        echo $numInserts. "件のデータを挿入しました。";
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "[insert_data_type01]挿入中にエラーが発生しました: " . $e->getMessage();
    } finally {
        // 処理時間を計算して表示する
        $end_time = microtime(true);
        $execution_time = $end_time - $start_time;
        echo "処理時間: ".$execution_time." 秒です。";
    }

    return $execution_time;
}

// server側で計算する方法
function insert_data_type02($numInserts, $pdo) {
    $start_time = microtime(true); // 開始時間を取得
    try {
        $sql = "INSERT INTO test_data (name, email) SELECT CONCAT('test_data_', FLOOR(RAND() * 1000000)), CONCAT('test_data_', UUID(), '@', FLOOR(RAND() * 1000000), '.jp');";
        $pdo->beginTransaction();

        $insertedCount = 0;
        for ($i = 0; $i < $numInserts; $i++) {
            $pdo->query($sql);
            $insertedCount++;

            // 1000件ごとにコミットを実行
            if ($insertedCount % 1000 === 0) {
                $pdo->commit();
                echo $insertedCount."件のデータを挿入しました。\n";
                $pdo->beginTransaction();
            }
        }

        $pdo->commit();
        echo $numInserts. "件のデータを挿入しました。";
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "[insert_data_type02]挿入中にエラーが発生しました: " . $e->getMessage();
    } finally {
        // 処理時間を計算して表示する
        $end_time = microtime(true);
        $execution_time = $end_time - $start_time;
        echo "処理時間: ".$execution_time." 秒です。";
    }

    return $execution_time;
}

// server側で計算し、BULK INSERTの方法
function insert_data_type03($numInserts, $pdo) {
    $start_time = microtime(true);

    try {
        $sql = "INSERT INTO test_data (name, email) VALUES ";
        $pdo->beginTransaction();

        $insertedCount = 0;
        // 1000件ごとにSQL文を生成し、一括で実行
        for ($i = 0; $i < $numInserts; $i++) {
            $sql .= "(CONCAT('test_data_', FLOOR(RAND() * 1000000)), CONCAT('test_data_', UUID(), '@', FLOOR(RAND() * 1000000), '.jp')),";
            $insertedCount++;

            // 1000件ごとにコミットを実行
            if ($insertedCount % 100000 === 0) {
                // 最後のカンマを除去
                $sql = rtrim($sql, ',');

                // SQLを実行
                $pdo->query($sql);
                $pdo->commit();

                echo $insertedCount . "件のデータを挿入しました。\n";
                $pdo->beginTransaction();

                // 新しいSQL文を生成
                $sql = "INSERT INTO test_data (name, email) VALUES ";
            }
        }

        // 余った分の処理
        if ($insertedCount % 10000 !== 0) {
            // 最後のカンマを除去
            $sql = rtrim($sql, ',');
            // SQLを実行
            $pdo->query($sql);
            $pdo->commit();
        }

        echo $numInserts. "件のデータを挿入しました。";
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "[insert_data_type03]挿入中にエラーが発生しました: " . $e->getMessage();
    } finally {
        // 処理時間を計算して表示する
        $end_time = microtime(true);
        $execution_time = $end_time - $start_time;
        echo "処理時間: ".$execution_time." 秒です。";
    }

    return $execution_time;
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

function generateUUID() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
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

// insert件数
$numInserts = 1000000;

// 関数呼び出し
insert_data_type03($numInserts, $pdo);
?>
