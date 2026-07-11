<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AIChatRequest;
use App\Services\AIChatService;
use Illuminate\Http\Request;

class AIChatController extends Controller
{
    private AIChatService $aiChatService;

    public function __construct(AIChatService $aiChatService)
    {
        $this->aiChatService = $aiChatService;
    }

    public function chat(AIChatRequest $request)
    {
        $response = $this->aiChatService->generateResponse(
            $request->user(),
            $request->message
        );

        return response()->json([
            'success' => true,
            'response' => $response,
        ]);
    }

}
