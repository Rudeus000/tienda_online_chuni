# 🔧 Variables de Entorno para Railway

## Variables Requeridas (Configurar ANTES del despliegue)

Estas variables son **obligatorias** y debes configurarlas antes del primer despliegue:

```
SUPABASE_URL=https://tu-proyecto-id.supabase.co
SUPABASE_ANON_KEY=tu_clave_anon_publica_aqui
KEY_CIFRADO=ABCD.1234-
METODO_CIFRADO=aes-128-cbc
```

## Variables Opcionales

### SUPABASE_SERVICE_ROLE (Opcional)

**NO es obligatorio configurar `SUPABASE_SERVICE_ROLE`.**

El código funciona perfectamente solo con `SUPABASE_ANON_KEY`.

**¿Cuándo necesitas SUPABASE_SERVICE_ROLE?**
- Solo si quieres usar Supabase Storage para subir imágenes de productos
- Para operaciones administrativas avanzadas de Supabase
- Si no lo configuras, el sistema usará `SUPABASE_ANON_KEY` automáticamente

**Si quieres configurarlo:**
```
SUPABASE_SERVICE_ROLE=tu_service_role_key_aqui
```

**Nota sobre KEY_CIFRADO:**
- Puedes usar el valor por defecto: `ABCD.1234-` (para desarrollo/pruebas)
- **Para producción, se recomienda usar una clave más fuerte** (mínimo 16 caracteres)
- El código tiene un valor por defecto si no se configura: `ABCD.1234-`

## Variables Opcionales

### SITE_URL (Opcional - Se detecta automáticamente)

**NO es necesario configurar `SITE_URL` inicialmente.**

El código detecta automáticamente la URL desde Railway usando `$_SERVER['HTTP_HOST']`.

**Si quieres configurarla manualmente:**

1. Despliega el proyecto primero
2. Railway te asignará una URL automáticamente (ej: `tu-proyecto-production.up.railway.app`)
3. Ve a Variables en Railway
4. Agrega:
   ```
   SITE_URL=https://tu-proyecto-production.up.railway.app
   ```
5. Railway redeployará automáticamente

**¿Cuándo configurar SITE_URL manualmente?**
- Si quieres usar un dominio personalizado
- Si necesitas una URL específica para webhooks de pagos
- Si el auto-detección no funciona correctamente

## Cómo Obtener las Credenciales

### Supabase

1. Ve a https://app.supabase.com
2. Selecciona tu proyecto
3. Ve a Settings → API
4. Copia:
   - **Project URL** → `SUPABASE_URL`
   - **anon public** key → `SUPABASE_ANON_KEY`
   - **service_role** key → `SUPABASE_SERVICE_ROLE`

### KEY_CIFRADO

**Valor por defecto:** `ABCD.1234-` (ya configurado en el código)

**Opciones:**
1. **Usar el valor por defecto** (para desarrollo/pruebas):
   ```
   KEY_CIFRADO=ABCD.1234-
   ```

2. **Generar una clave más fuerte** (recomendado para producción):
   - Mínimo 16 caracteres
   - Puedes generar una con: `openssl rand -base64 32`
   - O usa un generador online de claves seguras
   - Ejemplo: `KEY_CIFRADO=MiClaveSuperSecreta2024!@#$`

**⚠️ IMPORTANTE:** Si cambias la clave después de que la aplicación esté en uso, los datos cifrados anteriormente no se podrán descifrar.

## Configurar en Railway

1. Ve a tu proyecto en Railway
2. Clic en la pestaña "Variables"
3. Haz clic en "New Variable"
4. Agrega cada variable una por una
5. Guarda los cambios
6. Railway redeployará automáticamente

## Verificar que Funcionan

Después del despliegue, puedes verificar que las variables estén configuradas correctamente:

1. Ve a los logs de Railway
2. Busca mensajes de error relacionados con variables de entorno
3. O visita `/healthcheck.php` para verificar que la aplicación inicia correctamente

## Ejemplo Completo

```
SUPABASE_URL=https://abcdefghijklmnop.supabase.co
SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImFiY2RlZmdoaWprbG1ub3AiLCJyb2xlIjoiYW5vbiIsImlhdCI6MTYxNjIzOTAyMiwiZXhwIjoxOTMxODE1MDIyfQ.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
SUPABASE_SERVICE_ROLE=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImFiY2RlZmdoaWprbG1ub3AiLCJyb2xlIjoic2VydmljZV9yb2xlIiwiaWF0IjoxNjE2MjM5MDIyLCJleHAiOjE5MzE4MTUwMjJ9.yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy
KEY_CIFRADO=ABCD.1234-
METODO_CIFRADO=aes-128-cbc
```

**Nota:** `KEY_CIFRADO=ABCD.1234-` es el valor por defecto. Puedes usarlo tal cual o cambiarlo por una clave más fuerte.

---

**Recuerda:** `SITE_URL` es opcional y se detecta automáticamente. Solo configúrala si necesitas una URL específica.

