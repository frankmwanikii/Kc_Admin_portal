<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Services\ConnectFormService;

class ConnectFormController
{
    public function submit(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        $payload = json_decode(file_get_contents('php://input') ?: '', true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        $formType = (string) ($payload['form'] ?? '');
        $data = $payload['data'] ?? [];

        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Invalid submission.']);
            return;
        }

        $result = ConnectFormService::handle($formType, $data);

        if (!$result['ok']) {
            http_response_code(422);
        }

        echo json_encode($result);
    }
}
