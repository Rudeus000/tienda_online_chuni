# 🚀 Guía de Despliegue - Tienda Online

Esta guía te ayudará a desplegar la tienda online en un servidor de producción.

## 📋 Checklist Pre-Despliegue

- [ ] Todas las dependencias instaladas (`composer install --no-dev`)
- [ ] Archivo `.env` configurado con credenciales de producción
- [ ] Base de datos Supabase configurada y poblada
- [ ] Permisos de archivos correctos
- [ ] Configuración de pagos (PayPal, Mercado Pago) lista
- [ ] Servidor SMTP configurado
- [ ] Imágenes de productos subidas

## 🌐 Opciones de Despliegue

### 1. Hosting Compartido (cPanel, Hostinger, etc.)

#### Pasos:

1. **Subir archivos:**
   ```bash
   # Excluir archivos de desarrollo
   rsync -av --exclude 'vendor' --exclude '.env' --exclude 'test_*.php' --exclude 'debug_*.php' . usuario@servidor:/ruta/public_html/
   ```

2. **Instalar dependencias en el servidor:**
   ```bash
   cd /ruta/public_html/
   composer install --no-dev --optimize-autoloader
   ```

3. **Configurar `.env`:**
   - Crea el archivo `.env` en el servidor
   - Ingresa tus credenciales de producción

4. **Configurar permisos:**
   ```bash
   chmod 755 -R .
   chmod 777 images/productos/
   ```

5. **Configurar PHP:**
   - Versión mínima: PHP 7.4
   - Extensiones requeridas: `openssl`, `json`, `curl`, `mbstring`

### 2. VPS/Cloud (DigitalOcean, AWS, etc.)

#### Configuración inicial:

1. **Instalar PHP y Composer:**
   ```bash
   sudo apt update
   sudo apt install php8.1 php8.1-cli php8.1-fpm php8.1-mysql php8.1-xml php8.1-curl php8.1-mbstring
   sudo apt install composer nginx
   ```

2. **Configurar Nginx:**
   
   Crea `/etc/nginx/sites-available/tienda_online`:
   ```nginx
   server {
       listen 80;
       server_name tu-dominio.com;
       root /var/www/tienda_online;
       index index.php index.html;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
           fastcgi_index index.php;
           fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
           include fastcgi_params;
       }

       location ~ /\. {
           deny all;
       }
   }
   ```

   Habilita el sitio:
   ```bash
   sudo ln -s /etc/nginx/sites-available/tienda_online /etc/nginx/sites-enabled/
   sudo nginx -t
   sudo systemctl reload nginx
   ```

3. **Configurar SSL con Let's Encrypt:**
   ```bash
   sudo apt install certbot python3-certbot-nginx
   sudo certbot --nginx -d tu-dominio.com
   ```

### 3. Plataformas Cloud (Heroku, Railway, Render)

#### Heroku:

1. **Crear `Procfile`:**
   ```
   web: vendor/bin/heroku-php-apache2
   ```

2. **Variables de entorno en Heroku:**
   ```bash
   heroku config:set SUPABASE_URL=tu_url
   heroku config:set SUPABASE_ANON_KEY=tu_key
   # ... otras variables
   ```

3. **Deploy:**
   ```bash
   git push heroku main
   ```

#### Railway/Render:

Similar a Heroku, configurar variables de entorno en el panel y hacer push al repositorio.

## 🔧 Configuración Post-Despliegue

### 1. Variables de Entorno de Producción

Asegúrate de que tu `.env` tenga:

```env
SUPABASE_URL=https://tu-proyecto.supabase.co
SUPABASE_ANON_KEY=tu_clave_produccion
SUPABASE_SERVICE_ROLE=tu_service_role_produccion
SITE_URL=https://tu-dominio.com
KEY_CIFRADO=clave_secreta_fuerte_aqui
METODO_CIFRADO=aes-128-cbc
```

### 2. Optimizaciones

#### Composer:
```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

#### PHP OPcache:
En `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
```

### 3. Seguridad

#### Permisos de archivos:
```bash
# Directorios
find . -type d -exec chmod 755 {} \;

# Archivos
find . -type f -exec chmod 644 {} \;

# Carpeta de imágenes (escribible)
chmod 775 images/productos/
```

#### Proteger `.env`:
```bash
chmod 600 .env
```

#### Configurar `.htaccess` (Apache):
```apache
# Proteger archivos sensibles
<Files ".env">
    Order allow,deny
    Deny from all
</Files>

# Proteger directorios
RedirectMatch 403 ^/vendor/.*$
RedirectMatch 403 ^/config/.*$
```

### 4. Configurar Cron Jobs (si es necesario)

Para tareas programadas:
```bash
# Editar crontab
crontab -e

# Ejemplo: Limpiar sesiones cada día
0 2 * * * php /var/www/tienda_online/clases/limpiar_sesiones.php
```

## 📊 Monitoreo y Logs

### Habilitar logs de errores:

En `php.ini`:
```ini
log_errors = On
error_log = /var/log/php_errors.log
```

### Revisar logs:

```bash
# Errores de PHP
tail -f /var/log/php_errors.log

# Errores de Nginx
tail -f /var/log/nginx/error.log

# Errores de Apache
tail -f /var/log/apache2/error.log
```

## 🔄 Actualizaciones

### Proceso de actualización:

1. **Backup:**
   ```bash
   # Backup de archivos
   tar -czf backup_$(date +%Y%m%d).tar.gz .

   # Backup de base de datos (si aplica)
   # Exporta desde Supabase Dashboard
   ```

2. **Actualizar código:**
   ```bash
   git pull origin main
   composer install --no-dev --optimize-autoloader
   ```

3. **Verificar:**
   - Revisa logs de errores
   - Prueba funcionalidades principales
   - Verifica pagos de prueba

## 🐛 Solución de Problemas Comunes

### Error 500 Internal Server Error:

1. Revisa permisos de archivos
2. Verifica `.env` está configurado
3. Revisa logs de errores del servidor
4. Verifica que todas las dependencias estén instaladas

### Problemas de sesión:

1. Verifica que la carpeta de sesiones tenga permisos de escritura
2. Revisa `session_save_path` en `php.ini`
3. Verifica configuración de cookies en `supabase_config.php`

### Problemas con imágenes:

1. Verifica permisos de `images/productos/`
2. Revisa configuración de `StorageManager` si usas Supabase Storage
3. Verifica que las rutas sean correctas

### Problemas con pagos:

1. Verifica credenciales en panel de administración
2. Revisa que las URLs de callback estén correctas
3. Verifica logs de PayPal/Mercado Pago

## 📞 Soporte

Si tienes problemas durante el despliegue:

1. Revisa los logs de errores
2. Verifica la documentación de Supabase
3. Consulta las issues en GitHub
4. Abre una nueva issue con detalles del error

---

**¡Buena suerte con tu despliegue! 🚀**


