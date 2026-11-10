<?php

declare(strict_types=1);

namespace App\Showcase\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Datos de ejemplo que usan las plantillas del panel.
 *
 * Las facturas son ficticias y siempre tienen la misma forma, así que se puede
 * comparar el resultado de un motor con el de otro sin que cambien los datos.
 */
final class SampleData
{
    /**
     * @return array<string, mixed>
     */
    public static function invoice(): array
    {
        $lineas = [
            ['concepto' => 'Desarrollo a medida · sprint 14', 'cantidad' => 32, 'precio' => 68.50],
            ['concepto' => 'Integración con la pasarela de pago', 'cantidad' => 18, 'precio' => 72.00],
            ['concepto' => 'Mantenimiento mensual', 'cantidad' => 1, 'precio' => 450.00],
            ['concepto' => 'Formación del equipo (2 sesiones)', 'cantidad' => 2, 'precio' => 240.00],
        ];

        $subtotal = array_sum(array_map(
            static fn (array $linea): float => $linea['cantidad'] * $linea['precio'],
            $lineas,
        ));

        $impuesto = $subtotal * 0.21;
        $total = $subtotal + $impuesto;

        return [
            'empresa' => [
                'nombre' => 'Vellum Fábrica de PDF, S.L.',
                'cif' => 'B12345678',
                'direccion' => 'Calle del Papel 12, 28013 Madrid',
                'email' => 'hola@vellum.test',
                'telefono' => '+34 900 000 000',
            ],
            'cliente' => [
                'nombre' => 'Comercial Atlántica, S.A.',
                'cif' => 'A87654321',
                'direccion' => 'Avenida del Mar 45, 08003 Barcelona',
                'email' => 'administracion@atlantica.test',
            ],
            'numero' => 'F-2026-0042',
            'fecha' => '22/10/2026',
            'vencimiento' => '21/11/2026',
            'lineas' => array_map(
                static function (array $linea): array {
                    $linea['importe'] = $linea['cantidad'] * $linea['precio'];

                    return $linea;
                },
                $lineas,
            ),
            'subtotal' => $subtotal,
            'impuesto' => $impuesto,
            'total' => $total,
            'moneda' => 'EUR',
        ];
    }

    /**
     * Listado de usuarios de ejemplo.
     *
     * La aplicación no tiene base de datos, así que las plantillas del panel
     * siempre se alimentan con datos ficticios y estables.
     *
     * @return list<array{name: string, email: string, alta: string}>
     */
    public static function users(): array
    {
        return self::fallbackUsers();
    }

    /**
     * Usuarios con forma de modelo para las plantillas que usan colecciones.
     *
     * @return Collection<int, object>
     */
    public static function usersForPdf(): Collection
    {
        return collect(self::fallbackUsers())->map(static fn (array $usuario): object => (object) [
            'name' => $usuario['name'],
            'email' => $usuario['email'],
            'email_verified_at' => null,
            'created_at' => Carbon::createFromFormat('d/m/Y', $usuario['alta']),
        ]);
    }

    /**
     * @return list<array{name: string, email: string, alta: string}>
     */
    private static function fallbackUsers(): array
    {
        return [
            ['name' => 'Ana Torres', 'email' => 'ana.torres@ejemplo.test', 'alta' => '02/01/2026'],
            ['name' => 'Bruno Ledesma', 'email' => 'bruno.ledesma@ejemplo.test', 'alta' => '14/03/2026'],
            ['name' => 'Carla Ruiz', 'email' => 'carla.ruiz@ejemplo.test', 'alta' => '27/04/2026'],
            ['name' => 'Diego Marín', 'email' => 'diego.marin@ejemplo.test', 'alta' => '09/06/2026'],
            ['name' => 'Elena Vidal', 'email' => 'elena.vidal@ejemplo.test', 'alta' => '30/07/2026'],
            ['name' => 'Félix Ortega', 'email' => 'felix.ortega@ejemplo.test', 'alta' => '18/09/2026'],
        ];
    }
}
