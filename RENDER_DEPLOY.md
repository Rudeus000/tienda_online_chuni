# 🚀 Guía de Despliegue en Render

Esta guía te ayudará a desplegar la Tienda Online en [Render](https://render.com).

## 📋 Requisitos Previos

1. ✅ Cuenta en Render (gratis en https://render.com)
2. ✅ Repositorio en GitHub: https://github.com/Rudeus000/tienda_online_chuni.git
3. ✅ Cuenta de Supabase configurada
4. ✅ Variables de entorno listas

## 🚀 Pasos para Desplegar

### Paso 1: Crear un Nuevo Servicio Web en Render

1. **Inicia sesión en Render:**
   - Ve a https://dashboard.render.com
   - Inicia sesión con tu cuenta de GitHub

2. **Crear nuevo Web Service:**
   - Clic en "New +" → "Web Service"
   - Conecta tu repositorio de GitHub
   - Selecciona el repositorio: `Rudeus000/tienda_online_chuni`
   - Haz clic en "Connect"

### Paso 2: Configurar el Servicio

En la página de configuración:

**Configuración básica:**
- **Name:** `tienda-online` (o el nombre que prefieras)
- **Region:** Selecciona la región más cercana (ej: Oregon, US)
- **Branch:** `main`
- **Root Directory:** Deja vacío (o `.` si es requerido)
- **Runtime:** `PHP`
- **Build Command:** `composer install --no-dev --optimize-autoloader`
- **Start Command:** `php -S 0.0.0.0:$PORT -t .`

### Paso 3: Configurar Variables de Entorno

En la sección "Environment Variables", agrega las siguientes:

```
SUPABASE_URL=https://tu-proyecto-id.supabase.co
SUPABASE_ANON_KEY=tu_clave_anon_publica_aqui
SUPABASE_SERVICE_ROLE=tu_service_role_key_aqui
SITE_URL=https://tu-app-name.onrender.com
KEY_CIFRADO=tu_clave_secreta_fuerte_aqui
METODO_CIFRADO=aes-128-cbc
PHP_VERSION=8.1
```

**⚠️ IMPORTANTE:**
- Reemplaza `tu-app-name.onrender.com` con la URL que Render te asigne
- Usa claves fuertes y seguras
- NO compartas estas variables públicamente

### Paso 4: Configurar Opciones Avanzadas

**Plan:**
- **Free:** Para desarrollo/pruebas
- **Starter/Pro:** Para producción

**Health Check Path (opcional):**
- `/` o `/index.php`

**Auto-Deploy:**
- ✅ Marca "Auto-Deploy" si quieres que se actualice automáticamente al hacer push

### Paso 5: Desplegar

1. Haz clic en "Create Web Service"
2. Render comenzará a construir y desplegar tu aplicación
3. Esto puede tardar 5-10 minutos la primera vez
4. Verás los logs en tiempo real

## 🔧 Configuración Post-Despliegue

### 1. Actualizar SITE_URL

Una vez que Render te asigne una URL (ej: `https://tienda-online-xxx.onrender.com`):

1. Ve a "Environment" en el panel de Render
2. Actualiza `SITE_URL` con tu URL de Render
3. Guarda los cambios
4. Render reiniciará automáticamente el servicio

### 2. Configurar Dominio Personalizado (Opcional)

Si tienes un dominio:

1. Ve a "Settings" → "Custom Domains"
2. Agrega tu dominio
3. Configura los registros DNS según las instrucciones de Render
4. Actualiza `SITE_URL` en las variables de entorno

### 3. Configurar Webhooks de Pagos

Para PayPal y Mercado Pago, actualiza las URLs de webhook:

**PayPal:**
- Ve a PayPal Developer Dashboard
- Actualiza la URL de webhook a: `https://tu-app.onrender.com/clases/captura.php`

**Mercado Pago:**
- Ve a Mercado Pago Developers
- Actualiza la URL de webhook a: `https://tu-app.onrender.com/clases/captura_mp.php`

## 📊 Monitoreo

### Ver Logs:

1. Ve al panel de Render
2. Selecciona tu servicio
3. Ve a la pestaña "Logs"
4. Verás logs en tiempo real

### Métricas:

- Render muestra CPU, Memoria y Tráfico en el dashboard
- Plan Free tiene límites de uso

## 🐛 Solución de Problemas

### Error: "Build failed"

**Problema:** El build falló durante `composer install`

**Solución:**
1. Revisa los logs de build
2. Verifica que `composer.json` esté correcto
3. Asegúrate de que todas las extensiones PHP estén disponibles

### Error: "Application Error"

**Problema:** La aplicación no inicia

**Solución:**
1. Revisa los logs de runtime
2. Verifica que todas las variables de entorno estén configuradas
3. Verifica que `SITE_URL` apunte a la URL correcta de Render
4. Revisa que Supabase esté accesible desde Render

### Error: "Database connection failed"

**Problema:** No puede conectar con Supabase

**Solución:**
1. Verifica que `SUPABASE_URL` y `SUPABASE_ANON_KEY` estén correctos
2. Asegúrate de que la IP de Render no esté bloqueada en Supabase
3. Verifica las políticas RLS (Row Level Security) en Supabase

### Problemas con Sesiones

**Problema:** Las sesiones no persisten

**Solución:**
1. Render puede usar múltiples instancias
2. Considera usar Supabase para almacenar sesiones
3. O configura Redis (requiere plan de pago)

### Problemas con Imágenes

**Problema:** Las imágenes no se suben o no se muestran

**Solución:**
1. Render tiene sistema de archivos efímero
2. Configura Supabase Storage para imágenes
3. O usa un servicio externo (Cloudinary, AWS S3)

## 🔄 Actualizaciones

### Desplegar Actualizaciones:

1. Haz push a la rama `main` en GitHub
2. Render detectará automáticamente los cambios
3. Iniciará un nuevo build y deploy
4. El servicio se actualizará automáticamente

### Rollback:

1. Ve a "Events" en el panel de Render
2. Busca el deploy anterior
3. Haz clic en "Manual Deploy" del commit anterior

## 💰 Costos

**Plan Free:**
- ✅ 750 horas/mes gratis
- ✅ SSL automático
- ⚠️ Se "duerme" después de 15 minutos de inactividad
- ⚠️ Puede tardar 30-60 segundos en "despertar"

**Plan Starter ($7/mes):**
- ✅ Siempre activo
- ✅ Sin límites de horas
- ✅ Mejor rendimiento

## 📚 Recursos Adicionales

- [Documentación de Render](https://render.com/docs)
- [PHP en Render](https://render.com/docs/php)
- [Variables de Entorno](https://render.com/docs/environment-variables)

## 🎉 ¡Listo!

Una vez completados estos pasos, tu tienda estará disponible en Render.

**URL de ejemplo:** `https://tienda-online-xxx.onrender.com`

---

**Nota:** Si necesitas ayuda, revisa los logs en Render o consulta la documentación oficial.

