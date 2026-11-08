<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Datos de ejemplo para las demos de usuarios.
     *
     * No usa factories para poder ejecutarse con `composer install --no-dev`
     * (Faker solo está en require-dev).
     */
    public function run(): void
    {
        $usuarios = [
            ['name' => 'Ana Torres', 'email' => 'ana.torres@ejemplo.test'],
            ['name' => 'Bruno Ledesma', 'email' => 'bruno.ledesma@ejemplo.test'],
            ['name' => 'Carla Ruiz', 'email' => 'carla.ruiz@ejemplo.test'],
            ['name' => 'Diego Marín', 'email' => 'diego.marin@ejemplo.test'],
            ['name' => 'Elena Vidal', 'email' => 'elena.vidal@ejemplo.test'],
            ['name' => 'Félix Ortega', 'email' => 'felix.ortega@ejemplo.test'],
        ];

        foreach ($usuarios as $datos) {
            $usuario = User::query()->firstOrNew(['email' => $datos['email']]);

            $usuario->name = $datos['name'];
            $usuario->email_verified_at ??= now();

            if (! $usuario->exists) {
                $usuario->password = 'password';
            }

            $usuario->save();
        }
    }
}
