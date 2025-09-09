<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;


/*====>>>COMANDO ELIMINAR ARCHIVOS DUPLICADOS<<<=====
1. Ejecutar en terminal: php artisan comprobantes:clean-duplicates
2. Existe programa en Console\Kernel.php para ejecucion automatica (semanal o diaria)
->Este comando elimina los archivos duplicados en la carpeta
temp_comprobantes, se usa el mas reciente*
*/

class CleanDuplicateComprobantes extends Command
{
    protected $signature = 'comprobantes:clean-duplicates';
    protected $description = 'Limpia archivos duplicados de comprobantes manteniendo solo el más reciente por guía';

    public function handle()
    {
        $this->info('Iniciando limpieza de comprobantes duplicados...');

        $directorio = public_path('temp_comprobantes');

        if (!File::exists($directorio)) {
            $this->warn('El directorio temp_comprobantes no existe');
            return;
        }

        $archivos = File::files($directorio);
        $archivosLimpieza = [];
        $totalArchivos = count($archivos);

        $this->info("Analizando {$totalArchivos} archivos...");

        // Agrupar archivos por número de guía
        foreach ($archivos as $archivo) {
            $nombre = $archivo->getFilename();

            // Extraer número de guía del patrón: comprobante_NUMERO_TIMESTAMP.ext
            if (preg_match('/comprobante_(\d+)_\d{8}_\d{6}\./', $nombre, $matches)) {
                $numeroGuia = $matches[1];
                $archivosLimpieza[$numeroGuia][] = [
                    'path' => $archivo->getPathname(),
                    'time' => $archivo->getMTime(),
                    'name' => $nombre,
                    'size' => $archivo->getSize()
                ];
            }
        }

        $duplicadosEliminados = 0;
        $espacioLiberado = 0;

        // Procesar cada grupo de archivos por guía
        foreach ($archivosLimpieza as $guia => $archivos) {
            if (count($archivos) > 1) {
                $this->line("Procesando guía {$guia}: " . count($archivos) . " archivos encontrados");

                // Ordenar por tiempo de modificación, el más reciente primero
                usort($archivos, fn($a, $b) => $b['time'] <=> $a['time']);

                // Mantener el más reciente
                $mantener = array_shift($archivos);
                $this->info(" Manteniendo: {$mantener['name']}");

                // Eliminar los demás
                foreach ($archivos as $archivo) {
                    try {
                        File::delete($archivo['path']);
                        $duplicadosEliminados++;
                        $espacioLiberado += $archivo['size'];
                        $this->warn("  Eliminado: {$archivo['name']}");

                        Log::info('COMPROBANTE DUPLICADO ELIMINADO', [
                            'archivo' => $archivo['name'],
                            'guia' => $guia,
                            'size_bytes' => $archivo['size']
                        ]);
                    } catch (\Exception $e) {
                        $this->error("  Error eliminando {$archivo['name']}: {$e->getMessage()}");
                    }
                }
            }
        }

        $espacioLiberadoMB = round($espacioLiberado / 1024 / 1024, 2);

        $this->info("\n Resumen de limpieza:");
        $this->info("• Archivos duplicados eliminados: {$duplicadosEliminados}");
        $this->info("• Espacio liberado: {$espacioLiberadoMB} MB");
        $this->info("• Guías únicas procesadas: " . count($archivosLimpieza));

        Log::info('LIMPIEZA DE DUPLICADOS COMPLETADA', [
            'duplicados_eliminados' => $duplicadosEliminados,
            'espacio_liberado_mb' => $espacioLiberadoMB,
            'guias_procesadas' => count($archivosLimpieza)
        ]);

        $this->info(' Limpieza completada exitosamente!');
    }
}
