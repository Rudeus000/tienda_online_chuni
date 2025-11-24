<?php

namespace App;

use Supabase\Storage\StorageClient;
use Exception;

class StorageManager {
    private $storage;
    private $bucketName = 'productos';

    public function __construct() {
        // Solo cargar .env si existe (Railway usa variables de entorno directamente)
        if (file_exists(__DIR__ . '/../.env')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
            $dotenv->load();
        }

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
            
            $bucketsData = method_exists($buckets, 'getData') ? $buckets->getData() : (property_exists($buckets, 'data') ? $buckets->data : []);
            foreach ($bucketsData as $bucket) {
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

    /**
     * Sube una imagen de producto a Supabase Storage
     * @param string|resource $archivo Ruta local al archivo o recurso de archivo subido
     * @param int $productoId ID del producto
     * @param string $nombreArchivo Nombre del archivo (ej: 'principal.jpg' o '1.jpg')
     * @return string|false URL pública de la imagen o false en caso de error
     */
    public function subirImagenProducto($archivo, $productoId, $nombreArchivo) {
        try {
            $rutaStorage = "productos/{$productoId}/{$nombreArchivo}";
            
            // Manejar tanto archivos subidos (tmp_name) como rutas locales
            if (is_string($archivo) && file_exists($archivo)) {
                $fileContent = file_get_contents($archivo);
            } elseif (is_resource($archivo)) {
                // Si es un recurso, leerlo
                rewind($archivo);
                $fileContent = stream_get_contents($archivo);
            } else {
                // Asumir que es el contenido directo o tmp_name
                $fileContent = is_string($archivo) && file_exists($archivo) 
                    ? file_get_contents($archivo) 
                    : $archivo;
            }
            
            if ($fileContent === false || empty($fileContent)) {
                throw new Exception('No se pudo leer el contenido del archivo');
            }
            
            $result = $this->storage
                ->from($this->bucketName)
                ->upload($rutaStorage, $fileContent, [
                    'cacheControl' => '3600',
                    'upsert' => true,
                    'contentType' => 'image/jpeg'
                ]);
            
            // Verificar si hay error en la respuesta
            if (is_array($result) && isset($result['error'])) {
                throw new Exception($result['error']);
            }
            
            return $this->getUrlPublica("productos/{$productoId}/{$nombreArchivo}");
        } catch (Exception $e) {
            error_log('Error al subir imagen de producto: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina una imagen de producto de Supabase Storage
     * @param int $productoId ID del producto
     * @param string $nombreArchivo Nombre del archivo (ej: 'principal.jpg')
     * @return bool true si se eliminó correctamente, false en caso contrario
     */
    public function eliminarImagenProducto($productoId, $nombreArchivo) {
        try {
            $rutaStorage = "productos/{$productoId}/{$nombreArchivo}";
            $result = $this->storage
                ->from($this->bucketName)
                ->remove([$rutaStorage]);
                
            return !(is_array($result) && isset($result['error']));
        } catch (Exception $e) {
            error_log('Error al eliminar imagen de producto: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene la URL pública de una imagen de producto
     * @param int $productoId ID del producto
     * @param string $nombreArchivo Nombre del archivo (ej: 'principal.jpg')
     * @return string URL pública de la imagen
     */
    public function getUrlImagenProducto($productoId, $nombreArchivo) {
        $base = rtrim($_ENV['SUPABASE_URL'] ?? '', '/');
        $rutaStorage = "productos/{$productoId}/{$nombreArchivo}";
        return sprintf('%s/storage/v1/object/public/%s/%s', 
            $base,
            $this->bucketName,
            $rutaStorage
        );
    }

    /**
     * Lista todas las imágenes de un producto
     * @param int $productoId ID del producto
     * @return array Array de nombres de archivos
     */
    public function listarImagenesProducto($productoId) {
        try {
            $rutaCarpeta = "productos/{$productoId}";
            $result = $this->storage
                ->from($this->bucketName)
                ->list($rutaCarpeta);
            
            $imagenes = [];
            $data = method_exists($result, 'getData') ? $result->getData() : (is_array($result) ? $result : []);
            
            if (is_array($data)) {
                foreach ($data as $item) {
                    if (isset($item['name'])) {
                        $imagenes[] = $item['name'];
                    }
                }
            }
            
            return $imagenes;
        } catch (Exception $e) {
            error_log('Error al listar imágenes de producto: ' . $e->getMessage());
            return [];
        }
    }

    // Métodos legacy para compatibilidad
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
        $base = rtrim($_ENV['SUPABASE_URL'] ?? '', '/');
        return sprintf('%s/storage/v1/object/public/%s/%s', 
            $base,
            $this->bucketName,
            ltrim($ruta, '/\\')
        );
    }
}
