# Instrucciones para Ejecutar el Sistema

## 🚀 Cómo Correr el Sistema

### Opción 1: Servidor PHP Built-in (Recomendado para desarrollo)

1. **Abrir una terminal en la carpeta del proyecto:**
   ```bash
   cd C:\Users\User\Desktop\tienda_online
   ```

2. **Iniciar el servidor PHP:**
   ```bash
   php -S localhost:8000
   ```

3. **Abrir en el navegador:**
   - **Tienda (Cliente):** http://localhost:8000/
   - **Admin:** http://localhost:8000/admin/

### Opción 2: XAMPP/WAMP (Producción)

1. **Copiar la carpeta al directorio del servidor:**
   - XAMPP: `C:\xampp\htdocs\tienda_online`
   - WAMP: `C:\wamp64\www\tienda_online`

2. **Iniciar Apache desde el panel de control**

3. **Abrir en el navegador:**
   - **Tienda (Cliente):** http://localhost/tienda_online/
   - **Admin:** http://localhost/tienda_online/admin/

## 📝 Configuración de URLs

Las URLs están configuradas en `config/supabase_config.php`:

- **SITE_URL**: URL base de la tienda
- **ADMIN_URL**: URL del panel de administración (se construye automáticamente como `SITE_URL . 'admin/'`)

### Para Servidor PHP Built-in (puerto 8000):
- SITE_URL: `http://localhost:8000/`
- ADMIN_URL: `http://localhost:8000/admin/`

### Para XAMPP/WAMP:
- SITE_URL: `http://localhost/tienda_online/`
- ADMIN_URL: `http://localhost/tienda_online/admin/`

## 🔑 Credenciales de Prueba

### Administrador:
- **Usuario:** `admin` o `Rudeus`
- **Contraseña:** `admin` (o la que configuraste)

### Cliente:
- Necesitas crear un usuario desde: http://localhost:8000/registro.php
- O usar el script: http://localhost:8000/crear_usuario_prueba.php

## ⚠️ Notas Importantes

1. **El servidor PHP built-in NO soporta .htaccess**, por lo que:
   - Las URLs amigables pueden no funcionar
   - Usa `details.php?slug=nombre-producto` en lugar de `details/nombre-producto`

2. **Para producción**, usa Apache/Nginx con soporte para .htaccess

3. **Variables de entorno**: El sistema usa un archivo `.env` para configuración sensible

## 🛠️ Solución de Problemas

### Si las rutas del admin no funcionan:
1. Verifica que `SITE_URL` esté correctamente configurado
2. Asegúrate de que `ADMIN_URL` se defina correctamente
3. Revisa que el servidor esté corriendo en el puerto correcto

### Si hay errores 500:
1. Revisa los logs de PHP
2. Verifica que todas las dependencias estén instaladas (`composer install`)
3. Asegúrate de que el archivo `.env` tenga las credenciales de Supabase


