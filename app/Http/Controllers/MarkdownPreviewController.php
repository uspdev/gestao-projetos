<?php

namespace App\Http\Controllers;

use App\Services\MarkdownRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MarkdownPreviewController extends Controller
{
    public function __invoke(Request $request, MarkdownRenderer $renderer): Response
    {
        $validated = $request->validate([
            'markdown' => ['nullable', 'string', 'max:10000'],
        ]);

        return response($renderer->render($validated['markdown'] ?? ''), 200)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
