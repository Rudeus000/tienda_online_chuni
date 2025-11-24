<?php

namespace App;

use Supabase\Storage\StorageClient;
use Exception;

class StorageManager {
    private $storage;
    private $bucketName = 'productos';

    public function __construct() {
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();

        $base = trim($_ENV['SUPABASE_URL'] ?? '');
        // Corregir casos como "https//..." (falta el :) o sin esquema
        if ($base !== '') {
            $base = rtrim($base, '/');
            if (strpos($base, 'http://') !== 0 && strpos($base, 'https://') !== 0) {
                // Si empieza con "https//" sin ":", arréglalo
                if (strpos($base, 'https//') === 0) {
                    $base = 'https://' . substr($base, strlen('https//'));
                } elseif (strpos($base, 'http//') === 0) {
                    $base = 'http://' . substr($base, strlen('http//'));
                } else {
                    $base = 'https://' . ltrim($base, '/');
                }
            }
        }

        $storageUrl = $base . '/storage/v1';
        // Priorizar SERVICE_ROLE para operaciones administrativas; de lo contrario usar ANON/KEY
        $serviceRole = $_ENV['SUPABASE_SERVICE_ROLE'] ?? null;
        $apiKey = $serviceRole ?: ($_ENV['SUPABASE_ANON_KEY'] ?? ($_ENV['SUPABASE_KEY'] ?? null));

        if (!$apiKey || !$base) {
            throw new Exception('Faltan variables de entorno SUPABASE_URL o SUPABASE_ANON_KEY/SUPABASE_KEY para Storage');
        }

        $this->storage = new StorageClient($apiKey, $storageUrl);

        // Solo intentar administrar buckets si contamos con SERVICE_ROLE
        if ($serviceRole) {
            $this->initializeBucket();
        }
    }

    private function initializeBucket() {
        try {
            $buckets = $this->storage->listBuckets();
            $bucketExists = false;
            
            foreach ($buckets->getData() as $bucket) {
                if ($bucket->getName() === $this->bucketName) {
                    $bucketExists = true;
                    break;
                }
            }
            
            if (!$bucketExists) {
                $this->storage->createBucket($this->bucketName, [
                    'public' => true,
                    'allowedMimeTypes' => ['image/png', 'image/jpeg', 'image/gif'],
                    'fileSizeLimit' => 5 * 1024 * 1024 // 5MB
                ]);
            }
        } catch (Exception $e) {
            error_log('Error al inicializar el bucket: ' . $e->getMessage());
            // No propagar para no interrumpir la app si la clave no tiene permisos
            return;
        }
    }

    public function subirImagen($rutaLocal, $nombreArchivo) {
        try {
            $rutaStorage = "productos/{$nombreArchivo}";
            
            $fileContent = file_get_contents($rutaLocal);
            if ($fileContent === false) {
                throw new Exception('No se pudo leer el archivo: ' . $rutaLocal);
            }
            
            $result = $this->storage
                ->from($this->bucketName)
                ->upload($rutaStorage, $fileContent, [
                    'cacheControl' => '3600',
                    'upsert' => true
                ]);
            
            if (isset($result['error'])) {
                throw new Exception($result['error']);
            }
            
            return $this->getUrlPublica($rutaStorage);
        } catch (Exception $e) {
            error_log('Error al subir imagen: ' . $e->getMessage());
            return false;
        }
    }

    public function eliminarImagen($nombreArchivo) {
        try {
            $rutaStorage = "productos/{$nombreArchivo}";
            $result = $this->storage
                ->from($this->bucketName)
                ->remove([$rutaStorage]);
                
            return !isset($result['error']);
        } catch (Exception $e) {
            error_log('Error al eliminar imagen: ' . $e->getMessage());
            return false;
        }
    }

    public function getUrlPublica($ruta) {
        return sprintf('%s/storage/v1/object/public/%s/%s', 
            rtrim($_ENV['SUPABASE_URL'], '/'),
            $this->bucketName,
            ltrim($ruta, '/\\')
        );
    }
}
