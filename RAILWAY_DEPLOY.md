# 🚂 Guía de Despliegue en Railway

Esta guía te ayudará a desplegar la Tienda Online en [Railway](https://railway.app).

## 📋 Requisitos Previos

1. ✅ Cuenta en Railway (gratis en https://railway.app)
2. ✅ Repositorio en GitHub: https://github.com/Rudeus000/tienda_online_chuni.git
3. ✅ Cuenta de Supabase configurada
4. ✅ Variables de entorno listas

## 🚀 Pasos para Desplegar

### Paso 1: Crear un Nuevo Proyecto en Railway

1. **Inicia sesión en Railway:**
   - Ve a https://railway.app
   - Inicia sesión con tu cuenta de GitHub

2. **Crear nuevo proyecto:**
   - Clic en "New Project"
   - Selecciona "Deploy from GitHub repo"
   - Conecta tu repositorio de GitHub
   - Selecciona el repositorio: `Rudeus000/tienda_online_chuni`

### Paso 2: Configurar Variables de Entorno

En Railway, ve a la pestaña "Variables" y agrega:

```
SUPABASE_URL=https://tu-proyecto-id.supabase.co
SUPABASE_ANON_KEY=tu_clave_anon_publica_aqui
SUPABASE_SERVICE_ROLE=tu_service_role_key_aqui
SITE_URL=https://tu-proyecto-production.up.railway.app
KEY_CIFRADO=tu_clave_secreta_fuerte_aqui
METODO_CIFRADO=aes-128-cbc
```

**⚠️ IMPORTANTE:**
- Railway te asignará una URL automáticamente (ej: `tu-proyecto-production.up.railway.app`)
- Actualiza `SITE_URL` después de que Railway asigne la URL
- Usa claves fuertes y seguras

### Paso 3: Configurar el Servicio

Railway detectará automáticamente que es un proyecto PHP. Verifica:

1. **Settings → General:**
   - **Root Directory:** Deja vacío (o `.`)
   - **Build Command:** `composer install --no-dev --optimize-autoloader`
   - **Start Command:** `php -S 0.0.0.0:$PORT -t .`

2. **Settings → Healthcheck:**
   - **Healthcheck Path:** `/`
   - **Healthcheck Timeout:** 300 (5 minutos)

### Paso 4: Desplegar

1. Railway comenzará automáticamente el despliegue
2. Puedes ver los logs en tiempo real en la pestaña "Deployments"
3. El despliegue tomará 2-5 minutos

## 🔧 Solución de Problemas

### Error: "Healthcheck failed"

**Síntoma:** El healthcheck falla y el servicio no inicia.

**Soluciones:**

1. **Verificar variables de entorno:**
   - Asegúrate de que todas las variables estén configuradas
   - Especialmente `SUPABASE_URL` y `SUPABASE_ANON_KEY`

2. **Verificar logs:**
   - Ve a "Deployments" → Selecciona el deployment fallido
   - Revisa los logs para ver el error exacto

3. **Verificar conexión a Supabase:**
   - Asegúrate de que Supabase esté accesible desde Railway
   - Verifica las políticas RLS (Row Level Security) en Supabase

4. **Verificar rutas:**
   - Asegúrate de que `index.php` esté en la raíz del proyecto
   - Verifica que las rutas relativas funcionen correctamente

### Error: "Composer install failed"

**Solución:**
- Verifica que `composer.json` esté correcto
- Asegúrate de que todas las dependencias sean compatibles con PHP 8.4

### El servicio inicia pero muestra error 500

**Soluciones:**

1. **Verificar logs:**
   - Los logs de Railway mostrarán el error PHP exacto
   - Revisa la pestaña "Logs" en tiempo real

2. **Verificar variables de entorno:**
   - Asegúrate de que `.env` no sea necesario (usa variables de Railway)
   - Verifica que todas las constantes estén definidas

3. **Verificar conexión a Supabase:**
   - Prueba la conexión manualmente
   - Verifica las credenciales

### Problemas con rutas

**Síntoma:** Las rutas no funcionan correctamente.

**Solución:**
- Railway sirve desde la raíz del proyecto
- Asegúrate de que las rutas en tu código sean relativas o usen `SITE_URL`
- Verifica que `index.php` sea el punto de entrada principal

## 📊 Monitoreo

### Ver Logs:

1. Ve al dashboard de Railway
2. Selecciona tu servicio
3. Ve a la pestaña "Logs"
4. Verás logs en tiempo real

### Métricas:

- Railway muestra CPU, Memoria y Tráfico en el dashboard
- Plan gratuito tiene límites de uso

## 🔄 Actualizaciones

### Desplegar Actualizaciones:

1. Haz push a la rama `main` en GitHub
2. Railway detectará automáticamente los cambios
3. Iniciará un nuevo build y deploy
4. El servicio se actualizará automáticamente

### Rollback:

1. Ve a "Deployments"
2. Busca el deployment anterior
3. Haz clic en "Redeploy"

## 💰 Costos

**Plan Hobby (Gratis):**
- ✅ $5 de crédito gratis/mes
- ✅ SSL automático
- ✅ Despliegue automático
- ⚠️ Se suspende después de usar el crédito

**Plan Pro ($20/mes):**
- ✅ Créditos ilimitados
- ✅ Mejor rendimiento
- ✅ Soporte prioritario

## 📝 Notas Importantes

1. **Variables de entorno:**
   - Railway no lee archivos `.env`
   - Usa las variables de entorno de Railway

2. **Rutas:**
   - Railway sirve desde la raíz del proyecto
   - Asegúrate de que `index.php` sea accesible

3. **PHP Version:**
   - Railway detecta automáticamente PHP 8.4
   - Asegúrate de que tu código sea compatible

4. **Healthcheck:**
   - El healthcheck debe responder en `/`
   - Timeout de 5 minutos por defecto

## 🔐 Configuración Post-Despliegue

### 1. Actualizar SITE_URL

Una vez que Railway te asigne una URL:

1. Ve a "Variables" en Railway
2. Actualiza `SITE_URL` con tu URL de Railway
3. Guarda los cambios
4. Railway redeployará automáticamente

### 2. Configurar Dominio Personalizado (Opcional)

Si tienes un dominio:

1. Ve a "Settings" → "Domains"
2. Agrega tu dominio personalizado
3. Configura los registros DNS según las instrucciones de Railway
4. Actualiza `SITE_URL` en las variables de entorno

### 3. Configurar Webhooks de Pagos

Para PayPal y Mercado Pago, actualiza las URLs de webhook:

**PayPal:**
- Ve a PayPal Developer Dashboard
- Actualiza la URL de webhook a: `https://tu-dominio.railway.app/clases/captura.php`

**Mercado Pago:**
- Ve a Mercado Pago Developers
- Actualiza la URL de webhook a: `https://tu-dominio.railway.app/clases/captura_mp.php`

## 📚 Recursos Adicionales

- [Documentación de Railway](https://docs.railway.app)
- [PHP en Railway](https://docs.railway.app/languages/php)
- [Variables de Entorno](https://docs.railway.app/develop/variables)

## 🎉 ¡Listo!

Una vez completados estos pasos, tu tienda estará disponible en Railway.

**URL de ejemplo:** `https://tu-proyecto-production.up.railway.app`

---

**Nota:** Si necesitas ayuda, revisa los logs en Railway o consulta la documentación oficial.

