<?php
session_start();

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json; charset=utf-8');

function send_json(array $data): void
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['ban_id'])) {
    send_json([
        'success' => false,
        'need_table' => true,
        'message' => 'Vui lòng nhập số bàn để dùng Foodie AI.'
    ]);
}

$message = trim($_POST['message'] ?? '');

if ($message === '') {
    send_json([
        'success' => false,
        'message' => 'Vui lòng nhập nội dung chat.'
    ]);
}

try {
    /*
    |--------------------------------------------------------------------------
    | Lấy menu từ database
    |--------------------------------------------------------------------------
    */
    $stmt = $pdo->query("
        SELECT 
            id, ten_mon, gia, danh_muc, tag_hot, so_lan_goi, 
            rating, is_chay, is_cay, phu_hop_tre_em
        FROM mon_an
        WHERE trang_thai = 'dang_ban'
        ORDER BY tag_hot DESC, so_lan_goi DESC, rating DESC
    ");

    $menu = $stmt->fetchAll();

    /*
    |--------------------------------------------------------------------------
    | Fallback AI: chưa cần Gemini API vẫn chạy được
    |--------------------------------------------------------------------------
    */
    $lower = strtolower($message);

    $wantSet =
        stripos($message, 'set') !== false ||
        stripos($message, 'combo') !== false ||
        stripos($message, 'gợi ý') !== false ||
        stripos($message, 'goi y') !== false ||
        stripos($message, 'ngân sách') !== false ||
        stripos($message, 'ngan sach') !== false ||
        stripos($message, 'người') !== false ||
        stripos($message, 'nguoi') !== false ||
        stripos($message, '100k') !== false ||
        stripos($message, '200k') !== false ||
        stripos($message, '300k') !== false;

    if (!$wantSet) {
        $hotItems = array_slice($menu, 0, 3);

        $names = [];

        foreach ($hotItems as $item) {
            $names[] = $item['ten_mon'];
        }

        $reply = 'Xin chào! Bạn cần Foodie AI tư vấn món ăn gì hôm nay?';

        if (!empty($names)) {
            $reply .= ' Một số món nổi bật là: ' . implode(', ', $names) . '.';
        }

        $result = [
            'type' => 'text',
            'message' => $reply,
            'set_mon' => [],
            'tong_tien' => 0,
            'ly_do' => ''
        ];

        save_ai_history($pdo, (int) $_SESSION['ban_id'], $message, $result);

        send_json([
            'success' => true,
            'data' => $result
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Đọc ngân sách
    |--------------------------------------------------------------------------
    */
    $budget = 300000;

    if (preg_match('/(\d+)\s*k/i', $message, $m)) {
        $budget = (int) $m[1] * 1000;
    } elseif (preg_match('/(\d{5,7})/', $message, $m)) {
        $budget = (int) $m[1];
    }

    /*
    |--------------------------------------------------------------------------
    | Đọc số người
    |--------------------------------------------------------------------------
    */
    $people = 2;

    if (preg_match('/(\d+)\s*(người|nguoi)/iu', $message, $m)) {
        $people = max(1, (int) $m[1]);
    }

    /*
    |--------------------------------------------------------------------------
    | Lọc khẩu vị
    |--------------------------------------------------------------------------
    */
    $noSpicy =
        stripos($message, 'không cay') !== false ||
        stripos($message, 'ko cay') !== false ||
        stripos($message, 'khong cay') !== false;

    $vegetarian =
        stripos($message, 'chay') !== false;

    $filtered = [];

    foreach ($menu as $item) {
        if ($noSpicy && (int) $item['is_cay'] === 1) {
            continue;
        }

        if ($vegetarian && (int) $item['is_chay'] !== 1) {
            continue;
        }

        $filtered[] = $item;
    }

    /*
    |--------------------------------------------------------------------------
    | Sắp xếp ưu tiên món hot + bán chạy
    |--------------------------------------------------------------------------
    */
    usort($filtered, function ($a, $b) {
        $scoreA = ((int) $a['tag_hot'] * 1000)
            + ((int) $a['so_lan_goi'] * 5)
            + ((float) $a['rating'] * 20);

        $scoreB = ((int) $b['tag_hot'] * 1000)
            + ((int) $b['so_lan_goi'] * 5)
            + ((float) $b['rating'] * 20);

        return $scoreB <=> $scoreA;
    });

    /*
    |--------------------------------------------------------------------------
    | Tạo set món
    |--------------------------------------------------------------------------
    */
    $set = [];
    $total = 0;

    foreach ($filtered as $item) {
        $price = (float) $item['gia'];

        if ($price <= 0) {
            continue;
        }

        if ($total + $price <= $budget || count($set) < min(2, $people)) {
            $set[] = [
                'id' => (int) $item['id'],
                'ten_mon' => $item['ten_mon'],
                'gia' => $price,
                'so_luong' => 1
            ];

            $total += $price;
        }

        if (count($set) >= min(4, $people + 1)) {
            break;
        }
    }

    if (empty($set)) {
        $result = [
            'type' => 'text',
            'message' => 'Hiện tại mình chưa tìm được món phù hợp trong menu. Bạn thử tăng ngân sách hoặc đổi khẩu vị nhé.',
            'set_mon' => [],
            'tong_tien' => 0,
            'ly_do' => ''
        ];
    } else {
        $result = [
            'type' => 'recommend_set',
            'message' => 'Mình gợi ý set món này cho ' . $people . ' người.',
            'set_mon' => $set,
            'tong_tien' => $total,
            'ly_do' => 'Set này ưu tiên món HOT, món bán chạy và phù hợp ngân sách khoảng ' . number_format($budget, 0, ',', '.') . 'đ.'
        ];
    }

    save_ai_history($pdo, (int) $_SESSION['ban_id'], $message, $result);

    send_json([
        'success' => true,
        'data' => $result
    ]);
} catch (Throwable $e) {
    error_log('Foodie AI Error: ' . $e->getMessage());

    send_json([
        'success' => false,
        'message' => 'Foodie AI đang lỗi server: ' . $e->getMessage()
    ]);
}

function save_ai_history(PDO $pdo, int $banId, string $message, array $result): void
{
    try {
        $stmt = $pdo->prepare("
            INSERT INTO ai_chat_history 
            (ban_id, user_message, ai_response, response_type, tong_tien_goi_y)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $banId,
            $message,
            json_encode($result, JSON_UNESCAPED_UNICODE),
            $result['type'] ?? 'text',
            $result['tong_tien'] ?? 0
        ]);
    } catch (Throwable $e) {
        error_log('Save AI History Error: ' . $e->getMessage());
    }
}