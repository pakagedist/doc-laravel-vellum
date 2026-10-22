<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Documents\DocumentOptions;
use App\Documents\DocumentRenderer;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

final class DocumentController extends Controller
{
    public function __construct(private readonly DocumentRenderer $renderer) {}

    /**
     * Listado de usuarios en PDF.
     */
    public function users(): Response
    {
        $users = User::query()->orderBy('name')->get();

        return $this->renderer->download('documents.users', [
            'users' => $users,
            'generatedAt' => now(),
        ], new DocumentOptions(
            filename: 'usuarios-'.now()->format('Y-m-d').'.pdf',
            metadata: [
                'title' => 'Listado de usuarios',
                'author' => (string) config('app.name'),
                'subject' => 'Usuarios registrados en la aplicación',
            ],
        ));
    }
}
