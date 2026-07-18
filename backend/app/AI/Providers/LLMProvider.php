<?php

namespace App\AI\Providers;

use App\Ai\Agents\FinMateAgent;

class LLMProvider
{
    public function generate(string $prompt): string
    {
        $response = (new FinMateAgent())->prompt($prompt);

        return (string) $response;
    }
}
