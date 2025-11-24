<?php

namespace App;

use Supabase\CreateClient;

class Database {
    private static $instance = null;
    private $supabase;
    private $lastInsertId = null;

    private function __construct() {
        // Cargar variables de entorno
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();

        // Inicializar el cliente de Supabase
        $supabaseUrl = $_ENV['SUPABASE_URL'] ?? null;
        $supabaseKey = $_ENV['SUPABASE_ANON_KEY'] ?? ($_ENV['SUPABASE_KEY'] ?? null);

        if (!$supabaseUrl || !$supabaseKey) {
            throw new \RuntimeException('Faltan variables de entorno SUPABASE_URL o SUPABASE_ANON_KEY/SUPABASE_KEY');
        }

        $referenceId = $this->extractReferenceId($supabaseUrl);
        $domain = $this->extractDomain($supabaseUrl);

        $this->supabase = new CreateClient($supabaseKey, $referenceId, [], $domain);
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function conectar() {
        return $this->supabase;
    }

    // Métodos CRUD

    public function select($tabla, $columnas = '*', $filtros = []) {
        try {
            $query = $this->supabase->from($tabla)->select($columnas);

            foreach ($filtros as $campo => $valor) {
                $query->eq($campo, $valor);
            }

            $result = $query->execute();
            return $result->getData();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function insert($tabla, $datos) {
        try {
            $result = $this->supabase->from($tabla)
                ->insert([$datos])
                ->select()  // Seleccionar los datos insertados para obtener el ID
                ->execute();

            $data = $result->getData();
            if (!empty($data) && isset($data[0]['id'])) {
                $this->lastInsertId = $data[0]['id'];
                return $data[0];
            }
            $errorData = $result->getData();
            $errorInfo = method_exists($result, 'getError') ? $result->getError() : null;
            error_log('Insert: No se pudo obtener el ID. Data: ' . print_r($data, true));
            if ($errorInfo) {
                error_log('Error detallado: ' . print_r($errorInfo, true));
            }
            return null;
        } catch (\Throwable $e) {
            error_log('Error en insert: ' . $e->getMessage());
            error_log('Error en insert - Tabla: ' . $tabla);
            error_log('Error en insert - Datos: ' . print_r($datos, true));
            if (method_exists($e, 'getResponse')) {
                $response = $e->getResponse();
                if ($response) {
                    error_log('Error Response: ' . $response->getBody()->getContents());
                }
            }
            return null;
        }
    }

    public function update($tabla, $datos, $columnaId, $id) {
        try {
            $result = $this->supabase->from($tabla)
                ->update($datos)
                ->eq($columnaId, $id)
                ->execute();

            $data = $result->getData();
            return !empty($data) ? $data[0] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function delete($tabla, $columnaId, $id) {
        try {
            $result = $this->supabase->from($tabla)
                ->delete()
                ->eq($columnaId, $id)
                ->execute();

            return $result->getData();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function query($sql, $params = []) {
        try {
            $result = $this->supabase->rpc('execute_sql', [
                'query' => $sql,
                'params' => $params
            ])->execute();

            return $result->getData();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Obtener un solo registro por filtro
     */
    public function selectOne($tabla, $columnas = '*', $filtros = []) {
        try {
            $query = $this->supabase->from($tabla)->select($columnas);
            
            foreach ($filtros as $campo => $valor) {
                $query->eq($campo, $valor);
            }
            
            $result = $query->limit(1)->execute();
            $data = $result->getData();
            
            if (is_array($data) && !empty($data)) {
                return $data[0]; // Return the first element if it's an array
            }
            return null;
        } catch (\Throwable $e) {
            error_log('Error en selectOne: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Simular lastInsertId - retorna el ID del último insert
     */
    private function extractReferenceId(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        $parts = explode('.', $host);
        return $parts[0] ?? '';
    }

    private function extractDomain(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        $parts = explode('.', $host, 2);
        return $parts[1] ?? 'supabase.co';
    }
    
    public function setLastInsertId($id) {
        $this->lastInsertId = $id;
    }
    
    public function lastInsertId() {
        return $this->lastInsertId;
    }
}
