# 🔧 Solución al Error de Healthcheck en Railway

## Problema

El healthcheck falla porque Railway está intentando acceder a `/` que carga toda la aplicación (incluyendo Supabase), y puede haber errores de inicialización.

## Solución

### Paso 1: Verificar que healthcheck.php existe

El archivo `healthcheck.php` debe estar en la raíz del proyecto y debe responder sin cargar Supabase.

### Paso 2: Configurar Healthcheck en Railway

1. Ve a tu proyecto en Railway
2. Clic en tu servicio
3. Ve a **Settings** → **Healthcheck**
4. Configura:
   - **Healthcheck Path:** `/healthcheck.php`
   - **Healthcheck Timeout:** `300` (5 minutos)

### Paso 3: Verificar Variables de Entorno

Asegúrate de que estas variables estén configuradas:

```
SUPABASE_URL=https://tu-proyecto-id.supabase.co
SUPABASE_ANON_KEY=tu_clave_anon_publica
KEY_CIFRADO=ABCD.1234-
METODO_CIFRADO=aes-128-cbc
```

### Paso 4: Verificar Logs

1. Ve a **Deployments** en Railway
2. Selecciona el deployment fallido
3. Revisa los logs para ver el error exacto

## Errores Comunes

### Error: "Service unavailable"

**Causa:** El servidor PHP no está iniciando correctamente o hay un error fatal.

**Solución:**
1. Revisa los logs de Railway
2. Verifica que todas las variables de entorno estén configuradas
3. Verifica que `composer install` se haya completado correctamente

### Error: "Connection refused"

**Causa:** El servidor no está escuchando en el puerto correcto.

**Solución:**
- Verifica que el Start Command sea: `php -S 0.0.0.0:$PORT -t .`
- Railway asigna el puerto automáticamente con `$PORT`

### Error en healthcheck.php

**Causa:** El archivo healthcheck.php tiene un error.

**Solución:**
- Verifica que `healthcheck.php` esté en la raíz del proyecto
- Debe responder con JSON sin cargar Supabase

## Verificación Manual

Puedes verificar manualmente si el healthcheck funciona:

1. Despliega el proyecto
2. Espera a que Railway asigne una URL
3. Visita: `https://tu-url.railway.app/healthcheck.php`
4. Deberías ver: `{"status":"ok","service":"tienda-online",...}`

## Si el Problema Persiste

1. **Revisa los logs completos** en Railway
2. **Verifica que todas las dependencias estén instaladas** (`composer install`)
3. **Prueba acceder directamente a `/healthcheck.php`** en tu navegador
4. **Verifica que las variables de entorno estén correctamente configuradas**

## Comando de Inicio Alternativo

Si el problema persiste, puedes intentar usar este Start Command:

```
php -d display_errors=1 -S 0.0.0.0:$PORT -t .
```

Esto mostrará errores PHP directamente en los logs.

