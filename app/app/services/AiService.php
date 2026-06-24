<?php
declare(strict_types=1);

require_once __DIR__ . '/SettingService.php';

class AiService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function chat(int $banId, string $message): array
    {
        $menu = $this->getMenuContext();
        $apiKey = trim(SettingService::get($this->pdo, 'gemini_api_key', ''));

        if ($apiKey === '') {
            $result = $this->fallbackRecommend($menu, $message);
            $this->saveHistory($banId, $message, $result);
            return $result;
        }

        try {
            $prompt = $this->buildPrompt($menu, $message);
            $raw = $this->callGemini($apiKey, $prompt);
            $result = $this->validateAiJson($raw);
        } catch (Throwable $e) {
            $result = $this->fallbackRecommend($menu, $message);
        }

        $this->saveHistory($banId, $message, $result);

        return $result;
    }

    private function getMenuContext(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                id, ten_mon, gia, danh_muc, tag_hot, so_lan_goi, 
                rating, is_chay, is_cay, phu_hop_tre_em
            FROM mon_an
            WHERE trang_thai = 'dang_ban'
            ORDER BY tag_hot DESC, so_lan_goi DESC, rating DESC
        ");

        return $stmt->fetchAll();
    }

    private function buildPrompt(array $menu, string $message): string
    {
        $menuJson = json_encode($menu, JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Bạn là Foodie AI, chatbot tư vấn món ăn cho nhà hàng Việt Nam.

Quy tắc:
- Chỉ được chọn món có trong MENU_DB.
- Nếu khách hỏi tư vấn combo/set món theo số người, ngân sách, khẩu vị, hãy trả type = recommend_set.
- Ưu tiên tag_hot = 1, so_lan_goi cao, rating cao.
- Không bịa món ngoài database.
- Tính tong_tien = tổng gia * so_luong.
- Nếu khách chỉ hỏi bình thường, trả type = text.
- Nội dung tiếng Việt, ngắn gọn, dễ hiểu.

MENU_DB:
$menuJson

Tin nhắn khách:
$message

Chỉ trả JSON hợp lệ theo format:
{
  "type": "text" hoặc "recommend_set",
  "message": "nội dung trả lời",
  "set_mon": [
    {
      "id": number,
      "ten_mon": "string",
      "gia": number,
      "so_luong": number
    }
  ],
  "tong_tien": number,
  "ly_do": "string"
}
PROMPT;
    }

    private function callGemini(string $apiKey, string $prompt): array
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . urlencode($apiKey);

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json'
            ]
        ];

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new RuntimeException('Không gọi được Gemini API.');
        }

        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException('Gemini API lỗi HTTP ' . $httpCode);
        }

        $data = json_decode($response, true);
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        $json = json_decode($text, true);

        if (!is_array($json)) {
            throw new RuntimeException('AI không trả JSON hợp lệ.');
        }

        return $json;
    }

    private function validateAiJson(array $data): array
    {
        $type = $data['type'] ?? 'text';

        if (!in_array($type, ['text', 'recommend_set'], true)) {
            $type = 'text';
        }

        $result = [
            'type' => $type,
            'message' => (string) ($data['message'] ?? ''),
            'set_mon' => [],
            'tong_tien' => 0,
            'ly_do' => (string) ($data['ly_do'] ?? ''),
        ];

        if ($type === 'recommend_set' && !empty($data['set_mon']) && is_array($data['set_mon'])) {
            foreach ($data['set_mon'] as $item) {
                $id = (int) ($item['id'] ?? 0);
                $qty = max(1, (int) ($item['so_luong'] ?? 1));

                $stmt = $this->pdo->prepare("
                    SELECT id, ten_mon, gia
                    FROM mon_an
                    WHERE id = ? AND trang_thai = 'dang_ban'
                ");

                $stmt->execute([$id]);
                $mon = $stmt->fetch();

                if ($mon) {
                    $gia = (float) $mon['gia'];

                    $result['set_mon'][] = [
                        'id' => (int) $mon['id'],
                        'ten_mon' => $mon['ten_mon'],
                        'gia' => $gia,
                        'so_luong' => $qty,
                    ];

                    $result['tong_tien'] += $gia * $qty;
                }
            }

            if (empty($result['set_mon'])) {
                $result['type'] = 'text';
                $result['message'] = 'Mình chưa tìm được set món phù hợp trong menu hiện tại.';
            }
        }

        return $result;
    }

    private function fallbackRecommend(array $menu, string $message): array
    {
        $lower = mb_strtolower($message, 'UTF-8');

        $wantSet =
            str_contains($lower, 'set') ||
            str_contains($lower, 'combo') ||
            str_contains($lower, 'gợi ý') ||
            str_contains($lower, 'ngân sách') ||
            str_contains($lower, 'người');

        if (!$wantSet) {
            return [
                'type' => 'text',
                'message' => 'Mình có thể gợi ý món HOT, món bán chạy hoặc set món theo ngân sách. Ví dụ: "Gợi ý set món cho 3 người khoảng 300k không cay".',
                'set_mon' => [],
                'tong_tien' => 0,
                'ly_do' => ''
            ];
        }

        $budget = 300000;

        if (preg_match('/(\d+)\s*k/i', $message, $m)) {
            $budget = (int) $m[1] * 1000;
        } elseif (preg_match('/(\d{5,7})/', $message, $m)) {
            $budget = (int) $m[1];
        }

        $people = 2;

        if (preg_match('/(\d+)\s*người/i', $message, $m)) {
            $people = max(1, (int) $m[1]);
        }

        $noSpicy = str_contains($lower, 'không cay') || str_contains($lower, 'ko cay');
        $vegetarian = str_contains($lower, 'chay');

        $filtered = array_filter($menu, function ($item) use ($noSpicy, $vegetarian) {
            if ($noSpicy && (int) $item['is_cay'] === 1) {
                return false;
            }

            if ($vegetarian && (int) $item['is_chay'] !== 1) {
                return false;
            }

            return true;
        });

        usort($filtered, function ($a, $b) {
            $scoreA = ((int) $a['tag_hot'] * 1000) + ((int) $a['so_lan_goi'] * 5) + ((float) $a['rating'] * 20);
            $scoreB = ((int) $b['tag_hot'] * 1000) + ((int) $b['so_lan_goi'] * 5) + ((float) $b['rating'] * 20);

            return $scoreB <=> $scoreA;
        });

        $set = [];
        $total = 0;

        foreach ($filtered as $item) {
            $price = (float) $item['gia'];

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

        return [
            'type' => 'recommend_set',
            'message' => 'Mình gợi ý set món này theo menu hiện tại của quán.',
            'set_mon' => $set,
            'tong_tien' => $total,
            'ly_do' => 'Ưu tiên món HOT, món bán chạy, phù hợp ngân sách khoảng ' . number_format($budget, 0, ',', '.') . 'đ.'
        ];
    }

    private function saveHistory(int $banId, string $message, array $result): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO ai_chat_history 
            (ban_id, user_message, ai_response, response_type, tong_tien_goi_y)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $banId,
            $message,
            json_encode($result, JSON_UNESCAPED_UNICODE),
            $result['type'] ?? 'text',
            $result['tong_tien'] ?? 0,
        ]);
    }
}